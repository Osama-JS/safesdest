@extends('layouts/layoutMaster')

@section('title', __('Tasks'))

<!-- Vendor Styles -->
@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css"
        rel="stylesheet" />
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

    @vite(['resources/css/app.css'])
    @vite(['resources/assets/vendor/libs/dropzone/dropzone.scss'])
    <style>
        #preview-map {
            height: 80vh !important;
            width: 100% !important;
        }
    </style>

@endsection

<!-- Vendor Scripts -->
@section('vendor-script')

    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    @vite(['resources/assets/vendor/libs/dropzone/dropzone.js'])


    <script type="text/template" id="vehicle-row-template">
      <div class="row vehicle-row mb-3 " data-index="{index}">
        <div class="col-md-4">
          <label class="form-label">* Vehicle</label>
          <select class="form-select vehicle-select" name="vehicles[{index}][vehicle]">
            <option value="">Select a vehicle</option>
            @foreach ($vehicles as $vehicle)
              <option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>
            @endforeach
          </select>
          <span class="vehicles-{index}-vehicle-error text-danger text-error"></span>

        </div>
        <div class="col-md-3">
          <label class="form-label">* Vehicle Type</label>
          <select class="form-select vehicle-type-select" name="vehicles[{index}][vehicle_type]" disabled>
            <option value="">Select a vehicle type</option>
          </select>
          <span class="vehicles-{index}-vehicle_type-error text-danger text-error"></span>

        </div>
        <div class="col-md-3">
          <label class="form-label">* Vehicle Size</label>
          <select class="form-select vehicle-size-select" name="vehicles[{index}][vehicle_size]" disabled>
            <option value="">Select a vehicle size</option>
          </select>
          <span class="vehicles-{index}-vehicle_size-error text-danger text-error"></span>

        </div>
        <div class="col-md-2">
          <label class="form-label">* Quantity</label>
          <input type="number" class="form-control vehicle-quantity" name="vehicles[{index}][quantity]" min="1" value="1" />
          <span class="vehicles-{index}-quantity-error text-danger text-error"></span>

        </div>
        {{-- <div class="col-md-1 d-flex ">
          <button type="button" class="btn text-danger btn-icon btn-sm remove-vehicle-btn"><i
            class="ti ti-trash"></i></button>
        </div> --}}
      </div>
    </script>

@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/tasks.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])

@endsection

