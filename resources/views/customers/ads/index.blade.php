@extends('layouts/layoutMaster')

@section('title', __('My Task Ads'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js'])
@endsection

@section('page-script')
    @vite(['resources/js/customers/ads.js'])
@endsection

@section('content')
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Total Ads') }}</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2" id="total-ads">0</h4>
                                <small class="text-success">({{ __('All Time') }})</small>
                            </div>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="ti ti-speakerphone ti-sm"></i>
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
                            <span>{{ __('Active Ads') }}</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2" id="active-ads">0</h4>
                                <small class="text-warning">({{ __('Advertised') }})</small>
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
                            <span>{{ __('Total Offers') }}</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2" id="total-offers">0</h4>
                                <small class="text-info">({{ __('Received') }})</small>
                            </div>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="ti ti-hand-click ti-sm"></i>
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
                                <h4 class="mb-0 me-2" id="completed-ads">0</h4>
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
    </div>

    <!-- Task Ads List -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="ti ti-speakerphone me-2"></i>{{ __('My Task Advertisements') }}
            </h5>
        </div>

        <div class="card-datatable table-responsive">
            <div class="row mx-2">
                <div class="col-md-2">
                    <div class="me-3">
                        <div class="dataTables_length" id="DataTables_Table_0_length">
                            <label>{{ __('Show') }}
                                <select name="DataTables_Table_0_length" aria-controls="DataTables_Table_0"
                                    class="form-select">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                {{ __('entries') }}
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-10">
                    <div
                        class="dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0">
                        <div class="dt-buttons btn-group flex-wrap">
                            <!-- Date Filter -->
                            <div class="btn-group mx-2">
                                <input type="text" class="form-control" id="from_date"
                                    placeholder="{{ __('From Date') }}" readonly>
                            </div>
                            <div class="btn-group mx-2">
                                <input type="text" class="form-control" id="to_date" placeholder="{{ __('To Date') }}"
                                    readonly>
                            </div>

                            <!-- Status Filter -->
                            <div class="btn-group mx-2">
                                <select class="form-select" id="status_filter">
                                    <option value="">{{ __('All Status') }}</option>
                                    <option value="advertised">{{ __('Advertised') }}</option>
                                    <option value="assign">{{ __('Assigned') }}</option>
                                    <option value="started">{{ __('Started') }}</option>
                                    <option value="completed">{{ __('Completed') }}</option>
                                    <option value="cancelled">{{ __('Cancelled') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <table class="datatables-ads table border-top">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('#') }}</th>
                        <th>{{ __('Task ID') }}</th>
                        <th>{{ __('Pickup Address') }}</th>
                        <th>{{ __('Delivery Address') }}</th>
                        <th>{{ __('Vehicle') }}</th>
                        <th>{{ __('Price Range') }}</th>
                        <th>{{ __('Offers') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Created At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Help Section -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-help me-2"></i>{{ __('How Task Ads Work') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="avatar avatar-lg bg-label-primary mb-3">
                                    <i class="ti ti-speakerphone ti-lg"></i>
                                </div>
                                <h6>{{ __('1. Create Advertisement') }}</h6>
                                <p class="text-muted">
                                    {{ __('When you create a task, you can choose to advertise it instead of setting a fixed price') }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="avatar avatar-lg bg-label-warning mb-3">
                                    <i class="ti ti-users ti-lg"></i>
                                </div>
                                <h6>{{ __('2. Receive Offers') }}</h6>
                                <p class="text-muted">
                                    {{ __('Drivers will see your ad and submit their price offers for completing the task') }}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center mb-3">
                                <div class="avatar avatar-lg bg-label-success mb-3">
                                    <i class="ti ti-check ti-lg"></i>
                                </div>
                                <h6>{{ __('3. Choose Best Offer') }}</h6>
                                <p class="text-muted">
                                    {{ __('Review all offers and select the driver that best fits your requirements and budget') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <h6 class="alert-heading">{{ __('Tips for Better Results') }}</h6>
                        <ul class="mb-0">
                            <li>{{ __('Set realistic price ranges to attract more drivers') }}</li>
                            <li>{{ __('Provide clear and detailed task descriptions') }}</li>
                            <li>{{ __('Respond to offers promptly to secure the best drivers') }}</li>
                            <li>{{ __('Check driver ratings and reviews before accepting offers') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
