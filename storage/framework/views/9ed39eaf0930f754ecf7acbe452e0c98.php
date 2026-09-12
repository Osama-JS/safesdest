
<div class="modal fade" id="b2bTaskModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="b2bTaskModalTitle">
                    <i class="ti ti-building me-2"></i>
                    <?php echo e(__('إنشاء مهمة شركة B2B')); ?>

                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <form id="b2b-task-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" id="b2b-task-id" name="task_id" value="">
                    <input type="hidden" id="b2b-form-method" value="POST">

                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="ti ti-building me-1 text-primary"></i>
                            * <?php echo e(__('الشركة')); ?>

                        </label>
                        <select name="company_id" id="b2b-company-id" class="form-select b2b-select2">
                            <option value=""><?php echo e(__('اختر الشركة...')); ?></option>
                            <?php $__currentLoopData = $companies ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $company): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($company->id); ?>"><?php echo e($company->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="b2b-error text-danger small" data-field="company_id"></span>
                    </div>

                    <div class="row">
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-warehouse me-1 text-warning"></i>
                                * <?php echo e(__('المستودع (نقطة الاستلام)')); ?>

                            </label>
                            <select name="warehouse_id" id="b2b-warehouse-id" class="form-select" disabled>
                                <option value=""><?php echo e(__('اختر المستودع...')); ?></option>
                            </select>
                            <span class="b2b-error text-danger small" data-field="warehouse_id"></span>
                            
                            <div id="b2b-warehouse-preview" class="mt-2 p-2 bg-light rounded d-none small text-muted">
                                <i class="ti ti-map-pin me-1"></i><span id="b2b-warehouse-address"></span>
                            </div>
                        </div>

                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-user me-1 text-success"></i>
                                * <?php echo e(__('العميل النهائي (نقطة التسليم)')); ?>

                            </label>
                            <select name="end_client_id" id="b2b-end-client-id" class="form-select b2b-ajax-select2" disabled>
                                <option value=""><?php echo e(__('ابحث عن العميل...')); ?></option>
                            </select>
                            <span class="b2b-error text-danger small" data-field="end_client_id"></span>
                            
                            <div id="b2b-client-preview" class="mt-2 p-2 bg-light rounded d-none small text-muted">
                                <i class="ti ti-map-pin me-1"></i><span id="b2b-client-city"></span>
                                <span id="b2b-client-code" class="badge bg-label-secondary ms-1"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-truck me-1 text-info"></i>
                                * <?php echo e(__('المركبة')); ?>

                            </label>
                            <select id="b2b-vehicle-id" class="form-select b2b-select2" disabled>
                                <option value=""><?php echo e(__('اختر المركبة...')); ?></option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-settings me-1 text-info"></i>
                                * <?php echo e(__('نوع المركبة')); ?>

                            </label>
                            <select id="b2b-vehicle-type-id" class="form-select" disabled>
                                <option value=""><?php echo e(__('اختر النوع...')); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-arrows-maximize me-1 text-info"></i>
                                * <?php echo e(__('الحجم النهائي')); ?>

                            </label>
                            <select name="vehicle_size_id" id="b2b-vehicle-size-id" class="form-select" disabled>
                                <option value=""><?php echo e(__('اختر الحجم...')); ?></option>
                            </select>
                            <span class="b2b-error text-danger small" data-field="vehicle_size_id"></span>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-numbers me-1 text-primary"></i>
                                * <?php echo e(__('عدد المركبات')); ?>

                            </label>
                            <input type="number" name="quantity" id="b2b-quantity" class="form-control" value="1" min="1">
                            <span class="b2b-error text-danger small" data-field="quantity"></span>
                        </div>

                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-calendar me-1 text-secondary"></i>
                                <?php echo e(__('التسليم قبل')); ?>

                            </label>
                            <input type="datetime-local"
                                   name="delivery_before"
                                   id="b2b-delivery-before"
                                   class="form-control"
                                   value="<?php echo e(now()->addHours(3)->format('Y-m-d\TH:i')); ?>">
                        </div>
                    </div>

                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="ti ti-note me-1 text-secondary"></i>
                            <?php echo e(__('ملاحظات')); ?>

                        </label>
                        <textarea name="conditions" id="b2b-conditions" class="form-control" rows="2"
                                  placeholder="<?php echo e(__('أي شروط أو ملاحظات خاصة بهذه المهمة...')); ?>"></textarea>
                    </div>

                    
                    <div class="card bg-label-secondary border-0 mb-3">
                        <div class="card-body p-3">
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="b2b-select-template">
                                    <i class="ti ti-layout-grid me-1 text-primary"></i>
                                    <?php echo e(__('اختر القالب لبيانات إضافية')); ?>

                                </label>
                                <select name="template" id="b2b-select-template" class="form-select b2b-select2">
                                    <option value=""><?php echo e(__('--- بدون قالب ---')); ?></option>
                                    <?php $__currentLoopData = $templates ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($key->id); ?>"><?php echo e($key->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>

                            <div id="b2b-additional-form" class="row">
                                
                            </div>
                        </div>
                    </div>


                    
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" id="b2b-calc-price-btn" class="btn btn-outline-primary" disabled>
                            <i class="ti ti-calculator me-1"></i>
                            <?php echo e(__('احسب السعر')); ?>

                        </button>
                    </div>

                    
                    <div id="b2b-pricing-result" class="d-none">
                        <div class="alert alert-success border-0 p-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="small text-muted"><?php echo e(__('السعر الأساسي')); ?></div>
                                    <div class="fw-bold fs-6" id="b2b-base-price">—</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted"><?php echo e(__('الضريبة')); ?></div>
                                    <div class="fw-bold fs-6" id="b2b-vat-amount">—</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted fw-semibold"><?php echo e(__('الإجمالي')); ?></div>
                                    <div class="fw-bold fs-5 text-success" id="b2b-total-price">—</div>
                                </div>
                            </div>
                            <div class="text-center mt-2">
                                <span class="badge bg-label-primary" id="b2b-pricing-rule-badge"></span>
                            </div>
                        </div>
                    </div>

                    
                    <div id="b2b-pricing-error" class="alert alert-danger d-none">
                        <i class="ti ti-alert-circle me-1"></i>
                        <span id="b2b-pricing-error-msg"></span>
                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    <?php echo e(__('إلغاء')); ?>

                </button>
                <button type="button" id="b2b-submit-btn" class="btn btn-primary" disabled>
                    <i class="ti ti-check me-1"></i>
                    <span id="b2b-submit-label"><?php echo e(__('إنشاء المهمة')); ?></span>
                </button>
            </div>

        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/tasks/b2b-task-modal.blade.php ENDPATH**/ ?>