@extends('layouts/layoutMaster')

@section('title', __('Tasks Map'))

<!-- Vendor Styles -->
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

        .custom-pickup-marker {
            background-color: #0d6efd;
            /* لون الإبرة */
            color: white;
            font-size: 12px;
            font-weight: bold;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
            border: 2px solid white;
            cursor: pointer;
            transform: translate(-50%, -50%);
        }

        .mapboxgl-popup {
            max-width: 250px;
        }

        .mapboxgl-popup-content {
            background: rgba(0, 0, 0, 0.85);
            /* خلفية سوداء شفافة */
            color: white;
            /* نص أبيض */
            border-radius: 8px;
            padding: 10px;
            box-shadow: none;
            border: none;
            /* إزالة أي حدود بيضاء */
        }

        .mapboxgl-popup-tip {
            border-top-color: rgba(0, 0, 0, 0.85) !important;
            /* مثلث السهم نفس لون الخلفية */
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
        <div class="col-md-2 vehicle-quantity">
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
    @vite(['resources/js/admin/tasks/tasks.js'])
    @vite(['resources/js/admin/tasks/preview.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])
    <script>
        const navContent = document.querySelector('#navbar-custom-nav-container');
        const mobileContainer = document.querySelector('#mobile-custom-nav');
        const originalContent = navContent?.innerHTML;

        function moveCustomNav() {
            if (window.innerWidth < 1000) {
                // شاشة صغيرة، انقل المحتوى إلى الأسفل
                if (originalContent && mobileContainer && mobileContainer.innerHTML.trim() === '') {
                    mobileContainer.innerHTML = originalContent;
                    navContent.innerHTML = '';
                }
            } else {
                // شاشة كبيرة، أعد المحتوى إلى مكانه الأصلي
                if (originalContent && navContent && navContent.innerHTML.trim() === '') {
                    navContent.innerHTML = originalContent;
                    mobileContainer.innerHTML = '';
                }
            }
        }

        moveCustomNav(); // تنفيذ أولي
        window.addEventListener('resize', moveCustomNav); // تنفيذ عند تغيير حجم الشاشة
    </script>
@endsection
@section('navbar-custom-nav')
    <div class="btn-group col" role="group" aria-label="Map and Table toggle">
        <a href="{{ route('tasks.tasks') }}" class="btn btn-secondary" title="{{ __('View Map Layout') }}">
            <i class="fas fa-map-marked-alt mx-1"></i> {{ __('Map') }}
        </a>
        <a href="{{ route('tasks.list') }}" class="btn btn-outline-secondary" title="{{ __('view Table layout') }}">
            <i class="fas fa-table mx-1"></i> {{ __('Table') }}
        </a>
    </div>

    <div class="col">
        <input class="form-control w-auto mx-2" type="date" value="{{ now()->format('Y-m-d') }}" id="filter-by-day">

    </div>
@endsection
@section('content')
    <div id="mobile-custom-nav" class="d-lg-none overflow-auto z-1 card shadow mb-3 p-2" style="white-space: nowrap;">
    </div>
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
                    class="position-absolute top-0 start-0 w-100 h-100 bg-white shadow-lg p-0 overflow-auto"
                    style="display: none; z-index: 1050;">
                    <div class="d-flex justify-content-between p-3" id="taskDetailsControl">

                    </div>

                    <div class="nav-align-top  overflow-auto p-0 " style="min-height: 75vh">
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

                        <div class="tab-content p-0 m-0" style="max-height: calc(75vh - 60px); overflow-y: auto;">
                            <div class="tab-pane fade show active" id="navs-justified-details" role="tabpanel">

                                <div id="task-details-content">
                                    <!-- تفاصيل المهمة ستُحقن هنا -->
                                </div>
                            </div>

                            <div class="tab-pane fade p-0" id="navs-justified-owner" role="tabpanel">

                                <div id="task-owner-content">
                                    <!-- تفاصيل المهمة ستُحقن هنا -->
                                </div>
                            </div>

                            <div class="tab-pane fade " id="navs-justified-history" role="tabpanel">

                                <div id="task-history-content">

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
