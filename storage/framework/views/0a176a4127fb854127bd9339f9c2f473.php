<?php $__env->startSection('title', __('Roles')); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss']); ?>
    <style>
        .permissions-container {
            max-height: 400px;
            overflow-y: auto;
            border-radius: 5px;
        }

        .permissions-container .form-check {
            margin-bottom: 5px;
            border-radius: 4px;
        }

        .nav-pills .nav-link {
            border-radius: 0.375rem;
            transition: background-color 0.3s ease-in-out;
        }

        .nav-pills .nav-link.active {
            background-color: #007bff;
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/block-ui/block-ui.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/admin/roles.js']); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/ajax.js']); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2"><?php echo e(__('Roles & Permissions')); ?></h5>
            <p><?php echo e(__('Add new roles with customized permissions as per your requirement')); ?>. </p>
            <button class="add-new btn btn-primary waves-effect waves-light mb-5 mx-4" data-bs-toggle="modal"
                data-bs-target="#formModal">
                <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                <span class="d-none d-sm-inline-block"> <?php echo e(__('Add New Role')); ?></span>
            </button>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-data table">
                <thead class="border-top">
                    <tr>
                        <th></th>
                        <th>#</th>
                        <th><?php echo e(__('Role')); ?></th>
                        <th><?php echo e(__('Created At')); ?></th>
                        <th><?php echo e(__('Actions')); ?></th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>

    <div class="modal fade " id="formModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle"><?php echo e(__('Add New Role')); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="<?php echo e(__('Close')); ?>"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="<?php echo e(route('role.create')); ?>">
                    <div class="modal-body">

                        <input type="hidden" name="id" id="role_id">
                        <span class="id-error text-danger text-error"></span>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-6">
                                    <label class="form-label" for="role-name">* <?php echo e(__('Role')); ?></label>
                                    <input type="text" name="name" class="form-control" id="role-name"
                                        placeholder="<?php echo e(__('Role Name')); ?>" aria-label="<?php echo e(__('Role Name')); ?>" />
                                    <span class="name-error text-danger text-error"></span>

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-6" id="check-guard">
                                    <label class="form-label" for="role-guard">* <?php echo e(__('Guard')); ?></label>
                                    <select name="guard" id="role-guard" class="form-select ">
                                        <option value="web"><?php echo e(__('Administrator')); ?></option>
                                        <option value="driver"><?php echo e(__('Driver')); ?></option>
                                        <option value="customer"><?php echo e(__('Customer')); ?></option>
                                    </select>
                                    <span class="guard-error text-danger text-error"></span>

                                </div>
                            </div>

                            <div class="col-xl-12">
                                <h6 class="text-muted">* <?php echo e(__('Permissions')); ?></h6>
                                <span class="permissions-error text-danger text-error"></span>
                                <div class="nav-align-left mb-6">
                                    <ul class="nav nav-pills me-4" role="tablist" id="permissions_types">
                                        <li class="nav-item">
                                            <button type="button" class="nav-link active" role="tab"
                                                data-bs-toggle="tab" data-bs-target="#navs-pills-left-home"
                                                aria-controls="navs-pills-left-home"
                                                aria-selected="true"><?php echo e(__('Home')); ?></button>
                                        </li>
                                        <li class="nav-item">
                                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                                data-bs-target="#navs-pills-left-profile"
                                                aria-controls="navs-pills-left-profile"
                                                aria-selected="false"><?php echo e(__('Profile')); ?></button>
                                        </li>
                                        <li class="nav-item">
                                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                                data-bs-target="#navs-pills-left-messages"
                                                aria-controls="navs-pills-left-messages"
                                                aria-selected="false"><?php echo e(__('Messages')); ?></button>
                                        </li>
                                    </ul>
                                    <div class="tab-content permissions-container" id="permissions_container">
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
                        <button type="submit" class="btn btn-primary me-3 data-submit"><?php echo e(__('Submit')); ?></button>

                    </div>
                </form>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/roles/index.blade.php ENDPATH**/ ?>