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
            border-radius: 20px;
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
@endsection

@section('content')

    <div class="customer-dashboard-container">
        <div class="row">
            <!-- Enhanced User Sidebar -->
            <div class="col-xl-3 col-lg-4 order-1 order-md-0">
                <!-- Enhanced User Card -->
                <div class="user-profile-card mb-6 fade-in">
                    <div class="card-body pt-12">
                        <div class="user-avatar-section">
                            <div class="d-flex align-items-center flex-column">
                                <img class="user-avatar mb-4"
                                    src="{{ auth()->user()->image ? asset(auth()->user()->image) : asset('assets/img/person.png') }}"
                                    alt="User avatar" />
                                <div class="user-info text-center">
                                    <h5>{{ auth()->user()->name }}</h5>
                                    <span
                                        class="badge bg-label-secondary">{{ auth()->user()->company_name ?? 'No Company' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Stats Section -->
                        <div class="user-stats">
                            <div class="row">
                                <div class="col-6">
                                    <div class="stat-item">
                                        <div class="avatar">
                                            <div class="avatar-initial">
                                                <i class='ti ti-checkbox'></i>
                                            </div>
                                        </div>
                                        <h5>{{ auth()->user()->tasks()->where('status', 'completed')->count() }}</h5>
                                        <span>Tasks Done</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-item">
                                        <div class="avatar">
                                            <div class="avatar-initial">
                                                <i class='ti ti-truck-delivery'></i>
                                            </div>
                                        </div>
                                        <h5>{{ auth()->user()->tasks()->where('status', '!=', 'completed')->where('status', '!=', 'canceled')->count() }}
                                        </h5>
                                        <span>Running Tasks</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="pb-4 border-bottom mb-4">Details</h5>
                        <div class="info-container">
                            <ul class="list-unstyled mb-6">
                                <li class="mb-3">
                                    <span class="h6 text-muted">Phone:</span><br>
                                    <span class="fw-semibold">{{ auth()->user()->phone }}</span>
                                </li>
                                <li class="mb-3">
                                    <span class="h6 text-muted">Email:</span><br>
                                    <span class="fw-semibold">{{ auth()->user()->email }}</span>
                                </li>
                                <li class="mb-3">
                                    <span class="h6 text-muted">Address:</span><br>
                                    <span class="fw-semibold">{{ auth()->user()->address ?? 'Not specified' }}</span>
                                </li>
                                <li class="mb-3">
                                    <span class="h6 text-muted">Company:</span><br>
                                    <span class="fw-semibold">{{ auth()->user()->company_name ?? 'Not specified' }}</span>
                                </li>
                                <li class="mb-3">
                                    <span class="h6 text-muted">Status:</span><br>
                                    <span class="badge bg-success">{{ ucfirst(auth()->user()->status) }}</span>
                                </li>
                                <li class="mb-3">
                                    <span class="h6 text-muted">Role:</span><br>
                                    <span class="fw-semibold">{{ ucfirst(auth()->user()->role) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- /Enhanced User Card -->
            </div>
            <!--/ Enhanced User Sidebar -->

            <!-- Enhanced Tasks Section -->
            <div class="col-xl-9 col-lg-8">
                <div class="tasks-section fade-in">
                    <!-- Enhanced Tasks Header -->
                    <div class="tasks-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <h4>
                                <div class="header-icon">
                                    <i class="ti ti-list-check"></i>
                                </div>
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
            </div>
        </div>
    </div>

    @include('customers.tasks.from-modal')

@endsection
