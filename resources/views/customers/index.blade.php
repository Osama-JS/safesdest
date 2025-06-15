@php

    use Illuminate\Support\Facades\Session;
    $guard = Session::get('guard');

@endphp
@extends('layouts/layoutMaster')

@section('title', 'Customer Dashboard')

@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css"
        rel="stylesheet" />
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    @vite('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss')

    @vite(['resources/css/app.css'])
    <style>
        .tab-content,
        .nav-tabs {

            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* Internet Explorer 10+ */
        }

        .tab-content::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari */
        }
    </style>
@endsection

@section('page-style')
    <!-- Page -->
    @vite(['resources/assets/vendor/scss/pages/cards-advance.scss'])
@endsection

@section('vendor-script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    @vite('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')
@endsection

@section('page-script')
    <script>
        const taskTemplate = {!! json_encode($template_fields) !!}
    </script>
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
        @can('tasks_meltable')
          <div class="col-md-2 vehicle-quantity">
            <label class="form-label">* Quantity</label>
            <input type="number" class="form-control vehicle-quantity" name="vehicles[{index}][quantity]" min="1" value="1" />
            <span class="vehicles-{index}-quantity-error text-danger text-error"></span>
          </div>
         @endcan

        {{-- <div class="col-md-1 d-flex ">
          <button type="button" class="btn text-danger btn-icon btn-sm remove-vehicle-btn"><i
            class="ti ti-trash"></i></button>
        </div> --}}
      </div>
    </script>


    @vite(['resources/js/customer/tasks.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])
@endsection

@section('content')

    <div class="row">
        <!-- User Sidebar -->
        <div class="col-xl-3 col-lg-4 order-1 order-md-0">
            <!-- User Card -->
            <div class="card mb-6">
                <div class="card-body pt-12">
                    <div class="user-avatar-section">
                        <div class=" d-flex align-items-center flex-column">
                            <img class="img-fluid rounded mb-4"
                                src="{{ auth()->user()->image ? asset(auth()->user()->image) : asset('assets/img/person.png') }}"
                                style="width: 200px;" alt="User avatar" />
                            <div class="user-info text-center">
                                <h5>{{ auth()->user()->name }}</h5> <span
                                    class="badge bg-label-secondary">{{ auth()->user()->team->name ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-around flex-wrap my-6 gap-0 gap-md-3 gap-lg-4">
                        <div class="d-flex align-items-center me-5 gap-4">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded">
                                    <i class='ti ti-checkbox ti-lg'></i>
                                </div>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ auth()->user()->tasks()->where('status', 'completed')->count() }}</h5>
                                <span>{{ __('Task Done') }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-4">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded">
                                    <i class='ti ti-truck-delivery ti-lg'></i>
                                </div>
                            </div>
                            <div>
                                <h5 class="mb-0">
                                    {{ auth()->user()->tasks()->where('status', '!=', 'completed')->where('status', '!=', 'canceled')->count() }}
                                </h5>
                                <span>{{ __('Running Tasks') }}</span>
                            </div>
                        </div>
                    </div>
                    <h5 class="pb-4 border-bottom mb-4">{{ __('Details') }}</h5>
                    <div class="info-container">
                        <ul class="list-unstyled mb-6">

                            <li class="mb-2">
                                <span class="h6">{{ __('Phone') }}:</span>
                                <span>{{ auth()->user()->phone }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('Email') }}:</span>
                                <span>{{ auth()->user()->email }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('address') }}:</span>
                                <span>{{ auth()->user()->address }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('Status') }}:</span>
                                <span>{{ auth()->user()->status }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('Role') }}:</span>
                                <span>{{ auth()->user()->role }}</span>
                            </li>

                        </ul>

                    </div>
                </div>
            </div>
            <!-- /User Card -->

        </div>
        <!--/ User Sidebar -->

        <div class="col-xl-9 col-lg-8 ">
            <div class="py-3">
                <button class="btn btn-primary  mt-2 mt-sm-0" data-bs-toggle="modal" data-bs-target="#submitModal">
                    <i class="ti ti-plus me-1"></i>
                    {{ __('Add New Task') }}
                </button>
            </div>
            <div class="py-3">
                <div id="tasks-container"></div>
            </div>
        </div>


    </div>

    @include('customers.tasks.from-modal')

@endsection
