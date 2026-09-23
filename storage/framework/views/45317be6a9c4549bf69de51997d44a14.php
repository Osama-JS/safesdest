<?php $__env->startSection('title', __('طلبات الدفع عبر Payout')); ?>
<?php $__env->startSection('payout-requests-isactive', 'active'); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/spinkit/spinkit.scss'
    ]); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')([
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/block-ui/block-ui.js'
    ]); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <script>
        const payoutDataUrl = "<?php echo e(route('wallets.payout-requests.data')); ?>";
        const payoutShowUrl = "<?php echo e(route('wallets.payout-requests.show', ':id')); ?>";
        const payoutApproveUrl = "<?php echo e(route('wallets.payout-requests.approve', ':id')); ?>";
        const payoutRejectUrl = "<?php echo e(route('wallets.payout-requests.reject', ':id')); ?>";
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/admin/wallets/payout-requests.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-send text-primary me-2 fs-2 align-middle"></i>
                <?php echo e(__('طلبات الدفع عبر HyperPay Payout')); ?>

            </h4>
            <p class="text-muted mb-0"><?php echo e(__('مراجعة ومصادقة التحويلات البنكية المباشرة للسائقين (نظام الرقابة والمصادقة الثنائية)')); ?></p>
        </div>
        <div>
            <a href="<?php echo e(route('wallets.withdrawals.index')); ?>" class="btn btn-label-secondary me-2">
                <i class="ti ti-cash-banknote me-1"></i> <?php echo e(__('طلبات السحب')); ?>

            </a>
            <a href="<?php echo e(route('wallets.wallets')); ?>" class="btn btn-label-primary">
                <i class="ti ti-wallet me-1"></i> <?php echo e(__('المحافظ')); ?>

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
                            <span class="fw-semibold text-warning"><?php echo e(__('بانتظار المصادقة')); ?></span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-warning"><?php echo e(number_format($metrics['pending_approval_amount'], 2)); ?> <?php echo e(__('SAR')); ?></h4>
                            </div>
                            <small class="mb-0 text-muted"><?php echo e(__('عدد الطلبات:')); ?> <strong><?php echo e($metrics['pending_approval_count']); ?></strong></small>
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
                            <span class="fw-semibold text-info"><?php echo e(__('قيد المعالجة البنكية')); ?></span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-info"><?php echo e(number_format($metrics['processing_amount'], 2)); ?> <?php echo e(__('SAR')); ?></h4>
                            </div>
                            <small class="mb-0 text-muted"><?php echo e(__('عدد الطلبات:')); ?> <strong><?php echo e($metrics['processing_count']); ?></strong></small>
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
                            <span class="fw-semibold text-success"><?php echo e(__('مكتملة ومحولة')); ?></span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-success"><?php echo e(number_format($metrics['completed_amount'], 2)); ?> <?php echo e(__('SAR')); ?></h4>
                            </div>
                            <small class="mb-0 text-muted"><?php echo e(__('عدد الطلبات:')); ?> <strong><?php echo e($metrics['completed_count']); ?></strong></small>
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
                            <span class="fw-semibold text-danger"><?php echo e(__('مرفوضة / فاشلة')); ?></span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-danger"><?php echo e($metrics['rejected_count'] + $metrics['failed_count']); ?></h4>
                            </div>
                            <small class="mb-0 text-muted"><?php echo e(__('مرفوضة:')); ?> <?php echo e($metrics['rejected_count']); ?> | <?php echo e(__('فاشلة:')); ?> <?php echo e($metrics['failed_count']); ?></small>
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
                    <label class="form-label fw-semibold"><?php echo e(__('تصفية حسب الحالة')); ?></label>
                    <select id="filter_status" class="form-select">
                        <option value="" selected><?php echo e(__('جميع الحالات')); ?></option>
                        <option value="pending_approval"><?php echo e(__('بانتظار المصادقة (تحتاج قرار)')); ?></option>
                        <option value="processing"><?php echo e(__('قيد المعالجة بالبنك')); ?></option>
                        <option value="completed"><?php echo e(__('مكتمل بنجاح')); ?></option>
                        <option value="rejected"><?php echo e(__('مرفوض')); ?></option>
                        <option value="failed"><?php echo e(__('فشل التحويل')); ?></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold"><?php echo e(__('نوع العملية')); ?></label>
                    <select id="filter_type" class="form-select">
                        <option value=""><?php echo e(__('جميع الأنواع')); ?></option>
                        <option value="MT"><?php echo e(__('حركة يدوية (سحب/خصم)')); ?></option>
                        <option value="WP"><?php echo e(__('تسوية مستحقات')); ?></option>
                        <option value="WD"><?php echo e(__('طلب سحب رصيد')); ?></option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold"><?php echo e(__('من تاريخ')); ?></label>
                    <input type="date" id="filter_from_date" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold"><?php echo e(__('إلى تاريخ')); ?></label>
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
                <?php echo e(__('جدول طلبات الدفع عبر Payout')); ?>

            </h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-table">
                <i class="ti ti-refresh me-1"></i> <?php echo e(__('تحديث البيانات')); ?>

            </button>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-payout-requests table table-hover border-top font-small">
                <thead>
                    <tr>
                        <th><?php echo e(__('رقم المرجع')); ?></th>
                        <th><?php echo e(__('التاريخ')); ?></th>
                        <th><?php echo e(__('السائق')); ?></th>
                        <th><?php echo e(__('النوع')); ?></th>
                        <th><?php echo e(__('المبلغ')); ?></th>
                        <th><?php echo e(__('المستفيد والآيبان')); ?></th>
                        <th><?php echo e(__('المُنشئ')); ?></th>
                        <th><?php echo e(__('الحالة')); ?></th>
                        <th><?php echo e(__('المصادق / الرافض')); ?></th>
                        <th><?php echo e(__('الإجراءات')); ?></th>
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
                        <?php echo e(__('تفاصيل طلب الدفع عبر الـ Payout')); ?>

                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="payout-details-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2 text-muted"><?php echo e(__('جاري تحميل التفاصيل...')); ?></p>
                    </div>
                    <div id="payout-details-content" style="display: none;">
                        <!-- Status and Reference header -->
                        <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded mb-3">
                            <div>
                                <small class="text-muted d-block"><?php echo e(__('رقم المرجع الداخلي:')); ?></small>
                                <span class="fw-bold fs-5 text-dark" id="modal-reference-id"></span>
                            </div>
                            <div class="text-end">
                                <span id="modal-status-badge"></span>
                                <div class="small text-muted mt-1" id="modal-payout-type"></div>
                            </div>
                        </div>

                        <!-- Amount banner -->
                        <div class="alert alert-primary d-flex justify-content-between align-items-center py-2 px-3 mb-3">
                            <span class="fw-bold"><?php echo e(__('المبلغ المطلوب تحويله:')); ?></span>
                            <span class="fs-4 fw-bolder text-primary"><span id="modal-amount">0.00</span> SAR</span>
                        </div>

                        <div class="row g-3">
                            <!-- Driver & Bank Info -->
                            <div class="col-md-6">
                                <div class="card h-100 border shadow-none">
                                    <div class="card-header bg-label-info py-2 d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold"><i class="ti ti-steering-wheel me-1"></i> <?php echo e(__('بيانات السائق والمستفيد')); ?></h6>
                                        <a id="modal-driver-wallet-btn" href="#" target="_blank" class="btn btn-xs btn-success" style="display: none;">
                                            <i class="ti ti-wallet me-1"></i> <?php echo e(__('فتح المحفظة')); ?>

                                        </a>
                                    </div>
                                    <div class="card-body pt-3 small">
                                        <p class="mb-2"><strong><?php echo e(__('اسم السائق:')); ?></strong> <span id="modal-driver-name"></span></p>
                                        <p class="mb-2"><strong><?php echo e(__('رقم الجوال:')); ?></strong> <span id="modal-driver-mobile" dir="ltr"></span></p>
                                        <hr class="my-2">
                                        <p class="mb-2"><strong><?php echo e(__('اسم المستفيد:')); ?></strong> <span id="modal-beneficiary-name" class="fw-bold text-dark"></span></p>
                                        <p class="mb-2"><strong><?php echo e(__('اسم البنك:')); ?></strong> <span id="modal-bank-name"></span></p>
                                        <p class="mb-2"><strong><?php echo e(__('الآيبان (IBAN):')); ?></strong> <span id="modal-iban" class="fw-bold text-primary font-monospace" dir="ltr"></span></p>
                                        <p class="mb-2"><strong><?php echo e(__('كود السويفت/BIC:')); ?></strong> <span id="modal-bic" class="font-monospace" dir="ltr"></span></p>
                                        <p class="mb-0"><strong><?php echo e(__('العنوان / المدينة:')); ?></strong> <span id="modal-address"></span></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Audit / Creation Info -->
                            <div class="col-md-6">
                                <div class="card h-100 border shadow-none">
                                    <div class="card-header bg-label-secondary py-2">
                                        <h6 class="mb-0 fw-bold"><i class="ti ti-history me-1"></i> <?php echo e(__('بيانات التتبع والاعتماد')); ?></h6>
                                    </div>
                                    <div class="card-body pt-3 small">
                                        <p class="mb-2"><strong><?php echo e(__('تاريخ إنشاء الطلب:')); ?></strong> <span id="modal-created-at"></span></p>
                                        <p class="mb-2"><strong><?php echo e(__('مُنشئ الطلب:')); ?></strong> <span id="modal-created-by" class="badge bg-label-dark"></span></p>
                                        <hr class="my-2">
                                        <p class="mb-2"><strong><?php echo e(__('معرف Payout (HyperPay):')); ?></strong> <span id="modal-payout-id" class="font-monospace"></span></p>
                                        <p class="mb-2"><strong><?php echo e(__('معرف الدفعة (Bulk ID):')); ?></strong> <span id="modal-bulk-id" class="font-monospace"></span></p>
                                        <hr class="my-2">
                                        <p class="mb-2" id="modal-approved-wrapper" style="display: none;">
                                            <strong><?php echo e(__('تمت المصادقة بواسطة:')); ?></strong> <span id="modal-approved-by" class="text-success fw-bold"></span>
                                            <br><small class="text-muted"><?php echo e(__('في:')); ?> <span id="modal-approved-at"></span></small>
                                        </p>
                                        <p class="mb-2" id="modal-rejected-wrapper" style="display: none;">
                                            <strong class="text-danger"><?php echo e(__('تم الرفض بواسطة:')); ?></strong> <span id="modal-rejected-by" class="text-danger fw-bold"></span>
                                            <br><small class="text-muted"><?php echo e(__('في:')); ?> <span id="modal-rejected-at"></span></small>
                                            <br><span class="text-danger fw-semibold"><?php echo e(__('سبب الرفض:')); ?> <span id="modal-rejection-reason"></span></span>
                                        </p>
                                        <p class="mb-0" id="modal-failure-wrapper" style="display: none;">
                                            <strong class="text-danger"><?php echo e(__('سبب الخطأ من بوابة الدفع:')); ?></strong>
                                            <br><span id="modal-failure-reason" class="text-danger"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Description / Notes -->
                            <div class="col-12">
                                <div class="p-3 bg-light rounded">
                                    <h6 class="fw-bold mb-1"><i class="ti ti-notes me-1"></i> <?php echo e(__('الملاحظات / الوصف')); ?></h6>
                                    <p class="mb-0 small text-muted" id="modal-notes"></p>
                                </div>
                            </div>

                            <!-- Receipt Attachment (Image / PDF / File) -->
                            <div class="col-12" id="modal-attachment-section">
                                <div class="card border shadow-none mb-0">
                                    <div class="card-header bg-label-secondary py-2">
                                        <h6 class="mb-0 fw-bold"><i class="ti ti-paperclip me-1"></i> <?php echo e(__('الملف / السند المرفق مع الطلب')); ?></h6>
                                    </div>
                                    <div class="card-body pt-3">
                                        <!-- No file placeholder -->
                                        <div id="modal-no-attachment" class="text-center py-2 text-muted small">
                                            <i class="ti ti-file-off fs-4 d-block mb-1"></i>
                                            <?php echo e(__('لا يوجد ملف أو إشعار بنكي مرفق مع هذا الطلب.')); ?>

                                        </div>

                                        <!-- Image View -->
                                        <div id="modal-image-wrapper" style="display: none;">
                                            <div class="d-flex flex-column align-items-center p-3 bg-light rounded border">
                                                <div class="position-relative d-inline-block btn-open-image-lightbox" title="<?php echo e(__('انقر لتكبير الصورة')); ?>" style="cursor: zoom-in;">
                                                    <img id="modal-attachment-img" src="#" alt="<?php echo e(__('سند التحويل')); ?>" class="img-fluid rounded border shadow-sm" style="max-height: 250px; object-fit: contain;">
                                                    <span class="position-absolute top-50 start-50 translate-middle badge bg-dark bg-opacity-75 p-2 rounded-circle text-white shadow" style="pointer-events: none;">
                                                        <i class="ti ti-zoom-in fs-4"></i>
                                                    </span>
                                                </div>
                                                <div class="mt-2 d-flex gap-2">
                                                    <button type="button" id="modal-attachment-img-view-btn" class="btn btn-sm btn-primary btn-open-image-lightbox">
                                                        <i class="ti ti-zoom-in me-1"></i> <?php echo e(__('تكبير الصورة')); ?>

                                                    </button>
                                                    <a id="modal-attachment-img-download-btn" href="#" download class="btn btn-sm btn-outline-secondary">
                                                        <i class="ti ti-download me-1"></i> <?php echo e(__('تحميل الصورة')); ?>

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
                                                        <h6 class="mb-1 fw-bold text-dark" id="modal-attachment-filename"><?php echo e(__('مستند السند (PDF)')); ?></h6>
                                                        <span class="badge bg-label-danger" id="modal-attachment-ext-badge">PDF</span>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <a id="modal-attachment-doc-view-btn" href="#" target="_blank" class="btn btn-sm btn-primary">
                                                        <i class="ti ti-eye me-1"></i> <?php echo e(__('فتح واستعراض الملف')); ?>

                                                    </a>
                                                    <a id="modal-attachment-doc-download-btn" href="#" download class="btn btn-sm btn-outline-secondary">
                                                        <i class="ti ti-download me-1"></i> <?php echo e(__('تحميل')); ?>

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
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('إغلاق')); ?></button>
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
                        <?php echo e(__('مصادقة وتحويل الدفع عبر HyperPay')); ?>

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
                                <strong><?php echo e(__('تنبيه مالي هام!')); ?></strong><br>
                                <?php echo e(__('عند تأكيد المصادقة، سيتم إرسال أمر تحويل بنكي فوري ومباشر إلى بوابة HyperPay Payout وخصم المبلغ من الحساب البنكي للمنصة.')); ?>

                            </div>
                        </div>

                        <!-- Summary Details Card -->
                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted"><?php echo e(__('رقم المرجع:')); ?></span>
                                    <span class="fw-bold" id="approve_ref_display"></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted"><?php echo e(__('اسم المستفيد:')); ?></span>
                                    <span class="fw-bold" id="approve_beneficiary_display"></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                    <span class="fw-bold"><?php echo e(__('المبلغ المطلوب تحويله:')); ?></span>
                                    <span class="fs-4 fw-bolder text-success"><span id="approve_amount_display">0.00</span> SAR</span>
                                </div>
                            </div>
                        </div>

                        <!-- Manager Password Confirmation -->
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="approve_password">
                                <i class="ti ti-lock me-1 text-primary"></i>
                                <?php echo e(__('كلمة مرور المدير للمصادقة')); ?> <span class="text-danger">*</span>
                            </label>
                            <input type="password" class="form-control form-control-lg" id="approve_password" name="password" placeholder="<?php echo e(__('أدخل كلمة مرورك لتأكيد العملية')); ?>" required autocomplete="current-password">
                            <small class="text-muted"><?php echo e(__('تأكيد هويتك كمسؤول مفوض قبل إرسال المبلغ لبوابة Payout.')); ?></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('إلغاء')); ?></button>
                        <button type="submit" class="btn btn-success d-flex align-items-center" id="btn-submit-approval">
                            <i class="ti ti-send me-1"></i> <?php echo e(__('تأكيد المصادقة والإرسال الفوري')); ?>

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
                        <?php echo e(__('رفض طلب الدفع عبر الـ Payout')); ?>

                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectPayoutForm">
                    <input type="hidden" id="reject_payout_id" name="id">
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            <?php echo e(__('أنت على وشك رفض طلب الدفع رقم:')); ?> <strong id="reject_ref_display" class="text-dark"></strong>.
                            <?php echo e(__('لن يتم إرسال أي مبالغ، وسيتم إلغاء العملية.')); ?>

                        </p>
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="reject_reason">
                                <?php echo e(__('سبب الرفض')); ?> <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="reject_reason" name="reason" rows="3" placeholder="<?php echo e(__('اكتب سبب رفض طلب التحويل...')); ?>" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('إلغاء')); ?></button>
                        <button type="submit" class="btn btn-danger d-flex align-items-center" id="btn-submit-rejection">
                            <i class="ti ti-x me-1"></i> <?php echo e(__('تأكيد الرفض')); ?>

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
                            <i class="ti ti-arrow-right me-1"></i> <?php echo e(__('العودة للتفاصيل')); ?>

                        </button>
                        <h6 class="modal-title text-white d-flex align-items-center mb-0 ms-2">
                            <i class="ti ti-photo me-2 text-primary fs-4"></i>
                            <span id="lightbox-modal-title"><?php echo e(__('معاينة سند التحويل المرفق')); ?></span>
                        </h6>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" id="lightbox-zoom-toggle" class="btn btn-sm btn-outline-light py-1 px-2" title="<?php echo e(__('تبديل الحجم الكامل')); ?>">
                            <i class="ti ti-arrows-maximize me-1"></i> <span class="d-none d-sm-inline"><?php echo e(__('الحجم الطبيعي')); ?></span>
                        </button>
                        <a id="lightbox-modal-download-btn" href="#" download class="btn btn-sm btn-primary py-1 px-2">
                            <i class="ti ti-download me-1"></i> <?php echo e(__('تحميل')); ?>

                        </a>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-2 d-flex justify-content-center align-items-center" style="background-color: #0f1117; min-height: 420px; max-height: 82vh; overflow: auto;">
                    <img id="lightbox-modal-image" src="#" alt="<?php echo e(__('صورة المرفق')); ?>" class="img-fluid rounded shadow" style="max-height: 78vh; max-width: 100%; object-fit: contain; cursor: zoom-in; transition: max-height 0.2s ease;">
                </div>
                <div class="modal-footer border-top border-secondary py-2 px-3 bg-dark d-flex justify-content-between align-items-center">
                    <span class="small text-muted font-monospace" id="lightbox-modal-filename"></span>
                    <button type="button" class="btn btn-sm btn-secondary d-flex align-items-center" data-bs-dismiss="modal">
                        <i class="ti ti-arrow-right me-1"></i> <?php echo e(__('العودة لنافذة التفاصيل')); ?>

                    </button>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/wallets/payout_requests/index.blade.php ENDPATH**/ ?>