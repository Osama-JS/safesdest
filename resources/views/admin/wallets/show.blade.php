@extends('layouts/layoutMaster')

@section('title', __('Wallets') . ':' . $data->id)

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
    </script>
    @vite(['resources/js/admin/wallets/show.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])


@endsection
@section('wallets-isactive')
    active
@endsection
@section('content')

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
        <div class="card-header  py-4 px-3 border-bottom">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <!-- Title -->
                <div>
                    <h5 class="card-title mb-1 text-primary fw-bold">
                        <i class="tf-icons ti ti-wallet  me-2 fs-3 text-white bg-primary rounded p-1"></i>
                        {{ __('Wallet') }}
                        <span class="text-muted">| [{{ $data->id }}]</span>
                        <span class="text-dark">{{ $data->owner->name }}</span>
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
            @can('generate_payment_request')
                @if ($data->user_type === 'driver')
                    <div class="mt-4">
                        <a href="javascript:;" class="btn btn-success me-2" id="payment-request"><i
                                class="ti ti-receipt me-1"></i>{{ __('Payment Request') }}</a>
                        <a href="{{ route('wallets.hyperpay_payouts', $data->id) }}" class="btn btn-primary" id="hyperpay-payouts-btn"><i class="ti ti-brand-mastercard me-1"></i>{{ __('HyperPay Payouts') }}</a>
                    </div>
                @endif
            @endcan


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
                            <th>{{ __('Task / Clearance') }}</th>
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
                    action="{{ route('wallets.transaction.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="col-xl-12">
                            <div class="nav-align-top mb-6">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <!-- Hidden wallet_id -->
                                        <input type="hidden" name="wallet" id="wallet_id" value="{{ $data->id }}">
                                        <span class="wallet-error text-danger text-error"></span>

                                        <input type="hidden" name="id" id="trans_id">

                                        <!-- Amount -->
                                        <div class="mb-4">
                                            <label class="form-label" for="amount">* {{ __('Amount') }}</label>
                                            <input type="number" name="amount" class="form-control" id="trans_amount"
                                                placeholder="{{ __('Enter the amount') }}" step="0.01" min="0">
                                            <span class="amount-error text-danger text-error"></span>
                                        </div>

                                        <!-- Transaction Type -->
                                        <div class="mb-4">
                                            <label class="form-label d-block">* {{ __('Transaction Type') }}</label>
                                            <div class="row">
                                                <div class="col-6">
                                                    <input type="radio" class="btn-check" name="type" id="credit"
                                                        value="credit" autocomplete="off" required checked>
                                                    <label class="btn btn-outline-success w-100 py-2 btn-credit"
                                                        for="credit">
                                                        <i class="ti ti-circle-plus me-1"></i> {{ __('Credit') }}
                                                    </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="radio" class="btn-check" name="type" id="debit"
                                                        value="debit" autocomplete="off" required>
                                                    <label class="btn btn-outline-danger w-100 py-2 btn-debit"
                                                        for="debit">
                                                        <i class="ti ti-circle-minus me-1"></i> {{ __('Debit') }}
                                                    </label>
                                                </div>
                                            </div>
                                            <span class="type-error text-danger text-error"></span>
                                        </div>

                                        <!-- Investment Settlement Settings (Only visible for Credit and Customer Wallets) -->
                                        @if ($data->user_type === 'customer')
                                            <div id="investment-settlement-container" class="mb-4">
                                                <button type="button" class="btn btn-outline-info w-100 mb-2" id="toggleSettlementPanelBtn">
                                                    <i class="ti ti-settings me-1"></i> {{ __('Investment Settlement Settings') }}
                                                </button>
                                                
                                                <div id="settlement-panel" class="border rounded p-3 bg-light" style="display: none;">
                                                    <h6 class="mb-2 text-primary"><i class="ti ti-list-check me-1"></i>{{ __('Unsettled Investor Tasks') }}</h6>
                                                    <p class="small text-muted mb-2">{{ __('Select tasks to settle with this credit amount.') }}</p>
                                                    
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span class="fw-bold">{{ __('Credit Amount') }}: <span id="settlement-credit-amount" class="text-success">0</span> ريال</span>
                                                        <span class="fw-bold">{{ __('Selected Total') }}: <span id="settlement-selected-total" class="text-primary">0</span> ريال</span>
                                                        <span class="fw-bold">{{ __('Remaining Amount') }}: <span id="settlement-remaining-amount" class="text-warning">0</span> ريال</span>
                                                    </div>

                                                    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                                                        <table class="table table-sm table-bordered">
                                                            <thead class="table-dark sticky-top">
                                                                <tr>
                                                                    <th style="width: 40px;"><input type="checkbox" id="selectAllSettlementTasks" class="form-check-input"></th>
                                                                    <th>{{ __('Task #') }}</th>
                                                                    <th>{{ __('Unpaid Debt') }}</th>
                                                                    <th>{{ __('Investor') }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="settlement-tasks-tbody">
                                                                <!-- AJAX will load tasks here -->
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif



                                        <!-- Maturity Time (Hidden by default) -->
                                        <div class="mb-4" id="maturity-time-group" style="display: none;">
                                            <label class="form-label" for="maturity">{{ __('Maturity Time') }}</label>
                                            <input type="datetime-local" name="maturity" class="form-control"
                                                id="trans_maturity">
                                            <span class="maturity-error text-danger text-error"></span>
                                        </div>

                                        <!-- Payment Method (Visible only if Debit is selected) -->
                                        <div class="mb-4" id="payment-method-group" style="display: none;">
                                            <label class="form-label" for="payment_method">{{ __('Payment Method') }}</label>
                                            <select name="payment_method" class="form-select" id="trans_payment_method">
                                                <option value="manual" selected>{{ __('Manual (Cash / Bank Transfer)') }}</option>
                                                @if ($data->user_type === 'driver')
                                                    <option value="hyperpay">{{ __('HyperPay Payout') }}</option>
                                                @endif
                                            </select>
                                            <span class="payment_method-error text-danger text-error"></span>
                                        </div>

                                        <!-- Bank Details (Shown only for HyperPay) -->
                                        @if ($data->user_type === 'driver')
                                            <div id="manual-hyperpay-bank-details" class="alert alert-info mt-3"
                                                style="display: none;">
                                                <h6 class="alert-heading fw-bold mb-2"><i
                                                        class="ti ti-building-bank me-1"></i>{{ __('Driver Bank Details') }}
                                                </h6>
                                                <div class="row small">
                                                    <div class="col-md-6 mb-1"><strong>{{ __('Beneficiary') }}:</strong>
                                                        <span>{{ $data->driver->beneficiary_name ?? 'N/A' }}</span>
                                                    </div>
                                                    <div class="col-md-6 mb-1"><strong>{{ __('Bank') }}:</strong>
                                                        <span>{{ $data->driver->bank_name ?? 'N/A' }}</span>
                                                    </div>
                                                    <div class="col-md-12 mb-1"><strong>{{ __('IBAN') }}:</strong> <span
                                                            class="font-monospace">{{ $data->driver->iban_number ?? 'N/A' }}</span>
                                                    </div>
                                                    <div class="col-md-6"><strong>{{ __('BIC/SWIFT') }}:</strong>
                                                        <span>{{ $data->driver->bic_code ?? 'N/A' }}</span>
                                                    </div>
                                                </div>
                                                @if (!$data->driver->iban_number || !$data->driver->bic_code || !$data->driver->beneficiary_name)
                                                    <div class="text-danger mt-2 fw-bold">
                                                        <i
                                                            class="ti ti-alert-triangle me-1"></i>{{ __('Incomplete bank details! Payout may fail.') }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

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
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">{{ __('Submit') }}</button>
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
        <div class="modal fade" id="paymentRequestModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Payment Request from Wallet: ') }} <span id="paymentRequestWalletId"
                                class="bg-info text-white rounded p-1 px-2"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Task Information Section -->
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">{{ __('Wallet Information') }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('Wallet ID') }}:</label>
                                            <span id="walletInfoId" class="text-primary fw-bold"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('Wallet Balance') }}:</label>
                                            <span id="walletInfoAmount" class="text-success fw-bold"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('Wallet Owner') }}:</label>
                                            <span id="walletInfoOwner"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('Phone') }}:</label>
                                            <span id="walletInfoOwnerPhone" class="text-muted"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">{{ __('Email') }}:</label>
                                            <span id="walletInfoOwnerEmail" class="text-muted"></span>
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
                                        <form id="paymentRequestForm">
                                            <input type="hidden" id="paymentRequestWalletIdInput" name="task_id">

                                            <div class="mb-3">
                                                <label class="form-label" for="requestedAmount">*
                                                    {{ __('Requested Amount') }}</label>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" class="form-control"
                                                        id="requestedAmount" name="requested_amount" required>
                                                    <span class="input-group-text">{{ __('SAR') }}</span>
                                                </div>
                                                <div class="form-text">
                                                    <small class="text-muted">{{ __('Available amount') }}: <span
                                                            id="maxAmount" class="text-primary fw-bold"></span>
                                                        ({{ __('You can enter a larger amount') }})</small>
                                                </div>
                                                <span class="requested_amount-error text-error"></span>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="paymentMethod">*
                                                    {{ __('Payment Method') }}</label>
                                                <select name="payment_method" id="paymentMethod" class="form-select"
                                                    required>
                                                    <option value="">{{ __('Select Payment Method') }}</option>
                                                    <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                                                    <option value="other">{{ __('Other Method') }}</option>
                                                </select>
                                                <span class="payment_method-error text-error"></span>
                                            </div>

                                            <!-- Bank Transfer Fields -->
                                            <div id="bankTransferFields" style="display: none;">
                                                <div class="mb-3">
                                                    <label class="form-label" for="bankName">{{ __('Bank Name') }}
                                                        ({{ __('Optional') }})</label>

                                                    <select name="bank_name" id="bankName" class="form-select">
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
                                                    <input type="text" class="form-control mt-2" id="customBankName"
                                                        name="custom_bank_name" placeholder="{{ __('Enter bank name') }}"
                                                        style="display: none;">
                                                    <span class="bank_name-error text-danger text-error"></span>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label" for="accountNumber">
                                                        {{ __('Account Number') }} ({{ __('Optional') }})</label>
                                                    <input type="text" class="form-control" id="accountNumber"
                                                        name="account_number" placeholder="1234567890" minlength="8">
                                                    <span class="account_number-error text-danger text-error"></span>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label" for="ibanNumber">
                                                        {{ __('IBAN Number') }} ({{ __('Optional') }})</label>
                                                    <input type="text" class="form-control" id="ibanNumber"
                                                        name="iban_number" placeholder="SA12 3456 7890 1234 5678 90"
                                                        maxlength="29">
                                                    <div class="form-text">
                                                        <small class="text-muted">{{ __('Format: SA + 22 digits') }}</small>
                                                    </div>
                                                    <span class="iban_number-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Other Payment Method Field -->
                                            <div id="otherPaymentField" style="display: none;">
                                                <div class="mb-3">
                                                    <label class="form-label" for="otherPaymentMethod">*
                                                        {{ __('Payment Method Details') }}</label>
                                                    <textarea class="form-control" id="otherPaymentMethod" name="other_payment_method" rows="3"
                                                        placeholder="{{ __('مثال: عهدة إلى الأخ فلان يسلمها إلى السائق محمد فلان') }}"></textarea>
                                                    <span class="other_payment_method-error text-error"></span>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="notes">{{ __('Notes') }}</label>
                                                <textarea type="text" class="form-control" id="notes" name="notes" placeholder="Notes" maxlength="1000"></textarea>
                                                <span class="notes-error text-danger text-error"></span>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label" for="selectedTasks">{{ __('Related Tasks') }}
                                                    ({{ __('Optional') }})</label>
                                                <select class="form-select" id="selectedTasks" name="selected_tasks[]"
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
                            id="generatePaymentRequest">{{ __('Generate Payment Request') }}</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan


    @can('view_payment_requests_logs')
        <!-- Payment Request Logs Section -->
        @if ($data->user_type === 'driver')
            <div class="card shadow-sm border-0 mt-4" id="payment-logs-section">
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
                            <button type="button" class="btn btn-outline-primary btn-sm" id="loadRefresh">
                                <i class="ti ti-refresh me-1"></i>
                                {{ __('Refresh') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Logs Container -->
                    <div id="payment-logs-container">
                        <!-- Loading state -->
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">{{ __('Loading') }}...</span>
                            </div>
                            <p class="text-muted mt-2">{{ __('Loading payment request logs') }}...</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endcan



@endsection
