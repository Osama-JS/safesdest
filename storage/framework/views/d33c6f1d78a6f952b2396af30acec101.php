 

 <?php $__env->startSection('title', __('Customers')); ?>

 <!-- Vendor Styles -->
 <?php $__env->startSection('vendor-style'); ?>

     <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>

     <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css']); ?>
 <?php $__env->stopSection(); ?>

 <!-- Vendor Scripts -->
 <?php $__env->startSection('vendor-script'); ?>

     <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js']); ?>
     <script>
         const templateId = <?php echo e($task_template->value ?? 0); ?>

     </script>
 <?php $__env->stopSection(); ?>

 <!-- Page Scripts -->
 <?php $__env->startSection('page-script'); ?>
     <?php echo app('Illuminate\Foundation\Vite')(['resources/js/admin/customers/customers.js']); ?>
     <?php echo app('Illuminate\Foundation\Vite')(['resources/js/ajax.js']); ?>
     <?php echo app('Illuminate\Foundation\Vite')(['resources/js/spical.js']); ?>
 <?php $__env->stopSection(); ?>

 <?php $__env->startSection('content'); ?>

     <div class="row g-6 mb-6">
         <div class="col-sm-6 col-xl-3">
             <div class="card">
                 <div class="card-body">
                     <div class="d-flex align-items-start justify-content-between">
                         <div class="content-left">
                             <span class="text-heading"><?php echo e(__('Customers')); ?></span>
                             <div class="d-flex align-items-center my-1">
                                 <h4 class="mb-0 me-2" id="total"></h4>
                             </div>

                         </div>
                         <div class="avatar">
                             <span class="avatar-initial rounded bg-label-primary">
                                 <i class="ti ti-user ti-26px"></i>
                             </span>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
         <div class="col-sm-6 col-xl-3">
             <div class="card">
                 <div class="card-body">
                     <div class="d-flex align-items-start justify-content-between">
                         <div class="content-left">
                             <span class="text-heading"><?php echo e(__('Active Customers')); ?></span>
                             <div class="d-flex align-items-center my-1">
                                 <h4 class="mb-0 me-2" id="total-active"></h4>
                                 <p class="text-success mb-0">
                                 </p>
                             </div>

                         </div>
                         <div class="avatar">
                             <span class="avatar-initial rounded bg-label-success">
                                 <i class="ti ti-user-check ti-26px"></i>
                             </span>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
         <div class="col-sm-6 col-xl-3">
             <div class="card">
                 <div class="card-body">
                     <div class="d-flex align-items-start justify-content-between">
                         <div class="content-left">
                             <span class="text-heading"><?php echo e(__('Unverified Customers')); ?></span>
                             <div class="d-flex align-items-center my-1">
                                 <h4 class="mb-0 me-2" id="total-verified"></h4>
                                 <p class="text-success mb-0">

                                 </p>

                                 </p>
                             </div>

                         </div>
                         <div class="avatar">
                             <span class="avatar-initial rounded bg-label-danger">
                                 <i class="ti ti-users ti-26px"></i>
                             </span>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
         <div class="col-sm-6 col-xl-3">
             <div class="card">
                 <div class="card-body">
                     <div class="d-flex align-items-start justify-content-between">
                         <div class="content-left">
                             <span class="text-heading"><?php echo e(__('Blocked Customers')); ?></span>
                             <div class="d-flex align-items-center my-1">
                                 <h4 class="mb-0 me-2" id="total-blocked"></h4>
                                 <p class="text-success mb-0">
                                 </p>

                                 </p>
                             </div>

                         </div>
                         <div class="avatar">
                             <span class="avatar-initial rounded bg-label-warning">
                                 <i class="ti ti-user-search ti-26px"></i>
                             </span>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
     </div>
     <!-- Users List Table -->
     <div class="card">
         <div class="card-header border-bottom">
             <h5 class="card-title mb-0">
                 <i class="tf-icons ti ti-user-circle me-2 fs-3 text-white bg-primary rounded p-1"></i>
                 <?php echo e(__('Customers')); ?>

             </h5>
             <button class="add-new btn btn-primary waves-effect waves-light mt-5 mx-4" data-bs-toggle="modal"
                 data-bs-target="#submitModal">
                 <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                 <span class="d-none d-sm-inline-block"> <?php echo e(__('Add New Customer')); ?></span>
             </button>
         </div>
         <div class="card-datatable table-responsive">
             <table class="datatables-users table">
                 <thead class="table-light">
                     <tr>
                         <th></th>
                         <th>#</th>
                         <th><?php echo e(__('name')); ?></th>
                         <th><?php echo e(__('email')); ?></th>
                         <th><?php echo e(__('phone')); ?></th>
                         <th><?php echo e(__('role')); ?></th>
                         <th><?php echo e(__('tags')); ?></th>
                         <th><?php echo e(__('status')); ?></th>
                         <th><?php echo e(__('created at')); ?></th>
                         <th><?php echo e(__('actions')); ?></th>
                     </tr>
                 </thead>
             </table>
         </div>

     </div>

     <div class="modal fade " id="submitModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
         <div class="modal-dialog modal-xl" role="document">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="modelTitle"><?php echo e(__('Add New Customer')); ?></h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal"
                         aria-label="<?php echo e(__('Close')); ?>"></button>
                 </div>
                 <form class="add-new-user pt-0 form_submit" method="POST" action="<?php echo e(route('customers.create')); ?>"
                     enctype="multipart/form-data">
                     <div class="modal-body">
                         <div class="col-xl-12">
                             <div class="nav-align-top  mb-6">
                                 <ul class="nav nav-tabs " role="tablist">
                                     <li class="nav-item">
                                         <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                             data-bs-target="#navs-justified-home" aria-controls="navs-justified-home"
                                             aria-selected="true"><span class="d-none d-sm-block"><i
                                                     class="tf-icons ti ti-grid-dots ti-sm me-1_5"></i> <?php echo e(__('Main')); ?>

                                         </button>
                                     </li>
                                     <li class="nav-item">
                                         <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                             data-bs-target="#navs-justified-profile"
                                             aria-controls="navs-justified-profile" aria-selected="false"><span
                                                 class="d-none d-sm-block"><i
                                                     class="tf-icons ti ti-file-plus ti-sm me-1_5"></i>
                                                 <?php echo e(__('Additional ')); ?></span></button>
                                     </li>
                                 </ul>
                                 <div class="tab-content">
                                     <div class="tab-pane fade show active" id="navs-justified-home" role="tabpanel">
                                         <input type="hidden" name="id" id="customer_id" autocomplete="false">
                                         <div class="row">
                                             <div class="col-md-3">
                                                 <div class="mb-6">
                                                     <img src="<?php echo e(url(asset('assets/img/person.png'))); ?>"
                                                         data-image="<?php echo e(url(asset('assets/img/person.png'))); ?>"
                                                         alt="" id="image"
                                                         style="width: 100%;    height: 222px;
                                                    object-fit: cover;"
                                                         class="rounded preview-image image-input">

                                                     <input type="file" class="form-control file-input-image"
                                                         id="driver-image" name="image" style="display: none" />
                                                     <span class="image-error text-danger text-error"></span>

                                                 </div>
                                             </div>
                                             <div class="col-md-9">

                                                 <div class="row">
                                                     <div class="col-md-6">
                                                         <div class="mb-6">
                                                             <label class="form-label" for="customer-fullname">*
                                                                 <?php echo e(__('Full Name')); ?></label>
                                                             <input type="text" class="form-control"
                                                                 id="customer-fullname"
                                                                 placeholder="<?php echo e(__('Full Name')); ?>" name="name"
                                                                 aria-label="<?php echo e(__('Full Name')); ?>" />
                                                             <span class="name-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-6">
                                                         <div class="mb-6">
                                                             <label class="form-label" for="customer-email">*
                                                                 <?php echo e(__('Email')); ?></label>
                                                             <input type="text" id="customer-email"
                                                                 class="form-control"
                                                                 placeholder="<?php echo e(__('example@example.com')); ?>"
                                                                 aria-label="<?php echo e(__('example@example.com')); ?>"
                                                                 name="email" />
                                                             <span class="email-error text-danger text-error"></span>

                                                         </div>
                                                     </div>
                                                     <div class="col-md-6">
                                                         <div class="mb-6">
                                                             <label class="form-label" for="customer-phone">*
                                                                 <?php echo e(__('Phone')); ?></label>
                                                             <div class="input-group">
                                                                 <select id="country-code" name="phone_code"
                                                                     class="form-select" required
                                                                     style="max-width: 120px;">
                                                                     <option value="+966">🇸🇦 +966</option>
                                                                     <option value="+971">🇦🇪 +971</option>
                                                                     <option value="+20">🇪🇬 +20</option>
                                                                     <option value="+1">🇺🇸 +1</option>
                                                                 </select>
                                                                 <input type="tel" id="customer-phone"
                                                                     class="form-control"
                                                                     placeholder="<?php echo e(__('Enter phone number')); ?>"
                                                                     name="phone" />
                                                             </div>
                                                             <span class="phone-error text-danger text-error"></span>
                                                             <span
                                                                 class="phone_code_code-error text-danger text-error"></span>
                                                         </div>
                                                     </div>
                                                     <div class="col-md-6">
                                                         <div class="mb-6">
                                                             <label class="form-label" for="customer-role">
                                                                 <?php echo e(__('Customer Role')); ?></label>
                                                             <select id="customer-role" class="form-select"
                                                                 name="role">
                                                                 <option value="">-- <?php echo e(__('Select Role')); ?>

                                                                 </option>
                                                                 <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                     <option value="<?php echo e($key->id); ?>">
                                                                         <?php echo e($key->name); ?></option>
                                                                 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                             </select>
                                                             <span class="role-error text-danger text-error"></span>
                                                         </div>
                                                     </div>
                                                     <div class="col-md-6">
                                                         <div class="mb-6">
                                                             <label class="form-label" for="customer-password">*
                                                                 <?php echo e(__('Password')); ?></label>
                                                             <input type="password" id="customer-password"
                                                                 class="form-control" name="password" />
                                                             <span class="password-error text-danger text-error"></span>

                                                         </div>
                                                     </div>
                                                     <div class="col-md-6">
                                                         <div class="mb-6">
                                                             <label class="form-label" for="customer-re-password">*
                                                                 <?php echo e(__('Confirm Password')); ?></label>
                                                             <input type="password" id="customer-re-password"
                                                                 class="form-control" name="confirm-password" />
                                                             <span
                                                                 class="confirm-password-error text-danger text-error"></span>
                                                         </div>
                                                     </div>
                                                     <div class="divider text-start">
                                                         <div class="divider-text">
                                                             <strong><?php echo e(__('Company Info')); ?></strong>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-12 mb-4">
                                                         <div class="form-check form-switch card-p">
                                                             <input class="form-check-input" type="checkbox" name="is_company" id="customer-is-company" value="1">
                                                             <label class="form-check-label fw-bold" for="customer-is-company">
                                                                 <?php echo e(__('Mark as B2B Company')); ?>

                                                             </label>
                                                         </div>
                                                         <small class="text-muted"><?php echo e(__('Enables B2B specific features, warehouses and multiple end-clients.')); ?></small>
                                                     </div>

                                                     <div class="col-md-12 mb-4">
                                                         <div class="form-check form-switch card-p">
                                                             <input class="form-check-input" type="checkbox" name="allow_investment" id="customer-allow-investment" value="1">
                                                             <label class="form-check-label fw-bold" for="customer-allow-investment">
                                                                 <i class="ti ti-chart-arrows me-1 text-success"></i><?php echo e(__('Allow Investment for Tasks')); ?> (إتاحة المهام للاستثمار)
                                                             </label>
                                                         </div>
                                                         <small class="text-muted"><?php echo e(__('When enabled, this customer\'s eligible tasks will be visible to investors for funding.')); ?></small>
                                                     </div>


                                                     <div class="col-md-6">
                                                         <div class="mb-4">
                                                             <label class="form-label" for="customer-c_name">
                                                                 <?php echo e(__('Company Name')); ?></label>
                                                             <input type="text" name="c_name" class="form-control"
                                                                 id="customer-c_name"
                                                                 placeholder="<?php echo e(__('enter company name')); ?>" />
                                                             <span class="c_name-error text-danger text-error"></span>
                                                         </div>
                                                     </div>
                                                     <div class="col-md-6">
                                                         <div class="mb-4">
                                                             <label class="form-label" for="customer-c_address">
                                                                 <?php echo e(__('Company Address')); ?></label>
                                                             <input type="text" name="c_address" class="form-control"
                                                                 id="customer-c_address"
                                                                 placeholder="<?php echo e(__('enter company address')); ?>" />
                                                             <span class="c_address-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-6">
                                                         <div class="mb-4">
                                                             <label class="form-label" for="customer-policy-file">
                                                                 <?php echo e(__('Policy File Name')); ?></label>
                                                             <input type="text" name="policy_file_name" class="form-control"
                                                                 id="customer-policy-file"
                                                                 placeholder="<?php echo e(__('config.file_name_blade')); ?>" />
                                                             <span class="policy_file_name-error text-danger text-error"></span>
                                                         </div>
                                                     </div>


                                                     <div class="col-md-6">
                                                         <div class="  mb-6">
                                                             <label class="form-label"
                                                                 for="customer-tags"><?php echo e(__('Tags')); ?></label>
                                                             <select name="tags[]" id="customer-tags"
                                                                 class="select2 form-select" multiple>
                                                                 <option value=""></option>
                                                                 <?php $__currentLoopData = $tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                     <option value="<?php echo e($key->id); ?>">
                                                                         <?php echo e($key->name); ?>

                                                                     </option>
                                                                 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                             </select>
                                                             <span class="tags-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                     <!-- Bank Details Section -->
                                                     <div class="col-md-12">
                                                         <div class="divider text-start">
                                                             <div class="divider-text">
                                                                 <strong><i
                                                                         class="ti ti-building-bank me-2"></i><?php echo e(__('Bank Details')); ?></strong>
                                                             </div>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-4">
                                                         <div class="mb-4">
                                                             <label class="form-label"
                                                                 for="customer-bank-name"><?php echo e(__('Bank Name')); ?></label>
                                                             <select name="bank_name" id="customer-bank-name"
                                                                 class="form-select">
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
                                                             <span class="bank_name-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-4" id="customer-custom-bank-field"
                                                         style="display: none;">
                                                         <div class="mb-4">
                                                             <label class="form-label"
                                                                 for="customer-custom-bank-name"><?php echo e(__('Custom Bank Name')); ?></label>
                                                             <input type="text" name="custom_bank_name"
                                                                 id="customer-custom-bank-name" class="form-control"
                                                                 placeholder="<?php echo e(__('Enter bank name')); ?>">
                                                             <span
                                                                 class="custom_bank_name-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-4">
                                                         <div class="mb-4">
                                                             <label class="form-label"
                                                                 for="customer-account-number"><?php echo e(__('Account Number')); ?></label>
                                                             <input type="text" name="account_number"
                                                                 id="customer-account-number" class="form-control"
                                                                 placeholder="1234567890" pattern="[0-9]{8,20}"
                                                                 minlength="8" maxlength="20">
                                                             <div class="form-text">
                                                                 <small
                                                                     class="text-muted"><?php echo e(__('Numbers only, 8-20 digits')); ?></small>
                                                             </div>
                                                             <span
                                                                 class="account_number-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-4">
                                                         <div class="mb-4">
                                                             <label class="form-label"
                                                                 for="customer-iban-number"><?php echo e(__('IBAN Number')); ?></label>
                                                             <input type="text" name="iban_number"
                                                                 id="customer-iban-number" class="form-control"
                                                                 placeholder="SA12 3456 7890 1234 5678 90" maxlength="29"
                                                                 pattern="SA(?:[0-9]{2}\s?){11}">
                                                             <div class="form-text">
                                                                 <small
                                                                     class="text-muted"><?php echo e(__('Format: SA + 22 digits')); ?></small>
                                                             </div>
                                                             <span class="iban_number-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                    <!-- Task Numbering Section -->
                                                    <div class="col-md-12">
                                                        <div class="divider text-start">
                                                            <div class="divider-text">
                                                                <strong><i
                                                                        class="ti ti-hash me-2"></i><?php echo e(__('Task Numbering')); ?></strong>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="customer-task-number-start"><?php echo e(__('Task Number Start')); ?></label>
                                                            <input type="number" name="task_number_start"
                                                                id="customer-task-number-start" class="form-control"
                                                                placeholder="<?php echo e(__('e.g. 3000')); ?>" min="1">
                                                            <div class="form-text">
                                                                <small
                                                                    class="text-muted"><?php echo e(__('Starting number for custom task numbering')); ?></small>
                                                            </div>
                                                            <span class="task_number_start-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="customer-task-number-next"><?php echo e(__('Next Task Number')); ?></label>
                                                            <input type="number"
                                                                id="customer-task-number-next" class="form-control"
                                                                readonly disabled>
                                                            <div class="form-text">
                                                                <small
                                                                    class="text-muted"><?php echo e(__('Auto-calculated, read only')); ?></small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-12">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="customer-general-task-notes"><?php echo e(__('General Task Notes')); ?></label>
                                                            <textarea name="general_task_notes" id="customer-general-task-notes" class="form-control" rows="3" placeholder="<?php echo e(__('These notes will be automatically filled into new tasks created for this customer')); ?>"></textarea>
                                                            <span class="general_task_notes-error text-danger text-error"></span>
                                                        </div>
                                                    </div>


                                                 </div>
                                             </div>
                                         </div>





                                     </div>
                                     <div class="tab-pane fade" id="navs-justified-profile" role="tabpanel">
                                         <div class="form-group">
                                             <label for="select-template"><?php echo e(__('Select Template')); ?></label>
                                             <select name="template" id="select-template" class="form-select w-auto">
                                                 <option value=""><?php echo e(__('-- Select Template')); ?></option>
                                                 <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                     <option value="<?php echo e($key->id); ?>"
                                                         <?php echo e(optional($customer_template)->value == $key->id ? 'selected' : ''); ?>>
                                                         <?php echo e($key->name); ?></option>
                                                 <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                             </select>
                                         </div>
                                         <div id="additional-form" class="row mt-4">

                                         </div>
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


     <!-- Commissions Management Modal -->
     <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manage_beneficiaries')): ?>
         <div class="modal fade" id="commissionsModal" tabindex="-1" aria-labelledby="commissionsModalTitle"
             aria-hidden="true">
             <div class="modal-dialog modal-xl">
                 <div class="modal-content">
                     <div class="modal-header">
                         <h5 class="modal-title" id="commissionsModalTitle"><?php echo e(__('Manage Commissions')); ?></h5>
                         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                     </div>
                     <form id="commissionsForm">
                         <?php echo csrf_field(); ?>
                         <div class="modal-body">
                             <input type="hidden" id="current_customer_id" name="customer_id">

                             <div class="d-flex justify-content-between align-items-center mb-4">
                                 <h6 class="mb-0"><?php echo e(__('User Commissions')); ?></h6>
                                 <button type="button" id="add-commission" class="btn btn-outline-primary ">
                                     <i class="ti ti-plus me-1"></i>
                                     <?php echo e(__('Add Commission')); ?>

                                 </button>
                             </div>

                             <div id="commissions-container">
                                 <!-- Commissions will be loaded here -->
                             </div>

                             <div class="alert alert-info mt-3">
                                 <i class="ti ti-info-circle me-2"></i>
                                 <?php echo e(__('Note: Total commissions should not exceed the task commission amount when tasks are closed.')); ?>

                             </div>
                         </div>
                         <div class="modal-footer">
                             <button type="button" class="btn btn-secondary"
                                 data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                             <button type="submit" class="btn btn-primary">
                                 <i class="ti ti-device-floppy me-1"></i>
                                 <?php echo e(__('Save Commissions')); ?>

                             </button>
                         </div>
                     </form>
                 </div>
             </div>
         </div>
     <?php endif; ?>


     
    <?php echo $__env->make('admin.partials.signature-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/customers/index.blade.php ENDPATH**/ ?>