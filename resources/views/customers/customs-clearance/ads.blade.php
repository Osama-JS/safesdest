@extends('layouts/layoutMaster')

@section('title', __('Customs Clearance Ads'))

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

        /* Enhanced Cards */
        .enhanced-card {
            border: 1px solid #e7eef7;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            background: white;
        }

        .enhanced-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .enhanced-card .card-header {
            background: linear-gradient(135deg, #f1f3f4 0%, #e8eaed 100%);
            border-bottom: 1px solid #e7eef7;
            border-radius: 12px 12px 0 0;
            padding: 1.5rem;
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

        /* Filters Card Styling */
        #filters-card {
            border: 1px solid #e7eef7;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        #filters-card .card-header {
            background: white;
            border-bottom: 1px solid #e7eef7;
        }

        /* Driver specific enhancements */
        .driver-ads-header {
            background: white;
            border: 1px solid #e7eef7;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .vehicle-size-badge {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(105, 108, 255, 0.3);
        }

        .driver-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #e7eef7;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .driver-info-card .avatar {
            width: 60px;
            height: 60px;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Enhanced ad cards for drivers */
        .driver-ad-card {
            transition: all 0.3s ease;
            border: 1px solid #e7eef7;
            border-radius: 16px;
            overflow: hidden;
            height: 100%;
            position: relative;
            background: white;
        }

        .driver-ad-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
            border-color: #696cff;
        }

        .driver-ad-card .card-header {
            background: white;
            border-bottom: 1px solid #e7eef7;
            padding: 1.5rem;
        }

        .driver-ad-card .card-body {
            padding: 1.5rem;
        }

        .driver-ad-card .card-footer {
            background: #fafbfc;
            border-top: 1px solid #e7eef7;
            padding: 1.5rem;
        }

        .offer-status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 10;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .offer-status-badge.submitted {
            background: linear-gradient(135deg, #00cfe8 0%, #0dcaf0 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 207, 232, 0.3);
        }

        .offer-status-badge.not-submitted {
            background: linear-gradient(135deg, #ff9f43 0%, #ff8c00 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 159, 67, 0.3);
        }

        .offer-status-badge.accepted {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 199, 111, 0.3);
        }

        /* Ad Status Badges */
        .ad-status-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 10;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .ad-status-badge.running {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 199, 111, 0.3);
        }

        .ad-status-badge.closed {
            background: linear-gradient(135deg, #8a92a6 0%, #6c757d 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(138, 146, 166, 0.3);
        }

        /* Offer Information Section */
        .offer-info-section {
            margin-top: 1rem;
        }

        .offer-info-section .alert {
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
        }

        .offer-info-section .alert-success {
            background: linear-gradient(135deg, rgba(40, 199, 111, 0.1) 0%, rgba(32, 191, 107, 0.1) 100%);
            color: #28c76f;
            border-left: 4px solid #28c76f;
        }

        .offer-info-section .alert-info {
            background: linear-gradient(135deg, rgba(0, 207, 232, 0.1) 0%, rgba(13, 202, 240, 0.1) 100%);
            color: #00cfe8;
            border-left: 4px solid #00cfe8;
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

            .driver-info-card {
                padding: 1rem;
                text-align: center;
            }

            .driver-ad-card .card-body,
            .driver-ad-card .card-footer {
                padding: 1rem;
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
    @vite(['resources/js/customer/customs-clearances/ads.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
@endsection

@section('content')
    <!-- Driver Info Card -->
    <div class="driver-info-card">
        <div class="d-flex align-items-center">
            <div class="avatar me-3">
                @if (auth()->user()->image)
                    <img src="{{ asset(auth()->user()->image) }}" alt="Driver" class="rounded-circle">
                @else
                    <span class="avatar-initial rounded-circle bg-label-primary">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </span>
                @endif
            </div>
            <div class="flex-grow-1">
                <h5 class="mb-1">{{ auth()->user()->name }}</h5>
                <p class="mb-0 text-muted">{{ __('Customs Clearance Agent') }}</p>
            </div>

        </div>
    </div>

    <!-- Header Card -->
    <div class="card driver-ads-header">
        <div class="card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-2">
                        <i class="tf-icons ti ti-clipboard-check me-2 fs-3 text-white bg-primary rounded p-1"></i>
                        {{ __('Available Customs Clearance Advertisements') }}
                    </h5>
                    <p class="text-muted mb-0">Browse and submit offers for delivery Customs Clearance tasks </p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" id="refresh-ads">
                        <i class="ti ti-refresh me-1"></i>
                        Refresh
                    </button>

                </div>
            </div>
        </div>
    </div>



    <!-- Stats Cards -->
    <div class="row mt-3" id="stats-cards">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-2">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-speakerphone fs-4"></i>
                        </span>
                    </div>
                    <h5 class="mb-1" id="total-ads">0</h5>
                    <small class="text-muted">Available Customs Clearance Ads</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-2">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-check-circle fs-4"></i>
                        </span>
                    </div>
                    <h5 class="mb-1" id="my-offers">0</h5>
                    <small class="text-muted">My Offers</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar mx-auto mb-2">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="ti ti-currency-riyal fs-4"></i>
                        </span>
                    </div>
                    <h5 class="mb-1" id="avg-price">0</h5>
                    <small class="text-muted">Avg Price (SAR)</small>
                </div>
            </div>
        </div>

    </div>

    <!-- Loading State -->
    <div class="card mt-3" id="loading-card" style="display: none;">
        <div class="card-body text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading advertisements...</p>
        </div>
    </div>

    <!-- Ads Container -->
    <div class="mt-3">
        <div id="ads-container" class="row">
            <!-- Ads will be loaded here -->
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="empty-state" style="display: none;">
            <i class="ti ti-speakerphone-off"></i>
            <h5>No Advertisements Available</h5>
            <p>There are currently no task advertisements matching your vehicle size available for your area.</p>
            <small class="text-muted">Only ads for {{ auth()->user()->vehicleSize->name ?? 'your vehicle size' }} are
                shown</small>
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
