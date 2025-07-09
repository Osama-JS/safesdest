@php

    use Illuminate\Support\Facades\Session;
    $guard = Session::get('guard');

@endphp
@extends('layouts/layoutMaster')

@section('title', 'Driver Dashboard')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

@endsection

@section('page-style')
    <!-- Page -->
    @vite(['resources/assets/vendor/scss/pages/cards-advance.scss'])
    <style>
        /* Enhanced Driver Dashboard Styling */
        .driver-dashboard {
            background: #f8f9fa;
            min-height: 100vh;
        }

        /* Driver Profile Section */
        /* Driver Profile Header */
        .driver-profile-header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e7eef7;
        }

        .driver-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid #e7eef7;
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .driver-info h4 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .driver-info p {
            margin: 0;
            color: #8a92a6;
            font-size: 0.9rem;
        }

        .driver-stats {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-badge.online {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 199, 111, 0.3);
        }

        .stat-badge.offline {
            background: linear-gradient(135deg, #ea5455 0%, #e74c3c 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(234, 84, 85, 0.3);
        }

        .stat-badge.available {
            background: linear-gradient(135deg, #00cfe8 0%, #0dcaf0 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 207, 232, 0.3);
        }

        .stat-badge.busy {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(108, 117, 125, 0.3);
        }

        /* Section Headers */
        .section-header {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e7eef7;
        }

        .section-header h4 {
            margin: 0;
            color: #2c3e50;
            font-weight: 600;
        }

        .section-header p {
            margin: 0.5rem 0 0 0;
            color: #8a92a6;
            font-size: 0.9rem;
        }

        /* Enhanced Task Cards */
        .task-card {
            background: white;
            border: 1px solid #e7eef7;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }

        .task-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.1);
            border-color: #696cff;
        }

        .task-card-header {
            background: linear-gradient(135deg, #f1f3f4 0%, #e8eaed 100%);
            padding: 1.5rem;
            border-bottom: 1px solid #e7eef7;
        }

        .task-card-header h5 {
            margin: 0;
            color: #2c3e50;
            font-weight: 600;
        }

        .task-status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .task-status-badge.pending {
            background: linear-gradient(135deg, #ff9f43 0%, #ff8c00 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 159, 67, 0.3);
        }

        .task-status-badge.in_progress {
            background: linear-gradient(135deg, #00cfe8 0%, #0dcaf0 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 207, 232, 0.3);
        }

        .task-status-badge.completed {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 199, 111, 0.3);
        }

        /* Map Container */
        .map-container {
            height: 120px;
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

        /* Task Card Body */
        .task-card-body {
            padding: 1.5rem;
        }

        .owner-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .owner-info p {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .owner-info p:last-child {
            margin-bottom: 0;
        }

        .owner-info strong {
            color: #2c3e50;
            font-weight: 600;
        }

        .address-section {
            margin-bottom: 1rem;
        }

        .address-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .address-item strong {
            color: #696cff;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .address-item p {
            margin: 0.25rem 0 0 0;
            color: #5a6c7d;
            font-size: 0.9rem;
        }

        .price-section {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .price-section strong {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }

        .price-amount {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 0.25rem;
        }

        /* Task Card Footer */
        .task-card-footer {
            padding: 1rem 1.5rem;
            background: #fafbfc;
            border-top: 1px solid #e7eef7;
            display: flex;
            gap: 0.75rem;
        }

        .task-card-footer .btn {
            flex: 1;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .task-card-footer .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .task-card-footer .btn-primary:hover {
            box-shadow: 0 4px 12px rgba(105, 108, 255, 0.3);
        }

        .task-card-footer .btn-outline-danger:hover {
            box-shadow: 0 4px 12px rgba(234, 84, 85, 0.3);
        }

        /* Active Tasks Section */
        .active-task-card {
            background: white;
            border: 1px solid #e7eef7;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .active-task-header {
            background: white;
            color: #2c3e50;
            padding: 1.5rem;
            display: flex;
            justify-content: between;
            align-items: center;
            border-bottom: 1px solid #e7eef7;
        }

        .active-task-header h5 {
            margin: 0;
            font-weight: 600;
        }

        /* Stepper Enhancements */
        .stepper-container {
            overflow-x: auto;
            padding: 1rem 0;
            background: #f8f9fa;
            border-radius: 12px;
            margin: 1rem 0;
        }

        .stepper {
            min-width: 650px;
            gap: 0;
            padding: 0 1rem;
        }

        .step-form {
            position: relative;
            z-index: 1;
        }

        .step-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-weight: bold;
            padding: 0;
            transition: all 0.3s ease;
            border: 2px solid;
        }

        .step-circle:hover:not(:disabled) {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .step-line {
            flex-grow: 1;
            height: 4px;
            margin: 0 8px;
            z-index: 0;
            transition: 0.3s;
            border-radius: 2px;
        }

        .step-label {
            width: 90px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-align: center;
            margin-top: 0.5rem;
        }

        /* Tab Navigation */
        .nav-tabs .nav-link {
            border-radius: 8px 8px 0 0;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 500;
            color: #8a92a6;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            background: white;
            color: #696cff;
            border-bottom: 2px solid #696cff;
        }

        .nav-tabs .nav-link:hover {
            color: #696cff;
            background: rgba(105, 108, 255, 0.05);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .driver-profile-card {
                padding: 1.5rem;
                text-align: center;
            }

            .driver-avatar {
                width: 60px;
                height: 60px;
            }

            .task-card-body {
                padding: 1rem;
            }

            .task-card-footer {
                padding: 1rem;
                flex-direction: column;
            }

            .stepper {
                min-width: auto;
                flex-wrap: nowrap;
                overflow-x: auto;
            }

            .step-label {
                font-size: 10px;
                width: 60px;
            }

            .step-circle {
                width: 40px;
                height: 40px;
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

        /* Task Details Enhancement */
        .task-details-section {
            background: white;
            border-radius: 12px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e7eef7;
            overflow: hidden;
        }

        .task-details-header {
            background: white;
            color: #2c3e50;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e7eef7;
            font-weight: 600;
        }

        .task-details-body {
            padding: 1.5rem;
        }

        .detail-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #696cff;
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-item h6 {
            color: #696cff;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-item p {
            margin: 0;
            color: #5a6c7d;
            line-height: 1.5;
        }

        .detail-item strong {
            color: #2c3e50;
            font-weight: 600;
        }

        /* Location Details */
        .location-details {
            background: white;
            border: 1px solid #e7eef7;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .location-details h6 {
            color: #696cff;
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .location-details .location-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .location-details .location-info:last-child {
            margin-bottom: 0;
        }

        .location-details .location-info strong {
            color: #2c3e50;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .location-details .location-info p {
            margin: 0.25rem 0 0 0;
            color: #5a6c7d;
            font-size: 0.9rem;
        }

        /* Customer Info Enhancement */
        .customer-info-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #e7eef7;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .customer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
            margin-right: 1rem;
            box-shadow: 0 4px 12px rgba(105, 108, 255, 0.3);
        }

        .customer-details h6 {
            margin: 0 0 0.25rem 0;
            color: #2c3e50;
            font-weight: 600;
        }

        .customer-details p {
            margin: 0;
            color: #8a92a6;
            font-size: 0.9rem;
        }

        .customer-type-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.5rem;
        }

        .customer-type-badge.customer {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
        }

        .customer-type-badge.admin {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')

    @vite(['resources/js/driver/index.js'])
    @vite(['resources/js/ajax.js'])
    @php
        $taskMapData = auth()
            ->user()
            ->possible_tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'longitude' => optional($task->pickup)->longitude,
                    'latitude' => optional($task->pickup)->latitude,
                ];
            })
            ->values()
            ->toArray();
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tasks = @json($taskMapData);

            tasks.forEach(task => {
                if (task.longitude && task.latitude) {
                    initMapForAd(task.id, [task.longitude, task.latitude]);
                }
            });
        });
    </script>
    <script>
        function updateDriverLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    fetch('{{ route('driver.location') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            longitude: position.coords.longitude,
                            altitude: position.coords.latitude
                        })
                    });
                });
            }
        }

        // تحديث كل دقيقة
        setInterval(updateDriverLocation, 60000);
        updateDriverLocation(); // أول مرة
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endsection

