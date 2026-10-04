<?php $__env->startSection('title', __('Wallets') . ':' . $data->id); ?>

<?php $__env->startSection('vendor-style'); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss']); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/block-ui/block-ui.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <script>
        const walletId = "<?php echo e($data->id); ?>";
        const walletUserType = "<?php echo e($data->user_type); ?>";
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/admin/wallets/show.js']); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/ajax.js']); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/spical.js']); ?>


<?php $__env->stopSection(); ?>
<?php $__env->startSection('wallets-isactive'); ?>
    active
<?php $__env->stopSection(); ?>
<?php $__env->startSection('content'); ?>

    <?php
        $balance = $data->balance;
        $credit = $data->credit;
        $debit = $data->debit;
        $debtCeiling = $data->debt_ceiling;

        $balanceClass = $balance < 0 ? 'text-danger' : 'text-success';
        $balanceSign = $balance < 0 ? '-' : '+';

        // نسبة استخدام سقف الدين
        $usedDebt = abs($balance < 0 ? $balance : 0);
        $debtPercent = $debtCeiling > 0 ? min(100, round(($usedDebt / $debtCeiling) * 100)) : 0;

        $progressBarClass = $debtPercent < 50 ? 'bg-success' : ($debtPercent < 80 ? 'bg-warning' : 'bg-danger');
    ?>

    <div class="card shadow-sm border-0 mb-4">
        <!-- Header -->
        <div class="card-header  py-4 px-3 border-bottom">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <!-- Title -->
                <div>
                    <h5 class="card-title mb-1 text-primary fw-bold">
                        <i class="tf-icons ti ti-wallet  me-2 fs-3 text-white bg-primary rounded p-1"></i>
                        <?php echo e(__('Wallet')); ?>

                        <span class="text-muted">| [<?php echo e($data->id); ?>]</span>
                        <span class="text-dark"><?php echo e($data->owner->name); ?></span>
                    </h5>
                </div>

                <!-- Info Section -->
                <div class="d-flex flex-column flex-sm-row gap-3 text-nowrap">

                    <!-- Balance -->
                    <div class="d-flex align-items-center">
                        <i class="ti ti-wallet me-2 fs-5 <?php echo e($balanceClass); ?>"></i>
                        <span class="fw-semibold"><?php echo e(__('Balance')); ?>:</span>
                        <span class="ms-1 fw-bold <?php echo e($balanceClass); ?>">
                            <?php echo e($balanceSign); ?><?php echo e(number_format(abs($balance), 2)); ?>

                        </span>
                    </div>

                    <!-- Credit -->
                    <div class="d-flex align-items-center">
                        <i class="ti ti-arrow-up-right text-success me-2 fs-5"></i>
                        <span class="fw-semibold"><?php echo e(__('Credit')); ?>:</span>
                        <span class="ms-1 fw-bold text-success"><?php echo e(number_format($credit, 2)); ?></span>
                    </div>

                    <!-- Debit -->
                    <div class="d-flex align-items-center">
                        <i class="ti ti-arrow-down-left text-danger me-2 fs-5"></i>
                        <span class="fw-semibold"><?php echo e(__('Debit')); ?>:</span>
                        <span class="ms-1 fw-bold text-danger"><?php echo e(number_format($debit, 2)); ?></span>
                    </div>
                </div>
            </div>

            <!-- Progress Bar for Debt Ceiling -->
            <?php if($debtCeiling > 0): ?>
                <div class="mt-4">
                    <small class="text-muted d-block mb-1">
                        <?php echo e(__('Debt Usage')); ?> (<?php echo e($usedDebt); ?> / <?php echo e($debtCeiling); ?>) - <?php echo e($debtPercent); ?>%
                    </small>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar <?php echo e($progressBarClass); ?>" role="progressbar"
                            style="width: <?php echo e($debtPercent); ?>%;" aria-valuenow="<?php echo e($debtPercent); ?>" aria-valuemin="0"
                            aria-valuemax="100">
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('generate_payment_request')): ?>
                <?php if($data->user_type === 'driver'): ?>
                    <div class="mt-4">
                        <a href="javascript:;" class="btn btn-success me-2" id="payment-request"><i
                                class="ti ti-receipt me-1"></i><?php echo e(__('Payment Request')); ?></a>

                        <a href="<?php echo e(route('wallets.hyperpay_payouts', $data->id)); ?>" class="btn btn-primary" id="hyperpay-payouts-btn"><i class="ti ti-brand-mastercard me-1"></i><?php echo e(__('HyperPay Payouts')); ?></a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if($data->user_type === 'customer'): ?>
                <div class="mt-4 d-flex gap-2 flex-wrap">
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create_customer_invoices')): ?>
                        <button type="button" class="btn btn-primary" id="btnOpenCreateInvoiceModal">
                            <i class="ti ti-file-invoice me-1"></i> <?php echo e(__('Create Accounting Invoice')); ?>

                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

        <?php if($data->user_type === 'customer'): ?>
            <!-- Tabs for Customer Wallets -->
            <div class="card-header border-bottom py-2 px-3 bg-light">
                <ul class="nav nav-pills" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-transactions" aria-controls="navs-transactions" aria-selected="true">
                            <i class="ti ti-arrows-left-right me-1"></i> <?php echo e(__('Financial Transactions')); ?>

                        </button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" id="nav-tab-invoices" role="tab" data-bs-toggle="tab" data-bs-target="#navs-invoices" aria-controls="navs-invoices" aria-selected="false">
                            <i class="ti ti-file-invoice me-1"></i> <?php echo e(__('Accounting Invoices')); ?>

                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content p-0 border-0">
                <!-- Transactions Tab -->
                <div class="tab-pane fade show active" id="navs-transactions" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 datatables-users w-100">
                            <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>#</th>
                                    <th><?php echo e(__('Amount')); ?></th>
                                    <th><?php echo e(__('Description')); ?></th>
                                    <th><?php echo e(__('Maturity')); ?></th>
                                    <th><?php echo e(__('Task / Clearance')); ?></th>
                                    <th><?php echo e(__('User')); ?></th>
                                    <th><?php echo e(__('Created At')); ?></th>
                                    <th class="text-end"><?php echo e(__('Actions')); ?></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                <!-- Customer Invoices Tab -->
                <div class="tab-pane fade" id="navs-invoices" role="tabpanel">
                    <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 bg-light bg-opacity-25">
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0 fw-semibold text-muted"><?php echo e(__('Filter by Status')); ?>:</label>
                            <select class="form-select form-select-sm" id="filter-invoice-status" style="width: 170px;">
                                <option value="all"><?php echo e(__('All Statuses')); ?></option>
                                <option value="unpaid"><?php echo e(__('Unpaid')); ?></option>
                                <option value="paid"><?php echo e(__('Paid')); ?></option>
                                <option value="approved"><?php echo e(__('Approved')); ?></option>
                                <option value="cancelled"><?php echo e(__('Cancelled')); ?></option>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnRefreshInvoices">
                                <i class="ti ti-refresh me-1"></i> <?php echo e(__('Refresh')); ?>

                            </button>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create_customer_invoices')): ?>
                                <button type="button" class="btn btn-sm btn-primary" id="btnToolbarCreateInvoice">
                                    <i class="ti ti-plus me-1"></i> <?php echo e(__('New Accounting Invoice')); ?>

                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 datatables-customer-invoices w-100" id="invoicesTable">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th><?php echo e(__('Invoice Number')); ?></th>
                                    <th><?php echo e(__('Accounting Ref')); ?></th>
                                    <th><?php echo e(__('Total Amount')); ?></th>
                                    <th><?php echo e(__('Paid Amount')); ?></th>
                                    <th><?php echo e(__('Remaining')); ?></th>
                                    <th><?php echo e(__('Due Date')); ?></th>
                                    <th><?php echo e(__('Status')); ?></th>
                                    <th><?php echo e(__('Attachment')); ?></th>
                                    <th><?php echo e(__('Created By')); ?></th>
                                    <th class="text-end"><?php echo e(__('Actions')); ?></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Table for non-customer wallets -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 datatables-users w-100">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>#</th>
                                <th><?php echo e(__('Amount')); ?></th>
                                <th><?php echo e(__('Description')); ?></th>
                                <th><?php echo e(__('Maturity')); ?></th>
                                <th><?php echo e(__('Task / Clearance')); ?></th>
                                <th><?php echo e(__('User')); ?></th>
                                <th><?php echo e(__('Created At')); ?></th>
                                <th class="text-end"><?php echo e(__('Actions')); ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>


    <div class="modal fade " id="submitModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle"><?php echo e(__('Add New Transaction')); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="<?php echo e(__('Close')); ?>"></button>
                </div>
                <form class="add-new-transaction pt-0 form_submit" method="POST"
                    action="<?php echo e(route('wallets.transaction.store')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="modal-body">
                        <div class="col-xl-12">
                            <div class="nav-align-top mb-6">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <!-- Hidden wallet_id -->
                                        <input type="hidden" name="wallet" id="wallet_id" value="<?php echo e($data->id); ?>">
                                        <span class="wallet-error text-danger text-error"></span>

                                        <input type="hidden" name="id" id="trans_id">

                                        <!-- Amount -->
                                        <div class="mb-4">
                                            <label class="form-label" for="amount">* <?php echo e(__('Amount')); ?></label>
                                            <input type="number" name="amount" class="form-control" id="trans_amount"
                                                placeholder="<?php echo e(__('Enter the amount')); ?>" step="0.01" min="0">
                                            <span class="amount-error text-danger text-error"></span>
                                        </div>

                                        <!-- Transaction Type -->
                                        <div class="mb-4">
                                            <label class="form-label d-block">* <?php echo e(__('Transaction Type')); ?></label>
                                            <div class="row">
                                                <div class="col-6">
                                                    <input type="radio" class="btn-check" name="type" id="credit"
                                                        value="credit" autocomplete="off" required checked
                                                        onchange="
                                                            var mg = document.getElementById('maturity-time-group'); if (mg) mg.style.display = 'none';
                                                            var pmg = document.getElementById('payment-method-group'); if (pmg) pmg.style.display = 'none';
                                                            var hd = document.getElementById('manual-hyperpay-bank-details'); if (hd) hd.style.display = 'none';
                                                        ">
                                                    <label class="btn btn-outline-success w-100 py-2 btn-credit"
                                                        for="credit">
                                                        <i class="ti ti-circle-plus me-1"></i> <?php echo e(__('Credit')); ?>

                                                    </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="radio" class="btn-check" name="type" id="debit"
                                                        value="debit" autocomplete="off" required
                                                        onchange="
                                                            var mg = document.getElementById('maturity-time-group'); if (mg) mg.style.display = 'block';
                                                            var pmg = document.getElementById('payment-method-group'); if (pmg) pmg.style.display = 'block';
                                                            var pm = document.getElementById('trans_payment_method');
                                                            var hd = document.getElementById('manual-hyperpay-bank-details');
                                                            if (hd && pm && pm.value === 'hyperpay') hd.style.display = 'block';
                                                        ">
                                                    <label class="btn btn-outline-danger w-100 py-2 btn-debit"
                                                        for="debit">
                                                        <i class="ti ti-circle-minus me-1"></i> <?php echo e(__('Debit')); ?>

                                                    </label>
                                                </div>
                                            </div>
                                            <span class="type-error text-danger text-error"></span>
                                        </div>

                                        <!-- Investment Settlement Settings (Only visible for Credit and Customer Wallets) -->
                                        <?php if($data->user_type === 'customer'): ?>
                                            <div id="investment-settlement-container" class="mb-4">
                                                <button type="button" class="btn btn-outline-info w-100 mb-2" id="toggleSettlementPanelBtn">
                                                    <i class="ti ti-settings me-1"></i> <?php echo e(__('Investment Settlement Settings')); ?>

                                                </button>

                                                <div id="settlement-panel" class="border rounded p-3 bg-light" style="display: none;">
                                                    <h6 class="mb-2 text-primary"><i class="ti ti-list-check me-1"></i><?php echo e(__('Unsettled Investor Tasks')); ?></h6>
                                                    <p class="small text-muted mb-2"><?php echo e(__('Select tasks to settle with this credit amount.')); ?></p>

                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span class="fw-bold"><?php echo e(__('Credit Amount')); ?>: <span id="settlement-credit-amount" class="text-success">0</span> ريال</span>
                                                        <span class="fw-bold"><?php echo e(__('Selected Total')); ?>: <span id="settlement-selected-total" class="text-primary">0</span> ريال</span>
                                                        <span class="fw-bold"><?php echo e(__('Remaining Amount')); ?>: <span id="settlement-remaining-amount" class="text-warning">0</span> ريال</span>
                                                    </div>

                                                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                                        <table class="table table-sm table-bordered">
                                                            <thead class="table-dark sticky-top">
                                                                <tr>
                                                                    <th style="width: 40px;"><input type="checkbox" id="selectAllSettlementTasks" class="form-check-input"></th>
                                                                    <th><?php echo e(__('Task #')); ?></th>
                                                                    <th><?php echo e(__('Unpaid Debt')); ?></th>
                                                                    <th><?php echo e(__('Investor')); ?></th>
                                                                    <th><?php echo e(__('تسوية الاستثمار')); ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="settlement-tasks-tbody">
                                                                <!-- AJAX will load tasks here -->
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>



                                        <!-- Maturity Time (Hidden by default) -->
                                        <div class="mb-4" id="maturity-time-group" style="display: none;">
                                            <label class="form-label" for="maturity"><?php echo e(__('Maturity Time')); ?></label>
                                            <input type="datetime-local" name="maturity" class="form-control"
                                                id="trans_maturity">
                                            <span class="maturity-error text-danger text-error"></span>
                                        </div>

                                        <!-- Payment Method (Visible only if Debit is selected) -->
                                        <div class="mb-4" id="payment-method-group" style="display: none;">
                                            <label class="form-label fw-bold" for="trans_payment_method"><?php echo e(__('Payment Method')); ?></label>
                                            <select name="payment_method" class="form-select border-primary" id="trans_payment_method" onchange="
                                                var el = document.getElementById('manual-hyperpay-bank-details');
                                                if (el) el.style.display = (this.value === 'hyperpay') ? 'block' : 'none';
                                            ">
                                                <option value="manual" selected><?php echo e(__('Manual (Cash / Bank Transfer)')); ?></option>
                                                <?php if($data->user_type === 'driver'): ?>
                                                    <option value="hyperpay">⚡ <?php echo e(__('HyperPay Payout (تحويل بنكي فوري)')); ?></option>
                                                <?php endif; ?>
                                            </select>
                                            <span class="payment_method-error text-danger text-error"></span>
                                        </div>

                                        <!-- Bank Details & Payout Settings (Shown only for HyperPay) -->
                                        <?php if($data->user_type === 'driver'): ?>
                                            <div id="manual-hyperpay-bank-details" class="p-3 mb-4 rounded border border-primary bg-light shadow-sm"
                                                style="display: none;">
                                                <h6 class="fw-bold text-primary mb-3">
                                                    <i class="ti ti-brand-stripe me-1"></i><?php echo e(__('تفاصيل التحويل البنكي الفوري (HyperPay Payout)')); ?>

                                                </h6>

                                                <!-- Driver Bank Details Card -->
                                                <div class="card bg-white border mb-3">
                                                    <div class="card-body p-3">
                                                        <h6 class="card-title text-dark fw-bold mb-2">
                                                            <i class="ti ti-building-bank me-1 text-primary"></i><?php echo e(__('Driver Bank Details')); ?>

                                                        </h6>
                                                        <div class="row small">
                                                            <div class="col-md-6 mb-1"><strong><?php echo e(__('Beneficiary')); ?>:</strong>
                                                                <span class="text-dark fw-bold"><?php echo e($data->driver->beneficiary_name ?? 'N/A'); ?></span>
                                                            </div>
                                                            <div class="col-md-6 mb-1"><strong><?php echo e(__('Bank')); ?>:</strong>
                                                                <span><?php echo e($data->driver->bank_name ?? 'N/A'); ?></span>
                                                            </div>
                                                            <div class="col-md-12 mb-1"><strong><?php echo e(__('IBAN')); ?>:</strong>
                                                                <span class="font-monospace text-primary fw-bold"><?php echo e($data->driver->iban_number ?? 'N/A'); ?></span>
                                                            </div>
                                                            <div class="col-md-6"><strong><?php echo e(__('BIC/SWIFT')); ?>:</strong>
                                                                <span class="font-monospace"><?php echo e($data->driver->bic_code ?? 'N/A'); ?></span>
                                                            </div>
                                                        </div>
                                                        <?php if(!$data->driver->iban_number || !$data->driver->bic_code || !$data->driver->beneficiary_name): ?>
                                                            <div class="text-danger mt-2 fw-bold small">
                                                                <i class="ti ti-alert-triangle me-1"></i><?php echo e(__('Incomplete bank details! Payout may fail.')); ?>

                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- Beneficiary Name in English for HyperPay -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-dark d-flex justify-content-between" for="beneficiary_name_input">
                                                        <span><i class="ti ti-user me-1 text-primary"></i><?php echo e(__('اسم المستفيد بالإنجليزية (Beneficiary Name)')); ?> <span class="text-danger">*</span></span>
                                                        <small class="text-muted">مطلوب بحروف إنجليزية</small>
                                                    </label>
                                                    <input type="text" name="beneficiary_name" id="beneficiary_name_input" class="form-control bg-white text-dark border-primary"
                                                        value="<?php echo e(\App\Services\HyperPayPayoutService::formatBeneficiaryName($data->driver->beneficiary_name ?? '')); ?>"
                                                        placeholder="e.g. Amal Salman Al Faifi" required>
                                                    <small class="text-muted d-block mt-1">يجب أن يكون بالإنجليزية كما هو مسجل لدى البنك (تم تحويله تلقائياً ويمكنك تعديله)</small>
                                                </div>

                                                <!-- Admin Password -->
                                                <div class="mb-2">
                                                    <label class="form-label text-danger fw-bold" for="hyperpay_password">
                                                        <i class="ti ti-lock me-1"></i><?php echo e(__('كلمة مرور المشرف (مطلوبة لتأكيد التحويل)')); ?> <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="password" name="password" id="hyperpay_password" class="form-control border-danger" placeholder="أدخل كلمة المرور الخاصة بك لتأكيد عملية الدفع">
                                                    <span class="password-error text-danger text-error"></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Description -->
                                        <div class="mb-4">
                                            <label class="form-label" for="description">* <?php echo e(__('Description')); ?></label>
                                            <textarea name="description" class="form-control" id="trans_description" rows="3"
                                                placeholder="<?php echo e(__('Optional notes...')); ?>"></textarea>
                                            <span class="description-error text-danger text-error"></span>
                                        </div>
                                        <div class="mb-6">

                                            <div class="form-group mb-3">
                                                <label for="image" class="form-label">
                                                    <i class="fas fa-file-upload me-1"></i>
                                                    <?php echo e(__('Upload File')); ?>

                                                </label>
                                                <input type="file" name="image" class="form-control" id="image"
                                                    accept=".jpeg,.jpg,.png,.webp,.pdf,.doc,.docx,.txt,.csv">
                                                <div class="form-text text-muted mt-1">
                                                    <small>
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        <?php echo e(__('Supported formats: Images (JPEG, PNG, WebP), Documents (PDF). Max size: 10MB')); ?>

                                                    </small>
                                                </div>
                                                <span class="image-error text-danger text-error"></span>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
                        <button type="submit" class="btn btn-primary me-3 data-submit"><?php echo e(__('Submit')); ?></button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel"><?php echo e(__('View the File')); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="<?php echo e(__('close')); ?>"></button>
                </div>
                <div class="modal-body text-center" id="modalContent">
                    <img id="modalImage" src="" class="img-fluid rounded shadow" alt="<?php echo e(__('image')); ?>" />
                </div>
            </div>
        </div>
    </div>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('generate_payment_request')): ?>
        <div class="modal fade" id="paymentRequestModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo e(__('Payment Request from Wallet: ')); ?> <span id="paymentRequestWalletId"
                                class="bg-info text-white rounded p-1 px-2"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Task Information Section -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0"><?php echo e(__('Wallet Information')); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold"><?php echo e(__('Wallet ID')); ?>:</label>
                                            <span id="walletInfoId" class="text-primary fw-bold"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold"><?php echo e(__('Wallet Balance')); ?>:</label>
                                            <span id="walletInfoAmount" class="text-success fw-bold"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold"><?php echo e(__('Wallet Owner')); ?>:</label>
                                            <span id="walletInfoOwner"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold"><?php echo e(__('Phone')); ?>:</label>
                                            <span id="walletInfoOwnerPhone" class="text-muted"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold"><?php echo e(__('Email')); ?>:</label>
                                            <span id="walletInfoOwnerEmail" class="text-muted"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Request Form Section -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0"><?php echo e(__('Payment Request Form')); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <form id="paymentRequestForm">
                                            <input type="hidden" id="paymentRequestWalletIdInput" name="task_id">

                                            <div class="mb-3">
                                                <label class="form-label" for="requestedAmount">*
                                                    <?php echo e(__('Requested Amount')); ?></label>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" class="form-control"
                                                        id="requestedAmount" name="requested_amount" required>
                                                    <span class="input-group-text"><?php echo e(__('SAR')); ?></span>
                                                </div>
                                                <div class="form-text">
                                                    <small class="text-muted"><?php echo e(__('Available amount')); ?>: <span
                                                            id="maxAmount" class="text-primary fw-bold"></span>
                                                        (<?php echo e(__('You can enter a larger amount')); ?>)</small>
                                                </div>
                                                <span class="requested_amount-error text-error"></span>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="paymentMethod">*
                                                    <?php echo e(__('Payment Method')); ?></label>
                                                <select name="payment_method" id="paymentMethod" class="form-select"
                                                    required>
                                                    <option value=""><?php echo e(__('Select Payment Method')); ?></option>
                                                    <option value="bank_transfer"><?php echo e(__('Bank Transfer')); ?></option>
                                                    <option value="other"><?php echo e(__('Other Method')); ?></option>
                                                </select>
                                                <span class="payment_method-error text-error"></span>
                                            </div>

                                            <!-- Bank Transfer Fields -->
                                            <div id="bankTransferFields" style="display: none;">
                                                <div class="mb-3">
                                                    <label class="form-label" for="bankName"><?php echo e(__('Bank Name')); ?>

                                                        (<?php echo e(__('Optional')); ?>)</label>

                                                    <select name="bank_name" id="bankName" class="form-select">
                                                        <option value=""><?php echo e(__('Select Bank')); ?></option>
                                                        <option value="البنك الأهلي السعودي">البنك الأهلي السعودي
                                                        </option>
                                                        <option value="بنك الراجحي">بنك الراجحي</option>
                                                        <option value="بنك الرياض">بنك الرياض</option>
                                                        <option value="البنك السعودي للاستثمار">البنك السعودي
                                                            للاستثمار</option>
                                                        <option value="البنك السعودي الفرنسي">البنك السعودي
                                                            الفرنسي</option>
                                                        <option value="البنك السعودي البريطاني">البنك السعودي
                                                            البريطاني (ساب)</option>
                                                        <option value="بنك العربي الوطني">بنك العربي الوطني
                                                        </option>
                                                        <option value="بنك سامبا">بنك سامبا</option>
                                                        <option value="البنك الأول">البنك الأول</option>
                                                        <option value="بنك الجزيرة">بنك الجزيرة</option>
                                                        <option value="بنك الإنماء">بنك الإنماء</option>
                                                        <option value="البنك العربي">البنك العربي</option>
                                                        <option value="other"><?php echo e(__('Other')); ?></option>
                                                    </select>
                                                    <input type="text" class="form-control mt-2" id="customBankName"
                                                        name="custom_bank_name" placeholder="<?php echo e(__('Enter bank name')); ?>"
                                                        style="display: none;">
                                                    <span class="bank_name-error text-danger text-error"></span>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label" for="accountNumber">
                                                        <?php echo e(__('Account Number')); ?> (<?php echo e(__('Optional')); ?>)</label>
                                                    <input type="text" class="form-control" id="accountNumber"
                                                        name="account_number" placeholder="1234567890" minlength="8">
                                                    <span class="account_number-error text-danger text-error"></span>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label" for="ibanNumber">
                                                        <?php echo e(__('IBAN Number')); ?> (<?php echo e(__('Optional')); ?>)</label>
                                                    <input type="text" class="form-control" id="ibanNumber"
                                                        name="iban_number" placeholder="SA12 3456 7890 1234 5678 90"
                                                        maxlength="29">
                                                    <div class="form-text">
                                                        <small class="text-muted"><?php echo e(__('Format: SA + 22 digits')); ?></small>
                                                    </div>
                                                    <span class="iban_number-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Other Payment Method Field -->
                                            <div id="otherPaymentField" style="display: none;">
                                                <div class="mb-3">
                                                    <label class="form-label" for="otherPaymentMethod">*
                                                        <?php echo e(__('Payment Method Details')); ?></label>
                                                    <textarea class="form-control" id="otherPaymentMethod" name="other_payment_method" rows="3"
                                                        placeholder="<?php echo e(__('مثال: عهدة إلى الأخ فلان يسلمها إلى السائق محمد فلان')); ?>"></textarea>
                                                    <span class="other_payment_method-error text-error"></span>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="notes"><?php echo e(__('Notes')); ?></label>
                                                <textarea type="text" class="form-control" id="notes" name="notes" placeholder="Notes" maxlength="1000"></textarea>
                                                <span class="notes-error text-danger text-error"></span>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="selectedTasks"><?php echo e(__('Related Tasks')); ?>

                                                    (<?php echo e(__('Optional')); ?>)</label>
                                                <select class="form-select" id="selectedTasks" name="selected_tasks[]"
                                                    multiple>
                                                    <!-- سيتم تحميل المهام ديناميكياً -->
                                                </select>
                                                <div class="form-text">
                                                    <small
                                                        class="text-muted"><?php echo e(__('Select tasks related to this payment request')); ?></small>
                                                </div>
                                                <span class="selected_tasks-error text-danger text-error"></span>
                                            </div>


                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
                        <button type="button" class="btn btn-primary"
                            id="generatePaymentRequest"><?php echo e(__('Generate Payment Request')); ?></button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view_payment_requests_logs')): ?>
        <!-- Payment Request Logs Section -->
        <?php if($data->user_type === 'driver'): ?>
            <div class="card shadow-sm border-0 mt-4" id="payment-logs-section">
                <div class="card-header py-4 px-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="ti ti-file-text me-2 text-primary"></i>
                                <?php echo e(__('Payment Request Logs')); ?>

                            </h5>
                            <p class="text-muted mb-0"><?php echo e(__('History of printed payment requests')); ?></p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="loadRefresh">
                                <i class="ti ti-refresh me-1"></i>
                                <?php echo e(__('Refresh')); ?>

                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Logs Container -->
                    <div id="payment-logs-container">
                        <!-- Loading state -->
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden"><?php echo e(__('Loading')); ?>...</span>
                            </div>
                            <p class="text-muted mt-2"><?php echo e(__('Loading payment request logs')); ?>...</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>



    <?php if($data->user_type === 'customer'): ?>
        <!-- Modal: Create Customer Accounting Invoice -->
        <div class="modal fade" id="createCustomerInvoiceModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header py-3">
                        <h5 class="modal-title fw-bold text-primary">
                            <i class="ti ti-file-invoice me-2"></i> <?php echo e(__('Create Accounting Invoice')); ?>

                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo e(__('Close')); ?>"></button>
                    </div>
                    <form id="formCreateCustomerInvoice" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="wallet_id" value="<?php echo e($data->id); ?>">

                        <div class="modal-body py-3">
                            <!-- Invoice Meta Fields -->
                            <div class="card mb-4 border">
                                <div class="card-header bg-white border-bottom py-2">
                                    <h6 class="mb-0 fw-semibold text-secondary">
                                        <i class="ti ti-info-circle me-1"></i> <?php echo e(__('Invoice Details')); ?>

                                    </h6>
                                </div>
                                <div class="card-body pt-3 pb-2">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-bold"><?php echo e(__('Invoice Number Serial')); ?></label>
                                            <input type="text" class="form-control bg-white" value="<?php echo e(__('Auto-generated (INV-YYYY-XXXX)')); ?>" readonly>
                                            <small class="text-muted"><?php echo e(__('Generated automatically upon saving')); ?></small>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="accounting_reference_no"><?php echo e(__('Accounting Reference Number')); ?></label>
                                            <input type="text" class="form-control" id="accounting_reference_no" name="accounting_reference_no" placeholder="مثال: REF-10928">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="issue_date"><?php echo e(__('Issue Date')); ?></label>
                                            <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?php echo e(date('Y-m-d')); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label text-danger fw-bold" for="due_date">* <?php echo e(__('Due Date (Maturity)')); ?></label>
                                            <input type="date" class="form-control border-primary" id="due_date" name="due_date" required min="<?php echo e(date('Y-m-d')); ?>">
                                            <small class="text-primary d-block mt-1"><?php echo e(__('Will update maturity on all linked transactions')); ?></small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="attachment"><?php echo e(__('Invoice Attachment File (PDF or Image)')); ?></label>
                                            <input type="file" class="form-control" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                            <small class="text-muted"><?php echo e(__('Supported: PDF, JPG, PNG, WEBP (Max: 10MB)')); ?></small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="notes"><?php echo e(__('Notes / Remarks')); ?></label>
                                            <textarea class="form-control" id="notes" name="notes" rows="1" placeholder="<?php echo e(__('Optional notes on this invoice')); ?>"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Transactions Selection Box -->
                            <div class="card border">
                                <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h6 class="mb-0 fw-semibold text-secondary">
                                        <i class="ti ti-list-check me-1"></i> <?php echo e(__('Select Uninvoiced Debit Transactions')); ?>

                                    </h6>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <!-- Dedicated Task Number Search -->
                                        <div class="input-group input-group-sm" style="min-width: 290px; max-width: 380px;">
                                            <span class="input-group-text bg-white text-primary border-primary">
                                                <i class="ti ti-hash me-1"></i> <?php echo e(__('Task #')); ?>

                                            </span>
                                            <input type="text" class="form-control border-primary" id="uninvoiced-task-search" placeholder="<?php echo e(__('Search task numbers (e.g. 101, 102)...')); ?>" title="<?php echo e(__('Search by one or multiple task numbers separated by comma or space')); ?>">
                                            <button class="btn btn-primary" type="button" id="btnSelectFilteredTasks" title="<?php echo e(__('Select all matching tasks')); ?>">
                                                <i class="ti ti-checkbox me-1"></i> <?php echo e(__('Select Matching')); ?>

                                            </button>
                                            <button class="btn btn-outline-secondary" type="button" id="btnClearTaskSearch" title="<?php echo e(__('Clear')); ?>">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        </div>

                                        <!-- General search for description / sequence -->
                                        <input type="text" class="form-control form-control-sm" id="uninvoiced-search" placeholder="<?php echo e(__('General search...')); ?>" style="width: 160px;">

                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnReloadUninvoiced" title="<?php echo e(__('Refresh')); ?>">
                                            <i class="ti ti-refresh"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 340px; overflow-y: auto;">
                                        <table class="table table-hover table-sm align-middle mb-0" id="uninvoicedTransactionsTable">
                                            <thead class="bg-white border-bottom sticky-top">
                                                <tr>
                                                    <th style="width: 40px; background-color: #fff;" class="text-center bg-white">
                                                        <input type="checkbox" class="form-check-input" id="checkAllUninvoiced" title="<?php echo e(__('Select all visible')); ?>">
                                                    </th>
                                                    <th style="width: 130px; background-color: #fff;" class="bg-white"><?php echo e(__('Sequence / ID')); ?></th>
                                                    <th style="width: 140px; background-color: #fff;" class="bg-white"><?php echo e(__('Task #')); ?></th>
                                                    <th style="background-color: #fff;" class="bg-white"><?php echo e(__('Description')); ?></th>
                                                    <th style="width: 130px; background-color: #fff;" class="bg-white"><?php echo e(__('Current Maturity')); ?></th>
                                                    <th style="width: 140px; background-color: #fff;" class="text-end bg-white"><?php echo e(__('Amount (SAR)')); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody id="uninvoiced-transactions-table-body">
                                                <tr>
                                                    <td colspan="6" class="text-center py-4 text-muted">
                                                        <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>
                                                        <?php echo e(__('Loading uninvoiced transactions...')); ?>

                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="p-3 bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div>
                                                <span class="fw-semibold text-muted"><?php echo e(__('Selected Transactions')); ?>:</span>
                                                <span class="badge bg-primary fs-6 px-2 py-1 ms-1" id="create-invoice-selected-count">0</span>
                                            </div>
                                            <div id="matching-tasks-count-badge" style="display: none;">
                                                <span class="badge bg-label-info">
                                                    <i class="ti ti-search me-1"></i>
                                                    <span id="matching-tasks-count-text"></span>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-muted"><?php echo e(__('Total Invoice Amount')); ?>:</span>
                                            <span class="badge bg-danger fs-5 px-3 py-1 ms-1" id="create-invoice-selected-total">0.00</span>
                                            <span class="fw-bold text-dark">ر.س</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                            <button type="submit" class="btn btn-primary" id="btnSubmitCreateInvoice">
                                <i class="ti ti-check me-1"></i> <?php echo e(__('Save & Create Invoice')); ?>

                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal: View Customer Invoice Details -->
        <div class="modal fade" id="viewCustomerInvoiceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header py-3">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="modal-title fw-bold text-primary mb-0" id="viewInvoiceTitle">
                                <i class="ti ti-file-invoice me-1"></i> <?php echo e(__('Invoice Details')); ?>

                            </h5>
                            <span id="viewInvoiceStatusBadge"></span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo e(__('Close')); ?>"></button>
                    </div>
                    <div class="modal-body py-3">
                        <!-- Invoice Info Grid -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-3 col-6">
                                <small class="text-muted d-block"><?php echo e(__('Invoice Number')); ?></small>
                                <span class="fw-bold fs-6 text-dark" id="viewInvoiceNumber">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <small class="text-muted d-block"><?php echo e(__('Accounting Ref')); ?></small>
                                <span class="fw-bold fs-6 text-dark" id="viewInvoiceRef">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <small class="text-muted d-block"><?php echo e(__('Issue Date')); ?></small>
                                <span class="fw-bold fs-6 text-dark" id="viewInvoiceIssueDate">-</span>
                            </div>
                            <div class="col-md-3 col-6">
                                <small class="text-muted d-block"><?php echo e(__('Due Date')); ?></small>
                                <span class="fw-bold fs-6" id="viewInvoiceDueDate">-</span>
                            </div>
                            <div class="col-md-4 col-4">
                                <div class="p-2 border rounded text-center">
                                    <small class="text-muted d-block"><?php echo e(__('Total Amount')); ?></small>
                                    <span class="fw-bold text-dark fs-5" id="viewInvoiceTotal">0.00</span> <small>ر.س</small>
                                </div>
                            </div>
                            <div class="col-md-4 col-4">
                                <div class="p-2 border rounded text-center">
                                    <small class="text-muted d-block"><?php echo e(__('Paid Amount')); ?></small>
                                    <span class="fw-bold text-success fs-5" id="viewInvoicePaid">0.00</span> <small>ر.س</small>
                                </div>
                            </div>
                            <div class="col-md-4 col-4">
                                <div class="p-2 border rounded text-center">
                                    <small class="text-muted d-block"><?php echo e(__('Remaining Amount')); ?></small>
                                    <span class="fw-bold text-danger fs-5" id="viewInvoiceRemaining">0.00</span> <small>ر.س</small>
                                </div>
                            </div>
                        </div>

                        <!-- Notes & Attachment -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-8" id="viewInvoiceNotesBox" style="display: none;">
                                <div class="p-2 border rounded">
                                    <small class="text-muted fw-bold d-block"><?php echo e(__('Notes')); ?>:</small>
                                    <span id="viewInvoiceNotes" class="text-secondary" style="white-space: pre-line;"></span>
                                </div>
                            </div>
                            <div class="col-md-4" id="viewInvoiceAttachmentBox" style="display: none;">
                                <div class="p-2 border rounded text-center">
                                    <small class="text-muted fw-bold d-block mb-1"><?php echo e(__('Attachment File')); ?>:</small>
                                    <button type="button" class="btn btn-xs btn-outline-primary btn-preview-attachment" id="viewInvoiceAttachmentBtn" data-url="" data-name="">
                                        <i class="ti ti-eye me-1"></i> <?php echo e(__('View Attachment')); ?>

                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <h6 class="fw-bold text-secondary mb-2">
                            <i class="ti ti-list me-1"></i> <?php echo e(__('Linked Debit Transactions')); ?>

                        </h6>
                        <div class="table-responsive border rounded" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead class="bg-white border-bottom sticky-top">
                                    <tr>
                                        <th style="background-color: #fff;">#</th>
                                        <th style="background-color: #fff;"><?php echo e(__('Sequence')); ?></th>
                                        <th style="background-color: #fff;"><?php echo e(__('Task #')); ?></th>
                                        <th style="background-color: #fff;"><?php echo e(__('Description')); ?></th>
                                        <th style="background-color: #fff;" class="text-end"><?php echo e(__('Amount (SAR)')); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="viewInvoiceItemsBody"></tbody>
                            </table>
                        </div>

                        <!-- Audit Info -->
                        <div class="d-flex justify-content-between mt-3 text-muted small">
                            <div><?php echo e(__('Created By')); ?>: <span id="viewInvoiceCreator">-</span> (<span id="viewInvoiceCreatedAt">-</span>)</div>
                            <div id="viewInvoiceApproverBox" style="display: none;"><?php echo e(__('Approved By')); ?>: <span id="viewInvoiceApprover">-</span></div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Pay Invoice -->
        <div class="modal fade" id="payCustomerInvoiceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header py-3">
                        <h5 class="modal-title fw-bold text-success">
                            <i class="ti ti-cash me-1"></i> <?php echo e(__('Register Invoice Payment')); ?>

                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo e(__('Close')); ?>"></button>
                    </div>
                    <form id="formPayCustomerInvoice">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" id="pay_invoice_id">

                        <div class="modal-body py-3">
                            <div class="p-3 rounded border mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><?php echo e(__('Invoice Number')); ?>:</span>
                                    <span class="fw-bold" id="payInvoiceNumber">-</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><?php echo e(__('Total Amount')); ?>:</span>
                                    <span class="fw-bold" id="payInvoiceTotal">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted"><?php echo e(__('Remaining Balance')); ?>:</span>
                                    <span class="fw-bold text-danger fs-6" id="payInvoiceRemaining">0.00</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-success" for="pay_amount">* <?php echo e(__('Payment Amount (SAR)')); ?></label>
                                <input type="number" step="0.01" min="0.01" class="form-control" id="pay_amount" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="pay_date"><?php echo e(__('Payment Date')); ?></label>
                                <input type="date" class="form-control" id="pay_date" name="payment_date" value="<?php echo e(date('Y-m-d')); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="pay_notes"><?php echo e(__('Payment Notes')); ?></label>
                                <textarea class="form-control" id="pay_notes" name="notes" rows="2" placeholder="<?php echo e(__('e.g. Bank transfer, receipt number...')); ?>"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                            <button type="submit" class="btn btn-success" id="btnSubmitPayment">
                                <i class="ti ti-check me-1"></i> <?php echo e(__('Confirm Payment')); ?>

                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal: Edit Invoice -->
        <div class="modal fade" id="editCustomerInvoiceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header py-3">
                        <h5 class="modal-title fw-bold text-primary">
                            <i class="ti ti-edit me-1"></i> <?php echo e(__('Edit Invoice Details')); ?> - <span id="editInvoiceNumberTitle"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo e(__('Close')); ?>"></button>
                    </div>
                    <form id="formEditCustomerInvoice" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" id="edit_invoice_id">

                        <div class="modal-body py-3">
                            <div class="alert alert-info py-2 mb-3">
                                <i class="ti ti-info-circle me-1"></i>
                                <?php echo e(__('Note: Updating the due date will automatically propagate to all linked wallet transactions.')); ?>

                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="edit_accounting_reference_no"><?php echo e(__('Accounting Reference Number')); ?></label>
                                    <input type="text" class="form-control" id="edit_accounting_reference_no" name="accounting_reference_no">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-danger fw-bold" for="edit_due_date">* <?php echo e(__('Due Date (Maturity)')); ?></label>
                                    <input type="date" class="form-control border-primary" id="edit_due_date" name="due_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="edit_issue_date"><?php echo e(__('Issue Date')); ?></label>
                                    <input type="date" class="form-control" id="edit_issue_date" name="issue_date">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="edit_attachment"><?php echo e(__('Replace Invoice Attachment File')); ?></label>
                                    <input type="file" class="form-control" id="edit_attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                    <div id="editCurrentAttachmentPreview" class="mt-1 small"></div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label" for="edit_notes"><?php echo e(__('Notes')); ?></label>
                                    <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                                </div>
                            </div>

                            <!-- Transactions Linking & Management Box -->
                            <div class="card border mb-2">
                                <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h6 class="mb-0 fw-semibold text-secondary">
                                        <i class="ti ti-list-check me-1"></i> <?php echo e(__('Linked Debit Transactions')); ?>

                                    </h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleAddMoreTransactions">
                                        <i class="ti ti-plus me-1"></i> <?php echo e(__('Add More Uninvoiced Transactions')); ?>

                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                        <table class="table table-hover table-sm align-middle mb-0">
                                            <thead class="bg-white border-bottom sticky-top">
                                                <tr>
                                                    <th style="width: 40px; background-color: #fff;" class="text-center bg-white">#</th>
                                                    <th style="width: 120px; background-color: #fff;" class="bg-white"><?php echo e(__('Sequence / ID')); ?></th>
                                                    <th style="width: 130px; background-color: #fff;" class="bg-white"><?php echo e(__('Task #')); ?></th>
                                                    <th style="background-color: #fff;" class="bg-white"><?php echo e(__('Description')); ?></th>
                                                    <th style="width: 130px; background-color: #fff;" class="text-end bg-white"><?php echo e(__('Amount (SAR)')); ?></th>
                                                    <th style="width: 140px; background-color: #fff;" class="text-center bg-white"><?php echo e(__('Actions')); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody id="editInvoiceTransactionsBody">
                                                <!-- Dynamic via JS -->
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Expandable section for available uninvoiced transactions -->
                                    <div id="editAvailableUninvoicedSection" class="border-top p-2" style="display: none; background-color: #fff;">
                                        <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                            <small class="fw-bold text-primary">
                                                <i class="ti ti-plus me-1"></i> <?php echo e(__('Select Transactions to Add to Invoice')); ?>

                                            </small>
                                            <input type="text" class="form-control form-control-sm" id="edit-available-task-search" placeholder="<?php echo e(__('Search Task #...')); ?>" style="max-width: 220px;">
                                        </div>
                                        <div class="table-responsive border rounded" style="max-height: 180px; overflow-y: auto;">
                                            <table class="table table-sm table-hover align-middle mb-0">
                                                <thead class="bg-white border-bottom sticky-top">
                                                    <tr>
                                                        <th style="width: 40px; background-color: #fff;" class="text-center bg-white">
                                                            <input type="checkbox" class="form-check-input" id="checkAllAvailableForEdit">
                                                        </th>
                                                        <th style="background-color: #fff;" class="bg-white"><?php echo e(__('Sequence')); ?></th>
                                                        <th style="background-color: #fff;" class="bg-white"><?php echo e(__('Task #')); ?></th>
                                                        <th style="background-color: #fff;" class="bg-white"><?php echo e(__('Description')); ?></th>
                                                        <th style="background-color: #fff;" class="text-end bg-white"><?php echo e(__('Amount (SAR)')); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="editAvailableUninvoicedBody">
                                                    <!-- Dynamic via JS -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Summary / Live Calculation Bar -->
                                    <div class="p-3 bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div>
                                            <span class="fw-semibold text-muted"><?php echo e(__('Linked Transactions')); ?>:</span>
                                            <span class="badge bg-primary fs-6 px-2 py-1 ms-1" id="edit-invoice-selected-count">0</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3">
                                            <div>
                                                <span class="fw-semibold text-muted"><?php echo e(__('New Total')); ?>:</span>
                                                <span class="badge bg-primary fs-6 px-2 py-1 ms-1" id="edit-invoice-new-total">0.00</span>
                                                <small>ر.س</small>
                                            </div>
                                            <div>
                                                <span class="fw-semibold text-muted"><?php echo e(__('Paid')); ?>:</span>
                                                <span class="badge bg-success fs-6 px-2 py-1 ms-1" id="edit-invoice-paid">0.00</span>
                                                <small>ر.س</small>
                                            </div>
                                            <div>
                                                <span class="fw-semibold text-muted"><?php echo e(__('Remaining')); ?>:</span>
                                                <span class="badge bg-danger fs-6 px-2 py-1 ms-1" id="edit-invoice-new-remaining">0.00</span>
                                                <small>ر.س</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                            <button type="submit" class="btn btn-primary" id="btnSubmitEditInvoice">
                                <i class="ti ti-check me-1"></i> <?php echo e(__('Save Changes')); ?>

                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal: Preview Invoice Attachment -->
        <div class="modal fade" id="previewAttachmentModal" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header py-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="modal-title fw-bold text-primary mb-0">
                                <i class="ti ti-file-search me-1"></i> <span id="previewAttachmentTitle"><?php echo e(__('Attachment Preview')); ?></span>
                            </h5>
                        </div>
                        <div class="d-flex align-items-center gap-2 ms-auto me-2">
                            <a href="#" download id="previewAttachmentDownloadBtn" class="btn btn-sm btn-outline-primary" title="<?php echo e(__('Download File')); ?>">
                                <i class="ti ti-download me-1"></i> <?php echo e(__('Download')); ?>

                            </a>
                            <a href="#" target="_blank" id="previewAttachmentExternalBtn" class="btn btn-sm btn-outline-secondary" title="<?php echo e(__('Open in New Tab')); ?>">
                                <i class="ti ti-external-link"></i>
                            </a>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo e(__('Close')); ?>"></button>
                    </div>
                    <div class="modal-body p-2" id="previewAttachmentBody" style="min-height: 420px; display: flex; align-items: center; justify-content: center; background-color: #fff;">
                        <!-- Dynamic: Image / PDF Iframe / Download Notice -->
                    </div>
                    <div class="modal-footer py-2 border-top">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/wallets/show.blade.php ENDPATH**/ ?>