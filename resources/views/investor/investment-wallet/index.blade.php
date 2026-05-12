@extends('layouts/layoutMaster')

@section('title', 'محفظة الاستثمار')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- رأس الصفحة --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-wallet me-2 text-warning"></i>محفظة الاستثمار</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('investor.dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item active">محفظة الاستثمار</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-3">
            @if($investorWallet && $investorWallet->balance < 500)
                <div class="alert alert-label-warning d-flex align-items-center mb-0 py-2" role="alert">
                    <span class="alert-icon me-2"><i class="ti ti-alert-triangle ti-xs"></i></span>
                    رصيدك منخفض
                </div>
            @endif
            <button type="button" class="btn btn-primary d-flex align-items-center shadow-sm py-2 px-4" data-bs-toggle="modal" data-bs-target="#depositModal" style="background: linear-gradient(135deg, #7367f0 0%, #a098f5 100%); border: none;">
                <i class="ti ti-credit-card me-2 ti-sm"></i>
                <span class="fw-bold">شحن محفظة الإستثمار</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible mb-4" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible mb-4" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            {{ session('error') }}
        </div>
    @endif

    {{-- بطاقات الإحصائيات --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card card-border-shadow-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i class="ti ti-wallet ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($investorWallet?->balance ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-medium">الرصيد المتاح حالياً</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-warning me-1">ر.س</span> جاهز للاستثمار
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="ti ti-arrow-down-left ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($investorWallet?->credit ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-medium">إجمالي الإيداعات</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-success me-1">ر.س</span> تم شحنها في المحفظة
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card card-border-shadow-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-danger"><i class="ti ti-arrow-up-right ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($investorWallet?->debit ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-medium">إجمالي التمويلات</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-danger me-1">ر.س</span> مصروفة على المهام
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- فلاتر البحث --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-muted small">تصفية النتائج</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">نوع العملية</label>
                    <select name="type" class="form-select">
                        <option value="">الكل</option>
                        <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>إيداع (Credit)</option>
                        <option value="debit"  {{ request('type') === 'debit'  ? 'selected' : '' }}>خصم (Debit)</option>
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
                    <a href="{{ route('investor.investment-wallet') }}" class="btn btn-label-secondary w-100">تصفير</a>
                </div>
            </form>
        </div>
    </div>

    {{-- جدول الحركات --}}
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">سجل حركات المحفظة</h5>
            <span class="badge bg-label-secondary">إجمالي العمليات: {{ $transactions->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-muted small">نوع العملية</th>
                        <th class="text-muted small">المبلغ</th>
                        <th class="text-muted small">الرصيد بعد</th>
                        <th class="text-muted small">رقم المهمة</th>
                        <th class="text-muted small">البيان</th>
                        <th class="text-muted small">التاريخ والوقت</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($transactions as $tx)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($tx->transaction_type === 'credit')
                                    <div class="avatar avatar-xs me-2">
                                        <span class="avatar-initial rounded-circle bg-label-success"><i class="ti ti-arrow-down-left"></i></span>
                                    </div>
                                    <span class="fw-medium text-success">إيداع</span>
                                @else
                                    <div class="avatar avatar-xs me-2">
                                        <span class="avatar-initial rounded-circle bg-label-danger"><i class="ti ti-arrow-up-right"></i></span>
                                    </div>
                                    <span class="fw-medium text-danger">تمويل مهمة</span>
                                @endif
                            </div>
                        </td>
                        <td class="fw-bold">
                            {{ $tx->transaction_type === 'credit' ? '+' : '-' }}{{ number_format($tx->amount, 2) }} ر.س
                        </td>
                        <td>{{ number_format($tx->balance_after, 2) }} ر.س</td>
                        <td>
                            @if($tx->task_id)
                                <a href="javascript:void(0)" class="badge bg-label-primary">#{{ $tx->task_id }}</a>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-truncate" style="max-width: 200px;">{{ $tx->description ?? '—' }}</td>
                        <td class="small">{{ $tx->created_at->format('Y-m-d') }} <br> <span class="text-muted">{{ $tx->created_at->format('H:i') }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <img src="{{ asset('assets/img/illustrations/empty-state.png') }}" alt="Empty state" width="120" class="mb-3 opacity-50">
                            <p class="text-muted">لا توجد عمليات مسجلة في هذه المحفظة حتى الآن.</p>
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

    {{-- Modal Deposit --}}
    <div class="modal fade" id="depositModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">شحن رصيد المحفظة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('investor.investment-wallet.deposit.initiate') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-4 text-center">
                            <p class="text-muted">أدخل المبلغ الذي تود إضافته لمحفظة الاستثمار عبر بوابة الدفع الإلكتروني</p>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">المبلغ (ر.س)</label>
                            <div class="input-group input-group-lg border-2 rounded-3">
                                <input type="number" name="amount" class="form-control" placeholder="0.00" min="10" step="0.01" required>
                                <span class="input-group-text">ر.س</span>
                            </div>
                            <div class="form-text text-warning"><i class="ti ti-info-circle me-1"></i>الحد الأدنى للشحن هو 10 ر.س</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block fw-semibold mb-3">اختر وسيلة الدفع</label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="form-check custom-option custom-option-icon border-2">
                                        <label class="form-check-label custom-option-content" for="brandVisa">
                                            <span class="custom-option-body">
                                                <i class="ti ti-brand-visa ti-lg mb-2 text-primary"></i>
                                                <span class="custom-option-title fw-bold">Visa / Master</span>
                                            </span>
                                            <input name="brand" class="form-check-input" type="radio" value="VISA" id="brandVisa" />
                                        </label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check custom-option custom-option-icon border-2">
                                        <label class="form-check-label custom-option-content" for="brandMada">
                                            <span class="custom-option-body">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fb/Mada_Logo.svg/1280px-Mada_Logo.svg.png" alt="Mada" width="45" class="mb-2">
                                                <span class="custom-option-title fw-bold">مدى (Mada)</span>
                                            </span>
                                            <input name="brand" class="form-check-input" type="radio" value="MADA" id="brandMada" checked />
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pb-4">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary px-5 shadow">الانتقال للدفع الآمن</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
