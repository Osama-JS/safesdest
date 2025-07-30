@extends('layouts/layoutMaster')

@section('title', __('Customs Clearance Order Details'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Page Styles -->
@section('page-style')
    @vite(['resources/css/app.css'])
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

        .price-icon.highest {}

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

        /* Enhanced Offer Cards Styling for Admin */
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

        /* Offer Actions */
        .offer-actions {
            padding: 16px 20px;
            border-top: 1px solid #f0f2f5;
            background: #fafbfc;
        }

        .offer-actions .btn {
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .offer-actions .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .offer-actions .btn-primary:hover {
            box-shadow: 0 4px 12px rgba(105, 108, 255, 0.3);
        }

        .offer-actions .btn-outline-danger:hover {
            box-shadow: 0 4px 12px rgba(234, 84, 85, 0.3);
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

            .offer-actions {
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
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/customer/customs-clearances/orders-show.js'])
@endsection

@section('content')
    <div class="container my-4">
        <div class="row">
            <!-- تفاصيل التخليص الجمركي -->
            <div class="col-lg-8 col-md-12">
                <div class="card mb-4 info-card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark">
                            <i class="ti ti-file-text me-2 text-primary"></i>
                            {{ __('Customs Clearance Details') }} #{{ $data->id }}
                        </h5>

                        <div class="d-flex gap-2">


                            <a href="{{ route('customer.customs-clearances.orders') }}" class="btn btn-outline-secondary">
                                <i class="ti ti-arrow-left me-1"></i>
                                Back to List
                            </a>

                        </div>
                    </div>

                    <div class="card-body">
                        <div class="mb-3 border rounded px-6 mt-4">
                            @php
                                $statuses = ['assign', 'start', 'completed'];
                                $currentIndex = array_search($data->status, $statuses);
                            @endphp

                            <div class="stepper-container my-4">
                                <div class="stepper d-flex justify-content-between align-items-center position-relative">
                                    @foreach ($statuses as $index => $status)
                                        @php
                                            $isCompleted = $index < $currentIndex;
                                            $isActive = $index == $currentIndex;
                                            $isNext = $index == $currentIndex + 1;
                                        @endphp

                                        <form class="step-form text-center" method="POST"
                                            action="{{ route('customer.customs-clearances.orders.updateStatus') }}">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $data->id }}">
                                            <input type="hidden" name="status" value="{{ $status }}">

                                            <button type="button"
                                                class="step-circle btn {{ $isActive ? 'btn-primary' : ($isNext ? 'btn-outline-primary' : 'btn-outline-secondary') }}"
                                                data-status="{{ $status }}" {{ !$isNext ? 'disabled' : '' }}
                                                title="{{ ucfirst($status) }}"
                                                style="width: 45px; height: 45px; border-radius: 50%;">
                                                {{ $index + 1 }}
                                            </button>

                                            <div class="step-label mt-1 small">
                                                <small
                                                    class="{{ $isCompleted ? 'text-success' : ($isActive ? 'text-primary' : 'text-muted') }}">
                                                    {{ __($status) }}
                                                </small>
                                            </div>
                                        </form>


                                        @if ($index < count($statuses) - 1)
                                            <div
                                                class="step-line {{ $index < $currentIndex ? 'bg-primary' : 'bg-secondary' }}">
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @if ($data->status == 'completed')
                            <div class="alert alert-success" role="alert">
                                <b>{{ __('You need to wait for the responsible supervisor to check the task and close it, thank you') }}</b>
                            </div>
                        @endif

                        <!-- Owner Information -->
                        <div class="owner-info mt-6">
                            <div class="d-flex align-items-center">
                                <div class="owner-avatar">
                                    @php
                                        $ownerName = $data->owner->name;
                                        $initials = collect(explode(' ', $ownerName))
                                            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                                            ->take(2)
                                            ->implode('');
                                    @endphp
                                    {{ $initials }}
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 text-dark">{{ $ownerName }}</h6>
                                    <p class="mb-1 text-muted">
                                        <i class="ti ti-phone me-1"></i>
                                        {{ $data->owner->phone }}
                                    </p>

                                </div>
                                <div class="text-end">
                                    <small class="text-muted">Created</small>
                                    <div class="fw-semibold">{{ $data->created_at->format('M d, Y') }}</div>
                                    <small class="text-muted">{{ $data->created_at->format('H:i') }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Price and Status Information -->

                        <div class="row g-3">

                            <div class="col-md-4">
                                <div class="price-item">
                                    <div class="price-icon bg-success text-white  highest">
                                        <i class="ti ti-cash"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">{{ __('The Offering Price') }}</small>
                                        <div class="fw-bold text-success fs-5">
                                            @if ($data->total_price != 0)
                                                {{ number_format($data->total_price, 0) }} <small>SAR</small>
                                            @else
                                                <span class="text-muted">Not specified</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="price-item">
                                    <div class="price-icon bg-info text-white highest">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">{{ __('Status') }}</small>
                                        <div class="fw-bold text-info fs-5">
                                            <span class="text-muted">{{ $data->status }}</span>

                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="price-item">
                                    <div class="price-icon bg-secondary text-white highest">
                                        <i class="fas fa-lock{{ $data->closed ? '' : '-open' }}"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">{{ __('Status') }}</small>
                                        <div class="fw-bold text-secondary fs-5">
                                            <span class="text-muted">{{ $data->closed ? __('Closed') : __('Open') }}</span>

                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>


                        <!-- Ad Description -->
                        <div class="mt-4">
                            <h6 class="text-primary mb-3">
                                <i class="ti ti-notes me-2"></i>{{ __('Notes') }}
                            </h6>
                            <div class="bg-light rounded p-3">
                                <p class="mb-0 text-dark">
                                    {{ $data->notes ?: 'No description provided for this advertisement.' }}</p>
                            </div>
                        </div>

                    </div>
                </div>


                <!-- بيانات إضافية -->
                @if ($data->additional_data)
                    <div class="card mb-4 info-card">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-layer-group me-2 text-primary"></i>
                                {{ __('Additional Data') }}
                            </h5>
                        </div>

                        <div class="card-body mt-3">
                            @if (is_array($data->additional_data) && count($data->additional_data) > 0)
                                <div class="row">
                                    @foreach ($data->additional_data as $key => $field)
                                        <div class="col-md-6 mb-4">
                                            <div class="border rounded p-3 h-100 ">
                                                <h6 class="text-muted mb-2">

                                                    {{ $field['label'] }}
                                                </h6>

                                                @switch($field['type'])
                                                    @case('text')
                                                    @case('string')

                                                    @case('number')
                                                        <p class="mb-0">{{ $field['value'] }}</p>
                                                    @break

                                                    @case('image')
                                                        <img src="{{ asset('storage/' . $field['value']) }}"
                                                            alt="{{ $field['label'] }}" class="img-fluid rounded border"
                                                            style="max-height: 200px; object-fit: cover;">
                                                    @break

                                                    @case('file')
                                                        @php
                                                            $ext = strtolower(
                                                                pathinfo($field['value'], PATHINFO_EXTENSION),
                                                            );
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
                                                            class="d-flex align-items-center text-decoration-none mt-1">
                                                            <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                            <span class="text-truncate">{{ basename($field['value']) }}</span>
                                                        </a>
                                                    @break

                                                    @case('file_with_text')
                                                        @if ($field['value'])
                                                            @php
                                                                $ext = strtolower(
                                                                    pathinfo($field['value'], PATHINFO_EXTENSION),
                                                                );
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

                                                            <div class="d-flex align-items-center mb-2">
                                                                <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                                                    class="d-flex align-items-center text-decoration-none">
                                                                    <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                                    <span
                                                                        class="text-truncate">{{ basename($field['value']) }}</span>
                                                                </a>
                                                            </div>
                                                        @endif

                                                        @if (isset($field['text']) && $field['text'])
                                                            <div class="mt-2">
                                                                <small class="text-muted">Additional Info:</small>
                                                                <p class="mb-0 fw-medium">{{ $field['text'] }}</p>
                                                            </div>
                                                        @endif
                                                    @break

                                                    @case('file_expiration_date')
                                                        @if ($field['value'])
                                                            @php
                                                                $ext = strtolower(
                                                                    pathinfo($field['value'], PATHINFO_EXTENSION),
                                                                );
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

                                                            <div class="d-flex align-items-center mb-2">
                                                                <a href="{{ asset('storage/' . $field['value']) }}"
                                                                    target="_blank"
                                                                    class="d-flex align-items-center text-decoration-none">
                                                                    <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                                    <span
                                                                        class="text-truncate">{{ basename($field['value']) }}</span>
                                                                </a>
                                                            </div>
                                                        @endif

                                                        @if (isset($field['expiration']) && $field['expiration'])
                                                            <div class="mt-2">
                                                                <small class="text-muted">Expires:</small>
                                                                <p class="mb-0 fw-medium">
                                                                    {{ \Carbon\Carbon::parse($field['expiration'])->format('Y-m-d') }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                    @break

                                                    @default
                                                        <p class="mb-0">{{ $field['value'] }}</p>
                                                @endswitch
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info" role="alert">
                                    {{ __('No additional data found for this customer.') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- سجل الأحداث -->
            <div class="col-lg-4 col-md-12">
                <div class="card info-card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark">
                            <i class="fas fa-history me-2 text-primary"></i>
                            {{ __('Customs Clearance History') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        @if ($data->history->count())
                            <ul class="timeline">
                                @foreach ($data->history->sortByDesc('created_at') as $entry)
                                    <li class="timeline-item">
                                        <span class="timeline-point"></span>
                                        <div class="timeline-event">
                                            <div class="d-flex justify-content-between mb-1">
                                                <strong>{{ ucfirst($entry->action_type) }}</strong>
                                                <small
                                                    class="text-muted">{{ $entry->created_at->format('Y-m-d H:i') }}</small>
                                            </div>
                                            @if ($entry->user_id && $entry->clearance_agent_id)
                                                <p>
                                                    <small class="text-muted">{{ __('By') }}:
                                                        {{ $entry->user?->name }}</small>
                                                </p>
                                                <p>
                                                    <small class="text-muted">{{ __('To') }}:
                                                        {{ $entry->clearanceAgent->name }}</small>
                                                </p>
                                            @elseif ($entry->user_id)
                                                <p>
                                                    <small class="text-muted">{{ __('By') }}:
                                                        {{ $entry->user?->name }}</small>
                                                </p>
                                            @elseif ($entry->clearance_agent_id)
                                                <p>
                                                    <small class="text-muted">{{ __('By') }}:
                                                        {{ $entry->clearanceAgent->name }}</small>
                                                </p>
                                            @endif

                                            <p class="mb-1">{{ $entry->description }}</p>
                                            @if ($entry->file_path)
                                                <a href="{{ asset('storage/' . $entry->file_path) }}" target="_blank"
                                                    class="btn btn-sm btn-outline-primary">{{ __('download file') }}</a>
                                            @endif


                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted">{{ __('No History Recorded Yet') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endsection
