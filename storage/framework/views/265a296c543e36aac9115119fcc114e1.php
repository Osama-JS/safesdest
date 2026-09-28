<!-- In-App Notification Modal Alert -->
<div class="modal fade" id="inAppNotificationModal" tabindex="-1" aria-labelledby="inAppNotificationModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-bottom py-3 bg-label-primary">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-2">
                        <span class="avatar-initial rounded-circle bg-primary text-white">
                            <i class="ti ti-bell-ringing"></i>
                        </span>
                    </div>
                    <h5 class="modal-title fw-bold text-primary mb-0" id="inAppNotificationModalTitle"><?php echo e(__('تنبيه جديد')); ?></h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3" id="inAppModalNotificationsList">
                <!-- Items injected dynamically via notifications.js -->
            </div>
            <div class="modal-footer border-top py-2 d-flex justify-content-between">
                <small class="text-muted"><i class="ti ti-clock me-1"></i><?php echo e(__('إشعار لحظي من النظام')); ?></small>
                <button type="button" class="btn btn-sm btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('إغلاق')); ?></button>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/layouts/sections/notifications/modal.blade.php ENDPATH**/ ?>