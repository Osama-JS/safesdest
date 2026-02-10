@extends('layouts/layoutMaster')

@section('title', __('Wallet Reports'))

@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-style')
    @vite(['resources/css/app.css'])
    <style>
        /* Select2 Bootstrap 5 Compatibility */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #d0d7de;
            border-radius: 0.375rem;
            min-height: calc(2.25rem + 2px);
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.5;
            background-color: #fff;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .select2-container--bootstrap-5 .select2-selection:focus-within {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(2.25rem + 2px);
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding: 0;
            line-height: 1.5;
            color: #212529;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem);
            right: 0.75rem;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple {
            min-height: calc(2.25rem + 2px);
            padding: 0.125rem 0.75rem;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
            background-color: #0d6efd;
            border: 1px solid #0d6efd;
            border-radius: 0.25rem;
            color: #fff;
            font-size: 0.75rem;
            margin: 0.125rem 0.25rem 0.125rem 0;
            padding: 0.25rem 0.5rem;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff;
            margin-right: 0.25rem;
        }

        .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #f8f9fa;
        }

        .select2-dropdown {
            border: 1px solid #d0d7de;
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .select2-container--bootstrap-5 .select2-results__option {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background-color: #0d6efd;
            color: #fff;
        }

        .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            border: 1px solid #d0d7de;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        /* RTL Support */
        [dir="rtl"] .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            left: 0.75rem;
            right: auto;
        }

        [dir="rtl"] .select2-container--bootstrap-5 .select2-selection--single {
            padding: 0.375rem 0.75rem 0.375rem 2.25rem;
        }
    </style>
@endsection

@section('page-script')
    <script>
        const walletReportRoutes = {
            preview: "{{ route('admin.reports.wallet.preview') }}",
            generate: "{{ route('admin.reports.wallet.generate') }}",
            getOwners: "{{ route('admin.reports.wallet.get-owners') }}"
        };
    </script>
    @vite(['resources/js/admin/reports/wallet-reports.js'])
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1">
                                    <i class="ti ti-wallet me-2 text-primary"></i>
                                    {{ __('Wallet Reports') }}
                                </h4>
                                <p class="text-muted mb-0">
                                    {{ __('Generate detailed wallet reports for customers, drivers, and teams') }}</p>
                            </div>
                            <div class="d-flex gap-2">
                                <span class="badge bg-primary">{{ __('PDF Export') }}</span>
                                <span class="badge bg-success">{{ __('Detailed Analysis') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Generation Form -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-filter me-2"></i>
                            {{ __('Report Filters') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="walletReportForm">
                            @csrf
                            <div class="row">
                                <!-- Wallet Type Selection -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label required">{{ __('Wallet Type') }}</label>
                                    <select id="walletType" name="wallet_type" class="form-select" required>
                                        <option value="">{{ __('Select Wallet Type') }}</option>
                                        <option value="customer">{{ __('Customer Wallet') }}</option>
                                        <option value="driver">{{ __('Driver Wallet') }}</option>
                                        <option value="team">{{ __('Team Wallet') }}</option>
                                    </select>
                                </div>

                                <!-- Owner Selection -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label required">{{ __('Owner') }}</label>
                                    <select id="ownerId" name="owner_id" class="form-select" required disabled>
                                        <option value="">{{ __('Select Owner') }}</option>
                                    </select>
                                </div>

                                <!-- Date Range -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label required">{{ __('Date Range') }}</label>
                                    <input type="text" id="dateRange" class="form-control"
                                        placeholder="{{ __('Select date range') }}" required readonly>
                                    <input type="hidden" id="fromDate" name="from_date">
                                    <input type="hidden" id="toDate" name="to_date">
                                </div>
                            </div>

                            <div class="row">
                                <!-- Transaction Type Filter -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">{{ __('Transaction Type') }}</label>
                                    <select id="transactionType" name="transaction_type" class="form-select">
                                        <option value="">{{ __('All Types') }}</option>
                                        <option value="credit">{{ __('Credit (Incoming)') }}</option>
                                        <option value="debit">{{ __('Debit (Outgoing)') }}</option>
                                    </select>
                                </div>

                                <!-- Status Filter (for regular wallets only) -->
                                <div class="col-md-4 mb-3" id="statusFilterContainer">
                                    <label class="form-label">{{ __('Transaction Status') }}</label>
                                    <select id="status" name="status" class="form-select">
                                        <option value="">{{ __('All Statuses') }}</option>
                                        <option value="1">{{ __('Confirmed') }}</option>
                                        <option value="0">{{ __('Pending') }}</option>
                                    </select>
                                </div>

                                <!-- Action Buttons -->
                                <div class="col-md-4 mb-3 d-flex align-items-end gap-2">
                                    <button type="button" id="previewReportBtn" class="btn btn-info flex-fill">
                                        <i class="ti ti-eye me-2"></i>
                                        {{ __('Preview Data') }}
                                    </button>
                                    <button type="button" id="clearFiltersBtn" class="btn btn-outline-secondary">
                                        <i class="ti ti-refresh me-2"></i>
                                        {{ __('Clear') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Section -->
        <div class="row mt-4 preview-section" id="previewSection" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-table me-2"></i>
                            {{ __('Wallet Report Preview') }}
                        </h5>
                        <div class="export-buttons d-flex gap-2">
                            <button type="button" class="btn btn-primary" id="generatePdfBtn">
                                <i class="ti ti-file-type-pdf me-1"></i>
                                {{ __('Generate PDF Report') }}
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Wallet Summary -->
                        <div id="walletSummary" class="mb-4">
                            <!-- Summary will be populated by JavaScript -->
                        </div>

                        <!-- Transactions Table -->
                        <div id="previewTable">
                            <!-- Table will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                    </div>
                    <h6 class="mb-2">{{ __('Generating Report') }}</h6>
                    <p class="text-muted small mb-0">{{ __('Please wait while we prepare your wallet report...') }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
