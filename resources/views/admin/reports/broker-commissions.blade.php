@extends('layouts/layoutMaster')

@section('title', __('Brokers Commissions Report'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/select2/select2.scss'
    ])
@endsection

@section('page-style')
    @vite(['resources/css/app.css'])
    <style>
        /* ================= Modern Select2 Styling ================= */
        .select2-container .select2-selection {
            border: 1px solid #dbdade !important;
            border-radius: 0.375rem !important;
            min-height: 42px !important;
            font-size: 0.9rem !important;
            line-height: 1.5 !important;
            background-color: #fff !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
        }

        .select2-container--open .select2-selection,
        .select2-container .select2-selection:focus {
            border-color: #7367f0 !important;
            box-shadow: 0 0 0 0.2rem rgba(115, 103, 240, 0.15) !important;
            outline: none !important;
        }

        /* Single Select */
        .select2-container .select2-selection--single {
            height: 42px !important;
            display: flex !important;
            align-items: center !important;
            padding: 0 0.85rem !important;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            padding: 0 !important;
            color: #4b465c !important;
            line-height: normal !important;
        }

        [dir="rtl"] .select2-container .select2-selection--single .select2-selection__arrow {
            left: 10px !important;
            right: auto !important;
            height: 100% !important;
            top: 0 !important;
        }

        /* Multiple Select */
        .select2-container .select2-selection--multiple {
            min-height: 42px !important;
            padding: 4px 6px !important;
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: 4px !important;
        }

        .select2-container .select2-selection--multiple .select2-selection__rendered {
            display: contents !important;
        }

        .select2-container .select2-selection--multiple .select2-selection__choice {
            background: linear-gradient(135deg, #7367f0 0%, #9055fd 100%) !important;
            color: #ffffff !important;
            border: none !important;
            border-radius: 20px !important;
            padding: 3px 12px 3px 10px !important;
            font-size: 0.82rem !important;
            font-weight: 500 !important;
            margin: 2px !important;
            display: inline-flex !important;
            align-items: center !important;
            box-shadow: 0 2px 4px rgba(115, 103, 240, 0.25) !important;
        }

        .select2-container .select2-selection--multiple .select2-selection__choice__remove {
            color: #ffffff !important;
            margin-right: 6px !important;
            margin-left: 0 !important;
            font-size: 14px !important;
            border: none !important;
            background: transparent !important;
            opacity: 0.8 !important;
            transition: opacity 0.2s ease !important;
        }

        .select2-container .select2-selection--multiple .select2-selection__choice__remove:hover {
            opacity: 1 !important;
            color: #ffd2d2 !important;
        }

        /* Dropdown Styling */
        .select2-dropdown {
            border: 1px solid #dbdade !important;
            border-radius: 0.5rem !important;
            box-shadow: 0 0.5rem 1.25rem rgba(75, 70, 92, 0.15) !important;
            z-index: 1060 !important;
            overflow: hidden !important;
        }

        .select2-search--dropdown {
            padding: 8px 10px !important;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #dbdade !important;
            border-radius: 0.375rem !important;
            padding: 6px 10px !important;
            font-size: 0.875rem !important;
            outline: none !important;
        }

        .select2-search--dropdown .select2-search__field:focus {
            border-color: #7367f0 !important;
        }

        .select2-results__option {
            padding: 8px 12px !important;
            font-size: 0.875rem !important;
            color: #4b465c !important;
            transition: background 0.15s ease !important;
        }

        .select2-results__option--highlighted {
            background-color: #f1f0ff !important;
            color: #7367f0 !important;
            font-weight: 600 !important;
        }

        .select2-results__option[aria-selected="true"] {
            background-color: #e9ecef !important;
            color: #212529 !important;
        }

        /* ================= KPI & UI Elements ================= */
        .kpi-card {
            border: none;
            border-radius: 0.5rem;
            transition: all 0.25s ease;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .source-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35em 0.75em;
            font-size: 0.78em;
            font-weight: 700;
            line-height: 1;
            border-radius: 20px;
        }

        .source-badge.customer {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .source-badge.driver {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .source-badge.task {
            background-color: #cff4fc;
            color: #055160;
            border: 1px solid #b6effb;
        }

        .source-badge.investor {
            background-color: #f3e8ff;
            color: #6f42c1;
            border: 1px solid #e9d5ff;
        }

        .mode-toggle-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f5 100%);
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
        }

        .date-input-group .input-group-text {
            background-color: #f8f9fa;
            border-color: #dbdade;
            color: #6c757d;
        }

        .empty-placeholder {
            padding: 3.5rem 1rem;
            text-align: center;
            color: #a1a5b7;
        }

        .empty-placeholder i {
            font-size: 3.5rem;
            color: #c4c5d6;
            margin-bottom: 0.75rem;
            display: block;
        }
    </style>
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/select2/select2.js'
    ])
