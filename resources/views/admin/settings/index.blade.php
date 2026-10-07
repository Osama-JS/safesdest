@extends('layouts/layoutMaster')

@section('title', __('General Settings'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/@form-validation/form-validation.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/@form-validation/popular.js',
        'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
        'resources/assets/vendor/libs/@form-validation/auto-focus.js',
        'resources/assets/vendor/libs/cleavejs/cleave.js',
        'resources/assets/vendor/libs/cleavejs/cleave-phone.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
    @vite(['resources/js/admin/settings.js'])
@endsection

@section('content')
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item">
                <a href="{{ url('admin') }}"><i class="ti ti-home-2 me-1"></i>{{ __('الرئيسية') }}</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">{{ __('الإعدادات') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('الإعدادات العامة') }}</li>
        </ol>
    </nav>

    <!-- Hero Header Banner -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.02) 100%);">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-xl bg-primary text-white rounded-3 shadow-sm d-flex align-items-center justify-content-center p-2">
                        <i class="ti ti-adjustments-alt fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <h4 class="fw-bold mb-0 text-heading">{{ __('الإعدادات العامة للمنصة') }}</h4>
                            <span class="badge bg-label-primary rounded-pill px-3 py-1 fs-tiny fw-semibold">
                                <i class="ti ti-sliders me-1"></i> {{ __('الضبط الأساسي للنظام') }}
                            </span>
                        </div>
                        <p class="text-muted mb-0">
                            {{ __('مركز إدارة إعدادات المنصة الحيوية: خوادم الربط والـ API، واتساب وساعي، البريد الإلكتروني، تحديثات التطبيقات، وقوالب النماذج.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs (Pills) -->
    <ul class="nav nav-pills flex-column flex-md-row mb-4 gap-2 gap-md-1 border-bottom pb-3" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center" id="nav-general-tab" data-bs-toggle="pill" data-bs-target="#nav-general" type="button" role="tab" aria-controls="nav-general" aria-selected="true">
                <i class="ti ti-settings me-2 fs-5"></i> {{ __('الإعدادات والتشغيل') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center" id="nav-saei-tab" data-bs-toggle="pill" data-bs-target="#nav-saei" type="button" role="tab" aria-controls="nav-saei" aria-selected="false">
                <i class="ti ti-brand-whatsapp text-success me-2 fs-5"></i> {{ __('واتساب وساعي (OTP)') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center" id="nav-mail-tab" data-bs-toggle="pill" data-bs-target="#nav-mail" type="button" role="tab" aria-controls="nav-mail" aria-selected="false">
                <i class="ti ti-mail me-2 fs-5"></i> {{ __('خادم البريد (SMTP)') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center" id="nav-mtahd-tab" data-bs-toggle="pill" data-bs-target="#nav-mtahd" type="button" role="tab" aria-controls="nav-mtahd" aria-selected="false">
                <i class="ti ti-shield-check me-2 fs-5"></i> {{ __('منصة متعهد (الضمان)') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center" id="nav-apps-tab" data-bs-toggle="pill" data-bs-target="#nav-apps" type="button" role="tab" aria-controls="nav-apps" aria-selected="false">
                <i class="ti ti-device-mobile me-2 fs-5"></i> {{ __('تحديثات التطبيقات') }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center" id="nav-templates-tab" data-bs-toggle="pill" data-bs-target="#nav-templates" type="button" role="tab" aria-controls="nav-templates" aria-selected="false">
                <i class="ti ti-file-text me-2 fs-5"></i> {{ __('قوالب النماذج') }}
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content p-0 border-0 shadow-none" id="settingsTabContent">

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- TAB 1: General & Operations                                   --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade show active" id="nav-general" role="tabpanel" aria-labelledby="nav-general-tab">
            <div class="row g-4">
                <!-- Task Distribution Card -->
                <div class="col-12 col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-label-primary py-3">
                            <h5 class="card-title mb-0 text-primary d-flex align-items-center">
                                <i class="ti ti-git-fork me-2 fs-4"></i>{{ __('إعدادات توزيع المهام التشغيلية (Task Distribution)') }}
                            </h5>
                        </div>
                        <div class="card-body pt-4">
                            <div class="card bg-label-light border p-3 mb-4">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input update-setting-checkbox" type="checkbox" id="auto_distribution_enabled"
                                        data-key="auto_distribution_enabled"
                                        {{ ($settings['auto_distribution_enabled']['value'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold text-dark" for="auto_distribution_enabled">
                                        {{ __('تفعيل التوزيع التلقائي للمهام على السائقين (Auto Distribution)') }}
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">{{ $settings['auto_distribution_enabled']['description'] ?? __('يقوم النظام بتوزيع المهام الجديدة تلقائياً على السائقين القريبين المتاحين.') }}</small>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">{{ __('نمط التوزيع (Distribution Mode)') }}</label>
                                    <select class="form-select update-setting-select" data-key="distribution_mode">
                                        <option value="sequential" {{ ($settings['distribution_mode']['value'] ?? 'sequential') == 'sequential' ? 'selected' : '' }}>
                                            {{ __('تتابعي - واحد تلو الآخر (Sequential)') }}
                                        </option>
                                        <option value="broadcast" {{ ($settings['distribution_mode']['value'] ?? '') == 'broadcast' ? 'selected' : '' }}>
                                            {{ __('بث جماعي لأقرب 5 سائقين (Broadcast)') }}
                                        </option>
                                    </select>
                                    <small class="text-muted">{{ $settings['distribution_mode']['description'] ?? __('آلية إشعار السائقين بالمهمة الجديدة.') }}</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">{{ __('أقصى مسافة للبحث عن سائقين (بالأمتار)') }}</label>
                                    <div class="input-group">
                                        <input type="number" data-key="max_distribution_distance"
                                            value="{{ $settings['max_distribution_distance']['value'] ?? '1000' }}"
                                            class="form-control update-setting-input">
                                        <span class="input-group-text">{{ __('متر') }}</span>
                                    </div>
                                    <small class="text-muted">{{ $settings['max_distribution_distance']['description'] ?? __('النطاق الجغرافي للبحث عن السائقين المتاحين.') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Policies & Reports Signatures -->
                <div class="col-12 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-label-info py-3">
                            <h5 class="card-title mb-0 text-info d-flex align-items-center">
                                <i class="ti ti-signature me-2 fs-4"></i>{{ __('التوقيعات الرقمية والسياسات') }}
                            </h5>
                        </div>
                        <div class="card-body pt-4">
                            <div class="card bg-label-light border p-3 mb-3">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input update-setting-checkbox" type="checkbox" id="internal_signatures_enabled"
                                        data-key="internal_signatures_enabled"
                                        {{ ($settings['internal_signatures_enabled']['value'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold text-dark" for="internal_signatures_enabled">
                                        {{ __('تفعيل التوقيعات في بوالص الشحن والتقارير') }}
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">{{ $settings['internal_signatures_enabled']['description'] ?? __('إظهار توقيعات السائقين والعملاء المخزنة تلقائياً داخل وثائق الـ PDF المصدرة.') }}</small>
                            </div>

                            <hr class="my-4">

                            <!-- Backups Quick Link -->
                            <div class="d-flex align-items-center justify-content-between p-3 rounded bg-lighter">
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark"><i class="ti ti-database me-1"></i> {{ __('إدارة النسخ الاحتياطي') }}</h6>
                                    <small class="text-muted">{{ __('إنشاء واسترجاع النسخ الاحتياطية للنظام') }}</small>
                                </div>
                                <a href="{{ route('settings.backup') }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-settings me-1"></i> {{ __('إدارة') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- TAB 2: WhatsApp & Saei OTP                                    --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="nav-saei" role="tabpanel" aria-labelledby="nav-saei-tab">
            @php
                $waProvider = $settings['whatsapp_provider']['value'] ?? env('WHATSAPP_PROVIDER', 'saei');
                $saeiOtpEnabled = ($settings['saei_otp_enabled']['value'] ?? '1') == '1';
                $saeiSimulation = ($settings['saei_simulation']['value'] ?? '0') == '1';
                $saeiApiKey = $settings['saei_api_key']['value'] ?? env('SAEI_API_KEY', '');
                $saeiBaseUrl = $settings['saei_base_url']['value'] ?? env('SAEI_BASE_URL', 'https://api.saei.automize.sa/v1');
                $saeiFromPhoneId = $settings['saei_from_phone_id']['value'] ?? env('SAEI_FROM_PHONE_ID', '+966557507505');
                $saeiTemplateId = $settings['saei_template_id']['value'] ?? env('SAEI_TEMPLATE_ID', '77');
                $saeiCallbackSecret = $settings['saei_callback_secret']['value'] ?? env('SAEI_CALLBACK_SECRET', '');
                $waCloudToken = $settings['whatsapp_cloud_token']['value'] ?? env('WHATSAPP_CLOUD_TOKEN', '');
                $waCloudWabaId = $settings['whatsapp_cloud_waba_id']['value'] ?? env('WHATSAPP_CLOUD_WABA_ID', '2008107733152275');
                $waCloudPhoneId = $settings['whatsapp_cloud_phone_id']['value'] ?? env('WHATSAPP_CLOUD_PHONE_ID', '1276243858896899');
                $waVerifyToken = $settings['whatsapp_verify_token']['value'] ?? env('WHATSAPP_VERIFY_TOKEN', '23423jlkjkjkwsdl234kjlk99kd');
                $webhookUrl = url('/api/whatsapp/webhook');
            @endphp
            <div class="card border-0 shadow-sm" id="saei_settings_card">
                <div class="card-header bg-label-success py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="card-title mb-0 text-success">
                                <i class="ti ti-brand-whatsapp me-2 fs-3"></i>{{ __('إعدادات الربط مع واتساب وساعي (Saei & WhatsApp Cloud API)') }}
                            </h5>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn_test_saei_connection">
                                <i class="ti ti-plug-connected me-1"></i>{{ __('اختبار الاتصال بساعي') }}
                            </button>
                            <a href="{{ route('admin.whatsapp-otp-test.index') }}" class="btn btn-sm btn-outline-success">
                                <i class="ti ti-shield-check me-1"></i>{{ __('فحص واختبار OTP') }}
                            </a>
                            <button type="button" class="btn btn-sm btn-success" id="btn_save_saei_settings">
                                <i class="ti ti-device-floppy me-1"></i>{{ __('حفظ إعدادات ساعي وواتساب') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-4">
                    <div class="alert alert-success border-0 mb-4" role="alert">
                        <div class="d-flex">
                            <i class="ti ti-info-circle fs-3 me-2"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold">{{ __('إدارة المزود ساعي (Saei / Meta BSP):') }}</h6>
                                <p class="mb-0 small">
                                    {{ __('تتحكم هذه الإعدادات مباشرة في إرسال أكواد التحقق (OTP) للعملاء والسائقين عبر منصة ساعي المعتمدة، وكذلك إرسال واستقبال رسائل ومحادثات وقوالب الواتساب السحابية (Meta Cloud API). التعديلات هنا تطبق فورياً وتلغي الاعتماد على ملف .env.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Webhook Quick Info Banner -->
                    <div class="card bg-lighter border border-dashed mb-4">
                        <div class="card-body p-3">
                            <div class="row align-items-center g-3">
                                <div class="col-md-7">
                                    <label class="form-label fw-bold text-dark mb-1">
                                        <i class="ti ti-webhook text-primary me-1"></i>{{ __('رابط الـ Webhook المباشر لاستقبال الرسائل (Callback URL):') }}
                                    </label>
                                    <div class="input-group input-group-merge">
                                        <input type="text" class="form-control font-monospace bg-white" id="info_webhook_url" value="{{ $webhookUrl }}" readonly>
                                        <button class="btn btn-outline-primary" type="button" onclick="copySettingVal('info_webhook_url')">
                                            <i class="ti ti-copy me-1"></i>{{ __('نسخ') }}
                                        </button>
                                    </div>
                                    <small class="text-muted">{{ __('ضع هذا الرابط في خانة Webhook Callback URL في لوحة مطوري ميتا أو منصة ساعي.') }}</small>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-bold text-dark mb-1">
                                        <i class="ti ti-key text-warning me-1"></i>{{ __('رمز التحقق للـ Webhook (Verify Token):') }}
                                    </label>
                                    <div class="input-group input-group-merge">
                                        <input type="text" class="form-control font-monospace bg-white" id="info_verify_token" value="{{ $waVerifyToken }}" readonly>
                                        <button class="btn btn-outline-warning" type="button" onclick="copySettingVal('info_verify_token')">
                                            <i class="ti ti-copy me-1"></i>{{ __('نسخ') }}
                                        </button>
                                    </div>
                                    <small class="text-muted">{{ __('يجب أن يتطابق مع Verify Token في إعدادات ميتا.') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="saeiSettingsForm">
                        <!-- Active Provider Selection -->
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="card bg-label-success border p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="form-label fw-bold text-success mb-0 fs-6" for="setting_whatsapp_provider">
                                            <i class="ti ti-brand-whatsapp me-1"></i>{{ __('مزود خدمة الواتساب النشط للمنصة (Active WhatsApp Provider)') }}
                                        </label>
                                        <span class="badge bg-success">{{ __('ساعي هو المزود الافتراضي والموصى به') }}</span>
                                    </div>
                                    <select name="whatsapp_provider" id="setting_whatsapp_provider" class="form-select border-success fw-bold">
                                        <option value="saei" {{ $waProvider === 'saei' ? 'selected' : '' }}>🟢 ساعي (Saei WhatsApp API / Automize - موصى به لكافة الرسائل والشات)</option>
                                        <option value="cloud" {{ $waProvider === 'cloud' ? 'selected' : '' }}>واتساب كلاود المباشر (Meta Cloud API)</option>
                                        <option value="green" {{ $waProvider === 'green' ? 'selected' : '' }}>جرين إيه بي آي (Green API)</option>
                                    </select>
                                    <small class="text-muted d-block mt-2">
                                        {{ __('يحدد هذا الخيار المزود الذي تعتمده المنصة لإرسال واستقبال رسائل المحادثات والشات والقوالب في الوقت الفعلي.') }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Operational Switches -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card bg-label-light border p-3">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="setting_saei_otp_enabled" name="saei_otp_enabled" value="1" {{ $saeiOtpEnabled ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold text-dark" for="setting_saei_otp_enabled">
                                            {{ __('تفعيل التحقق بـ OTP عبر منصة ساعي (Saei Service)') }}
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">{{ __('عند التفعيل، تُرسل أكواد OTP للعملاء والسائقين عبر واتساب ساعي تلقائياً.') }}</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-label-light border p-3">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="setting_saei_simulation" name="saei_simulation" value="1" {{ $saeiSimulation ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold text-dark" for="setting_saei_simulation">
                                            {{ __('وضع المحاكاة والتجربة (Simulation Mode)') }}
                                        </label>
                                    </div>
                                    <small class="text-muted d-block mt-1">{{ __('إرسال أكواد وهمية في بيئة التطوير دون إجراء اتصال حقيقي مع ساعي أو خصم رصيد.') }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Saei Specific Settings Header -->
                        <h6 class="fw-bold text-heading mb-3 pb-2 border-bottom d-flex align-items-center">
                            <i class="ti ti-plug text-success me-2"></i>{{ __('بيانات الاعتماد الخاصة بمنصة ساعي (Saei / Automize Credentials)') }}
                        </h6>

                        <div class="row g-3 mb-4">
                            <!-- Saei API Key -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('مفتاح الـ API السري لمنصة ساعي (Saei API Key)') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" id="saei_api_key" name="saei_api_key" class="form-control font-monospace"
                                        value="{{ $saeiApiKey }}" placeholder="sk_live_...">
                                    <button class="btn btn-outline-secondary" type="button" id="btn_toggle_saei_api_key">
                                        <i class="ti ti-eye" id="saei_api_key_icon"></i>
                                    </button>
                                </div>
                                <small class="text-muted">{{ __('المفتاح السري المستخرج من لوحة تحكم منصة ساعي (saei.automize.sa).') }}</small>
                            </div>

                            <!-- Saei Base URL -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('رابط API ساعي الأساسي (Base URL)') }} <span class="text-danger">*</span></label>
                                <input type="url" id="saei_base_url" name="saei_base_url" class="form-control font-monospace"
                                    value="{{ $saeiBaseUrl }}" placeholder="https://api.saei.automize.sa/v1">
                                <small class="text-muted">{{ __('الافتراضي: https://api.saei.automize.sa/v1') }}</small>
                            </div>

                            <!-- Saei From Phone ID -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('رقم أو معرّف هاتف الإرسال في ساعي') }} <span class="text-danger">*</span></label>
                                <input type="text" id="saei_from_phone_id" name="saei_from_phone_id" class="form-control font-monospace"
                                    value="{{ $saeiFromPhoneId }}" placeholder="+966557507505" list="saei_numbers_list">
                                <datalist id="saei_numbers_list">
                                    <option value="+966557507505">شركة الوجهة الآمنة (Safe Destination)</option>
                                    <option value="+966555723838">شركة Fly vio</option>
                                    <option value="num_25">معرّف ساعي (num_25)</option>
                                    <option value="num_26">معرّف ساعي (num_26)</option>
                                </datalist>
                                <small class="text-muted">{{ __('يقبل رقم واتساب بالصيغة الدولية مثل +966557507505 أو معرّف ساعي مثل num_25') }}</small>
                            </div>

                            <!-- Saei Template ID -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('معرّف قالب OTP في ساعي (Template ID)') }} <span class="text-danger">*</span></label>
                                <input type="number" id="saei_template_id" name="saei_template_id" class="form-control font-monospace"
                                    value="{{ $saeiTemplateId }}" placeholder="77">
                                <small class="text-muted">{{ __('معرّف قالب الـ Authentication المعتمد في ساعي (مثل: 77).') }}</small>
                            </div>

                            <!-- Saei Callback Secret -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('مفتاح توقيع Callback (اختياري)') }}</label>
                                <input type="text" id="saei_callback_secret" name="saei_callback_secret" class="form-control font-monospace"
                                    value="{{ $saeiCallbackSecret }}" placeholder="sec_...">
                                <small class="text-muted">{{ __('للتحقق من توقيع الهيدر X-OTP-Signature إذا كان مفعلاً في ساعي.') }}</small>
                            </div>
                        </div>

                        <!-- Meta Cloud API Header -->
                        <h6 class="fw-bold text-heading mb-3 pb-2 border-bottom d-flex align-items-center">
                            <i class="ti ti-brand-meta text-primary me-2"></i>{{ __('بيانات الربط مع واتساب السحابي / ميتا (Meta Cloud API & Webhook)') }}
                        </h6>

                        <div class="row g-3">
                            <!-- Cloud Token -->
                            <div class="col-md-12">
                                <label class="form-label fw-bold">{{ __('رمز الوصول الدائم لواتساب كلاود (Meta Permanent Cloud Token)') }}</label>
                                <div class="input-group">
                                    <input type="password" id="whatsapp_cloud_token" name="whatsapp_cloud_token" class="form-control font-monospace"
                                        value="{{ $waCloudToken }}" placeholder="EAATbN3...">
                                    <button class="btn btn-outline-secondary" type="button" id="btn_toggle_cloud_token">
                                        <i class="ti ti-eye" id="cloud_token_icon"></i>
                                    </button>
                                </div>
                                <small class="text-muted">{{ __('رمز وصول الـ System User الدائم لإرسال واسترجاع القوالب والمحادثات.') }}</small>
                            </div>

                            <!-- WABA ID -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('معرّف حساب واتساب للأعمال (WABA ID)') }}</label>
                                <input type="text" id="whatsapp_cloud_waba_id" name="whatsapp_cloud_waba_id" class="form-control font-monospace"
                                    value="{{ $waCloudWabaId }}" placeholder="2008107733152275">
                                <small class="text-muted">{{ __('WhatsApp Business Account ID الخاص بالحساب.') }}</small>
                            </div>

                            <!-- Cloud Phone ID -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('معرّف رقم الهاتف السحابي (Cloud Phone ID)') }}</label>
                                <input type="text" id="whatsapp_cloud_phone_id" name="whatsapp_cloud_phone_id" class="form-control font-monospace"
                                    value="{{ $waCloudPhoneId }}" placeholder="1276243858896899">
                                <small class="text-muted">{{ __('Phone Number ID لرقم المنصة المعتمد.') }}</small>
                            </div>

                            <!-- Webhook Verify Token -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold">{{ __('رمز التحقق للـ Webhook (Verify Token)') }}</label>
                                <input type="text" id="whatsapp_verify_token" name="whatsapp_verify_token" class="form-control font-monospace"
                                    value="{{ $waVerifyToken }}" placeholder="23423jlkjkjkwsdl234kjlk99kd">
                                <small class="text-muted">{{ __('الرمز الذي يتم إدخاله في Meta لمصادقة الـ Webhook.') }}</small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end align-items-center gap-2 mt-4 pt-3 border-top">
                            <span class="text-muted small"><i class="ti ti-info-circle me-1"></i>{{ __('اضغط على زر الحفظ لتطبيق التغييرات فورياً على السيرفر') }}</span>
                            <button type="button" class="btn btn-success px-4 btn-save-saei-settings">
                                <i class="ti ti-device-floppy me-1"></i>{{ __('حفظ وتطبيق إعدادات ساعي وواتساب') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- TAB 3: Mail (SMTP) Settings                                   --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="nav-mail" role="tabpanel" aria-labelledby="nav-mail-tab">
            <div class="card border-0 shadow-sm" id="mail_settings_card">
                <div class="card-header bg-label-primary py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="card-title mb-0 text-primary">
                                <i class="ti ti-mail-cog me-2 fs-3"></i>{{ __('إعدادات خادم البريد الإلكتروني وإشعارات النظام (SMTP Configuration)') }}
                            </h5>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn_open_test_mail_modal">
                                <i class="ti ti-send me-1"></i>{{ __('اختبار الاتصال وإرسال بريد تجريبي') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" id="btn_save_mail_settings">
                                <i class="ti ti-device-floppy me-1"></i>{{ __('حفظ إعدادات البريد') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-4">
                    <div class="alert alert-primary border-0 mb-4" role="alert">
                        <div class="d-flex">
                            <i class="ti ti-info-circle fs-3 me-2"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold">{{ __('إدارة البريد الإلكتروني للمنصة:') }}</h6>
                                <p class="mb-0 small">
                                    {{ __('يتم استخدام هذه البيانات لإرسال كافة إشعارات الإدارة، فواتير المهام، تنبيهات سحب الأموال، وتقارير النظام عبر البريد الإلكتروني. تطبق الإعدادات فوراً وبدقة تامة دون الحاجة لإعادة تشغيل السيرفر.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    @php
                        $currentMailer = $settings['mail_mailer']['value'] ?? config('mail.default', 'smtp');
                        $currentHost = $settings['mail_host']['value'] ?? config('mail.mailers.smtp.host', 'smtp.hostinger.com');
                        $currentPort = $settings['mail_port']['value'] ?? config('mail.mailers.smtp.port', 587);
                        $currentUsername = $settings['mail_username']['value'] ?? config('mail.mailers.smtp.username', 'alnoumani@alnoumani.net');
                        $currentPassword = $settings['mail_password']['value'] ?? config('mail.mailers.smtp.password', '');
                        $currentEncryption = $settings['mail_encryption']['value'] ?? config('mail.mailers.smtp.encryption', 'tls');
                        $currentFromAddress = $settings['mail_from_address']['value'] ?? config('mail.from.address', 'alnoumani@alnoumani.net');
                        $currentFromName = $settings['mail_from_name']['value'] ?? config('mail.from.name', 'Safedests');
                    @endphp

                    <form id="mailSettingsForm">
                        <div class="row g-3">
                            <!-- Mail Driver -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold">{{ __('مشغل البريد (Driver / Mailer)') }} <span class="text-danger">*</span></label>
                                <select id="mail_mailer" name="mail_mailer" class="form-select">
                                    <option value="smtp" {{ $currentMailer == 'smtp' ? 'selected' : '' }}>SMTP ({{ __('موصى به') }})</option>
                                    <option value="sendmail" {{ $currentMailer == 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                                    <option value="log" {{ $currentMailer == 'log' ? 'selected' : '' }}>Log ({{ __('سجلات فقط') }})</option>
                                </select>
                                <small class="text-muted">{{ __('بروتوكول الإرسال الأساسي.') }}</small>
                            </div>

                            <!-- Host -->
                            <div class="col-md-5">
                                <label class="form-label fw-bold">{{ __('خادم البريد (Mail Host)') }} <span class="text-danger">*</span></label>
                                <input type="text" id="mail_host" name="mail_host" class="form-control font-monospace"
                                    value="{{ $currentHost }}" placeholder="e.g., smtp.hostinger.com">
                                <small class="text-muted">{{ __('عنوان خادم الـ SMTP الخاص بمزود الخدمة.') }}</small>
                            </div>

                            <!-- Port -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">{{ __('المنفذ (Port)') }} <span class="text-danger">*</span></label>
                                <input type="number" id="mail_port" name="mail_port" class="form-control font-monospace"
                                    value="{{ $currentPort }}" placeholder="587">
                                <small class="text-muted">{{ __('الافتراضي: 587 أو 465.') }}</small>
                            </div>

                            <!-- Encryption -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold">{{ __('التشفير (Encryption)') }}</label>
                                <select id="mail_encryption" name="mail_encryption" class="form-select">
                                    <option value="tls" {{ $currentEncryption == 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ $currentEncryption == 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="null" {{ in_array($currentEncryption, ['null', 'none', '']) ? 'selected' : '' }}>{{ __('بدون تشفير') }}</option>
                                </select>
                                <small class="text-muted">{{ __('نوع تشفير الاتصال.') }}</small>
                            </div>

                            <!-- Username -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('اسم مستخدم البريد (Username / Email)') }}</label>
                                <input type="text" id="mail_username" name="mail_username" class="form-control font-monospace"
                                    value="{{ $currentUsername }}" placeholder="notifications@safedest.com">
                                <small class="text-muted">{{ __('عنوان البريد الإلكتروني المستخدم للمصادقة.') }}</small>
                            </div>

                            <!-- Password -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('كلمة مرور البريد (Password / App Password)') }}</label>
                                <div class="input-group">
                                    <input type="password" id="mail_password" name="mail_password" class="form-control font-monospace"
                                        value="{{ $currentPassword }}" placeholder="••••••••••••">
                                    <button class="btn btn-outline-secondary" type="button" id="btn_toggle_mail_pwd">
                                        <i class="ti ti-eye" id="mail_pwd_icon"></i>
                                    </button>
                                </div>
                                <small class="text-muted">{{ __('كلمة المرور الخاصة بالحساب أو كلمة مرور التطبيقات المخصصة.') }}</small>
                            </div>

                            <!-- From Address -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('عنوان البريد المرسل (From Address)') }} <span class="text-danger">*</span></label>
                                <input type="email" id="mail_from_address" name="mail_from_address" class="form-control font-monospace"
                                    value="{{ $currentFromAddress }}" placeholder="noreply@safedest.com">
                                <small class="text-muted">{{ __('العنوان الذي سيظهر للمستلمين في حقل المرسل.') }}</small>
                            </div>

                            <!-- From Name -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">{{ __('اسم المرسل الظاهر (From Name)') }} <span class="text-danger">*</span></label>
                                <input type="text" id="mail_from_name" name="mail_from_name" class="form-control"
                                    value="{{ $currentFromName }}" placeholder="SafeDest Platform">
                                <small class="text-muted">{{ __('الاسم التعريفي الذي يظهر للمستلم في صندوق الوارد.') }}</small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- TAB 4: Mtahd Escrow Settings                                  --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="nav-mtahd" role="tabpanel" aria-labelledby="nav-mtahd-tab">
            <div class="card border-0 shadow-sm" id="mtahd_settings_card">
                <div class="card-header bg-label-primary py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="card-title mb-0 text-primary">
                                <i class="ti ti-shield-check me-2 fs-3"></i>{{ __('إعدادات وحساب المنصة في متعهد (Amnn / Mtahd Escrow)') }}
                            </h5>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input update-setting-checkbox" type="checkbox" id="setting_mtahd_enabled" data-key="mtahd_enabled" {{ ($settings['mtahd_enabled']['value'] ?? '1') != '0' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark" for="setting_mtahd_enabled">
                                    {{ __('تفعيل خدمة متعهد في النظام والتطبيق') }}
                                </label>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn_test_mtahd_conn">
                                <i class="ti ti-plug-connected me-1"></i>{{ __('اختبار الاتصال بالـ API') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-4">
                    <div class="alert alert-info border-0 mb-4" role="alert">
                        <div class="d-flex">
                            <i class="ti ti-info-circle fs-3 me-2"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold">{{ __('حساب المنصة كبائع معتمد (Seller Account):') }}</h6>
                                <p class="mb-0 small">
                                    {{ __('يتم إنشاء صفقات الضمان المالي تلقائياً بين المنصة والعميل لكل مهمة/شحنة. يجب ربط رقم بائع معتمد (Platform Seller Number) صادر من منصة أمن/متعهد لضمان قيد ودفع المبالغ بصورة صحيحة.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">{{ __('رقم حساب المنصة في متعهد (Platform Seller Number)') }}</label>
                                <div class="input-group">
                                    <input type="text" id="setting_mtahd_seller_num" data-key="mtahd_platform_customer_number"
                                        value="{{ $settings['mtahd_platform_customer_number']['value'] ?? config('services.mtahd.platform_customer_number') }}"
                                        class="form-control update-setting-input font-monospace" placeholder="e.g., CUS-XXXXXX">
                                    <button class="btn btn-primary" type="button" id="btn_create_platform_mtahd">
                                        <i class="ti ti-user-plus me-1"></i>{{ __('إنشاء / توثيق حساب') }}
                                    </button>
                                </div>
                                <small class="text-muted">{{ __('المعرف الرقمي لحساب المنصة لدى متعهد. إذا لم يكن لديك حساب، اضغط على زر الإنشاء لتوثيقه فوراً.') }}</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">{{ __('رابط الـ API الأساسي (Base URL)') }}</label>
                                <input type="url" data-key="mtahd_base_url"
                                    value="{{ $settings['mtahd_base_url']['value'] ?? config('services.mtahd.base_url') }}"
                                    class="form-control update-setting-input font-monospace" placeholder="https://sandbox-api.amnn.sa/api/v1">
                                <small class="text-muted">{{ __('بيئة الاختبار: https://sandbox-api.amnn.sa/api/v1 | بيئة الإنتاج: https://api.amnn.sa/api/v1') }}</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">{{ __('مفتاح الربط (API Token / Secret)') }}</label>
                                <input type="password" data-key="mtahd_api_token"
                                    value="{{ $settings['mtahd_api_token']['value'] ?? config('services.mtahd.api_token') }}"
                                    class="form-control update-setting-input font-monospace" placeholder="c2199c8e...">
                                <small class="text-muted">{{ __('رمز التفويض المعتمد الخاص بحساب منصتكم من شركة متعهد (أمن).') }}</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">{{ __('مفتاح توقيع الإشعارات (Webhook Secret)') }}</label>
                                <input type="password" data-key="mtahd_webhook_secret"
                                    value="{{ $settings['mtahd_webhook_secret']['value'] ?? config('services.mtahd.webhook_secret') }}"
                                    class="form-control update-setting-input font-monospace" placeholder="webhook_secret_key">
                                <small class="text-muted">{{ __('يستخدم للتحقق المشفر من صحة الإشعارات اللحظية الواردة إلى: ') }} <code>/api/webhooks/mtahd</code></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- TAB 5: Mobile Apps Updates                                    --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="nav-apps" role="tabpanel" aria-labelledby="nav-apps-tab">
            <div class="row g-4">
                <!-- Driver App Settings -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-label-warning py-3">
                            <h5 class="card-title mb-0 text-warning d-flex align-items-center">
                                <i class="ti ti-steering-wheel me-2 fs-4"></i>{{ __('تحديثات تطبيق السائق (Driver App)') }}
                            </h5>
                        </div>
                        <div class="card-body pt-4">
                            <div class="form-group mb-4">
                                <label class="form-label fw-bold">{{ __('الحد الأدنى لإصدار التطبيق (Minimum App Version)') }}</label>
                                <input type="text" data-key="min_driver_app_version"
                                    value="{{ $settings['min_driver_app_version']['value'] ?? '1.0.0' }}"
                                    class="form-control update-setting-input font-monospace" placeholder="e.g., 1.0.5">
                                <small class="text-muted">{{ $settings['min_driver_app_version']['description'] ?? __('الإصدارات الأقدم ستطلب التحديث الإجباري.') }}</small>
                            </div>

                            <div class="form-group mb-4">
                                <label class="form-label fw-bold"><i class="ti ti-brand-android text-success me-1"></i> {{ __('رابط متجر أندرويد (Google Play Store)') }}</label>
                                <input type="url" data-key="driver_app_update_url"
                                    value="{{ $settings['driver_app_update_url']['value'] ?? '' }}"
                                    class="form-control update-setting-input font-monospace" placeholder="https://play.google.com/store/apps/details?id=...">
                                <small class="text-muted">{{ $settings['driver_app_update_url']['description'] ?? '' }}</small>
                            </div>

                            <div class="form-group mb-2">
                                <label class="form-label fw-bold"><i class="ti ti-brand-apple text-dark me-1"></i> {{ __('رابط متجر آبل (Apple App Store)') }}</label>
                                <input type="url" data-key="driver_app_ios_update_url"
                                    value="{{ $settings['driver_app_ios_update_url']['value'] ?? '' }}"
                                    class="form-control update-setting-input font-monospace" placeholder="https://apps.apple.com/app/...">
                                <small class="text-muted">{{ $settings['driver_app_ios_update_url']['description'] ?? '' }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer App Settings -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-label-info py-3">
                            <h5 class="card-title mb-0 text-info d-flex align-items-center">
                                <i class="ti ti-user me-2 fs-4"></i>{{ __('تحديثات تطبيق العميل (Customer App)') }}
                            </h5>
                        </div>
                        <div class="card-body pt-4">
                            <div class="form-group mb-4">
                                <label class="form-label fw-bold">{{ __('الحد الأدنى لإصدار التطبيق (Minimum App Version)') }}</label>
                                <input type="text" data-key="min_customer_app_version"
                                    value="{{ $settings['min_customer_app_version']['value'] ?? '1.0.0' }}"
                                    class="form-control update-setting-input font-monospace" placeholder="e.g., 1.0.5">
                                <small class="text-muted">{{ $settings['min_customer_app_version']['description'] ?? __('الإصدارات الأقدم ستطلب التحديث الإجباري.') }}</small>
                            </div>

                            <div class="form-group mb-4">
                                <label class="form-label fw-bold"><i class="ti ti-brand-android text-success me-1"></i> {{ __('رابط متجر أندرويد (Google Play Store)') }}</label>
                                <input type="url" data-key="customer_app_update_url"
                                    value="{{ $settings['customer_app_update_url']['value'] ?? '' }}"
                                    class="form-control update-setting-input font-monospace" placeholder="https://play.google.com/store/apps/details?id=...">
                                <small class="text-muted">{{ $settings['customer_app_update_url']['description'] ?? '' }}</small>
                            </div>

                            <div class="form-group mb-2">
                                <label class="form-label fw-bold"><i class="ti ti-brand-apple text-dark me-1"></i> {{ __('رابط متجر آبل (Apple App Store)') }}</label>
                                <input type="url" data-key="customer_app_ios_update_url"
                                    value="{{ $settings['customer_app_ios_update_url']['value'] ?? '' }}"
                                    class="form-control update-setting-input font-monospace" placeholder="https://apps.apple.com/app/...">
                                <small class="text-muted">{{ $settings['customer_app_ios_update_url']['description'] ?? '' }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- TAB 6: Form Templates                                         --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade" id="nav-templates" role="tabpanel" aria-labelledby="nav-templates-tab">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-label-primary py-3">
                    <h5 class="card-title mb-0 text-primary d-flex align-items-center">
                        <i class="ti ti-file-text me-2 fs-4"></i>{{ __('قوالب النماذج الافتراضية للكيانات والمهام (Default Form Templates)') }}
                    </h5>
                </div>
                <div class="card-body pt-4">
                    <p class="text-muted small mb-4">
                        {{ __('تحديد القوالب الافتراضية التي يتم تحميلها وتطبيقها تلقائياً عند إنشاء كيانات جديدة أو إسناد المهام في لوحة التحكم والتطبيقات.') }}
                    </p>

                    <div class="row g-4">
                        <!-- Customer Template -->
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label fw-bold mb-2"><i class="ti ti-user me-1 text-primary"></i> {{ __('قالب العميل الافتراضي') }}</label>
                                <select class="form-select update-setting-select" data-key="customer_template">
                                    <option value="">{{ __('--- اختر قالباً ---') }}</option>
                                    @foreach ($templates as $val)
                                        <option value="{{ $val->id }}" {{ ($settings['customer_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                            {{ $val->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="customer-error text-danger small"></span>
                            </div>
                        </div>

                        <!-- Driver Template -->
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label fw-bold mb-2"><i class="ti ti-steering-wheel me-1 text-warning"></i> {{ __('قالب السائق الافتراضي') }}</label>
                                <select class="form-select update-setting-select" data-key="driver_template" id="driver-template">
                                    <option value="">{{ __('--- اختر قالباً ---') }}</option>
                                    @foreach ($templates as $val)
                                        <option value="{{ $val->id }}" {{ ($settings['driver_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                            {{ $val->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="driver-error text-danger small"></span>
                            </div>
                        </div>

                        <!-- User Template -->
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label fw-bold mb-2"><i class="ti ti-users me-1 text-info"></i> {{ __('قالب المستخدم الإداري') }}</label>
                                <select class="form-select update-setting-select" data-key="user_template" id="user-template">
                                    <option value="">{{ __('--- اختر قالباً ---') }}</option>
                                    @foreach ($templates as $val)
                                        <option value="{{ $val->id }}" {{ ($settings['user_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                            {{ $val->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="user-error text-danger small"></span>
                            </div>
                        </div>

                        <!-- Task Template -->
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label fw-bold mb-2"><i class="ti ti-checkbox me-1 text-success"></i> {{ __('قالب المهمة العام') }}</label>
                                <select class="form-select update-setting-select" data-key="task_template" id="task-template">
                                    <option value="">{{ __('--- اختر قالباً ---') }}</option>
                                    @foreach ($templates as $val)
                                        <option value="{{ $val->id }}" {{ ($settings['task_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                            {{ $val->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="task-error text-danger small"></span>
                            </div>
                        </div>

                        <!-- Task From Port Template -->
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label fw-bold mb-2"><i class="ti ti-ship me-1 text-primary"></i> {{ __('قالب مهمة (من الميناء)') }}</label>
                                <select class="form-select update-setting-select" data-key="task_from_port_template" id="task-from-port-template">
                                    <option value="">{{ __('--- اختر قالباً ---') }}</option>
                                    @foreach ($templates as $val)
                                        <option value="{{ $val->id }}" {{ ($settings['task_from_port_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                            {{ $val->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="task-error text-danger small"></span>
                            </div>
                        </div>

                        <!-- Task To Port Template -->
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label fw-bold mb-2"><i class="ti ti-anchor me-1 text-secondary"></i> {{ __('قالب مهمة (إلى الميناء)') }}</label>
                                <select class="form-select update-setting-select" data-key="task_to_port_template" id="task-to-port-template">
                                    <option value="">{{ __('--- اختر قالباً ---') }}</option>
                                    @foreach ($templates as $val)
                                        <option value="{{ $val->id }}" {{ ($settings['task_to_port_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                            {{ $val->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="task-error text-danger small"></span>
                            </div>
                        </div>

                        <!-- Customs Clearance Template -->
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label fw-bold mb-2"><i class="ti ti-building-warehouse me-1 text-danger"></i> {{ __('قالب التخليص الجمركي') }}</label>
                                <select class="form-select update-setting-select" data-key="customs_clearance_template">
                                    <option value="">{{ __('--- اختر قالباً ---') }}</option>
                                    @foreach ($templates as $val)
                                        <option value="{{ $val->id }}" {{ ($settings['customs_clearance_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                            {{ $val->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="task-error text-danger small"></span>
                            </div>
                        </div>

                        <!-- Customs Clearance Agent Template -->
                        <div class="col-md-6 col-lg-3">
                            <div class="form-group">
                                <label class="form-label fw-bold mb-2"><i class="ti ti-id me-1 text-dark"></i> {{ __('قالب وكيل التخليص') }}</label>
                                <select class="form-select update-setting-select" data-key="customs_clearance_agent_template">
                                    <option value="">{{ __('--- اختر قالباً ---') }}</option>
                                    @foreach ($templates as $val)
                                        <option value="{{ $val->id }}" {{ ($settings['customs_clearance_agent_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                            {{ $val->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="task-error text-danger small"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Test Email Modal -->
    <div class="modal fade" id="testEmailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-label-primary py-3">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-send fs-4 me-2 text-primary"></i>
                        <h5 class="modal-title fw-bold mb-0 text-heading">{{ __('اختبار اتصال خادم البريد (SMTP)') }}</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        {{ __('سيقوم النظام بإرسال بريد تجريبي فوري للتحقق من دقة بيانات الاتصال (Host, Port, Username, Password) مع إعطائك تقريراً لحظياً بالنتيجة أو كود الخطأ إن وجد.') }}
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('إرسال البريد التجريبي إلى:') }} <span class="text-danger">*</span></label>
                        <input type="email" id="modal_test_email_recipient" class="form-control"
                            value="{{ auth()->user()?->email ?? 'admin@safedest.com' }}" placeholder="name@example.com">
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="test_using_form_values" checked>
                        <label class="form-check-label small fw-bold" for="test_using_form_values">
                            {{ __('استخدم القيم الحالية المدخلة في النموذج أعلاه (للتجربة قبل الحفظ)') }}
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                    <button type="button" class="btn btn-primary" id="btn_send_test_email">
                        <i class="ti ti-mail-fast me-1"></i> {{ __('إرسال رسالة الاختبار الآن') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Activate Tab from URL Hash on page load (e.g., #mail_settings_card or #saei_settings_card)
        const hash = window.location.hash;
        if (hash) {
            const tabMap = {
                '#mail_settings_card': '#nav-mail-tab',
                '#saei_settings_card': '#nav-saei-tab',
                '#mtahd_settings_card': '#nav-mtahd-tab',
                '#nav-saei': '#nav-saei-tab',
                '#nav-mail': '#nav-mail-tab',
                '#nav-mtahd': '#nav-mtahd-tab',
                '#nav-apps': '#nav-apps-tab',
                '#nav-templates': '#nav-templates-tab',
                '#nav-general': '#nav-general-tab'
            };
            const targetTabSelector = tabMap[hash] || hash + '-tab';
            const targetTrigger = document.querySelector(targetTabSelector) || document.querySelector(`[data-bs-target="${hash}"]`);
            if (targetTrigger) {
                const tab = new bootstrap.Tab(targetTrigger);
                tab.show();
            }
        }

        // Toggle Mail password visibility
        const btnTogglePwd = document.getElementById('btn_toggle_mail_pwd');
        if (btnTogglePwd) {
            btnTogglePwd.addEventListener('click', function () {
                const pwdInput = document.getElementById('mail_password');
                const pwdIcon = document.getElementById('mail_pwd_icon');
                if (pwdInput.type === 'password') {
                    pwdInput.type = 'text';
                    pwdIcon.className = 'ti ti-eye-off';
                } else {
                    pwdInput.type = 'password';
                    pwdIcon.className = 'ti ti-eye';
                }
            });
        }

        // Save Mail Settings
        const btnSaveMail = document.getElementById('btn_save_mail_settings');
        if (btnSaveMail) {
            btnSaveMail.addEventListener('click', function () {
                const btn = this;
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري الحفظ...';

                const payload = {
                    _token: '{{ csrf_token() }}',
                    mail_mailer: document.getElementById('mail_mailer').value,
                    mail_host: document.getElementById('mail_host').value,
                    mail_port: document.getElementById('mail_port').value,
                    mail_encryption: document.getElementById('mail_encryption').value,
                    mail_username: document.getElementById('mail_username').value,
                    mail_password: document.getElementById('mail_password').value,
                    mail_from_address: document.getElementById('mail_from_address').value,
                    mail_from_name: document.getElementById('mail_from_name').value,
                };

                fetch("{{ route('settings.mail.update') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("تم بنجاح") }}',
                            text: data.message,
                            customClass: { confirmButton: 'btn btn-success' },
                            buttonsStyling: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("خطأ") }}',
                            text: data.message || '{{ __("فشل في حفظ إعدادات البريد") }}',
                            customClass: { confirmButton: 'btn btn-danger' },
                            buttonsStyling: false
                        });
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("خطأ") }}',
                        text: '{{ __("تعذر الاتصال بالسيرفر") }}',
                        customClass: { confirmButton: 'btn btn-danger' },
                        buttonsStyling: false
                    });
                });
            });
        }

        // Open Test Email Modal
        const btnOpenModal = document.getElementById('btn_open_test_mail_modal');
        if (btnOpenModal) {
            btnOpenModal.addEventListener('click', function () {
                const modal = new bootstrap.Modal(document.getElementById('testEmailModal'));
                modal.show();
            });
        }

        // Send Test Email
        const btnSendTest = document.getElementById('btn_send_test_email');
        if (btnSendTest) {
            btnSendTest.addEventListener('click', function () {
                const recipient = document.getElementById('modal_test_email_recipient').value.trim();
                if (!recipient) {
                    Swal.fire({ icon: 'warning', title: '{{ __("تنبيه") }}', text: '{{ __("يرجى إدخال عنوان بريد إلكتروني صالح لاستقبال الاختبار.") }}' });
                    return;
                }

                const btn = this;
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري الفحص والإرسال...';

                const payload = {
                    _token: '{{ csrf_token() }}',
                    test_email: recipient,
                };

                const useCurrentValues = document.getElementById('test_using_form_values').checked;
                if (useCurrentValues) {
                    payload.mail_mailer = document.getElementById('mail_mailer').value;
                    payload.mail_host = document.getElementById('mail_host').value;
                    payload.mail_port = document.getElementById('mail_port').value;
                    payload.mail_encryption = document.getElementById('mail_encryption').value;
                    payload.mail_username = document.getElementById('mail_username').value;
                    payload.mail_password = document.getElementById('mail_password').value;
                    payload.mail_from_address = document.getElementById('mail_from_address').value;
                    payload.mail_from_name = document.getElementById('mail_from_name').value;
                }

                fetch("{{ route('settings.mail.test-connection') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (data.success) {
                        const modalEl = document.getElementById('testEmailModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();

                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("الاتصال والإرسال ناجح!") }}',
                            text: data.message,
                            customClass: { confirmButton: 'btn btn-success' },
                            buttonsStyling: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("فشل الاتصال / الإرسال") }}',
                            html: '<div class="text-start dir-ltr font-monospace small bg-light p-3 rounded mt-2 text-danger">' +
                                (data.message || 'Unknown error') + '</div>',
                            customClass: { confirmButton: 'btn btn-danger' },
                            buttonsStyling: false
                        });
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    Swal.fire({ icon: 'error', title: '{{ __("خطأ") }}', text: '{{ __("تعذر الاتصال بالسيرفر") }}' });
                });
            });
        }

        // Test Mtahd Connection Button
        const btnTest = document.getElementById('btn_test_mtahd_conn');
        if (btnTest) {
            btnTest.addEventListener('click', function () {
                Swal.fire({
                    title: '{{ __("جاري فحص الاتصال...") }}',
                    text: '{{ __("التحقق من صحة مفتاح الربط والاتصال بمنصة متعهد") }}',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch("{{ route('settings.mtahd.test-connection') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: '{{ __("الاتصال سليم") }}', text: data.message });
                    } else {
                        Swal.fire({ icon: 'error', title: '{{ __("فشل الاتصال") }}', text: data.message });
                    }
                })
                .catch(() => Swal.fire({ icon: 'error', title: '{{ __("خطأ") }}', text: '{{ __("تعذر الاتصال بالسيرفر") }}' }));
            });
        }

        // Create Platform Account in Mtahd Button
        const btnCreate = document.getElementById('btn_create_platform_mtahd');
        if (btnCreate) {
            btnCreate.addEventListener('click', function () {
                Swal.fire({
                    title: '{{ __("إنشاء / توثيق حساب المنصة في متعهد") }}',
                    text: '{{ __("سيتم إرسال بيانات المنصة الرسمية إلى متعهد للحصول على رقم بائع معتمد (Platform Seller Number).") }}',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '{{ __("نعم، أنشئ الحساب") }}',
                    cancelButtonText: '{{ __("إلغاء") }}',
                    customClass: { confirmButton: 'btn btn-primary me-2', cancelButton: 'btn btn-label-secondary' },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({ title: '{{ __("جاري الإرسال...") }}', didOpen: () => { Swal.showLoading(); } });

                        fetch("{{ route('settings.mtahd.create-account') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                name: 'منصة سيف ديست للخدمات اللوجستية (SafeDests)',
                                phone: '+966500000000',
                                email: 'finance@safedests.com'
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('setting_mtahd_seller_num').value = data.customer_number;
                                Swal.fire({ icon: 'success', title: '{{ __("تم بنجاح") }}', text: data.message });
                            } else {
                                Swal.fire({ icon: 'error', title: '{{ __("فشل الإنشاء") }}', text: data.message });
                            }
                        })
                        .catch(() => Swal.fire({ icon: 'error', title: '{{ __("خطأ") }}', text: '{{ __("تعذر الاتصال بالسيرفر") }}' }));
                    }
                });
            });
        }

        // Copy setting value to clipboard
        window.copySettingVal = function (elementId) {
            const input = document.getElementById(elementId);
            if (!input) return;
            navigator.clipboard.writeText(input.value).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: '{{ __("تم النسخ إلى الحافظة بنجاح!") }}',
                    showConfirmButton: false,
                    timer: 2000
                });
            });
        };

        // Toggle Saei API Key visibility
        const btnToggleSaeiKey = document.getElementById('btn_toggle_saei_api_key');
        if (btnToggleSaeiKey) {
            btnToggleSaeiKey.addEventListener('click', function () {
                const input = document.getElementById('saei_api_key');
                const icon = document.getElementById('saei_api_key_icon');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'ti ti-eye-off';
                } else {
                    input.type = 'password';
                    icon.className = 'ti ti-eye';
                }
            });
        }

        // Toggle Cloud Token visibility
        const btnToggleCloudToken = document.getElementById('btn_toggle_cloud_token');
        if (btnToggleCloudToken) {
            btnToggleCloudToken.addEventListener('click', function () {
                const input = document.getElementById('whatsapp_cloud_token');
                const icon = document.getElementById('cloud_token_icon');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'ti ti-eye-off';
                } else {
                    input.type = 'password';
                    icon.className = 'ti ti-eye';
                }
            });
        }

        // Save Saei & WhatsApp Settings
        const saveSaeiButtons = document.querySelectorAll('#btn_save_saei_settings, .btn-save-saei-settings');
        if (saveSaeiButtons.length > 0) {
            saveSaeiButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    const originalHtml = btn.innerHTML;
                    saveSaeiButtons.forEach(b => {
                        b.disabled = true;
                        b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __("جاري الحفظ...") }}';
                    });

                    const payload = {
                        _token: '{{ csrf_token() }}',
                        whatsapp_provider: document.getElementById('setting_whatsapp_provider') ? document.getElementById('setting_whatsapp_provider').value : 'saei',
                        saei_otp_enabled: document.getElementById('setting_saei_otp_enabled').checked ? '1' : '0',
                        saei_simulation: document.getElementById('setting_saei_simulation').checked ? '1' : '0',
                        saei_api_key: document.getElementById('saei_api_key').value,
                        saei_base_url: document.getElementById('saei_base_url').value,
                        saei_from_phone_id: document.getElementById('saei_from_phone_id').value,
                        saei_template_id: document.getElementById('saei_template_id').value,
                        saei_callback_secret: document.getElementById('saei_callback_secret').value,
                        whatsapp_cloud_token: document.getElementById('whatsapp_cloud_token').value,
                        whatsapp_cloud_waba_id: document.getElementById('whatsapp_cloud_waba_id').value,
                        whatsapp_cloud_phone_id: document.getElementById('whatsapp_cloud_phone_id').value,
                        whatsapp_verify_token: document.getElementById('whatsapp_verify_token').value,
                    };

                    fetch("{{ route('settings.saei.update') }}", {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(res => res.json())
                    .then(data => {
                        saveSaeiButtons.forEach(b => {
                            b.disabled = false;
                            b.innerHTML = originalHtml;
                        });
                        if (data.success) {
                            const infoVerify = document.getElementById('info_verify_token');
                            if (infoVerify) infoVerify.value = payload.whatsapp_verify_token;

                            Swal.fire({
                                icon: 'success',
                                title: '{{ __("تم الحفظ بنجاح") }}',
                                text: data.message,
                                customClass: { confirmButton: 'btn btn-success' },
                                buttonsStyling: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("خطأ") }}',
                                text: data.message || '{{ __("فشل حفظ الإعدادات") }}',
                                customClass: { confirmButton: 'btn btn-danger' },
                                buttonsStyling: false
                            });
                        }
                    })
                    .catch(() => {
                        saveSaeiButtons.forEach(b => {
                            b.disabled = false;
                            b.innerHTML = originalHtml;
                        });
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("خطأ") }}',
                            text: '{{ __("تعذر الاتصال بالسيرفر") }}',
                            customClass: { confirmButton: 'btn btn-danger' },
                            buttonsStyling: false
                        });
                    });
                });
            });
        }

        // Test Saei Connection Button
        const btnTestSaei = document.getElementById('btn_test_saei_connection');
        if (btnTestSaei) {
            btnTestSaei.addEventListener('click', function () {
                const originalHtml = btnTestSaei.innerHTML;
                btnTestSaei.disabled = true;
                btnTestSaei.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> {{ __("جاري الفحص...") }}';

                const testPayload = {
                    saei_api_key: document.getElementById('saei_api_key').value,
                    saei_base_url: document.getElementById('saei_base_url').value,
                    saei_from_phone_id: document.getElementById('saei_from_phone_id').value,
                };

                fetch("{{ route('settings.saei.test-connection') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(testPayload)
                })
                .then(res => res.json())
                .then(data => {
                    btnTestSaei.disabled = false;
                    btnTestSaei.innerHTML = originalHtml;
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("نجاح الاتصال بساعي") }}',
                            text: data.message,
                            customClass: { confirmButton: 'btn btn-success' },
                            buttonsStyling: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("فشل الاتصال") }}',
                            text: data.message,
                            customClass: { confirmButton: 'btn btn-danger' },
                            buttonsStyling: false
                        });
                    }
                })
                .catch(err => {
                    btnTestSaei.disabled = false;
                    btnTestSaei.innerHTML = originalHtml;
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("خطأ") }}',
                        text: '{{ __("تعذر إتمام فحص الاتصال بساعي") }}',
                        customClass: { confirmButton: 'btn btn-danger' },
                        buttonsStyling: false
                    });
                });
            });
        }
    });
</script>
@endsection
