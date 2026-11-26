@extends('layouts/layoutMaster')

@section('title', __('محفظة عمولاتي الشخصية'))
@section('my-wallet-isactive', 'active')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
    <style>
        .wallet-table-loader {
            display: none !important;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.85);
            z-index: 50;
            border-radius: 8px;
            align-items: center;
            justify-content: center;
        }
        .wallet-table-loader.active {
            display: flex !important;
        }
        .pagination .page-item .page-link {
            border-color: #e0e7ff;
            color: #4f46e5;
            font-weight: 600;
        }
        .pagination .page-item.active .page-link {
            background-color: #6366f1;
            border-color: #6366f1;
            color: #fff;
        }
    </style>
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- رأس الصفحة --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-wallet me-2 text-primary"></i>{{ __('محفظة عمولاتي') }}
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">{{ __('الرئيسية') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('محفظة عمولاتي') }}</li>
                </ol>
            </nav>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('admin.my-wallet.export', request()->query()) }}" class="btn btn-label-success shadow-sm" id="btnExportExcel">
                <i class="ti ti-file-spreadsheet me-1"></i> {{ __('تصدير كشف الحساب (Excel)') }}
            </a>

            @if($withdrawableBalance > 0 && !$hasActivePayout && $hasBankDetails)
                <button type="button" class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#requestWithdrawalModal">
                    <i class="ti ti-cash-banknote me-1"></i> {{ __('طلب سحب عمولة') }}
                </button>
            @elseif($hasActivePayout)
                <button type="button" class="btn btn-label-warning shadow-sm" disabled title="يوجد طلب سحب نشط قيد المراجعة">
                    <i class="ti ti-clock-hour-4 me-1"></i> {{ __('طلب سحب قيد الانتظار') }}
                </button>
            @elseif(!$hasBankDetails)
                <button type="button" class="btn btn-outline-warning shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#bankAlertModal">
                    <i class="ti ti-alert-triangle me-1"></i> {{ __('طلب سحب عمولة') }}
                </button>
            @else
                <button type="button" class="btn btn-outline-primary shadow-sm" disabled>
                    <i class="ti ti-cash-banknote me-1"></i> {{ __('الرصيد غير كافٍ للسحب') }}
                </button>
            @endif
        </div>
    </div>

    {{-- التنبيهات ورسائل الفلاش --}}
    @foreach(['success', 'error', 'info', 'warning'] as $msg)
        @if(session($msg))
            <div class="alert alert-{{ $msg === 'error' ? 'danger' : $msg }} alert-dismissible mb-4 shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <i class="ti ti-{{ $msg === 'success' ? 'check' : ($msg === 'error' ? 'alert-circle' : 'info-circle') }} me-2 fs-5"></i>
                    <div>{{ session($msg) }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
    @endforeach

    {{-- تنبيه نقص البيانات البنكية --}}
    @if(!$hasBankDetails)
        <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <span class="badge bg-warning p-2 me-3 rounded-circle">
                        <i class="ti ti-alert-triangle text-white fs-5"></i>
                    </span>
                    <div>
                        <h6 class="alert-heading mb-1 fw-bold text-dark">البيانات البنكية غير مكتملة لاستقبال التحويل المباشر (Payout)</h6>
                        <p class="mb-0 small text-dark">
                            يرجى تزويد الإدارة أو تحديث ملفك الشخصي باسم المستفيد الرسمي ورقم الآيبان (IBAN) لتتمكن من تقديم طلبات سحب العمولات إلى حسابك البنكي مباشرة.
                        </p>
                    </div>
                </div>
                <a href="{{ route('user.profile') }}" class="btn btn-sm btn-warning fw-bold">
                    <i class="ti ti-user-edit me-1"></i> تحديث الملف الشخصي
                </a>
            </div>
        </div>
    @endif

    {{-- تنبيه طلب السحب النشط حالياً --}}
    @if($hasActivePayout && $activePayout)
        <div class="alert alert-info border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <span class="badge bg-info p-2 me-3 rounded-circle">
                        <i class="ti ti-clock-check text-white fs-5"></i>
                    </span>
                    <div>
                        <h6 class="alert-heading mb-1 fw-bold text-dark">
                            يوجد لديك طلب سحب قيد المراجعة والمعالجة بمبلغ
                            <span class="text-primary fw-bold">{{ number_format($activePayout->amount, 2) }} ر.س</span>
                        </h6>
                        <p class="mb-0 small text-dark">
                            رقم المرجع: <span class="font-monospace fw-bold text-primary">#{{ $activePayout->reference_id }}</span> |
                            الحالة: {!! $activePayout->status_badge !!} |
                            تاريخ الطلب: <span class="fw-semibold text-dark">{{ $activePayout->created_at->format('Y-m-d H:i') }}</span>
                        </p>
                    </div>
                </div>
                <span class="badge bg-label-info p-2">
                    <i class="ti ti-shield-lock me-1"></i> بانتظار مصادقة الإدارة لتحويل المبلغ
                </span>
            </div>
        </div>
    @endif

    {{-- بطاقات الإحصائيات المالية --}}
    <div class="row g-3 mb-4">
        {{-- الرصيد القابل للسحب --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm" style="border-top: 3px solid #28a745; border-color: #d1fae5;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded" style="background: linear-gradient(135deg,#28a745,#20c997); color:#fff;">
                                <i class="ti ti-cash ti-md"></i>
                            </span>
                        </div>
                        <h4 class="ms-1 mb-0 text-success fw-bold">{{ number_format($withdrawableBalance, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-bold text-dark">{{ __('الرصيد القابل للسحب حالياً') }}</p>
                    <p class="mb-0 small text-dark">
                        <span class="text-success fw-bold me-1">{{ __('ر.س') }}</span>
                        {{ __('المتاح للتحويل الفوري إلى حسابك البنكي') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- الرصيد الدفتري الحالي --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm" style="border-top: 3px solid #7367f0; border-color: #e0e7ff;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-wallet ti-md"></i>
                            </span>
                        </div>
                        <h4 class="ms-1 mb-0 text-primary fw-bold">{{ number_format($currentBalance, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-bold text-dark">{{ __('الرصيد الدفتري الحالي') }}</p>
                    <p class="mb-0 small text-dark">
                        <span class="text-primary fw-bold me-1">{{ __('ر.س') }}</span>
                        {{ __('صافي الرصيد الدفتري بالمحفظة') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- إجمالي العمولات المكتسبة --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm" style="border-top: 3px solid #00cfe8; border-color: #cffafe;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-trending-up ti-md"></i>
                            </span>
                        </div>
                        <h4 class="ms-1 mb-0 text-info fw-bold">{{ number_format($totalCredits, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-bold text-dark">{{ __('إجمالي العمولات المكتسبة') }}</p>
                    <p class="mb-0 small text-dark">
                        <span class="text-info fw-bold me-1">{{ __('ر.س') }}</span>
                        {{ __('إجمالي ما تم قيده لحسابك من عمولات') }}
                    </p>
                </div>
            </div>
        </div>

        {{-- إجمالي المسحوبات --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100 shadow-sm" style="border-top: 3px solid #ea5455; border-color: #fee2e2;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-arrow-up-right ti-md"></i>
                            </span>
                        </div>
                        <h4 class="ms-1 mb-0 text-danger fw-bold">{{ number_format($totalDebits, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-bold text-dark">{{ __('إجمالي المسحوبات') }}</p>
                    <p class="mb-0 small text-dark">
                        <span class="text-danger fw-bold me-1">{{ __('ر.س') }}</span>
                        {{ __('إجمالي الحوالات والمبالغ المصروفة') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- بيانات الحساب البنكي المعتمد للـ Payout --}}
    <div class="card shadow-sm mb-4 border-0" style="border: 1.5px solid #dbeafe !important;">
        <div class="card-header py-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: #f0f7ff; border-bottom: 1.5px solid #dbeafe;">
            <div class="d-flex align-items-center">
                <i class="ti ti-building-bank text-primary me-2 fs-4"></i>
                <h6 class="mb-0 fw-bold text-dark">{{ __('بيانات الحساب البنكي المعتمد للتحويلات المالية المباشرة (HyperPay Payout)') }}</h6>
            </div>
            @if($hasBankDetails)
                <span class="badge bg-label-success">
                    <i class="ti ti-check me-1"></i> جاهز لاستقبال التحويلات
                </span>
            @else
                <span class="badge bg-label-warning">
                    <i class="ti ti-alert-triangle me-1"></i> بحاجة لاكتمال البيانات
                </span>
            @endif
        </div>
        <div class="card-body py-3 bg-white">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <small class="text-primary fw-bold d-block">{{ __('اسم البنك:') }}</small>
                    <span class="fw-bold text-dark">{{ $user->bank_name ?: 'غير محدد' }}</span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <small class="text-primary fw-bold d-block">{{ __('اسم المستفيد الرسمي:') }}</small>
                    <span class="fw-bold text-dark">{{ $user->beneficiary_name ?: ($user->name ?: 'غير محدد') }}</span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <small class="text-primary fw-bold d-block">{{ __('رقم الحساب:') }}</small>
                    <span class="fw-bold font-monospace text-dark">{{ $user->account_number ?: 'غير محدد' }}</span>
                </div>
                <div class="col-md-3 col-sm-6">
                    <small class="text-primary fw-bold d-block">{{ __('رقم الآيبان (IBAN):') }}</small>
                    <span class="fw-bold font-monospace text-primary" dir="ltr">{{ $user->iban_number ?: 'غير محدد' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- بطاقة التبويبات: كشف حساب الحركات وسجل طلبات السحب --}}
    <div class="card shadow-sm border-0">
        <div class="card-header border-bottom p-0">
            <ul class="nav nav-tabs nav-fill card-header-tabs m-0" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active py-3 fw-bold" data-bs-toggle="tab" data-bs-target="#tab-transactions" type="button" role="tab">
                        <i class="ti ti-list-details me-1"></i> {{ __('كشف حساب حركات العمولات') }}
                        <span class="badge bg-primary ms-1 rounded-pill" id="transactions-count-badge">{{ $transactions->total() }}</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 fw-bold" data-bs-toggle="tab" data-bs-target="#tab-payouts" type="button" role="tab">
                        <i class="ti ti-history me-1"></i> {{ __('سجل طلبات السحب والحوالات') }}
                        <span class="badge bg-primary ms-1 rounded-pill" id="payouts-count-badge">{{ $payoutRequests->total() }}</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-0">
            <div class="tab-content p-0">

                {{-- التبويب الأول: كشف حساب حركات العمولات --}}
                <div class="tab-pane fade show active p-3" id="tab-transactions" role="tabpanel">

                    {{-- فلاتر البحث التفاعلية عبر الـ AJAX --}}
                    <form method="GET" action="{{ route('admin.my-wallet.index') }}" id="walletFilterForm" class="mb-4">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label small mb-1 fw-semibold text-dark" for="filter_type">{{ __('نوع العملية') }}</label>
                                <select name="type" id="filter_type" class="form-select form-select-sm border-primary">
                                    <option value="">{{ __('الكل (إحتساب وسحب)') }}</option>
                                    <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>{{ __('إحتساب عمولة (إيداع)') }}</option>
                                    <option value="debit" {{ request('type') === 'debit' ? 'selected' : '' }}>{{ __('سحب عمولة (خصم)') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small mb-1 fw-semibold text-dark" for="filter_from">{{ __('من تاريخ') }}</label>
                                <input type="date" name="from" id="filter_from" class="form-control form-control-sm border-primary" value="{{ request('from') }}">
                            </div>
                            <div class="col-md-2 col-sm-6">
                                <label class="form-label small mb-1 fw-semibold text-dark" for="filter_to">{{ __('إلى تاريخ') }}</label>
                                <input type="date" name="to" id="filter_to" class="form-control form-control-sm border-primary" value="{{ request('to') }}">
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <label class="form-label small mb-1 fw-semibold text-dark" for="filter_search">{{ __('بحث برقم المهمة أو الوصف') }}</label>
                                <input type="text" name="search" id="filter_search" class="form-control form-control-sm border-primary" placeholder="{{ __('رقم المهمة أو كلمة في الوصف...') }}" value="{{ request('search') }}">
                            </div>
                            <div class="col-md-2 col-sm-12 d-flex gap-1">
                                <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold" id="filterBtn">
                                    <i class="ti ti-filter me-1"></i> {{ __('تصفية') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="resetFilterBtn" title="{{ __('إعادة تعيين') }}">
                                    <i class="ti ti-refresh"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- الحاوية التفاعلية لجدول العمليات والترقيم --}}
                    <div id="transactions-table-wrapper" class="position-relative">
                        <div id="transactions-loading" class="wallet-table-loader">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">{{ __('Loading...') }}</span>
                            </div>
                        </div>
                        @include('admin.my-wallet.partials.transactions_table')
                    </div>
                </div>

                {{-- التبويب الثاني: سجل طلبات السحب والحوالات --}}
                <div class="tab-pane fade p-3" id="tab-payouts" role="tabpanel">
                    <div id="payouts-table-wrapper" class="position-relative">
                        <div id="payouts-loading" class="wallet-table-loader">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">{{ __('Loading...') }}</span>
                            </div>
                        </div>
                        @include('admin.my-wallet.partials.payouts_table')
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

{{-- مودال طلب سحب العمولة --}}
<div class="modal fade" id="requestWithdrawalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content border-0 shadow" method="POST" action="{{ route('admin.my-wallet.request-withdrawal') }}" id="withdrawalForm">
            @csrf
            <div class="modal-header" style="background: #f0f7ff; border-bottom: 1.5px solid #dbeafe;">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="ti ti-cash-banknote text-primary me-2"></i>{{ __('تقديم طلب سحب عمولة') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">

                {{-- صندوق تفاصيل الحساب البنكي للتحويل --}}
                <div class="mb-3 p-3 rounded" style="background: #f0f7ff; border: 1.5px solid #bfdbfe;">
                    <div class="d-flex align-items-center mb-2">
                        <i class="ti ti-building-bank text-primary me-2 fs-5"></i>
                        <span class="fw-bold text-dark">{{ __('الحساب البنكي المعتمد للتحويل:') }}</span>
                    </div>
                    <div class="small ps-4 text-dark">
                        <div><strong>المستفيد:</strong> <span class="text-primary fw-bold">{{ $user->beneficiary_name ?: $user->name }}</span></div>
                        <div><strong>البنك:</strong> <span class="text-dark fw-bold">{{ $user->bank_name ?: 'البنك المعتمد' }}</span></div>
                        <div><strong>الآيبان:</strong> <span class="font-monospace fw-bold text-primary" dir="ltr">{{ $user->iban_number }}</span></div>
                    </div>
                </div>

                {{-- الرصيد المتاح --}}
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-label-success rounded">
                    <span class="fw-bold text-success">{{ __('الرصيد المتاح للسحب:') }}</span>
                    <span class="fw-bold text-success fs-5">{{ number_format($withdrawableBalance, 2) }} <small>ر.س</small></span>
                </div>

                {{-- حقل المبلغ --}}
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark" for="withdrawal_amount">{{ __('المبلغ المراد سحبه (ر.س)') }} <span class="text-danger">*</span></label>
                    <div class="input-group input-group-merge">
                        <input type="number" step="0.01" min="1" max="{{ $withdrawableBalance }}" name="amount" id="withdrawal_amount" class="form-control form-control-lg text-center font-monospace fw-bold fs-4 border-primary" placeholder="0.00" required>
                        <span class="input-group-text fw-bold border-primary text-primary">ر.س</span>
                    </div>
                    <div class="d-flex gap-1 mt-2">
                        <button type="button" class="btn btn-xs btn-outline-primary flex-fill fw-bold" onclick="setWithdrawAmount({{ $withdrawableBalance * 0.25 }})">25%</button>
                        <button type="button" class="btn btn-xs btn-outline-primary flex-fill fw-bold" onclick="setWithdrawAmount({{ $withdrawableBalance * 0.50 }})">50%</button>
                        <button type="button" class="btn btn-xs btn-outline-primary flex-fill fw-bold" onclick="setWithdrawAmount({{ $withdrawableBalance * 0.75 }})">75%</button>
                        <button type="button" class="btn btn-xs btn-primary flex-fill fw-bold" onclick="setWithdrawAmount({{ $withdrawableBalance }})">كامل الرصيد (100%)</button>
                    </div>
                </div>

                {{-- ملاحظات إضافية --}}
                <div class="mb-2">
                    <label class="form-label small fw-semibold text-dark" for="withdrawal_notes">{{ __('ملاحظات إضافية (اختياري)') }}</label>
                    <textarea name="notes" id="withdrawal_notes" class="form-control border-primary" rows="2" placeholder="{{ __('أي تعليمات أو ملاحظات إضافية...') }}" maxlength="500"></textarea>
                </div>

                <div class="form-text small mt-2 text-dark">
                    <i class="ti ti-info-circle me-1 text-primary"></i>
                    سيتم إرسال طلب السحب فوراً إلى الإدارة لمصادقته وإجراء التحويل المالي المباشر عبر بوابة HyperPay Payout.
                </div>

            </div>
            <div class="modal-footer" style="background: #f0f7ff; border-top: 1.5px solid #dbeafe;">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">{{ __('Cancel') }} }}</button>
                <button type="submit" class="btn btn-primary fw-bold" id="btnSubmitWithdrawal">
                    <i class="ti ti-send me-1"></i> {{ __('تأكيد وإرسال الطلب') }}
                </button>
            </div>
        </form>
    </div>
</div>

{{-- مودال تنبيه عدم اكتمال البيانات البنكية --}}
<div class="modal fade" id="bankAlertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-label-warning">
                <h5 class="modal-title fw-bold text-warning">
                    <i class="ti ti-alert-triangle me-2"></i>{{ __('تنبيه: البيانات البنكية غير مكتملة') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4 bg-white">
                <i class="ti ti-building-bank text-warning mb-3" style="font-size: 3.5rem;"></i>
                <h6 class="fw-bold text-dark mb-2">يتطلب سحب العمولات وجود حساب بنكي معتمد</h6>
                <p class="text-dark small mb-0">
                    لم نتمكن من العثور على اسم المستفيد الرسمي أو رقم الآيبان (IBAN) المسجل في ملفك الشخصي.
                    لكي يتم تحويل المبالغ المالية مباشرة إلى حسابك البنكي، يرجى تحديث بياناتك البنكية.
                </p>
            </div>
            <div class="modal-footer justify-content-center" style="background: #f0f7ff; border-top: 1.5px solid #dbeafe;">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">{{ __('إغلاق') }}</button>
                <a href="{{ route('user.profile') }}" class="btn btn-warning fw-bold">
                    <i class="ti ti-user-edit me-1"></i> {{ __('الانتقال لتحديث الملف الشخصي') }}
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function setWithdrawAmount(val) {
    document.getElementById('withdrawal_amount').value = Math.max(0, val).toFixed(2);
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('withdrawalForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const amountInput = document.getElementById('withdrawal_amount');
            const amount = parseFloat(amountInput.value);
            const maxVal = parseFloat(amountInput.getAttribute('max'));

            if (isNaN(amount) || amount <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: '{{ __('Notice') }}',
                    text: 'يرجى إدخال مبلغ صحيح أكبر من 0',
                    confirmButtonText: '{{ __('OK') }}',
                    customClass: { confirmButton: 'btn btn-primary' },
                    buttonsStyling: false
                });
                return;
            }

            if (amount > maxVal) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'المبلغ غير متاح',
                    text: 'المبلغ المدخل يتجاوز الرصيد المتاح للسحب (' + maxVal.toFixed(2) + ' ر.س)',
                    confirmButtonText: '{{ __('OK') }}',
                    customClass: { confirmButton: 'btn btn-primary' },
                    buttonsStyling: false
                });
                return;
            }

            const btn = document.getElementById('btnSubmitWithdrawal');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري إرسال الطلب...';
        });
    }

    // --- منطق الفلترة والترقيم عبر AJAX ---

    function updateExportUrl() {
        const params = $('#walletFilterForm').serialize();
        const exportBase = '{{ route("admin.my-wallet.export') }}';
        $('#btnExportExcel').attr('href', exportBase + (params ? '?' + params : ''));
    }

    function fetchTransactions(targetUrl) {
        let url = targetUrl;
        if (!url) {
            const formParams = $('#walletFilterForm').serialize();
            url = '{{ route("admin.my-wallet.index') }}' + (formParams ? '?' + formParams : '');
        } else {
            try {
                const urlObj = new URL(url, window.location.origin);
                const formObj = new FormData(document.getElementById('walletFilterForm'));
                for (let [key, val] of formObj.entries()) {
                    if (val && !urlObj.searchParams.has(key)) {
                        urlObj.searchParams.set(key, val);
                    }
                }
                url = urlObj.pathname + '?' + urlObj.searchParams.toString();
            } catch(e) {}
        }

        $('#transactions-loading').addClass('active');
        $('#transactions-table-wrapper table, #transactions-table-wrapper .transactions-pagination').css('opacity', '0.4');

        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            dataType: 'json',
            success: function(res) {
                $('#transactions-loading').removeClass('active');
                $('#transactions-table-wrapper table, #transactions-table-wrapper .transactions-pagination').css('opacity', '1');

                if (res.status === 1) {
                    $('#transactions-table-wrapper').html(res.html);
                    if (typeof res.total !== 'undefined') {
                        $('#transactions-count-badge').text(res.total);
                    }
                    updateExportUrl();
                    window.history.pushState(null, '', url);
                }
            },
            error: function(err) {
                $('#transactions-loading').removeClass('active');
                $('#transactions-table-wrapper table, #transactions-table-wrapper .transactions-pagination').css('opacity', '1');
                console.error('Transactions AJAX Error:', err);
            }
        });
    }

    // إرسال نموذج التصفية
    $(document).on('submit', '#walletFilterForm', function(e) {
        e.preventDefault();
        fetchTransactions();
    });

    // تصفية تلقائية عند تغيير نوع العملية
    $(document).on('change', '#filter_type', function() {
        fetchTransactions();
    });

    // تصفية تلقائية عند اختيار التواريخ
    $(document).on('change', '#filter_from, #filter_to', function() {
        fetchTransactions();
    });

    // تصفية تلقائية مع تأخير عند الكتابة في حقل البحث
    let searchTimeout = null;
    $(document).on('input', '#filter_search', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            fetchTransactions();
        }, 450);
    });

    // زر إعادة التعيين
    $(document).on('click', '#resetFilterBtn', function(e) {
        e.preventDefault();
        $('#walletFilterForm')[0].reset();
        $('#filter_type').val('');
        $('#filter_from').val('');
        $('#filter_to').val('');
        $('#filter_search').val('');
        fetchTransactions('{{ route("admin.my-wallet.index') }}');
    });

    // الترقيم التفاعلي لجدول الحركات
    $(document).on('click', '#transactions-table-wrapper .pagination a', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (href && href !== '#' && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
            fetchTransactions(href);
        }
    });

    // الترقيم التفاعلي لجدول طلبات السحب
    $(document).on('click', '#payouts-table-wrapper .pagination a', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (!href || href === '#' || $(this).parent().hasClass('disabled') || $(this).parent().hasClass('active')) {
            return;
        }

        $('#payouts-loading').addClass('active');
        $('#payouts-table-wrapper table, #payouts-table-wrapper .payouts-pagination').css('opacity', '0.4');

        $.ajax({
            url: href,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            dataType: 'json',
            success: function(res) {
                $('#payouts-loading').removeClass('active');
                $('#payouts-table-wrapper table, #payouts-table-wrapper .payouts-pagination').css('opacity', '1');

                if (res.status === 1) {
                    $('#payouts-table-wrapper').html(res.html);
                    if (typeof res.total !== 'undefined') {
                        $('#payouts-count-badge').text(res.total);
                    }
                }
            },
            error: function(err) {
                $('#payouts-loading').removeClass('active');
                $('#payouts-table-wrapper table, #payouts-table-wrapper .payouts-pagination').css('opacity', '1');
                console.error('Payouts AJAX Error:', err);
            }
        });
    });
});
</script>
@endsection
