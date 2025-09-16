@extends('layouts/layoutMaster')

@section('title', __('Platform Reports'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Page Styles -->
@section('page-style')
    @vite(['resources/css/app.css'])
    <style>
        .report-card {
            transition: all 0.3s ease;
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-color: #5a5c69;
        }

        .report-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 1rem;
        }

        .report-icon.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .report-icon.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .report-icon.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .report-icon.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }


        .breadcrumb-item+.breadcrumb-item::before {
            color: rgba(255, 255, 255, 0.7);
        }

        .breadcrumb-item.active {
            color: rgba(255, 255, 255, 0.8);
        }

        .breadcrumb-item a {
            color: white;
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
@endsection

@section('content')


    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">
                <i class="tf-icons ti ti-report me-2 fs-3 text-white bg-primary rounded p-1"></i>

                {{ __('Platform Reports') }}
            </h5>
            <p>{{ __('Generate comprehensive reports for your platform data') }}
            </p>
        </div>
    </div>


    <!-- Reports Grid -->
    <div class="container-fluid">
        <div class="row">
            <!-- Customer Tasks Report -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <div class="report-icon primary mx-auto">
                            <i class="ti ti-truck"></i>
                        </div>
                        <h5 class="card-title">{{ __('Customer Tasks Report') }}</h5>
                        <p class="card-text text-muted">
                            {{ __('Generate detailed reports for customer tasks with customizable filters and export options') }}
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-primary me-2">{{ __('Excel Export') }}</span>
                            <span class="badge bg-secondary">{{ __('PDF Export') }}</span>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('admin.reports.customer-tasks') }}" class="btn btn-primary">
                                <i class="ti ti-report me-1"></i>
                                {{ __('Generate Report') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- <!-- Driver Tasks Report -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <div class="report-icon success mx-auto">
                            <i class="ti ti-user-check"></i>
                        </div>
                        <h5 class="card-title">{{ __('Driver Tasks Report') }}</h5>
                        <p class="card-text text-muted">
                            {{ __('Generate detailed reports for driver tasks with commission calculations and customizable filters') }}
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-success me-2">{{ __('Excel Export') }}</span>
                            <span class="badge bg-secondary me-2">{{ __('PDF Export') }}</span>
                            <span class="badge bg-warning">{{ __('Commission Calc') }}</span>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('admin.reports.driver-tasks') }}" class="btn btn-success">
                                <i class="ti ti-report me-1"></i>
                                {{ __('Generate Report') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Tasks Report -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <div class="report-icon info mx-auto">
                            <i class="ti ti-users"></i>
                        </div>
                        <h5 class="card-title">{{ __('Team Tasks Report') }}</h5>
                        <p class="card-text text-muted">
                            {{ __('Generate comprehensive reports for team tasks with driver and customer information') }}
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-info me-2">{{ __('Excel Export') }}</span>
                            <span class="badge bg-secondary me-2">{{ __('PDF Export') }}</span>
                            <span class="badge bg-warning">{{ __('Commission Calc') }}</span>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('admin.reports.team-tasks') }}" class="btn btn-info">
                                <i class="ti ti-report me-1"></i>
                                {{ __('Generate Report') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div> --}}

            <!-- Wallet Reports -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <div class="report-icon success mx-auto">
                            <i class="ti ti-wallet"></i>
                        </div>
                        <h5 class="card-title">{{ __('Wallet Reports') }}</h5>
                        <p class="card-text text-muted">
                            {{ __('Generate comprehensive wallet reports for customers, drivers, and teams with transaction details') }}
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-success me-2">{{ __('PDF Export') }}</span>
                            <span class="badge bg-info">{{ __('Multi-Type') }}</span>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('admin.reports.wallet.index') }}" class="btn btn-success">
                                <i class="ti ti-report me-1"></i>
                                {{ __('Generate Report') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- <!-- Driver Performance Report (Coming Soon) -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100 opacity-50">
                    <div class="card-body text-center">
                        <div class="report-icon success mx-auto">
                            <i class="ti ti-user-check"></i>
                        </div>
                        <h5 class="card-title">{{ __('Driver Performance Report') }}</h5>
                        <p class="card-text text-muted">
                            {{ __('Analyze driver performance metrics, completion rates, and earnings') }}
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-light text-muted me-2">{{ __('Coming Soon') }}</span>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="ti ti-clock me-1"></i>
                                {{ __('Coming Soon') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Report (Coming Soon) -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100 opacity-50">
                    <div class="card-body text-center">
                        <div class="report-icon warning mx-auto">
                            <i class="ti ti-chart-line"></i>
                        </div>
                        <h5 class="card-title">{{ __('Financial Report') }}</h5>
                        <p class="card-text text-muted">
                            {{ __('Comprehensive financial analysis including revenue, commissions, and payments') }}
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-light text-muted me-2">{{ __('Coming Soon') }}</span>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="ti ti-clock me-1"></i>
                                {{ __('Coming Soon') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Performance Report (Coming Soon) -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100 opacity-50">
                    <div class="card-body text-center">
                        <div class="report-icon info mx-auto">
                            <i class="ti ti-users"></i>
                        </div>
                        <h5 class="card-title">{{ __('Team Performance Report') }}</h5>
                        <p class="card-text text-muted">
                            {{ __('Track team performance, task distribution, and productivity metrics') }}
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-light text-muted me-2">{{ __('Coming Soon') }}</span>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="ti ti-clock me-1"></i>
                                {{ __('Coming Soon') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom Report Builder (Coming Soon) -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100 opacity-50">
                    <div class="card-body text-center">
                        <div class="report-icon primary mx-auto">
                            <i class="ti ti-settings"></i>
                        </div>
                        <h5 class="card-title">{{ __('Custom Report Builder') }}</h5>
                        <p class="card-text text-muted">
                            {{ __('Build custom reports with drag-and-drop interface and advanced filters') }}
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-light text-muted me-2">{{ __('Coming Soon') }}</span>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="ti ti-clock me-1"></i>
                                {{ __('Coming Soon') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Analytics (Coming Soon) -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100 opacity-50">
                    <div class="card-body text-center">
                        <div class="report-icon success mx-auto">
                            <i class="ti ti-chart-pie"></i>
                        </div>
                        <h5 class="card-title">{{ __('System Analytics') }}</h5>
                        <p class="card-text text-muted">
                            {{ __('System usage analytics, user activity, and platform performance metrics') }}
                        </p>
                        <div class="mt-3">
                            <span class="badge bg-light text-muted me-2">{{ __('Coming Soon') }}</span>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="ti ti-clock me-1"></i>
                                {{ __('Coming Soon') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-info-circle me-2"></i>
                            {{ __('Reports Information') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>{{ __('Available Features:') }}</h6>
                                <ul class="list-unstyled">
                                    <li><i
                                            class="ti ti-check text-success me-2"></i>{{ __('Customer Tasks Report with Excel/PDF export') }}
                                    </li>
                                    <li><i
                                            class="ti ti-check text-success me-2"></i>{{ __('Driver Tasks Report with commission calculations') }}
                                    </li>
                                    <li><i
                                            class="ti ti-check text-success me-2"></i>{{ __('Team Tasks Report with comprehensive data') }}
                                    </li>
                                    <li><i
                                            class="ti ti-check text-success me-2"></i>{{ __('Customizable column selection') }}
                                    </li>
                                    <li><i class="ti ti-check text-success me-2"></i>{{ __('Advanced filtering options') }}
                                    </li>
                                    <li><i class="ti ti-check text-success me-2"></i>{{ __('Date range selection') }}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>{{ __('Export Formats:') }}</h6>
                                <ul class="list-unstyled">
                                    <li><i
                                            class="ti ti-file-spreadsheet text-success me-2"></i>{{ __('Excel (.xlsx) - Detailed report with customizable columns') }}
                                    </li>
                                    <li><i
                                            class="ti ti-file-text text-danger me-2"></i>{{ __('PDF - Simplified report for printing') }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}
        </div>
    @endsection

    <!-- Vendor Scripts -->
    @section('vendor-script')
        @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    @endsection

    <!-- Page Scripts -->
    @section('page-script')
        <script>
            $(document).ready(function() {
                // Add any initialization scripts here
                console.log('Platform Reports page loaded');
            });
        </script>
    @endsection
