<?php $__env->startSection('title', __('Geo-fence')); ?>

<!-- Vendor Styles -->
<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/leaflet/leaflet.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/select2/select2.scss']); ?>
    <?php echo app('Illuminate\Foundation\Vite')('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss'); ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">

    <style>
        .selected-geofence {
            background-color: #ffdddd !important;
            border-radius: 5px;
            border: 1px solid gray;
            border-left: 7px solid red;
            transition: background-color 0.3s, border-left 0.3s;
        }

        .hidden-label {
            display: none;
        }

        #geofence-list {
            max-height: 420px;
            overflow-y: auto;
            padding-right: 5px;
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
        }

        #geofence-list::-webkit-scrollbar {
            width: 6px;
        }

        #geofence-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        #geofence-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        #geofence-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        #submitModal .modal-body {
            max-height: 500px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
        }

        #submitModal .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        #submitModal .modal-body::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
    </style>
<?php $__env->stopSection(); ?>

<!-- Vendor Scripts -->
<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/leaflet/leaflet.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js']); ?>
    <?php echo app('Illuminate\Foundation\Vite')('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js'); ?>


<?php $__env->stopSection(); ?>

<!-- Page Scripts -->
<?php $__env->startSection('page-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/ajax.js']); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/admin/geofences.js']); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">
                <i class="tf-icons ti ti-adjustments me-2 fs-3 text-white bg-primary rounded p-1"></i>

                <?php echo e(__('Settings')); ?> | <?php echo e(__('Geo-fence')); ?>

            </h5>
            <p><?php echo e(__('It allows you to categorize Manager and simplifies the process of task assignment by letting you create virtual boundaries.')); ?>

            </p>

            <div class="col-md-12">
                <button class="add-new btn btn-primary waves-effect waves-light mb-5 mx-4" data-bs-toggle="modal"
                    data-bs-target="#submitModal">
                    <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                    <span class="d-none d-sm-inline-block"><?php echo e(__('Add New Geo-fence')); ?></span>
                </button>
                <input type="text" id="search-geo" class="form-control " placeholder="<?php echo e(__('🔍 Search Team')); ?>">

            </div>
        </div>
    </div>



    <div class="row mt-6">
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo e(__('Geofences')); ?></h5>
                    <div id="vertical-scroll">
                        <div id="geofence-list">
                            <!-- سيتم إضافة البيانات هنا باستخدام JavaScript -->
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card mb-6">
                <div class="leaflet-map" id="shapehMap" style="height: 500px;"></div>
            </div>
        </div>
    </div>


    <div class="modal fade " id="submitModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="row">

                    <div class="col-md-4">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modelTitle"><?php echo e(__('Add Geo-fence')); ?></h5>

                        </div>
                        <div class=" mt-6">
                            <div class="px-3">
                                <form action="<?php echo e(route('settings.geofences.store')); ?>" method="POST" class="form_submit">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" id="geo-id" name="id">

                                    <input type="hidden" id="geo-coordinates" name="coordinates">
                                    <span class="coordinates-error text-danger text-error"></span>

                                    <div class="form-group">
                                        <label for="geo-name">* <?php echo e(__('Name')); ?></label>
                                        <input type="text" class="form-control" id="geo-name" name="name"
                                            placeholder="<?php echo e(__('Enter name')); ?>">
                                        <span class="name-error text-danger text-error"></span>

                                    </div>
                                    <div class="form-group mt-3">
                                        <label for="geo-description"><?php echo e(__('Description')); ?></label>
                                        <textarea class="form-control" id="geo-description" name="description" rows="2"
                                            placeholder="<?php echo e(__('Enter description')); ?>"></textarea>
                                        <span class="description-error text-danger text-error"></span>

                                    </div>

                                    <div class="form-group mt-3 mb-5">
                                        <label for="geo-teams"><?php echo e(__('Teams')); ?></label>
                                        <select name="teams[]" id="geo-teams" class="select2 form-select" multiple>
                                            <option value=""></option>
                                            <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($key->id); ?>"><?php echo e($key->name); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <span class="teams-error text-danger text-error"></span>
                                    </div>

                                    <button type="submit"
                                        class="btn btn-primary me-3 data-submit"><?php echo e(__('Submit')); ?></button>

                                    <button type="button" class="btn btn-label-secondary"
                                        data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="p-3">
                            <div class="leaflet-map" id="submit-map"></div>
                        </div>

                    </div>
                </div>


            </div>
        </div>
    </div>






<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/settings/geofences.blade.php ENDPATH**/ ?>