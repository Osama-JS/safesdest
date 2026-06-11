@extends('layouts/layoutMaster')

@section('title', __('Team Analytics') . ' - ' . $team->name)
@section('teams-isactive')
    active
@endsection
<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
    @vite(['resources/css/app.css'])
    <style>
        /* Custom tooltip styles for charts */
        .custom-tooltip {
            background: #fff;
            border: 1px solid #e7eef7;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            font-family: 'Inter', sans-serif;
            min-width: 150px;
        }

        .tooltip-title {
            font-weight: 600;
            font-size: 14px;
            color: #5a6c7d;
            margin-bottom: 8px;
            border-bottom: 1px solid #f0f2f5;
            padding-bottom: 4px;
        }

        .tooltip-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }

        .tooltip-label {
            font-size: 12px;
            color: #8a92a6;
        }

        .tooltip-value {
            font-weight: 600;
            font-size: 14px;
            color: #2c3e50;
        }

        .tooltip-note {
            font-size: 11px;
            color: #95a5a6;
            text-align: center;
            margin-top: 4px;
        }

        /* Chart container improvements */
        .chart-container {
            position: relative;
        }

        .chart-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
        }

        .chart-no-data {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 300px;
            color: #8a92a6;
        }
    </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
    <script>
        const teamID = {{ $team->id }};
        const analyticsData = @json($analytics);
    </script>
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/teams/dashboard/analytics.js'])
@endsection