@section('content')
    <div class="container-fluid driver-dashboard">
        <!-- Driver Profile Section -->
        <div class="driver-profile-header fade-in">
            <div class="d-flex align-items-center">
                <div class="me-4">
                    @if (auth()->user()->image)
                        <img src="{{ asset(auth()->user()->image) }}" alt="Driver" class="driver-avatar">
                    @else
                        <div
                            class="driver-avatar d-flex align-items-center justify-content-center bg-primary text-white fs-3 fw-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    @endif
                </div>
                <div class="driver-info flex-grow-1">
                    <h4>{{ auth()->user()->name }}</h4>
                    <p>{{ auth()->user()->team->name ?? 'No Team Assigned' }}</p>
                    <div class="driver-stats">
                        <span class="stat-badge {{ auth()->user()->online ? 'online' : 'offline' }}">
                            <i class="ti ti-{{ auth()->user()->online ? 'wifi' : 'wifi-off' }} me-1"></i>
                            {{ auth()->user()->online ? 'Online' : 'Offline' }}
                        </span>
                        <span class="stat-badge {{ auth()->user()->free ? 'available' : 'busy' }}">
                            <i class="ti ti-{{ auth()->user()->free ? 'check-circle' : 'clock' }} me-1"></i>
                            {{ auth()->user()->free ? 'Available' : 'Busy' }}
                        </span>
                        @if (auth()->user()->vehicleSize)
                            <span class="stat-badge"
                                style="background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%); color: white; box-shadow: 0 2px 8px rgba(105, 108, 255, 0.3);">
                                <i class="ti ti-truck me-1"></i>
                                {{ auth()->user()->vehicleSize->name }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="text-end">
                    <small class="text-muted">Last Updated</small>
                    <div class="fw-semibold">{{ now()->format('H:i') }}</div>
                </div>
            </div>
        </div>


        <!-- Available Tasks Section -->
        @if (auth()->user()->possible_tasks->count() > 0)
            <div class="section-header fade-in">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4>
                            <i class="ti ti-clipboard-list me-2 text-primary"></i>
                            Available Tasks
                        </h4>
                        <p>Tasks waiting for your response - Accept or reject to proceed</p>
                    </div>
                    <div class="badge bg-label-primary fs-6">
                        {{ auth()->user()->possible_tasks->count() }} Tasks
                    </div>
                </div>
            </div>
        @endif
        <div class="row">
            @foreach (auth()->user()->possible_tasks as $task)
                <div class="col-lg-6 col-md-6 mb-4">
                    <div class="task-card fade-in">
                        <div class="task-card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5>
                                    <i class="ti ti-package me-2 text-primary"></i>
                                    Task #{{ $task->id }}
                                </h5>
                                <span class="task-status-badge {{ $task->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                </span>
                            </div>
                        </div>

                        <!-- Map Container -->
                        <div class="map-container" id="map-{{ $task->id }}"></div>

                        <div class="task-card-body">
                            <!-- Owner Information -->
                            <div class="owner-info">
                                @if ($task->owner === 'customer' && $task->customer)
                                    <p><strong>Customer:</strong> {{ $task->customer->name }}</p>
                                    <p><strong>Phone:</strong> {{ $task->customer->phone ?? 'N/A' }}</p>
                                @elseif ($task->owner === 'admin' && $task->user)
                                    <p><strong>Admin:</strong> {{ $task->user->name }}</p>
                                @endif
                                <p><strong>Owner Type:</strong> {{ ucfirst($task->owner) }}</p>
                            </div>

                            <!-- Address Information -->
                            <div class="address-section">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="address-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <strong>
                                                        <i class="ti ti-map-pin me-1"></i>
                                                        {{ __('Pickup Address') }}
                                                    </strong>
                                                    <p>{{ optional($task->pickup)->address ?? 'Not specified' }}</p>
                                                </div>
                                                @if ($task->pickup && $task->pickup->latitude && $task->pickup->longitude)
                                                    <button class="btn btn-sm btn-outline-primary ms-2"
                                                        onclick="openGoogleMaps({{ $task->pickup->latitude }}, {{ $task->pickup->longitude }})"
                                                        title="Open in Google Maps">
                                                        <i class="ti ti-map-2"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="address-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <strong>
                                                        <i class="ti ti-truck-delivery me-1"></i>
                                                        {{ __('Delivery Address') }}
                                                    </strong>
                                                    <p>{{ optional($task->delivery)->address ?? 'Not specified' }}</p>
                                                </div>
                                                @if ($task->delivery && $task->delivery->latitude && $task->delivery->longitude)
                                                    <button class="btn btn-sm btn-outline-primary ms-2"
                                                        onclick="openGoogleMaps({{ $task->delivery->latitude }}, {{ $task->delivery->longitude }})"
                                                        title="Open in Google Maps">
                                                        <i class="ti ti-map-2"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Price Information -->
                            <div class="price-section">
                                <strong>Your Earnings</strong>
                                <div class="price-amount">
                                    {{ number_format($task->total_price - auth()->user()->calculateCommission($task->total_price), 0) }}
                                    SAR
                                </div>
                            </div>
                        </div>

                        <div class="task-card-footer">
                            <form action="{{ route('driver.respond.task') }}" method="POST" class="form_submit flex-fill">
                                @csrf
                                <input type="hidden" name="task_id" value="{{ $task->id }}">
                                <input type="hidden" name="response" value="accept">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ti ti-check me-1"></i>
                                    Accept Task
                                </button>
                            </form>
                            <form action="{{ route('driver.respond.task') }}" method="POST" class="form_submit flex-fill">
                                @csrf
                                <input type="hidden" name="task_id" value="{{ $task->id }}">
                                <input type="hidden" name="response" value="reject">
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="ti ti-x me-1"></i>
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Active Tasks Section -->
        @if ($data->count() > 0)
            <div class="section-header fade-in mt-5">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4>
                            <i class="ti ti-truck me-2 text-success"></i>
                            Active Tasks
                        </h4>
                        <p>Tasks currently in progress - Track and update status</p>
                    </div>
                    <div class="badge bg-label-success fs-6">
                        {{ $data->count() }} Active
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            @foreach ($data as $task)
                <div class="col-md-6 active-task-card fade-in">
                    <div class="active-task-header">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-package fs-4 me-3"></i>
                                <div>
                                    <h5 class="mb-0">Task #{{ $task->id }}</h5>
                                    <small class="opacity-75">Started {{ $task->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                            <span class="task-status-badge {{ $task->status }}">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>
                        </div>
                    </div>

                    <div class="nav-align-top p-0" style="min-height: 75vh;">
                        <ul class="nav nav-tabs nav-fill bg-white border-bottom sticky-top" style="top: 0; z-index: 1030;">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" data-bs-toggle="tab"
                                    data-bs-target="#navs-justified-details-{{ $task->id }}" role="tab">
                                    <span class="d-none d-sm-inline">{{ __('Details') }}</span>
                                    <i class="ti ti-info-circle ti-sm d-sm-none"></i>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#navs-justified-history-{{ $task->id }}" role="tab">
                                    <span class="d-none d-sm-inline">{{ __('History') }}</span>
                                    <i class="ti ti-clock ti-sm d-sm-none"></i>
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content p-0 m-0" style="max-height: calc(75vh - 60px); overflow-y: auto;">
                            {{-- Details Tab --}}
                            <div class="tab-pane fade show active" id="navs-justified-details-{{ $task->id }}"
                                role="tabpanel">
                                <div id="task-details-content" class="p-4">
                                    <div class="  mb-4">
                                        <div id="map-{{ $task->id }}" class="rounded-top" style="height: 150px;">
                                        </div>
                                        <div class="mb-3">
                                            @php
                                                $statuses = [
                                                    'started',
                                                    'in pickup point',
                                                    'loading',
                                                    'in the way',
                                                    'in delivery point',
                                                    'unloading',
                                                    'completed',
                                                ];
                                                $currentIndex = array_search($task->status, $statuses);
                                            @endphp

                                            <div class="stepper-container my-4">
                                                <div
                                                    class="stepper d-flex justify-content-between align-items-center position-relative">
                                                    @foreach ($statuses as $index => $status)
                                                        @php
                                                            $isCompleted = $index < $currentIndex;
                                                            $isActive = $index == $currentIndex;
                                                            $isNext = $index == $currentIndex + 1;
                                                        @endphp

                                                        <form class="step-form text-center" method="POST"
                                                            action="{{ route('driver.task.updateStatus') }}">
                                                            @csrf
                                                            <input type="hidden" name="task_id"
                                                                value="{{ $task->id }}">
                                                            <input type="hidden" name="status"
                                                                value="{{ $status }}">

                                                            <button type="button"
                                                                class="step-circle btn {{ $isActive ? 'btn-primary' : ($isNext ? 'btn-outline-primary' : 'btn-outline-secondary') }}"
                                                                data-status="{{ $status }}"
                                                                {{ !$isNext ? 'disabled' : '' }}
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
                                        @if ($task->status == 'completed')
                                            <div class="alert alert-success" role="alert">
                                                <b>{{ __('You need to wait for the responsible supervisor to check the task and close it, thank you') }}</b>
                                            </div>
                                        @endif

                                        <div class="">
                                            <h6 class="fw-bold mb-3">Client Information</h6>
                                            <input type="hidden" id="task-id-history" value="{{ $task->id }}">
                                            {{-- <p><strong>Owner Type:</strong> {{ ucfirst($task->owner) }}</p> --}}
                                            @if ($task->owner === 'customer' && $task->customer)
                                                <p><strong>Customer Name:</strong> {{ $task->customer->name }}</p>
                                                <p><strong>Customer Phone:</strong>
                                                    {{ $task->customer->phone ?? 'N/A' }}</p>
                                            @elseif ($task->owner === 'admin' && $task->user)
                                                <p><strong>Admin:</strong> {{ $task->user->name }}</p>
                                            @endif

                                            <div class="row mt-3">
                                                <div class="col-md-6">
                                                    <div class="border p-3 rounded">
                                                        <strong>Price:</strong>
                                                        {{ $task->total_price - auth()->user()->calculateCommission($task->total_price) }}
                                                        SAR
                                                    </div>
                                                </div>






                                            </div>

                                            <hr class="my-4" />

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="fw-bold">{{ __('Pickup Information') }}</h6>
                                                        @if ($task->pickup && $task->pickup->latitude && $task->pickup->longitude)
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                onclick="openGoogleMaps({{ $task->pickup->latitude }}, {{ $task->pickup->longitude }})"
                                                                title="Open Pickup Location in Google Maps">
                                                                <i class="ti ti-map-2 me-1"></i>
                                                                Maps
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <p class="mb-1"><strong>{{ __('Address') }}:</strong>
                                                        {{ optional($task->pickup)->address ?? 'Not set' }}</p>
                                                    <p class="mb-1"><strong>{{ __('Contact Name') }}:</strong>
                                                        {{ optional($task->pickup)->contact_name ?? 'Not set' }}</p>
                                                    <p class="mb-1"><strong>{{ __('Phone') }}:</strong>
                                                        {{ optional($task->pickup)->contact_phone ?? 'Not set' }}</p>
                                                    <p class="mb-1"><strong>{{ __('Email') }}:</strong>
                                                        {{ optional($task->pickup)->contact_email ?? 'Not set' }}</p>
                                                    <p class="mb-0"><strong>{{ __('Note') }}:</strong>
                                                        {{ optional($task->pickup)->note ?? 'Not set' }}</p>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="fw-bold">{{ __('Delivery Information') }}</h6>
                                                        @if ($task->delivery && $task->delivery->latitude && $task->delivery->longitude)
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                onclick="openGoogleMaps({{ $task->delivery->latitude }}, {{ $task->delivery->longitude }})"
                                                                title="Open Delivery Location in Google Maps">
                                                                <i class="ti ti-map-2 me-1"></i>
                                                                Maps
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <p class="mb-1"><strong>{{ __('Address') }}:</strong>
                                                        {{ optional($task->delivery)->address ?? 'Not set' }}</p>
                                                    <p class="mb-1"><strong>{{ __('Contact Name') }}:</strong>
                                                        {{ optional($task->delivery)->contact_name ?? 'Not set' }}</p>
                                                    <p class="mb-1"><strong>{{ __('Phone') }}:</strong>
                                                        {{ optional($task->delivery)->contact_phone ?? 'Not set' }}</p>
                                                    <p class="mb-1"><strong>{{ __('Email') }}:</strong>
                                                        {{ optional($task->delivery)->contact_email ?? 'Not set' }}</p>
                                                    <p class="mb-0"><strong>{{ __('Note') }}:</strong>
                                                        {{ optional($task->delivery)->note ?? 'Not set' }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-footer bg-white border-top-0 text-end">


                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- History Tab --}}
                            <div class="tab-pane fade" id="navs-justified-history-{{ $task->id }}" role="tabpanel">
                                <div class="row m-0 p-4">
                                    <div class="col-md-6">
                                        <div id="task-history-container"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="sticky-top" style="top: 80px;">
                                            <form action="{{ route('task-histories.store') }}" method="POST"
                                                class="card shadow-sm p-4 border-0 form_submit"
                                                enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="task" id="task_id"
                                                    value="{{ $task->id }}">
                                                <span class="task-error text-danger text-error"></span>

                                                <div class="mb-3">
                                                    <label for="description"
                                                        class="form-label">{{ __('Add Note') }}</label>
                                                    <textarea name="description" id="description" class="form-control" rows="3"
                                                        placeholder="{{ __('Type the note here') }}..."></textarea>
                                                    <span class="description-error text-danger text-error"></span>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="file" class="form-label">{{ __('Upload File') }}
                                                        ({{ __('optional') }})
                                                    </label>
                                                    <input type="file" name="file" id="file"
                                                        class="form-control">
                                                    <span class="file-error text-danger text-error"></span>
                                                </div>

                                                <button type="submit"
                                                    class="btn btn-primary">{{ __('Submit') }}</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>





    <script>
        // Function to open Google Maps with coordinates
        function openGoogleMaps(latitude, longitude) {
            const url = `https://www.google.com/maps?q=${latitude},${longitude}`;
            window.open(url, '_blank');
        }
    </script>
@endsection
