@extends('layouts/layoutMaster')

@section('title', __('طلبات الدفع عبر Payout للفرق'))
@section('teams-payout-requests-isactive', 'active')

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
        const payoutDataUrl = "{{ route('teams.payout-requests.data') }}";
        const payoutShowUrl = "{{ route('teams.payout-requests.show', ':id') }}";
        const payoutApproveUrl = "{{ route('teams.payout-requests.approve', ':id') }}";
        const payoutRejectUrl = "{{ route('teams.payout-requests.reject', ':id') }}";
    </script>
    @vite(['resources/js/admin/teams/payout-requests.js'])
@endsection

@section('content')
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-building-bank text-primary me-2 fs-2 align-middle"></i>
                {{ __('طلبات الدفع عبر HyperPay Payout للفرق') }}
            </h4>
            <p class="text-muted mb-0">{{ __('مراجعة ومصادقة التحويلات البنكية المباشرة للفرق (نظام الرقابة والمصادقة الثنائية)') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('teams.teams') }}" class="btn btn-label-secondary">
                <i class="ti ti-users-group me-1"></i> {{ __('قائمة الفرق') }}
            </a>
            <a href="{{ route('wallets.payout-requests.index') }}" class="btn btn-label-primary">
                <i class="ti ti-send me-1"></i> {{ __('طلبات دفع السائقين') }}
            </a>
            <a href="{{ route('investors.payout-requests.index') }}" class="btn btn-label-warning">
                <i class="ti ti-cash me-1"></i> {{ __('طلبات دفع المستثمرين') }}
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <!-- Pending Approval -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-top border-warning border-3 shadow-sm">
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
            <div class="card border-top border-info border-3 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="fw-semibold text-info">{{ __('قيد المعالجة بالبنك') }}</span>
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
            <div class="card border-top border-success border-3 shadow-sm">
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
            <div class="card border-top border-danger border-3 shadow-sm">
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
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('تصفية حسب الحالة') }}</label>
                    <select id="filter_status" class="form-select">
                        <option value="" selected>{{ __('جميع الحالات') }}</option>
                        <option value="pending_approval">{{ __('بانتظار المصادقة (تحتاج قرار)') }}</option>
                        <option value="processing">{{ __('قيد المعالجة بالبنك') }}</option>
                        <option value="completed">{{ __('مكتملة ومحولة بنجاح') }}</option>
                        <option value="rejected">{{ __('مرفوضة') }}</option>
                        <option value="failed">{{ __('فاشلة بالبوابة') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('الفريق') }}</label>
                    <select id="filter_team" class="form-select select2">
                        <option value="" selected>{{ __('جميع الفرق') }}</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">{{ __('من تاريخ') }}</label>
                    <input type="date" id="filter_from_date" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">{{ __('إلى تاريخ') }}</label>
                    <input type="date" id="filter_to_date" class="form-control">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary w-100" id="btn-refresh-table">
                        <i class="ti ti-refresh me-1"></i> {{ __('تحديث') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable Card -->
    <div class="card shadow-sm border-0">
        <div class="card-datatable table-responsive">
            <table class="datatables-team-payout-requests table table-hover border-top font-small">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('رقم المرجع') }}</th>
                        <th>{{ __('تاريخ الإنشاء') }}</th>
                        <th>{{ __('الفريق') }}</th>
                        <th>{{ __('نوع الدفعة') }}</th>
                        <th>{{ __('المبلغ') }}</th>
                        <th>{{ __('المستفيد / الآيبان') }}</th>
                        <th>{{ __('المنشئ') }}</th>
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
                        {{ __('تفاصيل طلب الدفع عبر الـ Payout للفريق') }}
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
                            <!-- Team & Bank Info -->
                            <div class="col-md-6">
                                <div class="card h-100 border shadow-none">
                                    <div class="card-header bg-label-info py-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold"><i class="ti ti-building-bank me-1"></i> {{ __('بيانات الفريق والمستفيد') }}</h6>
                                        <a id="modal-team-wallet-btn" href="#" target="_blank" class="btn btn-xs btn-success" style="display: none;">
                                            <i class="ti ti-wallet me-1"></i> {{ __('فتح المحفظة') }}
                                        </a>
                                    </div>
                                    <div class="card-body pt-3 small">
                                        <p class="mb-2"><strong>{{ __('اسم الفريق:') }}</strong> <span id="modal-team-name"></span></p>
                                        <hr class="my-2">
                                        <p class="mb-2"><strong>{{ __('اسم المستفيد:') }}</strong> <span id="modal-beneficiary-name" class="fw-bold text-dark"></span></p>
                                        <p class="mb-2"><strong>{{ __('اسم البنك:') }}</strong> <span id="modal-bank-name"></span></p>
                                        <p class="mb-2"><strong>{{ __('الآيبان (IBAN):') }}</strong> <span id="modal-iban" class="fw-bold text-primary font-monospace" dir="ltr"></span></p>
                                        <p class="mb-2"><strong>{{ __('كود السويفت/BIC:') }}</strong> <span id="modal-bic" class="font-monospace" dir="ltr"></span></p>
                                        <p class="mb-2"><strong>{{ __('رمز الغرض (Purpose):') }}</strong> <span id="modal-purpose" class="badge bg-label-dark"></span></p>
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

                            <!-- Receipt Attachment (Image / PDF / File) -->
                            <div class="col-12" id="modal-attachment-section">
                                <div class="card border shadow-none mb-0">
                                    <div class="card-header bg-label-secondary py-2">
                                        <h6 class="mb-0 fw-bold"><i class="ti ti-paperclip me-1"></i> {{ __('الملف / السند المرفق مع الطلب') }}</h6>
                                    </div>
                                    <div class="card-body pt-3">
                                        <!-- No file placeholder -->
                                        <div id="modal-no-attachment" class="text-center py-2 text-muted small">
                                            <i class="ti ti-file-off fs-4 d-block mb-1"></i>
                                            {{ __('لا يوجد ملف أو إشعار بنكي مرفق مع هذا الطلب.') }}
                                        </div>

                                        <!-- Image View -->
                                        <div id="modal-image-wrapper" style="display: none;">
                                            <div class="d-flex flex-column align-items-center p-3 bg-light rounded border">
                                                <div class="position-relative d-inline-block btn-open-image-lightbox" title="{{ __('انقر لتكبير الصورة') }}" style="cursor: zoom-in;">
                                                    <img id="modal-attachment-img" src="#" alt="{{ __('سند التحويل') }}" class="img-fluid rounded border shadow-sm" style="max-height: 250px; object-fit: contain;">
                                                    <span class="position-absolute top-50 start-50 translate-middle badge bg-dark bg-opacity-75 p-2 rounded-circle text-white shadow" style="pointer-events: none;">
                                                        <i class="ti ti-zoom-in fs-4"></i>
                                                    </span>
                                                </div>
                                                <div class="mt-2 d-flex gap-2">
                                                    <button type="button" id="modal-attachment-img-view-btn" class="btn btn-sm btn-primary btn-open-image-lightbox">
                                                        <i class="ti ti-zoom-in me-1"></i> {{ __('تكبير الصورة') }}
                                                    </button>
                                                    <a id="modal-attachment-img-download-btn" href="#" download class="btn btn-sm btn-outline-secondary">
                                                        <i class="ti ti-download me-1"></i> {{ __('تحميل الصورة') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- PDF / Document View -->
                                        <div id="modal-doc-wrapper" style="display: none;">
                                            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar avatar-md bg-label-danger rounded d-flex align-items-center justify-content-center">
                                                        <i class="ti ti-file-type-pdf fs-2"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1 fw-bold text-dark" id="modal-attachment-filename">{{ __('مستند السند (PDF)') }}</h6>
                                                        <span class="badge bg-label-danger" id="modal-attachment-ext-badge">PDF</span>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <a id="modal-attachment-doc-view-btn" href="#" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="ti ti-eye me-1"></i> {{ __('فتح واستعراض الملف') }}
                                                    </a>
                                                    <a id="modal-attachment-doc-download-btn" href="#" download class="btn btn-sm btn-outline-secondary">
                                                        <i class="ti ti-download me-1"></i> {{ __('تحميل') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                        {{ __('مصادقة وتحويل الدفع عبر HyperPay للفريق') }}
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
                                {{ __('عند تأكيد المصادقة، سيتم إرسال أمر تحويل بنكي فوري ومباشر إلى بوابة HyperPay Payout لحساب الفريق البنكي.') }}
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
                        {{ __('رفض طلب الدفع عبر الـ Payout للفريق') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectPayoutForm">
                    <input type="hidden" id="reject_payout_id" name="id">
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            {{ __('أنت على وشك رفض طلب الدفع رقم:') }} <strong id="reject_ref_display" class="text-dark"></strong>.
                            {{ __('لن يتم إرسال أي مبالغ، وسيتم إلغاء العملية دون المساس برصيد المحفظة.') }}
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

    <!-- Dedicated Image Lightbox Preview Modal -->
    <div class="modal fade" id="payoutImagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg bg-dark text-white">
                <div class="modal-header border-bottom border-secondary py-2 px-3 bg-dark d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-light py-1 px-2 d-flex align-items-center" data-bs-dismiss="modal">
                            <i class="ti ti-arrow-right me-1"></i> {{ __('العودة للتفاصيل') }}
                        </button>
                        <h6 class="modal-title text-white d-flex align-items-center mb-0 ms-2">
                            <i class="ti ti-photo me-2 text-primary fs-4"></i>
                            <span id="lightbox-modal-title">{{ __('معاينة سند التحويل المرفق') }}</span>
                        </h6>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" id="lightbox-zoom-toggle" class="btn btn-sm btn-outline-light py-1 px-2" title="{{ __('تبديل الحجم الكامل') }}">
                            <i class="ti ti-arrows-maximize me-1"></i> <span class="d-none d-sm-inline">{{ __('الحجم الطبيعي') }}</span>
                        </button>
                        <a id="lightbox-modal-download-btn" href="#" download class="btn btn-sm btn-primary py-1 px-2">
                            <i class="ti ti-download me-1"></i> {{ __('تحميل') }}
                        </a>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-2 d-flex justify-content-center align-items-center" style="background-color: #0f1117; min-height: 420px; max-height: 82vh; overflow: auto;">
                    <img id="lightbox-modal-image" src="#" alt="{{ __('صورة المرفق') }}" class="img-fluid rounded shadow" style="max-height: 78vh; max-width: 100%; object-fit: contain; cursor: zoom-in; transition: max-height 0.2s ease;">
                </div>
                <div class="modal-footer border-top border-secondary py-2 px-3 bg-dark d-flex justify-content-between align-items-center">
                    <span class="small text-muted font-monospace" id="lightbox-modal-filename"></span>
                    <button type="button" class="btn btn-sm btn-secondary d-flex align-items-center" data-bs-dismiss="modal">
                        <i class="ti ti-arrow-right me-1"></i> {{ __('العودة لنافذة التفاصيل') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
