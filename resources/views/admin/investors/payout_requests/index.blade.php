@extends('layouts/layoutMaster')

@section('title', __('طلبات الدفع عبر Payout للمستثمرين'))
@section('investors-payout-requests-isactive', 'active')

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
        const payoutDataUrl = "{{ route('investors.payout-requests.data') }}";
        const payoutShowUrl = "{{ route('investors.payout-requests.show', ':id') }}";
        const payoutApproveUrl = "{{ route('investors.payout-requests.approve', ':id') }}";
        const payoutRejectUrl = "{{ route('investors.payout-requests.reject', ':id') }}";
    </script>
    @vite(['resources/js/admin/investors/payout-requests.js'])
@endsection

@section('content')
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-cash text-primary me-2 fs-2 align-middle"></i>
                {{ __('طلبات الدفع عبر HyperPay Payout للمستثمرين') }}
            </h4>
            <p class="text-muted mb-0">{{ __('مراجعة ومصادقة صرف عمولات المستثمرين بنكياً (نظام الرقابة والمصادقة الثنائية)') }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.investors.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-briefcase me-1"></i> {{ __('المستثمرون') }}
            </a>
            <a href="{{ route('admin.investors.commission-withdrawals.index') }}" class="btn btn-label-info">
                <i class="ti ti-file-invoice me-1"></i> {{ __('طلبات سحب العمولات') }}
            </a>
            <a href="{{ route('wallets.payout-requests.index') }}" class="btn btn-label-primary">
                <i class="ti ti-send me-1"></i> {{ __('طلبات دفع السائقين') }}
            </a>
            <a href="{{ route('teams.payout-requests.index') }}" class="btn btn-label-success">
                <i class="ti ti-building-bank me-1"></i> {{ __('طلبات دفع الفرق') }}
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
                    <label class="form-label fw-semibold">{{ __('المستثمر') }}</label>
                    <select id="filter_investor" class="form-select select2">
                        <option value="" selected>{{ __('جميع المستثمرين') }}</option>
                        @foreach ($investors as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->name }} ({{ $inv->phone }})</option>
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

    <!-- Data Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-datatable table-responsive">
            <table class="datatables-payout-requests table border-top">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('رقم المرجع') }}</th>
                        <th>{{ __('المستثمر') }}</th>
                        <th>{{ __('بيانات المستفيد والبنك') }}</th>
                        <th>{{ __('المبلغ') }}</th>
                        <th>{{ __('الحالة') }}</th>
                        <th>{{ __('تاريخ الإنشاء') }}</th>
                        <th>{{ __('سجل الإجراءات') }}</th>
                        <th>{{ __('العمليات') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">
                        <i class="ti ti-file-invoice text-primary me-2"></i>
                        {{ __('تفاصيل طلب دفع Payout للمستثمر') }}
                        <span id="detail_reference_id" class="badge bg-label-primary font-monospace ms-2"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="modal-loading-spinner" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="modal-content-area" style="display: none;">
                        <!-- Top Summary Row -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-center">
                                    <small class="text-muted d-block mb-1">{{ __('مبلغ الحوالة') }}</small>
                                    <h4 class="text-success mb-0 fw-bold"><span id="detail_amount"></span> <small class="fs-6">ر.س</small></h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-center">
                                    <small class="text-muted d-block mb-1">{{ __('الحالة الحالية') }}</small>
                                    <div id="detail_status_badge" class="mt-1"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-center">
                                    <small class="text-muted d-block mb-1">{{ __('نوع العملية') }}</small>
                                    <span id="detail_payout_type" class="fw-semibold text-heading"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Investor & Bank Info Grid -->
                        <div class="row g-4 mb-4">
                            <!-- Investor Info -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="fw-bold mb-3 text-primary">
                                        <i class="ti ti-user me-1"></i> {{ __('بيانات المستثمر والمحفظة') }}
                                    </h6>
                                    <dl class="row mb-0 small">
                                        <dt class="col-sm-5 text-muted">{{ __('اسم المستثمر:') }}</dt>
                                        <dd class="col-sm-7 fw-semibold" id="detail_investor_name"></dd>

                                        <dt class="col-sm-5 text-muted">{{ __('رقم الهاتف:') }}</dt>
                                        <dd class="col-sm-7" id="detail_investor_phone"></dd>

                                        <dt class="col-sm-5 text-muted">{{ __('البريد الإلكتروني:') }}</dt>
                                        <dd class="col-sm-7" id="detail_investor_email"></dd>

                                        <dt class="col-sm-5 text-muted">{{ __('رصيد محفظة العمولات:') }}</dt>
                                        <dd class="col-sm-7 fw-bold text-success" id="detail_investor_balance"></dd>

                                        <dt class="col-sm-5 text-muted">{{ __('رابط المحفظة:') }}</dt>
                                        <dd class="col-sm-7">
                                            <a href="#" id="detail_wallet_link" target="_blank" class="btn btn-xs btn-label-primary">
                                                <i class="ti ti-external-link me-1"></i> {{ __('عرض المحفظة') }}
                                            </a>
                                        </dd>
                                    </dl>
                                </div>
                            </div>

                            <!-- Bank Info -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h6 class="fw-bold mb-3 text-primary">
                                        <i class="ti ti-building-bank me-1"></i> {{ __('بيانات التحويل البنكي') }}
                                    </h6>
                                    <dl class="row mb-0 small">
                                        <dt class="col-sm-5 text-muted">{{ __('اسم المستفيد:') }}</dt>
                                        <dd class="col-sm-7 fw-bold" id="detail_bank_beneficiary"></dd>

                                        <dt class="col-sm-5 text-muted">{{ __('اسم البنك:') }}</dt>
                                        <dd class="col-sm-7" id="detail_bank_name"></dd>

                                        <dt class="col-sm-5 text-muted">{{ __('رقم الآيبان (IBAN):') }}</dt>
                                        <dd class="col-sm-7 font-monospace fw-semibold text-break" id="detail_bank_iban"></dd>

                                        <dt class="col-sm-5 text-muted">{{ __('رمز السويفت (BIC):') }}</dt>
                                        <dd class="col-sm-7 font-monospace" id="detail_bank_bic"></dd>

                                        <dt class="col-sm-5 text-muted">{{ __('المدينة والدولة:') }}</dt>
                                        <dd class="col-sm-7" id="detail_bank_location"></dd>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Technical & Audit Details -->
                        <div class="border rounded p-3 mb-4">
                            <h6 class="fw-bold mb-3 text-secondary">
                                <i class="ti ti-shield-check me-1"></i> {{ __('سجل المعالجة والتدقيق الأمني') }}
                            </h6>
                            <dl class="row mb-0 small">
                                <dt class="col-sm-4 text-muted">{{ __('تاريخ الإنشاء والطلب:') }}</dt>
                                <dd class="col-sm-8" id="detail_created_at"></dd>

                                <dt class="col-sm-4 text-muted">{{ __('أنشئ بواسطة:') }}</dt>
                                <dd class="col-sm-8" id="detail_created_by"></dd>

                                <dt class="col-sm-4 text-muted">{{ __('معرف HyperPay PayoutId:') }}</dt>
                                <dd class="col-sm-8 font-monospace" id="detail_payout_id"></dd>

                                <dt class="col-sm-4 text-muted">{{ __('معرف الحزمة BulkId:') }}</dt>
                                <dd class="col-sm-8 font-monospace" id="detail_bulk_id"></dd>

                                <dt class="col-sm-4 text-muted">{{ __('المصادقة والاعتماد:') }}</dt>
                                <dd class="col-sm-8" id="detail_approval_info"></dd>

                                <dt class="col-sm-4 text-muted" id="detail_rejection_label" style="display:none;">{{ __('سبب الرفض:') }}</dt>
                                <dd class="col-sm-8 text-danger" id="detail_rejection_reason" style="display:none;"></dd>

                                <dt class="col-sm-4 text-muted" id="detail_failure_label" style="display:none;">{{ __('سبب فشل الحوالة:') }}</dt>
                                <dd class="col-sm-8 text-danger" id="detail_failure_reason" style="display:none;"></dd>

                                <dt class="col-sm-4 text-muted">{{ __('وصف / ملاحظات الطلب:') }}</dt>
                                <dd class="col-sm-8" id="detail_notes"></dd>
                            </dl>
                        </div>

                        <!-- Attachment Preview if any -->
                        <div id="detail_attachment_box" class="border rounded p-3" style="display:none;">
                            <h6 class="fw-bold mb-2 text-secondary">
                                <i class="ti ti-paperclip me-1"></i> {{ __('المرفق المرفوع (الإيصال / الفاتورة)') }}
                            </h6>
                            <div id="detail_attachment_content"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('إغلاق') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Manager Approval Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="approvePayoutForm">
                    @csrf
                    <input type="hidden" id="approve_payout_id" name="payout_id">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-success">
                            <i class="ti ti-circle-check me-2"></i>
                            {{ __('مصادقة واعتماد صرف الحوالة البنكية (HyperPay)') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                            <i class="ti ti-alert-triangle fs-4 me-2"></i>
                            <div>
                                {{ __('تحذير: سيتم إرسال أمر تحويل بنكي فوري ومباشر إلى حساب المستثمر عبر بوابة هايبر باي.') }}
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">{{ __('المستثمر:') }}</span>
                                <strong id="approve_modal_investor" class="text-heading"></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">{{ __('رقم المرجع:') }}</span>
                                <span id="approve_modal_reference" class="font-monospace fw-semibold"></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">{{ __('المبلغ المصادق عليه:') }}</span>
                                <h5 class="text-success mb-0 fw-bold"><span id="approve_modal_amount"></span> ر.س</h5>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" for="manager_password">
                                <i class="ti ti-lock me-1"></i>
                                {{ __('أدخل كلمة المرور الخاصة بك لتأكيد الاعتماد:') }}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="manager_password" name="password" class="form-control form-control-lg" placeholder="••••••••" required autocomplete="current-password">
                            <small class="text-muted">{{ __('إجراء أمني للرقابة والمصادقة الثنائية لضمان تفويض المسؤول المالي.') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
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
                <form id="rejectPayoutForm">
                    @csrf
                    <input type="hidden" id="reject_payout_id" name="payout_id">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold text-danger">
                            <i class="ti ti-x me-2"></i>
                            {{ __('رفض طلب دفع الـ Payout للمستثمر') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted mb-3">
                            {{ __('عند رفض الطلب، لن يتم إرسال أي حوالة مالية، ولن يتم إجراء أي خصم من محفظة العمولات للمستثمر.') }}
                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="rejection_reason">
                                {{ __('سبب الرفض:') }} <span class="text-danger">*</span>
                            </label>
                            <textarea id="rejection_reason" name="reason" rows="3" class="form-control" placeholder="{{ __('اكتب سبب الرفض هنا لتوثيقه في السجل...') }}" required minlength="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                        <button type="submit" class="btn btn-danger" id="btn-submit-reject">
                            <i class="ti ti-trash me-1"></i> {{ __('تأكيد الرفض') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
