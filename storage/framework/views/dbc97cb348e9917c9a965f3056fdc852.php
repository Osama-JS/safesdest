<?php $__env->startSection('title', __('Investor Management')); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <script>
        window.customers = <?php echo json_encode($customers, 15, 512) ?>;
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/admin/investors.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span><?php echo e(__('Total Investors')); ?></span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2" id="total-investors">0</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-users ti-md"></i>
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
                            <span><?php echo e(__('Active Investors')); ?></span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2" id="active-investors">0</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user-check ti-md"></i>
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
                            <span><?php echo e(__('Task-based Investment')); ?></span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2" id="task-based-investors">0</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-settings ti-md"></i>
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
                            <span><?php echo e(__('General Investment')); ?></span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2" id="general-based-investors">0</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-chart-bar ti-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0"><?php echo e(__('Investors List')); ?></h5>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#exportInvestorsModal" id="btn-export-investors">
                    <i class="ti ti-file-spreadsheet me-1"></i> <?php echo e(__('Comprehensive Excel Report')); ?>

                </button>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('save_investors')): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#investorModal" id="btn-add-investor">
                        <i class="ti ti-plus me-1"></i> <?php echo e(__('Add New Investor')); ?>

                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-investors table border-top">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?php echo e(__('Investor')); ?></th>
                        <th><?php echo e(__('Email')); ?></th>
                        <th><?php echo e(__('Wallet Balance')); ?></th>
                        <th><?php echo e(__('Contract Type')); ?></th>
                        <th><?php echo e(__('Commission')); ?></th>
                        <th><?php echo e(__('Status')); ?></th>
                        <th><?php echo e(__('Reset Password')); ?></th>
                        <th><?php echo e(__('Actions')); ?></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('save_investors')): ?>
        <div class="modal fade" id="investorModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-transparent">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-sm-5 pb-5">
                        <div class="text-center mb-4">
                            <h3 class="mb-2" id="modalTitle"><?php echo e(__('Add New Investor')); ?></h3>
                            <p class="text-muted"><?php echo e(__('Manage investor data and active contract settings')); ?></p>
                        </div>
                        <form id="investorForm" class="row g-3 form_submit" onsubmit="return false" method="POST" action="<?php echo e(route('admin.investors.store')); ?>" enctype="multipart/form-data">
                            <input type="hidden" name="id" id="investor_id">
                            
                            <div class="nav-align-top mb-6">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                            data-bs-target="#navs-main" aria-controls="navs-main"
                                            aria-selected="true">
                                            <i class="tf-icons ti ti-grid-dots ti-sm me-1"></i> <?php echo e(__('Main')); ?>

                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                            data-bs-target="#navs-bank" aria-controls="navs-bank"
                                            aria-selected="false">
                                            <i class="tf-icons ti ti-building-bank ti-sm me-1"></i> <?php echo e(__('Banking')); ?>

                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                            data-bs-target="#navs-additional" aria-controls="navs-additional"
                                            aria-selected="false">
                                            <i class="tf-icons ti ti-file-plus ti-sm me-1"></i> <?php echo e(__('Additional')); ?>

                                        </button>
                                    </li>
                                </ul>
                                <div class="tab-content border-0 p-0 pt-4">
                                    <div class="tab-pane fade show active" id="navs-main" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-12"><h5 class="border-bottom pb-2"><?php echo e(__('Basic Information')); ?></h5></div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Full Name')); ?></label>
                                                <input type="text" name="name" class="form-control" placeholder="<?php echo e(__('Enter name')); ?>">
                                                <span class="name-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Email')); ?></label>
                                                <input type="email" name="email" class="form-control" placeholder="example@mail.com">
                                                <span class="email-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Phone Number')); ?></label>
                                                <div class="input-group">
                                                    <select name="phone_code" class="form-select" style="max-width: 100px;">
                                                        <option value="+966">🇸🇦 +966</option>
                                                        <option value="+971">🇦🇪 +971</option>
                                                        <option value="+20">🇪🇬 +20</option>
                                                        <option value="+1">🇺🇸 +1</option>
                                                    </select>
                                                    <input type="text" name="phone" class="form-control" placeholder="5xxxxxxxx">
                                                </div>
                                                <span class="phone-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label"><?php echo e(__('Password')); ?></label>
                                                <input type="password" name="password" class="form-control" placeholder="••••••••">
                                                <span class="password-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label"><?php echo e(__('Confirm Password')); ?></label>
                                                <input type="password" name="password_confirmation" class="form-control" placeholder="••••••••">
                                                <span class="password_confirmation-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-12">
                                                <small class="text-muted" id="pass-hint"><?php echo e(__('Leave blank when editing to keep unchanged')); ?></small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Status')); ?></label>
                                                <select name="status" class="form-select">
                                                    <option value="active"><?php echo e(__('Active')); ?></option>
                                                    <option value="inactive"><?php echo e(__('Inactive')); ?></option>
                                                    <option value="pending"><?php echo e(__('Pending Review')); ?></option>
                                                </select>
                                                <span class="status-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-12 mt-4"><h5 class="border-bottom pb-2"><?php echo e(__('Investment Contract Settings')); ?></h5></div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Investment Type')); ?></label>
                                                <select name="contract_type" class="form-select" id="contract_type">
                                                    <option value="task_investment"><?php echo e(__('Task investment (manual payment per task)')); ?></option>
                                                    <option value="general_investment"><?php echo e(__('General investment (periodic cumulative commissions)')); ?></option>
                                                </select>
                                                <span class="contract_type-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label"><?php echo e(__('Commission Type')); ?></label>
                                                <select name="commission_type" class="form-select">
                                                    <option value="percentage"><?php echo e(__('Percentage (%)')); ?></option>
                                                    <option value="fixed"><?php echo e(__('Fixed Amount (SAR)')); ?></option>
                                                </select>
                                                <span class="commission_type-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label"><?php echo e(__('Commission Value')); ?></label>
                                                <input type="number" step="0.01" name="commission_value" class="form-control" placeholder="0.00">
                                                <span class="commission_value-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Contract Start Date')); ?></label>
                                                <input type="date" name="start_date" class="form-control" value="<?php echo e(date('Y-m-d')); ?>">
                                                <span class="start_date-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Contract End Date (optional)')); ?></label>
                                                <input type="date" name="end_date" class="form-control">
                                                <span class="end_date-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Filter by Customers (optional)')); ?></label>
                                                <select name="customer_ids[]" class="form-select select2" multiple id="customer_ids">
                                                    <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($customer->id); ?>"><?php echo e($customer->name); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                                <small class="text-muted"><?php echo e(__('Leave empty for all customers')); ?></small>
                                                <span class="customer_ids-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Minimum Platform Commission (optional)')); ?></label>
                                                <input type="number" step="0.01" name="min_commission_threshold" class="form-control" placeholder="0.00">
                                                <small class="text-muted"><?php echo e(__('Tasks below this platform commission are not eligible')); ?></small>
                                                <span class="min_commission_threshold-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-12 mt-4"><h5 class="border-bottom pb-2"><?php echo e(__('Broker Settings (optional)')); ?></h5></div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Broker')); ?></label>
                                                <select name="broker_id" class="form-select select2" id="broker_id">
                                                    <option value=""><?php echo e(__('No Broker')); ?></option>
                                                    <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($user->id); ?>"><?php echo e($user->name); ?> (<?php echo e($user->email); ?>)</option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                                <span class="broker_id-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Broker Commission Source')); ?></label>
                                                <select name="broker_commission_source" class="form-select">
                                                    <option value="investor_commission"><?php echo e(__('From investor profit commission')); ?></option>
                                                    <option value="task_commission"><?php echo e(__('From total task commission (platform bears)')); ?></option>
                                                </select>
                                                <span class="broker_commission_source-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Broker Commission Calculation')); ?></label>
                                                <select name="broker_commission_type" class="form-select">
                                                    <option value="percentage"><?php echo e(__('Percentage (%)')); ?></option>
                                                    <option value="fixed"><?php echo e(__('Fixed Amount (SAR)')); ?></option>
                                                </select>
                                                <span class="broker_commission_type-error text-danger text-error"></span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo e(__('Broker Commission Value')); ?></label>
                                                <input type="number" step="0.01" name="broker_commission_value" class="form-control" placeholder="0.00">
                                                <span class="broker_commission_value-error text-danger text-error"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="navs-bank" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-12"><h5 class="border-bottom pb-2"><?php echo e(__('Banking Information')); ?></h5></div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-name"><?php echo e(__('Bank Name')); ?></label>
                                                <select id="user-bank-name" name="bank_name" class="form-select">
                                                    <option value=""><?php echo e(__('Select Bank')); ?></option>
                                                    <option value="البنك الأهلي السعودي">البنك الأهلي السعودي (SNB)</option>
                                                    <option value="مصرف الراجحي">مصرف الراجحي (Al Rajhi)</option>
                                                    <option value="بنك الرياض">بنك الرياض (Riyad Bank)</option>
                                                    <option value="البنك السعودي الأول">البنك السعودي الأول (SAB)</option>
                                                    <option value="بنك البلاد">بنك البلاد (Albilad)</option>
                                                    <option value="مصرف الإنماء">مصرف الإنماء (Alinma)</option>
                                                    <option value="البنك السعودي للاستثمار">البنك السعودي للاستثمار (SAIB)</option>
                                                    <option value="البنك العربي الوطني">البنك العربي الوطني (ANB)</option>
                                                    <option value="بنك الجزيرة">بنك الجزيرة (Aljazira)</option>
                                                    <option value="البنك السعودي الفرنسي">البنك السعودي الفرنسي (BSF)</option>
                                                    <option value="other"><?php echo e(__('Other')); ?></option>
                                                </select>
                                                <span class="bank_name-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6" id="user-custom-bank-field" style="display: none;">
                                                <label class="form-label" for="user-custom-bank-name"><?php echo e(__('Custom Bank Name')); ?></label>
                                                <input type="text" id="user-custom-bank-name" name="custom_bank_name" class="form-control" placeholder="<?php echo e(__('Enter bank name')); ?>">
                                                <span class="custom_bank_name-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-account-number"><?php echo e(__('Account Number')); ?></label>
                                                <input type="text" id="user-account-number" name="account_number" class="form-control" placeholder="<?php echo e(__('Enter account number')); ?>">
                                                <span class="account_number-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-iban-number"><?php echo e(__('IBAN Number')); ?></label>
                                                <input type="text" id="user-iban-number" name="iban_number" class="form-control" placeholder="SA00 0000 0000 0000 0000 0000" dir="ltr">
                                                <span class="iban_number-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bic-code"><?php echo e(__('BIC/SWIFT Code')); ?></label>
                                                <input type="text" id="user-bic-code" name="bic_code" class="form-control" placeholder="<?php echo e(__('Enter BIC code')); ?>">
                                                <span class="bic_code-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-beneficiary-name"><?php echo e(__('Beneficiary Name (official)')); ?></label>
                                                <input type="text" id="user-beneficiary-name" name="beneficiary_name" class="form-control" placeholder="<?php echo e(__('Enter beneficiary name as in bank')); ?>">
                                                <span class="beneficiary_name-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-address1"><?php echo e(__('Bank Address 1')); ?></label>
                                                <input type="text" id="user-bank-address1" name="bank_address1" class="form-control" placeholder="<?php echo e(__('Bank Address 1')); ?>">
                                                <span class="bank_address1-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-address2"><?php echo e(__('Bank Address 2')); ?></label>
                                                <input type="text" id="user-bank-address2" name="bank_address2" class="form-control" placeholder="<?php echo e(__('Bank Address 2')); ?>">
                                                <span class="bank_address2-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-city"><?php echo e(__('City')); ?></label>
                                                <input type="text" id="user-bank-city" name="bank_city" class="form-control" placeholder="<?php echo e(__('Enter city')); ?>">
                                                <span class="bank_city-error text-danger text-error"></span>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label" for="user-bank-country"><?php echo e(__('Country')); ?></label>
                                                <select id="user-bank-country" name="bank_country" class="form-select">
                                                    <option value="السعودية">🇸🇦 المملكة العربية السعودية</option>
                                                    <option value="الإمارات">🇦🇪 الإمارات العربية المتحدة</option>
                                                    <option value="الكويت">🇰🇼 الكويت</option>
                                                    <option value="عمان">🇴🇲 عمان</option>
                                                    <option value="البحرين">🇧🇭 البحرين</option>
                                                    <option value="قطر">🇶🇦 قطر</option>
                                                    <option value="مصر">🇪🇬 مصر</option>
                                                    <option value="الأردن">🇯🇴 الأردن</option>
                                                    <option value="أخرى">🌐 أخرى</option>
                                                </select>
                                                <span class="bank_country-error text-danger text-error"></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="tab-pane fade" id="navs-additional" role="tabpanel">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label"><?php echo e(__('Select Template')); ?></label>
                                                <select name="template" id="select-template" class="form-select">
                                                    <option value=""><?php echo e(__('Choose template')); ?></option>
                                                    <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($template->id); ?>"><?php echo e($template->name); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                            <div id="additional-form" class="row mt-4">
                                                <!-- سيتم تعبئتها ديناميكياً -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit"><?php echo e(__('Save Data')); ?></button>
                                <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close"><?php echo e(__('Cancel')); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    
    <div class="modal fade" id="viewInvestorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-sm-5 pb-5">
                    <div class="text-center mb-4">
                        <h3 class="mb-2"><?php echo e(__('Investor Details')); ?></h3>
                        <p class="text-muted"><?php echo e(__('Full personal and financial data')); ?></p>
                    </div>
                    
                    <div class="row g-4">
                        <!-- Personal Info -->
                        <div class="col-md-6">
                            <div class="info-container">
                                <h5 class="border-bottom pb-2"><?php echo e(__('Personal Information')); ?></h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2"><span class="fw-medium me-1"><?php echo e(__('Name')); ?>:</span> <span id="view-name"></span></li>
                                    <li class="mb-2"><span class="fw-medium me-1"><?php echo e(__('Email')); ?>:</span> <span id="view-email"></span></li>
                                    <li class="mb-2"><span class="fw-medium me-1"><?php echo e(__('Mobile')); ?>:</span> <span id="view-phone"></span></li>
                                    <li class="mb-2"><span class="fw-medium me-1"><?php echo e(__('Status')); ?>:</span> <span id="view-status"></span></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Wallets Info -->
                        <div class="col-md-6">
                            <div class="info-container">
                                <h5 class="border-bottom pb-2"><?php echo e(__('Financial Wallets')); ?></h5>
                                <div class="d-flex align-items-center mb-3 p-2 border rounded bg-light">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-wallet"></i></span>
                                    </div>
                                    <div>
                                        <small class="d-block text-muted"><?php echo e(__('Investment Wallet (available balance)')); ?></small>
                                        <h5 class="mb-0 text-primary" id="view-invest-balance">0.00 <?php echo e(__('SAR')); ?></h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center p-2 border rounded bg-light">
                                    <div class="avatar me-3">
                                        <span class="avatar-initial rounded bg-label-success"><i class="ti ti-coins"></i></span>
                                    </div>
                                    <div>
                                        <small class="d-block text-muted"><?php echo e(__('Commission Wallet (personal balance)')); ?></small>
                                        <h5 class="mb-0 text-success" id="view-commission-balance">0.00 <?php echo e(__('SAR')); ?></h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contract Info -->
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mt-2"><?php echo e(__('Active Contract')); ?></h5>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?php echo e(__('Type')); ?></th>
                                            <th><?php echo e(__('Commission')); ?></th>
                                            <th><?php echo e(__('Start Date')); ?></th>
                                            <th><?php echo e(__('End Date')); ?></th>
                                            <th><?php echo e(__('Minimum')); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td id="view-contract-type"></td>
                                            <td id="view-contract-commission"></td>
                                            <td id="view-contract-start"></td>
                                            <td id="view-contract-end"></td>
                                            <td id="view-contract-min"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="linkTasksModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-sm-5 pb-5">
                    <div class="text-center mb-4">
                        <h3 class="mb-2"><?php echo e(__('Link Historical Tasks')); ?></h3>
                        <p class="text-muted"><?php echo e(__('Select tasks previously funded by the investor')); ?></p>
                        <h5 id="investor-name-modal" class="text-primary mt-2"></h5>
                    </div>
                    
                    <div class="alert alert-warning mb-4">
                        <div class="d-flex">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <div>
                                <?php echo e(__('Note: selected task amounts will be debited from the investment wallet and investor commission recorded (for task-based contracts).')); ?>

                            </div>
                        </div>
                    </div>

                    <div class="table-responsive border rounded" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover" id="historicalTasksTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th><input type="checkbox" class="form-check-input" id="selectAllTasks"></th>
                                    <th><?php echo e(__('Task #')); ?></th>
                                    <th><?php echo e(__('Customer')); ?></th>
                                    <th><?php echo e(__('Driver')); ?></th>
                                    <th><?php echo e(__('Truck')); ?></th>
                                    <th><?php echo e(__('Route (from - to)')); ?></th>
                                    <th><?php echo e(__('Total Amount')); ?></th>
                                    <th><?php echo e(__('Date')); ?></th>
                                </tr>
                            </thead>
                            <tbody id="historicalTasksBody">
                                <!-- سيتم تعبئتها عبر AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <div class="col-12 text-center mt-4">
                        <button type="button" class="btn btn-primary me-sm-3 me-1" id="btnLinkTasks"><?php echo e(__('Link Selected Tasks')); ?></button>
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="modal fade" id="exportInvestorsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-transparent">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-sm-5 pb-5">
                    <div class="text-center mb-4">
                        <div class="avatar avatar-lg bg-label-success mx-auto mb-3">
                            <span class="avatar-initial rounded-circle"><i class="ti ti-file-spreadsheet fs-2"></i></span>
                        </div>
                        <h3 class="mb-2"><?php echo e(__('Comprehensive Investors Report')); ?></h3>
                        <p class="text-muted"><?php echo e(__('Export all investment wallets, commission wallets, and statistics into a single Excel file.')); ?></p>
                    </div>

                    <form id="exportInvestorsForm" method="GET" action="<?php echo e(route('admin.investors.export-all-excel')); ?>">
                        <div class="row g-3">
                            
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label mb-0" for="export_investor_ids"><?php echo e(__('Investors')); ?></label>
                                    <button type="button" class="btn btn-xs btn-label-primary" id="btn-toggle-all-investors">
                                        <i class="ti ti-checks me-1"></i><?php echo e(__('Select All / Clear')); ?>

                                    </button>
                                </div>
                                <select name="investor_ids[]" id="export_investor_ids" class="form-select select2" multiple="multiple">
                                    <?php
                                        $allInvestors = \App\Models\User::where('investor', true)->where('status', '!=', 'deleted')->orderBy('name')->get();
                                    ?>
                                    <?php $__currentLoopData = $allInvestors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($inv->id); ?>"><?php echo e($inv->name); ?> (<?php echo e($inv->phone ?? $inv->email); ?>)</option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <small class="text-muted d-block mt-1"><?php echo e(__('Leave empty to export for all investors, or select multiple investors.')); ?></small>
                            </div>

                            
                            <div class="col-md-6">
                                <label class="form-label" for="export_from_date"><?php echo e(__('From Date')); ?></label>
                                <input type="date" name="from_date" id="export_from_date" class="form-control">
                            </div>

                            
                            <div class="col-md-6">
                                <label class="form-label" for="export_to_date"><?php echo e(__('To Date')); ?></label>
                                <input type="date" name="to_date" id="export_to_date" class="form-control">
                            </div>

                            
                            <div class="col-12">
                                <label class="form-label text-muted small d-block mb-2"><?php echo e(__('Quick Date Presets')); ?></label>
                                <div class="btn-group btn-group-sm w-100 flex-wrap" role="group">
                                    <button type="button" class="btn btn-outline-secondary btn-preset-date" data-preset="all"><?php echo e(__('All Periods')); ?></button>
                                    <button type="button" class="btn btn-outline-secondary btn-preset-date" data-preset="today"><?php echo e(__('Today')); ?></button>
                                    <button type="button" class="btn btn-outline-secondary btn-preset-date" data-preset="this_month"><?php echo e(__('This Month')); ?></button>
                                    <button type="button" class="btn btn-outline-secondary btn-preset-date" data-preset="last_month"><?php echo e(__('Last Month')); ?></button>
                                    <button type="button" class="btn btn-outline-secondary btn-preset-date" data-preset="this_year"><?php echo e(__('This Year')); ?></button>
                                </div>
                            </div>

                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-success me-sm-3 me-1" id="btnSubmitExport">
                                    <i class="ti ti-download me-1"></i> <?php echo e(__('Download Excel Report')); ?>

                                </button>
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/investors/index.blade.php ENDPATH**/ ?>