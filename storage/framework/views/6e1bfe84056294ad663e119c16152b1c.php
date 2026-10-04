<?php $__env->startSection('title', __('General Settings')); ?>

<!-- Vendor Styles -->
<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>
<?php $__env->stopSection(); ?>

<!-- Vendor Scripts -->
<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js']); ?>
<?php $__env->stopSection(); ?>

<!-- Page Scripts -->
<?php $__env->startSection('page-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/ajax.js']); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/model.js']); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/admin/settings.js']); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item">
                <a href="<?php echo e(url('admin')); ?>"><i class="ti ti-home-2 me-1"></i><?php echo e(__('الرئيسية')); ?></a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);"><?php echo e(__('الإعدادات')); ?></a>
            </li>
            <li class="breadcrumb-item active"><?php echo e(__('الإعدادات العامة')); ?></li>
        </ol>
    </nav>

    <!-- Hero Header Banner -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.02) 100%);">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-xl bg-primary text-white rounded-3 shadow-sm d-flex align-items-center justify-content-center p-2">
                        <i class="ti ti-adjustments-alt fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <h4 class="fw-bold mb-0 text-heading"><?php echo e(__('الإعدادات العامة للمنصة')); ?></h4>
                            <span class="badge bg-label-primary rounded-pill px-3 py-1 fs-tiny fw-semibold">
                                <i class="ti ti-sliders me-1"></i> <?php echo e(__('الضبط الأساسي للنظام')); ?>

                            </span>
                        </div>
                        <p class="text-muted mb-0">
                            <?php echo e(__('إدارة إعدادات المنصة الحيوية والقوالب الافتراضية، الربط مع خدمات الخرائط والدفع، والخيارات التشغيلية الرئيسية.')); ?>

                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong><?php echo e(__('Templates')); ?></strong>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-9">
                        <label for="customer-template" class="mb-2"><?php echo e(__('Default Customer Template')); ?></label>
                        <select class="form-select  update-setting-select" data-key="customer_template">
                            <?php if(empty($settings['customer_template']['value']) || empty($templates)): ?>
                                <option value=""><?php echo e(__('--- Select Template')); ?></option>
                            <?php endif; ?>
                            <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($val->id); ?>"
                                    <?php echo e(($settings['customer_template']['value'] ?? null) == $val->id ? 'selected' : ''); ?>>
                                    <?php echo e($val->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if(!empty($settings['customer_template']['value'])): ?>
                                <option value=""><?php echo e(__('--- Select Template')); ?></option>
                            <?php endif; ?>
                        </select>
                        <span class="customer-error text-danger"></span>
                    </div>
                    <div class="form-group mb-9">
                        <label for="driver-template" class="mb-2"><?php echo e(__('Default Driver Template')); ?></label>
                        <select class="form-select  update-setting-select" data-key="driver_template" id="driver-template">
                            <?php if(empty($settings['driver_template']['value']) || empty($templates)): ?>
                                <option value=""><?php echo e(__('--- Select Template')); ?></option>
                            <?php endif; ?>
                            <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($val->id); ?>"
                                    <?php echo e(($settings['driver_template']['value'] ?? null) == $val->id ? 'selected' : ''); ?>>
                                    <?php echo e($val->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if(!empty($settings['customer_template']['value'])): ?>
                                <option value=""><?php echo e(__('--- Select Template')); ?></option>
                            <?php endif; ?>
                        </select>
                        <span class="driver-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="user-template" class="mb-2"><?php echo e(__('Default User Template')); ?></label>
                        <select class="form-select  update-setting-select" data-key="user_template" id="user-template">
                            <?php if(empty($settings['user_template']['value']) || empty($templates)): ?>
                                <option value=""><?php echo e(__('--- Select Template')); ?></option>
                            <?php endif; ?>
                            <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($val->id); ?>"
                                    <?php echo e(($settings['user_template']['value'] ?? null) == $val->id ? 'selected' : ''); ?>>
                                    <?php echo e($val->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if(!empty($settings['customer_template']['value'])): ?>
                                <option value=""><?php echo e(__('--- Select Template')); ?></option>
                            <?php endif; ?>
                        </select>
                        <span class="user-error text-danger"></span>
                    </div>
                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2"><?php echo e(__('Default Task Template')); ?></label>
                        <select class="form-select  update-setting-select" data-key="task_template" id="task-template">
                            <option value=""><?php echo e(__('--- Select Template')); ?></option>
                            <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($val->id); ?>"
                                    <?php echo e(($settings['task_template']['value'] ?? null) == $val->id ? 'selected' : ''); ?>>
                                    <?php echo e($val->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2"><?php echo e(__('Default Task (From Port) Template')); ?></label>
                        <select class="form-select  update-setting-select" data-key="task_from_port_template"
                            id="task-from-port-template">
                            <option value=""><?php echo e(__('--- Select Template')); ?></option>
                            <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($val->id); ?>"
                                    <?php echo e(($settings['task_from_port_template']['value'] ?? null) == $val->id ? 'selected' : ''); ?>>
                                    <?php echo e($val->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2"><?php echo e(__('Default Task (To Port) Template')); ?></label>
                        <select class="form-select  update-setting-select" data-key="task_to_port_template"
                            id="task-to-port-template">
                            <option value=""><?php echo e(__('--- Select Template')); ?></option>
                            <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($val->id); ?>"
                                    <?php echo e(($settings['task_to_port_template']['value'] ?? null) == $val->id ? 'selected' : ''); ?>>
                                    <?php echo e($val->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2"><?php echo e(__('Default Customs Clearances Template')); ?></label>
                        <select class="form-select  update-setting-select" data-key="customs_clearance_template"
                            id="task-to-port-template">
                            <option value=""><?php echo e(__('--- Select Template')); ?></option>
                            <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($val->id); ?>"
                                    <?php echo e(($settings['customs_clearance_template']['value'] ?? null) == $val->id ? 'selected' : ''); ?>>
                                    <?php echo e($val->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="task-template"
                            class="mb-2"><?php echo e(__('Default Customs Clearances Agent Template')); ?></label>
                        <select class="form-select  update-setting-select" data-key="customs_clearance_agent_template"
                            id="task-to-port-template">
                            <option value=""><?php echo e(__('--- Select Template')); ?></option>
                            <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($val->id); ?>"
                                    <?php echo e(($settings['customs_clearance_agent_template']['value'] ?? null) == $val->id ? 'selected' : ''); ?>>
                                    <?php echo e($val->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                </div>
            </div>
        <div class="col-md-4">
            <div class="card mt-3">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong><?php echo e(__('Policies & Reports Settings')); ?></strong>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label class="mb-2 d-flex justify-content-between align-items-center">
                            <?php echo e(__('Enable Internal Signatures')); ?>

                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input update-setting-checkbox" type="checkbox"
                                    data-key="internal_signatures_enabled"
                                    <?php echo e(($settings['internal_signatures_enabled']['value'] ?? '0') == '1' ? 'checked' : ''); ?>>
                            </div>
                        </label>
                        <p class="text-muted small"><?php echo e($settings['internal_signatures_enabled']['description'] ?? __('Enable display of stored signatures in PDF policies and reports.')); ?></p>
                    </div>
                </div>
            </div>
        </div>
        </div>
        
        <div class="col-md-8">

            <div class="card border ">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong><?php echo e(__('System Management')); ?></strong></div>
                    </div>
                </div>

                <div class="card-body text-center">

                    <h5 class="card-title"><?php echo e(__('Backup Management')); ?></h5>
                    <p class="card-text text-muted">
                        <?php echo e(__('Manage database backups and uploaded files with advanced encryption')); ?>

                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="<?php echo e(route('settings.backup')); ?>" class="btn btn-primary">
                            <i class="ti ti-settings me-1"></i>
                            <?php echo e(__('Manage Backups')); ?>

                        </a>

                    </div>
                </div>
            </div>
            <div class="row">
        <!-- Task Distribution Settings -->
        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong><?php echo e(__('Task Distribution Settings')); ?></strong></div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label class="mb-2 d-flex justify-content-between align-items-center">
                            <?php echo e(__('Auto Distribution Enabled')); ?>

                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input update-setting-checkbox" type="checkbox"
                                    data-key="auto_distribution_enabled"
                                    <?php echo e(($settings['auto_distribution_enabled']['value'] ?? '0') == '1' ? 'checked' : ''); ?>>
                            </div>
                        </label>
                        <p class="text-muted small"><?php echo e($settings['auto_distribution_enabled']['description'] ?? ''); ?></p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="distribution_mode" class="mb-2"><?php echo e(__('Distribution Mode')); ?></label>
                        <select class="form-select update-setting-select" data-key="distribution_mode">
                            <option value="sequential" <?php echo e(($settings['distribution_mode']['value'] ?? 'sequential') == 'sequential' ? 'selected' : ''); ?>>
                                <?php echo e(__('Sequential (One by one)')); ?>

                            </option>
                            <option value="broadcast" <?php echo e(($settings['distribution_mode']['value'] ?? '') == 'broadcast' ? 'selected' : ''); ?>>
                                <?php echo e(__('Broadcast (Top 5 nearby)')); ?>

                            </option>
                        </select>
                        <p class="text-muted small"><?php echo e($settings['distribution_mode']['description'] ?? ''); ?></p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="max_distribution_distance" class="mb-2"><?php echo e(__('Max Distribution Distance (Meters)')); ?></label>
                        <input type="number" data-key="max_distribution_distance"
                            value="<?php echo e($settings['max_distribution_distance']['value'] ?? '1000'); ?>"
                            class="form-control update-setting-input">
                        <p class="text-muted small"><?php echo e($settings['max_distribution_distance']['description'] ?? ''); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- App Update Settings -->
        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong><?php echo e(__('Driver App Update Settings')); ?></strong></div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label for="min_driver_app_version" class="mb-2"><?php echo e(__('Minimum App Version')); ?></label>
                        <input type="text" data-key="min_driver_app_version"
                            value="<?php echo e($settings['min_driver_app_version']['value'] ?? '1.0.0'); ?>"
                            class="form-control update-setting-input" placeholder="e.g., 1.0.5">
                        <p class="text-muted small"><?php echo e($settings['min_driver_app_version']['description'] ?? ''); ?></p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="driver_app_update_url" class="mb-2"><?php echo e(__('ِAndroid App Update URL')); ?></label>
                        <input type="url" data-key="driver_app_update_url"
                            value="<?php echo e($settings['driver_app_update_url']['value'] ?? ''); ?>"
                            class="form-control update-setting-input" placeholder="https://play.google.com/store/apps/details?id=...">
                        <p class="text-muted small"><?php echo e($settings['driver_app_update_url']['description'] ?? ''); ?></p>
                    </div>
                    <div class="form-group mb-4">
                        <label for="driver_app_ios_update_url" class="mb-2"><?php echo e(__('IOS App Update URL')); ?></label>
                        <input type="url" data-key="driver_app_ios_update_url"
                            value="<?php echo e($settings['driver_app_ios_update_url']['value'] ?? ''); ?>"
                            class="form-control update-setting-input" placeholder="https://">
                        <p class="text-muted small"><?php echo e($settings['driver_app_ios_update_url']['description'] ?? ''); ?></p>
                    </div>
                </div>
            </div>

             <div class="card mt-3">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong><?php echo e(__('Customer App Update Settings')); ?></strong></div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label for="min_customer_app_version" class="mb-2"><?php echo e(__('Minimum App Version')); ?></label>
                        <input type="text" data-key="min_customer_app_version"
                            value="<?php echo e($settings['min_customer_app_version']['value'] ?? '1.0.0'); ?>"
                            class="form-control update-setting-input" placeholder="e.g., 1.0.5">
                        <p class="text-muted small"><?php echo e($settings['min_customer_app_version']['description'] ?? ''); ?></p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="customer_app_update_url" class="mb-2"><?php echo e(__('ِAndroid App Update URL')); ?></label>
                        <input type="url" data-key="customer_app_update_url"
                            value="<?php echo e($settings['customer_app_update_url']['value'] ?? ''); ?>"
                            class="form-control update-setting-input" placeholder="https://play.google.com/store/apps/details?id=...">
                        <p class="text-muted small"><?php echo e($settings['customer_app_update_url']['description'] ?? ''); ?></p>
                    </div>
                    <div class="form-group mb-4">
                        <label for="customer_app_ios_update_url" class="mb-2"><?php echo e(__('IOS App Update URL')); ?></label>
                        <input type="url" data-key="customer_app_ios_update_url"
                            value="<?php echo e($settings['customer_app_ios_update_url']['value'] ?? ''); ?>"
                            class="form-control update-setting-input" placeholder="https://">
                        <p class="text-muted small"><?php echo e($settings['customer_app_ios_update_url']['description'] ?? ''); ?></p>
                    </div>
                </div>
            </div>

            <!-- Mtahd (Amnn) Escrow Settings Card -->
            <div class="card mt-4 border-0 shadow-sm">
                <div class="card-header bg-label-primary py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="card-title mb-0 text-primary">
                                <i class="ti ti-shield-check me-2 fs-3"></i><?php echo e(__('إعدادات وحساب المنصة في متعهد (Amnn / Mtahd Escrow)')); ?>

                            </h5>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input update-setting-checkbox" type="checkbox" id="setting_mtahd_enabled" data-key="mtahd_enabled" <?php echo e(($settings['mtahd_enabled']['value'] ?? '1') != '0' ? 'checked' : ''); ?>>
                                <label class="form-check-label fw-bold text-dark" for="setting_mtahd_enabled">
                                    <?php echo e(__('تفعيل خدمة متعهد في النظام والتطبيق')); ?>

                                </label>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn_test_mtahd_conn">
                                <i class="ti ti-plug-connected me-1"></i><?php echo e(__('اختبار الاتصال بالـ API')); ?>

                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-4">
                    <div class="alert alert-info border-0 mb-4" role="alert">
                        <div class="d-flex">
                            <i class="ti ti-info-circle fs-3 me-2"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold"><?php echo e(__('آلية الضمان المالي في متعهد:')); ?></h6>
                                <p class="mb-0 small">
                                    <?php echo e(__('المنصة مسجلة كبائع معتمد في متعهد، ويقوم العميل بسداد قيمة المهمة في حساب الضمان، وعند إتمام التوصيل يتم تحرير كامل المبلغ لحساب المنصة وتغذية محفظة السائق بصافي مستحقاته تلقائياً.')); ?>

                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold"><?php echo e(__('رقم حساب المنصة كبائع في متعهد (Seller Customer #)')); ?></label>
                                <div class="input-group">
                                    <input type="text" id="setting_mtahd_seller_num" data-key="mtahd_platform_customer_number"
                                        value="<?php echo e($settings['mtahd_platform_customer_number']['value'] ?? config('services.mtahd.platform_seller_number')); ?>"
                                        class="form-control update-setting-input font-monospace" placeholder="e.g., CUST_123456">
                                    <button class="btn btn-primary" type="button" id="btn_create_platform_mtahd">
                                        <i class="ti ti-user-plus me-1"></i><?php echo e(__('إنشاء / توثيق في متعهد')); ?>

                                    </button>
                                </div>
                                <small class="text-muted"><?php echo e(__('معرف حساب المنصة المسجل لدى متعهد لتلقي أموال الضمان المالي.')); ?></small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold"><?php echo e(__('رابط الـ API الأساسي (Base URL)')); ?></label>
                                <input type="url" data-key="mtahd_base_url"
                                    value="<?php echo e($settings['mtahd_base_url']['value'] ?? config('services.mtahd.base_url')); ?>"
                                    class="form-control update-setting-input font-monospace" placeholder="https://sandbox-api.amnn.sa/api/v1">
                                <small class="text-muted"><?php echo e(__('بيئة الاختبار: https://sandbox-api.amnn.sa/api/v1 | بيئة الإنتاج: https://api.amnn.sa/api/v1')); ?></small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold"><?php echo e(__('مفتاح الربط (API Token / Secret)')); ?></label>
                                <input type="password" data-key="mtahd_api_token"
                                    value="<?php echo e($settings['mtahd_api_token']['value'] ?? config('services.mtahd.api_token')); ?>"
                                    class="form-control update-setting-input font-monospace" placeholder="c2199c8e...">
                                <small class="text-muted"><?php echo e(__('رمز التفويض المعتمد الخاص بحساب منصتكم من شركة متعهد (أمن).')); ?></small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold"><?php echo e(__('مفتاح توقيع الإشعارات (Webhook Secret)')); ?></label>
                                <input type="password" data-key="mtahd_webhook_secret"
                                    value="<?php echo e($settings['mtahd_webhook_secret']['value'] ?? config('services.mtahd.webhook_secret')); ?>"
                                    class="form-control update-setting-input font-monospace" placeholder="webhook_secret_key">
                                <small class="text-muted"><?php echo e(__('يستخدم للتحقق المشفر من صحة الإشعارات اللحظية الواردة إلى: ')); ?> <code>/api/webhooks/mtahd</code></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mail (SMTP) Settings Card -->
            <div class="card mt-4 border-0 shadow-sm" id="mail_settings_card">
                <div class="card-header bg-label-primary py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="card-title mb-0 text-primary">
                                <i class="ti ti-mail-cog me-2 fs-3"></i><?php echo e(__('إعدادات خادم البريد الإلكتروني وإشعارات النظام (SMTP Configuration)')); ?>

                            </h5>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn_open_test_mail_modal">
                                <i class="ti ti-send me-1"></i><?php echo e(__('اختبار الاتصال وإرسال بريد تجريبي')); ?>

                            </button>
                            <button type="button" class="btn btn-sm btn-primary" id="btn_save_mail_settings">
                                <i class="ti ti-device-floppy me-1"></i><?php echo e(__('حفظ إعدادات البريد')); ?>

                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-4">
                    <div class="alert alert-primary border-0 mb-4" role="alert">
                        <div class="d-flex">
                            <i class="ti ti-info-circle fs-3 me-2"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold"><?php echo e(__('إدارة البريد الإلكتروني للمنصة:')); ?></h6>
                                <p class="mb-0 small">
                                    <?php echo e(__('يتم استخدام هذه البيانات لإرسال كافة إشعارات الإدارة، فواتير المهام، تنبيهات سحب الأموال، وتقارير النظام عبر البريد الإلكتروني. تطبق الإعدادات فوراً وبدقة تامة دون الحاجة لإعادة تشغيل السيرفر.')); ?>

                                </p>
                            </div>
                        </div>
                    </div>

                    <?php
                        $currentMailer = $settings['mail_mailer']['value'] ?? config('mail.default', 'smtp');
                        $currentHost = $settings['mail_host']['value'] ?? config('mail.mailers.smtp.host', 'smtp.hostinger.com');
                        $currentPort = $settings['mail_port']['value'] ?? config('mail.mailers.smtp.port', 587);
                        $currentUsername = $settings['mail_username']['value'] ?? config('mail.mailers.smtp.username', 'alnoumani@alnoumani.net');
                        $currentPassword = $settings['mail_password']['value'] ?? config('mail.mailers.smtp.password', '');
                        $currentEncryption = $settings['mail_encryption']['value'] ?? config('mail.mailers.smtp.encryption', 'tls');
                        $currentFromAddress = $settings['mail_from_address']['value'] ?? config('mail.from.address', 'alnoumani@alnoumani.net');
                        $currentFromName = $settings['mail_from_name']['value'] ?? config('mail.from.name', 'Safedests');
                    ?>

                    <form id="mailSettingsForm">
                        <div class="row g-3">
                            <!-- Mail Driver -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><?php echo e(__('مشغل البريد (Driver / Mailer)')); ?> <span class="text-danger">*</span></label>
                                <select id="mail_mailer" name="mail_mailer" class="form-select">
                                    <option value="smtp" <?php echo e($currentMailer == 'smtp' ? 'selected' : ''); ?>>SMTP (<?php echo e(__('موصى به')); ?>)</option>
                                    <option value="sendmail" <?php echo e($currentMailer == 'sendmail' ? 'selected' : ''); ?>>Sendmail</option>
                                    <option value="log" <?php echo e($currentMailer == 'log' ? 'selected' : ''); ?>>Log (<?php echo e(__('سجلات فقط')); ?>)</option>
                                </select>
                                <small class="text-muted"><?php echo e(__('بروتوكول الإرسال الأساسي.')); ?></small>
                            </div>

                            <!-- Host -->
                            <div class="col-md-5">
                                <label class="form-label fw-bold"><?php echo e(__('خادم البريد (Mail Host)')); ?> <span class="text-danger">*</span></label>
                                <input type="text" id="mail_host" name="mail_host" class="form-control font-monospace"
                                    value="<?php echo e($currentHost); ?>" placeholder="e.g., smtp.hostinger.com">
                                <small class="text-muted"><?php echo e(__('عنوان خادم الـ SMTP الخاص بمزود الخدمة.')); ?></small>
                            </div>

                            <!-- Port -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold"><?php echo e(__('المنفذ (Port)')); ?> <span class="text-danger">*</span></label>
                                <input type="number" id="mail_port" name="mail_port" class="form-control font-monospace"
                                    value="<?php echo e($currentPort); ?>" placeholder="587">
                                <small class="text-muted"><?php echo e(__('الافتراضي: 587 أو 465.')); ?></small>
                            </div>

                            <!-- Encryption -->
                            <div class="col-md-2">
                                <label class="form-label fw-bold"><?php echo e(__('التشفير (Encryption)')); ?></label>
                                <select id="mail_encryption" name="mail_encryption" class="form-select">
                                    <option value="tls" <?php echo e($currentEncryption == 'tls' ? 'selected' : ''); ?>>TLS</option>
                                    <option value="ssl" <?php echo e($currentEncryption == 'ssl' ? 'selected' : ''); ?>>SSL</option>
                                    <option value="null" <?php echo e(in_array($currentEncryption, ['null', 'none', '']) ? 'selected' : ''); ?>><?php echo e(__('بدون تشفير')); ?></option>
                                </select>
                                <small class="text-muted"><?php echo e(__('نوع تشفير الاتصال.')); ?></small>
                            </div>

                            <!-- Username -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?php echo e(__('اسم مستخدم البريد (Username / Email)')); ?></label>
                                <input type="text" id="mail_username" name="mail_username" class="form-control font-monospace"
                                    value="<?php echo e($currentUsername); ?>" placeholder="notifications@safedest.com">
                                <small class="text-muted"><?php echo e(__('عنوان البريد الإلكتروني المستخدم للمصادقة.')); ?></small>
                            </div>

                            <!-- Password -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?php echo e(__('كلمة مرور البريد (Password / App Password)')); ?></label>
                                <div class="input-group">
                                    <input type="password" id="mail_password" name="mail_password" class="form-control font-monospace"
                                        value="<?php echo e($currentPassword); ?>" placeholder="••••••••••••">
                                    <button class="btn btn-outline-secondary" type="button" id="btn_toggle_mail_pwd">
                                        <i class="ti ti-eye" id="mail_pwd_icon"></i>
                                    </button>
                                </div>
                                <small class="text-muted"><?php echo e(__('كلمة المرور الخاصة بالحساب أو كلمة مرور التطبيقات المخصصة.')); ?></small>
                            </div>

                            <!-- From Address -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?php echo e(__('عنوان البريد المرسل (From Address)')); ?> <span class="text-danger">*</span></label>
                                <input type="email" id="mail_from_address" name="mail_from_address" class="form-control font-monospace"
                                    value="<?php echo e($currentFromAddress); ?>" placeholder="noreply@safedest.com">
                                <small class="text-muted"><?php echo e(__('العنوان الذي سيظهر للمستلمين في حقل المرسل.')); ?></small>
                            </div>

                            <!-- From Name -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?php echo e(__('اسم المرسل الظاهر (From Name)')); ?> <span class="text-danger">*</span></label>
                                <input type="text" id="mail_from_name" name="mail_from_name" class="form-control"
                                    value="<?php echo e($currentFromName); ?>" placeholder="SafeDest Platform">
                                <small class="text-muted"><?php echo e(__('الاسم التعريفي الذي يظهر للمستلم في صندوق الوارد.')); ?></small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Test Email Modal -->
            <div class="modal fade" id="testEmailModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-label-primary py-3">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-send fs-4 me-2 text-primary"></i>
                                <h5 class="modal-title fw-bold mb-0 text-heading"><?php echo e(__('اختبار اتصال خادم البريد (SMTP)')); ?></h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <p class="text-muted small mb-3">
                                <?php echo e(__('سيقوم النظام بإرسال بريد تجريبي فوري للتحقق من دقة بيانات الاتصال (Host, Port, Username, Password) مع إعطائك تقريراً لحظياً بالنتيجة أو كود الخطأ إن وجد.')); ?>

                            </p>
                            <div class="mb-3">
                                <label class="form-label fw-bold"><?php echo e(__('إرسال البريد التجريبي إلى:')); ?> <span class="text-danger">*</span></label>
                                <input type="email" id="modal_test_email_recipient" class="form-control"
                                    value="<?php echo e(auth()->user()?->email ?? 'admin@safedest.com'); ?>" placeholder="name@example.com">
                            </div>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="test_using_form_values" checked>
                                <label class="form-check-label small fw-bold" for="test_using_form_values">
                                    <?php echo e(__('استخدم القيم الحالية المدخلة في النموذج أعلاه (للتجربة قبل الحفظ)')); ?>

                                </label>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light">
                            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('إلغاء')); ?></button>
                            <button type="button" class="btn btn-primary" id="btn_send_test_email">
                                <i class="ti ti-mail-fast me-1"></i> <?php echo e(__('إرسال رسالة الاختبار الآن')); ?>

                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Toggle password visibility
        const btnTogglePwd = document.getElementById('btn_toggle_mail_pwd');
        if (btnTogglePwd) {
            btnTogglePwd.addEventListener('click', function () {
                const pwdInput = document.getElementById('mail_password');
                const pwdIcon = document.getElementById('mail_pwd_icon');
                if (pwdInput.type === 'password') {
                    pwdInput.type = 'text';
                    pwdIcon.className = 'ti ti-eye-off';
                } else {
                    pwdInput.type = 'password';
                    pwdIcon.className = 'ti ti-eye';
                }
            });
        }

        // Save Mail Settings
        const btnSaveMail = document.getElementById('btn_save_mail_settings');
        if (btnSaveMail) {
            btnSaveMail.addEventListener('click', function () {
                const btn = this;
                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري الحفظ...';

                const payload = {
                    _token: '<?php echo e(csrf_token()); ?>',
                    mail_mailer: document.getElementById('mail_mailer').value,
                    mail_host: document.getElementById('mail_host').value,
                    mail_port: document.getElementById('mail_port').value,
                    mail_encryption: document.getElementById('mail_encryption').value,
                    mail_username: document.getElementById('mail_username').value,
                    mail_password: document.getElementById('mail_password').value,
                    mail_from_address: document.getElementById('mail_from_address').value,
                    mail_from_name: document.getElementById('mail_from_name').value,
                };

                fetch("<?php echo e(route('settings.mail.update')); ?>", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '<?php echo e(__("تم الحفظ بنجاح")); ?>',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '<?php echo e(__("خطأ")); ?>',
                            text: data.message || '<?php echo e(__("حدث خطأ أثناء حفظ الإعدادات")); ?>'
                        });
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    Swal.fire({
                        icon: 'error',
                        title: '<?php echo e(__("خطأ")); ?>',
                        text: '<?php echo e(__("تعذر الاتصال بالسيرفر")); ?>'
                    });
                });
            });
        }

        // Open Test Email Modal
        const btnOpenTestModal = document.getElementById('btn_open_test_mail_modal');
        if (btnOpenTestModal) {
            btnOpenTestModal.addEventListener('click', function () {
                const modalElem = document.getElementById('testEmailModal');
                if (modalElem) {
                    const modal = new bootstrap.Modal(modalElem);
                    modal.show();
                }
            });
        }

        // Send Test Email
        const btnSendTestEmail = document.getElementById('btn_send_test_email');
        if (btnSendTestEmail) {
            btnSendTestEmail.addEventListener('click', function () {
                const recipient = document.getElementById('modal_test_email_recipient').value;
                if (!recipient) {
                    Swal.fire({ icon: 'warning', title: 'تنبيه', text: 'يرجى كتابة عنوان البريد الإلكتروني للمستلم.' });
                    return;
                }

                const useFormValues = document.getElementById('test_using_form_values').checked;
                const payload = {
                    _token: '<?php echo e(csrf_token()); ?>',
                    test_email: recipient
                };

                if (useFormValues) {
                    payload.mail_mailer = document.getElementById('mail_mailer').value;
                    payload.mail_host = document.getElementById('mail_host').value;
                    payload.mail_port = document.getElementById('mail_port').value;
                    payload.mail_encryption = document.getElementById('mail_encryption').value;
                    payload.mail_username = document.getElementById('mail_username').value;
                    payload.mail_password = document.getElementById('mail_password').value;
                    payload.mail_from_address = document.getElementById('mail_from_address').value;
                    payload.mail_from_name = document.getElementById('mail_from_name').value;
                }

                Swal.fire({
                    title: '<?php echo e(__("جاري الاتصال وإرسال البريد...")); ?>',
                    text: '<?php echo e(__("يتم الاتصال بخادم الـ SMTP والتحقق من الصلاحيات")); ?>',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch("<?php echo e(route('settings.mail.test-connection')); ?>", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const modalElem = document.getElementById('testEmailModal');
                        if (modalElem) {
                            const modalInstance = bootstrap.Modal.getInstance(modalElem);
                            if (modalInstance) modalInstance.hide();
                        }
                        Swal.fire({
                            icon: 'success',
                            title: '<?php echo e(__("نجاح الاتصال والإرسال")); ?>',
                            text: data.message
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '<?php echo e(__("فشل الاتصال / الإرسال")); ?>',
                            html: `<div class="text-danger small font-monospace text-start p-2 bg-light rounded">${data.message}</div>`
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({ icon: 'error', title: '<?php echo e(__("خطأ")); ?>', text: '<?php echo e(__("تعذر الاتصال بالسيرفر")); ?>' });
                });
            });
        }

        // Test Mtahd Connection Button
        const btnTest = document.getElementById('btn_test_mtahd_conn');
        if (btnTest) {
            btnTest.addEventListener('click', function () {
                Swal.fire({
                    title: '<?php echo e(__("جاري فحص الاتصال...")); ?>',
                    text: '<?php echo e(__("التحقق من صحة مفتاح الربط والاتصال بمنصة متعهد")); ?>',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch("<?php echo e(route('settings.mtahd.test-connection')); ?>", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: '<?php echo e(__("الاتصال سليم")); ?>', text: data.message });
                    } else {
                        Swal.fire({ icon: 'error', title: '<?php echo e(__("فشل الاتصال")); ?>', text: data.message });
                    }
                })
                .catch(() => Swal.fire({ icon: 'error', title: '<?php echo e(__("خطأ")); ?>', text: '<?php echo e(__("تعذر الاتصال بالسيرفر")); ?>' }));
            });
        }

        // Create Platform Account in Mtahd Button
        const btnCreate = document.getElementById('btn_create_platform_mtahd');
        if (btnCreate) {
            btnCreate.addEventListener('click', function () {
                Swal.fire({
                    title: '<?php echo e(__("إنشاء / توثيق حساب المنصة في متعهد")); ?>',
                    text: '<?php echo e(__("سيتم إرسال بيانات المنصة الرسمية إلى متعهد للحصول على رقم بائع معتمد (Platform Seller Number).")); ?>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<?php echo e(__("نعم، أنشئ الحساب")); ?>',
                    cancelButtonText: '<?php echo e(__("إلغاء")); ?>',
                    customClass: { confirmButton: 'btn btn-primary me-2', cancelButton: 'btn btn-label-secondary' },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({ title: '<?php echo e(__("جاري الإرسال...")); ?>', didOpen: () => { Swal.showLoading(); } });

                        fetch("<?php echo e(route('settings.mtahd.create-account')); ?>", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                name: 'منصة سيف ديست للخدمات اللوجستية (SafeDests)',
                                phone: '+966500000000',
                                email: 'finance@safedests.com'
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('setting_mtahd_seller_num').value = data.customer_number;
                                Swal.fire({ icon: 'success', title: '<?php echo e(__("تم بنجاح")); ?>', text: data.message });
                            } else {
                                Swal.fire({ icon: 'error', title: '<?php echo e(__("فشل الإنشاء")); ?>', text: data.message });
                            }
                        })
                        .catch(() => Swal.fire({ icon: 'error', title: '<?php echo e(__("خطأ")); ?>', text: '<?php echo e(__("تعذر الاتصال بالسيرفر")); ?>' }));
                    }
                });
            });
        }
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/settings/index.blade.php ENDPATH**/ ?>