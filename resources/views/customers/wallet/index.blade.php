@extends('layouts/layoutMaster')

@section('title', __('My Wallet'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.js'])
@endsection

@section('page-script')
    @vite(['resources/js/customers/wallet.js'])
@endsection

@section('content')
    <!-- Wallet Overview Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Current Balance') }}</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2 text-{{ $data->balance >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($data->balance, 2) }} SAR
                                </h4>
                            </div>
                            <small class="text-muted">{{ __('Available Balance') }}</small>
                        </div>
                        <span class="badge bg-label-{{ $data->balance >= 0 ? 'success' : 'danger' }} rounded p-2">
                            <i class="ti ti-wallet ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Debt Ceiling') }}</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2 text-info">
                                    {{ number_format($data->debt_ceiling, 2) }} SAR
                                </h4>
                            </div>
                            <small class="text-muted">{{ __('Maximum Credit Limit') }}</small>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="ti ti-credit-card ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Available Credit') }}</span>
                            <div class="d-flex align-items-end mt-2">
                                @php
                                    $availableCredit = $data->debt_ceiling + $data->balance;
                                @endphp
                                <h4 class="mb-0 me-2 text-{{ $availableCredit >= 0 ? 'success' : 'warning' }}">
                                    {{ number_format($availableCredit, 2) }} SAR
                                </h4>
                            </div>
                            <small class="text-muted">{{ __('Total Available') }}</small>
                        </div>
                        <span class="badge bg-label-{{ $availableCredit >= 0 ? 'success' : 'warning' }} rounded p-2">
                            <i class="ti ti-coins ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Wallet Status') }}</span>
                            <div class="d-flex align-items-end mt-2">
                                <span class="badge bg-label-{{ $data->status ? 'success' : 'danger' }} fs-6">
                                    {{ $data->status ? __('Active') : __('Inactive') }}
                                </span>
                            </div>
                            <small class="text-muted">{{ __('Account Status') }}</small>
                        </div>
                        <span class="badge bg-label-{{ $data->status ? 'success' : 'secondary' }} rounded p-2">
                            <i class="ti ti-{{ $data->status ? 'check' : 'x' }} ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Transactions -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="ti ti-history me-2"></i>{{ __('Transaction History') }}
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

                            <!-- Transaction Type Filter -->
                            <div class="btn-group mx-2">
                                <select class="form-select" id="type_filter">
                                    <option value="">{{ __('All Types') }}</option>
                                    <option value="credit">{{ __('Credit') }}</option>
                                    <option value="debit">{{ __('Debit') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <table class="datatables-wallet table border-top">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('#') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Task') }}</th>
                        <th>{{ __('Maturity') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Date') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Wallet Information -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-info-circle me-2"></i>{{ __('Wallet Information') }}
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <span class="h6">{{ __('Wallet ID') }}:</span>
                            <span>#{{ $data->id }}</span>
                        </li>
                        <li class="mb-2">
                            <span class="h6">{{ __('Account Type') }}:</span>
                            <span class="badge bg-label-primary">{{ ucfirst($data->user_type) }}</span>
                        </li>
                        <li class="mb-2">
                            <span class="h6">{{ __('Preview Status') }}:</span>
                            <span class="badge bg-label-{{ $data->preview ? 'success' : 'secondary' }}">
                                {{ $data->preview ? __('Enabled') : __('Disabled') }}
                            </span>
                        </li>
                        <li class="mb-2">
                            <span class="h6">{{ __('Created At') }}:</span>
                            <span>{{ $data->created_at->format('Y-m-d H:i') }}</span>
                        </li>
                        <li class="mb-2">
                            <span class="h6">{{ __('Last Updated') }}:</span>
                            <span>{{ $data->updated_at->format('Y-m-d H:i') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-help me-2"></i>{{ __('Wallet Help') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">{{ __('Understanding Your Wallet') }}</h6>
                        <ul class="mb-0">
                            <li><strong>{{ __('Current Balance') }}:</strong> {{ __('Your available funds') }}</li>
                            <li><strong>{{ __('Debt Ceiling') }}:</strong> {{ __('Maximum credit you can use') }}</li>
                            <li><strong>{{ __('Available Credit') }}:</strong> {{ __('Total funds you can spend') }}</li>
                            <li><strong>{{ __('Credit Transactions') }}:</strong> {{ __('Money added to your wallet') }}
                            </li>
                            <li><strong>{{ __('Debit Transactions') }}:</strong> {{ __('Money spent from your wallet') }}
                            </li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <h6 class="alert-heading">{{ __('Important Notes') }}</h6>
                        <ul class="mb-0">
                            <li>{{ __('Negative balance means you owe money') }}</li>
                            <li>{{ __('Contact support for wallet issues') }}</li>
                            <li>{{ __('All transactions are recorded and tracked') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
