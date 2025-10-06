 @extends('layouts/layoutMaster')

 @section('title', __('Products'))

 <!-- Vendor Styles -->
 @section('vendor-style')
     <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
     <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css"
         rel="stylesheet" />
     @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

     @vite(['resources/css/app.css'])
 @endsection

 <!-- Vendor Scripts -->
 @section('vendor-script')
     <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
     <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>

     @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
     <script>
         const templateId = {{ $task_template->value ?? 0 }}
     </script>
 @endsection

 <!-- Page Scripts -->
 @section('page-script')
     @vite(['resources/js/admin/products/products.js'])
     @vite(['resources/js/ajax.js'])
     @vite(['resources/js/spical.js'])
 @endsection
 @section('products-isactive')
     active
 @endsection


 @section('content')

     <div class="row g-6 mb-6">
         <div class="col-sm-6 col-xl-4">
             <div class="card">
                 <div class="card-body">
                     <div class="d-flex align-items-start justify-content-between">
                         <div class="content-left">
                             <span class="text-heading">{{ __('Products') }}</span>
                             <div class="d-flex align-items-center my-1">
                                 <h4 class="mb-0 me-2" id="total"></h4>
                             </div>

                         </div>
                         <div class="avatar">
                             <span class="avatar-initial rounded bg-label-primary">
                                 <i class="ti ti-package ti-26px"></i>
                             </span>
                         </div>
                     </div>
                 </div>
             </div>
         </div>
         <div class="col-sm-6 col-xl-4">
             <div class="card">
                 <div class="card-body">
                     <div class="d-flex align-items-start justify-content-between">
                         <div class="content-left">
                             <span class="text-heading">{{ __('Active Products') }}</span>
                             <div class="d-flex align-items-center my-1">
                                 <h4 class="mb-0 me-2" id="total-active"></h4>
                                 <p class="text-success mb-0">
                                 </p>
                             </div>

                         </div>
                         <div class="avatar">
                             <span class="avatar-initial rounded bg-label-success">
                                 <i class="ti ti-package  ti-26px"></i>
                             </span>
                         </div>
                     </div>
                 </div>
             </div>
         </div>

         <div class="col-sm-6 col-xl-4">
             <div class="card">
                 <div class="card-body">
                     <div class="d-flex align-items-start justify-content-between">
                         <div class="content-left">
                             <span class="text-heading">{{ __('Blocked Products') }}</span>
                             <div class="d-flex align-items-center my-1">
                                 <h4 class="mb-0 me-2" id="total-blocked"></h4>
                                 <p class="text-success mb-0">
                                 </p>

                                 </p>
                             </div>

                         </div>
                         <div class="avatar">
                             <span class="avatar-initial rounded bg-label-warning">
                                 <i class="ti ti-package  ti-26px"></i>
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
                 <i class="tf-icons ti ti-package  me-2 fs-3 text-white bg-primary rounded p-1"></i>
                 {{ __('Products') }}
             </h5>
             <button class="add-new btn btn-primary waves-effect waves-light mt-5 mx-4" data-bs-toggle="modal"
                 data-bs-target="#submitModal">
                 <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                 <span class="d-none d-sm-inline-block"> {{ __('Add New Products') }}</span>
             </button>
         </div>
         <div class="card-datatable table-responsive">
             <table class="datatables-users table">
                 <thead class="class="table-light"">
                     <tr>
                         <th></th>
                         <th>#</th>
                         <th>{{ __('image') }}</th>
                         <th>{{ __('name') }}</th>
                         <th>{{ __('code') }}</th>
                         <th>{{ __('default price') }}</th>
                         <th>{{ __('minimum order') }}</th>
                         <th>{{ __('increase') }}</th>
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
                     <h5 class="modal-title" id="modelTitle">{{ __('Add New Products') }}</h5>
                     <button type="button" class="btn-close" data-bs-dismiss="modal"
                         aria-label="{{ __('Close') }}"></button>
                 </div>
                 <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('products.create') }}"
                     enctype="multipart/form-data">
                     @csrf
                     <div class="modal-body">
                         <div class="col-xl-12">

                             <!-- Main Tab -->
                             <input type="hidden" name="id" id="product_id" autocomplete="false">
                             <div class="row">
                                 <div class="col-md-3">
                                     <div class="mb-6">
                                         <img src="{{ url(asset('assets/img/placeholder.jpg')) }}"
                                             data-image="{{ url(asset('assets/img/placeholder.jpg')) }}" alt=""
                                             id="image" style="width: 100%; height: 222px; object-fit: cover;"
                                             class="rounded preview-image image-input">
                                         <input type="file" class="form-control file-input-image" id="product-image"
                                             name="image" style="display: none" />
                                         <span class="image-error text-danger text-error"></span>
                                     </div>
                                 </div>
                                 <div class="col-md-9">
                                     <div class="row">
                                         <div class="col-md-6">
                                             <div class="mb-6">
                                                 <label class="form-label" for="product-name">*
                                                     {{ __('Product Name') }}</label>
                                                 <input type="text" class="form-control" id="product-name"
                                                     placeholder="{{ __('Product Name') }}" name="name"
                                                     aria-label="{{ __('Product Name') }}" />
                                                 <span class="name-error text-danger text-error"></span>
                                             </div>
                                         </div>
                                         <div class="col-md-6">
                                             <div class="mb-6">
                                                 <label class="form-label" for="product-code">*
                                                     {{ __('Product Code') }}</label>
                                                 <input type="text" id="product-code" class="form-control"
                                                     placeholder="{{ __('Product Code') }}"
                                                     aria-label="{{ __('Product Code') }}" name="code" />
                                                 <span class="code-error text-danger text-error"></span>
                                             </div>
                                         </div>
                                         <div class="col-md-6">
                                             <div class="mb-6">
                                                 <label class="form-label" for="product-min-order">*
                                                     {{ __('Minimum Order') }}</label>
                                                 <input type="number" step="0.01" id="product-min-order"
                                                     class="form-control" placeholder="{{ __('Minimum Order') }}"
                                                     name="min" />
                                                 <span class="min-error text-danger text-error"></span>
                                             </div>
                                         </div>
                                         <div class="col-md-6">
                                             <div class="mb-6">
                                                 <label class="form-label" for="product-increase">*
                                                     {{ __('Increase') }}</label>
                                                 <input type="number" step="0.01" id="product-increase"
                                                     class="form-control" placeholder="{{ __('Increase') }}"
                                                     name="increase" />
                                                 <span class="increase-error text-danger text-error"></span>
                                             </div>
                                         </div>
                                         <div class="col-md-6">
                                             <div class="mb-6">
                                                 <label class="form-label" for="product-unit">*
                                                     {{ __('Unit') }}</label>
                                                 <select id="product-unit" class="form-select" name="unit">
                                                     <option value="">-- {{ __('Select Unit') }} --
                                                     </option>
                                                     <option value="kg">{{ __('Kilogram') }} (kg)</option>
                                                     <option value="ton">{{ __('Ton') }} (ton)</option>
                                                 </select>
                                                 <span class="unit-error text-danger text-error"></span>
                                             </div>
                                         </div>
                                         <div class="col-md-6">
                                             <div class="mb-6">
                                                 <label class="form-label" for="product-price">*
                                                     {{ __('Default Price') }}</label>
                                                 <input type="number" step="0.01" id="product-price"
                                                     class="form-control" placeholder="{{ __('Default Price') }}"
                                                     name="price" />
                                                 <span class="price-error text-danger text-error"></span>
                                             </div>
                                         </div>
                                         <div class="col-md-12">
                                             <div class="mb-6">
                                                 <label class="form-label" for="product-description">
                                                     {{ __('Description') }}</label>
                                                 <textarea class="form-control" id="product-description" rows="3"
                                                     placeholder="{{ __('Product Description') }}" name="description"></textarea>
                                                 <span class="description-error text-danger text-error"></span>
                                             </div>
                                         </div>

                                     </div>
                                 </div>
                             </div>
                             <div class="row">
                                 <div class="row col-md-5">

                                     <div class="p-3">
                                         <div class="divider text-start">
                                             <div class="divider-text">
                                                 <strong>{{ __('Contact Information') }}</strong>
                                             </div>
                                         </div>
                                         <div class="row">
                                             <div class="col-md-12">
                                                 <div class="mb-6">
                                                     <label class="form-label" for="product-contact-name">
                                                         {{ __('Contact Name') }}</label>
                                                     <input type="text" class="form-control" id="product-contact-name"
                                                         placeholder="{{ __('Contact Name') }}" name="contact_name" />
                                                     <span class="contact_name-error text-danger text-error"></span>
                                                 </div>
                                             </div>
                                             <div class="col-md-12">
                                                 <div class="mb-6">
                                                     <label class="form-label" for="product-contact-phone">
                                                         {{ __('Contact Phone') }}</label>
                                                     <input type="text" class="form-control"
                                                         id="product-contact-phone"
                                                         placeholder="{{ __('Contact Phone') }}" name="contact_phone" />
                                                     <span class="contact_phone-error text-danger text-error"></span>
                                                 </div>
                                             </div>
                                             <div class="col-md-12">
                                                 <div class="mb-6">
                                                     <label class="form-label" for="product-contact-email">
                                                         {{ __('Contact Email') }}</label>
                                                     <input type="email" class="form-control"
                                                         id="product-contact-email"
                                                         placeholder="{{ __('Contact Email') }}" name="contact_email" />
                                                     <span class="contact_email-error text-danger text-error"></span>
                                                 </div>
                                             </div>

                                             <div class="col-md-12">
                                                 <div class="mb-6">
                                                     <label class="form-label" for="product-notes">
                                                         {{ __('Notes') }}</label>
                                                     <textarea class="form-control" id="product-notes" rows="3" placeholder="{{ __('Additional Notes') }}"
                                                         name="notes"></textarea>
                                                     <span class="notes-error text-danger text-error"></span>
                                                 </div>
                                             </div>

                                         </div>
                                     </div>
                                 </div>
                                 <div class="col-md-7">


                                     <div class="p-3">
                                         <div class="divider text-start">
                                             <div class="divider-text">
                                                 <strong>{{ __('Location') }}</strong>
                                             </div>
                                         </div>
                                         <div class="mb-3">

                                             <label class="form-label" for="product-address">*
                                                 {{ __('Address') }}</label>
                                             <input type="text" name="address" class="form-control"
                                                 id="product-address"
                                                 placeholder="{{ __('Enter the product address') }}" />
                                             <span class="address-error text-danger text-error"></span>
                                         </div>
                                         <div class="mb-3">
                                             <label for="product-location">* {{ __('Location') }}</label>
                                             <div class="input-group mb-2">
                                                 <div class="form-control p-0" id="product-geocoder"></div>
                                                 <button type="button" title="تحليل رابط موقع"
                                                     id="product-toggle-link-input" class="input-group-text bg-white">
                                                     <i class="fas fa-link text-secondary"></i>
                                                 </button>
                                                 <button type="button" title="إدخال يدوي" id="product-manual-btn"
                                                     class="input-group-text bg-white">
                                                     <i class="fas fa-globe text-secondary"></i>
                                                 </button>
                                                 <button type="button" title="موقعي الحالي"
                                                     id="product-getCurrentLocation" class="input-group-text bg-white">
                                                     <i class="fas fa-location-crosshairs text-secondary"></i>
                                                 </button>
                                             </div>
                                             <div id="product-link-input-wrapper" class="mt-2" style="display: none;">
                                                 <div class="input-group">
                                                     <input type="text" id="product-map-link" class="form-control"
                                                         placeholder="ألصق رابط الموقع هنا" />
                                                     <button type="button" id="product-parse-link"
                                                         class="btn btn-secondary">
                                                         تحليل الرابط
                                                     </button>
                                                 </div>
                                             </div>
                                             <!-- Map Container -->
                                             <div id="product-map-container"
                                                 class="position-relative rounded overflow-hidden border"
                                                 style="height: 300px; display: none;">
                                                 <div class="row mb-2 position-absolute top-0 start-0 m-2 z-3">
                                                     <div class="col">
                                                         <input type="number" name="longitude" step="any"
                                                             id="product-longitude" class="form-control"
                                                             placeholder="(Longitude)">
                                                     </div>
                                                     <div class="col">
                                                         <input type="number" name="latitude" step="any"
                                                             id="product-latitude" class="form-control"
                                                             placeholder="(Latitude)">
                                                     </div>
                                                 </div>
                                                 <button id="product-confirm-location" type="button"
                                                     class="btn btn-primary btn-sm position-absolute top-0 end-0 m-2 z-3"
                                                     style="display: none;">
                                                     {{ __('confirm location') }}
                                                 </button>
                                                 <div id="product-map" class="w-100 h-100" style="display: none;"></div>
                                                 <!-- Hidden Final Address -->
                                                 <input type="hidden" id="product_address" name="product_address" />
                                             </div>
                                             <span class="longitude-error text-danger text-error"></span>
                                             <span class="latitude-error text-danger text-error"></span>
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



 @endsection
