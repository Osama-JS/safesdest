@extends('layouts/layoutMaster')

@section('title', __('Wallets'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss'])

    <style>
        #wallet-statistics .card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
        }

        #wallet-statistics .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 25px 0 rgba(67, 89, 113, 0.15);
        }

        #wallet-statistics .avatar-initial {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #wallet-statistics .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        #wallet-statistics .fw-medium {
            font-weight: 500;
            color: #6c757d;
        }

        .statistics-loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .statistics-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/block-ui/block-ui.js'])
@endsection

@section('page-script')

    @vite(['resources/js/admin/wallets/wallets.js'])
    @vite(['resources/js/ajax.js'])
@endsection

@section('content')

    <!-- Statistics Cards -->
    <div class="row mb-4" id="wallet-statistics">
        <div class="col-lg-4 col-md-6 col-12 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <div class="avatar-initial bg-success rounded">
                                <i class="ti ti-trending-up"></i>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" id="cardOpt3" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">

                            </button>
                        </div>
                    </div>
                    <span class="fw-medium d-block mb-1">{{ __('Credit Balances') }}</span>
                    <h3 class="card-title text-nowrap mb-2" id="credit-total">0 {{ __('SAR') }}</h3>
                    <small class="text-success fw-medium">
                        <i class="ti ti-arrow-up"></i>
                        <span id="credit-count">0</span> {{ __('Wallets') }}
                    </small>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-12 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <div class="avatar-initial bg-danger rounded">
                                <i class="ti ti-trending-down"></i>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" id="cardOpt4" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            </button>
                        </div>
                    </div>
                    <span class="fw-medium d-block mb-1">{{ __('Debit Balances') }}</span>
                    <h3 class="card-title text-nowrap mb-2" id="debit-total">0 {{ __('SAR') }}</h3>
                    <small class="text-danger fw-medium">
                        <i class="ti ti-arrow-down"></i>
                        <span id="debit-count">0</span> {{ __('Wallets') }}
                    </small>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-12 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="card-title d-flex align-items-start justify-content-between">
                        <div class="avatar flex-shrink-0">
                            <div class="avatar-initial bg-info rounded" id="net-icon">
                                <i class="ti ti-calculator"></i>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" id="cardOpt5" data-bs-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                            </button>
                        </div>
                    </div>
                    <span class="fw-medium d-block mb-1">{{ __('Net Total') }}</span>
                    <h3 class="card-title text-nowrap mb-2" id="net-total">0 {{ __('SAR') }}</h3>
                    <small class="fw-medium" id="net-description">
                        <i class="ti ti-minus"></i>
                        <span id="total-count">0</span> {{ __('Total Wallets') }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">
                <i class="tf-icons ti ti-wallet  me-2 fs-3 text-white bg-primary rounded p-1"></i>
                {{ __('Wallets') }}
            </h5>
            <p class="text-muted mb-0">{{ __('Manage wallet balances and transactions') }}</p>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-users table">
                <thead class="border-top">
                    <tr>
                        <th></th>
                        <th>#</th>
                        <th>{{ __('name') }}</th>
                        <th>{{ __('balance') }}</th>
                        <th>{{ __('Debt Ceiling') }}</th>
                        <th>{{ __('status') }}</th>
                        <th>{{ __('preview') }}</th>
                        <th>{{ __('last transaction') }}</th>
                        <th>{{ __('actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>

@endsection
