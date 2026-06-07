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
        /* Enhanced Customer Dashboard Styling */
        .customer-dashboard-container {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 1rem 0;
        }

        /* Enhanced User Card */
        .user-profile-card {
            background: white;
            border-radius: 5px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e7eef7;
            overflow: hidden;
            position: relative;
        }

        .user-profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 120px;
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            z-index: 1;
        }

        .user-avatar-section {
            position: relative;
            z-index: 2;
            padding-top: 2rem;
        }

        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 6px solid white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            object-fit: cover;
        }

        .user-info h5 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .user-stats {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
        }

        .stat-item .avatar {
            margin: 0 auto 1rem;
        }

        .stat-item .avatar-initial {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 15px rgba(105, 108, 255, 0.3);
        }

        .stat-item h5 {
            color: #2c3e50;
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .stat-item span {
            color: #8a92a6;
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Enhanced Tasks Section */
        .tasks-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e7eef7;
            overflow: hidden;
        }

        /* Hidden by default - using a class for better control */
        .tasks-section.hidden {
            display: none;
        }

        /* Toggle Buttons Section */
        .section-toggles {
            margin-bottom: 1.5rem;
        }

        .toggle-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 15px;
            color: white;
            padding: 1rem 1.5rem;
            margin-right: 1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .toggle-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .toggle-button:active {
            transform: translateY(0);
        }

        .toggle-button.active {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .toggle-button .count-badge {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            margin-left: 0.5rem;
        }

        .toggle-button .icon {
            font-size: 1.1rem;
        }

        .toggle-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .toggle-button:hover::before {
            left: 100%;
        }

        .tasks-header {
            background: white;
            padding: 2rem;
            border-bottom: 1px solid #e7eef7;
        }

        .tasks-header h4 {
            color: #2c3e50;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .tasks-header .header-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            box-shadow: 0 4px 15px rgba(40, 199, 111, 0.3);
        }

        .add-task-btn {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(105, 108, 255, 0.3);
        }

        .add-task-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(105, 108, 255, 0.4);
            color: white;
        }

        /* Enhanced Task Cards */
        .task-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e7eef7;
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .task-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .task-card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem;
            border-bottom: 1px solid #e7eef7;
        }

        .task-id {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(105, 108, 255, 0.3);
        }

        .task-price {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(40, 199, 111, 0.3);
        }

        .task-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .task-status.completed {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
        }

        .task-status.in_progress {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
        }

        .task-status.pending {
            background: linear-gradient(135deg, #ff9f43 0%, #ff8c00 100%);
            color: white;
        }

        .task-status.canceled {
            background: linear-gradient(135deg, #ea5455 0%, #e74c3c 100%);
            color: white;
        }

        .location-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            border-left: 4px solid #696cff;
            transition: all 0.3s ease;
        }

        .location-card.pickup {
            border-left-color: #696cff;
        }

        .location-card.delivery {
            border-left-color: #28c76f;
        }

        .location-card:hover {
            background: #f1f3f4;
            transform: translateX(5px);
        }

        .location-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: between;
        }

        .location-title.pickup {
            color: #696cff;
        }

        .location-title.delivery {
            color: #28c76f;
        }

        .maps-btn {
            background: linear-gradient(135deg, #00cfe8 0%, #0dcaf0 100%);
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 207, 232, 0.3);
        }

        .maps-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 207, 232, 0.4);
            color: white;
        }

        /* Driver Info Section */
        .driver-info-section {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f8f0 100%);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 1px solid #c3e6c3;
        }

        .driver-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid #28c76f;
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(40, 199, 111, 0.3);
        }

        /* Filters Section */
        .filters-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e7eef7;
        }

        .filter-control {
            margin-bottom: 1rem;
        }

        .filter-control .form-control,
        .filter-control .form-select {
            border: 1px solid #e7eef7;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .filter-control .form-control:focus,
        .filter-control .form-select:focus {
            border-color: #696cff;
            box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.25);
            background: white;
        }

        .filter-btn {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(105, 108, 255, 0.4);
            color: white;
        }

        .clear-btn {
            background: #6c757d;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .clear-btn:hover {
            background: #5a6268;
            color: white;
        }

        /* Pagination */
        .pagination-wrapper {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e7eef7;
        }

        .pagination .page-link {
            border: 1px solid #e7eef7;
            border-radius: 8px;
            margin: 0 2px;
            color: #696cff;
            font-weight: 500;
            padding: 0.75rem 1rem;
        }

        .pagination .page-link:hover {
            background: #696cff;
            color: white;
            border-color: #696cff;
        }

        .pagination .page-item.active .page-link {
            background: #696cff;
            border-color: #696cff;
        }

        /* Loading State */
        .loading-state {
            text-align: center;
            padding: 3rem;
            color: #8a92a6;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #696cff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #8a92a6;
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: #adb5bd;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .customer-dashboard-container {
                padding: 0.5rem;
            }

            .user-profile-card,
            .tasks-section {
                border-radius: 15px;
            }

            .tasks-header {
                padding: 1.5rem;
            }

            .tasks-header h4 {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .task-card-header {
                padding: 1rem;
            }

            .location-card {
                margin-bottom: 1rem;
            }

            .filters-section {
                padding: 1rem;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .tab-content,
        .nav-tabs {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .tab-content::-webkit-scrollbar {
            display: none;
        }
    </style>
@endsection

@section('page-style')
    <!-- Page -->
    @vite(['resources/assets/vendor/scss/pages/cards-advance.scss'])
@endsection

@section('vendor-script')
    <!-- Custom Tabs CSS -->
    <style>
        .custom-tabs {
            border-bottom: none;
            gap: 10px;
        }
        .custom-tabs .nav-link {
            border: none;
            border-radius: 12px;
            background: #f8f9fa;
            color: #6c757d;
            font-weight: 600;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }
        .custom-tabs .nav-link:hover {
            background: #e9ecef;
            color: #495057;
        }
        .custom-tabs .nav-link.active {
            background: #696cff;
            color: #fff;
            box-shadow: 0 4px 10px rgba(105, 108, 255, 0.2);
        }
    </style>

    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    @vite('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')
@endsection

@section('page-script')
    <script>
        const taskTemplate = {!! json_encode($template_fields) !!}
        const taskTemplateFrom = {!! json_encode($template_from_fields) !!}
        const taskTemplateTo = {!! json_encode($template_to_fields) !!}
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

    <script>
        $(document).ready(function() {

            // Mapbox Initialization
            mapboxgl.accessToken = 'pk.eyJ1Ijoib3NhbWExOTk4IiwiYSI6ImNtOWk3eXd4MjBkbWcycHF2MDkxYmI3NjcifQ.2axcu5Sk9dx6GX3NtjjAvA';
            const map = new mapboxgl.Map({
                container: 'active-tasks-map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [46.6753, 24.7136], // Default Riyadh
                zoom: 10
            });
            let markers = [];

            // Function to update task count
            window.updateTaskCount = function(count) {
                $('#tasks-count').text(count || 0);
            };

            // Listen for task data updates
            $(document).on('tasksLoaded', function(event, data) {
                if (data && data.pagination && data.pagination.total !== undefined) {
                    updateTaskCount(data.pagination.total);
                }
                
                // Update map markers
                if (data && data.data) {
                    // Clear existing markers
                    markers.forEach(m => m.remove());
                    markers = [];
                    
                    const bounds = new mapboxgl.LngLatBounds();
                    let hasCoordinates = false;

                    data.data.forEach(task => {
                        if (task.status !== 'completed' && task.status !== 'canceled') {
                            if (task.pickup && task.pickup.longitude && task.pickup.latitude) {
                                const el = document.createElement('div');
                                el.className = 'marker';
                                el.innerHTML = '<i class="ti ti-map-pin text-primary fs-3"></i>';
                                
                                const popup = new mapboxgl.Popup({ offset: 25 })
                                    .setHTML(`<strong>Task #${task.id}</strong><br>${task.status}`);

                                const marker = new mapboxgl.Marker(el)
                                    .setLngLat([task.pickup.longitude, task.pickup.latitude])
                                    .setPopup(popup)
                                    .addTo(map);
                                
                                markers.push(marker);
                                bounds.extend([task.pickup.longitude, task.pickup.latitude]);
                                hasCoordinates = true;
                            }
                        }
                    });

                    if (hasCoordinates) {
                        map.fitBounds(bounds, { padding: 50 });
                    }
                }
            });

            // Ensure map resizes correctly when the tab is shown
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                if (e.target.id === 'map-tab') {
                    setTimeout(function() {
                        map.resize();
                    }, 200);
                }
            });
        });
    </script>
@endsection

@section('content')

    <div class="customer-dashboard-container">
        <div class="row">
            <!-- Main Content Area -->
            <div class="col-12">
                
                <!-- Dashboard Tabs Navigation -->
                <ul class="nav nav-tabs custom-tabs mb-4" id="dashboardTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active d-flex align-items-center" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks-tab-pane" type="button" role="tab" aria-controls="tasks-tab-pane" aria-selected="true">
                            <i class="ti ti-truck-delivery me-2 fs-5"></i>{{ __('My Tasks') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link d-flex align-items-center" id="map-tab" data-bs-toggle="tab" data-bs-target="#map-tab-pane" type="button" role="tab" aria-controls="map-tab-pane" aria-selected="false">
                            <i class="ti ti-map-2 me-2 fs-5"></i>{{ __('Live Map') }}
                        </button>
                    </li>
                </ul>

                <!-- Tabs Content -->
                <div class="tab-content p-0 bg-transparent border-0 shadow-none" id="dashboardTabsContent">
                    
                    <!-- My Tasks Tab Pane (Active) -->
                    <div class="tab-pane fade show active" id="tasks-tab-pane" role="tabpanel" aria-labelledby="tasks-tab" tabindex="0">
                        @if (auth()->user()->is_customs_clearance_agent)
                            <div class="card mb-4 shadow-sm border-0 bg-white">
                                <div class="card-body position-relative p-4">
                                    <h4>
                                        <i class="ti ti-clipboard-check"></i>
                                        {{ __('Clearance Tasks') }}
                                    </h4>

                                    <p class="text-muted mb-4 fs-6">
                                        {{ __('There are') }} <strong>{{ $clearance }}</strong>
                                        {{ __('customs clearance tasks available. You can view them and submit your offer.') }}
                                    </p>

                                    <a href="{{ route('customer.customs-clearances.ads') }}"
                                        class="btn btn-primary btn-lg px-4 py-2 d-inline-flex align-items-center">
                                        <i class="ti ti-clipboard-check me-2 fs-5"></i>
                                        {{ __('View Customs Clearance Tasks Now') }}
                                    </a>

                                    <div class="position-absolute top-0 end-0 m-3">
                                        <span class="badge bg-success fs-6 px-3 py-2 rounded-pill shadow-sm">
                                            {{ $clearance }} {{ __('new customs clearance available') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="tasks-section fade-in mt-4" id="tasks-section">                    <!-- Enhanced Tasks Header -->
                    <div class="tasks-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <h4>
                                <i class="ti ti-truck-delivery"></i>
                                My Tasks
                            </h4>
                            <button class="add-task-btn" data-bs-toggle="modal" data-bs-target="#taskTypeModal">
                                <i class="ti ti-plus me-2"></i>
                                Add New Task
                            </button>
                        </div>
                    </div>

                    <!-- Enhanced Filters Section -->
                    <div class="filters-section">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="filter-control">
                                    <label class="form-label fw-semibold">Search</label>
                                    <input type="text" id="search-tasks" class="form-control"
                                        placeholder="Search in tasks...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-control">
                                    <label class="form-label fw-semibold">Status</label>
                                    <select id="filter-status" class="form-select">
                                        <option value="">All Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="canceled">Canceled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-control">
                                    <label class="form-label fw-semibold">Date Range</label>
                                    <input type="date" id="filter-date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-control">
                                    <label class="form-label fw-semibold">&nbsp;</label>
                                    <div class="d-flex gap-2">
                                        <button type="button" id="apply-filters" class="filter-btn flex-fill">
                                            <i class="ti ti-search me-1"></i>
                                            Apply
                                        </button>
                                        <button type="button" id="clear-filters" class="clear-btn">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks Container -->
                    <div class="p-4">
                        <div id="tasks-container">
                            <!-- Loading State -->
                            <div class="loading-state">
                                <div class="loading-spinner"></div>
                                <p>Loading tasks...</p>
                            </div>
                        </div>

                        <!-- Pagination Container -->
                        <div id="pagination-container"></div>
                    </div>
                </div>
                    </div> <!-- End Tasks Tab Pane -->

                    <!-- Map Tab Pane -->
                    <div class="tab-pane fade" id="map-tab-pane" role="tabpanel" aria-labelledby="map-tab" tabindex="0">
                        <div class="card shadow-sm border-0 bg-white overflow-hidden" style="border-radius: 15px;">
                            <div class="position-relative w-100" style="height: 65vh; min-height: 400px;">
                                <!-- Map Container -->
                                <div id="active-tasks-map" class="w-100 h-100"></div>

                                <!-- Floating Stats Card (Bottom Left) -->
                                <div class="position-absolute" style="bottom: 20px; left: 20px; z-index: 1000; width: 220px;">
                                    <div class="card shadow-lg border-0 rounded-4" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="ti ti-chart-bar text-danger fs-5 me-2"></i>
                                                <h6 class="m-0 fw-bold" style="color: #333; font-size: 14px;">{{ __('Task Statistics') }}</h6>
                                            </div>
                                            <div class="d-flex flex-column gap-2">
                                                <!-- Running Tasks -->
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded p-1 me-2" style="background: rgba(33, 150, 243, 0.1);"><i class="ti ti-truck-delivery fs-6" style="color: #2196F3;"></i></div>
                                                        <span class="text-muted" style="font-size: 12px; font-weight: 600;">{{ __('Running') }}</span>
                                                    </div>
                                                    <span class="badge rounded-pill" style="background-color: #2196F3; font-size: 11px;">{{ auth()->user()->tasks()->where('status', '!=', 'completed')->where('status', '!=', 'canceled')->count() }}</span>
                                                </div>
                                                <!-- Completed Tasks -->
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <div class="rounded p-1 me-2" style="background: rgba(156, 39, 176, 0.1);"><i class="ti ti-checkbox fs-6" style="color: #9C27B0;"></i></div>
                                                        <span class="text-muted" style="font-size: 12px; font-weight: 600;">{{ __('Completed') }}</span>
                                                    </div>
                                                    <span class="badge rounded-pill" style="background-color: #9C27B0; font-size: 11px;">{{ auth()->user()->tasks()->where('status', 'completed')->count() }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Floating Add Task Button (Bottom Right) -->
                                <div class="position-absolute" style="bottom: 20px; right: 20px; z-index: 1000;">
                                    <button class="btn btn-primary rounded-pill shadow-lg px-4 py-2 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#taskTypeModal" style="background-color: #696cff; border: none;">
                                        <i class="ti ti-bolt fs-5 text-white"></i> 
                                        <span class="fw-bold text-white">{{ __('New Fast Task') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div> <!-- End Map Tab Pane -->
                </div> <!-- End Tabs Content -->
            </div>
        </div>
    </div>

    @include('customers.tasks.from-modal')
    @include('customers.tasks.payment-modal')

@endsection
