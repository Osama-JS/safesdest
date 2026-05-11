@extends('layouts/layoutMaster')

@section('title', __('Platform Wallet'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

<!-- Page Styles -->
@section('page-style')
    @vite(['resources/css/app.css'])
    <style>
        .stats-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }



        .commission-badge {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }

        .payment-status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }



        /* .dataTables_wrapper .dataTables_length,
                                        .dataTables_wrapper .dataTables_filter {
                                            margin-bottom: 1rem;
                                        } */

        .btn-export {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
    </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/admin/platform-wallet/index.js'])
@endsection

@section('content')


    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">
                                <i class="tf-icons ti ti-cash me-2 fs-3 text-white bg-primary rounded p-1"></i>
                                {{ __('Platform Wallet') }}
                            </h5>
                            <p class="text-muted mb-0">{{ __('Track platform commissions from completed tasks') }}</p>
                        </div>

                    </div>


                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4" id="statisticsCards">
        <!-- Statistics will be loaded here -->
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filter-card">
                <div class="card-body">
                    <h5 class="card-title  mb-3">
                        <i class="ti ti-filter me-2"></i>{{ __('Filters') }}
                    </h5>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label ">{{ __('Date From') }}</label>
                            <input type="date" class="form-control" id="dateFrom" name="date_from">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label ">{{ __('Date To') }}</label>
                            <input type="date" class="form-control" id="dateTo" name="date_to">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label ">{{ __('Task Status') }}</label>
                            <select class="form-select" id="taskStatus" name="task_status">
                                <option value="">{{ __('All Statuses (Excl. Canceled/Refund)') }}</option>
                                <option value="pending_payment">{{ __('Pending Payment') }}</option>
                                <option value="advertised">{{ __('Advertised') }}</option>
                                <option value="in_progress">{{ __('In Progress') }}</option>
                                <option value="assign">{{ __('Assign') }}</option>
                                <option value="accepted">{{ __('Accepted') }}</option>
                                <option value="started">{{ __('Started') }}</option>
                                <option value="in pickup point">{{ __('In Pickup Point') }}</option>
                                <option value="loading">{{ __('Loading') }}</option>
                                <option value="in the way">{{ __('In the Way') }}</option>
                                <option value="in delivery point">{{ __('In Delivery Point') }}</option>
                                <option value="unloading">{{ __('Unloading') }}</option>
                                <option value="completed">{{ __('Completed') }}</option>
                                <option value="canceled">{{ __('Canceled') }}</option>
                                <option value="refund">{{ __('Refund') }}</option>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label ">{{ __('Closed Status') }}</label>
                            <select class="form-select" id="isClosed" name="is_closed">
                                <option value="">{{ __('All') }}</option>
                                <option value="1">{{ __('Closed Only') }}</option>
                                <option value="0">{{ __('Open Only') }}</option>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label ">{{ __('Commission Type') }}</label>
                            <select class="form-select" id="commissionType" name="commission_type">
                                <option value="">{{ __('All Types') }}</option>
                                <option value="dynamic">{{ __('Dynamic') }}</option>
                                <option value="manual">{{ __('Manual') }}</option>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label ">{{ __('Payment Status') }}</label>
                            <select class="form-select" id="paymentStatus" name="payment_status">
                                <option value="">{{ __('All Statuses') }}</option>
                                <option value="pending">{{ __('Pending') }}</option>
                                <option value="just_commission">{{ __('Commission Only') }}</option>
                                <option value="all">{{ __('Fully Paid') }}</option>
                            </select>
                        </div>

                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-primary" id="applyFilters">
                                <i class="ti ti-search me-1"></i>{{ __('Apply Filters') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                                <i class="ti ti-refresh me-1"></i>{{ __('Clear Filters') }}
                            </button>
                        </div>
                    </div>
                    <div class="row">

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-list me-2"></i>{{ __('Commission Records') }}
                    </h5>
                    <div class="card-actions d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="exportExcel">
                            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('Export Excel') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="refreshTable">
                            <i class="ti ti-refresh me-1"></i>{{ __('Refresh') }}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover datatables-platform-wallet" id="platformWalletTable">
                            <thead>
                                <tr>
                                    <th>{{ __('Task ID') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Driver') }}</th>
                                    <th>{{ __('Team') }}</th>
                                    <th>{{ __('Route') }}</th>
                                    <th>{{ __('Total Price') }}</th>
                                    <th>{{ __('Commission') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Payment Status') }}</th>
                                    <th>{{ __('Task Status') }}</th>
                                    <th>{{ __('Completed At') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
