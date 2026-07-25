@extends('layouts/layoutMaster')

@section('title', 'اختبار إرسال OTP — ساعي')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
    <style>
        .otp-input-wrapper {
            display: flex;
            gap: 10px;
            direction: ltr;
        }
        .otp-digit {
            width: 48px;
            height: 54px;
            text-align: center;
            font-size: 1.25rem;
            font-weight: 700;
            border-radius: 8px;
            border: 1px solid #d9dee3;
            color: #566a7f;
            transition: all 0.2s;
        }
        .otp-digit:focus {
            border-color: #696cff;
            box-shadow: 0 0 0 0.15rem rgba(105, 108, 255, 0.1);
            outline: 0;
        }
        .otp-digit.filled {
            border-color: #71dd37;
            background-color: rgba(113, 221, 55, 0.05);
        }
        
        /* ━━ WhatsApp preview box ━━ */
        .wa-preview-box {
            background-color: #e5ddd5;
            background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
            background-repeat: repeat;
            border-radius: 8px;
            padding: 20px;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .wa-bubble-in {
            background-color: #ffffff;
            border-radius: 7.5px;
            padding: 12px;
            position: relative;
            max-width: 90%;
            margin-right: auto;
            margin-bottom: 5px;
            box-shadow: 0 1px 0.5px rgba(0, 0, 0, 0.13);
        }
        .wa-bubble-in::before {
            content: '';
            position: absolute;
            top: 0;
            left: -8px;
            width: 0;
            height: 0;
            border-top: 10px solid #ffffff;
            border-left: 10px solid transparent;
        }

        .log-list {
            max-height: 200px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.85rem;
            background: #f8f9fa;
            border: 1px solid #d9dee3;
            border-radius: 6px;
            padding: 10px;
        }
        .log-entry { margin-bottom: 5px; }
        .log-entry.info { color: #696cff; }
        .log-entry.success { color: #71dd37; }
        .log-entry.error { color: #ff3e1d; }
    </style>
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">الواتساب /</span> اختبار إرسال OTP
</h4>

<div class="row">
    <!-- Left Column: Steps -->
    <div class="col-md-7">
        
        <!-- Step 1: Request OTP -->
        <div class="card mb-4" id="step1-card">
            <h5 class="card-header border-bottom">
                <span class="badge bg-label-primary rounded-pill me-2">1</span>
                إرسال رمز OTP
            </h5>
            <div class="card-body pt-4">
                <p class="card-text text-muted">أدخل رقم الهاتف بصيغة دولية للتأكد من وصول رسالة رمز التحقق.</p>
                
                <div class="row g-3 align-items-center mb-3">
                    <div class="col-sm-8">
                        <div class="input-group input-group-merge" dir="ltr">
                            <span class="input-group-text">🇸🇦</span>
                            <input type="tel" id="phone-input" class="form-control" placeholder="+9665XXXXXXXX" value="+966">
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <button class="btn btn-primary w-100" id="btn-send" onclick="sendOtp()">
                            <i class="ti ti-send me-1"></i> إرسال
                        </button>
                    </div>
                </div>
                
                <div id="send-result"></div>
            </div>
        </div>

        <!-- Step 2: Verify OTP -->
        <div class="card mb-4" style="opacity: 0.5; pointer-events: none;" id="step2-card">
            <h5 class="card-header border-bottom">
                <span class="badge bg-label-primary rounded-pill me-2">2</span>
                إدخال الرمز والتحقق منه
            </h5>
            <div class="card-body pt-4">
                <p class="card-text text-muted">أدخل الرمز الذي وصل إلى الهاتف للتحقق من صحته عبر خدمة ساعي.</p>
                
                <input type="hidden" id="verification-id" value="">
                
                <div class="d-flex flex-column align-items-center mb-4">
                    <div class="otp-input-wrapper mb-3">
                        <input class="otp-digit" maxlength="1" id="d1" oninput="otpNext(this,'d2')" onkeydown="otpBack(event,this,'')">
                        <input class="otp-digit" maxlength="1" id="d2" oninput="otpNext(this,'d3')" onkeydown="otpBack(event,this,'d1')">
                        <input class="otp-digit" maxlength="1" id="d3" oninput="otpNext(this,'d4')" onkeydown="otpBack(event,this,'d2')">
                        <input class="otp-digit" maxlength="1" id="d4" oninput="otpNext(this,'d5')" onkeydown="otpBack(event,this,'d3')">
                        <input class="otp-digit" maxlength="1" id="d5" oninput="otpNext(this,'d6')" onkeydown="otpBack(event,this,'d4')">
                        <input class="otp-digit" maxlength="1" id="d6" oninput="otpNext(this,'')"  onkeydown="otpBack(event,this,'d5')">
                    </div>
                    
                    <button class="btn btn-success" id="btn-verify" onclick="verifyOtp()" disabled>
                        <i class="ti ti-circle-check me-1"></i> التحقق من الرمز
                    </button>
                </div>

                <div id="verify-result" class="text-center" style="display: none;">
                    <div id="verify-icon" class="mb-2" style="font-size:2.5rem;"></div>
                    <h5 id="verify-text" class="mb-1"></h5>
                    <p id="verify-sub" class="text-muted mb-0"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Info & Preview -->
    <div class="col-md-5">
        
        <!-- Service Configuration -->
        <div class="card mb-4">
            <h5 class="card-header border-bottom pb-3">
                <i class="ti ti-settings text-primary me-2"></i> إعدادات الخدمة الحالية
            </h5>
            <div class="card-body pt-3">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between align-items-center mb-3">
                        <span>المزود النشط:</span>
                        <span class="badge {{ $config['saei_enabled'] ? 'bg-label-success' : 'bg-label-warning' }}">
                            {{ $config['provider'] }}
                        </span>
                    </li>
                    <li class="d-flex justify-content-between align-items-center mb-3">
                        <span>وضع المحاكاة:</span>
                        <span class="badge {{ $config['simulation'] ? 'bg-label-info' : 'bg-label-secondary' }}">
                            {{ $config['simulation'] ? 'مفعّل' : 'معطّل' }}
                        </span>
                    </li>
                    <li class="d-flex justify-content-between align-items-center mb-3">
                        <span>API Key:</span>
                        <span class="badge {{ $config['api_key_set'] ? 'bg-label-success' : 'bg-label-danger' }}">
                            {{ $config['api_key_set'] ? 'مضبوط' : 'غير مضبوط' }}
                        </span>
                    </li>
                    <li class="d-flex justify-content-between align-items-center mb-3">
                        <span>Phone Number ID:</span>
                        <code>{{ $config['from_phone'] }}</code>
                    </li>
                    <li class="d-flex justify-content-between align-items-center">
                        <span>Template ID:</span>
                        <code>{{ $config['template_id'] }}</code>
                    </li>
                </ul>
                
                @if(!$config['saei_enabled'])
                <div class="alert alert-warning mt-4 mb-0 py-2">
                    <i class="ti ti-info-circle me-1"></i>
                    ساعي معطّل حالياً. للتبديل إليه، ضع <code>SAEI_OTP_ENABLED=true</code> في الـ <code>.env</code>
                </div>
                @endif
            </div>
        </div>

        <!-- WhatsApp Preview -->
        <div class="card mb-4">
            <h5 class="card-header border-bottom pb-3">
                <i class="ti ti-brand-whatsapp text-success me-2"></i> معاينة الرسالة
            </h5>
            <div class="card-body pt-3">
                <div class="wa-preview-box">
                    <div class="wa-bubble-in">
                        <div class="mb-1 text-muted" style="font-size: 0.85rem;">🔐 رمز التحقق من سيف ديست</div>
                        <div class="text-center fw-bold fs-4 my-2" style="letter-spacing: 4px; color: #128C7E;" id="preview-code">- - - - - -</div>
                        <div class="text-muted" style="font-size: 0.75rem;">الرمز صالح لمدة 5 دقائق. لا تشاركه مع أحد.</div>
                        <div class="border-top mt-2 pt-1 d-flex justify-content-between align-items-center">
                            <span style="font-size: 0.7rem; color: #a9a9a9;">safedest_otp_code</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Log -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center border-bottom pb-3">
                <h5 class="mb-0"><i class="ti ti-terminal text-secondary me-2"></i> سجل النشاط</h5>
                <button type="button" class="btn btn-sm btn-icon btn-label-secondary" onclick="clearLog()" title="مسح السجل">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
            <div class="card-body pt-3">
                <div id="activity-log" class="log-list">
                    <div class="log-entry info">[جاهز] - بانتظار بدء الاختبار...</div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('page-script')
<script>
    let verificationId = null;
    const CSRF = '{{ csrf_token() }}';
    const URL_SEND   = '{{ route("admin.whatsapp-otp-test.send") }}';
    const URL_VERIFY = '{{ route("admin.whatsapp-otp-test.verify") }}';

    function log(msg, type = 'info') {
        const box = document.getElementById('activity-log');
        const now = new Date().toLocaleTimeString('ar-SA');
        const el  = document.createElement('div');
        el.className = `log-entry ${type}`;
        el.textContent = `[${now}] ${msg}`;
        box.prepend(el);
    }

    function clearLog() {
        document.getElementById('activity-log').innerHTML = '<div class="log-entry info">[جاهز] - السجل فارغ.</div>';
    }

    function setLoading(btnId, loading, defaultText) {
        const btn = document.getElementById(btnId);
        if (loading) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> جارٍ...';
        } else {
            btn.disabled = false;
            btn.innerHTML = defaultText;
        }
    }

    // OTP Navigation
    function otpNext(el, nextId) {
        if (el.value.length > 0) {
            el.classList.add('filled');
            if (nextId) document.getElementById(nextId).focus();
        } else {
            el.classList.remove('filled');
        }
        updatePreviewAndBtn();
    }

    function otpBack(e, el, prevId) {
        if (e.key === 'Backspace' && !el.value && prevId) {
            document.getElementById(prevId).focus();
        }
    }

    function getOtpCode() {
        return ['d1','d2','d3','d4','d5','d6'].map(id => document.getElementById(id).value).join('');
    }

    function updatePreviewAndBtn() {
        const code = getOtpCode();
        document.getElementById('btn-verify').disabled = code.length < 6;
        document.getElementById('preview-code').textContent = code.length > 0 ? code.padEnd(6, '·').split('').join(' ') : '- - - - - -';
    }

    // Send Request
    async function sendOtp() {
        const phone = document.getElementById('phone-input').value.trim();
        if (!phone || phone.length < 8) {
            Swal.fire({ icon: 'warning', title: 'تنبيه', text: 'الرجاء إدخال رقم هاتف صحيح', confirmButtonText: 'حسناً' });
            return;
        }

        setLoading('btn-send', true, '<i class="ti ti-send me-1"></i> إرسال');
        log(`إرسال الرمز إلى: ${phone}`, 'info');
        
        try {
            const res = await fetch(URL_SEND, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ phone })
            });
            const data = await res.json();

            if (data.success) {
                verificationId = data.verification_id;
                document.getElementById('verification-id').value = verificationId;

                document.getElementById('send-result').innerHTML = `
                    <div class="alert alert-success d-flex align-items-center mt-3 mb-0" role="alert">
                        <i class="ti ti-check me-2"></i>
                        <div>
                            ${data.message} 
                            ${verificationId ? `<br><small>Verification ID: <strong>${verificationId}</strong></small>` : ''}
                        </div>
                    </div>`;

                // Unlock step 2
                const step2 = document.getElementById('step2-card');
                step2.style.opacity = '1';
                step2.style.pointerEvents = 'auto';
                document.getElementById('d1').focus();

                log(`✅ تم الإرسال (ID: ${verificationId})`, 'success');
            } else {
                document.getElementById('send-result').innerHTML = `
                    <div class="alert alert-danger d-flex align-items-center mt-3 mb-0" role="alert">
                        <i class="ti ti-alert-circle me-2"></i> ${data.message}
                    </div>`;
                log(`❌ خطأ: ${data.message}`, 'error');
            }
        } catch (err) {
            log(`❌ خطأ في الشبكة: ${err.message}`, 'error');
        } finally {
            setLoading('btn-send', false, '<i class="ti ti-send me-1"></i> إرسال');
        }
    }

    // Verify Request
    async function verifyOtp() {
        const code = getOtpCode();
        const vid  = document.getElementById('verification-id').value;

        if (code.length < 6) return;

        setLoading('btn-verify', true, '<i class="ti ti-circle-check me-1"></i> التحقق من الرمز');
        log(`التحقق من الكود: ${code}`, 'info');

        try {
            const res = await fetch(URL_VERIFY, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ verification_id: parseInt(vid), code })
            });
            const data = await res.json();

            const resultBox = document.getElementById('verify-result');
            resultBox.style.display = 'block';
            
            const iconEl = document.getElementById('verify-icon');
            const textEl = document.getElementById('verify-text');
            const subEl  = document.getElementById('verify-sub');

            if (data.status === 'approved') {
                iconEl.innerHTML = '<i class="ti ti-circle-check-filled text-success"></i>';
                textEl.className = 'text-success mb-1';
                textEl.textContent = 'تم التحقق بنجاح!';
                subEl.textContent = 'الرمز المدخل صحيح ومطابق.';
                log('✅ نجاح: الرمز صحيح ومقبول.', 'success');
                
                Swal.fire({
                    icon: 'success',
                    title: 'عملية ناجحة',
                    text: 'تم التحقق من صحة الكود.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                let colorClass = data.status === 'expired' ? 'text-warning' : 'text-danger';
                let iconClass  = data.status === 'expired' ? 'ti-clock-exclamation' : 'ti-circle-x-filled';
                
                iconEl.innerHTML = `<i class="ti ${iconClass} ${colorClass}"></i>`;
                textEl.className = `${colorClass} mb-1`;
                textEl.textContent = data.label;
                subEl.textContent = data.message;
                log(`❌ فشل: ${data.label}`, 'error');
            }

        } catch (err) {
            log(`❌ خطأ في الشبكة: ${err.message}`, 'error');
        } finally {
            setLoading('btn-verify', false, '<i class="ti ti-circle-check me-1"></i> التحقق من الرمز');
        }
    }

    // Allow Enter key to trigger send
    document.getElementById('phone-input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') sendOtp();
    });
</script>
@endsection
