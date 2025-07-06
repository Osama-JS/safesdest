@extends('layouts/layoutMaster')

@section('title', __('Team Wallet') . ' - ' . $team->name)
@section('teams-isactive')
    active
@endsection
<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    @vite(['resources/css/app.css'])

    <style>
        /* Wallet Statistics Cards Styling */
        .wallet-stats-card {
            transition: all 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
        }

        .wallet-stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .wallet-stats-card .avatar-initial {
            background: linear-gradient(135deg, var(--bs-primary), var(--bs-primary-dark));
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .wallet-stats-card .bg-success {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
        }

        .wallet-stats-card .bg-primary {
            background: linear-gradient(135deg, #007bff, #6610f2) !important;
        }

        .wallet-stats-card .bg-danger {
            background: linear-gradient(135deg, #dc3545, #e83e8c) !important;
        }

        .wallet-stats-card .progress {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 3px;
        }

        .wallet-stats-card .progress-bar {
            border-radius: 3px;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
        }

        .wallet-stats-card h3 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .wallet-stats-card h3 {
                font-size: 1.5rem;
            }
        }
    </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    <script>
        const teamID = {{ $team->id }};
        const walletId = {{ $teamWallet->id }};
    </script>
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/teams/dashboard/wallet.js'])
    @vite(['resources/js/ajax.js'])
@endsection

@section('content')
    <!-- Breadcrumbs -->
    @include('admin.teams.dashboard.partials.breadcrumbs', ['team' => $team])

    <!-- Navigation -->
    @include('admin.teams.dashboard.partials.navigation', ['team' => $team])

    @php
        $balance = $teamWallet->balance ?? 0;
        $credit = $teamWallet->credit ?? 0;
        $debit = $teamWallet->debit ?? 0;
        $balanceClass = $balance < 0 ? 'text-danger' : 'text-success';
        $balanceSign = $balance < 0 ? '-' : '+';

        // Prepare data for wallet stats cards
        $totalCredit = $credit;
        $totalDebit = $debit;
        $netBalance = $balance;
        $balanceCardClass = $netBalance >= 0 ? 'success' : 'danger';
        $balanceIcon = $netBalance >= 0 ? 'ti-trending-up' : 'ti-trending-down';
    @endphp


    <div class="card mb-3">
        <div class="card-header py-4 px-3 border-bottom">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h5 class="card-title mb-1 text-primary fw-bold">
                        <i class="tf-icons ti ti-wallet me-2 fs-3 text-white bg-primary rounded p-1"></i>
                        {{ __('Team Wallet Management') }}
                        <span class="text-muted"> [ <i class="ti ti-wallet fs-5"></i>{{ $teamWallet->id }}]</span>
                    </h5>
                </div>

            </div>
        </div>
    </div>
    <!-- Team Wallet Statistics -->
    <div class="row g-4 mb-6">
        <!-- Total Credit Card -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100 wallet-stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg me-4">
                            <div class="avatar-initial bg-success rounded-circle">
                                <i class="ti ti-arrow-up-right ti-lg text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="mb-0 text-success">{{ __('Total Credit') }}</h6>
                                <i class="ti ti-info-circle text-muted" data-bs-toggle="tooltip"
                                    title="{{ __('Total amount credited to team wallet') }}"></i>
                            </div>
                            <h3 class="mb-0 text-success fw-bold">{{ number_format($totalCredit, 2) }}</h3>
                            <small class="text-muted">
                                <i class="ti ti-currency-riyal me-1"></i>{{ __('Saudi Riyal') }}
                            </small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="ti ti-wallet me-1"></i>{{ __('Team wallet income') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Debit Card -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100 wallet-stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg me-4">
                            <div class="avatar-initial bg-primary rounded-circle">
                                <i class="ti ti-arrow-down-left ti-lg text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="mb-0 text-primary">{{ __('Total Debit') }}</h6>
                                <i class="ti ti-info-circle text-muted" data-bs-toggle="tooltip"
                                    title="{{ __('Total amount debited from team wallet') }}"></i>
                            </div>
                            <h3 class="mb-0 text-primary fw-bold">{{ number_format($totalDebit, 2) }}</h3>
                            <small class="text-muted">
                                <i class="ti ti-currency-riyal me-1"></i>{{ __('Saudi Riyal') }}
                            </small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: 100%"></div>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="ti ti-credit-card me-1"></i>{{ __('Team wallet expenses') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Net Balance Card -->
        <div class="col-xl-4 col-md-12">
            <div class="card border-0 shadow-sm h-100 wallet-stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg me-4">
                            <div class="avatar-initial bg-{{ $balanceCardClass }} rounded-circle">
                                <i class="ti {{ $balanceIcon }} ti-lg text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="mb-0 text-{{ $balanceCardClass }}">{{ __('Net Balance') }}</h6>
                                <i class="ti ti-info-circle text-muted" data-bs-toggle="tooltip"
                                    title="{{ __('Current team wallet balance (Debit - Credit)') }}"></i>
                            </div>
                            <h3 class="mb-0 text-{{ $balanceCardClass }} fw-bold">
                                {{ $netBalance >= 0 ? '+' : '' }}{{ number_format($netBalance, 2) }}
                            </h3>
                            <small class="text-muted">
                                <i class="ti ti-currency-riyal me-1"></i>{{ __('Saudi Riyal') }}
                            </small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $balanceCardClass }}" style="width: 100%"></div>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="ti ti-calculator me-1"></i>
                            @if ($netBalance >= 0)
                                {{ __('Positive balance - funds available') }}
                            @else
                                {{ __('Negative balance - deficit') }}
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Overview -->
    <div class="card shadow-sm border-0 mb-4">




        <!-- Payment Controls -->
        <div class="card mb-3" id="payment-controls" style="display: none;">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-check-box text-primary me-2 fs-4"></i>
                            <span class="fw-semibold">{{ __('Selected Transactions') }}:</span>
                            <span class="badge bg-primary ms-2" id="selected-count">0</span>
                            <span class="fw-semibold ms-3">{{ __('Total Amount') }}:</span>
                            <span class="badge bg-success ms-2" id="selected-total">0.00 SAR</span>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-outline-secondary me-2" id="clear-selection">
                            <i class="ti ti-x me-1"></i>{{ __('Clear Selection') }}
                        </button>
                        <button type="button" class="btn btn-success" id="process-payment" data-bs-toggle="modal"
                            data-bs-target="#paymentModal">
                            <i class="ti ti-credit-card me-1"></i>{{ __('Process Payment') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>



        <!-- Transactions Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ti ti-list me-2"></i>{{ __('Transaction History') }}
                </h5>
            </div>



            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 datatables-transactions">
                        <thead class="table-light">
                            <tr>

                                <th></th>
                                <th>#</th>
                                <th>{{ __('Amount') }}</th>
                                \ <th>{{ __('Description') }}</th>
                                <th>{{ __('Maturity') }}</th>
                                <th>{{ __('Task') }}</th>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('Created At') }}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Transaction Modal -->
        <div class="modal fade" id="addTransactionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="transactionModalTitle">{{ __('Add New Transaction') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="{{ __('Close') }}"></button>
                    </div>
                    <form class="add-new-transaction pt-0 form_submit" method="POST"
                        action="{{ route('teams.wallet.transaction.store') }}">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" name="wallet" id="wallet_id" value="{{ $teamWallet->id }}">
                            <input type="hidden" name="id" id="trans_id">

                            <!-- Amount -->
                            <div class="mb-4">
                                <label class="form-label" for="amount">* {{ __('Amount') }}</label>
                                <input type="number" name="amount" class="form-control" id="trans_amount"
                                    placeholder="{{ __('Enter the amount') }}" step="0.01" min="0" required>
                                <span class="amount-error text-danger text-error"></span>
                            </div>

                            <!-- Transaction Type -->
                            <div class="mb-4">
                                <label class="form-label d-block">* {{ __('Transaction Type') }}</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="type" id="credit"
                                            value="credit" autocomplete="off" required checked>
                                        <label class="btn btn-outline-success w-100 py-2 btn-credit" for="credit">
                                            <i class="ti ti-circle-plus me-1"></i> {{ __('Credit') }}
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="type" id="debit"
                                            value="debit" autocomplete="off" required>
                                        <label class="btn btn-outline-danger w-100 py-2 btn-debit" for="debit">
                                            <i class="ti ti-circle-minus me-1"></i> {{ __('Debit') }}
                                        </label>
                                    </div>
                                </div>
                                <span class="type-error text-danger text-error"></span>
                            </div>

                            <!-- Maturity Time (Hidden by default) -->
                            <div class="mb-4" id="maturity-time-group" style="display: none;">
                                <label class="form-label" for="maturity">{{ __('Maturity Time') }}</label>
                                <input type="datetime-local" name="maturity" class="form-control" id="trans_maturity">
                                <span class="maturity-error text-danger text-error"></span>
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label class="form-label" for="description">* {{ __('Description') }}</label>
                                <textarea name="description" class="form-control" id="trans_description" rows="3"
                                    placeholder="{{ __('Transaction description...') }}" required></textarea>
                                <span class="description-error text-danger text-error"></span>
                            </div>

                            <!-- Image -->
                            <div class="mb-6">
                                <label class="form-label" for="trans-image">{{ __('Attachment') }}</label>
                                <div class="form-group mt-2">
                                    <img src="{{ url(asset('assets/img/placeholder.jpg')) }}"
                                        data-image="{{ url(asset('assets/img/placeholder.jpg')) }}" alt=""
                                        id="image" style="width: 120px; height: 100px; object-fit: cover;"
                                        class="rounded preview-pickup-image image-input">
                                    <input type="file" class="form-control file-pickup-image" id="trans-image"
                                        name="image" style="display: none" />
                                    <span class="image-error text-danger text-error"></span>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-label-secondary"
                                data-bs-dismiss="modal">{{ __('Close') }}</button>
                            <button type="submit" class="btn btn-primary me-3 data-submit">{{ __('Submit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalLabel">
                            <i class="ti ti-credit-card me-2"></i>{{ __('Process Team Payment') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="paymentForm">
                            @csrf
                            <input type="hidden" name="team_id" value="{{ $team->id }}">

                            <!-- Payment form content will be loaded here -->
                            <div id="payment-form-content">
                                <div class="text-center py-4">
                                    <i class="ti ti-loader ti-spin fs-2"></i>
                                    <p class="mt-2">{{ __('Loading payment form...') }}</p>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-1"></i>{{ __('Cancel') }}
                        </button>
                        <button type="button" class="btn btn-success" id="confirmPayment">
                            <i class="ti ti-check me-1"></i>{{ __('Confirm Payment') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Modal -->
        <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="imageModalLabel">{{ __('View Attachment') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="{{ __('close') }}"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="modalImage" src="" class="img-fluid rounded shadow"
                            alt="{{ __('attachment') }}" />
                    </div>
                </div>
            </div>
        </div>
    @endsection
