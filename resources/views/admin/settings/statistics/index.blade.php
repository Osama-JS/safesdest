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
            <div class="card">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-2">
                        <i class="ti ti-chart-line me-2 fs-3 text-white bg-primary rounded p-1"></i>
                        {{ __('Settings') }} | {{ __('System Statistics') }}
                    </h5>
                    <p class="text-muted mb-3">
                        {{ __('Comprehensive statistics for system performance and financial operations') }}</p>

                    <div class="d-flex gap-2 align-items-center">
                        <div class="d-flex gap-2">
                            <input type="text" id="dateFromFilter" class="form-control flatpickr-date"
                                placeholder="{{ __('Date From') }}" style="width: 150px;">
                            <input type="text" id="dateToFilter" class="form-control flatpickr-date"
                                placeholder="{{ __('Date To') }}" style="width: 150px;">
                        </div>
                        <button type="button" class="btn btn-primary" id="updateStatistics">
                            <i class="ti ti-refresh me-1"></i>
                            {{ __('Update Statistics') }}
                        </button>
                        {{-- <button type="button" class="btn btn-outline-success" id="exportStatistics">
                            <i class="ti ti-download me-1"></i>
                            {{ __('Export') }}
                        </button> --}}
                    </div>
                </div>

                <!-- Main Statistics Cards -->
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card ">
                                <div class="card-body text-center">
                                    <i class="ti ti-truck-delivery fs-1 mb-2"></i>
                                    <h3 class="card-title" id="totalTasks">0</h3>
                                    <p class="card-text">{{ __('Total Tasks') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card ">
                                <div class="card-body text-center">
                                    <i class="ti ti-circle-check fs-1 mb-2"></i>
                                    <h3 class="card-title" id="closedTasks">0</h3>
                                    <p class="card-text">{{ __('Closed Tasks') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card ">
                                <div class="card-body text-center">
                                    <i class="ti ti-currency-riyal fs-1 mb-2"></i>
                                    <h3 class="card-title" id="totalRevenue">0 SAR</h3>
                                    <p class="card-text">{{ __('Total Revenue') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card ">
                                <div class="card-body text-center">
                                    <i class="ti ti-percentage fs-1 mb-2"></i>
                                    <h3 class="card-title" id="totalCommission">0 SAR</h3>
                                    <p class="card-text">{{ __('Total Commission') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Secondary Statistics -->
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <h4 class="text-primary" id="inProgressTasks">0</h4>
                                    <small class="text-muted">{{ __('In Progress Tasks') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <h4 class="text-secondary" id="completedTasks">0</h4>
                                    <small class="text-muted">{{ __('Completed Tasks') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <h4 class="text-danger" id="cancelledTasks">0</h4>
                                    <small class="text-muted">{{ __('Cancelled Tasks') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <h4 class="text-info" id="platformIncome">0 SAR</h4>
                                    <small class="text-muted">Platform Income{{ __('') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <h4 class="text-success" id="averageTaskPrice">0 SAR</h4>
                                    <small class="text-muted">{{ __('Average Task Price') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border">
                                <div class="card-body text-center">
                                    <h4 class="text-warning" id="completionRate">0%</h4>
                                    <small class="text-muted">{{ __('Completion Rate') }}</small>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Charts Section -->
                <div class="card-body">
                    <div class="row g-4">
                        <!-- Daily Tasks Chart -->
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">{{ __('Daily Tasks') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div id="dailyTasksChart"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Daily Revenue Chart -->
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">{{ __('Daily Revenue') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div id="dailyRevenueChart"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Tasks Status Distribution -->
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">{{ __('Task Status Distribution') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div id="statusDistributionChart"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Commission Chart -->
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">{{ __('Daily Commission') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div id="dailyCommissionChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Statistics Table -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">{{ __('Status Details') }}</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Count') }}</th>
                                            <th>{{ __('Percentage') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="statusDetailsTable">
                                        <!-- Will be populated by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">{{ __('Payment Details') }}</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Payment Method') }}</th>
                                            <th>{{ __('Amount') }}</th>
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

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Export Statistics') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="exportForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Export Format') }}</label>
                            <select name="format" class="form-select" required>
                                <option value="">{{ __('Choose Format') }}</option>
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-success">
                            <i class="ti ti-download me-1"></i>
                            {{ __('Export') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/js/admin/settings/statistics.js', 'resources/js/ajax.js'])
@endsection
