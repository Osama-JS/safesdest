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


        </div>

        <!-- Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 datatables-users">
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
                                                        value="credit" autocomplete="off" required checked>
                                                    <label class="btn btn-outline-success w-100 py-2 btn-credit"
                                                        for="credit">
                                                        <i class="ti ti-circle-plus me-1"></i> <?php echo e(__('Credit')); ?>

                                                    </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="radio" class="btn-check" name="type" id="debit"
                                                        value="debit" autocomplete="off" required>
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
                                            <label class="form-label" for="payment_method"><?php echo e(__('Payment Method')); ?></label>
                                            <select name="payment_method" class="form-select" id="trans_payment_method">
                                                <option value="manual" selected><?php echo e(__('Manual (Cash / Bank Transfer)')); ?></option>
                                                <?php if($data->user_type === 'driver'): ?>
                                                    <option value="hyperpay"><?php echo e(__('HyperPay Payout')); ?></option>
                                                <?php endif; ?>
                                            </select>
                                            <span class="payment_method-error text-danger text-error"></span>
                                        </div>

                                        <!-- Bank Details (Shown only for HyperPay) -->
                                        <?php if($data->user_type === 'driver'): ?>
                                            <div id="manual-hyperpay-bank-details" class="alert alert-info mt-3"
                                                style="display: none;">
                                                <h6 class="alert-heading fw-bold mb-2"><i
                                                        class="ti ti-building-bank me-1"></i><?php echo e(__('Driver Bank Details')); ?>

                                                </h6>
                                                <div class="row small">
                                                    <div class="col-md-6 mb-1"><strong><?php echo e(__('Beneficiary')); ?>:</strong>
                                                        <span><?php echo e($data->driver->beneficiary_name ?? 'N/A'); ?></span>
                                                    </div>
                                                    <div class="col-md-6 mb-1"><strong><?php echo e(__('Bank')); ?>:</strong>
                                                        <span><?php echo e($data->driver->bank_name ?? 'N/A'); ?></span>
                                                    </div>
                                                    <div class="col-md-12 mb-1"><strong><?php echo e(__('IBAN')); ?>:</strong> <span
                                                            class="font-monospace"><?php echo e($data->driver->iban_number ?? 'N/A'); ?></span>
                                                    </div>
                                                    <div class="col-md-6"><strong><?php echo e(__('BIC/SWIFT')); ?>:</strong>
                                                        <span><?php echo e($data->driver->bic_code ?? 'N/A'); ?></span>
                                                    </div>
                                                </div>
                                                <?php if(!$data->driver->iban_number || !$data->driver->bic_code || !$data->driver->beneficiary_name): ?>
                                                    <div class="text-danger mt-2 fw-bold">
                                                        <i class="ti ti-alert-triangle me-1"></i><?php echo e(__('Incomplete bank details! Payout may fail.')); ?>

                                                    </div>
                                                <?php endif; ?>

                                                <div class="mt-3">
                                                    <label class="form-label text-danger fw-bold" for="hyperpay_password">
                                                        <i class="ti ti-lock me-1"></i>كلمة مرور المشرف (مطلوبة لتأكيد التحويل)
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



<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/wallets/show.blade.php ENDPATH**/ ?>