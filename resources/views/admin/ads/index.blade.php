@extends('layouts/layoutMaster')

@section('title', __('Tasks Ads'))

<!-- Vendor Styles -->
@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css" />
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    /* تنسيق البطاقة */

    <style>
        /* Enhanced Ad Card Styling */
        .ad-card {
            transition: all 0.3s ease;
            border: 1px solid #e7eef7;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
            position: relative;
        }

        .ad-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #696cff;
        }

        .ad-card .card-img-top {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid #e7eef7;
        }

        /* Map Container Styling */
        .map-container {
            height: 180px;
            width: 100%;
            position: relative;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #e7eef7;
        }

        .map-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(105, 108, 255, 0.05);
            z-index: 1;
        }

        /* Avatar Styling */
        .avatar {
            width: 45px;
            height: 45px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 12px;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-initial {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }

        /* Card Content Styling */
        .ad-card .card-body {
            padding: 20px;
            flex-grow: 1;
        }

        .ad-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #5a6c7d;
            margin-bottom: 0;
            line-height: 1.3;
        }

        .ad-card .card-text {
            color: #8a92a6;
            font-size: 0.875rem;
            margin-bottom: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Address Styling */
        .address-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
        }

        .address-info p {
            margin-bottom: 8px;
            font-size: 0.8rem;
            color: #5a6c7d;
        }

        .address-info p:last-child {
            margin-bottom: 0;
        }

        .address-info strong {
            color: #2c3e50;
            font-weight: 600;
        }

        /* Card Footer Styling */
        .ad-card .card-footer {
            background: #fff;
            border-top: 1px solid #e7eef7;
            padding: 16px 20px;
            margin-top: auto;
        }

        .price-info {
            font-size: 0.9rem;
            color: #696cff;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .ad-card .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .ad-card .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(105, 108, 255, 0.3);
        }

        /* Badge Styling */
        .badge-status {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 10;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .badge-ownership {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 10;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(105, 108, 255, 0.3);
        }

        .badge-running {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
        }

        .badge-closed {
            background: linear-gradient(135deg, #ea5455 0%, #e74c3c 100%);
            color: white;
        }

        /* Empty State Styling */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #8a92a6;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h5 {
            color: #5a6c7d;
            margin-bottom: 10px;
        }

        /* Filters Card Styling */
        #filters-card {
            border: 1px solid #e7eef7;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        #filters-card .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #e7eef7;
        }

        /* Stats Cards Styling */
        .stats-card {
            border: 1px solid #e7eef7;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        /* Pagination Styling */
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 2px;
            border: 1px solid #e7eef7;
            color: #5a6c7d;
            font-weight: 500;
        }

        .pagination .page-link:hover {
            background-color: #696cff;
            border-color: #696cff;
            color: white;
        }

        .pagination .page-item.active .page-link {
            background-color: #696cff;
            border-color: #696cff;
        }

        /* Loading Animation */
        .loading-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .ad-card .card-body {
                padding: 15px;
            }

            .address-info {
                padding: 10px;
            }

            .map-container {
                height: 150px;
            }
        }
    </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/ads.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
@endsection

