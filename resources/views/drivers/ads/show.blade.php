@extends('layouts/layoutMaster')

@section('title', __('Tasks Ads'))

<!-- Vendor Styles -->
@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css"
        @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']) <style>
    /* تنسيق البطاقة */


   <style>
        /* Enhanced Ad Details Page Styling */
        .ad-details-page {
            background: #f8f9fa;
            min-height: 100vh;
        }

        /* Header Section */
        .page-header {
            background: #ffffff;

            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .page-header .breadcrumb {
            background: rgba(90, 108, 125, 0.1);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
        }

        .page-header .breadcrumb-item a {
            color: #5a6c7d;
            text-decoration: none;
            opacity: 0.8;
        }

        .page-header .breadcrumb-item a:hover {
            opacity: 1;
        }

        .page-header .breadcrumb-item.active {
            color: #5a6c7d;
            font-weight: 600;
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

        .enhanced-card .card-body {
            padding: 1.5rem;
        }

        /* Status Badges */
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-running {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 199, 111, 0.3);
        }

        .status-closed {
            background: linear-gradient(135deg, #ea5455 0%, #e74c3c 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(234, 84, 85, 0.3);
        }

        /* Price Display */
        .price-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #e7eef7;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .price-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #e7eef7;
            transition: all 0.3s ease;
        }

        .price-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .price-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .price-icon.highest {
            background: linear-gradient(135deg, #ea5455 0%, #e74c3c 100%);
            color: white;
        }

        .price-icon.lowest {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
        }

        .price-icon.status {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
        }

        /* Location Cards */
        .location-card {
            background: white;
            border: 1px solid #e7eef7;
            border-radius: 12px;
            padding: 1.5rem;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .location-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #696cff 0%, #5a67d8 100%);
        }

        .location-card.pickup::before {
            background: linear-gradient(90deg, #696cff 0%, #5a67d8 100%);
        }

        .location-card.delivery::before {
            background: linear-gradient(90deg, #28c76f 0%, #20bf6b 100%);
        }

        .location-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .location-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e7eef7;
        }

        .location-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .location-icon.pickup {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
        }

        .location-icon.delivery {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
        }

        .location-info {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .location-info li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f2f5;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .location-info li:last-child {
            border-bottom: none;
        }

        .location-info li strong {
            color: #5a6c7d;
            min-width: 80px;
            font-weight: 600;
        }

        /* Owner Info */
        .owner-info {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #e7eef7;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .owner-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            margin-right: 1rem;
            box-shadow: 0 4px 12px rgba(105, 108, 255, 0.3);
        }

        /* Additional Data */
        .additional-data-item {
            background: white;
            border: 1px solid #e7eef7;
            border-radius: 8px;
            padding: 1rem;
            height: 100%;
            transition: all 0.3s ease;
        }

        .additional-data-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .additional-data-item h6 {
            color: #696cff;
            font-weight: 600;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e7eef7;
        }

        /* Offers Section */
        .offers-card {
            background: white;
            border: 1px solid #e7eef7;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            min-height: 80vh;
        }

        .offers-header {
            background: linear-gradient(135deg, #f1f3f4 0%, #e8eaed 100%);
            color: #5a6c7d;
            padding: 1.5rem;
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #e7eef7;
            display: flex;
            align-items: center;
            justify-content: between;
        }

        .offers-counter {
            background: rgba(90, 108, 125, 0.1);
            color: #5a6c7d;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 600;
            margin-left: auto;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem;
                margin-bottom: 1rem;
            }

            .price-container {
                padding: 1rem;
            }

            .location-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }

            .enhanced-card .card-body {
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

        /* Loading States */
        .loading-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
            border-radius: 4px;
            height: 20px;
            margin-bottom: 10px;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        /* Enhanced Offer Cards Styling */
        .offer-card {
            background: white;
            border: 1px solid #e7eef7;
            border-radius: 12px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }

        .offer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #696cff;
        }

        .offer-card.offer-accepted {
            border-color: #28c76f;
            background: linear-gradient(135deg, rgba(40, 199, 111, 0.02) 0%, rgba(32, 191, 107, 0.02) 100%);
        }

        .offer-card.my-offer {
            border-color: #696cff;
            background: linear-gradient(135deg, rgba(105, 108, 255, 0.02) 0%, rgba(90, 103, 216, 0.02) 100%);
        }

        .offer-card.my-offer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #696cff 0%, #5a67d8 100%);
        }

        .offer-card.offer-accepted::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #28c76f 0%, #20bf6b 100%);
        }

        /* Offer Header */
        .offer-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 20px 20px 16px;
            border-bottom: 1px solid #f0f2f5;
        }

        .driver-info {
            display: flex;
            gap: 12px;
            flex: 1;
        }

        .driver-avatar {
            position: relative;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #e7eef7;
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .driver-avatar .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .driver-avatar .avatar-initial {
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .my-offer-badge {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 18px;
            height: 18px;
            background: #696cff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }

        .my-offer-badge i {
            font-size: 10px;
            color: white;
        }

        .driver-details {
            flex: 1;
        }

        .driver-name {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            color: #2c3e50;
        }

        .driver-contact,
        .offer-time {
            display: flex;
            align-items: center;
            font-size: 13px;
            color: #8a92a6;
            margin-bottom: 2px;
        }

        .driver-contact i,
        .offer-time i {
            font-size: 12px;
            opacity: 0.7;
        }

        /* Offer Status */
        .offer-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }

        .status-badge {
            display: flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.accepted {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 199, 111, 0.3);
        }

        .status-badge.pending {
            background: linear-gradient(135deg, #ff9f43 0%, #ff8c00 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 159, 67, 0.3);
        }

        .my-offer-label {
            font-size: 11px;
            color: #696cff;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Offer Content */
        .offer-content {
            padding: 16px 20px;
        }

        .price-section {
            margin-bottom: 16px;
        }

        .price-label {
            font-size: 12px;
            color: #8a92a6;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .price-value {
            display: flex;
            align-items: baseline;
            gap: 4px;
        }

        .price-value .amount {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }

        .price-value .currency {
            font-size: 14px;
            color: #8a92a6;
            font-weight: 500;
        }

        .notes-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
        }

        .notes-label {
            display: flex;
            align-items: center;
            font-size: 12px;
            color: #5a6c7d;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .notes-content {
            font-size: 14px;
            color: #2c3e50;
            line-height: 1.5;
            margin: 0;
        }

        /* Offer Footer */
        .offer-footer {
            padding: 12px 20px;
            border-top: 1px solid #f0f2f5;
            background: #fafbfc;
        }

        .driver-rating {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rating-stars {
            display: flex;
            gap: 2px;
        }

        .rating-stars i {
            font-size: 14px;
        }

        .rating-value {
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
        }

        .rating-count {
            font-size: 12px;
            color: #8a92a6;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state i {
            opacity: 0.5;
            margin-bottom: 16px;
        }

        .empty-state h6 {
            color: #5a6c7d;
            margin-bottom: 8px;
        }

        .empty-state p {
            color: #8a92a6;
            font-size: 14px;
        }

        /* Responsive Design for Offers */
        @media (max-width: 768px) {
            .offer-header {
                padding: 16px;
            }

            .offer-content {
                padding: 12px 16px;
            }

            .driver-avatar {
                width: 45px;
                height: 45px;
            }

            .price-value .amount {
                font-size: 20px;
            }

            .driver-name {
                font-size: 15px;
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
    <script>
      const adId = {{ $ad->id }}
    </script>
    @vite(['resources/js/driver/offers.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
@endsection
@section('ad-isactive', 'active')
@section('content')
    <div class="container-fluid ad-details-page">
        <!-- Page Header -->
        <div class="page-header fade-in">

            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2">
                        <i class="ti ti-speakerphone me-2"></i>
                        Task Advertisement Details
                    </h2>
                    <p class="mb-0 opacity-75">View task details and submit your offer</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>
                        Back to Ads
                    </a>

                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Side: Ad and Task Details -->
            <div class="col-lg-7 col-md-12">
                <!-- Ad Details Card -->
                <div class="enhanced-card mb-4 fade-in">
                    <div class="card-header">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="ti ti-speakerphone me-2 text-primary"></i>
                            {{ __('Advertisement Information') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Price and Status Information -->
                        <div class="price-container">
                            <div class="row g-3">
                                @if ($ad->highest_price)
                                    <div class="col-md-4">
                                        <div class="price-item">
                                            <div class="price-icon highest">
                                                <i class="ti ti-trending-up"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">{{ __('Highest Price') }}</small>
                                                <div class="fw-bold text-danger fs-5">
                                                    {{ number_format($ad->highest_price, 0) }} <small>SAR</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($ad->lowest_price)
                                    <div class="col-md-4">
                                        <div class="price-item">
                                            <div class="price-icon lowest">
                                                <i class="ti ti-trending-down"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">{{ __('Lowest Price') }}</small>
                                                <div class="fw-bold text-success fs-5">
                                                    {{ number_format($ad->lowest_price, 0) }} <small>SAR</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="col-md-4">
                                    <div class="price-item">
                                        <div class="price-icon status">
                                            <i class="ti ti-{{ $ad->status == 'running' ? 'lock-open' : 'lock' }}"></i>
                                        </div>
                                        <div>
                                            <span class="status-badge status-{{ $ad->status }}">
                                                {{ ucfirst($ad->status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ad Description -->
                        <div class="mt-4">
                            <h6 class="text-primary mb-3">
                                <i class="ti ti-notes me-2"></i>{{ __('Task Description') }}
                            </h6>
                            <div class="bg-light rounded p-3">
                                <p class="mb-0 text-dark">{{ $ad->description ?: 'No description provided for this task.' }}</p>
                            </div>
                        </div>

                        <div class="mt-4">

                            <div class=" rounded p-3">
                                @if ($task->vehicle_size_id)
                                    <div class="col-md-6 col-lg-4">
                                        <div class="additional-data-item text-center">
                                            <i class="ti ti-truck text-primary fs-2 mb-3"></i>
                                            <h6 class="text-primary">{{ __('Vehicle') }}</h6>
                                            @php
                                                $vehicle = $task->vehicle_size->type->vehicle->name ?? 'غير معروف';
                                                $type = $task->vehicle_size->type->name ?? 'غير معروف';
                                                $size = $task->vehicle_size->name ?? 'غير معروف';
                                            @endphp

                                            <p class="mb-0 fw-semibold">
                                                {{ $vehicle }} - {{ $type }} - {{ $size }}
                                            </p>

                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
            </div>

                <!-- Pickup & Delivery Points -->
                <div class="enhanced-card mb-4 fade-in">
                    <div class="card-header">
                        <h5 class="mb-0 d-flex align-items-center">
                            <i class="ti ti-route me-2 text-primary"></i>
                            {{ __('Pickup & Delivery Points') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <!-- Pickup Point -->
                            <div class="col-md-6">
                                <div class="location-card pickup">
                                    <div class="location-header">
                                        <div class="d-flex align-items-center">
                                            <div class="location-icon pickup">
                                                <i class="ti ti-map-pin"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h6 class="mb-0 text-primary">{{ __('Pickup Point') }}</h6>
                                                <small class="text-muted">Collection Location</small>
                                            </div>
                                        </div>
                                        @if (optional($task->pickup)->latitude && optional($task->pickup)->longitude)
                                            <a href="https://www.google.com/maps?q={{ $task->pickup->latitude }},{{ $task->pickup->longitude }}"
                                                target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-external-link me-1"></i>
                                                View Map
                                            </a>
                                        @endif
                                    </div>
                                    <ul class="location-info">
                                        <li>
                                            <strong>Address:</strong>
                                            <span>{{ optional($task->pickup)->address ?: 'Not specified' }}</span>
                                        </li>
                                        <li>
                                            <strong>Contact:</strong>
                                            <span>{{ optional($task->pickup)->contact_name ?: 'Not specified' }}</span>
                                        </li>
                                        @if (optional($task->pickup)->contact_phone)
                                            <li>
                                                <strong>Phone:</strong>
                                                <span>{{ $task->pickup->contact_phone }}</span>
                                            </li>
                                        @endif
                                        @if (optional($task->pickup)->scheduled_time)
                                            <li>
                                                <strong>Scheduled:</strong>
                                                <span>{{ \Carbon\Carbon::parse($task->pickup->scheduled_time)->format('M d, Y H:i') }}</span>
                                            </li>
                                        @endif
                                        @if (optional($task->pickup)->note)
                                            <li>
                                                <strong>Notes:</strong>
                                                <span>{{ $task->pickup->note }}</span>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>

                            <!-- Delivery Point -->
                            <div class="col-md-6">
                                <div class="location-card delivery">
                                    <div class="location-header">
                                        <div class="d-flex align-items-center">
                                            <div class="location-icon delivery">
                                                <i class="ti ti-truck-delivery"></i>
                                            </div>
                                            <div class="ms-3">
                                                <h6 class="mb-0 text-success">{{ __('Delivery Point') }}</h6>
                                                <small class="text-muted">Destination Location</small>
                                            </div>
                                        </div>
                                        @if (optional($task->delivery)->latitude && optional($task->delivery)->longitude)
                                            <a href="https://www.google.com/maps?q={{ $task->delivery->latitude }},{{ $task->delivery->longitude }}"
                                                target="_blank" class="btn btn-sm btn-outline-success">
                                                <i class="ti ti-external-link me-1"></i>
                                                View Map
                                            </a>
                                        @endif
                                    </div>
                                    <ul class="location-info">
                                        <li>
                                            <strong>Address:</strong>
                                            <span>{{ optional($task->delivery)->address ?: 'Not specified' }}</span>
                                        </li>
                                        <li>
                                            <strong>Contact:</strong>
                                            <span>{{ optional($task->delivery)->contact_name ?: 'Not specified' }}</span>
                                        </li>
                                        @if (optional($task->delivery)->contact_phone)
                                            <li>
                                                <strong>Phone:</strong>
                                                <span>{{ $task->delivery->contact_phone }}</span>
                                            </li>
                                        @endif
                                        @if (optional($task->delivery)->scheduled_time)
                                            <li>
                                                <strong>Scheduled:</strong>
                                                <span>{{ \Carbon\Carbon::parse($task->delivery->scheduled_time)->format('M d, Y H:i') }}</span>
                                            </li>
                                        @endif
                                        @if (optional($task->delivery)->note)
                                            <li>
                                                <strong>Notes:</strong>
                                                <span>{{ $task->delivery->note }}</span>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Data -->
                @if ($task->additional_data)
                    <div class="enhanced-card mb-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0 d-flex align-items-center">
                                <i class="ti ti-info-circle me-2 text-primary"></i>
                                {{ __('Additional Task Information') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            @if (is_array($task->driver_visible_additional_data) && count($task->driver_visible_additional_data) > 0)
                                <div class="row g-3">
                                    @foreach ($task->driver_visible_additional_data as $key => $field)
                                        <div class="col-md-6 mb-3">
                                            <div class="additional-data-item">
                                                <h6>{{ $field['label'] }}</h6>
                                                @switch($field['type'])
                                                    @case('text')
                                                    @case('string')
                                                    @case('number')
                                                        <p class="mb-0">{{ $field['value'] }}</p>
                                                        @break

                                                    @case('image')
                                                        <img src="{{ asset('storage/' . $field['value']) }}"
                                                             alt="{{ $field['label'] }}"
                                                             class="img-fluid rounded border"
                                                             style="max-height: 200px; object-fit: cover;">
                                                        @break

                                                    @case('file')
                                                        @php
                                                            $ext = strtolower(pathinfo($field['value'], PATHINFO_EXTENSION));
                                                            $icons = [
                                                                'pdf' => 'ti ti-file-text',
                                                                'doc' => 'ti ti-file-description',
                                                                'docx' => 'ti ti-file-description',
                                                                'xls' => 'ti ti-file-spreadsheet',
                                                                'xlsx' => 'ti ti-file-spreadsheet',
                                                                'ppt' => 'ti ti-presentation',
                                                                'pptx' => 'ti ti-presentation',
                                                            ];
                                                            $iconClass = $icons[$ext] ?? 'ti ti-file';
                                                        @endphp
                                                        <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                                           class="d-flex align-items-center text-decoration-none">
                                                            <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                            <span class="text-truncate">{{ basename($field['value']) }}</span>
                                                        </a>
                                                        @break

                                                    @case('file_expiration_date')
                                                        @php
                                                            $ext = strtolower(pathinfo($field['value'], PATHINFO_EXTENSION));
                                                            $icons = [
                                                                'pdf' => 'ti ti-file-text',
                                                                'doc' => 'ti ti-file-description',
                                                                'docx' => 'ti ti-file-description',
                                                                'xls' => 'ti ti-file-spreadsheet',
                                                                'xlsx' => 'ti ti-file-spreadsheet',
                                                                'ppt' => 'ti ti-presentation',
                                                                'pptx' => 'ti ti-presentation',
                                                            ];
                                                            $iconClass = $icons[$ext] ?? 'ti ti-file';
                                                        @endphp
                                                        <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                                           class="d-flex align-items-center text-decoration-none mb-2">
                                                            <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                            <span class="text-truncate">{{ basename($field['value']) }}</span>
                                                        </a>
                                                        <small class="text-muted">
                                                            <i class="ti ti-calendar me-1"></i>
                                                            Expires: {{ $field['expiration'] }}
                                                        </small>
                                                        @break

                                                    @default
                                                        <p class="mb-0">{{ $field['value'] }}</p>
                                                @endswitch
                                            </div>
                                        </div> @endforeach
                                </div>
                            @else
                                <div class="alert alert-info" role="alert">
                                    <i class="ti ti-info-circle me-2"></i>
                                    {{ __('No additional data available for this task.') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
        </div>

            <!-- Right Side: Offers Section -->
            <div class="col-md-5">
                <div class="offers-card fade-in">
                    <div class="offers-header">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-users me-2 fs-4"></i>
                            <div>
                                <h5 class="mb-0">{{ __('Submitted Offers') }}</h5>
                                <small class="opacity-75">All driver proposals for this task</small>
                            </div>
                        </div>
                        <div class="offers-counter">
                            <span id="total-offers-counter">0</span> Offers
                        </div>
                    </div>

                    <!-- Offer Actions -->
                    <div class="p-3 border-bottom">
                        @if ($offer && !$offer->accepted)
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#offerModal">
                                <i class="ti ti-edit me-1"></i>
                                {{ __('Update your offer') }}
                            </button>
                        @elseif (!$offer)
                            <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#offerModal">
                                <i class="ti ti-plus me-1"></i>
                                {{ __('Submit your offer') }}
                            </button>
                        @endif
                    </div>

                    <!-- Accepted Offer Alert -->
                    @if ($offer && $offer->accepted)
                        <div class="p-3 ">
                            <div class="offer-alert">
                                <div class="alert mb-3">
                                    <i class="ti ti-check-circle me-2"></i>
                                    Your offer has been accepted!
                                </div>
                                <div class="mb-3">
                                    <strong class="d-block mb-1">Accepted Price:</strong>
                                    <span class="fs-4 fw-bold">{{ number_format($offer->price, 0) }} SAR</span>
                                </div>
                                <div class="mb-3">
                                    <strong class="d-block mb-1">Your Note:</strong>
                                    <p class="mb-0 opacity-90">{{ $offer->description }}</p>
                                </div>
                                @if ($ad->status === 'running')
                                <button id="accept-task" class="btn btn-light w-100 fw-semibold" data-id="{{ $offer->id }}">
                                    <i class="ti ti-check me-1"></i>
                                    Confirm Task Acceptance
                                </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Offers Container -->
                    <div class="card-body p-0" id="offers-container">
                        <div class="text-center text-muted p-5">
                            <i class="ti ti-loader-2 fs-2 mb-3 text-primary"></i>
                            <p class="mb-0">Loading offers...</p>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>

  <div class="modal fade " id="offerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ $offer ? __('Update your offer') : __('Add your offer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('Close') }}"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('driver.offers.store') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden"  name="id" value="{{ $offer ? $offer->id : '' }}">
                        <input type="hidden"  name="ad" value="{{ $ad->id }}">
                         <span class="ad-error text-danger text-error"></span>

                        <div class="mb-2">
                            <label class="form-label" for="price">* {{ __('Your Price') }}</label>

                            <input type="number" step="any" name="price" value="{{ $offer ? $offer->price : '' }}" min="0.00" id="offer-price" class="form-control"
                                   placeholder="{{ __('Offer Price') }}">
                         <span class="price-error text-danger text-error"></span>

                        </div>
                        <div class="mb-2">
                                            <label class="form-label" for="description">* {{ __('Notes') }}</label>

                            <textarea name="description" id="description" class="form-control"
                                      placeholder="{{ __('Write your Notes or Description') }}" rows="2">{{ $offer ? $offer->description : '' }}</textarea>
                         <span class="description-error text-danger text-error"></span>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">{{ __('Submit') }}</button>

                    </div>
                </form>

            </div>
        </div>
    </div>


@endsection
