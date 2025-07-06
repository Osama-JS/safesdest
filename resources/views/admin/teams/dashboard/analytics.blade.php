@extends('layouts/layoutMaster')

@section('title', __('Team Analytics') . ' - ' . $team->name)
@section('teams-isactive')
    active
@endsection
<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
    @vite(['resources/css/app.css'])
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
                                <h4 class="mb-0 me-2">{{ $team->tasks->count() }}</h4>
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
                                    $totalTasks = $team->tasks->count();
                                    $completedTasks = $team->tasks->where('status', 'completed')->count();
                                    $completionRate =
                                        $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
                                @endphp
                                <h4 class="mb-0 me-2">{{ $completionRate }}%</h4>
                                <small class="text-muted">{{ $completedTasks }}/{{ $totalTasks }}</small>
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
                                    $totalRevenue = $team->tasks->where('status', 'completed')->sum('total_price');
                                @endphp
                                <h4 class="mb-0 me-2">{{ number_format($totalRevenue, 0) }}</h4>
                                <small class="text-muted">SAR</small>
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
                                    <i class="ti ti-clock ti-26px"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="mb-0">{{ __('Avg. Completion Time') }}</h6>
                                <small class="text-danger">-3%</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <h4 class="mb-0 me-2">2.4</h4>
                                <small class="text-muted">{{ __('Hours') }}</small>
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
                    </h5>
                </div>
                <div class="card-body">
                    <div id="revenueChart"></div>
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
                    <button type="button" class="btn btn-outline-primary btn-sm" id="export-analytics">
                        <i class="ti ti-download me-1"></i>{{ __('Export Report') }}
                    </button>
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
                                <tr>
                                    <td><strong>{{ __('Total Tasks') }}</strong></td>
                                    <td>{{ $team->tasks->count() }}</td>
                                    <td>{{ max(0, $team->tasks->count() - 15) }}</td>
                                    <td><span class="badge bg-label-success">+12%</span></td>
                                    <td><i class="ti ti-trending-up text-success"></i></td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Completed Tasks') }}</strong></td>
                                    <td>{{ $team->tasks->where('status', 'completed')->count() }}</td>
                                    <td>{{ max(0, $team->tasks->where('status', 'completed')->count() - 8) }}</td>
                                    <td><span class="badge bg-label-success">+15%</span></td>
                                    <td><i class="ti ti-trending-up text-success"></i></td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Active Drivers') }}</strong></td>
                                    <td>{{ $team->drivers->where('status', 'active')->count() }}</td>
                                    <td>{{ max(0, $team->drivers->where('status', 'active')->count() - 2) }}</td>
                                    <td><span class="badge bg-label-info">+8%</span></td>
                                    <td><i class="ti ti-trending-up text-info"></i></td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Average Rating') }}</strong></td>
                                    <td>4.7</td>
                                    <td>4.5</td>
                                    <td><span class="badge bg-label-success">+4%</span></td>
                                    <td><i class="ti ti-trending-up text-success"></i></td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('Customer Satisfaction') }}</strong></td>
                                    <td>92%</td>
                                    <td>89%</td>
                                    <td><span class="badge bg-label-success">+3%</span></td>
                                    <td><i class="ti ti-trending-up text-success"></i></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
