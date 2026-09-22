@extends('layouts/layoutMaster')

@section('title', __('طلبات الدفع عبر Payout'))
@section('payout-requests-isactive', 'active')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/spinkit/spinkit.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/block-ui/block-ui.js'
    ])
@endsection

@section('page-script')
    <script>
        const payoutDataUrl = "{{ route('wallets.payout-requests.data') }}";
        const payoutShowUrl = "{{ route('wallets.payout-requests.show', ':id') }}";
        const payoutApproveUrl = "{{ route('wallets.payout-requests.approve', ':id') }}";
        const payoutRejectUrl = "{{ route('wallets.payout-requests.reject', ':id') }}";
    </script>
    @vite(['resources/js/admin/wallets/payout-requests.js'])
@endsection

@section('content')
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-send text-primary me-2 fs-2 align-middle"></i>
                {{ __('طلبات الدفع عبر HyperPay Payout') }}
            </h4>
            <p class="text-muted mb-0">{{ __('مراجعة ومصادقة التحويلات البنكية المباشرة للسائقين (نظام الرقابة والمصادقة الثنائية)') }}</p>
        </div>
        <div>
            <a href="{{ route('wallets.withdrawals.index') }}" class="btn btn-label-secondary me-2">
                <i class="ti ti-cash-banknote me-1"></i> {{ __('طلبات السحب') }}
            </a>
            <a href="{{ route('wallets.wallets') }}" class="btn btn-label-primary">
                <i class="ti ti-wallet me-1"></i> {{ __('المحافظ') }}
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <!-- Pending Approval -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-top border-warning border-3">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-semibold text-warning">{{ __('بانتظار المصادقة') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-warning">{{ number_format($metrics['pending_approval_amount'], 2) }} {{ __('SAR') }}</h4>
                            </div>
                            <small class="mb-0 text-muted">{{ __('عدد الطلبات:') }} <strong>{{ $metrics['pending_approval_count'] }}</strong></small>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                            <i class="ti ti-clock-pause ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Processing -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-top border-info border-3">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-semibold text-info">{{ __('قيد المعالجة البنكية') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-info">{{ number_format($metrics['processing_amount'], 2) }} {{ __('SAR') }}</h4>
                            </div>
                            <small class="mb-0 text-muted">{{ __('عدد الطلبات:') }} <strong>{{ $metrics['processing_count'] }}</strong></small>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="ti ti-loader ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-top border-success border-3">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-semibold text-success">{{ __('مكتملة ومحولة') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-success">{{ number_format($metrics['completed_amount'], 2) }} {{ __('SAR') }}</h4>
                            </div>
                            <small class="mb-0 text-muted">{{ __('عدد الطلبات:') }} <strong>{{ $metrics['completed_count'] }}</strong></small>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="ti ti-circle-check ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rejected / Failed -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-top border-danger border-3">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-semibold text-danger">{{ __('مرفوضة / فاشلة') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-danger">{{ $metrics['rejected_count'] + $metrics['failed_count'] }}</h4>
                            </div>
                            <small class="mb-0 text-muted">{{ __('مرفوضة:') }} {{ $metrics['rejected_count'] }} | {{ __('فاشلة:') }} {{ $metrics['failed_count'] }}</small>
                        </div>
                        <span class="badge bg-label-danger rounded p-2">
                            <i class="ti ti-x ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('تصفية حسب الحالة') }}</label>
                    <select id="filter_status" class="form-select">
                        <option value="" selected>{{ __('جميع الحالات') }}</option>
                        <option value="pending_approval">{{ __('بانتظار المصادقة (تحتاج قرار)') }}</option>
                        <option value="processing">{{ __('قيد المعالجة بالبنك') }}</option>
                        <option value="completed">{{ __('مكتمل بنجاح') }}</option>
                        <option value="rejected">{{ __('مرفوض') }}</option>
                        <option value="failed">{{ __('فشل التحويل') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('نوع العملية') }}</label>
                    <select id="filter_type" class="form-select">
                        <option value="">{{ __('جميع الأنواع') }}</option>
                        <option value="MT">{{ __('حركة يدوية (سحب/خصم)') }}</option>
                        <option value="WP">{{ __('تسوية مستحقات') }}</option>
                        <option value="WD">{{ __('طلب سحب رصيد') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('من تاريخ') }}</label>
                    <input type="date" id="filter_from_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('إلى تاريخ') }}</label>
                    <input type="date" id="filter_to_date" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="ti ti-list me-1 text-primary"></i>
                {{ __('جدول طلبات الدفع عبر Payout') }}
            </h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-table">
                <i class="ti ti-refresh me-1"></i> {{ __('تحديث البيانات') }}
            </button>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-payout-requests table table-hover border-top font-small">
                <thead>
                    <tr>
                        <th>{{ __('رقم المرجع') }}</th>
                        <th>{{ __('التاريخ') }}</th>
                        <th>{{ __('السائق') }}</th>
                        <th>{{ __('النوع') }}</th>
                        <th>{{ __('المبلغ') }}</th>
                        <th>{{ __('المستفيد والآيبان') }}</th>
                        <th>{{ __('المُنشئ') }}</th>
                        <th>{{ __('الحالة') }}</th>
                        <th>{{ __('المصادق / الرافض') }}</th>
                        <th>{{ __('الإجراءات') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- View Payout Details Modal -->
    <div class="modal fade" id="viewPayoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-label-primary py-3">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="ti ti-file-info me-2 fs-4"></i>
                        {{ __('تفاصيل طلب الدفع عبر الـ Payout') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="payout-details-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted">{{ __('جاري تحميل التفاصيل...') }}</p>
                    </div>
                    <div id="payout-details-content" style="display: none;">
                        <!-- Status and Reference header -->
                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded mb-3">
                            <div>
                                <small class="text-muted d-block">{{ __('رقم المرجع الداخلي:') }}</small>
                                <span class="fw-bold fs-5 text-dark" id="modal-reference-id"></span>
                            </div>
                            <div class="text-end">
                                <span id="modal-status-badge"></span>
                                <div class="small text-muted mt-1" id="modal-payout-type"></div>
                            </div>
                        </div>

                        <!-- Amount banner -->
                        <div class="alert alert-primary d-flex justify-content-between align-items-center py-2 px-3 mb-3">
                            <span class="fw-bold">{{ __('المبلغ المطلوب تحويله:') }}</span>
                            <span class="fs-4 fw-bolder text-primary"><span id="modal-amount">0.00</span> SAR</span>
                        </div>

                        <div class="row g-3">
                            <!-- Driver & Bank Info -->
                            <div class="col-md-6">
                                <div class="card h-100 border shadow-none">
                                    <div class="card-header bg-label-info py-2">
                                        <h6 class="mb-0 fw-bold"><i class="ti ti-steering-wheel me-1"></i> {{ __('بيانات السائق والمستفيد') }}</h6>
                                    </div>
                                    <div class="card-body pt-3 small">
                                        <p class="mb-2"><strong>{{ __('اسم السائق:') }}</strong> <span id="modal-driver-name"></span></p>
                                        <p class="mb-2"><strong>{{ __('رقم الجوال:') }}</strong> <span id="modal-driver-mobile" dir="ltr"></span></p>
                                        <hr class="my-2">
                                        <p class="mb-2"><strong>{{ __('اسم المستفيد:') }}</strong> <span id="modal-beneficiary-name" class="fw-bold text-dark"></span></p>
                                        <p class="mb-2"><strong>{{ __('اسم البنك:') }}</strong> <span id="modal-bank-name"></span></p>
                                        <p class="mb-2"><strong>{{ __('الآيبان (IBAN):') }}</strong> <span id="modal-iban" class="fw-bold text-primary font-monospace" dir="ltr"></span></p>
                                        <p class="mb-2"><strong>{{ __('كود السويفت/BIC:') }}</strong> <span id="modal-bic" class="font-monospace" dir="ltr"></span></p>
                                        <p class="mb-0"><strong>{{ __('العنوان / المدينة:') }}</strong> <span id="modal-address"></span></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Audit / Creation Info -->
                            <div class="col-md-6">
                                <div class="card h-100 border shadow-none">
                                    <div class="card-header bg-label-secondary py-2">
                                        <h6 class="mb-0 fw-bold"><i class="ti ti-history me-1"></i> {{ __('بيانات التتبع والاعتماد') }}</h6>
                                    </div>
                                    <div class="card-body pt-3 small">
                                        <p class="mb-2"><strong>{{ __('تاريخ إنشاء الطلب:') }}</strong> <span id="modal-created-at"></span></p>
                                        <p class="mb-2"><strong>{{ __('مُنشئ الطلب:') }}</strong> <span id="modal-created-by" class="badge bg-label-dark"></span></p>
                                        <hr class="my-2">
                                        <p class="mb-2"><strong>{{ __('معرف Payout (HyperPay):') }}</strong> <span id="modal-payout-id" class="font-monospace"></span></p>
                                        <p class="mb-2"><strong>{{ __('معرف الدفعة (Bulk ID):') }}</strong> <span id="modal-bulk-id" class="font-monospace"></span></p>
                                        <hr class="my-2">
                                        <p class="mb-2" id="modal-approved-wrapper" style="display: none;">
                                            <strong>{{ __('تمت المصادقة بواسطة:') }}</strong> <span id="modal-approved-by" class="text-success fw-bold"></span>
                                            <br><small class="text-muted">{{ __('في:') }} <span id="modal-approved-at"></span></small>
                                        </p>
                                        <p class="mb-2" id="modal-rejected-wrapper" style="display: none;">
                                            <strong class="text-danger">{{ __('تم الرفض بواسطة:') }}</strong> <span id="modal-rejected-by" class="text-danger fw-bold"></span>
                                            <br><small class="text-muted">{{ __('في:') }} <span id="modal-rejected-at"></span></small>
                                            <br><span class="text-danger fw-semibold">{{ __('سبب الرفض:') }} <span id="modal-rejection-reason"></span></span>
                                        </p>
                                        <p class="mb-0" id="modal-failure-wrapper" style="display: none;">
                                            <strong class="text-danger">{{ __('سبب الخطأ من بوابة الدفع:') }}</strong>
                                            <br><span id="modal-failure-reason" class="text-danger"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Description / Notes -->
                            <div class="col-12">
                                <div class="p-3 bg-light rounded">
                                    <h6 class="fw-bold mb-1"><i class="ti ti-notes me-1"></i> {{ __('الملاحظات / الوصف') }}</h6>
                                    <p class="mb-0 small text-muted" id="modal-notes"></p>
                                </div>
                            </div>

                            <!-- Receipt Image if available -->
                            <div class="col-12" id="modal-image-container" style="display: none;">
                                <h6 class="fw-bold mb-2"><i class="ti ti-photo me-1"></i> {{ __('مرفق السند') }}</h6>
                                <div class="text-center p-2 border rounded">
                                    <a id="modal-image-link" href="#" target="_blank">
                                        <img id="modal-image-preview" src="#" alt="{{ __('سند التحويل') }}" class="img-fluid rounded" style="max-height: 200px;">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('إغلاق') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Payout Modal -->
    <div class="modal fade" id="approvePayoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-label-success py-3">
                    <h5 class="modal-title d-flex align-items-center text-success">
                        <i class="ti ti-shield-check me-2 fs-4"></i>
                        {{ __('مصادقة وتحويل الدفع عبر HyperPay') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="approvePayoutForm">
                    <input type="hidden" id="approve_payout_id" name="id">
                    <div class="modal-body">
                        <!-- Warning Alert -->
                        <div class="alert alert-warning d-flex align-items-center mb-3">
                            <i class="ti ti-alert-triangle fs-3 me-2 flex-shrink-0"></i>
                            <div>
                                <strong>{{ __('تنبيه مالي هام!') }}</strong><br>
                                {{ __('عند تأكيد المصادقة، سيتم إرسال أمر تحويل بنكي فوري ومباشر إلى بوابة HyperPay Payout وخصم المبلغ من الحساب البنكي للمنصة.') }}
                            </div>
                        </div>

                        <!-- Summary Details Card -->
                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">{{ __('رقم المرجع:') }}</span>
                                    <span class="fw-bold" id="approve_ref_display"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">{{ __('اسم المستفيد:') }}</span>
                                    <span class="fw-bold" id="approve_beneficiary_display"></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                    <span class="fw-bold">{{ __('المبلغ المطلوب تحويله:') }}</span>
                                    <span class="fs-4 fw-bolder text-success"><span id="approve_amount_display">0.00</span> SAR</span>
                                </div>
                            </div>
                        </div>

                        <!-- Manager Password Confirmation -->
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="approve_password">
                                <i class="ti ti-lock me-1 text-primary"></i>
                                {{ __('كلمة مرور المدير للمصادقة') }} <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control form-control-lg" id="approve_password" name="password" placeholder="{{ __('أدخل كلمة مرورك لتأكيد العملية') }}" required autocomplete="current-password">
                            <small class="text-muted">{{ __('تأكيد هويتك كمسؤول مفوض قبل إرسال المبلغ لبوابة Payout.') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                        <button type="submit" class="btn btn-success d-flex align-items-center" id="btn-submit-approval">
                            <i class="ti ti-send me-1"></i> {{ __('تأكيد المصادقة والإرسال الفوري') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Payout Modal -->
    <div class="modal fade" id="rejectPayoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-label-danger py-3">
                    <h5 class="modal-title d-flex align-items-center text-danger">
                        <i class="ti ti-circle-x me-2 fs-4"></i>
                        {{ __('رفض طلب الدفع عبر الـ Payout') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectPayoutForm">
                    <input type="hidden" id="reject_payout_id" name="id">
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            {{ __('أنت على وشك رفض طلب الدفع رقم:') }} <strong id="reject_ref_display" class="text-dark"></strong>.
                            {{ __('لن يتم إرسال أي مبالغ، وسيتم إلغاء العملية.') }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="reject_reason">
                                {{ __('سبب الرفض') }} <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="reject_reason" name="reason" rows="3" placeholder="{{ __('اكتب سبب رفض طلب التحويل...') }}" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                        <button type="submit" class="btn btn-danger d-flex align-items-center" id="btn-submit-rejection">
                            <i class="ti ti-x me-1"></i> {{ __('تأكيد الرفض') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
