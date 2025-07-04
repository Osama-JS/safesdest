@extends('layouts/layoutMaster')

@section('title', __('My Tasks'))

@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
            <h5 class="card-title mb-0">
                <i class="tf-icons ti ti-truck-delivery me-2 fs-3 text-white bg-primary rounded p-1"></i>
                {{ __('My Tasks') }}
            </h5>
        </div>

        <div class="card-datatable table-responsive">


            <table class="datatables-tasks table border-top">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('#') }}</th>
                        <th>{{ __('Task ID') }}</th>
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
@endsection
