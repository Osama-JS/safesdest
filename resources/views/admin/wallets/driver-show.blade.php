@extends('layouts/layoutMaster')

@section('title', __('Driver Wallet') . ':' . $data->id)

@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss'])

@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/block-ui/block-ui.js'])
@endsection

@section('page-script')
    <script>
        const walletId = "{{ $data->id }}";
        const walletType = "{{ $data->user_type }}";
    </script>
    @vite(['resources/js/admin/wallets/driver-show.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])

@endsection

@section('wallets-isactive')
    active
@endsection

@section('content')

    <style>
        /* Payment Controls Styling */
        #payment-controls {
            border-left: 4px solid #28a745;
            box-shadow: 0 2px 10px rgba(40, 167, 69, 0.1);
        }

        #payment-controls .badge {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }

        /* Checkbox Styling */
        .transaction-checkbox {
            transform: scale(1.2);
            cursor: pointer;
        }

        .transaction-checkbox:checked {
            background-color: #28a745;
            border-color: #28a745;
        }

        /* Modal Styling */
        #paymentModal .modal-content {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }



        #paymentModal .btn-close {
            filter: invert(1);
        }

        /* Payment Summary Card */
        #paymentModal .card.bg-light {
            border: 1px solid #e9ecef;
            border-radius: 10px;
        }

        /* Selected Transactions Table */
        #selectedTransactionsTable {
            font-size: 0.875rem;
        }

        #selectedTransactionsTable th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        #selectedTransactionsTable .payment-amount {
            font-weight: 600;
            color: #28a745;
        }

        /* Button Styling */
        .remove-transaction {
            border-radius: 50%;
            width: 30px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }


        #confirmPayment:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        /* Loading Animation */
        .ti-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive Improvements */
        @media (max-width: 768px) {
            #payment-controls .row {
                flex-direction: column;
                gap: 1rem;
            }

            #payment-controls .text-end {
                text-align: start !important;
            }

            #selectedTransactionsTable {
                font-size: 0.75rem;
            }

            .modal-lg {
                max-width: 95%;
            }
        }
    </style>

    @php
        $balance = $data->balance;
        $credit = $data->credit;
        $debit = $data->debit;
        $debtCeiling = $data->debt_ceiling;

        $balanceClass = $balance < 0 ? 'text-danger' : 'text-success';
        $balanceSign = $balance < 0 ? '-' : '+';

        // نسبة استخدام سقف الدين
        $usedDebt = abs($balance < 0 ? $balance : 0);
        $debtPercent = $debtCeiling > 0 ? min(100, round(($usedDebt / $debtCeiling) * 100)) : 0;

        $progressBarClass = $debtPercent < 50 ? 'bg-success' : ($debtPercent < 80 ? 'bg-warning' : 'bg-danger');
    @endphp

    <div class="card shadow-sm border-0 mb-4">
        <!-- Header -->
        <div class="card-header py-4 px-3 border-bottom">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <!-- Title -->
                <div>
                    <h5 class="card-title mb-1 text-primary fw-bold">
                        <i class="tf-icons ti ti-steering-wheel me-2"></i>{{ __('Driver Wallet') }}
                        <span class="text-muted">| [{{ $data->id }}]</span>
                        <span class="text-dark">{{ $data->owner->name }}</span>
                        @if ($data->driver && $data->driver->team)
                            <span class="badge bg-info ms-2">{{ $data->driver->team->name }}</span>
                        @endif
                    </h5>
                </div>

                <!-- Info Section -->
                <div class="d-flex flex-column flex-sm-row gap-3 text-nowrap">
                    <!-- Balance -->
                    <div class="d-flex align-items-center">
                        <i class="ti ti-wallet me-2 fs-5 {{ $balanceClass }}"></i>
                        <span class="fw-semibold">{{ __('Balance') }}:</span>
                        <span class="ms-1 fw-bold {{ $balanceClass }}">
                            {{ $balanceSign }}{{ number_format(abs($balance), 2) }}
                        </span>
                    </div>

                    <!-- Credit -->
                    <div class="d-flex align-items-center">
                        <i class="ti ti-arrow-up-right text-success me-2 fs-5"></i>
                        <span class="fw-semibold">{{ __('Credit') }}:</span>
                        <span class="ms-1 fw-bold text-success">{{ number_format($credit, 2) }}</span>
                    </div>

                    <!-- Debit -->
                    <div class="d-flex align-items-center">
                        <i class="ti ti-arrow-down-left text-danger me-2 fs-5"></i>
                        <span class="fw-semibold">{{ __('Debit') }}:</span>
                        <span class="ms-1 fw-bold text-danger">{{ number_format($debit, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Progress Bar for Debt Ceiling -->
            @if ($debtCeiling > 0)
                <div class="mt-4">
                    <small class="text-muted d-block mb-1">
                        {{ __('Debt Usage') }} ({{ $usedDebt }} / {{ $debtCeiling }}) - {{ $debtPercent }}%
                    </small>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar {{ $progressBarClass }}" role="progressbar"
                            style="width: {{ $debtPercent }}%;" aria-valuenow="{{ $debtPercent }}" aria-valuemin="0"
                            aria-valuemax="100">
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Payment Controls (Hidden by default) -->
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

        <!-- Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 datatables-transactions">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="select-all-transactions" class="form-check-input">
                            </th>
                            <th>#</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Maturity') }}</th>
                            <th>{{ __('Task') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Created At') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true"
        data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">
                        <i class="ti ti-credit-card me-2"></i>{{ __('Process Driver Payment') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('Close') }}"></button>
                </div>

                <form id="paymentForm">
                    <div class="modal-body">
                        <div class="row mb-4">
                            <div class="col-md-6">

                                <!-- Payment Amount -->
                                <div class="mb-4">
                                    <label for="totalPaymentAmount" class="form-label fw-semibold">
                                        <i class="ti ti-currency-dollar me-1"></i>{{ __('Payment Amount') }}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="totalPaymentAmount"
                                            name="total_amount" placeholder="{{ __('Enter payment amount') }}"
                                            step="0.01" min="0" required>
                                        <span class="input-group-text">SAR</span>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <i class="ti ti-info-circle me-1"></i>{{ __('Maximum amount') }}:
                                                <span class="fw-bold text-primary" id="maxAmountDisplay">0.00 SAR</span>
                                            </small>
                                        </div>
                                        <div class="col-md-6 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                id="useMaxAmount">
                                                <i class="ti ti-arrow-up me-1"></i>{{ __('Use Maximum') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <!-- Payment Summary -->
                                <div class="card  mb-4">
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-4">
                                                <div class="d-flex flex-column">
                                                    <span
                                                        class="text-muted small">{{ __('Selected Transactions') }}</span>
                                                    <span class="fw-bold text-primary fs-5"
                                                        id="modal-selected-count">0</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex flex-column">
                                                    <span class="text-muted small">{{ __('Original Total') }}</span>
                                                    <span class="fw-bold text-info fs-5" id="modal-original-total">0.00
                                                        SAR</span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex flex-column">
                                                    <span class="text-muted small">{{ __('Payment Total') }}</span>
                                                    <span class="fw-bold text-success fs-5" id="modal-payment-total">0.00
                                                        SAR</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- Selected Transactions Table -->
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3">
                                <i class="ti ti-list me-1"></i>{{ __('Selected Transactions') }}
                            </h6>
                            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                <table class="table table-sm table-bordered" id="selectedTransactionsTable">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th width="60">#</th>
                                            <th>{{ __('Driver') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th width="100">{{ __('Amount') }}</th>
                                            <th width="120">{{ __('Payment') }}</th>
                                            <th width="50">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedTransactionsBody">
                                        <!-- Dynamic content -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Payment Notes -->
                        <div class="mb-3">
                            <label for="paymentNotes" class="form-label">
                                <i class="ti ti-note me-1"></i>{{ __('Payment Notes') }}
                                <small class="text-muted">({{ __('Optional') }})</small>
                            </label>
                            <textarea class="form-control" id="paymentNotes" name="notes" rows="3"
                                placeholder="{{ __('Add any notes about this payment (e.g., payment method, reference number, special instructions)...') }}"></textarea>
                            <small class="text-muted">
                                <i class="ti ti-info-circle me-1"></i>
                                {{ __('These notes will be included in the payment description for the driver\'s wallet transaction') }}
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="ti ti-x me-1"></i>{{ __('Cancel') }}
                        </button>
                        <button type="button" class="btn btn-success" id="confirmPayment">
                            <i class="ti ti-check me-1"></i>{{ __('Confirm Payment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