@endsection

@section('content')
    <!-- Title Card -->
    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">
                <i class="tf-icons ti ti-briefcase me-2 fs-3 text-white bg-warning rounded p-1"></i>
                {{ __('Platform Reports') }} | {{ __('Brokers Commissions Report') }}
            </h5>
            <p class="mb-0 text-muted">
                {{ __('View and analyze brokers commissions from customers, drivers, and tasks') }}
            </p>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="ti ti-filter me-2 text-primary"></i>
                {{ __('Report Filters and Options') }}
            </h5>
        </div>
        <div class="card-body">
            <form id="brokerReportForm" method="POST" action="{{ route('admin.reports.brokers.generate') }}" target="_blank">
                @csrf
                <input type="hidden" name="export_type" id="export_type" value="excel">

                <!-- Report Mode Selector -->
                <div class="mode-toggle-card mb-4">
                    <label class="form-label fw-bold mb-2">
                        <i class="ti ti-layout-grid me-1 text-primary"></i>
                        {{ __('Report Display Type') }} <span class="text-danger">*</span>
                    </label>
                    <div class="d-flex flex-wrap gap-4">
                        <div class="form-check custom-option custom-option-basic">
                            <label class="form-check-label custom-option-content" for="mode_transactions">
                                <input name="report_mode" class="form-check-input" type="radio" value="transactions" id="mode_transactions" checked />
                                <span class="custom-option-header pb-0">
                                    <span class="h6 mb-0 fw-bold">{{ __('Recorded Wallet Transactions (Detailed)') }}</span>
                                </span>
                                <small class="text-muted d-block mt-1">{{ __('Fetch actual credit transactions recorded in brokers wallets with commission source and task details') }}</small>
                            </label>
                        </div>
                        <div class="form-check custom-option custom-option-basic">
                            <label class="form-check-label custom-option-content" for="mode_aggregated">
                                <input name="report_mode" class="form-check-input" type="radio" value="aggregated" id="mode_aggregated" />
                                <span class="custom-option-header pb-0">
                                    <span class="h6 mb-0 fw-bold">{{ __('Brokers Commissions Aggregates (Summary)') }}</span>
                                </span>
                                <small class="text-muted d-block mt-1">{{ __('Display summary row per broker with customer commissions, driver commissions, task commissions, and wallet balance') }}</small>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <!-- Broker Select (Select2 Multiple) -->
                    <div class="col-lg-5 col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="broker_ids" class="form-label mb-0 fw-bold">
                                {{ __('Brokers Included in Report') }} <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="chkSelectAllBrokers" style="cursor: pointer;">
                                    <label class="form-check-label small fw-bold text-primary" for="chkSelectAllBrokers" style="cursor: pointer;">
                                        {{ __('Select All') }}
                                    </label>
                                </div>
                                <span class="text-muted">|</span>
                                <a href="javascript:void(0);" class="small text-danger" id="btnClearBrokers">{{ __('Clear') }}</a>
                            </div>
                        </div>
                        <select class="form-select" id="broker_ids" name="broker_ids[]" multiple="multiple" data-placeholder="{{ __('-- Select Brokers or Enable Select All --') }}">
                            <option value="all">{{ __('★ Select All Brokers ★') }}</option>
                            @foreach ($brokers as $broker)
                                <option value="{{ $broker->id }}">
                                    {{ $broker->name }} @if($broker->phone) ({{ $broker->phone }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">{{ __('Must select at least one broker or click Select All to preview report') }}</small>
                    </div>

                    <!-- Commission Sources Filter (Select2 Multiple) -->
                    <div class="col-lg-3 col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="commission_sources" class="form-label mb-0 fw-bold">
                                {{ __('Commission Sources') }}
                            </label>
                            <div class="d-flex align-items-center gap-2">
                                <a href="javascript:void(0);" class="small text-primary" id="btnSelectAllSources">{{ __('Select All') }}</a>
                                <span class="text-muted">|</span>
                                <a href="javascript:void(0);" class="small text-danger" id="btnClearSources">{{ __('Clear') }}</a>
                            </div>
                        </div>
                        <select class="form-select" id="commission_sources" name="commission_sources[]" multiple="multiple" data-placeholder="{{ __('-- Select Commission Sources --') }}">
                            <option value="all">{{ __('★ All Sources ★') }}</option>
                            <option value="customer" selected>{{ __('Customer Linking Commissions') }}</option>
                            <option value="driver" selected>{{ __('Driver Linking Commissions') }}</option>
                            <option value="task" selected>{{ __('Direct Task Linking Commissions') }}</option>
                            <option value="investor" selected>{{ __('Investor Linking Commissions') }}</option>
                        </select>
                        <small class="text-muted d-block mt-1">{{ __('Accepts selecting multiple sources or leaving all selected') }}</small>
                    </div>

                    <!-- Date From (Separate Input) -->
                    <div class="col-lg-2 col-md-3">
                        <label for="date_from" class="form-label fw-bold">
                            {{ __('From Date') }}
                        </label>
                        <div class="input-group date-input-group">
                            <span class="input-group-text"><i class="ti ti-calendar"></i></span>
                            <input type="date" class="form-control" id="date_from" name="date_from" placeholder="YYYY-MM-DD">
                        </div>
                    </div>

                    <!-- Date To (Separate Input) -->
                    <div class="col-lg-2 col-md-3">
                        <label for="date_to" class="form-label fw-bold">
                            {{ __('To Date') }}
                        </label>
                        <div class="input-group date-input-group">
                            <span class="input-group-text"><i class="ti ti-calendar"></i></span>
                            <input type="date" class="form-control" id="date_to" name="date_to" placeholder="YYYY-MM-DD">
                        </div>
                    </div>
                </div>

                <!-- Action Buttons & Date Reset -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary px-4" id="previewBtn">
                            <i class="ti ti-eye me-1"></i>
                            {{ __('Preview Report') }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="resetBtn">
                            <i class="ti ti-refresh me-1"></i>
                            {{ __('Reset Filters') }}
                        </button>
                    </div>
                    <div class="d-flex gap-1 align-items-center mt-2 mt-md-0">
                        <span class="small text-muted me-1">{{ __('Quick Ranges:') }}</span>
                        <button type="button" class="btn btn-xs btn-outline-primary" id="btnDateThisMonth">{{ __('This Month') }}</button>
                        <button type="button" class="btn btn-xs btn-outline-primary" id="btnDateLast3Months">{{ __('Last 3 Months') }}</button>
                        <button type="button" class="btn btn-xs btn-outline-primary" id="btnDateThisYear">{{ __('This Year') }}</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" id="btnClearDates">{{ __('Clear Dates') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary KPI Cards (Initially 0) -->
    <div class="row g-3 mb-4" id="summarySection">
        <!-- Total Commissions -->
        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card bg-label-success h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="d-block text-muted small fw-semibold">{{ __('Grand Total Commissions') }}</span>
                        <h4 class="card-title mb-0 fw-bold mt-1 text-success" id="kpi_total_commissions">0.00 {{ __('SAR') }}</h4>
                    </div>
                    <div class="kpi-icon bg-success text-white">
                        <i class="ti ti-cash"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Commissions -->
        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card bg-label-warning h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="d-block text-muted small fw-semibold">{{ __('Commissions from Customers') }}</span>
                        <h4 class="card-title mb-0 fw-bold mt-1 text-warning" id="kpi_customer_commissions">0.00 {{ __('SAR') }}</h4>
                    </div>
                    <div class="kpi-icon bg-warning text-white">
                        <i class="ti ti-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Driver Commissions -->
        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card bg-label-info h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="d-block text-muted small fw-semibold">{{ __('Commissions from Drivers') }}</span>
                        <h4 class="card-title mb-0 fw-bold mt-1 text-info" id="kpi_driver_commissions">0.00 {{ __('SAR') }}</h4>
                    </div>
                    <div class="kpi-icon bg-info text-white">
                        <i class="ti ti-steering-wheel"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Direct Commissions -->
        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card bg-label-primary h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="d-block text-muted small fw-semibold">{{ __('Commissions from Tasks') }}</span>
                        <h4 class="card-title mb-0 fw-bold mt-1 text-primary" id="kpi_task_commissions">0.00 {{ __('SAR') }}</h4>
                    </div>
                    <div class="kpi-icon bg-primary text-white">
                        <i class="ti ti-checklist"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Investor Commissions -->
        <div class="col-xl-3 col-md-6">
            <div class="card kpi-card bg-label-secondary h-100" style="background-color: rgba(111, 66, 193, 0.08) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="d-block text-muted small fw-semibold">{{ __('Commissions from Investors') }}</span>
                        <h4 class="card-title mb-0 fw-bold mt-1" style="color: #6f42c1;" id="kpi_investor_commissions">0.00 {{ __('SAR') }}</h4>
                    </div>
                    <div class="kpi-icon text-white" style="background-color: #6f42c1;">
                        <i class="ti ti-chart-pie"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Table Section -->
    <div class="card" id="previewCard">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom py-3">
            <div>
                <h5 class="card-title mb-0">
                    <i class="ti ti-table me-2 text-primary"></i>
                    <span id="previewTitle">{{ __('Report Preview') }}</span>
                </h5>
                <small class="text-muted" id="previewSubtitle">{{ __('Please select brokers and click Preview Report to view results') }}</small>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success" id="exportExcelBtn" disabled>
                    <i class="ti ti-file-spreadsheet me-1"></i>
                    {{ __('Export to Excel') }}
                </button>
                <button type="button" class="btn btn-danger" id="exportPdfBtn" disabled>
                    <i class="ti ti-file-text me-1"></i>
                    {{ __('Export to PDF / Print') }}
                </button>
            </div>
        </div>
        <div class="card-body pt-3">
            <!-- Loading Indicator -->
            <div id="tableLoading" class="text-center py-5" style="display: none;">
                <div class="spinner-border text-primary mb-2" role="status">
                    <span class="visually-hidden">{{ __('Loading...') }}</span>
                </div>
                <div class="text-muted fw-semibold">{{ __('Extracting report data and calculating commissions...') }}</div>
            </div>

            <!-- Table Container -->
            <div class="table-responsive" id="tableContainer">
                <table class="table table-hover table-bordered w-100" id="brokerReportTable">
                    <thead>
                        <tr id="tableHeaders">
                            <th class="text-center" style="width: 70px;">{{ __('Tx #') }}</th>
                            <th>{{ __('Broker Name') }}</th>
                            <th>{{ __('Source Type') }}</th>
                            <th>{{ __('Source Entity / Name') }}</th>
                            <th>{{ __('Task #') }}</th>
                            <th>{{ __('Commission Amount') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Date & Time') }}</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="8">
                                <div class="empty-placeholder">
                                    <i class="ti ti-user-search"></i>
                                    <h6 class="fw-bold text-secondary mb-1">{{ __('Report awaiting broker selection') }}</h6>
                                    <p class="small text-muted mb-0">{{ __('Select broker from the field above or enable Select All then click Preview Report') }}</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        // Exported translations for JavaScript usage
        window.reportTranslations = {
            previewSummaryTitle: "{{ __('Preview Brokers Commissions Summary') }}",
            previewSummarySubtitle: "{{ __('Summary of total commissions per broker categorized by commission source') }}",
            previewTransactionsTitle: "{{ __('Preview Wallet Commission Transactions') }}",
            previewTransactionsSubtitle: "{{ __('Details of credit commission transactions recorded in brokers wallets') }}",
            brokerSelectionRequired: "{{ __('Broker Selection Required') }}",
            brokerSelectionRequiredMsg: "{{ __('Please select at least one broker or click Select All to preview report') }}",
            invalidDateRange: "{{ __('Invalid Date Range') }}",
            invalidDateRangeMsg: "{{ __('Start date cannot be greater than end date') }}",
            error: "{{ __('Error') }}",
            unableToLoad: "{{ __('Unable to load report data') }}",
            exportExcelReq: "{{ __('Please select brokers first to export report to Excel') }}",
            exportPdfReq: "{{ __('Please select brokers first to export report to PDF') }}",
            ok: "{{ __('OK') }}",
            currency: "{{ __('SAR') }}",
            linkedCustomer: "{{ __('Linked with Customer') }}",
            linkedDriver: "{{ __('Linked with Driver') }}",
            linkedTask: "{{ __('Linked with Task') }}",
            linkedInvestor: "{{ __('Linked with Investor') }}",
            invComm: "{{ __('Investor Commissions') }}",
            txNum: "{{ __('Tx #') }}",
            brokerName: "{{ __('Broker Name') }}",
            sourceType: "{{ __('Source Type') }}",
            sourceName: "{{ __('Source Entity / Name') }}",
            taskId: "{{ __('Task #') }}",
            amount: "{{ __('Commission Amount') }}",
            description: "{{ __('Description') }}",
            dateTime: "{{ __('Date & Time') }}",
            phone: "{{ __('Phone') }}",
            custComm: "{{ __('Customer Commissions') }}",
            drivComm: "{{ __('Driver Commissions') }}",
            taskComm: "{{ __('Task Commissions') }}",
            totalComm: "{{ __('Total Commissions') }}",
            txCount: "{{ __('Transactions Count') }}",
            walletBal: "{{ __('Current Wallet Balance') }}",
            emptyPlaceholderTitle: "{{ __('Report awaiting broker selection') }}",
            emptyPlaceholderSubtitle: "{{ __('Select broker from the field above or enable Select All then click Preview Report') }}"
        };

        document.addEventListener('DOMContentLoaded', function () {
            const T = window.reportTranslations;

            // 1. Initialize Modern Select2 on all select elements
            if ($.fn.select2) {
                // Broker Select (Multiple)
                $('#broker_ids').select2({
                    dir: '{{ app()->getLocale() == "ar" ? "rtl" : "ltr" }}',
                    width: '100%',
                    allowClear: true,
                    placeholder: '{{ __("-- Select Brokers or Enable Select All --") }}',
                    closeOnSelect: false
                });

                // Commission Sources Select (Multiple)
                $('#commission_sources').select2({
                    dir: '{{ app()->getLocale() == "ar" ? "rtl" : "ltr" }}',
                    width: '100%',
                    allowClear: true,
                    placeholder: '{{ __("-- Select Commission Sources --") }}',
                    closeOnSelect: false
                });
            }

            // Quick select / clear for Commission Sources
            $('#btnSelectAllSources').on('click', function () {
                $('#commission_sources').val(['customer', 'driver', 'task']).trigger('change');
            });
            $('#btnClearSources').on('click', function () {
                $('#commission_sources').val(null).trigger('change');
            });
            $('#commission_sources').on('select2:select', function (e) {
                if (e.params.data.id === 'all') {
                    $('#commission_sources').val(['customer', 'driver', 'task']).trigger('change');
                }
            });

            // 2. Select All / Deselect Logic for Brokers
            let isSelectingAll = false;

            function selectAllBrokers() {
                isSelectingAll = true;
                const allValues = [];
                $('#broker_ids option').each(function () {
                    const val = $(this).val();
                    if (val && val !== 'all') {
                        allValues.push(val);
                    }
                });
                $('#broker_ids').val(allValues).trigger('change');
                $('#chkSelectAllBrokers').prop('checked', true);
                isSelectingAll = false;
            }

            function clearBrokers() {
                $('#broker_ids').val(null).trigger('change');
                $('#chkSelectAllBrokers').prop('checked', false);
            }

            // Listen to checkbox toggle
            $('#chkSelectAllBrokers').on('change', function () {
                if ($(this).is(':checked')) {
                    selectAllBrokers();
                } else {
                    clearBrokers();
                }
            });

            // Listen to clear link
            $('#btnClearBrokers').on('click', function () {
                clearBrokers();
            });

            // Listen to selecting "all" inside Select2 dropdown
            $('#broker_ids').on('select2:select', function (e) {
                if (e.params.data.id === 'all') {
                    selectAllBrokers();
                } else {
                    checkIfAllSelected();
                }
            });

            $('#broker_ids').on('select2:unselect', function (e) {
                if (!isSelectingAll) {
                    $('#chkSelectAllBrokers').prop('checked', false);
                }
            });

            function checkIfAllSelected() {
                const selected = $('#broker_ids').val() || [];
                const totalOptions = $('#broker_ids option').length - 1; // exclude 'all' option
                if (selected.length >= totalOptions && totalOptions > 0) {
                    $('#chkSelectAllBrokers').prop('checked', true);
                } else {
                    $('#chkSelectAllBrokers').prop('checked', false);
                }
            }

            // 3. Quick Date Presets
            $('#btnDateThisMonth').on('click', function() {
                const now = new Date();
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                $('#date_from').val(formatDate(firstDay));
                $('#date_to').val(formatDate(lastDay));
            });

            $('#btnDateLast3Months').on('click', function() {
                const now = new Date();
                const threeMonthsAgo = new Date(now.getFullYear(), now.getMonth() - 2, 1);
                const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                $('#date_from').val(formatDate(threeMonthsAgo));
                $('#date_to').val(formatDate(lastDay));
            });

            $('#btnDateThisYear').on('click', function() {
                const now = new Date();
                const firstDay = new Date(now.getFullYear(), 0, 1);
                const lastDay = new Date(now.getFullYear(), 11, 31);
                $('#date_from').val(formatDate(firstDay));
                $('#date_to').val(formatDate(lastDay));
            });

            $('#btnClearDates').on('click', function() {
                $('#date_from').val('');
                $('#date_to').val('');
            });

            function formatDate(d) {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }

            // 4. DataTables Instance
            let dataTable = null;

            function getCurrentMode() {
                return $('input[name="report_mode"]:checked').val() || 'transactions';
            }

            // Mode switch listener
            $('input[name="report_mode"]').on('change', function() {
                const mode = getCurrentMode();
                if (mode === 'aggregated') {
                    $('#previewTitle').text(T.previewSummaryTitle);
                    $('#previewSubtitle').text(T.previewSummarySubtitle);
                } else {
                    $('#previewTitle').text(T.previewTransactionsTitle);
                    $('#previewSubtitle').text(T.previewTransactionsSubtitle);
                }

                // If brokers are already selected, refresh preview
                const selectedBrokers = $('#broker_ids').val() || [];
                if (selectedBrokers.length > 0) {
                    loadReportPreview();
                }
            });

            function formatCurrency(val) {
                const num = parseFloat(val) || 0;
                return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + T.currency;
            }

            // 5. Load Report Preview
            function loadReportPreview() {
                const mode = getCurrentMode();
                const brokerIds = $('#broker_ids').val() || [];
                const sources = $('#commission_sources').val() || [];
                const dateFrom = $('#date_from').val();
                const dateTo = $('#date_to').val();

                // Validate brokers selected
                if (!brokerIds || brokerIds.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: T.brokerSelectionRequired,
                        text: T.brokerSelectionRequiredMsg,
                        confirmButtonText: T.ok,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                    return;
                }

                // Validate date range if both provided
                if (dateFrom && dateTo && dateFrom > dateTo) {
                    Swal.fire({
                        icon: 'warning',
                        title: T.invalidDateRange,
                        text: T.invalidDateRangeMsg,
                        confirmButtonText: T.ok,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                    return;
                }

                $('#tableLoading').show();
                $('#tableContainer').hide();

                $.ajax({
                    url: '{{ route("admin.reports.brokers.preview") }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        report_mode: mode,
                        broker_ids: brokerIds,
                        commission_sources: sources,
                        date_from: dateFrom,
                        date_to: dateTo
                    },
                    success: function (res) {
                        $('#tableLoading').hide();
                        $('#tableContainer').show();

                        if (!res.success) {
                            Swal.fire({
                                icon: 'error',
                                title: T.error,
                                text: res.message || T.unableToLoad,
                                confirmButtonText: T.ok
                            });
                            return;
                        }

                        // Enable Export Buttons
                        $('#exportExcelBtn').prop('disabled', false);
                        $('#exportPdfBtn').prop('disabled', false);

                        // Update KPIs
                        const s = res.summary || {};
                        if (mode === 'aggregated') {
                            $('#kpi_total_commissions').text(formatCurrency(s.grand_total_commissions));
                            $('#kpi_customer_commissions').text(formatCurrency(s.total_customer_commissions));
                            $('#kpi_driver_commissions').text(formatCurrency(s.total_driver_commissions));
                            $('#kpi_task_commissions').text(formatCurrency(s.total_task_commissions));
                            $('#kpi_investor_commissions').text(formatCurrency(s.total_investor_commissions));
                        } else {
                            $('#kpi_total_commissions').text(formatCurrency(s.total_commissions));
                            $('#kpi_customer_commissions').text(formatCurrency(s.customer_commissions_total));
                            $('#kpi_driver_commissions').text(formatCurrency(s.driver_commissions_total));
                            $('#kpi_task_commissions').text(formatCurrency(s.task_commissions_total));
                            $('#kpi_investor_commissions').text(formatCurrency(s.investor_commissions_total));
                        }

                        renderTable(mode, res.data || []);
                    },
                    error: function (xhr) {
                        $('#tableLoading').hide();
                        $('#tableContainer').show();
                        Swal.fire({
                            icon: 'error',
                            title: T.error,
                            text: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : T.unableToLoad,
                            confirmButtonText: T.ok
                        });
                    }
                });
            }

            // 6. Render Table
            function renderTable(mode, data) {
                if (dataTable) {
                    dataTable.destroy();
                    $('#brokerReportTable').empty();
                }

                if (mode === 'aggregated') {
                    // Mode 2: Aggregated
                    const thead = `
                        <thead>
                            <tr class="table-light">
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>${T.brokerName}</th>
                                <th>${T.phone}</th>
                                <th>${T.custComm}</th>
                                <th>${T.drivComm}</th>
                                <th>${T.taskComm}</th>
                                <th>${T.invComm}</th>
                                <th>${T.totalComm}</th>
                                <th>${T.txCount}</th>
                                <th>${T.walletBal}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    `;
                    $('#brokerReportTable').html(thead);

                    dataTable = $('#brokerReportTable').DataTable({
                        data: data,
                        language: {
                            url: '{{ app()->getLocale() == "ar" ? "//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json" : "" }}'
                        },
                        columns: [
                            {
                                data: null,
                                className: 'text-center',
                                render: function (d, t, r, meta) {
                                    return meta.row + 1;
                                }
                            },
                            {
                                data: 'broker_name',
                                render: function (data) {
                                    return `<span class="fw-bold text-dark">${data || '-'}</span>`;
                                }
                            },
                            {
                                data: 'broker_phone',
                                render: function(d) { return d || '-'; }
                            },
                            {
                                data: 'customer_commissions',
                                render: function(d) {
                                    return `<span class="text-warning fw-bold">${formatCurrency(d)}</span>`;
                                }
                            },
                            {
                                data: 'driver_commissions',
                                render: function(d) {
                                    return `<span class="text-info fw-bold">${formatCurrency(d)}</span>`;
                                }
                            },
                            {
                                data: 'task_commissions',
                                render: function(d) {
                                    return `<span class="text-primary fw-bold">${formatCurrency(d)}</span>`;
                                }
                            },
                            {
                                data: 'investor_commissions',
                                render: function(d) {
                                    return `<span class="fw-bold" style="color: #6f42c1;">${formatCurrency(d)}</span>`;
                                }
                            },
                            {
                                data: 'total_commissions',
                                render: function(d) {
                                    return `<span class="badge bg-success fs-6">${formatCurrency(d)}</span>`;
                                }
                            },
                            {
                                data: 'transactions_count',
                                className: 'text-center',
                                render: function(d) {
                                    return `<span class="badge bg-label-secondary">${d || 0}</span>`;
                                }
                            },
                            {
                                data: 'wallet_balance',
                                render: function(d) {
                                    return `<span class="fw-bold">${formatCurrency(d)}</span>`;
                                }
                            }
                        ],
                        order: [[6, 'desc']],
                        responsive: true,
                        pageLength: 25
                    });

                } else {
                    // Mode 1: Detailed Transactions
                    const thead = `
                        <thead>
                            <tr class="table-light">
                                <th class="text-center" style="width: 70px;">${T.txNum}</th>
                                <th>${T.brokerName}</th>
                                <th>${T.sourceType}</th>
                                <th>${T.sourceName}</th>
                                <th>${T.taskId}</th>
                                <th>${T.amount}</th>
                                <th>${T.description}</th>
                                <th>${T.dateTime}</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    `;
                    $('#brokerReportTable').html(thead);

                    dataTable = $('#brokerReportTable').DataTable({
                        data: data,
                        language: {
                            url: '{{ app()->getLocale() == "ar" ? "//cdn.datatables.net/plug-ins/1.13.6/i18n/ar.json" : "" }}'
                        },
                        columns: [
                            {
                                data: null,
                                className: 'text-center',
                                render: function(d) {
                                    return '#' + (d.sequence || d.id);
                                }
                            },
                            {
                                data: 'broker_name',
                                render: function(d) {
                                    return `<span class="fw-bold text-dark">${d || '-'}</span>`;
                                }
                            },
                            {
                                data: 'source_type',
                                render: function(d) {
                                    if (d === 'customer') {
                                        return `<span class="source-badge customer"><i class="ti ti-user me-1"></i>${T.linkedCustomer}</span>`;
                                    } else if (d === 'driver') {
                                        return `<span class="source-badge driver"><i class="ti ti-steering-wheel me-1"></i>${T.linkedDriver}</span>`;
                                    } else if (d === 'investor') {
                                        return `<span class="source-badge investor"><i class="ti ti-chart-pie me-1"></i>${T.linkedInvestor}</span>`;
                                    } else {
                                        return `<span class="source-badge task"><i class="ti ti-checklist me-1"></i>${T.linkedTask}</span>`;
                                    }
                                }
                            },
                            {
                                data: 'source_name',
                                render: function(d) { return d || '-'; }
                            },
                            {
                                data: 'task_id',
                                className: 'text-center',
                                render: function(d) {
                                    return d && d !== '-' ? `<span class="badge bg-label-primary">#${d}</span>` : '-';
                                }
                            },
                            {
                                data: 'amount',
                                render: function(d) {
                                    return `<span class="text-success fw-bold">${formatCurrency(d)}</span>`;
                                }
                            },
                            {
                                data: 'description',
                                render: function(d) {
                                    return `<small class="text-muted">${d || '-'}</small>`;
                                }
                            },
                            {
                                data: 'created_at',
                                render: function(d) {
                                    return `<span class="small">${d || '-'}</span>`;
                                }
                            }
                        ],
                        order: [[0, 'desc']],
                        responsive: true,
                        pageLength: 25
                    });
                }
            }

            // 7. Export Handlers
            $('#exportExcelBtn').on('click', function() {
                const brokerIds = $('#broker_ids').val() || [];
                if (!brokerIds || brokerIds.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: T.brokerSelectionRequired,
                        text: T.exportExcelReq,
                        confirmButtonText: T.ok
                    });
                    return;
                }
                $('#export_type').val('excel');
                $('#brokerReportForm').submit();
            });

            $('#exportPdfBtn').on('click', function() {
                const brokerIds = $('#broker_ids').val() || [];
                if (!brokerIds || brokerIds.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: T.brokerSelectionRequired,
                        text: T.exportPdfReq,
                        confirmButtonText: T.ok
                    });
                    return;
                }
                $('#export_type').val('pdf');
                $('#brokerReportForm').submit();
            });

            // 8. Buttons
            $('#previewBtn').on('click', function() {
                loadReportPreview();
            });

            $('#resetBtn').on('click', function() {
                $('#mode_transactions').prop('checked', true);
                clearBrokers();
                $('#commission_sources').val(['customer', 'driver', 'task']).trigger('change');
                $('#date_from').val('');
                $('#date_to').val('');

                // Reset KPIs to 0
                $('#kpi_total_commissions').text('0.00 ' + T.currency);
                $('#kpi_customer_commissions').text('0.00 ' + T.currency);
                $('#kpi_driver_commissions').text('0.00 ' + T.currency);
                $('#kpi_task_commissions').text('0.00 ' + T.currency);

                // Disable export buttons
                $('#exportExcelBtn').prop('disabled', true);
                $('#exportPdfBtn').prop('disabled', true);

                // Destroy datatable & restore empty placeholder
                if (dataTable) {
                    dataTable.destroy();
                    dataTable = null;
                }
                $('#brokerReportTable').html(`
                    <thead>
                        <tr id="tableHeaders">
                            <th class="text-center" style="width: 70px;">${T.txNum}</th>
                            <th>${T.brokerName}</th>
                            <th>${T.sourceType}</th>
                            <th>${T.sourceName}</th>
                            <th>${T.taskId}</th>
                            <th>${T.amount}</th>
                            <th>${T.description}</th>
                            <th>${T.dateTime}</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <tr>
                            <td colspan="8">
                                <div class="empty-placeholder">
                                    <i class="ti ti-user-search"></i>
                                    <h6 class="fw-bold text-secondary mb-1">${T.emptyPlaceholderTitle}</h6>
                                    <p class="small text-muted mb-0">${T.emptyPlaceholderSubtitle}</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                `);
            });
        });
    </script>
@endsection
