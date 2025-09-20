@extends('layouts/layoutMaster')

@section('title', __('Wallets') . ':' . $data->id)

@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss'])

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

        /* Enhanced header styling */
        .wallet-header-card {
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .wallet-stats-card h3 {
                font-size: 1.5rem;
            }
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/block-ui/block-ui.js'])
@endsection

@section('page-script')
    <script>
        const walletId = {{ $data->id }};
    </script>
    @vite(['resources/js/admin/teams/wallet.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])


@endsection
@section('teams-isactive')
    active
@endsection
@section('content')

    @php
        $balance = $data->balance ?? 0;
        $credit = $data->credit ?? 0;
        $debit = $data->debit ?? 0;
        $debtCeiling = $data->debt_ceiling;

        $balanceClass = $balance < 0 ? 'text-danger' : 'text-success';
        $balanceSign = $balance < 0 ? '-' : '+';

        // نسبة استخدام سقف الدين
        $usedDebt = abs($balance < 0 ? $balance : 0);
        $debtPercent = $debtCeiling > 0 ? min(100, round(($usedDebt / $debtCeiling) * 100)) : 0;

        $progressBarClass = $debtPercent < 50 ? 'bg-success' : ($debtPercent < 80 ? 'bg-warning' : 'bg-danger');

        // Prepare data for wallet stats cards
        $totalCredit = $credit;
        $totalDebit = $debit;
        $netBalance = $balance;
        $balanceCardClass = $netBalance >= 0 ? 'success' : 'danger';
        $balanceIcon = $netBalance >= 0 ? 'ti-trending-up' : 'ti-trending-down';
    @endphp

    <!-- Team Wallet Statistics Cards -->
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
                                    title="{{ __('Current team wallet balance') }}"></i>
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

    <!-- Enhanced Wallet Overview -->
    <div class="card wallet-header-card shadow-sm border-0 mb-4">
        <!-- Header -->
        <div class="card-header  py-4 px-3 border-bottom">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <!-- Enhanced Title -->
                <div>
                    <h4 class="card-title mb-2 d-flex align-items-center">
                        <div class="avatar avatar-md me-3">
                            <div class="avatar-initial bg-primary rounded-circle">
                                <i class="ti ti-wallet text-white"></i>
                            </div>
                        </div>
                        <div>
                            <span class=" fw-bold">{{ __('Team Wallet') }}: <span
                                    class="text-primary">{{ $data->team?->name }}</span></span>
                            <br>
                            <small class="text-muted">

                                <span class="badge bg-label-primary ms-2">
                                    <i class="ti ti-hash me-1"></i>{{ $data->id }}
                                </span>
                            </small>
                        </div>
                    </h4>
                </div>
                <div class="d-flex gap-2">
                    @can('generate_payment_request')
                        <button type="button" class="btn btn-success" onclick="showTeamPaymentRequestOptions()">
                            <i class="ti ti-file-invoice me-1"></i>{{ __('Payment Request') }}
                        </button>
                    @endcan
                    <a href="{{ route('teams.teams') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-left me-1"></i>{{ __('Back to Teams') }}
                    </a>
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

        <!-- Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 datatables-users">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>#</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Maturity') }}</th>
                            <th>{{ __('Task') }}</th>
                            <th>{{ __('User') }}</th>
                            <th>{{ __('Created At') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>


    <div class="modal fade " id="submitModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ __('Add New Transaction') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('Close') }}"></button>
                </div>
                <form class="add-new-transaction pt-0 form_submit" method="POST"
                    action="{{ route('teams.wallet.transaction.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="col-xl-12">
                            <div class="nav-align-top mb-6">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <!-- Hidden wallet_id -->
                                        <input type="hidden" name="wallet" id="wallet_id"
                                            value="{{ $data->id }}">
                                        <span class="wallet-error text-danger text-error"></span>

                                        <input type="hidden" name="id" id="trans_id">

                                        <!-- Amount -->
                                        <div class="mb-4">
                                            <label class="form-label" for="amount">* {{ __('Amount') }}</label>
                                            <input type="number" name="amount" class="form-control" id="trans_amount"
                                                placeholder="{{ __('Enter the amount') }}" step="0.01"
                                                min="0">
                                            <span class="amount-error text-danger text-error"></span>
                                        </div>

                                        <!-- Transaction Type -->
                                        <div class="mb-4">
                                            <label class="form-label d-block">* {{ __('Transaction Type') }}</label>
                                            <div class="row">
                                                <div class="col-6">
                                                    <input type="radio" class="btn-check" name="type"
                                                        id="credit" value="credit" autocomplete="off" required
                                                        checked>
                                                    <label class="btn btn-outline-success w-100 py-2 btn-credit"
                                                        for="credit">
                                                        <i class="ti ti-circle-plus me-1"></i> {{ __('Credit') }}
                                                    </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="radio" class="btn-check" name="type"
                                                        id="debit" value="debit" autocomplete="off" required>
                                                    <label class="btn btn-outline-danger w-100 py-2 btn-debit"
                                                        for="debit">
                                                        <i class="ti ti-circle-minus me-1"></i> {{ __('Debit') }}
                                                    </label>
                                                </div>
                                            </div>
                                            <span class="type-error text-danger text-error"></span>
                                        </div>



                                        <!-- Maturity Time (Hidden by default) -->
                                        <div class="mb-4" id="maturity-time-group" style="display: none;">
                                            <label class="form-label" for="maturity">{{ __('Maturity Time') }}</label>
                                            <input type="datetime-local" name="maturity" class="form-control"
                                                id="trans_maturity">
                                            <span class="maturity-error text-danger text-error"></span>
                                        </div>

                                        <!-- Description -->
                                        <div class="mb-4">
                                            <label class="form-label" for="description">* {{ __('Description') }}</label>
                                            <textarea name="description" class="form-control" id="trans_description" rows="3"
                                                placeholder="{{ __('Optional notes...') }}"></textarea>
                                            <span class="description-error text-danger text-error"></span>
                                        </div>
                                        <div class="mb-6">
                                            <div class="form-group mb-3">
                                                <label for="image" class="form-label">
                                                    <i class="fas fa-file-upload me-1"></i>
                                                    {{ __('Upload File') }}
                                                </label>
                                                <input type="file" name="image" class="form-control" id="image"
                                                    accept=".jpeg,.jpg,.png,.webp,.pdf,.doc,.docx,.txt,.csv">
                                                <div class="form-text text-muted mt-1">
                                                    <small>
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        {{ __('Supported formats: Images (JPEG, PNG, WebP), Documents (PDF). Max size: 10MB') }}
                                                    </small>
                                                </div>
                                                <span class="image-error text-danger text-error"></span>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">Submit</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">{{ __('View the File') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('close') }}"></button>
                </div>
                <div class="modal-body text-center" id="modalContent">
                    <img id="modalImage" src="" class="img-fluid rounded shadow" alt="{{ __('image') }}" />
                </div>
            </div>
        </div>
    </div>

    @can('generate_payment_request')
        <!-- Team Payment Request Modal -->
        <div class="modal fade" id="teamPaymentRequestModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Payment Request from Team Wallet: ') }} <span
                                id="teamPaymentRequestWalletId" class="bg-info text-white rounded p-1 px-2"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Team Wallet Information Section -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">{{ __('Team Wallet Information') }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('Team Wallet ID') }}:</label>
                                            <span id="teamWalletInfoId" class="text-primary fw-bold"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('Team Wallet Balance') }}:</label>
                                            <span id="teamWalletInfoAmount" class="text-success fw-bold"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('Team Name') }}:</label>
                                            <span id="teamWalletInfoName"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('Team Leader') }}:</label>
                                            <span id="teamWalletInfoLeader" class="text-muted"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('Leader Email') }}:</label>
                                            <span id="teamWalletInfoLeaderEmail" class="text-muted"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Request Form Section -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">{{ __('Payment Request Form') }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <form id="teamPaymentRequestForm">
                                            <input type="hidden" id="teamPaymentRequestWalletIdInput" name="team_wallet_id">

                                            <div class="mb-3">
                                                <label class="form-label" for="teamRequestedAmount">*
                                                    {{ __('Requested Amount') }}</label>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" class="form-control"
                                                        id="teamRequestedAmount" name="requested_amount" required>
                                                    <span class="input-group-text">{{ __('SAR') }}</span>
                                                </div>
                                                <div class="form-text">
                                                    <small class="text-muted">{{ __('Available amount') }}: <span
                                                            id="teamMaxAmount" class="text-primary fw-bold"></span>
                                                        ({{ __('You can enter a larger amount') }})</small>
                                                </div>
                                                <span class="team_requested_amount-error text-error"></span>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="teamBankName">* {{ __('Bank Name') }}</label>

                                                <select name="bank_name" id="teamBankName" class="form-select">
                                                    <option value="">{{ __('Select Bank') }}</option>
                                                    <option value="البنك الأهلي السعودي">البنك الأهلي السعودي
                                                    </option>
                                                    <option value="بنك الراجحي">بنك الراجحي</option>
                                                    <option value="بنك الرياض">بنك الرياض</option>
                                                    <option value="البنك السعودي للاستثمار">البنك السعودي
                                                        للاستثمار</option>
                                                    <option value="البنك السعودي الفرنسي">البنك السعودي
                                                        الفرنسي</option>
                                                    <option value="البنك السعودي البريطاني">البنك السعودي
                                                        البريطاني (ساب)</option>
                                                    <option value="بنك العربي الوطني">بنك العربي الوطني
                                                    </option>
                                                    <option value="بنك سامبا">بنك سامبا</option>
                                                    <option value="البنك الأول">البنك الأول</option>
                                                    <option value="بنك الجزيرة">بنك الجزيرة</option>
                                                    <option value="بنك الإنماء">بنك الإنماء</option>
                                                    <option value="البنك العربي">البنك العربي</option>
                                                    <option value="other">{{ __('Other') }}</option>
                                                </select>
                                                <input type="text" class="form-control mt-2" id="teamCustomBankName"
                                                    name="custom_bank_name" placeholder="{{ __('Enter bank name') }}"
                                                    style="display: none;">
                                                <span class="team_bank_name-error text-danger text-error"></span>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="teamAccountNumber">*
                                                    {{ __('Account Number') }}</label>
                                                <input type="text" class="form-control" id="teamAccountNumber"
                                                    name="account_number" placeholder="1234567890" minlength="8" required>
                                                <span class="team_account_number-error text-danger text-error"></span>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="teamIbanNumber">*
                                                    {{ __('IBAN Number') }}</label>
                                                <input type="text" class="form-control" id="teamIbanNumber"
                                                    name="iban_number" placeholder="SA12 3456 7890 1234 5678 90"
                                                    maxlength="29" required>
                                                <div class="form-text">
                                                    <small class="text-muted">{{ __('Format: SA + 22 digits') }}</small>
                                                </div>
                                                <span class="team_iban_number-error text-danger text-error"></span>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="teamNotes">{{ __('Notes') }}</label>
                                                <textarea type="text" class="form-control" id="teamNotes" name="notes" placeholder="Notes" maxlength="1000"></textarea>
                                                <span class="notes-error text-danger text-error"></span>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="teamSelectedTasks">{{ __('Related Tasks') }}
                                                    ({{ __('Optional') }})</label>
                                                <select class="form-select" id="teamSelectedTasks" name="selected_tasks[]"
                                                    multiple>
                                                    <!-- سيتم تحميل المهام ديناميكياً -->
                                                </select>
                                                <div class="form-text">
                                                    <small
                                                        class="text-muted">{{ __('Select tasks related to this payment request') }}</small>
                                                </div>
                                                <span class="selected_tasks-error text-danger text-error"></span>
                                            </div>


                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="button" class="btn btn-primary"
                            id="generateTeamPaymentRequest">{{ __('Generate Payment Request') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan




    @can('view_payment_requests_logs')
        <!-- Team Payment Request Logs Section -->
        <div class="card mt-4">
            <div class="card-header py-4 px-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1">
                            <i class="ti ti-file-text me-2 text-primary"></i>
                            {{ __('Payment Request Logs') }}
                        </h5>
                        <p class="text-muted mb-0">{{ __('History of printed payment requests') }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadTeamPaymentRequestLogs()">
                            <i class="ti ti-refresh me-1"></i>{{ __('Refresh') }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="teamPaymentLogsContainer">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ __('Loading...') }}</span>
                        </div>
                        <p class="mt-2 text-muted">{{ __('Loading payment request logs...') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endcan



@endsection
