@extends('layouts/layoutMaster')

@section('title', __('طلبات الدفع عبر Payout للمستخدمين'))
@section('users-payout-requests-isactive', 'active')

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
        const payoutDataUrl = "{{ route('users.payout-requests.data') }}";
        const payoutShowUrl = "{{ route('users.payout-requests.show', ':id') }}";
        const payoutApproveUrl = "{{ route('users.payout-requests.approve', ':id') }}";
        const payoutRejectUrl = "{{ route('users.payout-requests.reject', ':id') }}";
        const payoutCheckStatusUrl = "{{ route('users.payout-requests.check-status', ':id') }}";
    </script>
    @vite(['resources/js/admin/users/payout-requests.js'])
@endsection

@section('content')
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-users text-primary me-2 fs-2 align-middle"></i>
                {{ __('طلبات الدفع عبر HyperPay Payout للمستخدمين') }}
            </h4>
            <p class="text-muted mb-0">{{ __('مراجعة ومصادقة مستحقات وحوالات المستخدمين بنكياً (نظام الرقابة والمصادقة الثنائية)') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.users') }}" class="btn btn-label-secondary">
                <i class="ti ti-users me-1"></i> {{ __('المستخدمون') }}
            </a>
            <a href="{{ route('wallets.payout-requests.index') }}" class="btn btn-label-primary">
                <i class="ti ti-send me-1"></i> {{ __('طلبات دفع السائقين') }}
            </a>
            <a href="{{ route('teams.payout-requests.index') }}" class="btn btn-label-success">
                <i class="ti ti-building-bank me-1"></i> {{ __('طلبات دفع الفرق') }}
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
                            <span class="fw-semibold text-success">{{ __('مكتملة ومحولة بنجاح') }}</span>
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
                            <small class="mb-0 text-muted">{{ __('مرفوض:') }} <strong>{{ $metrics['rejected_count'] }}</strong> | {{ __('فشل:') }} <strong>{{ $metrics['failed_count'] }}</strong></small>
                        </div>
                        <span class="badge bg-label-danger rounded p-2">
                            <i class="ti ti-circle-x ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Table Card -->
    <div class="card shadow-sm">
        <div class="card-header border-bottom py-3">
            <div class="row g-3 align-items-end">
                <!-- Status Filter -->
                <div class="col-md-3">
                    <label class="form-label" for="filter_status">{{ __('الحالة:') }}</label>
                    <select id="filter_status" class="form-select">
                        <option value="" selected>{{ __('جميع الحالات') }}</option>
                        <option value="pending_approval">{{ __('بانتظار المصادقة') }}</option>
                        <option value="processing">{{ __('قيد المعالجة بالبنك') }}</option>
                        <option value="completed">{{ __('مكتملة') }}</option>
                        <option value="rejected">{{ __('مرفوضة') }}</option>
                        <option value="failed">{{ __('فاشلة') }}</option>
                    </select>
                </div>

                <!-- User Filter -->
                <div class="col-md-3">
                    <label class="form-label" for="filter_user">{{ __('المستخدم:') }}</label>
                    <select id="filter_user" class="form-select select2">
                        <option value="">{{ __('كل المستخدمين') }}</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->phone }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range -->
                <div class="col-md-2">
                    <label class="form-label" for="filter_from_date">{{ __('من تاريخ:') }}</label>
                    <input type="date" id="filter_from_date" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filter_to_date">{{ __('إلى تاريخ:') }}</label>
                    <input type="date" id="filter_to_date" class="form-control">
                </div>

                <!-- Actions -->
                <div class="col-md-2 d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary w-100" id="btn-refresh-table" title="{{ __('تحديث') }}">
                        <i class="ti ti-refresh me-1"></i> {{ __('تحديث') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="card-datatable table-responsive">
            <table class="datatables-user-payout-requests table border-top table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('رقم المرجع') }}</th>
                        <th>{{ __('المستخدم') }}</th>
                        <th>{{ __('البيانات البنكية') }}</th>
                        <th>{{ __('المبلغ') }}</th>
                        <th>{{ __('الحالة') }}</th>
                        <th>{{ __('تاريخ الإنشاء') }}</th>
                        <th>{{ __('سجل التدقيق') }}</th>
                        <th>{{ __('الإجراءات') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-label-primary py-3">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="ti ti-file-text me-2 fs-4"></i>
                        {{ __('تفاصيل طلب الدفع عبر الـ Payout') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="modal-loading-spinner" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">{{ __('جاري تحميل التفاصيل...') }}</p>
                    </div>

                    <div id="modal-content-area" style="display: none;">
                        <!-- Status and Reference Header -->
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3 flex-wrap gap-2">
                            <div>
                                <span class="text-muted d-block small">{{ __('رقم العملية المرجعي') }}</span>
                                <h5 class="mb-0 fw-bold font-monospace text-primary" id="detail_reference_id"></h5>
                            </div>
                            <div class="text-end">
                                <span class="text-muted d-block small mb-1">{{ __('الحالة الحالية') }}</span>
                                <div id="detail_status_badge"></div>
                            </div>
                        </div>

                        <!-- Amount Highlight Card -->
                        <div class="bg-label-success rounded p-3 mb-4 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small fw-semibold">{{ __('المبلغ المطلوب صرفه') }}</span>
                                <h3 class="mb-0 text-success fw-bold"><span id="detail_amount">0.00</span> <small class="fs-6">ر.س</small></h3>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-success fs-6" id="detail_payout_type"></span>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- User Information -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="fw-bold mb-3 text-secondary d-flex align-items-center">
                                        <i class="ti ti-user me-2"></i> {{ __('بيانات المستخدم') }}
                                    </h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2 d-flex justify-content-between">
                                            <span class="text-muted">{{ __('الاسم:') }}</span>
                                            <span class="fw-semibold" id="detail_user_name"></span>
                                        </li>
                                        <li class="mb-2 d-flex justify-content-between">
                                            <span class="text-muted">{{ __('الهاتف:') }}</span>
                                            <span class="fw-semibold font-monospace" id="detail_user_phone"></span>
                                        </li>
                                        <li class="mb-2 d-flex justify-content-between">
                                            <span class="text-muted">{{ __('البريد الإلكتروني:') }}</span>
                                            <span class="fw-semibold" id="detail_user_email"></span>
                                        </li>
                                        <li class="mb-2 d-flex justify-content-between">
                                            <span class="text-muted">{{ __('رصيد المحفظة:') }}</span>
                                            <span class="badge bg-label-info fw-bold" id="detail_user_balance"></span>
                                        </li>
                                        <li class="mt-3">
                                            <a href="#" target="_blank" id="detail_wallet_link" class="btn btn-sm btn-label-primary w-100">
                                                <i class="ti ti-wallet me-1"></i> {{ __('فتح محفظة المستخدم') }}
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Bank Details -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="fw-bold mb-3 text-secondary d-flex align-items-center">
                                        <i class="ti ti-building-bank me-2"></i> {{ __('البيانات البنكية للتحويل') }}
                                    </h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2 d-flex justify-content-between">
                                            <span class="text-muted">{{ __('المستفيد:') }}</span>
                                            <span class="fw-bold text-dark" id="detail_bank_beneficiary"></span>
                                        </li>
                                        <li class="mb-2 d-flex justify-content-between">
                                            <span class="text-muted">{{ __('البنك:') }}</span>
                                            <span class="fw-semibold" id="detail_bank_name"></span>
                                        </li>
                                        <li class="mb-2 d-flex justify-content-between">
                                            <span class="text-muted">{{ __('الآيبان IBAN:') }}</span>
                                            <span class="font-monospace fw-bold text-primary" id="detail_bank_iban"></span>
                                        </li>
                                        <li class="mb-2 d-flex justify-content-between">
                                            <span class="text-muted">{{ __('كود السويفت BIC:') }}</span>
                                            <span class="font-monospace" id="detail_bank_bic"></span>
                                        </li>
                                        <li class="mb-0 d-flex justify-content-between">
                                            <span class="text-muted">{{ __('الموقع:') }}</span>
                                            <span id="detail_bank_location"></span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- System Audit & HyperPay IDs -->
                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <h6 class="fw-bold mb-3 text-secondary d-flex align-items-center">
                                        <i class="ti ti-shield-check me-2"></i> {{ __('بيانات المتابعة والتدقيق') }}
                                    </h6>
                                    <div class="row g-2 small">
                                        <div class="col-md-3">
                                            <span class="text-muted d-block">{{ __('أنشئ في:') }}</span>
                                            <span class="fw-semibold" id="detail_created_at"></span>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="text-muted d-block">{{ __('بواسطة:') }}</span>
                                            <span class="fw-semibold" id="detail_created_by"></span>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="text-muted d-block">{{ __('معرف HyperPay Payout ID:') }}</span>
                                            <span class="font-monospace fw-bold" id="detail_payout_id"></span>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="text-muted d-block">{{ __('معرف الدفعة Bulk ID:') }}</span>
                                            <span class="font-monospace" id="detail_bulk_id"></span>
                                        </div>
                                        <div class="col-12 mt-2" id="detail_approval_info"></div>
                                        <div class="col-12 mt-2" id="detail_rejection_label" style="display: none;">
                                            <div class="alert alert-danger py-2 mb-0">
                                                <strong>{{ __('سبب الرفض:') }}</strong> <span id="detail_rejection_reason"></span>
                                            </div>
                                        </div>
                                        <div class="col-12 mt-2" id="detail_failure_label" style="display: none;">
                                            <div class="alert alert-warning py-2 mb-0">
                                                <strong>{{ __('سبب الفشل البنكي:') }}</strong> <span id="detail_failure_reason"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="col-12">
                                <div class="border rounded p-3">
                                    <h6 class="fw-bold mb-2 text-secondary">{{ __('ملاحظات العملية:') }}</h6>
                                    <p class="mb-0 text-muted" id="detail_notes">—</p>
                                </div>
                            </div>

                            <!-- Attachment Box -->
                            <div class="col-12" id="detail_attachment_box" style="display: none;">
                                <div class="border rounded p-3">
                                    <h6 class="fw-bold mb-2 text-secondary">{{ __('المرفق (سند/فاتورة):') }}</h6>
                                    <div id="detail_attachment_content"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('إغلاق') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal (Manager Password Required) -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-label-success py-3">
                    <h5 class="modal-title d-flex align-items-center text-success">
                        <i class="ti ti-check-circle me-2 fs-4"></i>
                        {{ __('المصادقة على صرف الحوالة عبر HyperPay') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="approvePayoutForm">
                    <input type="hidden" id="approve_payout_id" name="id">
                    <div class="modal-body p-4">
                        <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                            <i class="ti ti-alert-triangle fs-3 me-2"></i>
                            <div>
                                {{ __('سيتم إرسال أمر تحويل بنكي فوري عبر بوابة HyperPay Payout لحساب المستخدم.') }}
                            </div>
                        </div>

                        <div class="card bg-light border mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">{{ __('رقم المرجع:') }}</span>
                                    <span class="fw-bold font-monospace" id="approve_modal_reference"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">{{ __('المستفيد:') }}</span>
                                    <span class="fw-bold" id="approve_modal_user"></span>
                                </div>
                                <div class="d-flex justify-content-between border-top pt-2">
                                    <span class="fw-bold">{{ __('المبلغ:') }}</span>
                                    <span class="fw-bold text-success fs-5"><span id="approve_modal_amount"></span> ر.س</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-danger" for="manager_password">
                                <i class="ti ti-lock me-1"></i> {{ __('كلمة مرور المدير للمصادقة') }} <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control form-control-lg border-danger" id="manager_password" name="password" placeholder="{{ __('أدخل كلمة المرور الخاصة بك لتأكيد العملية') }}" required autocomplete="current-password">
                            <small class="text-muted">{{ __('إجراء أمني للتحقق من هوية المدير المصادق.') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                        <button type="submit" class="btn btn-success" id="btn-submit-approve">
                            <i class="ti ti-send me-1"></i> {{ __('تأكيد وإرسال الحوالة البنكية') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-label-danger py-3">
                    <h5 class="modal-title d-flex align-items-center text-danger">
                        <i class="ti ti-ban me-2 fs-4"></i>
                        {{ __('رفض طلب الدفع عبر الـ Payout للمستخدم') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectPayoutForm">
                    <input type="hidden" id="reject_payout_id" name="id">
                    <div class="modal-body p-4">
                        <p class="text-muted mb-3">
                            {{ __('سيتم إلغاء طلب الدفع نهائياً دون المساس برصيد المحفظة أو إرسال أي حوالة بنكية.') }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="rejection_reason">
                                {{ __('سبب الرفض') }} <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="rejection_reason" name="reason" rows="3" placeholder="{{ __('يرجى توضيح سبب الرفض...') }}" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                        <button type="submit" class="btn btn-danger" id="btn-submit-reject">
                            <i class="ti ti-trash me-1"></i> {{ __('تأكيد الرفض') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
