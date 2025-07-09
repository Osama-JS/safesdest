@php

    use Illuminate\Support\Facades\Session;
    $guard = Session::get('guard');

@endphp
@extends('layouts/layoutMaster')

@section('title', 'Driver Dashboard')

@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

@endsection

@section('page-style')
    <!-- Page -->
    @vite(['resources/assets/vendor/scss/pages/cards-advance.scss'])

    <style>
        /* Enhanced Driver Tasks Page Styling */
        .driver-tasks-container {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 1rem 0;
        }

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

        /* Enhanced Tasks Card */
        .tasks-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e7eef7;
            overflow: hidden;
        }

        .tasks-card-header {
            background: white;
            border-bottom: 1px solid #e7eef7;
            padding: 2rem;
        }

        .tasks-card-header h5 {
            margin: 0;
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .tasks-card-header .header-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(105, 108, 255, 0.3);
        }

        .tasks-card-header .header-subtitle {
            color: #8a92a6;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        /* Enhanced DataTable Styling */
        .card-datatable {
            padding: 0;
        }

        .datatables-tasks {
            border: none !important;
        }

        .datatables-tasks thead th {
            background: #f8f9fa !important;
            border: none !important;
            color: #2c3e50 !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            font-size: 0.75rem !important;
            letter-spacing: 0.5px !important;
            padding: 1.5rem 1rem !important;
        }

        .datatables-tasks tbody td {
            border: none !important;
            border-bottom: 1px solid #f0f2f5 !important;
            padding: 1.25rem 1rem !important;
            vertical-align: middle !important;
        }

        .datatables-tasks tbody tr {
            transition: all 0.3s ease;
        }

        .datatables-tasks tbody tr:hover {
            background: #f8f9fa !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* Enhanced Status Badges */
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            min-width: 100px;
            justify-content: center;
        }

        .status-badge.advertised {
            background: linear-gradient(135deg, #8a92a6 0%, #6c757d 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(138, 146, 166, 0.3);
        }

        .status-badge.in_progress {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(105, 108, 255, 0.3);
        }

        .status-badge.assign {
            background: linear-gradient(135deg, #00cfe8 0%, #0dcaf0 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 207, 232, 0.3);
        }

        .status-badge.accepted {
            background: linear-gradient(135deg, #ff9f43 0%, #ff8c00 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(255, 159, 67, 0.3);
        }

        .status-badge.start {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.3);
        }

        .status-badge.completed {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 199, 111, 0.3);
        }

        .status-badge.canceled {
            background: linear-gradient(135deg, #ea5455 0%, #e74c3c 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(234, 84, 85, 0.3);
        }

        .status-badge.open {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(105, 108, 255, 0.3);
        }

        .status-badge.closed {
            background: linear-gradient(135deg, #8a92a6 0%, #6c757d 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(138, 146, 166, 0.3);
        }

        /* Enhanced Price Display */
        .price-display {
            background: linear-gradient(135deg, #28c76f 0%, #20bf6b 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-weight: 700;
            text-align: center;
            box-shadow: 0 2px 8px rgba(40, 199, 111, 0.3);
            min-width: 80px;
        }

        /* Enhanced Action Buttons */
        .action-btn {
            background: linear-gradient(135deg, #696cff 0%, #5a67d8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 8px rgba(105, 108, 255, 0.3);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(105, 108, 255, 0.4);
            color: white;
        }

        /* Enhanced DataTable Controls */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e7eef7;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            background: white;
            color: #2c3e50;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: 1px solid #e7eef7 !important;
            border-radius: 8px !important;
            margin: 0 2px !important;
            background: white !important;
            color: #2c3e50 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #696cff !important;
            color: white !important;
            border-color: #696cff !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #696cff !important;
            color: white !important;
            border-color: #696cff !important;
        }

        /* Enhanced Filter Controls */
        .filter-controls {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .filter-controls .form-control,
        .filter-controls .form-select {
            border: 1px solid #e7eef7;
            border-radius: 8px;
            background: white;
            color: #2c3e50;
        }

        /* Responsive Enhancements */
        @media (max-width: 768px) {
            .driver-profile-header {
                padding: 1.5rem;
                text-align: center;
            }

            .driver-avatar {
                width: 60px;
                height: 60px;
            }

            .driver-stats {
                justify-content: center;
                flex-wrap: wrap;
            }

            .tasks-card-header {
                padding: 1.5rem;
            }

            .tasks-card-header h5 {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
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

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')

    @vite(['resources/js/driver/tasks.js'])
    @vite(['resources/js/ajax.js'])


@endsection

@section('content')
    <div class="driver-tasks-container">

        <!-- Enhanced Tasks Card -->
        <div class="tasks-card fade-in">
            <div class="tasks-card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">

                        <div>
                            <h5>
                                <i class="tf-icons ti ti-truck-delivery  me-2 fs-3 text-white bg-primary rounded p-1"></i>

                                {{ __('My Tasks') }}
                            </h5>
                            <p class="header-subtitle mb-0">Manage and track your assigned delivery tasks</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                            <i class="ti ti-refresh me-1"></i>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-datatable table-responsive">
                <table class="datatables-tasks table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>
                                <i class="ti ti-hash me-1"></i>
                                {{ __('ID') }}
                            </th>
                            <th>
                                <i class="ti ti-currency-riyal me-1"></i>
                                {{ __('Price') }}
                            </th>
                            <th>
                                <i class="ti ti-user me-1"></i>
                                {{ __('Owner') }}
                            </th>
                            <th>
                                <i class="ti ti-map-pin me-1"></i>
                                {{ __('Pickup Address') }}
                            </th>
                            <th>
                                <i class="ti ti-clock me-1"></i>
                                {{ __('Start Before') }}
                            </th>
                            <th>
                                <i class="ti ti-calendar-due me-1"></i>
                                {{ __('Complete Before') }}
                            </th>
                            <th>
                                <i class="ti ti-flag me-1"></i>
                                {{ __('Status') }}
                            </th>
                            <th>
                                <i class="ti ti-lock me-1"></i>
                                {{ __('Closed') }}
                            </th>
                            <th>
                                <i class="ti ti-settings me-1"></i>
                                {{ __('Action') }}
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

@endsection
