@extends('layouts/layoutMaster')

@section('title', 'المحفظة الشخصية - العمولات')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- رأس الصفحة --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-cash me-2 text-success"></i>محفظة العمولات</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('investor.dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item active">محفظة العمولات</li>
                </ol>
            </nav>
        </div>
        {{-- زر احتساب العمولات للمضارب العام فقط --}}
        <div class="d-flex align-items-center gap-2">
        @if(($personalWallet?->withdrawable_balance ?? 0) > 0)
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#reinvestProfitsModal">
            <i class="ti ti-refresh me-1"></i> استثمار الأرباح
        </button>
        @endif
        @if($contract && $contract->contract_type === 'general_investment' && $contract->isActive())
            <button type="button" class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#calculateCommissionsModal">
                <i class="ti ti-calculator me-1"></i> احتساب العمولات الآن
            </button>
        @endif
        </div>
    </div>

    @foreach(['success','error','info'] as $msg)
        @if(session($msg))
            <div class="alert alert-{{ $msg === 'error' ? 'danger' : $msg }} alert-dismissible mb-4" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                {{ session($msg) }}
            </div>
        @endif
    @endforeach

    {{-- معلومات العقد للمضارب العام --}}
    @if($contract && $contract->contract_type === 'general_investment')
    <div class="card bg-label-info mb-4 border-0">
        <div class="card-body py-3">
            <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                    <span class="avatar-initial rounded-circle bg-info"><i class="ti ti-info-circle"></i></span>
                </div>
                <div>
                    <p class="mb-0 small fw-medium text-info">نطاق الاستحقاق الفعلي</p>
                    <p class="mb-0 small">
                        أنت تحصل على <strong>{{ $contract->commission_value }}{{ $contract->commission_type === 'percentage' ? '%' : ' ر.س ثابت' }}</strong>
                        من صافي عمولة المنصة لكل مهمة تنطبق عليها شروط عقدك.
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- تنبيه خاص بالمستثمر بالمهام --}}
    @if($contract && $contract->contract_type === 'task_investment')
    <div class="card mb-4 border-0" style="background: linear-gradient(135deg, #f0f7ff 0%, #e8f4e8 100%); border-right: 4px solid #696cff !important; border-right-width: 4px !important;">
        <div class="card-body py-3">
            <div class="d-flex align-items-start gap-3">
                <div class="avatar avatar-sm flex-shrink-0 mt-1">
                    <span class="avatar-initial rounded-circle" style="background: linear-gradient(135deg, #696cff, #7367f0); color:#fff;">
                        <i class="ti ti-shield-check"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <p class="mb-1 fw-bold" style="color:#696cff; font-size: 0.9rem;">
                        <i class="ti ti-calculator me-1"></i>كيف يُحتسب الرصيد القابل للسحب؟
                    </p>
                    <p class="mb-2 small text-muted">
                        بما أنك مستثمر بالمهام، يتم احتساب رصيدك القابل للسحب بدقة محاسبية وفق المعادلة التالية:
                    </p>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                        <span class="badge" style="background:#e8f4e8; color:#28a745; font-size:0.8rem; padding: 6px 10px;">
                            <i class="ti ti-check me-1"></i>عمولات المهام المسواة
                        </span>
                        <span class="text-muted fw-bold">+</span>
                        <span class="badge" style="background:#fff3cd; color:#856404; font-size:0.8rem; padding: 6px 10px;">
                            <i class="ti ti-gift me-1"></i>الإيداعات اليدوية (مكافآت)
                        </span>
                        <span class="text-muted fw-bold">−</span>
                        <span class="badge" style="background:#fde8e8; color:#dc3545; font-size:0.8rem; padding: 6px 10px;">
                            <i class="ti ti-arrow-up me-1"></i>إجمالي السحوبات السابقة
                        </span>
                    </div>
                    <p class="mb-0 small" style="color:#555;">
                        <i class="ti ti-info-circle me-1 text-primary"></i>
                        عمولة المهمة تصبح <strong>قابلة للسحب</strong> فقط بعد تسوية مبلغها وإرجاع رأس المال إلى محفظة استثمارك.
                    </p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- بطاقات الإحصائيات --}}
    <div class="row g-4 mb-4">
        {{-- الرصيد القابل للسحب --}}
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100" style="border-top: 3px solid #28a745; box-shadow: 0 4px 18px rgba(40,167,69,0.10);">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded" style="background: linear-gradient(135deg,#28a745,#20c997); color:#fff;">
                                <i class="ti ti-wallet ti-md"></i>
                            </span>
                        </div>
                        <h4 class="ms-1 mb-0 text-success">{{ number_format($personalWallet?->withdrawable_balance ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-bold">الرصيد المتاح للسحب</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-success me-1">ر.س</span>
                        @if($contract && $contract->contract_type === 'task_investment')
                            عمولات المهام المسواة + الإيداعات اليدوية − السحوبات
                        @else
                            رصيد أرباحك الصافي المتاح
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- إجمالي العمولات المكتسبة --}}
        <div class="col-sm-6 col-xl-4">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-trending-up ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($personalWallet?->credit ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-medium">إجمالي العمولات المكتسبة</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-primary me-1">ر.س</span>
                        @if($contract && $contract->contract_type === 'task_investment')
                            <span class="badge bg-label-warning" style="font-size:0.72rem;">
                                <i class="ti ti-clock-hour-4 me-1"></i>قد تتضمن عمولات غير مسواة
                            </span>
                        @else
                            إجمالي ما دخل المحفظة
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- عدد العمليات --}}
        <div class="col-sm-6 col-xl-4">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="ti ti-checklist ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ $transactions->total() ?? 0 }}</h4>
                    </div>
                    <p class="mb-1 fw-medium">عدد العمليات</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-info me-1">عمولة</span> مسجلة في سجلاتك
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- فلاتر البحث --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">نوع العملية</label>
                    <select name="type" class="form-select">
                        <option value="">الكل</option>
                        <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>عمولة / إيداع</option>
                        <option value="debit"  {{ request('type') === 'debit'  ? 'selected' : '' }}>سحب / إعادة استثمار</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>فلترة</button>
                    <a href="{{ route('investor.personal-wallet') }}" class="btn btn-label-secondary w-100">تصفير</a>
                </div>
            </form>
        </div>
    </div>

    {{-- جدول العمولات --}}
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">سجل حركات محفظة العمولات</h5>
            <span class="badge bg-label-secondary">إجمالي العمليات: {{ $transactions->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-muted small">نوع العملية</th>
                        <th class="text-muted small">المهمة</th>
                        <th class="text-muted small">المبلغ</th>
                        <th class="text-muted small">البيان</th>
                        <th class="text-muted small">التاريخ والوقت</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($transactions as $tx)
                    @php
                        $isReinvest = $tx->transaction_type === 'debit' && str_contains($tx->description ?? '', 'إعادة استثمار الأرباح');
                    @endphp
                    <tr>
                        <td>
                            @if($tx->transaction_type === 'credit')
                                <span class="badge bg-label-success"><i class="ti ti-plus ti-xs me-1"></i>عمولة</span>
                            @elseif($isReinvest)
                                <span class="badge bg-label-primary"><i class="ti ti-refresh ti-xs me-1"></i>إعادة استثمار</span>
                            @else
                                <span class="badge bg-label-danger"><i class="ti ti-minus ti-xs me-1"></i>سحب</span>
                            @endif
                        </td>
                        <td>
                            @if($tx->task_id)
                                <span class="badge bg-label-primary">#{{ $tx->task_id }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold {{ $tx->transaction_type === 'credit' ? 'text-success' : 'text-danger' }}">
                                {{ $tx->transaction_type === 'credit' ? '+' : '−' }}{{ number_format($tx->amount, 2) }} ر.س
                            </span>
                        </td>
                        <td class="text-truncate" style="max-width: 280px;">{{ $tx->description ?? '—' }}</td>
                        <td class="small">{{ $tx->created_at->format('Y-m-d') }} <br> <span class="text-muted">{{ $tx->created_at->format('H:i') }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <img src="{{ asset('assets/img/illustrations/empty-state.png') }}" alt="Empty state" width="120" class="mb-3 opacity-50">
                            <p class="text-muted">لا توجد حركات مسجلة حتى الآن.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer d-flex justify-content-center border-top">
            {{ $transactions->appends(request()->input())->links() }}
        </div>
        @endif
    </div>

</div>

    {{-- Modal احتساب العمولات --}}
    @if($contract && $contract->contract_type === 'general_investment' && $contract->isActive())
    <div class="modal fade" id="calculateCommissionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="ti ti-calculator text-success me-2 ti-md"></i>
                        تأكيد احتساب العمولات
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('investor.personal-wallet.calculate') }}" id="calculateCommissionsForm">
                    @csrf
                    <div class="modal-body">
                        <!-- تعليمات الاحتساب -->
                        <div class="alert alert-info d-flex align-items-start mb-4">
                            <i class="ti ti-info-circle me-2 mt-1"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold">تعليمات هامة:</h6>
                                <ul class="mb-0 ps-3 small">
                                    <li>سيتم فحص جميع المهام ضمن فترة عقدك الحالي.</li>
                                    <li>سيتم احتساب العمولات للمهام التي لم يتم احتسابها مسبقاً فقط.</li>
                                    <li>ستتم إضافة الأرباح مباشرة إلى رصيدك المتاح للسحب.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- طلب كلمة المرور -->
                        <div class="mb-0">
                            <label class="form-label fw-bold mb-1" for="password">أدخل كلمة المرور للتأكيد:</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti ti-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control" 
                                    placeholder="············" required autocomplete="current-password">
                            </div>
                            <small class="text-danger mt-1 d-block">
                                * يرجى إدخال كلمة المرور الخاصة بك لتأكيد العملية.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-success btn-submit">
                            <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                            تأكيد الاحتساب
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal إعادة استثمار الأرباح --}}
    @if(($personalWallet?->withdrawable_balance ?? 0) > 0)
    <div class="modal fade" id="reinvestProfitsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="ti ti-refresh text-primary me-2 ti-md"></i>
                        استثمار الأرباح
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('investor.personal-wallet.reinvest') }}" id="reinvestProfitsForm">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-primary d-flex align-items-start mb-4">
                            <i class="ti ti-info-circle me-2 mt-1"></i>
                            <div class="small">
                                <strong>كيف تعمل العملية؟</strong>
                                <ul class="mb-0 ps-3 mt-1">
                                    <li>يُخصم المبلغ من <strong>الرصيد القابل للسحب</strong> في محفظة العمولات.</li>
                                    <li>يُضاف المبلغ إلى <strong>محفظة المضاربة</strong> كرأس مال جديد.</li>
                                    <li>يمكنك استخدامه فوراً لتمويل مهام جديدة (للمضارب بالمهام).</li>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">المبلغ (ر.س)</label>
                            <div class="input-group">
                                <input type="number" name="amount" id="reinvest_amount" class="form-control"
                                    step="0.01" min="0.01"
                                    max="{{ number_format($personalWallet->withdrawable_balance, 2, '.', '') }}"
                                    value="{{ number_format($personalWallet->withdrawable_balance, 2, '.', '') }}"
                                    required>
                                <button type="button" class="btn btn-label-secondary" id="reinvestMaxBtn">الحد الأقصى</button>
                            </div>
                            <small class="text-muted">الرصيد القابل للسحب: {{ number_format($personalWallet->withdrawable_balance, 2) }} ر.س</small>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-bold" for="reinvest_password">كلمة المرور للتأكيد</label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti ti-lock"></i></span>
                                <input type="password" name="password" id="reinvest_password" class="form-control"
                                    placeholder="············" required autocomplete="current-password">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary btn-reinvest-submit">
                            <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                            تأكيد إعادة الاستثمار
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const calcForm = document.getElementById('calculateCommissionsForm');
        if (calcForm) {
            calcForm.addEventListener('submit', function() {
                const submitBtn = this.querySelector('.btn-submit');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    const spinner = submitBtn.querySelector('.spinner-border');
                    if (spinner) spinner.classList.remove('d-none');
                }
            });
        }

        const reinvestForm = document.getElementById('reinvestProfitsForm');
        const reinvestMaxBtn = document.getElementById('reinvestMaxBtn');
        const reinvestAmount = document.getElementById('reinvest_amount');

        if (reinvestMaxBtn && reinvestAmount) {
            reinvestMaxBtn.addEventListener('click', function () {
                reinvestAmount.value = reinvestAmount.getAttribute('max');
            });
        }

        if (reinvestForm) {
            reinvestForm.addEventListener('submit', function () {
                const submitBtn = this.querySelector('.btn-reinvest-submit');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    const spinner = submitBtn.querySelector('.spinner-border');
                    if (spinner) spinner.classList.remove('d-none');
                }
            });
        }
    });
</script>
@endsection
