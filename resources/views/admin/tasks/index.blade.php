@extends('layouts/layoutMaster')

@section('title', __('Tasks'))

<!-- Vendor Styles -->
@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css"
        rel="stylesheet" />
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    @vite('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss')

    @vite(['resources/css/app.css'])
    <style>
        #preview-map {
            height: 80vh !important;
            width: 100% !important;
        }

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

<!-- Vendor Scripts -->
@section('vendor-script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    @vite('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')

    <script>
        const templateId = {{ $task_template->value ?? 0 }}
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
    @vite(['resources/js/mapbox-helper.js'])
    @vite(['resources/js/admin/tasks.js'])
    @vite(['resources/js/admin/tasks-preview.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])
@endsection
@section('navbar-custom-nav')
    <input class="form-control w-auto" type="date" value="{{ now()->format('Y-m-d') }}" id="filter-by-day">
@endsection
@section('content')
    <div class="row body-container-block">

        <div id="task-details-container" class="col-md-4 mb-3" style="display: none;"></div>

        <div class="col-md-4   overflow-auto " style="z-index: 1000">
            <div class="card mb-2">
                <div class="card-header py-3 sticky-top" style="top: 0; z-index: 1020;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h5 class="card-title mb-0">{{ __('Tasks') }}</h5>

                        <button class="btn btn-outline-secondary btn-sm mt-2 mt-sm-0" data-bs-toggle="modal"
                            data-bs-target="#submitModal">
                            <i class="ti ti-plus me-1"></i>
                            {{ __('Add New Task') }}
                        </button>
                    </div>

                </div>
                <div class="nav-align-top  overflow-auto" style="min-height: 75vh">
                    <ul class="nav nav-tabs nav-fill bg-white border-bottom sticky-top" style="top: 0; z-index: 1030;"
                        role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                data-bs-target="#navs-justified-home" aria-controls="navs-justified-home"
                                aria-selected="true"><span class="d-none d-sm-block"> {{ __('unassigned') }} <span
                                        class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-warning ms-1_5 pt-50 count-unassigned">0</span></span><i
                                    class="ti ti-home ti-sm d-sm-none"></i></button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                data-bs-target="#navs-justified-profile" aria-controls="navs-justified-profile"
                                aria-selected="false"><span class="d-none d-sm-block"> {{ __('assigned') }} <span
                                        class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-success ms-1_5 pt-50 count-assigned">0</span></span><i
                                    class="ti ti-user ti-sm d-sm-none"></i></button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                data-bs-target="#navs-justified-messages" aria-controls="navs-justified-messages"
                                aria-selected="false"><span class="d-none d-sm-block"> {{ __('Completed') }} <span
                                        class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-info ms-1_5 pt-50 count-completed">0</span></span><i
                                    class="ti ti-message-dots ti-sm d-sm-none"></i></button>
                        </li>
                    </ul>
                    <div class="tab-content" style="max-height: calc(75vh - 60px); overflow-y: auto;">
                        <div class="tab-pane fade show active" id="navs-justified-home" role="tabpanel">

                            <div id="task-unassigned-container">

                            </div>
                        </div>
                        <div class="tab-pane fade" id="navs-justified-profile" role="tabpanel">
                            <div id="task-assigned-container">

                            </div>

                        </div>
                        <div class="tab-pane fade" id="navs-justified-messages" role="tabpanel">
                            <div id="task-completed-container">

                            </div>
                        </div>
                    </div>
                </div>

                <div id="task-details-view"
                    class="position-absolute top-0 start-0 w-100 h-100 bg-white shadow-lg p-4 overflow-auto"
                    style="display: none; z-index: 1050;">

                    <button id="close-task-details" class="btn btn-sm  mb-3">
                        <i class="ti ti-x"></i>
                    </button>
                    <div class="nav-align-top  overflow-auto" style="min-height: 75vh">
                        <ul class="nav nav-tabs nav-fill bg-white border-bottom sticky-top" style="top: 0; z-index: 1030;"
                            role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-justified-details" aria-controls="navs-justified-home"
                                    aria-selected="true"><span class="d-none d-sm-block"> {{ __('details') }}</span><i
                                        class="ti ti-home ti-sm d-sm-none"></i></button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-justified-owner" aria-controls="navs-justified-profile"
                                    aria-selected="false"><span class="d-none d-sm-block"> {{ __('owner') }}</span><i
                                        class="ti ti-user ti-sm d-sm-none"></i></button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-justified-history" aria-controls="navs-justified-messages"
                                    aria-selected="false"><span class="d-none d-sm-block"> {{ __('history') }}</span><i
                                        class="ti ti-message-dots ti-sm d-sm-none"></i></button>
                            </li>
                        </ul>

                        <div class="tab-content" style="max-height: calc(75vh - 60px); overflow-y: auto;">
                            <div class="tab-pane fade show active" id="navs-justified-details" role="tabpanel">

                                <div id="task-details-content">
                                    <!-- تفاصيل المهمة ستُحقن هنا -->
                                </div>
                            </div>

                            <div class="tab-pane fade show active" id="navs-justified-owner" role="tabpanel">

                                <div id="task-owner-content">
                                    <!-- تفاصيل المهمة ستُحقن هنا -->
                                </div>
                            </div>

                            <div class="tab-pane fade show active" id="navs-justified-history" role="tabpanel">
                                <ul class="timeline mb-0">
                                    <li class="timeline-item timeline-item-transparent">
                                        <span class="timeline-point timeline-point-primary"></span>
                                        <div class="timeline-event">
                                            <div class="timeline-header mb-3">
                                                <h6 class="mb-0">12 Invoices have been paid</h6>
                                                <small class="text-muted">12 min ago</small>
                                            </div>
                                            <p class="mb-2">
                                                Invoices have been paid to the company
                                            </p>
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="badge bg-lighter rounded d-flex align-items-center">
                                                    <img src="{{ asset('assets/img/icons/misc/pdf.png') }}"
                                                        alt="img" width="15" class="me-2">
                                                    <span class="h6 mb-0 text-body">invoices.pdf</span>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="timeline-item timeline-item-transparent">
                                        <span class="timeline-point timeline-point-success"></span>
                                        <div class="timeline-event">
                                            <div class="timeline-header mb-3">
                                                <h6 class="mb-0">Client Meeting</h6>
                                                <small class="text-muted">45 min ago</small>
                                            </div>
                                            <p class="mb-2">
                                                Project meeting with john @10:15am
                                            </p>
                                            <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                                                <div class="d-flex flex-wrap align-items-center mb-50">
                                                    <div class="avatar avatar-sm me-2">
                                                        <img src="{{ asset('assets/img/avatars/1.png') }}" alt="Avatar"
                                                            class="rounded-circle" />
                                                    </div>
                                                    <div>
                                                        <p class="mb-0 small fw-medium">Lester McCarthy (Client)</p>
                                                        <small>CEO of {{ config('variables.creatorName') }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="timeline-item timeline-item-transparent">
                                        <span class="timeline-point timeline-point-info"></span>
                                        <div class="timeline-event">
                                            <div class="timeline-header mb-3">
                                                <h6 class="mb-0">Create a new project for client</h6>
                                                <small class="text-muted">2 Day Ago</small>
                                            </div>
                                            <p class="mb-2">
                                                6 team members in a project
                                            </p>
                                            <ul class="list-group list-group-flush">
                                                <li
                                                    class="list-group-item d-flex justify-content-between align-items-center flex-wrap border-top-0 p-0">
                                                    <div class="d-flex flex-wrap align-items-center">
                                                        <ul
                                                            class="list-unstyled users-list d-flex align-items-center avatar-group m-0 me-2">
                                                            <li data-bs-toggle="tooltip" data-popup="tooltip-custom"
                                                                data-bs-placement="top" title="Vinnie Mostowy"
                                                                class="avatar pull-up">
                                                                <img class="rounded-circle"
                                                                    src="{{ asset('assets/img/avatars/5.png') }}"
                                                                    alt="Avatar" />
                                                            </li>
                                                            <li data-bs-toggle="tooltip" data-popup="tooltip-custom"
                                                                data-bs-placement="top" title="Allen Rieske"
                                                                class="avatar pull-up">
                                                                <img class="rounded-circle"
                                                                    src="{{ asset('assets/img/avatars/12.png') }}"
                                                                    alt="Avatar" />
                                                            </li>
                                                            <li data-bs-toggle="tooltip" data-popup="tooltip-custom"
                                                                data-bs-placement="top" title="Julee Rossignol"
                                                                class="avatar pull-up">
                                                                <img class="rounded-circle"
                                                                    src="{{ asset('assets/img/avatars/6.png') }}"
                                                                    alt="Avatar" />
                                                            </li>
                                                            <li class="avatar">
                                                                <span
                                                                    class="avatar-initial rounded-circle pull-up text-heading"
                                                                    data-bs-toggle="tooltip" data-bs-placement="bottom"
                                                                    title="3 more">+3</span>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </li>
                                </ul>
                                <div id="task-history-content">
                                    <!-- تفاصيل المهمة ستُحقن هنا -->
                                </div>
                            </div>

                        </div>

                    </div>

                </div>


            </div>



        </div>

        <!-- الخريطة -->
        <div class="col-md-8 p-0">
            <div id="taskMap" class="w-100" style="height: 80vh; ">
            </div>
        </div>
    </div>





    @include('admin.tasks.from-modal')

@endsection
