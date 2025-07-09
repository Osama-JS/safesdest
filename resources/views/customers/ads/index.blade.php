@extends('layouts/layoutMaster')

@section('title', __('Tasks Ads'))

<!-- Vendor Styles -->
@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css" />
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    /* تنسيق البطاقة */

    <style>
        /* Enhanced Customer Ads Dashboard Styling */
        .customer-ads-container {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 1rem 0;
        }

        /* Enhanced Header */
        .ads-header {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e7eef7;
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .ads-header .card-header {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
            border: none;
            padding: 2rem;
        }

        .ads-header h5 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .ads-header .header-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        /* Enhanced Filters Section */
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

        /* Enhanced Ad Cards - Matching Admin Design */
        .ad-card {
            transition: all 0.3s ease;
            border: 1px solid #e7eef7;
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
            position: relative;
            background: white;
            margin-bottom: 2rem;
        }

        .ad-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #696cff;
        }

        /* Map Container Styling - Matching Admin */
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

        /* Badge Styling - Matching Admin */
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

        /* Avatar Styling - Matching Admin */
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

        /* Card Content Styling - Matching Admin */
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

        /* Address Styling - Matching Admin */
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

        /* Card Footer Styling - Matching Admin */
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

        /* Enhanced Pagination */
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

        /* Loading and Empty States */
        .loading-state {
            text-align: center;
            padding: 4rem 2rem;
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
            .customer-ads-container {
                padding: 0.5rem;
            }

            .ads-header .card-header {
                padding: 1.5rem;
            }

            .ads-header h5 {
                font-size: 1.5rem;
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .filters-section {
                padding: 1rem;
            }

            .ad-card .card-body {
                padding: 1.5rem;
            }

            .location-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .location-value {
                max-width: 100%;
                text-align: left;
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
    @vite(['resources/js/customers/ads.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-2">
                        <i class="tf-icons ti ti-speakerphone me-2 fs-3 text-white bg-primary rounded p-1"></i>
                        {{ __('Tasks Ads') }}
                    </h5>
                    <p class="text-muted mb-0">Browse and manage all task advertisements in the system</p>
                </div>

            </div>
        </div>
    </div>
    <div class="customer-ads-container">
        <!-- Enhanced Header -->


        <!-- Enhanced Filters Section -->
        <div class="filters-section fade-in">
            <div class="row">
                <div class="col-md-4">
                    <div class="filter-control">
                        <label class="form-label fw-semibold">Search</label>
                        <input type="text" id="search-ads" class="form-control" placeholder="Search in ads...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="filter-control">
                        <label class="form-label fw-semibold">Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">All Status</option>
                            <option value="running">Running</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="filter-control">
                        <label class="form-label fw-semibold">Price Range</label>
                        <select id="filter-price" class="form-select">
                            <option value="">All Prices</option>
                            <option value="0-100">0 - 100 SAR</option>
                            <option value="100-500">100 - 500 SAR</option>
                            <option value="500+">500+ SAR</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
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

        <!-- Ads Container -->
        <div class="container-fluid">
            <div id="ads-container" class="row">
                <!-- Loading State -->
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Loading ads...</p>
                </div>
            </div>

            <!-- Pagination Container -->
            <div id="pagination-container"></div>
        </div>
    </div>
@endsection
