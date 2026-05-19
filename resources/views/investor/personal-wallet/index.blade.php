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
        @if($contract && $contract->contract_type === 'general_investment' && $contract->isActive())
        <div class="col-auto">
            <button type="button" class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#calculateCommissionsModal">
                <i class="ti ti-calculator me-1"></i> احتساب العمولات الآن
            </button>
        </div>
        @endif
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

    {{-- بطاقات الإحصائيات --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="ti ti-cash ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($personalWallet?->balance ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-medium">الرصيد المتاح للسحب</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-success me-1">ر.س</span> رصيد أرباحك الصافي
                    </p>
                </div>
            </div>
        </div>
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
                        <span class="text-primary me-1">ر.س</span> إجمالي ما دخل المحفظة
                    </p>
                </div>
            </div>
        </div>
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
                <div class="col-md-4">
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>فلترة</button>
                    <a href="{{ route('investor.personal-wallet') }}" class="btn btn-label-secondary w-100">تصفير</a>
                </div>
            </form>
        </div>
    </div>

    {{-- جدول العمولات --}}
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">سجل استحقاق العمولات</h5>
            <span class="badge bg-label-secondary">إجمالي العمولات: {{ $transactions->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-muted small">المهمة</th>
                        <th class="text-muted small">مبلغ العمولة</th>
                        <th class="text-muted small">الرصيد بعد</th>
                        <th class="text-muted small">البيان</th>
                        <th class="text-muted small">التاريخ والوقت</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($transactions as $tx)
                    <tr>
                        <td>
                            @if($tx->task_id)
                                <a href="javascript:void(0)" class="badge bg-label-primary">#{{ $tx->task_id }}</a>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-xs me-2">
                                    <span class="avatar-initial rounded-circle bg-label-success"><i class="ti ti-plus ti-xs"></i></span>
                                </div>
                                <span class="fw-bold text-success">{{ number_format($tx->amount, 2) }} ر.س</span>
                            </div>
                        </td>
                        <td>{{ number_format($tx->balance_after, 2) }} ر.س</td>
                        <td class="text-truncate" style="max-width: 250px;">{{ $tx->description ?? '—' }}</td>
                        <td class="small">{{ $tx->created_at->format('Y-m-d') }} <br> <span class="text-muted">{{ $tx->created_at->format('H:i') }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <img src="{{ asset('assets/img/illustrations/empty-state.png') }}" alt="Empty state" width="120" class="mb-3 opacity-50">
                            <p class="text-muted">لا توجد عمولات مكتسبة مسجلة حتى الآن.</p>
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
    });
</script>
@endsection
