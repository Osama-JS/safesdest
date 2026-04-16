 @extends('layouts/layoutMaster')

 @section('title', __('Customers'))

 <!-- Vendor Styles -->
 @section('vendor-style')

     @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

     @vite(['resources/css/app.css'])
 @endsection

 <!-- Vendor Scripts -->
 @section('vendor-script')

     @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
     <script>
         const templateId = {{ $task_template->value ?? 0 }}
     </script>
 @endsection

 <!-- Page Scripts -->
 @section('page-script')
     @vite(['resources/js/admin/customers/customers.js'])
     @vite(['resources/js/ajax.js'])
     @vite(['resources/js/spical.js'])
 @endsection

 @section('content')

     <div class="row g-6 mb-6">
         <div class="col-sm-6 col-xl-3">
             <div class="card">
                 <div class="card-body">
                     <div class="d-flex align-items-start justify-content-between">
                         <div class="content-left">
                             <span class="text-heading">{{ __('Customers') }}</span>
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
                             <span class="text-heading">{{ __('Active Customers') }}</span>
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
                             <span class="text-heading">{{ __('Unverified Customers') }}</span>
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
                             <span class="text-heading">{{ __('Blocked Customers') }}</span>
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
                 {{ __('Customers') }}
             </h5>
             <button class="add-new btn btn-primary waves-effect waves-light mt-5 mx-4" data-bs-toggle="modal"
                 data-bs-target="#submitModal">
                 <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                 <span class="d-none d-sm-inline-block"> {{ __('Add New Customer') }}</span>
             </button>
         </div>
         <div class="card-datatable table-responsive">
             <table class="datatables-users table">
                 <thead class="class="table-light"">
                     <tr>
                         <th></th>
                         <th>#</th>
                         <th>{{ __('name') }}</th>
                         <th>{{ __('email') }}</th>
                         <th>{{ __('phone') }}</th>
                         <th>{{ __('role') }}</th>
                         <th>{{ __('tags') }}</th>
                         <th>{{ __('status') }}</th>
                         <th>{{ __('created at') }}</th>
                         <th>{{ __('actions') }}</th>
                     </tr>
                 </thead>
             </table>
         </div>

     </div>

     <div class="modal fade " id="submitModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
         <div class="modal-dialog modal-xl" role="document">
             <div class="modal-content">
                 <div class="modal-header">
                     <h5 class="modal-title" id="modelTitle">{{ __('Add New Customer') }}</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal"
                         aria-label="{{ __('Close') }}"></button>
                 </div>
                 <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('customers.create') }}"
                     enctype="multipart/form-data">
                     <div class="modal-body">
                         <div class="col-xl-12">
                             <div class="nav-align-top  mb-6">
                                 <ul class="nav nav-tabs " role="tablist">
                                     <li class="nav-item">
                                         <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                             data-bs-target="#navs-justified-home" aria-controls="navs-justified-home"
                                             aria-selected="true"><span class="d-none d-sm-block"><i
                                                     class="tf-icons ti ti-grid-dots ti-sm me-1_5"></i> {{ __('Main') }}
                                         </button>
                                     </li>
                                     <li class="nav-item">
                                         <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                             data-bs-target="#navs-justified-profile"
                                             aria-controls="navs-justified-profile" aria-selected="false"><span
                                                 class="d-none d-sm-block"><i
                                                     class="tf-icons ti ti-file-plus ti-sm me-1_5"></i>
                                                 {{ __('Additional ') }}</span></button>
                                     </li>
                                 </ul>
                                 <div class="tab-content">
                                     <div class="tab-pane fade show active" id="navs-justified-home" role="tabpanel">
                                         <input type="hidden" name="id" id="customer_id" autocomplete="false">
                                         <div class="row">
                                             <div class="col-md-3">
                                                 <div class="mb-6">
                                                     <img src="{{ url(asset('assets/img/person.png')) }}"
                                                         data-image="{{ url(asset('assets/img/person.png')) }}"
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
                                                                 {{ __('Full Name') }}</label>
                                                             <input type="text" class="form-control"
                                                                 id="customer-fullname"
                                                                 placeholder="{{ __('Full Name') }}" name="name"
                                                                 aria-label="{{ __('Full Name') }}" />
                                                             <span class="name-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-6">
                                                         <div class="mb-6">
                                                             <label class="form-label" for="customer-email">*
                                                                 {{ __('Email') }}</label>
                                                             <input type="text" id="customer-email"
                                                                 class="form-control"
                                                                 placeholder="{{ __('example@example.com') }}"
                                                                 aria-label="{{ __('example@example.com') }}"
                                                                 name="email" />
                                                             <span class="email-error text-danger text-error"></span>

                                                         </div>
                                                     </div>
                                                     <div class="col-md-6">
                                                         <div class="mb-6">
                                                             <label class="form-label" for="customer-phone">*
                                                                 {{ __('Phone') }}</label>
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
                                                                     placeholder="{{ __('Enter phone number') }}"
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
                                                                 {{ __('Customer Role') }}</label>
                                                             <select id="customer-role" class="form-select"
                                                                 name="role">
                                                                 <option value="">-- {{ __('Select Role') }}
                                                                 </option>
                                                                 @foreach ($roles as $key)
                                                                     <option value="{{ $key->id }}">
                                                                         {{ $key->name }}</option>
                                                                 @endforeach
                                                             </select>
                                                             <span class="role-error text-danger text-error"></span>
                                                         </div>
                                                     </div>
                                                     <div class="col-md-6">
                                                         <div class="mb-6">
                                                             <label class="form-label" for="customer-password">*
                                                                 {{ __('Password') }}</label>
                                                             <input type="password" id="customer-password"
                                                                 class="form-control" name="password" />
                                                             <span class="password-error text-danger text-error"></span>

                                                         </div>
                                                     </div>
                                                     <div class="col-md-6">
                                                         <div class="mb-6">
                                                             <label class="form-label" for="customer-re-password">*
                                                                 {{ __('Confirm Password') }}</label>
                                                             <input type="password" id="customer-re-password"
                                                                 class="form-control" name="confirm-password" />
                                                             <span
                                                                 class="confirm-password-error text-danger text-error"></span>
                                                         </div>
                                                     </div>
                                                     <div class="divider text-start">
                                                         <div class="divider-text">
                                                             <strong>{{ __('Company Info') }}</strong>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-12 mb-4">
                                                         <div class="form-check form-switch card-p">
                                                             <input class="form-check-input" type="checkbox" name="is_company" id="customer-is-company" value="1">
                                                             <label class="form-check-label fw-bold" for="customer-is-company">
                                                                 {{ __('Mark as B2B Company') }}
                                                             </label>
                                                         </div>
                                                         <small class="text-muted">{{ __('Enables B2B specific features, warehouses and multiple end-clients.') }}</small>
                                                     </div>


                                                     <div class="col-md-6">
                                                         <div class="mb-4">
                                                             <label class="form-label" for="customer-c_name">
                                                                 {{ __('Company Name') }}</label>
                                                             <input type="text" name="c_name" class="form-control"
                                                                 id="customer-c_name"
                                                                 placeholder="{{ __('enter company name') }}" />
                                                             <span class="c_name-error text-danger text-error"></span>
                                                         </div>
                                                     </div>
                                                     <div class="col-md-6">
                                                         <div class="mb-4">
                                                             <label class="form-label" for="customer-c_address">
                                                                 {{ __('Company Address') }}</label>
                                                             <input type="text" name="c_address" class="form-control"
                                                                 id="customer-c_address"
                                                                 placeholder="{{ __('enter company address') }}" />
                                                             <span class="c_address-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-6">
                                                         <div class="mb-4">
                                                             <label class="form-label" for="customer-policy-file">
                                                                 {{ __('Policy File Name') }}</label>
                                                             <input type="text" name="policy_file_name" class="form-control"
                                                                 id="customer-policy-file"
                                                                 placeholder="{{ __('config.file_name_blade') }}" />
                                                             <span class="policy_file_name-error text-danger text-error"></span>
                                                         </div>
                                                     </div>


                                                     <div class="col-md-6">
                                                         <div class="  mb-6">
                                                             <label class="form-label"
                                                                 for="customer-tags">{{ __('Tags') }}</label>
                                                             <select name="tags[]" id="customer-tags"
                                                                 class="select2 form-select" multiple>
                                                                 <option value=""></option>
                                                                 @foreach ($tags as $key)
                                                                     <option value="{{ $key->id }}">
                                                                         {{ $key->name }}
                                                                     </option>
                                                                 @endforeach
                                                             </select>
                                                             <span class="tags-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                     <!-- Bank Details Section -->
                                                     <div class="col-md-12">
                                                         <div class="divider text-start">
                                                             <div class="divider-text">
                                                                 <strong><i
                                                                         class="ti ti-building-bank me-2"></i>{{ __('Bank Details') }}</strong>
                                                             </div>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-4">
                                                         <div class="mb-4">
                                                             <label class="form-label"
                                                                 for="customer-bank-name">{{ __('Bank Name') }}</label>
                                                             <select name="bank_name" id="customer-bank-name"
                                                                 class="form-select">
                                                                 <option value="">{{ __('Select Bank') }}</option>
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
                                                                 <option value="other">{{ __('Other') }}</option>
                                                             </select>
                                                             <span class="bank_name-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-4" id="customer-custom-bank-field"
                                                         style="display: none;">
                                                         <div class="mb-4">
                                                             <label class="form-label"
                                                                 for="customer-custom-bank-name">{{ __('Custom Bank Name') }}</label>
                                                             <input type="text" name="custom_bank_name"
                                                                 id="customer-custom-bank-name" class="form-control"
                                                                 placeholder="{{ __('Enter bank name') }}">
                                                             <span
                                                                 class="custom_bank_name-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-4">
                                                         <div class="mb-4">
                                                             <label class="form-label"
                                                                 for="customer-account-number">{{ __('Account Number') }}</label>
                                                             <input type="text" name="account_number"
                                                                 id="customer-account-number" class="form-control"
                                                                 placeholder="1234567890" pattern="[0-9]{8,20}"
                                                                 minlength="8" maxlength="20">
                                                             <div class="form-text">
                                                                 <small
                                                                     class="text-muted">{{ __('Numbers only, 8-20 digits') }}</small>
                                                             </div>
                                                             <span
                                                                 class="account_number-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                     <div class="col-md-4">
                                                         <div class="mb-4">
                                                             <label class="form-label"
                                                                 for="customer-iban-number">{{ __('IBAN Number') }}</label>
                                                             <input type="text" name="iban_number"
                                                                 id="customer-iban-number" class="form-control"
                                                                 placeholder="SA12 3456 7890 1234 5678 90" maxlength="29"
                                                                 pattern="SA(?:[0-9]{2}\s?){11}">
                                                             <div class="form-text">
                                                                 <small
                                                                     class="text-muted">{{ __('Format: SA + 22 digits') }}</small>
                                                             </div>
                                                             <span class="iban_number-error text-danger text-error"></span>
                                                         </div>
                                                     </div>

                                                    <!-- Task Numbering Section -->
                                                    <div class="col-md-12">
                                                        <div class="divider text-start">
                                                            <div class="divider-text">
                                                                <strong><i
                                                                        class="ti ti-hash me-2"></i>{{ __('Task Numbering') }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="customer-task-number-start">{{ __('Task Number Start') }}</label>
                                                            <input type="number" name="task_number_start"
                                                                id="customer-task-number-start" class="form-control"
                                                                placeholder="{{ __('e.g. 3000') }}" min="1">
                                                            <div class="form-text">
                                                                <small
                                                                    class="text-muted">{{ __('Starting number for custom task numbering') }}</small>
                                                            </div>
                                                            <span class="task_number_start-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="customer-task-number-next">{{ __('Next Task Number') }}</label>
                                                            <input type="number"
                                                                id="customer-task-number-next" class="form-control"
                                                                readonly disabled>
                                                            <div class="form-text">
                                                                <small
                                                                    class="text-muted">{{ __('Auto-calculated, read only') }}</small>
                                                            </div>
                                                        </div>
                                                    </div>


                                                 </div>
                                             </div>
                                         </div>





                                     </div>
                                     <div class="tab-pane fade" id="navs-justified-profile" role="tabpanel">
                                         <div class="form-group">
                                             <label for="select-template">{{ __('Select Template') }}</label>
                                             <select name="template" id="select-template" class="form-select w-auto">
                                                 <option value="">{{ __('-- Select Template') }}</option>
                                                 @foreach ($templates as $key)
                                                     <option value="{{ $key->id }}"
                                                         {{ $customer_template->value == $key->id ? 'selected' : '' }}>
                                                         {{ $key->name }}</option>
                                                 @endforeach
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
                             data-bs-dismiss="modal">{{ __('Close') }}</button>
                         <button type="submit" class="btn btn-primary me-3 data-submit">{{ __('Submit') }}</button>

                     </div>
                 </form>

             </div>
         </div>
     </div>


     <!-- Commissions Management Modal -->
     @can('manage_beneficiaries')
         <div class="modal fade" id="commissionsModal" tabindex="-1" aria-labelledby="commissionsModalTitle"
             aria-hidden="true">
             <div class="modal-dialog modal-xl">
                 <div class="modal-content">
                     <div class="modal-header">
                         <h5 class="modal-title" id="commissionsModalTitle">{{ __('Manage Commissions') }}</h5>
                         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                     </div>
                     <form id="commissionsForm">
                         @csrf
                         <div class="modal-body">
                             <input type="hidden" id="current_customer_id" name="customer_id">

                             <div class="d-flex justify-content-between align-items-center mb-4">
                                 <h6 class="mb-0">{{ __('User Commissions') }}</h6>
                                 <button type="button" id="add-commission" class="btn btn-outline-primary ">
                                     <i class="ti ti-plus me-1"></i>
                                     {{ __('Add Commission') }}
                                 </button>
                             </div>

                             <div id="commissions-container">
                                 <!-- Commissions will be loaded here -->
                             </div>

                             <div class="alert alert-info mt-3">
                                 <i class="ti ti-info-circle me-2"></i>
                                 {{ __('Note: Total commissions should not exceed the task commission amount when tasks are closed.') }}
                             </div>
                         </div>
                         <div class="modal-footer">
                             <button type="button" class="btn btn-secondary"
                                 data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                             <button type="submit" class="btn btn-primary">
                                 <i class="ti ti-device-floppy me-1"></i>
                                 {{ __('Save Commissions') }}
                             </button>
                         </div>
                     </form>
                 </div>
             </div>
         </div>
     @endcan


     {{-- Include Signature Modal --}}
    @include('admin.partials.signature-modal')

@endsection