@section('content')


    <!-- Users List Table -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">{{ __('Tasks') }}</h5>
            <button class="add-new btn btn-primary waves-effect waves-light mt-5 mx-4" data-bs-toggle="modal"
                data-bs-target="#submitModal">
                <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                <span class="d-none d-sm-inline-block"> {{ __('Add New Task') }}</span>
            </button>
        </div>
    </div>

    <div class="modal fade" id="submitModal" tabindex="-1" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-fullscreen" role="document">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ __('Add New Tasks') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form class="add-new-user pt-0 " id="task-form" method="POST" action="{{ route('tasks.create') }}"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-5">

                                <ul class="nav nav-tabs" id="taskTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="tab-step1" data-bs-toggle="tab"
                                            data-bs-target="#step1" type="button" role="tab">Step 1</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link disabled" id="tab-step2" data-bs-toggle="tab"
                                            data-bs-target="#step2" type="button" role="tab">Step 2</button>
                                    </li>
                                </ul>

                                <div class="tab-content mt-3">
                                    <!-- Step 1 -->
                                    <div class="tab-pane fade show active" id="step1" role="tabpanel">

                                        <div class="mb-3">

                                            <label for="task-owner" class="form-label">* {{ __('owner type') }}</label>
                                            <select name="owner" id="task-owner" class="form-select">
                                                <option value="admin">{{ __('Administrator') }}</option>
                                                <option value="customer">{{ __('Customer') }}</option>
                                            </select>
                                            <span class="owner-error text-danger text-error"></span>

                                            <!-- Customer Dropdown (Hidden initially) -->
                                            <div id="customers-wrapper" class="mt-2" style="display: none;">
                                                <label for="task-customer" class="form-label">*
                                                    {{ __('Select Customer') }}</label>
                                                <select name="customer" id="task-customer" class="form-select">
                                                    <option value="">Select Customer</option>
                                                    @foreach ($customers as $val)
                                                        <option value="{{ $val->id }}">{{ $val->name }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="customer-error text-danger text-error"></span>
                                            </div>
                                        </div>


                                        <!-- Vehicle Selection -->
                                        <div class="mb-3">
                                            <div class="divider text-start">
                                                <div class="divider-text"><strong>Vehicle Selection</strong></div>
                                            </div>

                                            <div id="vehicle-selection-container">
                                                <!-- سيتم توليد السطور ديناميكيًا هنا -->
                                            </div>

                                            {{-- <button type="button" id="add-vehicle-btn" class="btn btn-sm border mt-2">
                                                <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i> Add Another Vehicle
                                            </button> --}}
                                        </div>

                                        <div class="mb-3">
                                            <div class="divider text-start">
                                                <div class="divider-text"><strong>Task Information</strong></div>
                                            </div>
                                            <div class="form-group">
                                                <label for="task-select-template">{{ __('Select Template') }}</label>
                                                <select name="template" id="task-select-template"
                                                    class="form-select w-auto">
                                                    <option value="">{{ __('-- Select Template') }}</option>
                                                    @foreach ($templates as $key)
                                                        <option value="{{ $key->id }}">{{ $key->name }}</option>
                                                    @endforeach
                                                </select>
                                                <span class="template-error text-danger text-error"></span>

                                            </div>
                                            <div id="additional-form" class="row mt-4">

                                            </div>
                                        </div>


                                        <button type="button" id="go-to-step2" class="btn btn-primary mt-3">التالي
                                            ⏭️</button>

                                    </div>

                                    <!-- Step 2 -->
                                    <div class="tab-pane fade" id="step2" role="tabpanel">
                                        <!-- Pricing Method Selection -->
                                        <div class="mb-3">
                                            <div class="divider text-start">
                                                <div class="divider-text"><strong>{{ __('Pricing') }}</strong></div>
                                            </div>

                                            <select id="pricing-method-select" class="form-select">
                                                <!-- سيتم تعبئته عبر الجافاسكربت -->
                                            </select>



                                        </div>
                                        <div class="mb-3">
                                            <div class="divider text-start">
                                                <div class="divider-text"><strong>(Pickup / Delivery) Point</strong></div>
                                            </div>
                                            <div id="accordionCustomIcon" class="accordion mt-4 accordion-custom-button">
                                                <div class="accordion-item">
                                                    <h4 class="accordion-header text-body d-flex justify-content-between"
                                                        id="accordionCustomIconOne">
                                                        <button type="button" class="accordion-button collapsed"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#accordionCustomIcon-1"
                                                            aria-controls="accordionCustomIcon-1">
                                                            <i class="ri-bar-chart-2-line me-2 ri-20px"></i>
                                                            <h5> Pickup Point</h5>
                                                        </button>
                                                    </h4>



                                                    <div id="accordionCustomIcon-1" class="accordion-collapse collapse"
                                                        data-bs-parent="#accordionCustomIcon">
                                                        <div class="accordion-body">

                                                            <!-- Pickup Point -->
                                                            <div class="mb-3">

                                                                <!-- Name & Phone -->
                                                                <div class="mb-3">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <label for="pickup-contact-name">*
                                                                                {{ __('Name') }}</label>
                                                                            <input type="text" id="pickup-contact-name"
                                                                                name="pickup_name" class="form-control"
                                                                                placeholder="{{ __('Enter pickup address') }}"
                                                                                required />
                                                                            <span
                                                                                class="pickup_name-error text-danger text-error"></span>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="pickup-contact-phone">*
                                                                                {{ __('Phone') }}</label>
                                                                            <input type="text"
                                                                                id="pickup-contact-phone"
                                                                                name="pickup_phone" class="form-control"
                                                                                placeholder="{{ __('Enter pickup address') }}"
                                                                                required />
                                                                            <span
                                                                                class="pickup_phone-error text-danger text-error"></span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Email & Pickup Before -->
                                                                <div class="mb-3">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <label for="pickup-email">*
                                                                                {{ __('Email') }}</label>
                                                                            <input type="email" id="pickup-email"
                                                                                name="pickup_email" class="form-control"
                                                                                placeholder="{{ __('Email') }}"
                                                                                required />
                                                                            <span
                                                                                class="pickup_email-error text-danger text-error"></span>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="pickup-before">*
                                                                                {{ __('Pickup before') }}</label>
                                                                            <input type="date" id="pickup-before"
                                                                                name="pickup_before" class="form-control"
                                                                                required />
                                                                            <span
                                                                                class="pickup_before-error text-danger text-error"></span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Address -->
                                                                <div class="mb-3">
                                                                    <label for="pickup-address">*
                                                                        {{ __('Address') }}</label>
                                                                    <input type="text" id="pickup-address"
                                                                        name="pickup_address" class="form-control"
                                                                        placeholder="{{ __('Enter pickup address') }}"
                                                                        required />
                                                                    <span
                                                                        class="pickup_address-error text-danger text-error"></span>
                                                                </div>

                                                                <!-- Location Geocoder + Map -->
                                                                <div class="mb-3">
                                                                    <label for="pickup-location">*
                                                                        {{ __('Location') }}</label>
                                                                    <div class="input-group mb-2">
                                                                        <div class="form-control p-0"
                                                                            id="pickup-geocoder"></div>
                                                                        <button type="button" title="إدخال يدوي"
                                                                            id="pickup-manual-btn"
                                                                            class="input-group-text bg-white">
                                                                            <i class="fas fa-globe text-secondary"></i>
                                                                        </button>
                                                                        <button type="button" title="موقعي الحالي"
                                                                            id="pickup-getCurrentLocation"
                                                                            class="input-group-text bg-white">
                                                                            <i
                                                                                class="fas fa-location-crosshairs text-secondary"></i>
                                                                        </button>
                                                                    </div>

                                                                    <!-- Map Container -->
                                                                    <div id="pickup-map-container"
                                                                        class="position-relative rounded overflow-hidden border"
                                                                        style="height: 200px; display: none;">
                                                                        <div
                                                                            class="row mb-2 position-absolute top-0 start-0 m-2 z-3">
                                                                            <div class="col">
                                                                                <input type="number"
                                                                                    name="pickup_latitude" step="any"
                                                                                    id="pickup-latitude"
                                                                                    class="form-control"
                                                                                    placeholder="(Latitude)">
                                                                            </div>
                                                                            <div class="col">
                                                                                <input type="number"
                                                                                    name="pickup_longitude" step="any"
                                                                                    id="pickup-longitude"
                                                                                    class="form-control"
                                                                                    placeholder="(Longitude)">
                                                                            </div>
                                                                        </div>
                                                                        <button id="pickup-confirm-location"
                                                                            type="button"
                                                                            class="btn btn-primary btn-sm position-absolute top-0 end-0 m-2 z-3"
                                                                            style="display: none;">
                                                                            {{ __('confirm location') }}
                                                                        </button>
                                                                        <div id="pickup-map" class="w-100 h-100"
                                                                            style="display: none;"></div>
                                                                    </div>

                                                                    <!-- Hidden Final Address -->
                                                                    <input type="hidden" id="pickup_address"
                                                                        name="pickup_address" />
                                                                </div>

                                                                <!-- Note -->
                                                                <div class="mb-3">
                                                                    <label for="pickup-note">{{ __('Note') }}</label>
                                                                    <input type="text" id="pickup-note"
                                                                        name="pickup_note" class="form-control"
                                                                        placeholder="{{ __('Note') }}" required />
                                                                    <span
                                                                        class="pickup_note-error text-danger text-error"></span>
                                                                </div>

                                                                <div class="mb-6">
                                                                    <label
                                                                        for="pickup-image">{{ __('Image for pickup address') }}</label>

                                                                    <div class="form-group mt-2">
                                                                        <img src="{{ url(asset('assets/img/placeholder.jpg')) }}"
                                                                            data-image="{{ url(asset('assets/img/placeholder.jpg')) }}"
                                                                            alt="" id="image"
                                                                            style="width: 120px;    height: 100px;
                                                                      object-fit: cover;"
                                                                            class="rounded preview-pickup-image image-input">

                                                                        <input type="file"
                                                                            class="form-control file-pickup-image"
                                                                            id="pickup-image" name="pickup_image"
                                                                            style="display: none" />
                                                                        <span
                                                                            class="pickup_image-error text-danger text-error"></span>

                                                                    </div>

                                                                </div>

                                                            </div>

                                                        </div>
                                                    </div>

                                                    <h4 class="accordion-header text-body d-flex justify-content-between"
                                                        id="accordionCustomIconOne">
                                                        <button type="button" class="accordion-button collapsed"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#accordionCustomIcon-2"
                                                            aria-controls="accordionCustomIcon-2">
                                                            <i class="ri-bar-chart-2-line me-2 ri-20px"></i>
                                                            <h5> Delivery Point</h5>
                                                        </button>
                                                    </h4>

                                                    <div id="accordionCustomIcon-2" class="accordion-collapse collapse"
                                                        data-bs-parent="#accordionCustomIcon">
                                                        <div class="accordion-body">

                                                            <div class="mb-3">


                                                                <!-- Name & Phone -->
                                                                <div class="mb-3">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <label for="delivery-contact-name">*
                                                                                {{ __('Name') }}</label>
                                                                            <input type="text"
                                                                                id="delivery-contact-name"
                                                                                name="delivery_name" class="form-control"
                                                                                placeholder="{{ __('Enter delivery name') }}"
                                                                                required />
                                                                            <span
                                                                                class="delivery_name-error text-danger text-error"></span>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="delivery-contact-phone">*
                                                                                {{ __('Phone') }}</label>
                                                                            <input type="text"
                                                                                id="delivery-contact-phone"
                                                                                name="delivery_phone" class="form-control"
                                                                                placeholder="{{ __('Enter delivery phone') }}"
                                                                                required />
                                                                            <span
                                                                                class="delivery_phone-error text-danger text-error"></span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Email & Delivery Before -->
                                                                <div class="mb-3">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <label for="delivery-email">*
                                                                                {{ __('Email') }}</label>
                                                                            <input type="email" id="delivery-email"
                                                                                name="delivery_email" class="form-control"
                                                                                placeholder="{{ __('Email') }}"
                                                                                required />
                                                                            <span
                                                                                class="delivery_email-error text-danger text-error"></span>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label for="delivery-before">*
                                                                                {{ __('Delivery before') }}</label>
                                                                            <input type="date" id="delivery-before"
                                                                                name="delivery_before"
                                                                                class="form-control" required />
                                                                            <span
                                                                                class="delivery_before-error text-danger text-error"></span>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Address -->
                                                                <div class="mb-3">
                                                                    <label for="delivery-address">*
                                                                        {{ __('Address') }}</label>
                                                                    <input type="text" id="delivery-address"
                                                                        name="delivery_address" class="form-control"
                                                                        placeholder="{{ __('Enter delivery address') }}"
                                                                        required />
                                                                    <span
                                                                        class="delivery_address-error text-danger text-error"></span>
                                                                </div>

                                                                <!-- Location Geocoder + Map -->
                                                                <div class="mb-3">
                                                                    <label for="delivery-location">*
                                                                        {{ __('Location') }}</label>
                                                                    <div class="input-group mb-2">
                                                                        <div class="form-control p-0"
                                                                            id="delivery-geocoder">
                                                                        </div>
                                                                        <button type="button" title="إدخال يدوي"
                                                                            id="delivery-manual-btn"
                                                                            class="input-group-text bg-white">
                                                                            <i class="fas fa-globe text-secondary"></i>
                                                                        </button>
                                                                        <button type="button" title="موقعي الحالي"
                                                                            id="delivery-getCurrentLocation"
                                                                            class="input-group-text bg-white">
                                                                            <i
                                                                                class="fas fa-location-crosshairs text-secondary"></i>
                                                                        </button>
                                                                    </div>

                                                                    <!-- Map Container -->
                                                                    <div id="delivery-map-container"
                                                                        class="position-relative rounded overflow-hidden border"
                                                                        style="height: 200px; display: none;">
                                                                        <div
                                                                            class="row mb-2 position-absolute top-0 start-0 m-2 z-3">
                                                                            <div class="col">
                                                                                <input type="number"
                                                                                    name="delivery_latitude"
                                                                                    step="any" id="delivery-latitude"
                                                                                    class="form-control"
                                                                                    placeholder="(Latitude)">
                                                                            </div>
                                                                            <div class="col">
                                                                                <input type="number"
                                                                                    name="delivery_longitude"
                                                                                    step="any" id="delivery-longitude"
                                                                                    class="form-control"
                                                                                    placeholder="(Longitude)">
                                                                            </div>
                                                                        </div>
                                                                        <button id="delivery-confirm-location"
                                                                            type="button"
                                                                            class="btn btn-primary btn-sm position-absolute top-0 end-0 m-2 z-3"
                                                                            style="display: none;">
                                                                            {{ __('confirm location') }}
                                                                        </button>
                                                                        <div id="delivery-map" class="w-100 h-100"
                                                                            style="display: none;"></div>
                                                                    </div>

                                                                    <!-- Hidden Final Address -->
                                                                    <input type="hidden" id="delivery_address"
                                                                        name="delivery_address" />
                                                                </div>

                                                                <!-- Note -->
                                                                <div class="mb-3">
                                                                    <label for="delivery-note">{{ __('Note') }}</label>
                                                                    <input type="text" id="delivery-note"
                                                                        name="delivery_note" class="form-control"
                                                                        placeholder="{{ __('Note') }}" required />
                                                                    <span
                                                                        class="delivery_note-error text-danger text-error"></span>
                                                                </div>

                                                                <div class="mb-6">
                                                                    <label
                                                                        for="delivery-image">{{ __('Image for pickup address') }}</label>

                                                                    <div class="form-group mt-2">
                                                                        <img src="{{ url(asset('assets/img/placeholder.jpg')) }}"
                                                                            data-image="{{ url(asset('assets/img/placeholder.jpg')) }}"
                                                                            alt="" id="image"
                                                                            style="width: 120px;    height: 100px;
                                                                      object-fit: cover;"
                                                                            class="rounded preview-deliver-image image-input">

                                                                        <input type="file"
                                                                            class="form-control file-deliver-image"
                                                                            id="delivery-image" name="delivery-image"
                                                                            style="display: none" />
                                                                        <span
                                                                            class="image-error text-danger text-error"></span>

                                                                    </div>

                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </div>

                            <!-- Right Column (Empty) -->
                            <div class="col-md-7">
                                <div class="w-full" style="position: sticky; top:0 ">
                                    <div id="preview-map">
                                    </div>
                                    <p id="distance-info"
                                        class="mt-2 text-primary fw-bold position-absolute top-0 end-0 m-2 z-3"></p>
                                </div>

                            </div>

                        </div>
                    </form>

                </div>

                <div class="modal-footer pt-3">
                    <button type="button" class="btn btn-label-secondary"
                        data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>

            </div>

        </div>
    </div>

@endsection
