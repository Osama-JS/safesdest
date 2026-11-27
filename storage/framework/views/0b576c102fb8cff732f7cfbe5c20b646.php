<!-- Manual Commission Modal -->
<div class="modal fade" id="manualCommissionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo e(__('Manual Task Commission Calculation')); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="search_task_id" class="form-label"><?php echo e(__('Search by Task ID')); ?></label>
                        <div class="input-group">
                            <input type="number" id="search_task_id" class="form-control" placeholder="e.g. 1234">
                            <button class="btn btn-outline-primary" type="button" id="btnSearchTask">
                                <i class="ti ti-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="taskSearchResult" class="d-none mt-3 p-3 border rounded bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0"><?php echo e(__('Task Details')); ?></h6>
                        <span id="taskStatusBadge" class="badge"></span>
                    </div>
                    <ul class="list-unstyled mb-0">
                        <li><strong><?php echo e(__('Customer')); ?>:</strong> <span id="resCustomerName"></span></li>
                        <li><strong><?php echo e(__('Total Price')); ?>:</strong> <span id="resTotalPrice"></span> ريال</li>
                        <li><strong><?php echo e(__('Platform Commission')); ?>:</strong> <span id="resPlatformCut"></span> ريال</li>
                    </ul>
                    <hr>
                    <div id="taskEligibilityMsg" class="alert mb-0"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
                <button type="button" class="btn btn-primary d-none" id="btnConfirmManualCalc"><?php echo e(__('Calculate Commission')); ?></button>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/user-wallets/manual-commission-modal.blade.php ENDPATH**/ ?>