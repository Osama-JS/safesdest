@extends('layouts/layoutMaster')

@section('title', __('Vehicles'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    <style>
        .vehicle-icon {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 8px;
        }

        .statics {
            position: sticky;
            top: 20px;
            /* المسافة من أعلى الشاشة */
            z-index: 1000;
            /* للتأكد أنه فوق العناصر الأخرى إذا لزم */
        }

        .nav-pills .nav-link {
            border-radius: 25px;
            padding: 12px 24px;
            margin: 0 5px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .nav-pills .nav-link:hover:not(.active) {
            background-color: #f8f9fa;
            transform: translateY(-1px);
        }

        .form-section {
            background: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            border: 1px solid #e3e6f0;
        }

        .form-group label {
            font-weight: 600;
            color: #5a5c69;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e3e6f0;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .table-enhanced {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
        }

        .table-enhanced thead th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            font-weight: 600;
            color: #5a5c69;
            padding: 15px;
        }

        .table-enhanced tbody td {
            padding: 15px;
            border-color: #f1f3f4;
            vertical-align: middle;
        }

        .table-enhanced tbody tr:hover {
            background-color: #f8f9ff;
        }

        .btn-action {
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 2px;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: scale(1.1);
        }

        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
        }

        .vehicle-stats-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .type-stats-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .size-stats-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .tab-content {
            padding: 20px 0;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state h6 {
            margin-bottom: 10px;
            color: #495057;
        }

        .empty-state p {
            margin-bottom: 0;
            font-size: 0.875rem;
        }

        /* Enhanced table styling */
        .table-enhanced {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            margin-top: 20px;
        }

        .table-enhanced .card-header {
            border-bottom: 1px solid #e9ecef;
            padding: 20px;
        }

        /* Avatar enhancements */
        .avatar-initial {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }

        /* Badge styling */
        .badge {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
        }

        /* Table hover effects */
        .table-hover tbody tr:hover {
            background-color: rgba(67, 89, 113, 0.05);
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 15px;
            }

            .filter-section {
                flex-direction: column;
                gap: 15px !important;
            }

            .btn-action {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            .table-enhanced .card-header {
                padding: 15px;
            }

            .table-enhanced .card-header .d-flex {
                flex-direction: column;
                gap: 15px;
            }
        }

        /* Pagination Styling */
        .pagination-wrapper {
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            border-radius: 0 0 15px 15px;
        }

        .pagination-info {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .pagination .page-link {
            border: none;
            color: #6c757d;
            padding: 0.375rem 0.75rem;
            margin: 0 2px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .pagination .page-link:hover {
            background-color: #e9ecef;
            color: #495057;
        }

        .pagination .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            color: white;
        }

        .pagination .page-item.disabled .page-link {
            color: #adb5bd;
            background-color: transparent;
        }

        .pagination-controls .form-select {
            min-width: 70px;
        }

        /* Hide pagination when not needed */
        .pagination-wrapper.d-none {
            display: none !important;
        }
    </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
    @vite(['resources/js/admin/vehicles.js'])

@endsection

@section('content')
    <!-- Enhanced Header -->
    <div class="card vehicle-header-card mb-4">
        <div class="card-header border-0">
            <div class="d-flex align-items-center">
                <div class="vehicle-icon me-3">
                    <i class="tf-icons ti ti-adjustments me-2 fs-3 text-white bg-primary rounded p-1"></i>

                </div>
                <div>
                    <h4 class="card-title mb-1">
                        {{ __('Settings') }} | {{ __('Vehicles Management') }}
                    </h4>
                    <p class="mb-0">
                        {{ __('Managing the types of vehicles and trucks that will provide delivery services on the platform') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->

    <div class="row mt-6">
        <div class="col-md-8 order-sm-2">
            <div class="nav-align-top mb-6">
                <ul class="nav nav-pills mb-4" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-pills-top-home" aria-controls="navs-pills-top-home"
                            aria-selected="true">{{ __('Vehicles') }}</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-pills-top-profile" aria-controls="navs-pills-top-profile"
                            aria-selected="false">{{ __('Vehicles Types') }}</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-pills-top-messages" aria-controls="navs-pills-top-messages"
                            aria-selected="false">{{ __('Vehicles Sizes') }}</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="navs-pills-top-home" role="tabpanel">
                        <!-- Vehicle Form Section -->
                        <div class="form-section">
                            <div class="d-flex align-items-center mb-3">
                                <i class="ti ti-plus-circle me-2 text-primary fs-4"></i>
                                <h5 class="mb-0">{{ __('Add New Vehicle') }}</h5>
                            </div>
                            <form action="{{ route('settings.vehicles.store') }}" method="post" class="form_submit">
                                @csrf
                                <div class="row">
                                    <input type="hidden" name="id" id="vehicle-id">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="vehicle-name" class="form-label">
                                                <i class="ti ti-car me-1"></i>
                                                * {{ __('Arabic Name') }}
                                            </label>
                                            <input type="text" name="v_name" id="vehicle-name" class="form-control"
                                                placeholder="{{ __('Enter vehicle name in Arabic') }}">
                                            <span class="v_name-error text-danger text-error"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="vehicle-en-name" class="form-label">
                                                <i class="ti ti-car me-1"></i>
                                                * {{ __('English Name') }}
                                            </label>
                                            <input type="text" name="v_en_name" id="vehicle-en-name" class="form-control"
                                                placeholder="{{ __('Enter vehicle name in English') }}">
                                            <span class="v_en_name-error text-danger text-error"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i>
                                        {{ __('Save Vehicle') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                        <!-- Vehicles Table Section -->
                        <div class="table-enhanced">
                            <div class="card-header bg-light">
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-list me-2 text-primary"></i>
                                    <h6 class="mb-0">{{ __('Vehicles List') }}</h6>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="60">#</th>
                                            <th>
                                                <i class="ti ti-car me-1"></i>
                                                {{ __('Vehicle Name') }}
                                            </th>
                                            <th>
                                                <i class="ti ti-category me-1"></i>
                                                {{ __('Available Types') }}
                                            </th>
                                            <th class="text-center" width="120">
                                                <i class="ti ti-settings me-1"></i>
                                                {{ __('Actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="vehicle-table">
                                        <!-- Dynamic content will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="empty-state d-none" id="vehicles-empty">
                                <i class="ti ti-car-off"></i>
                                <h6>{{ __('No Vehicles Found') }}</h6>
                                <p>{{ __('Start by adding your first vehicle above') }}</p>
                            </div>

                            <!-- Vehicles Pagination -->
                            <div class="pagination-wrapper" id="vehicles-pagination-wrapper">
                                <div class="d-flex justify-content-between align-items-center p-3">
                                    <div class="pagination-info">
                                        <span class="text-muted" id="vehicles-pagination-info">{{ __('Showing') }} 0
                                            {{ __('to') }} 0 {{ __('of') }} 0 {{ __('entries') }}</span>
                                    </div>
                                    <div class="pagination-controls">
                                        <div class="d-flex align-items-center gap-2">
                                            <label class="form-label mb-0">{{ __('Show') }}:</label>
                                            <select class="form-select form-select-sm" id="vehicles-per-page"
                                                style="width: auto;">
                                                <option value="5">5</option>
                                                <option value="10" selected>10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                            </select>
                                        </div>
                                    </div>
                                    <nav aria-label="Vehicles pagination">
                                        <ul class="pagination pagination-sm mb-0" id="vehicles-pagination">
                                            <!-- Pagination buttons will be generated here -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="tab-pane fade" id="navs-pills-top-profile" role="tabpanel">
                        <!-- Vehicle Types Form Section -->
                        <div class="form-section">
                            <div class="d-flex align-items-center mb-3">
                                <i class="ti ti-category-plus me-2 text-primary fs-4"></i>
                                <h5 class="mb-0">{{ __('Add Vehicle Type') }}</h5>
                            </div>
                            <form action="{{ route('settings.vehicles.store.type') }}" method="post"
                                class="form_submit">
                                @csrf
                                <div class="row">
                                    <input type="hidden" name="id" id="vehicle-type-id">
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="vehicle-type-name" class="form-label">
                                                <i class="ti ti-tag me-1"></i>
                                                * {{ __('Arabic Type Name') }}
                                            </label>
                                            <input type="text" name="name" id="vehicle-type-name"
                                                class="form-control" placeholder="{{ __('Enter type name in Arabic') }}">
                                            <span class="name-error text-danger text-error"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="vehicle-type-en-name" class="form-label">
                                                <i class="ti ti-tag me-1"></i>
                                                * {{ __('English Type Name') }}
                                            </label>
                                            <input type="text" name="en_name" id="vehicle-type-en-name"
                                                class="form-control"
                                                placeholder="{{ __('Enter type name in English') }}">
                                            <span class="en_name-error text-danger text-error"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="vehicle-type-vehicle" class="form-label">
                                                <i class="ti ti-car me-1"></i>
                                                * {{ __('Select Vehicle') }}
                                            </label>
                                            <select name="vehicle" id="vehicle-type-vehicle"
                                                class="form-select vehicle-type-vehicle">
                                                <option value="">{{ __('Choose a vehicle') }}</option>
                                            </select>
                                            <span class="vehicle-error text-danger text-error"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i>
                                        {{ __('Save Type') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                        <!-- Vehicle Types Table Section -->
                        <div class="table-enhanced">
                            <div class="card-header bg-light">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-list me-2 text-primary"></i>
                                        <h6 class="mb-0">{{ __('Vehicle Types List') }}</h6>
                                    </div>
                                    <div class="filter-section">
                                        <label for="type-vehicle-flitter"
                                            class="form-label mb-1">{{ __('Filter by Vehicle') }}</label>
                                        <select name="flitter-vehicle" id="type-vehicle-flitter"
                                            class="form-select form-select-sm vehicle-type-vehicle">
                                            <option value="">{{ __('All Vehicles') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="60">#</th>
                                            <th>
                                                <i class="ti ti-car me-1"></i>
                                                {{ __('Vehicle') }}
                                            </th>
                                            <th>
                                                <i class="ti ti-tag me-1"></i>
                                                {{ __('Type Name') }}
                                            </th>
                                            <th>
                                                <i class="ti ti-dimensions me-1"></i>
                                                {{ __('Available Sizes') }}
                                            </th>
                                            <th class="text-center" width="120">
                                                <i class="ti ti-settings me-1"></i>
                                                {{ __('Actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="types-table">
                                        <!-- Dynamic content will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="empty-state d-none" id="types-empty">
                                <i class="ti ti-category-off"></i>
                                <h6>{{ __('No Vehicle Types Found') }}</h6>
                                <p>{{ __('Add vehicle types to organize your fleet') }}</p>
                            </div>

                            <!-- Types Pagination -->
                            <div class="pagination-wrapper" id="types-pagination-wrapper">
                                <div class="d-flex justify-content-between align-items-center p-3">
                                    <div class="pagination-info">
                                        <span class="text-muted" id="types-pagination-info">{{ __('Showing') }} 0
                                            {{ __('to') }} 0 {{ __('of') }} 0 {{ __('entries') }}</span>
                                    </div>
                                    <div class="pagination-controls">
                                        <div class="d-flex align-items-center gap-2">
                                            <label class="form-label mb-0">{{ __('Show') }}:</label>
                                            <select class="form-select form-select-sm" id="types-per-page"
                                                style="width: auto;">
                                                <option value="5">5</option>
                                                <option value="10" selected>10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                            </select>
                                        </div>
                                    </div>
                                    <nav aria-label="Types pagination">
                                        <ul class="pagination pagination-sm mb-0" id="types-pagination">
                                            <!-- Pagination buttons will be generated here -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="navs-pills-top-messages" role="tabpanel">
                        <!-- Vehicle Sizes Form Section -->
                        <div class="form-section">
                            <div class="d-flex align-items-center mb-3">
                                <i class="ti ti-dimensions me-2 text-primary fs-4"></i>
                                <h5 class="mb-0">{{ __('Add Vehicle Size') }}</h5>
                            </div>
                            <form action="{{ route('settings.vehicles.store.size') }}" method="post"
                                class="form_submit">
                                @csrf
                                <div class="row">
                                    <input type="hidden" name="id" id="vehicle-size-id">
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="vehicle-size-name" class="form-label">
                                                <i class="ti ti-ruler me-1"></i>
                                                * {{ __('Size Name') }}
                                            </label>
                                            <input type="text" name="name" id="vehicle-size-name"
                                                class="form-control" placeholder="{{ __('Enter size name') }}">
                                            <span class="name-error text-danger text-error"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="vehicle-size-vehicle" class="form-label">
                                                <i class="ti ti-car me-1"></i>
                                                * {{ __('Select Vehicle') }}
                                            </label>
                                            <select name="vehicle" id="vehicle-size-vehicle"
                                                class="form-select vehicle-type-vehicle">
                                                <option value="">{{ __('Choose a vehicle') }}</option>
                                            </select>
                                            <span class="vehicle-error text-danger text-error"></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label for="vehicle-size-type" class="form-label">
                                                <i class="ti ti-category me-1"></i>
                                                * {{ __('Select Vehicle Type') }}
                                            </label>
                                            <select name="type" id="vehicle-size-type"
                                                class="form-select vehicle-sizes-vehicle">
                                                <option value="">{{ __('Choose a type') }}</option>
                                            </select>
                                            <span class="type-error text-danger text-error"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ti ti-device-floppy me-1"></i>
                                        {{ __('Save Size') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                        <!-- Vehicle Sizes Table Section -->
                        <div class="table-enhanced">
                            <div class="card-header bg-light">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-list me-2 text-primary"></i>
                                        <h6 class="mb-0">{{ __('Vehicle Sizes List') }}</h6>
                                    </div>
                                    <div class="filter-section d-flex gap-3">
                                        <div>
                                            <label for="size-vehicle-flitter"
                                                class="form-label mb-1">{{ __('Filter by Vehicle') }}</label>
                                            <select name="flitter-vehicle" id="size-vehicle-flitter"
                                                class="form-select form-select-sm vehicle-type-vehicle">
                                                <option value="">{{ __('All Vehicles') }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="size-type-flitter"
                                                class="form-label mb-1">{{ __('Filter by Type') }}</label>
                                            <select name="flitter-type" id="size-type-flitter"
                                                class="form-select form-select-sm vehicle-sizes-vehicle">
                                                <option value="">{{ __('All Types') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="60">#</th>
                                            <th>
                                                <i class="ti ti-car me-1"></i>
                                                {{ __('Vehicle') }}
                                            </th>
                                            <th>
                                                <i class="ti ti-category me-1"></i>
                                                {{ __('Vehicle Type') }}
                                            </th>
                                            <th>
                                                <i class="ti ti-ruler me-1"></i>
                                                {{ __('Size') }}
                                            </th>
                                            <th class="text-center" width="120">
                                                <i class="ti ti-settings me-1"></i>
                                                {{ __('Actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="sizes-table">
                                        <!-- Dynamic content will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="empty-state d-none" id="sizes-empty">
                                <i class="ti ti-dimensions-off"></i>
                                <h6>{{ __('No Vehicle Sizes Found') }}</h6>
                                <p>{{ __('Add vehicle sizes to complete your fleet configuration') }}</p>
                            </div>

                            <!-- Sizes Pagination -->
                            <div class="pagination-wrapper" id="sizes-pagination-wrapper">
                                <div class="d-flex justify-content-between align-items-center p-3">
                                    <div class="pagination-info">
                                        <span class="text-muted" id="sizes-pagination-info">{{ __('Showing') }} 0
                                            {{ __('to') }} 0 {{ __('of') }} 0 {{ __('entries') }}</span>
                                    </div>
                                    <div class="pagination-controls">
                                        <div class="d-flex align-items-center gap-2">
                                            <label class="form-label mb-0">{{ __('Show') }}:</label>
                                            <select class="form-select form-select-sm" id="sizes-per-page"
                                                style="width: auto;">
                                                <option value="5">5</option>
                                                <option value="10" selected>10</option>
                                                <option value="25">25</option>
                                                <option value="50">50</option>
                                            </select>
                                        </div>
                                    </div>
                                    <nav aria-label="Sizes pagination">
                                        <ul class="pagination pagination-sm mb-0" id="sizes-pagination">
                                            <!-- Pagination buttons will be generated here -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 order-sm-1 statics">
            <div class="row mb-4" id="stats-section">
                <div class="col-md-12 col-sm-4 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon vehicle-stats-icon">
                            <i class="ti ti-truck"></i>
                        </div>
                        <h3 class="mb-1" id="vehicles-count">0</h3>
                        <p class="text-muted mb-0">{{ __('Total Vehicles') }}</p>
                    </div>
                </div>
                <div class="col-md-12 col-sm-4 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon type-stats-icon">
                            <i class="ti ti-category"></i>
                        </div>
                        <h3 class="mb-1" id="types-count">0</h3>
                        <p class="text-muted mb-0">{{ __('Vehicle Types') }}</p>
                    </div>
                </div>
                <div class="col-md-12 col-sm-4 mb-3">
                    <div class="stats-card">
                        <div class="stats-icon size-stats-icon">
                            <i class="ti ti-dimensions"></i>
                        </div>
                        <h3 class="mb-1" id="sizes-count">0</h3>
                        <p class="text-muted mb-0">{{ __('Vehicle Sizes') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>



@endsection
