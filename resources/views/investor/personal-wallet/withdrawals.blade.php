@extends('layouts/layoutMaster')

@section('title', __('Commission Withdrawal Requests - Investor'))

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- رأس الصفحة --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-cash-banknote me-2 text-warning"></i>طلبات سحب العمولات</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('investor.dashboard') }}">{{ __('Home') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('investor.personal-wallet') }}">{{ __('Commission Wallet') }}</a></li>
                    <li class="breadcrumb-item active">طلبات سحب العمولات</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('investor.personal-wallet') }}" class="btn btn-label-secondary shadow-sm">
                <i class="ti ti-arrow-right me-1"></i> العودة لمحفظة العمولات
            </a>
            <button type="button" class="btn btn-warning shadow-sm text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#withdrawCommissionModal">
                <i class="ti ti-plus me-1"></i> طلب سحب جديد
            </button>
        </div>
    </div>

    {{-- تنبيهات الفلاش --}}
    @foreach(['success', 'error', 'info'] as $msg)
        @if(session($msg))
            <div class="alert alert-{{ $msg === 'error' ? 'danger' : $msg }} alert-dismissible mb-4" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                {{ session($msg) }}
            </div>
        @endif
    @endforeach

    {{-- بطاقات الإحصائيات --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-files ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ $withdrawals->total() }}</h4>
                    </div>
                    <p class="mb-0 fw-medium text-muted">إجمالي طلبات السحب</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i class="ti ti-clock ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0 text-warning">{{ $withdrawals->where('status', 'pending')->count() }}</h4>
                    </div>
                    <p class="mb-0 fw-medium text-muted">قيد المراجعة والتدقيق</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="ti ti-check ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0 text-success">{{ $withdrawals->where('status', 'approved')->count() }}</h4>
                    </div>
                    <p class="mb-0 fw-medium text-muted">الطلبات المقبولة والمصروفة</p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-danger"><i class="ti ti-x ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0 text-danger">{{ $withdrawals->where('status', 'rejected')->count() }}</h4>
                    </div>
                    <p class="mb-0 fw-medium text-muted">الطلبات المرفوضة</p>
                </div>
            </div>
        </div>
    </div>

    {{-- بطاقة الجدول مع الفلترة --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0 d-flex align-items-center">
                <i class="ti ti-list me-2 text-primary"></i> سجل طلبات السحب
            </h5>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('investor.commission-withdrawals') }}" class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-label-primary' }}">الكل</a>
                <a href="{{ route('investor.commission-withdrawals', ['status' => 'pending']) }}" class="btn btn-sm {{ request('status') === 'pending' ? 'btn-warning' : 'btn-label-warning' }}">قيد المراجعة</a>
                <a href="{{ route('investor.commission-withdrawals', ['status' => 'approved']) }}" class="btn btn-sm {{ request('status') === 'approved' ? 'btn-success' : 'btn-label-success' }}">مقبولة</a>
                <a href="{{ route('investor.commission-withdrawals', ['status' => 'rejected']) }}" class="btn btn-sm {{ request('status') === 'rejected' ? 'btn-danger' : 'btn-label-danger' }}">مرفوضة</a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3">رقم الطلب</th>
                        <th class="py-3">تاريخ الطلب</th>
                        <th class="py-3">المبلغ المطلوب</th>
                        <th class="py-3">بيانات التحويل</th>
                        <th class="py-3 text-center">الحالة</th>
                        <th class="py-3">الإيصال / تفاصيل الصرف</th>
                        <th class="py-3">ملاحظات / سبب الرفض</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($withdrawals as $withdrawal)
                    <tr>
                        <td class="fw-bold">#{{ $withdrawal->id }}</td>
                        <td>
                            <div>{{ $withdrawal->created_at->format('Y-m-d') }}</div>
                            <small class="text-muted">{{ $withdrawal->created_at->format('H:i') }}</small>
                        </td>
                        <td>
                            <strong class="text-primary fs-6">{{ number_format($withdrawal->amount, 2) }}</strong>
                            <small class="text-muted">ر.س</small>
                        </td>
                        <td>
                            @if($withdrawal->bank_name || $withdrawal->iban_number)
                                <div class="fw-medium small">{{ $withdrawal->bank_name ?: 'غير محدد' }}</div>
                                <div class="text-muted font-monospace small" style="font-size: 0.78rem;">{{ $withdrawal->iban_number ?: $withdrawal->account_number }}</div>
                                @if($withdrawal->account_holder)
                                    <small class="text-muted">{{ $withdrawal->account_holder }}</small>
                                @endif
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($withdrawal->status === 'pending')
                                <span class="badge bg-label-warning px-3 py-2">
                                    <i class="ti ti-clock me-1"></i> قيد المراجعة
                                </span>
                            @elseif($withdrawal->status === 'approved')
                                <span class="badge bg-label-success px-3 py-2">
                                    <i class="ti ti-check me-1"></i> مقبول ومصروف
                                </span>
                            @elseif($withdrawal->status === 'rejected')
                                <span class="badge bg-label-danger px-3 py-2">
                                    <i class="ti ti-x me-1"></i> مرفوض
                                </span>
                            @else
                                <span class="badge bg-label-secondary px-3 py-2">{{ $withdrawal->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($withdrawal->receipt_image)
                                <button type="button" class="btn btn-sm btn-label-info" onclick="viewReceipt('{{ $withdrawal->receipt_url }}')">
                                    <i class="ti ti-file-certificate me-1"></i> عرض الإيصال
                                </button>
                                @if($withdrawal->processed_at)
                                    <div class="small text-muted mt-1">تاريخ الصرف: {{ $withdrawal->processed_at->format('Y-m-d') }}</div>
                                @endif
                            @elseif($withdrawal->status === 'approved')
                                <span class="badge bg-label-success">تم الصرف</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            @if($withdrawal->status === 'rejected' && $withdrawal->rejection_reason)
                                <div class="alert alert-danger py-1 px-2 mb-0 small" style="border-radius: 6px;">
                                    <strong><i class="ti ti-alert-triangle me-1"></i>سبب الرفض:</strong>
                                    <div>{{ $withdrawal->rejection_reason }}</div>
                                </div>
                            @elseif($withdrawal->admin_notes)
                                <span class="small text-muted">{{ $withdrawal->admin_notes }}</span>
                            @elseif($withdrawal->investor_notes)
                                <span class="small text-muted">ملاحظتك: {{ $withdrawal->investor_notes }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ti ti-files-off display-3 text-light mb-3"></i>
                                <h5>لا توجد طلبات سحب عمولات مسجلة حالياً</h5>
                                <p class="mb-3">يمكنك تقديم طلب لسحب مبالغ من عمولاتك المتاحة مباشرة من صفحة محفظة العمولات.</p>
                                <a href="{{ route('investor.personal-wallet') }}" class="btn btn-primary btn-sm">
                                    <i class="ti ti-wallet me-1"></i> الذهاب لمحفظة العمولات
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
        <div class="card-footer border-top py-3">
            {{ $withdrawals->links() }}
        </div>
        @endif
    </div>

    <!-- نافذة معاينة الإيصال -->
    <div class="modal fade" id="receiptPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="ti ti-file-certificate text-success me-2 ti-md"></i>
                        إيصال تحويل السحب
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-3" id="receiptPreviewBody">
                    <!-- Dynamic Receipt Content -->
                </div>
                <div class="modal-footer border-top">
                    <a href="#" id="downloadReceiptBtn" class="btn btn-primary" download>
                        <i class="ti ti-download me-1"></i> تحميل الإيصال
                    </a>
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    {{-- نافذة طلب سحب العمولة من محفظة العمولات --}}
    @php
        $availWithdrawable = (float) ($personalWallet?->withdrawable_balance ?? 0);
    @endphp
    <div class="modal fade" id="withdrawCommissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="ti ti-cash-banknote text-warning me-2 ti-md"></i>
                        طلب سحب من محفظة العمولات
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('investor.personal-wallet.request-withdrawal') }}" id="withdrawCommissionForm">
                    @csrf
                    <div class="modal-body">
                        @if($availWithdrawable <= 0)
                            <div class="alert alert-warning d-flex align-items-start mb-4">
                                <i class="ti ti-alert-triangle me-2 mt-1"></i>
                                <div class="small">
                                    <strong>لا يوجد رصيد متاح للسحب حالياً (0.00 ر.س).</strong>
                                    <p class="mb-0 mt-1">
                                        تصبح عمولات المهام متاحة للسحب بعد سداد العميل واسترداد رأس مال المهام الممولة إلى محفظة الاستثمار.
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-warning d-flex align-items-start mb-4">
                                <i class="ti ti-info-circle me-2 mt-1"></i>
                                <div class="small">
                                    <strong>تنبيه هام:</strong>
                                    <p class="mb-0 mt-1">يتم تقديم الطلب للإدارة لمراجعته وإتمام التحويل لحسابك البنكي، وتوثيق إيصال السداد وخصمه من محفظة العمولات فور الاعتماد.</p>
                                </div>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-bold">المبلغ المطلوب سحبه (ر.س) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="amount" id="withdraw_amount" class="form-control"
                                    step="0.01" min="1"
                                    max="{{ number_format($availWithdrawable, 2, '.', '') }}"
                                    value="{{ $availWithdrawable > 0 ? number_format($availWithdrawable, 2, '.', '') : '0.00' }}"
                                    {{ $availWithdrawable <= 0 ? 'disabled' : '' }}
                                    required>
                                <button type="button" class="btn btn-label-secondary" id="withdrawMaxBtn" {{ $availWithdrawable <= 0 ? 'disabled' : '' }}>{{ __('Maximum') }}</button>
                            </div>
                            <small class="text-muted">الرصيد المتاح للسحب حالياً: <strong class="{{ $availWithdrawable > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($availWithdrawable, 2) }} ر.س</strong></small>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small">اسم البنك</label>
                                <input type="text" name="bank_name" class="form-control" value="{{ $investor->bank_name }}" placeholder="مثال: مصرف الراجحي" {{ $availWithdrawable <= 0 ? 'disabled' : '' }}>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">اسم المستفيد</label>
                                <input type="text" name="account_holder" class="form-control" value="{{ $investor->name }}" placeholder="الاسم ثلاثي">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small">رقم الآيبان (IBAN) أو الحساب</label>
                                <input type="text" name="iban_number" class="form-control font-monospace" value="{{ $investor->iban_number ?: $investor->account_number }}" placeholder="SA0000000000000000000000" {{ $availWithdrawable <= 0 ? 'disabled' : '' }}>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label small">ملاحظات للمسؤول (اختياري)</label>
                            <textarea name="investor_notes" class="form-control" rows="2" placeholder="أي تفاصيل أو تعليمات إضافية بخصوص السحب..." {{ $availWithdrawable <= 0 ? 'disabled' : '' }}></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-warning text-dark fw-bold" {{ $availWithdrawable <= 0 ? 'disabled' : '' }}>
                            <i class="ti ti-send me-1"></i> تقديم طلب السحب
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@section('page-script')
<script>
    function viewReceipt(url) {
        const modalBody = document.getElementById('receiptPreviewBody');
        const downloadBtn = document.getElementById('downloadReceiptBtn');
        const ext = url.split('.').pop().toLowerCase();

        modalBody.innerHTML = '';
        downloadBtn.href = url;

        if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext)) {
            modalBody.innerHTML = `<img src="${url}" class="img-fluid rounded shadow-sm" style="max-height: 70vh;" alt="Receipt">`;
        } else if (ext === 'pdf') {
            modalBody.innerHTML = `<iframe src="${url}" width="100%" height="520px" style="border: none;"></iframe>`;
        } else {
            modalBody.innerHTML = `
                <div class="py-5">
                    <i class="ti ti-file-text display-1 text-muted mb-3"></i>
                    <h5>المعاينة غير متاحة لهذا الملف</h5>
                    <p class="text-muted">يرجى تحميل الملف للاطلاع عليه.</p>
                </div>`;
        }

        new bootstrap.Modal(document.getElementById('receiptPreviewModal')).show();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const withdrawMaxBtn = document.getElementById('withdrawMaxBtn');
        const withdrawAmount = document.getElementById('withdraw_amount');
        if (withdrawMaxBtn && withdrawAmount) {
            withdrawMaxBtn.addEventListener('click', function () {
                withdrawAmount.value = withdrawAmount.getAttribute('max');
            });
        }
    });
</script>
@endsection
