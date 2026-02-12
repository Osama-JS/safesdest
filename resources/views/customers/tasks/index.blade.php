@extends('layouts/layoutMaster')

@section('title', __('My Tasks'))

@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/js/customers/tasks.js'])
@endsection

@section('content')
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Total Tasks') }}</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2" id="total-tasks">0</h4>
                                <small class="text-success">({{ __('All Time') }})</small>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="ti ti-clipboard-list ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Active Tasks') }}</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2" id="active-tasks">0</h4>
                                <small class="text-warning">({{ __('On Going') }})</small>
                            </div>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                            <i class="ti ti-clock ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Completed Tasks') }}</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2" id="completed-tasks">0</h4>
                                <small class="text-success">({{ __('Finished') }})</small>
                            </div>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="ti ti-check ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Total Spent') }}</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2" id="total-spent">0 SAR</h4>
                                <small class="text-info">({{ __('All Time') }})</small>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="ti ti-currency-dollar ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks List Table -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="tf-icons ti ti-truck-delivery me-2 fs-3 text-white bg-primary rounded p-1"></i>
                    {{ __('My Tasks') }}
                </h5>
                <div class="btn-group">
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#reportModal">
                        <i class="ti ti-file-type-pdf me-2"></i>{{ __('Generate PDF Report') }}
                    </button>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#excelReportModal">
                        <i class="ti ti-file-spreadsheet me-2"></i>{{ __('Export to Excel') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="card-datatable table-responsive">


            <table class="datatables-tasks table border-top">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('#') }}</th>
                        <th>{{ __('Task ID') }}</th>
                        <th>{{ __('My Task #') }}</th>
                        <th>{{ __('Total Price') }}</th>
                        <th>{{ __('Pickup Address') }}</th>
                        <th>{{ __('Delivery Address') }}</th>
                        <th>{{ __('Driver') }}</th>
                        <th>{{ __('Vehicle') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Created At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Report Generation Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportModalLabel">
                        <i class="ti ti-file-type-pdf me-2"></i>{{ __('Generate Tasks Report') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="reportForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Date Range') }}</label>
                                    <input type="text" id="reportDateRange" class="form-control"
                                        placeholder="{{ __('Select Date Range') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Task Status') }}</label>
                                    <select id="reportStatus" class="form-select flitter-status" multiple>
                                        <option value="advertised">{{ __('Advertised') }}</option>
                                        <option value="in_progress">{{ __('In Progress') }}</option>
                                        <option value="assign">{{ __('Assigned') }}</option>
                                        <option value="started">{{ __('Started') }}</option>
                                        <option value="in pickup point">{{ __('In Pickup Point') }}</option>
                                        <option value="loading">{{ __('Loading') }}</option>
                                        <option value="in the way">{{ __('In The Way') }}</option>
                                        <option value="in delivery point">{{ __('In Delivery Point') }}</option>
                                        <option value="unloading">{{ __('Unloading') }}</option>
                                        <option value="completed">{{ __('Completed') }}</option>
                                        <option value="invoiced">{{ __('Invoiced') }}</option>
                                        <option value="canceled">{{ __('Canceled') }}</option>
                                        <option value="refund">{{ __('Refund') }}</option>
                                    </select>
                                    <div class="form-text">
                                        <small
                                            class="text-muted">{{ __('Hold Ctrl/Cmd to select multiple options. Leave empty for all statuses.') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Payment Status') }}</label>
                                    <select id="reportPaymentStatus" class="form-select flitter-payment" multiple>
                                        <option value="waiting">{{ __('Waiting') }}</option>
                                        <option value="pending">{{ __('Pending') }}</option>
                                        <option value="completed">{{ __('Completed') }}</option>
                                    </select>
                                    <div class="form-text">
                                        <small
                                            class="text-muted">{{ __('Hold Ctrl/Cmd to select multiple options. Leave empty for all payment statuses.') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Payment Method') }}</label>
                                    <select id="reportPaymentMethod" class="form-select flitter-payment-type" multiple>
                                        <option value="cash">{{ __('Cash') }}</option>
                                        <option value="credit">{{ __('Credit Card') }}</option>
                                        <option value="banking">{{ __('Bank Transfer') }}</option>
                                        <option value="wallet">{{ __('Wallet') }}</option>
                                    </select>
                                    <div class="form-text">
                                        <small
                                            class="text-muted">{{ __('Hold Ctrl/Cmd to select multiple options. Leave empty for all payment methods.') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="ti ti-info-circle me-2"></i>
                                    {{ __('This report will include all your tasks within the selected criteria with detailed information and statistics.') }}
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-2"></i>{{ __('Cancel') }}
                    </button>
                    <button type="button" class="btn btn-success" id="generateReportBtn">
                        <i class="ti ti-file-type-pdf me-2"></i>{{ __('Generate PDF Report') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Excel Export Modal -->
    <div class="modal fade" id="excelReportModal" tabindex="-1" aria-labelledby="excelReportModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="excelReportModalLabel">
                        <i class="ti ti-file-spreadsheet me-2"></i>{{ __('Export Tasks to Excel') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="excelReportForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Date Range') }}</label>
                                    <input type="text" id="excelReportDateRange" class="form-control"
                                        placeholder="{{ __('Select Date Range') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Task Status') }}</label>
                                    <select id="excelReportStatus" class="form-select excel-flitter-status" multiple>
                                        <option value="advertised">{{ __('Advertised') }}</option>
                                        <option value="in_progress">{{ __('In Progress') }}</option>
                                        <option value="assign">{{ __('Assigned') }}</option>
                                        <option value="started">{{ __('Started') }}</option>
                                        <option value="in pickup point">{{ __('In Pickup Point') }}</option>
                                        <option value="loading">{{ __('Loading') }}</option>
                                        <option value="in the way">{{ __('In The Way') }}</option>
                                        <option value="in delivery point">{{ __('In Delivery Point') }}</option>
                                        <option value="unloading">{{ __('Unloading') }}</option>
                                        <option value="completed">{{ __('Completed') }}</option>
                                        <option value="invoiced">{{ __('Invoiced') }}</option>
                                        <option value="canceled">{{ __('Canceled') }}</option>
                                        <option value="refund">{{ __('Refund') }}</option>
                                    </select>
                                    <div class="form-text">
                                        <small
                                            class="text-muted">{{ __('Hold Ctrl/Cmd to select multiple options. Leave empty for all statuses.') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Payment Status') }}</label>
                                    <select id="excelReportPaymentStatus" class="form-select excel-flitter-payment"
                                        multiple>
                                        <option value="waiting">{{ __('Waiting') }}</option>
                                        <option value="pending">{{ __('Pending') }}</option>
                                        <option value="completed">{{ __('Completed') }}</option>
                                    </select>
                                    <div class="form-text">
                                        <small
                                            class="text-muted">{{ __('Hold Ctrl/Cmd to select multiple options. Leave empty for all payment statuses.') }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Payment Method') }}</label>
                                    <select id="excelReportPaymentMethod" class="form-select excel-flitter-payment-type"
                                        multiple>
                                        <option value="cash">{{ __('Cash') }}</option>
                                        <option value="credit">{{ __('Credit Card') }}</option>
                                        <option value="banking">{{ __('Bank Transfer') }}</option>
                                        <option value="wallet">{{ __('Wallet') }}</option>
                                    </select>
                                    <div class="form-text">
                                        <small
                                            class="text-muted">{{ __('Hold Ctrl/Cmd to select multiple options. Leave empty for all payment methods.') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-success">
                                    <i class="ti ti-info-circle me-2"></i>
                                    {{ __('This will export all your tasks within the selected criteria to an Excel file with detailed information and statistics.') }}
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-2"></i>{{ __('Cancel') }}
                    </button>
                    <button type="button" class="btn btn-success" id="generateExcelReportBtn">
                        <i class="ti ti-file-spreadsheet me-2"></i>{{ __('Export to Excel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
