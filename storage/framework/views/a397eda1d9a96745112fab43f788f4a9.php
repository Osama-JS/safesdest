<?php $__env->startSection('title', __('Withdrawal Requests')); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/block-ui/block-ui.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <script>
        const withdrawalDataUrl = "<?php echo e(route('wallets.withdrawals.data')); ?>";
        const processWithdrawalUrl = "<?php echo e(route('wallets.withdrawals.process', ':id')); ?>";
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/admin/withdrawals/withdrawals.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span><?php echo e(__('Pending Requests')); ?></span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2"><?php echo e($stats['pending_amount']); ?> <?php echo e(__('SAR')); ?></h4>
                                <span class="text-warning">(<?php echo e($stats['pending_count']); ?>)</span>
                            </div>
                            <small class="mb-0"><?php echo e(__('Waiting for approval')); ?></small>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                            <i class="ti ti-clock ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span><?php echo e(__('Processing')); ?></span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2"><?php echo e($stats['processing_amount']); ?> <?php echo e(__('SAR')); ?></h4>
                                <span class="text-info">(<?php echo e($stats['processing_count']); ?>)</span>
                            </div>
                            <small class="mb-0"><?php echo e(__('Initiated (HyperPay)')); ?></small>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="ti ti-refresh ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span><?php echo e(__('Approved Requests')); ?></span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2"><?php echo e($stats['approved_amount']); ?> <?php echo e(__('SAR')); ?></h4>
                                <span class="text-success">(<?php echo e($stats['approved_count']); ?>)</span>
                            </div>
                            <small class="mb-0"><?php echo e(__('Successfully processed')); ?></small>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="ti ti-check ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span><?php echo e(__('Rejected Requests')); ?></span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2"><?php echo e($stats['rejected_count']); ?></h4>
                            </div>
                            <small class="mb-0"><?php echo e(__('Total rejected')); ?></small>
                        </div>
                        <span class="badge bg-label-danger rounded p-2">
                            <i class="ti ti-x ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label"><?php echo e(__('Filter by Driver')); ?></label>
                    <select id="filter_driver" class="select2 form-select" data-allow-clear="true">
                        <option value=""><?php echo e(__('All Drivers')); ?></option>
                        <?php $__currentLoopData = $drivers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $driver): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($driver->id); ?>"><?php echo e($driver->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo e(__('Filter by Status')); ?></label>
                    <select id="filter_status" class="select2 form-select" data-allow-clear="true">
                        <option value=""><?php echo e(__('All Statuses')); ?></option>
                        <option value="pending"><?php echo e(__('Pending')); ?></option>
                        <option value="processing"><?php echo e(__('Processing')); ?></option>
                        <option value="completed"><?php echo e(__('Approved')); ?></option>
                        <option value="rejected"><?php echo e(__('Rejected')); ?></option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">
                <i class="tf-icons ti ti-cash-banknote me-2 fs-3 text-white bg-warning rounded p-1"></i>
                <?php echo e(__('Withdrawal Requests')); ?>

            </h5>
            <p class="text-muted mb-0"><?php echo e(__('Manage driver cash withdrawal requests')); ?></p>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-withdrawals table font-small">
                <thead class="border-top">
                    <tr>
                        <th>#</th>
                        <th><?php echo e(__('Driver')); ?></th>
                        <th><?php echo e(__('Requested')); ?></th>
                        <th><?php echo e(__('Approved')); ?></th>
                        <th><?php echo e(__('Status')); ?></th>
                        <th><?php echo e(__('Method')); ?></th>
                        <th><?php echo e(__('Date')); ?></th>
                        <th><?php echo e(__('Action By')); ?></th>
                        <th><?php echo e(__('Actions')); ?></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Process Withdrawal Modal -->
    <div class="modal fade" id="processWithdrawalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo e(__('Process Withdrawal Request')); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="processWithdrawalForm" enctype="multipart/form-data">
                    <input type="hidden" id="withdrawal_id" name="id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label"><?php echo e(__('Action')); ?></label>
                                <select name="action" id="withdrawal_action" class="form-select" required>
                                    <option value="approve"><?php echo e(__('Approve')); ?></option>
                                    <option value="reject"><?php echo e(__('Reject')); ?></option>
                                </select>
                            </div>

                            <div class="col-12 approve-fields">
                                <label class="form-label"><?php echo e(__('Amount to Pay')); ?></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="form-control" required>
                                    <span class="input-group-text"><?php echo e(__('SAR')); ?></span>
                                </div>
                                <small class="text-muted"><?php echo e(__('Requested:')); ?> <span id="requested_amount_display">0</span></small>
                            </div>

                            <div class="col-12 approve-fields">
                                <div class="bg-label-info p-3 rounded mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 fw-bold"><?php echo e(__('Driver Financial Info')); ?></h6>
                                        <span class="badge bg-primary"><?php echo e(__('Wallet Balance')); ?>: <span id="driver_wallet_balance">0</span> SAR</span>
                                    </div>
                                    <div id="driver_bank_info" class="small" style="display: none;">
                                        <div class="row g-2">
                                            <div class="col-6 mb-1"><strong><?php echo e(__('Beneficiary')); ?>:</strong> <span id="info_beneficiary"></span></div>
                                            <div class="col-6 mb-1"><strong><?php echo e(__('BIC/Swift')); ?>:</strong> <span id="info_bic"></span></div>
                                            <div class="col-12 mb-1"><strong><?php echo e(__('IBAN')); ?>:</strong> <span id="info_iban" class="text-break text-primary fw-bold"></span></div>
                                            <hr class="my-1">
                                            <div class="col-12 mb-1"><strong><?php echo e(__('Address')); ?>:</strong> <span id="info_address"></span></div>
                                            <div class="col-6"><strong><?php echo e(__('City')); ?>:</strong> <span id="info_city"></span></div>
                                            <div class="col-6"><strong><?php echo e(__('Country')); ?>:</strong> <span id="info_country"></span></div>
                                        </div>
                                        <div id="driver_bank_warning" class="text-danger mt-2 fw-bold small" style="display: none;">
                                            <i class="ti ti-alert-triangle me-1"></i><?php echo e(__('Incomplete bank details! Payout may fail.')); ?>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 approve-fields">
                                <label class="form-label"><?php echo e(__('Payment Method')); ?></label>
                                <select name="payment_method" id="payment_method" class="form-select select2" onchange="toggleWithdrawalPaymentMethod(this.value)">
                                    <option value="bank_transfer"><?php echo e(__('Bank Transfer')); ?></option>
                                    <option value="cash"><?php echo e(__('Cash Handover')); ?></option>
                                    <option value="hyperpay"><?php echo e(__('HyperPay HyperSplits (Auto)')); ?></option>
                                </select>
                            </div>

                            <!-- Beneficiary Name in English for HyperPay -->
                            <div class="col-12 approve-fields hyperpay-fields" id="hyperpay_beneficiary_container" style="display: none;">
                                <label class="form-label fw-bold text-dark d-flex justify-content-between" for="beneficiary_name_input">
                                    <span><i class="ti ti-user me-1 text-primary"></i><?php echo e(__('اسم المستفيد بالإنجليزية (Beneficiary Name)')); ?> <span class="text-danger">*</span></span>
                                    <small class="text-muted"><?php echo e(__('مطلوب بحروف إنجليزية')); ?></small>
                                </label>
                                <input type="text" name="beneficiary_name" id="beneficiary_name_input" class="form-control"
                                    placeholder="e.g. Amal Salman Al Faifi">
                                <small class="text-muted d-block mt-1"><?php echo e(__('يجب أن يكون بالإنجليزية كما هو مسجل لدى البنك (تم تحويله تلقائياً ويمكنك تعديله)')); ?></small>
                            </div>

                            <!-- Admin Password for HyperPay -->
                            <div class="col-12 approve-fields hyperpay-fields" id="hyperpay_password_container" style="display: none;">
                                <label class="form-label text-danger fw-bold" for="hyperpay_password">
                                    <i class="ti ti-lock me-1"></i><?php echo e(__('كلمة مرور المشرف (مطلوبة لتأكيد التحويل)')); ?> <span class="text-danger">*</span>
                                </label>
                                <input type="password" name="password" id="hyperpay_password" class="form-control border-danger" placeholder="<?php echo e(__('أدخل كلمة المرور الخاصة بك لتأكيد عملية الدفع')); ?>">
                                <small class="text-muted d-block mt-1"><?php echo e(__('مطلوبة للتحقق من هوية المشرف قبل إرسال أمر الصرف')); ?></small>
                            </div>

                            <div class="col-12 approve-fields" id="receipt_field_container">
                                <label class="form-label"><?php echo e(__('Receipt Image')); ?></label>
                                <input type="file" name="receipt" class="form-control" accept="image/*,application/pdf">
                            </div>

                            <div class="col-12">
                                <label class="form-label"><?php echo e(__('Notes')); ?></label>
                                <textarea name="admin_notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                        <button type="submit" class="btn btn-primary" id="submitProcessBtn"><?php echo e(__('Process Request')); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Withdrawal Details Modal -->
    <div class="modal fade" id="viewWithdrawalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-label-primary">
                    <h5 class="modal-title"><?php echo e(__('Withdrawal Request Details')); ?> #<span id="view_request_id"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Driver Info -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold"><?php echo e(__('Driver Information')); ?></h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="ps-0" width="40%"><?php echo e(__('Name')); ?>:</td>
                                    <td id="view_driver_name" class="fw-bold"></td>
                                </tr>
                                <tr>
                                    <td class="ps-0"><?php echo e(__('Wallet ID')); ?>:</td>
                                    <td id="view_wallet_id"></td>
                                </tr>
                            </table>
                        </div>
                        <!-- Request Info -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold"><?php echo e(__('Request Information')); ?></h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="ps-0" width="40%"><?php echo e(__('Amount')); ?>:</td>
                                    <td id="view_amount_requested" class="fw-bold text-primary"></td>
                                </tr>
                                <tr>
                                    <td class="ps-0"><?php echo e(__('Created At')); ?>:</td>
                                    <td id="view_created_at"></td>
                                </tr>
                                <tr>
                                    <td class="ps-0"><?php echo e(__('Status')); ?>:</td>
                                    <td id="view_status"></td>
                                </tr>
                            </table>
                        </div>

                        <hr class="my-0">

                        <!-- Processing Info -->
                        <div class="col-12" id="processing_details_section">
                            <h6 class="fw-semibold mt-3"><?php echo e(__('Processing Details')); ?></h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="ps-0" width="40%"><?php echo e(__('Action By')); ?>:</td>
                                            <td id="view_processed_by"></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0"><?php echo e(__('Processed At')); ?>:</td>
                                            <td id="view_processed_at"></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="ps-0" width="40%"><?php echo e(__('Approved Amount')); ?>:</td>
                                            <td id="view_amount_paid" class="fw-bold text-success"></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0"><?php echo e(__('Payment Method')); ?>:</td>
                                            <td id="view_payment_method"></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-12">
                                    <label class="fw-semibold"><?php echo e(__('Admin Notes')); ?>:</label>
                                    <p id="view_admin_notes" class="text-muted fst-italic p-2 bg-label-secondary rounded mb-0"></p>
                                </div>
                                <div class="col-12" id="view_receipt_container">
                                    <label class="fw-semibold"><?php echo e(__('Receipt Image')); ?>:</label>
                                    <div class="mt-2">
                                        <a href="#" target="_blank" id="view_receipt_link">
                                            <img src="" id="view_receipt_img" class="img-fluid rounded border p-1" style="max-height: 200px;" alt="Receipt">
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleWithdrawalPaymentMethod(val) {
            var isHyperPay = (val === 'hyperpay');
            var receiptContainer = document.getElementById('receipt_field_container');
            var bankInfo = document.getElementById('driver_bank_info');
            var beneficiaryContainer = document.getElementById('hyperpay_beneficiary_container');
            var passwordContainer = document.getElementById('hyperpay_password_container');
            var passwordInput = document.getElementById('hyperpay_password');

            if (receiptContainer) receiptContainer.style.display = isHyperPay ? 'none' : 'block';
            if (bankInfo) bankInfo.style.display = isHyperPay ? 'block' : 'none';
            if (beneficiaryContainer) beneficiaryContainer.style.display = isHyperPay ? 'block' : 'none';
            if (passwordContainer) passwordContainer.style.display = isHyperPay ? 'block' : 'none';
            if (passwordInput) {
                passwordInput.required = isHyperPay;
                if (!isHyperPay) passwordInput.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof $ !== 'undefined') {
                $(document).on('change select2:select', '#payment_method', function () {
                    toggleWithdrawalPaymentMethod($(this).val());
                });
            }
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/wallets/withdrawals/index.blade.php ENDPATH**/ ?>