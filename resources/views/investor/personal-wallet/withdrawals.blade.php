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
                <i class="ti ti-arrow-right me-1"></i> {{ __('Back to Commission Wallet') }}
            </a>
            @if(!empty($hasActiveRequest))
                <button type="button" class="btn btn-secondary shadow-sm fw-bold disabled" disabled title="{{ __('لديك طلب سحب نقدي قيد المعالجة حالياً') }}">
                    <i class="ti ti-clock me-1"></i> {{ __('طلب سحب جديد (لديك طلب قيد المعالجة)') }}
                </button>
            @else
                <button type="button" class="btn btn-warning shadow-sm text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#withdrawCommissionModal">
                    <i class="ti ti-plus me-1"></i> {{ __('طلب سحب جديد') }}
                </button>
            @endif
        </div>
    </div>

    {{-- تنبيه في حال وجود طلب سحب قيد المعالجة --}}
    @if(!empty($hasActiveRequest))
        <div class="alert alert-warning border-warning d-flex align-items-center mb-4 shadow-sm" role="alert">
            <i class="ti ti-clock-pause me-2 fs-3 text-warning"></i>
            <div>
                <strong class="d-block mb-1">{{ __('يوجد طلب سحب نقدي قيد المعالجة حالياً') }}</strong>
                <span class="small">{{ __('لديك طلب سحب عمولات قيد المراجعة والمعالجة المصرفية حالياً. لا يمكنك تقديم طلب سحب إضافي حتى يتم الانتهاء من معالجة هذا الطلب بالكامل.') }}</span>
            </div>
        </div>
    @endif

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
                            @elseif($withdrawal->status === 'processing')
                                <span class="badge bg-label-info px-3 py-2">
                                    <i class="ti ti-refresh me-1"></i> قيد التحويل البنكي
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
        $investorIban = trim($investor->iban_number ?: ($investor->account_number ?: ''));
        $saudiBankInfo = $investorIban ? \App\Services\HyperPayPayoutService::getSaudiBankInfoFromIban($investorIban) : null;
        $investorBankName = $investor->bank_name ?: ($saudiBankInfo['name'] ?? 'غير محدد');
        $investorBeneficiary = $investor->beneficiary_name ?: $investor->name;
        $hasBankDetails = !empty($investorIban);
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
                        @if(!empty($hasActiveRequest))
                            <div class="alert alert-warning d-flex align-items-start mb-3">
                                <i class="ti ti-clock-pause me-2 mt-1 fs-4"></i>
                                <div>
                                    <strong class="d-block mb-1">{{ __('You have an active withdrawal request under processing') }}</strong>
                                    <p class="mb-0 small">
                                        {{ __('You already have a commission withdrawal request under review or banking processing. You cannot submit an additional request until the current request is fully processed.') }}
                                    </p>
                                </div>
                            </div>
                        @elseif(!$hasBankDetails)
                            <div class="alert alert-danger d-flex align-items-start mb-3">
                                <i class="ti ti-alert-triangle me-2 mt-1 fs-4"></i>
                                <div>
                                    <strong class="d-block mb-1">{{ __('Bank account details not registered!') }}</strong>
                                    <p class="mb-0 small">
                                        {{ __('You have not added your IBAN to your profile yet. Please contact platform administration to configure your bank account details.') }}
                                    </p>
                                </div>
                            </div>
                        @elseif($availWithdrawable <= 0)
                            <div class="alert alert-warning d-flex align-items-start mb-3">
                                <i class="ti ti-alert-triangle me-2 mt-1"></i>
                                <div class="small">
                                    <strong>{{ __('No withdrawable balance currently available (0.00 SAR).') }}</strong>
                                    <p class="mb-0 mt-1">
                                        {{ __('Task commissions become withdrawable after customer payment and return of capital to the investment wallet.') }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-success d-flex align-items-start mb-3">
                                <i class="ti ti-shield-check me-2 mt-1 text-success fs-5"></i>
                                <div class="small">
                                    <strong>{{ __('Secure & Direct Bank Transfer:') }}</strong>
                                    <p class="mb-0 mt-1">{{ __('The amount will be transferred automatically to your verified bank account below once approved by financial administration.') }}</p>
                                </div>
                            </div>
                        @endif

                        {{-- بطاقة الحساب البنكي المعتمد المسجل (للقراءة فقط) --}}
                        @if($hasBankDetails)
                        <div class="card bg-label-secondary mb-3 border">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark small d-flex align-items-center">
                                        <i class="ti ti-building-bank me-1 text-primary"></i> {{ __('Approved Bank Account for Transfer') }}
                                    </span>
                                    <span class="badge bg-label-success rounded-pill px-2 py-0 small">
                                        <i class="ti ti-check me-1 small"></i>{{ __('Verified') }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">{{ __('Bank:') }}</span>
                                    <strong class="text-heading small">{{ $investorBankName }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">{{ __('Beneficiary Name:') }}</span>
                                    <strong class="text-heading small">{{ $investorBeneficiary }}</strong>
                                </div>
                                <div class="mt-2 pt-2 border-top">
                                    <span class="text-muted small d-block mb-1">{{ __('IBAN Number:') }}</span>
                                    <div class="bg-white rounded px-2 py-1 border font-monospace small fw-bold text-dark text-break" dir="ltr">
                                        {{ $investorIban }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-bold">{{ __('Requested Withdrawal Amount (SAR)') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="amount" id="withdraw_amount" class="form-control"
                                    step="0.01" min="1"
                                    max="{{ number_format($availWithdrawable, 2, '.', '') }}"
                                    value="{{ $availWithdrawable > 0 ? number_format($availWithdrawable, 2, '.', '') : '0.00' }}"
                                    {{ ($availWithdrawable <= 0 || !$hasBankDetails || !empty($hasActiveRequest)) ? 'disabled' : '' }}
                                    required>
                                <button type="button" class="btn btn-label-secondary" id="withdrawMaxBtn" {{ ($availWithdrawable <= 0 || !$hasBankDetails || !empty($hasActiveRequest)) ? 'disabled' : '' }}>{{ __('Maximum') }}</button>
                            </div>
                            <small class="text-muted">{{ __('Current Withdrawable Balance:') }} <strong class="{{ $availWithdrawable > 0 ? 'text-success' : 'text-danger' }}">{{ number_format($availWithdrawable, 2) }} {{ __('SAR') }}</strong></small>
                        </div>

                        <div class="mb-0">
                            <label class="form-label small">{{ __('Notes for Administrator (Optional)') }}</label>
                            <textarea name="investor_notes" class="form-control" rows="2" placeholder="{{ __('Any notes or instructions regarding the withdrawal...') }}" {{ ($availWithdrawable <= 0 || !$hasBankDetails || !empty($hasActiveRequest)) ? 'disabled' : '' }}></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-warning text-dark fw-bold" {{ ($availWithdrawable <= 0 || !$hasBankDetails || !empty($hasActiveRequest)) ? 'disabled' : '' }}>
                            <i class="ti ti-send me-1"></i> {{ __('Submit Withdrawal Request') }}
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