@section('content')
    <!-- Header Card -->
    <div class="card">
        <div class="card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-2">
                        <i class="tf-icons ti ti-speakerphone me-2 fs-3 text-white bg-primary rounded p-1"></i>
                        {{ __('Tasks Ads') }}
                    </h5>
                    <p class="text-muted mb-0">{{ __('Browse and manage all task advertisements in the system') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" id="refresh-ads">
                        <i class="ti ti-refresh me-1"></i>
                        {{ __('Refresh') }}
                    </button>
                    <button class="btn btn-outline-primary" id="toggle-filters">
                        <i class="ti ti-filter me-1"></i>
                        {{ __('Filters') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Advanced Filters Card -->
    <div class="card mt-3" id="filters-card" style="display: none;">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="ti ti-adjustments me-2"></i>
                {{ __('Advanced Filters') }}
            </h6>
        </div>
        <div class="card-body">
            <form id="filters-form">
                <div class="row g-3">
                    <!-- Search Input -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Search') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" id="search-input" placeholder="{{ __('Search ads...') }}">
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select class="form-select" id="status-filter">
                            <option value="">{{ __('All Status') }}</option>
                            <option value="running">{{ __('Running') }}</option>
                            <option value="closed">{{ __('Closed') }}</option>
                        </select>
                    </div>

                    <!-- Price Range -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Price Range') }}</label>
                        <select class="form-select" id="price-range-filter">
                            <option value="">{{ __('All Prices') }}</option>
                            <option value="0-100">0 - 100 {{ __('SAR') }}</option>
                            <option value="100-500">100 - 500 {{ __('SAR') }}</option>
                            <option value="500-1000">500 - 1000 {{ __('SAR') }}</option>
                            <option value="1000+">1000+ {{ __('SAR') }}</option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Date Created') }}</label>
                        <select class="form-select" id="date-filter">
                            <option value="">{{ __('All Time') }}</option>
                            <option value="today">{{ __('Today') }}</option>
                            <option value="week">{{ __('This Week') }}</option>
                            <option value="month">{{ __('This Month') }}</option>
                        </select>
                    </div>

                    <!-- Owner Type Filter -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Owner Type') }}</label>
                        <select class="form-select" id="owner-filter">
                            <option value="">{{ __('All Owners') }}</option>
                            <option value="customer">{{ __('Customer') }}</option>
                            <option value="admin">{{ __('Admin') }}</option>
                        </select>
                    </div>

                    <!-- Sort Options -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Sort By') }}</label>
                        <select class="form-select" id="sort-filter">
                            <option value="newest">{{ __('Newest First') }}</option>
                            <option value="oldest">{{ __('Oldest First') }}</option>
                            <option value="price_high">{{ __('Price: High to Low') }}</option>
                            <option value="price_low">{{ __('Price: Low to High') }}</option>
                        </select>
                    </div>

                    <!-- Items Per Page -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Items Per Page') }}</label>
                        <select class="form-select" id="per-page-filter">
                            <option value="9">9 {{ __('Items') }}</option>
                            <option value="18">18 {{ __('Items') }}</option>
                            <option value="36">36 {{ __('Items') }}</option>
                        </select>
                    </div>

                    <!-- Filter Actions -->
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="btn-group w-100">
                            <button type="button" class="btn btn-primary" id="apply-filters">
                                <i class="ti ti-check me-1"></i>
                                {{ __('Apply') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="clear-filters">
                                <i class="ti ti-x me-1"></i>
                                {{ __('Clear') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mt-3" id="stats-cards">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-2">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-speakerphone fs-4"></i>
                        </span>
                    </div>
                    <h5 class="mb-1" id="total-ads">0</h5>
                    <small class="text-muted">{{ __('Total Ads') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-2">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-play fs-4"></i>
                        </span>
                    </div>
                    <h5 class="mb-1" id="running-ads">0</h5>
                    <small class="text-muted">{{ __('Running Ads') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-2">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-square fs-4"></i>
                        </span>
                    </div>
                    <h5 class="mb-1" id="closed-ads">0</h5>
                    <small class="text-muted">{{ __('Closed Ads') }}</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-2">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="ti ti-currency-riyal fs-4"></i>
                        </span>
                    </div>
                    <h5 class="mb-1" id="avg-price">0</h5>
                    <small class="text-muted">{{ __('Avg Price (SAR)') }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div class="card mt-3" id="loading-card" style="display: none;">
        <div class="card-body text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">{{ __('Loading...') }}</span>
            </div>
            <p class="mt-3 text-muted">{{ __('Loading advertisements...') }}</p>
        </div>
    </div>

    <!-- Ads Container -->
    <div class="mt-3">
        <div id="ads-container" class="row">
            <!-- Ads will be loaded here -->
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Ads pagination">
                <ul class="pagination" id="pagination">
                    <!-- Pagination will be loaded here -->
                </ul>
            </nav>
        </div>
    </div>



@endsection
