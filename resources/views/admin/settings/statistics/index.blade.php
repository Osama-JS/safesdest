@extends('layouts/layoutMaster')

@section('title', __('System Statistics'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header border-bottom pb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="ti ti-chart-line me-2 fs-3 text-white bg-primary rounded p-1"></i>
                                {{ __('Settings') }} | {{ __('System Statistics') }}
                            </h5>
                            <p class="text-muted mb-0 small">
                                {{ __('Comprehensive statistics for system performance and financial operations') }}
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <div class="d-flex gap-2">
                                <input type="text" id="dateFromFilter" class="form-control flatpickr-date"
                                    placeholder="{{ __('Date From') }}" style="width: 140px;">
                                <input type="text" id="dateToFilter" class="form-control flatpickr-date"
                                    placeholder="{{ __('Date To') }}" style="width: 140px;">
                            </div>
                            <button type="button" class="btn btn-primary" id="updateStatistics">
                                <i class="ti ti-refresh me-1"></i>
                                {{ __('Update') }}
                            </button>
                        </div>
                    </div>

                    <!-- Quick Preset Filters -->
                    <div class="mt-3 pt-3 border-top d-flex flex-wrap gap-2 align-items-center">
                        <span class="text-muted small fw-medium me-1">
                            <i class="ti ti-filter me-1"></i>{{ __('Quick Presets') }}:
                        </span>
                        <button type="button" class="btn btn-sm btn-outline-primary preset-filter-btn" data-preset="today">
                            {{ __('Today') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary preset-filter-btn" data-preset="yesterday">
                            {{ __('Yesterday') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary preset-filter-btn" data-preset="last_7_days">
                            {{ __('Last 7 Days') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary preset-filter-btn active" data-preset="last_30_days">
                            {{ __('Last 30 Days') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary preset-filter-btn" data-preset="this_month">
                            {{ __('This Month') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary preset-filter-btn" data-preset="last_month">
                            {{ __('Last Month') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary preset-filter-btn" data-preset="this_year">
                            {{ __('This Year') }}
                        </button>
                    </div>
                </div>

                <!-- Main Financial & Task KPI Cards with Closed vs Unclosed Breakdown -->
                <div class="card-body border-bottom bg-light-subtle py-3">
                    <div class="row g-3">
                        <!-- Total Tasks -->
                        <div class="col-xl-3 col-sm-6">
                            <div class="card h-100 shadow-none border">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="avatar bg-label-primary p-2 rounded">
                                            <i class="ti ti-truck-delivery fs-3 text-primary"></i>
                                        </div>
                                        <span id="tasksGrowthBadge" class="badge bg-label-secondary">--</span>
                                    </div>
                                    <h4 class="mb-1" id="totalTasks">0</h4>
                                    <p class="text-muted mb-2 small fw-semibold">{{ __('Total Tasks') }}</p>
                                    <div class="d-flex justify-content-between pt-2 border-top small">
                                        <span class="text-success" title="{{ __('Closed Tasks') }}">
                                            <i class="ti ti-lock me-1"></i>{{ __('Closed') }}: <strong id="closedTasks">0</strong>
                                        </span>
                                        <span class="text-warning" title="{{ __('Unclosed Tasks') }}">
                                            <i class="ti ti-lock-open me-1"></i>{{ __('Unclosed') }}: <strong id="unclosedTasks">0</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Revenue (Closed vs Unclosed) -->
                        <div class="col-xl-3 col-sm-6">
                            <div class="card h-100 shadow-none border">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="avatar bg-label-info p-2 rounded">
                                            <i class="ti ti-currency-riyal fs-3 text-info"></i>
                                        </div>
                                        <span id="revenueGrowthBadge" class="badge bg-label-secondary">--</span>
                                    </div>
                                    <h4 class="mb-1 text-info fw-bold" id="totalRevenue">0 SAR</h4>
                                    <p class="text-muted mb-2 small fw-semibold">{{ __('Total Revenue (All Tasks)') }}</p>
                                    <div class="d-flex justify-content-between pt-2 border-top small">
                                        <span class="text-success" title="{{ __('Closed Tasks Revenue') }}">
                                            <i class="ti ti-circle-check me-1"></i>{{ __('Closed') }}: <strong id="closedRevenue">0 SAR</strong>
                                        </span>
                                        <span class="text-warning" title="{{ __('Unclosed Tasks Revenue') }}">
                                            <i class="ti ti-clock me-1"></i>{{ __('Unclosed') }}: <strong id="unclosedRevenue">0 SAR</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Commission (Closed vs Unclosed) -->
                        <div class="col-xl-3 col-sm-6">
                            <div class="card h-100 shadow-none border">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="avatar bg-label-warning p-2 rounded">
                                            <i class="ti ti-percentage fs-3 text-warning"></i>
                                        </div>
                                        <span id="commissionGrowthBadge" class="badge bg-label-secondary">--</span>
                                    </div>
                                    <h4 class="mb-1 text-warning fw-bold" id="totalCommission">0 SAR</h4>
                                    <p class="text-muted mb-2 small fw-semibold">{{ __('Total Commission (All Tasks)') }}</p>
                                    <div class="d-flex justify-content-between pt-2 border-top small">
                                        <span class="text-success" title="{{ __('Closed Tasks Commission') }}">
                                            <i class="ti ti-lock me-1"></i>{{ __('Closed') }}: <strong id="closedCommission">0 SAR</strong>
                                        </span>
                                        <span class="text-warning" title="{{ __('Unclosed Tasks Commission') }}">
                                            <i class="ti ti-clock me-1"></i>{{ __('Unclosed') }}: <strong id="unclosedCommission">0 SAR</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Net Platform Profit -->
                        <div class="col-xl-3 col-sm-6">
                            <div class="card h-100 shadow-none border border-success border-opacity-50" style="background: rgba(40, 199, 111, 0.04);">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="avatar bg-label-success p-2 rounded">
                                            <i class="ti ti-wallet fs-3 text-success"></i>
                                        </div>
                                        <span id="netCommissionGrowthBadge" class="badge bg-label-secondary">--</span>
                                    </div>
                                    <h4 class="mb-1 text-success fw-bold" id="closedNetCommission">0 SAR</h4>
                                    <p class="text-muted mb-2 small fw-semibold">{{ __('Net Realized Commission (Closed)') }}</p>
                                    <div class="d-flex justify-content-between pt-2 border-top small">
                                        <span class="text-warning" title="{{ __('Pending / Unclosed Commission') }}">
                                            <i class="ti ti-clock me-1"></i>{{ __('Pending') }}: <strong id="unclosedNetCommission">0 SAR</strong>
                                        </span>
                                        <span class="text-danger" title="{{ __('Partner Deductions') }}">
                                            <i class="ti ti-arrow-down-right me-1"></i>{{ __('Deductions') }}: <strong id="totalDeductions">0 SAR</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Operational & Performance Metrics -->
                <div class="card-body border-bottom py-3">
                    <div class="row g-3">
                        <div class="col-md-2 col-6">
                            <div class="p-2 border rounded text-center h-100">
                                <h5 class="text-primary mb-1" id="inProgressTasks">0</h5>
                                <small class="text-muted">{{ __('In Progress Tasks') }}</small>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="p-2 border rounded text-center h-100">
                                <h5 class="text-success mb-1" id="completedTasks">0</h5>
                                <small class="text-muted">{{ __('Completed Tasks') }}</small>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="p-2 border rounded text-center h-100">
                                <h5 class="text-danger mb-1" id="cancelledTasks">0</h5>
                                <small class="text-muted">{{ __('Cancelled Tasks') }}</small>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="p-2 border rounded text-center h-100">
                                <h5 class="text-info mb-1" id="completionRate">0%</h5>
                                <small class="text-muted">{{ __('Completion Rate') }}</small>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="p-2 border rounded text-center h-100">
                                <h5 class="text-secondary mb-1" id="totalDriverPayouts">0 SAR</h5>
                                <small class="text-muted">{{ __('Driver Payouts') }}</small>
                                <div class="small text-muted mt-1" style="font-size: 11px;">
                                    <span class="text-success">{{ __('Closed') }}: <span id="closedDriverPayouts">0</span></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="p-2 border rounded text-center h-100">
                                <h5 class="text-warning mb-1" id="operationalFleet">0 / 0</h5>
                                <small class="text-muted">{{ __('Active Drivers') }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section (With Closed vs Unclosed Breakdown) -->
                <div class="card-body border-bottom">
                    <div class="row g-4">
                        <!-- Daily Tasks Chart -->
                        <div class="col-md-6">
                            <div class="card border shadow-none h-100">
                                <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0"><i class="ti ti-chart-bar me-1 text-primary"></i>{{ __('Daily Tasks (Closed vs Unclosed)') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div id="dailyTasksChart"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Daily Revenue Chart -->
                        <div class="col-md-6">
                            <div class="card border shadow-none h-100">
                                <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0"><i class="ti ti-chart-line me-1 text-info"></i>{{ __('Daily Revenue (Closed vs Unclosed)') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div id="dailyRevenueChart"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Tasks Status Distribution -->
                        <div class="col-md-6">
                            <div class="card border shadow-none h-100">
                                <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0"><i class="ti ti-chart-pie me-1 text-warning"></i>{{ __('Task Status Distribution') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div id="statusDistributionChart"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Daily Commission Chart -->
                        <div class="col-md-6">
                            <div class="card border shadow-none h-100">
                                <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0"><i class="ti ti-chart-dots me-1 text-success"></i>{{ __('Daily Commission (Closed vs Unclosed)') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div id="dailyCommissionChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leaderboards: Top Performers (With Closed vs Unclosed Breakdown) -->
                <div class="card-body border-bottom">
                    <div class="row g-4">
                        <!-- Top Customers -->
                        <div class="col-md-6">
                            <div class="card border shadow-none h-100">
                                <div class="card-header pb-2 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0">
                                        <i class="ti ti-crown me-1 text-warning"></i>{{ __('Top 5 Customers') }}
                                    </h6>
                                    <small class="text-muted">{{ __('By Total Spent') }}</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>{{ __('Customer') }}</th>
                                                <th>{{ __('Tasks') }}</th>
                                                <th>{{ __('Closed Spent') }}</th>
                                                <th>{{ __('Unclosed Spent') }}</th>
                                                <th>{{ __('Total Spent') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="topCustomersTable">
                                            <tr><td colspan="6" class="text-center text-muted py-3">{{ __('Loading...') }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Top Drivers -->
                        <div class="col-md-6">
                            <div class="card border shadow-none h-100">
                                <div class="card-header pb-2 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0">
                                        <i class="ti ti-steering-wheel me-1 text-primary"></i>{{ __('Top 5 Drivers') }}
                                    </h6>
                                    <small class="text-muted">{{ __('By Tasks & Delivery') }}</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>{{ __('Driver') }}</th>
                                                <th>{{ __('Phone') }}</th>
                                                <th>{{ __('Tasks (Closed/All)') }}</th>
                                                <th>{{ __('Closed Deliv.') }}</th>
                                                <th>{{ __('Total Deliv.') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="topDriversTable">
                                            <tr><td colspan="6" class="text-center text-muted py-3">{{ __('Loading...') }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Breakdown Tables (With Closed vs Unclosed Amounts) -->
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Status Details -->
                        <div class="col-md-6">
                            <div class="card border shadow-none h-100">
                                <div class="card-header pb-2 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0">{{ __('Status Details & Amounts') }}</h6>
                                    <small class="text-muted">{{ __('Closed vs Unclosed') }}</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Count') }}</th>
                                                <th>{{ __('Closed Amount') }}</th>
                                                <th>{{ __('Unclosed Amount') }}</th>
                                                <th>{{ __('Total Amount') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="statusDetailsTable">
                                            <!-- Will be populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details -->
                        <div class="col-md-6">
                            <div class="card border shadow-none h-100">
                                <div class="card-header pb-2 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0">{{ __('Payment Methods & Amounts') }}</h6>
                                    <small class="text-muted">{{ __('Closed vs Unclosed') }}</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('Payment Method') }}</th>
                                                <th>{{ __('Closed Amount') }}</th>
                                                <th>{{ __('Unclosed Amount') }}</th>
                                                <th>{{ __('Total Amount') }}</th>
                                                <th>{{ __('Percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="paymentDetailsTable">
                                            <!-- Will be populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/admin/settings/statistics.js', 'resources/js/ajax.js'])
@endsection