@section('content')
    <!-- Breadcrumbs -->
    @include('admin.teams.dashboard.partials.breadcrumbs', ['team' => $team])

    <!-- Navigation -->
    @include('admin.teams.dashboard.partials.navigation', ['team' => $team])

    <!-- Analytics Controls -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Date Range') }}</label>
                    <input type="text" class="form-control" id="analytics-date-range"
                        placeholder="{{ __('Select date range') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Metric Type') }}</label>
                    <select class="form-select" id="metric-type">
                        <option value="tasks">{{ __('Tasks') }}</option>
                        <option value="revenue">{{ __('Revenue') }}</option>
                        <option value="performance">{{ __('Performance') }}</option>
                        <option value="drivers">{{ __('Drivers') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Group By') }}</label>
                    <select class="form-select" id="group-by">
                        <option value="day">{{ __('Daily') }}</option>
                        <option value="week">{{ __('Weekly') }}</option>
                        <option value="month">{{ __('Monthly') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-primary w-100" id="update-analytics">
                        <i class="ti ti-refresh me-1"></i>{{ __('Update Analytics') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Performance Indicators -->
    <div class="row g-4 mb-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-shrink-0">
                            <div class="avatar">
                                <div class="avatar-initial bg-primary rounded">
                                    <i class="ti ti-truck-delivery ti-26px"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="mb-0">{{ __('Total Tasks') }}</h6>
                                <small class="text-success">+12%</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-0 me-2 kpi-total-tasks">
                                    {{ $analytics['kpis']['total_tasks_this_month'] ?? 0 }}</h4>
                                <small class="text-muted">{{ __('This Month') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-shrink-0">
                            <div class="avatar">
                                <div class="avatar-initial bg-success rounded">
                                    <i class="ti ti-check ti-26px"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="mb-0">{{ __('Completion Rate') }}</h6>
                                <small class="text-success">+5%</small>
                            </div>
                            <div class="d-flex align-items-center">
                                @php
                                    $totalTasksMonth = $analytics['kpis']['total_tasks_this_month'] ?? 0;
                                    $completedTasksMonth = $analytics['kpis']['completed_tasks_this_month'] ?? 0;
                                    $completionRate =
                                        $totalTasksMonth > 0
                                            ? round(($completedTasksMonth / $totalTasksMonth) * 100, 1)
                                            : 0;
                                @endphp
                                <h4 class="mb-0 me-2 kpi-completion-rate">{{ $completionRate }}%</h4>
                                <small class="text-muted">{{ $completedTasksMonth }}/{{ $totalTasksMonth }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-shrink-0">
                            <div class="avatar">
                                <div class="avatar-initial bg-info rounded">
                                    <i class="ti ti-currency-dollar ti-26px"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="mb-0">{{ __('Total Revenue') }}</h6>
                                <small class="text-success">+8%</small>
                            </div>
                            <div class="d-flex align-items-center">
                                @php
                                    $totalRevenue = $analytics['kpis']['total_revenue_this_month'] ?? 0;
                                @endphp
                                <h4 class="mb-0 me-2 kpi-total-revenue">{{ number_format($totalRevenue, 0) }}</h4>
                                <small class="text-muted">{{ __('SAR') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="flex-shrink-0">
                            <div class="avatar">
                                <div class="avatar-initial bg-warning rounded">
                                    <i class="ti ti-users ti-26px"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="mb-0">{{ __('Driver Utilization') }}</h6>
                                <small class="text-success">+7%</small>
                            </div>
                            <div class="d-flex align-items-center">
                                @php
                                    $activeDrivers = $team->drivers->where('status', 'active')->count();
                                    $totalDrivers = $team->drivers->count();
                                    $utilization =
                                        $totalDrivers > 0 ? round(($activeDrivers / $totalDrivers) * 100, 1) : 0;
                                @endphp
                                <h4 class="mb-0 me-2">{{ $utilization }}%</h4>
                                <small class="text-muted">{{ $activeDrivers }}/{{ $totalDrivers }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-6">
        <!-- Tasks Overview Chart -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-chart-line me-2"></i>{{ __('Tasks Overview') }}
                        <span class="text-muted">({{ __('Under Development') }})</span>

                    </h5>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            {{ __('Last 30 Days') }}
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">{{ __('Last 7 Days') }}</a></li>
                            <li><a class="dropdown-item" href="#">{{ __('Last 30 Days') }}</a></li>
                            <li><a class="dropdown-item" href="#">{{ __('Last 3 Months') }}</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div id="tasksOverviewChart"></div>
                </div>
            </div>
        </div>

        <!-- Task Status Distribution -->
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-chart-pie me-2"></i>{{ __('Task Status Distribution') }}
                        <span class="text-muted">({{ __('Under Development') }})</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div id="taskStatusChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Driver Performance & Revenue -->
    <div class="row g-4 mb-6">
        <!-- Driver Performance -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-users me-2"></i>{{ __('Driver Performance') }}
                    </h5>
                </div>
                <div class="card-body">
                    @if ($analytics['driver_performance']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <thead>
                                    <tr>
                                        <th>{{ __('Driver') }}</th>
                                        <th>{{ __('Completed Tasks') }}</th>
                                        <th>{{ __('Performance') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($analytics['driver_performance']->take(5) as $driver)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm me-3">
                                                        @if ($driver->image)
                                                            <img src="{{ asset($driver->image) }}"
                                                                alt="{{ $driver->name }}" class="rounded-circle">
                                                        @else
                                                            <div class="avatar-initial bg-label-primary rounded-circle">
                                                                {{ substr($driver->name, 0, 1) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <span>{{ $driver->name }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $driver->completed_tasks }}</td>
                                            <td>
                                                @php
                                                    $performance =
                                                        $driver->completed_tasks > 0
                                                            ? min(100, ($driver->completed_tasks / 10) * 100)
                                                            : 0;
                                                @endphp
                                                <div class="d-flex align-items-center">
                                                    <div class="progress me-2" style="width: 60px; height: 6px;">
                                                        <div class="progress-bar bg-success"
                                                            style="width: {{ $performance }}%"></div>
                                                    </div>
                                                    <small>{{ round($performance) }}%</small>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti ti-chart-bar-off text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">{{ __('No performance data available') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-chart-bar me-2"></i>{{ __('Monthly Revenue') }}
                        <span class="text-muted">({{ __('Under Development') }})</span>

                    </h5>
                </div>
                <div class="card-body">
                    <div id="revenueChart"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle & Geofence Analytics -->
    <div class="row g-4 mb-6">
        <!-- Vehicle Utilization -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-truck me-2"></i>{{ __('Vehicle Utilization') }}
                        <span class="text-muted">({{ __('Under Development') }})</span>

                    </h5>
                </div>
                <div class="card-body">
                    <div id="vehicleUtilizationChart"></div>
                    <div class="mt-4">
                        <div class="row text-center">
                            @php
                                $vehicleSizes = $team->drivers->groupBy('vehicle_size_id')->map(function ($drivers) {
                                    return $drivers->count();
                                });
                            @endphp
                            @foreach ($vehicleSizes->take(3) as $sizeId => $count)
                                <div class="col-4">
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold">{{ $count }}</span>
                                        <small class="text-muted">Size {{ $sizeId }}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Geofence Coverage -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-map-pin me-2"></i>{{ __('Geofence Coverage') }}
                    </h5>
                </div>
                <div class="card-body">
                    @if ($team->geofences && $team->geofences->count() > 0)
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="fw-semibold">{{ __('Active Geofences') }}</span>
                            <span class="badge bg-label-primary">{{ $team->geofences->count() }}</span>
                        </div>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: 85%"></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">{{ __('Coverage Rate') }}</small>
                            <small class="fw-semibold">85%</small>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti ti-map-off text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">{{ __('No geofences configured') }}</p>
                            <button class="btn btn-sm btn-outline-primary">
                                {{ __('Configure Geofences') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Analytics Table -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-table me-2"></i>{{ __('Detailed Analytics') }}
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="refresh-analytics">
                            <i class="ti ti-refresh me-1"></i>{{ __('Refresh') }}
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="ti ti-download me-1"></i>{{ __('Export') }}
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" id="export-pdf">
                                        <i class="ti ti-file-type-pdf me-2"></i>{{ __('Export as PDF') }}
                                    </a></li>
                                <li><a class="dropdown-item" href="#" id="export-excel">
                                        <i class="ti ti-file-type-xls me-2"></i>{{ __('Export as Excel') }}
                                    </a></li>
                                <li><a class="dropdown-item" href="#" id="export-csv">
                                        <i class="ti ti-file-type-csv me-2"></i>{{ __('Export as CSV') }}
                                    </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Metric') }}</th>
                                    <th>{{ __('Current Period') }}</th>
                                    <th>{{ __('Previous Period') }}</th>
                                    <th>{{ __('Change') }}</th>
                                    <th>{{ __('Trend') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    // Total Tasks Calculations
                                    $currentTotalTasks = $analytics['kpis']['total_tasks_this_month'] ?? 0;
                                    $previousTotalTasks =
                                        $analytics['previous_period_data']['total_tasks_last_month'] ?? 0;
                                    $totalTasksChange =
                                        $previousTotalTasks > 0
                                            ? round(
                                                (($currentTotalTasks - $previousTotalTasks) / $previousTotalTasks) *
                                                    100,
                                                1,
                                            )
                                            : 0;

                                    // Completed Tasks Calculations
                                    $currentCompletedTasks = $analytics['kpis']['completed_tasks_this_month'] ?? 0;
                                    $previousCompletedTasks =
                                        $analytics['previous_period_data']['completed_tasks_last_month'] ?? 0;
                                    $completedTasksChange =
                                        $previousCompletedTasks > 0
                                            ? round(
                                                (($currentCompletedTasks - $previousCompletedTasks) /
                                                    $previousCompletedTasks) *
                                                    100,
                                                1,
                                            )
                                            : 0;

                                    // Active Drivers Calculations
                                    $currentActiveDrivers = $team->drivers->where('status', 'active')->count();
                                    $previousActiveDrivers =
                                        $analytics['previous_period_data']['active_drivers_last_month'] ?? 0;
                                    $activeDriversChange =
                                        $previousActiveDrivers > 0
                                            ? round(
                                                (($currentActiveDrivers - $previousActiveDrivers) /
                                                    $previousActiveDrivers) *
                                                    100,
                                                1,
                                            )
                                            : 0;

                                    // Revenue Calculations
                                    $currentRevenue = $analytics['kpis']['total_revenue_this_month'] ?? 0;
                                    $previousRevenue =
                                        $analytics['previous_period_data']['total_revenue_last_month'] ?? 0;
                                    $revenueChange =
                                        $previousRevenue > 0
                                            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
                                            : 0;
                                @endphp

                                <tr>
                                    <td><strong>{{ __('Total Tasks') }}</strong></td>
                                    <td>{{ number_format($currentTotalTasks) }}</td>
                                    <td>{{ number_format($previousTotalTasks) }}</td>
                                    <td>
                                        @if ($totalTasksChange > 0)
                                            <span class="badge bg-label-success">+{{ $totalTasksChange }}%</span>
                                        @elseif($totalTasksChange < 0)
                                            <span class="badge bg-label-danger">{{ $totalTasksChange }}%</span>
                                        @else
                                            <span class="badge bg-label-secondary">0%</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($totalTasksChange > 0)
                                            <i class="ti ti-trending-up text-success"></i>
                                        @elseif($totalTasksChange < 0)
                                            <i class="ti ti-trending-down text-danger"></i>
                                        @else
                                            <i class="ti ti-minus text-secondary"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Completed Tasks') }}</strong></td>
                                    <td>{{ number_format($currentCompletedTasks) }}</td>
                                    <td>{{ number_format($previousCompletedTasks) }}</td>
                                    <td>
                                        @if ($completedTasksChange > 0)
                                            <span class="badge bg-label-success">+{{ $completedTasksChange }}%</span>
                                        @elseif($completedTasksChange < 0)
                                            <span class="badge bg-label-danger">{{ $completedTasksChange }}%</span>
                                        @else
                                            <span class="badge bg-label-secondary">0%</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($completedTasksChange > 0)
                                            <i class="ti ti-trending-up text-success"></i>
                                        @elseif($completedTasksChange < 0)
                                            <i class="ti ti-trending-down text-danger"></i>
                                        @else
                                            <i class="ti ti-minus text-secondary"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Active Drivers') }}</strong></td>
                                    <td>{{ number_format($currentActiveDrivers) }}</td>
                                    <td>{{ number_format($previousActiveDrivers) }}</td>
                                    <td>
                                        @if ($activeDriversChange > 0)
                                            <span class="badge bg-label-success">+{{ $activeDriversChange }}%</span>
                                        @elseif($activeDriversChange < 0)
                                            <span class="badge bg-label-danger">{{ $activeDriversChange }}%</span>
                                        @else
                                            <span class="badge bg-label-secondary">0%</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($activeDriversChange > 0)
                                            <i class="ti ti-trending-up text-success"></i>
                                        @elseif($activeDriversChange < 0)
                                            <i class="ti ti-trending-down text-danger"></i>
                                        @else
                                            <i class="ti ti-minus text-secondary"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Total Revenue') }}</strong></td>
                                    <td>{{ number_format($currentRevenue, 0) }} SAR</td>
                                    <td>{{ number_format($previousRevenue, 0) }} SAR</td>
                                    <td>
                                        @if ($revenueChange > 0)
                                            <span class="badge bg-label-success">+{{ $revenueChange }}%</span>
                                        @elseif($revenueChange < 0)
                                            <span class="badge bg-label-danger">{{ $revenueChange }}%</span>
                                        @else
                                            <span class="badge bg-label-secondary">0%</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($revenueChange > 0)
                                            <i class="ti ti-trending-up text-success"></i>
                                        @elseif($revenueChange < 0)
                                            <i class="ti ti-trending-down text-danger"></i>
                                        @else
                                            <i class="ti ti-minus text-secondary"></i>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Average Rating') }}</strong></td>
                                    <td><span class="text-muted">{{ __('Under Development') }}</span></td>
                                    <td><span class="text-muted">{{ __('Under Development') }}</span></td>
                                    <td><span class="badge bg-label-secondary">N/A</span></td>
                                    <td><i class="ti ti-clock text-muted"></i></td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Customer Satisfaction') }}</strong></td>
                                    <td><span class="text-muted">{{ __('Under Development') }}</span></td>
                                    <td><span class="text-muted">{{ __('Under Development') }}</span></td>
                                    <td><span class="badge bg-label-secondary">N/A</span></td>
                                    <td><i class="ti ti-clock text-muted"></i></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        const teamID = {{ $team->id }};
        const baseUrl = '{{ url('/') }}/';

        // Pass analytics data to JavaScript
        const analyticsData = {
            monthly_tasks: @json($analytics['monthly_tasks'] ?? []),
            task_status_distribution: @json($analytics['task_status_distribution'] ?? []),
            revenue_data: @json($analytics['revenue_data'] ?? []),
            daily_tasks: @json($analytics['daily_tasks'] ?? []),
            driver_performance: @json($analytics['driver_performance'] ?? []),
            kpis: @json($analytics['kpis'] ?? []),
            previous_period_data: @json($analytics['previous_period_data'] ?? [])
        };

        // Make data available globally
        window.analyticsData = analyticsData;
    </script>
    @vite(['resources/js/admin/teams/dashboard/analytics.js'])
@endsection
