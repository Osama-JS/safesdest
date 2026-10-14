@extends('layouts/layoutMaster')

@section('title', __('Customer Accounting Invoices'))

@section('customer-invoices-isactive', 'active')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/spinkit/spinkit.scss'
    ])
    <style>
        .badge-overdue {
            background-color: rgba(234, 84, 85, 0.16) !important;
            color: #ea5455 !important;
            border: 1px solid rgba(234, 84, 85, 0.4);
        }
        .badge-upcoming {
            background-color: rgba(0, 207, 232, 0.16) !important;
            color: #00cfe8 !important;
            border: 1px solid rgba(0, 207, 232, 0.4);
        }
        .badge-today {
            background-color: rgba(255, 159, 67, 0.16) !important;
            color: #ff9f43 !important;
            border: 1px solid rgba(255, 159, 67, 0.4);
        }
        .badge-expiring-soon {
            background-color: rgba(255, 159, 67, 0.2) !important;
            color: #e67e22 !important;
            border: 1px solid rgba(230, 126, 34, 0.45);
        }
        .badge-settled {
            background-color: rgba(40, 199, 111, 0.16) !important;
            color: #28c76f !important;
            border: 1px solid rgba(40, 199, 111, 0.4);
        }
        .card-stat-pulse {
            position: relative;
            overflow: hidden;
            transition: all 0.25s ease-in-out;
            cursor: pointer;
        }
        .card-stat-pulse:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }
        .card-stat-pulse::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
        }
        .pulse-danger::after { background-color: #ea5455; }
        .pulse-warning::after { background-color: #ff9f43; }
        .pulse-info::after { background-color: #00cfe8; }
        .pulse-success::after { background-color: #28c76f; }
        .pulse-primary::after { background-color: #7367f0; }

        .progress-sm {
            height: 6px !important;
        }

        .filter-pill-item {
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        .filter-pill-item:hover {
            opacity: 0.85;
            transform: translateY(-1px);
        }
    </style>
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/block-ui/block-ui.js'
    ])
@endsection

@section('page-script')
    <script>
        const allInvoicesDataUrl = "{{ route('admin.customer-invoices.all-data') }}";
        const invoicesStatsUrl   = "{{ route('admin.customer-invoices.statistics') }}";
        const invoiceBaseUrl     = "{{ url('admin/customer-invoices') }}";
        const canPayInvoices     = {{ auth()->user()->can('pay_customer_invoices') ? 'true' : 'false' }};
        const canApproveInvoices = {{ auth()->user()->can('approve_customer_invoices') ? 'true' : 'false' }};
        const canEditInvoices    = {{ auth()->user()->can('edit_customer_invoices') ? 'true' : 'false' }};
        const canCancelInvoices  = {{ auth()->user()->can('cancel_customer_invoices') ? 'true' : 'false' }};
    </script>
    @vite(['resources/js/admin/customer_invoices/index.js'])
@endsection

@section('content')
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-file-invoice text-primary me-2"></i> {{ __('Customer Accounting Invoices') }}
            </h4>
            <p class="text-muted mb-0">
                {{ __('Track, monitor and manage accounting invoices across all customers with maturity and overdue analysis.') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="btnRefreshStatsAndTable">
                <i class="ti ti-refresh me-1"></i> {{ __('Refresh') }}
            </button>
        </div>
    </div>

    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <!-- Alerts & Urgent Maturity Notices -->
    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <div id="invoicesAlertsContainer" class="mb-4" style="{{ ($stats['overdue_count'] > 0 || $stats['expiring_soon_count'] > 0 || $stats['due_today_count'] > 0) ? '' : 'display: none;' }}">
        <!-- 1. Overdue Invoices Alert -->
        <div id="alertOverdueContainer" class="alert alert-danger p-3 mb-2 border-danger shadow-xs" style="{{ ($stats['overdue_count'] > 0) ? '' : 'display: none;' }}">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="badge bg-danger rounded p-2 me-2">
                        <i class="ti ti-alert-triangle ti-md text-white"></i>
                    </span>
                    <div>
                        <h6 class="text-danger fw-bold mb-0">
                            {{ __('Urgent Alert: Overdue Invoices Requiring Immediate Follow-up') }}
                        </h6>
                        <div class="text-dark small mt-1">
                            {{ __('There are') }} <strong class="text-danger fs-6" id="alertOverdueCount">{{ $stats['overdue_count'] }}</strong> {{ __('invoices past their maturity date with a total overdue balance of') }}
                            <strong class="text-danger fs-6" id="alertOverdueAmount">{{ number_format($stats['overdue_amount'], 2) }} ر.س</strong>.
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-danger px-3 btn-filter-quick text-nowrap mt-2 mt-sm-0" data-due-status="overdue">
                    <i class="ti ti-filter me-1"></i> {{ __('View Overdue Invoices') }}
                </button>
            </div>
        </div>

        <!-- 2. Expiring Soon Alert (Next 7 Days) -->
        <div id="alertExpiringSoonContainer" class="alert alert-warning p-3 mb-2 border-warning shadow-xs" style="{{ ($stats['expiring_soon_count'] > 0) ? '' : 'display: none;' }}">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="badge bg-warning rounded p-2 me-2">
                        <i class="ti ti-hourglass-empty ti-md text-dark"></i>
                    </span>
                    <div>
                        <h6 class="text-warning-emphasis fw-bold mb-0">
                            {{ __('Maturity Notice: Invoices Expiring Soon (Next 7 Days)') }}
                        </h6>
                        <div class="text-dark small mt-1">
                            {{ __('There are') }} <strong class="text-warning-emphasis fs-6" id="alertExpiringCount">{{ $stats['expiring_soon_count'] }}</strong> {{ __('invoices approaching their maturity date within the next 7 days, amounting to') }}
                            <strong class="text-warning-emphasis fs-6" id="alertExpiringAmount">{{ number_format($stats['expiring_soon_amount'], 2) }} ر.س</strong>.
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-warning text-dark px-3 btn-filter-quick text-nowrap mt-2 mt-sm-0" data-due-status="expiring_soon">
                    <i class="ti ti-clock-check me-1"></i> {{ __('View Expiring Soon') }}
                </button>
            </div>
        </div>

        <!-- 3. Invoices Due Today Alert -->
        <div id="alertDueTodayContainer" class="alert alert-info p-3 mb-2 border-info shadow-xs" style="{{ ($stats['due_today_count'] > 0) ? '' : 'display: none;' }}">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="badge bg-info rounded p-2 me-2">
                        <i class="ti ti-bell-ringing ti-md text-white"></i>
                    </span>
                    <div>
                        <h6 class="text-info-emphasis fw-bold mb-0">
                            {{ __('Action Required: Invoices Due Today') }}
                        </h6>
                        <div class="text-dark small mt-1">
                            {{ __('There are') }} <strong class="text-info fs-6" id="alertDueTodayCount">{{ $stats['due_today_count'] }}</strong> {{ __('invoices due today with total amount of') }}
                            <strong class="text-info fs-6" id="alertDueTodayAmount">{{ number_format($stats['due_today_amount'], 2) }} ر.س</strong>.
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-info px-3 btn-filter-quick text-nowrap mt-2 mt-sm-0" data-due-status="due_today">
                    <i class="ti ti-calendar me-1"></i> {{ __('View Invoices Due Today') }}
                </button>
            </div>
        </div>
    </div>

    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <!-- Detailed Statistics Cards (5 Cards Grid) -->
    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <div class="row g-3 mb-3">
        <!-- 1. Total Invoiced -->
        <div class="col-sm-6 col-lg-4 col-xl">
            <div class="card card-stat-pulse pulse-primary h-100 card-filter-quick" data-due-status="all" data-status="all" title="{{ __('Click to show all invoices') }}">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-muted d-block small mb-1">{{ __('Total Invoiced') }}</span>
                            <h5 class="mb-1 text-primary fw-bold" id="statTotalAmount">
                                {{ number_format($stats['total_amount'], 2) }} <small class="fs-tiny">ر.س</small>
                            </h5>
                            <small class="text-muted">
                                <span class="badge bg-label-primary font-monospace" id="statTotalCount">{{ $stats['total_count'] }}</span> {{ __('Invoices') }}
                            </small>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="ti ti-file-invoice ti-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Overdue Invoices (المتأخرة) -->
        <div class="col-sm-6 col-lg-4 col-xl">
            <div class="card card-stat-pulse pulse-danger h-100 border-danger border-opacity-25 card-filter-quick" data-due-status="overdue" title="{{ __('Click to filter overdue invoices') }}">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-danger fw-bold d-block small mb-1">
                                <i class="ti ti-alert-triangle me-1"></i> {{ __('Overdue Invoices') }}
                            </span>
                            <h5 class="mb-1 text-danger fw-bold" id="statOverdueAmount">
                                {{ number_format($stats['overdue_amount'], 2) }} <small class="fs-tiny">ر.س</small>
                            </h5>
                            <small class="text-muted">
                                <span class="badge bg-label-danger font-monospace" id="statOverdueCount">{{ $stats['overdue_count'] }}</span> {{ __('Past due date') }}
                            </small>
                        </div>
                        <span class="badge bg-label-danger rounded p-2">
                            <i class="ti ti-clock-exclamation ti-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Expiring Soon (على وشك الاستحقاق / الانتهاء) -->
        <div class="col-sm-6 col-lg-4 col-xl">
            <div class="card card-stat-pulse pulse-warning h-100 border-warning border-opacity-25 card-filter-quick" data-due-status="expiring_soon" title="{{ __('Click to filter expiring soon invoices') }}">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-warning-emphasis fw-bold d-block small mb-1">
                                <i class="ti ti-hourglass-empty me-1"></i> {{ __('Expiring Soon') }}
                            </span>
                            <h5 class="mb-1 text-warning-emphasis fw-bold" id="statExpiringSoonAmount">
                                {{ number_format($stats['expiring_soon_amount'], 2) }} <small class="fs-tiny">ر.س</small>
                            </h5>
                            <small class="text-muted">
                                <span class="badge bg-label-warning font-monospace" id="statExpiringSoonCount">{{ $stats['expiring_soon_count'] }}</span> {{ __('Due in 7 days') }}
                            </small>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                            <i class="ti ti-clock-pause ti-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Upcoming Invoices (لم يأت وقت استحقاقها بعد) -->
        <div class="col-sm-6 col-lg-4 col-xl">
            <div class="card card-stat-pulse pulse-info h-100 card-filter-quick" data-due-status="upcoming" title="{{ __('Click to filter upcoming invoices') }}">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-info fw-bold d-block small mb-1">
                                <i class="ti ti-calendar-time me-1"></i> {{ __('Upcoming Invoices') }}
                            </span>
                            <h5 class="mb-1 text-info fw-bold" id="statUpcomingAmount">
                                {{ number_format($stats['upcoming_amount'], 2) }} <small class="fs-tiny">ر.س</small>
                            </h5>
                            <small class="text-muted">
                                <span class="badge bg-label-info font-monospace" id="statUpcomingCount">{{ $stats['upcoming_count'] }}</span> {{ __('Due in future') }}
                            </small>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="ti ti-calendar-event ti-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Collected / Settled (المسددة) -->
        <div class="col-sm-6 col-lg-4 col-xl">
            <div class="card card-stat-pulse pulse-success h-100 card-filter-quick" data-status="paid" data-due-status="all" title="{{ __('Click to filter paid invoices') }}">
                <div class="card-body p-3">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <span class="text-success fw-bold d-block small mb-1">
                                <i class="ti ti-check me-1"></i> {{ __('Paid & Closed') }}
                            </span>
                            <h5 class="mb-1 text-success fw-bold" id="statTotalPaid">
                                {{ number_format($stats['total_paid'], 2) }} <small class="fs-tiny">ر.س</small>
                            </h5>
                            <small class="text-muted">
                                <span class="badge bg-label-success font-monospace" id="statPaidCount">{{ $stats['paid_approved_count'] }}</span> {{ __('Fully settled') }}
                            </small>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="ti ti-circle-check ti-md"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <!-- Interactive Quick Counts Filter Bar -->
    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <div class="card mb-4 border-0 shadow-none bg-label-secondary bg-opacity-25">
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="fw-bold text-dark small me-1">
                        <i class="ti ti-chart-pie me-1 text-primary"></i> {{ __('Invoice Counts by Status:') }}
                    </span>
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill btn-filter-quick filter-pill-item" data-due-status="all" data-status="all">
                        {{ __('All') }} (<span id="pillTotalCount">{{ $stats['total_count'] }}</span>)
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-danger rounded-pill btn-filter-quick filter-pill-item" data-due-status="overdue" data-status="all">
                        <i class="ti ti-alert-triangle ti-xs me-1"></i> {{ __('Overdue') }} (<span id="pillOverdueCount">{{ $stats['overdue_count'] }}</span>)
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-warning rounded-pill btn-filter-quick filter-pill-item" data-due-status="expiring_soon" data-status="all">
                        <i class="ti ti-hourglass-empty ti-xs me-1"></i> {{ __('Expiring Soon') }} (<span id="pillExpiringSoonCount">{{ $stats['expiring_soon_count'] }}</span>)
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-info rounded-pill btn-filter-quick filter-pill-item" data-due-status="due_today" data-status="all">
                        <i class="ti ti-bell ti-xs me-1"></i> {{ __('Due Today') }} (<span id="pillDueTodayCount">{{ $stats['due_today_count'] }}</span>)
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill btn-filter-quick filter-pill-item" data-status="unpaid" data-due-status="all">
                        <i class="ti ti-clock ti-xs me-1"></i> {{ __('Pending Approval') }} (<span id="pillUnpaidCount">{{ $stats['unpaid_count'] }}</span>)
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-info rounded-pill btn-filter-quick filter-pill-item" data-status="approved" data-due-status="all">
                        <i class="ti ti-check me-1 ti-xs"></i> {{ __('Approved (Awaiting Payment)') }} (<span id="pillApprovedCount">{{ $stats['approved_count'] }}</span>)
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-success rounded-pill btn-filter-quick filter-pill-item" data-status="paid" data-due-status="all">
                        <i class="ti ti-circle-check ti-xs me-1"></i> {{ __('Paid & Closed') }} (<span id="pillPaidCount">{{ $stats['paid_approved_count'] }}</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">
                <i class="ti ti-filter me-1 text-primary"></i> {{ __('Filters and Search') }}
            </h6>
            <button type="button" class="btn btn-sm btn-link text-decoration-none p-0" id="btnResetFilters">
                <i class="ti ti-rotate me-1"></i> {{ __('Reset Filters') }}
            </button>
        </div>
        <div class="card-body py-3">
            <div class="row g-3">
                <!-- Customer Filter -->
                <div class="col-md-3 col-sm-6">
                    <label class="form-label" for="filter_customer_id">{{ __('Customer') }}</label>
                    <select class="form-select select2" id="filter_customer_id" data-allow-clear="true">
                        <option value="all">{{ __('All Customers') }}</option>
                        @foreach ($customers as $cust)
                            <option value="{{ $cust->id }}">
                                {{ $cust->name }} {{ $cust->phone ? '('.$cust->phone.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Maturity Filter (حالة الاستحقاق) -->
                <div class="col-md-3 col-sm-6">
                    <label class="form-label" for="filter_due_status">{{ __('Maturity Status') }}</label>
                    <select class="form-select" id="filter_due_status">
                        <option value="all">{{ __('All Maturity Statuses') }}</option>
                        <option value="overdue" class="text-danger fw-bold">⚠️ {{ __('Overdue Invoices (Past Due)') }}</option>
                        <option value="expiring_soon" class="text-warning fw-bold">⏳ {{ __('Expiring Soon (Next 7 Days)') }}</option>
                        <option value="upcoming" class="text-info">📅 {{ __('Upcoming Invoices (Not Due Yet)') }}</option>
                        <option value="due_today" class="text-warning">🔔 {{ __('Due Today') }}</option>
                        <option value="settled" class="text-success">✅ {{ __('Paid & Closed Invoices') }}</option>
                    </select>
                </div>

                <!-- Invoice Status Filter (الحالة المحاسبية) -->
                <div class="col-md-2 col-sm-6">
                    <label class="form-label" for="filter_status">{{ __('Invoice Status') }}</label>
                    <select class="form-select" id="filter_status">
                        <option value="all">{{ __('All Statuses') }}</option>
                        <option value="unpaid">⏳ {{ __('Pending Approval') }}</option>
                        <option value="approved">📝 {{ __('Approved (Awaiting Payment)') }}</option>
                        <option value="paid">✅ {{ __('Paid & Closed') }}</option>
                        <option value="cancelled">❌ {{ __('Cancelled') }}</option>
                    </select>
                </div>

                <!-- Due Date From -->
                <div class="col-md-2 col-sm-6">
                    <label class="form-label" for="filter_due_date_from">{{ __('Due Date (From)') }}</label>
                    <input type="date" class="form-control" id="filter_due_date_from">
                </div>

                <!-- Due Date To -->
                <div class="col-md-2 col-sm-6">
                    <label class="form-label" for="filter_due_date_to">{{ __('Due Date (To)') }}</label>
                    <input type="date" class="form-control" id="filter_due_date_to">
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold">
                <i class="ti ti-list me-1 text-primary"></i> {{ __('Invoices List') }}
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small" id="tableFilterSummary"></span>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 w-100" id="allCustomerInvoicesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>{{ __('Invoice Number') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Issue Date') }}</th>
                            <th>{{ __('Due Date & Maturity') }}</th>
                            <th>{{ __('Total (SAR)') }}</th>
                            <th>{{ __('Paid / Remaining') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Attachment') }}</th>
                            <th class="text-end" style="width: 100px;">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <!-- Modal: View Customer Invoice Details -->
    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <div class="modal fade" id="viewCustomerInvoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header py-3 bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="modal-title fw-bold text-primary mb-0" id="viewInvoiceTitle">
                            <i class="ti ti-file-invoice me-1"></i> {{ __('Invoice Details') }}
                        </h5>
                        <span id="viewInvoiceStatusBadge"></span>
                        <span id="viewInvoiceMaturityBadge"></span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body py-3">
                    <!-- Customer & Basic Info -->
                    <div class="row g-3 mb-3 p-3 bg-lighter rounded border">
                        <div class="col-md-4 col-sm-6">
                            <small class="text-muted d-block">{{ __('Customer Name') }}</small>
                            <span class="fw-bold fs-6 text-dark" id="viewInvoiceCustomer">-</span>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <small class="text-muted d-block">{{ __('Invoice Number') }}</small>
                            <span class="fw-bold fs-6 text-primary" id="viewInvoiceNumber">-</span>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <small class="text-muted d-block">{{ __('Accounting Ref') }}</small>
                            <span class="fw-bold fs-6 text-dark" id="viewInvoiceRef">-</span>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <small class="text-muted d-block">{{ __('Issue Date') }}</small>
                            <span class="fw-bold fs-6 text-dark" id="viewInvoiceIssueDate">-</span>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <small class="text-muted d-block">{{ __('Due Date') }}</small>
                            <span class="fw-bold fs-6" id="viewInvoiceDueDate">-</span>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <small class="text-muted d-block">{{ __('Wallet') }}</small>
                            <a href="#" target="_blank" class="fw-bold text-primary" id="viewInvoiceWalletLink">
                                <i class="ti ti-wallet me-1"></i> <span id="viewInvoiceWalletText">-</span>
                            </a>
                        </div>
                    </div>

                    <!-- Financial Balances -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-4 col-4">
                            <div class="p-3 border rounded text-center bg-light">
                                <small class="text-muted d-block mb-1">{{ __('Total Amount') }}</small>
                                <span class="fw-bold text-dark fs-5" id="viewInvoiceTotal">0.00</span> <small>ر.س</small>
                            </div>
                        </div>
                        <div class="col-md-4 col-4">
                            <div class="p-3 border rounded text-center bg-light">
                                <small class="text-muted d-block mb-1">{{ __('Paid Amount') }}</small>
                                <span class="fw-bold text-success fs-5" id="viewInvoicePaid">0.00</span> <small>ر.س</small>
                            </div>
                        </div>
                        <div class="col-md-4 col-4">
                            <div class="p-3 border rounded text-center bg-light">
                                <small class="text-muted d-block mb-1">{{ __('Remaining Amount') }}</small>
                                <span class="fw-bold text-danger fs-5" id="viewInvoiceRemaining">0.00</span> <small>ر.س</small>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Progress Bar -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>{{ __('Payment Progress') }}</span>
                            <span id="viewInvoicePercentage">0%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" id="viewInvoiceProgressBar" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Notes & Attachment -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-8" id="viewInvoiceNotesBox" style="display: none;">
                            <div class="p-2 border rounded">
                                <small class="text-muted fw-bold d-block">{{ __('Notes') }}:</small>
                                <span id="viewInvoiceNotes" class="text-secondary" style="white-space: pre-line;"></span>
                            </div>
                        </div>
                        <div class="col-md-4" id="viewInvoiceAttachmentBox" style="display: none;">
                            <div class="p-2 border rounded text-center">
                                <small class="text-muted fw-bold d-block mb-1">{{ __('Attachment File') }}:</small>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="viewInvoiceAttachmentBtn">
                                    <i class="ti ti-eye me-1"></i> {{ __('View Attachment') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Linked Items Table -->
                    <h6 class="fw-bold text-secondary mb-2">
                        <i class="ti ti-list me-1"></i> {{ __('Linked Debit Transactions') }}
                    </h6>
                    <div class="table-responsive border rounded" style="max-height: 220px; overflow-y: auto;">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="bg-white border-bottom sticky-top">
                                <tr>
                                    <th style="background-color: #fff;">#</th>
                                    <th style="background-color: #fff;">{{ __('Sequence') }}</th>
                                    <th style="background-color: #fff;">{{ __('Task #') }}</th>
                                    <th style="background-color: #fff;">{{ __('Delivery Number') }}</th>
                                    <th style="background-color: #fff;">{{ __('Description') }}</th>
                                    <th style="background-color: #fff;" class="text-end">{{ __('Amount (SAR)') }}</th>
                                </tr>
                            </thead>
                            <tbody id="viewInvoiceItemsBody"></tbody>
                        </table>
                    </div>

                    <!-- Audit Info -->
                    <div class="d-flex justify-content-between mt-3 text-muted small border-top pt-2">
                        <div>{{ __('Created By') }}: <span id="viewInvoiceCreator">-</span> (<span id="viewInvoiceCreatedAt">-</span>)</div>
                        <div id="viewInvoiceApproverBox" style="display: none;">{{ __('Approved By') }}: <span id="viewInvoiceApprover">-</span></div>
                    </div>
                </div>
                <div class="modal-footer py-2 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-sm btn-danger" id="viewInvoiceQuickForceEditBtn" style="display: none;">
                        <i class="ti ti-shield-bolt me-1"></i> {{ __('Force Edit') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-success" id="viewInvoiceQuickPayBtn" style="display: none;">
                        <i class="ti ti-cash me-1"></i> {{ __('Register Payment') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" id="viewInvoiceQuickApproveBtn" style="display: none;">
                        <i class="ti ti-check me-1"></i> {{ __('Final Approval') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <!-- Modal: Pay Customer Invoice -->
    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <div class="modal fade" id="payCustomerInvoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header py-3 bg-success bg-opacity-10">
                    <h5 class="modal-title fw-bold text-success mb-0">
                        <i class="ti ti-cash me-1"></i> {{ __('Register Invoice Payment') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <form id="formPayCustomerInvoice">
                    @csrf
                    <input type="hidden" id="pay_invoice_id">

                    <div class="modal-body py-3">
                        <div class="p-3 rounded border mb-3 bg-light">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">{{ __('Invoice Number') }}:</span>
                                <span class="fw-bold" id="payInvoiceNumber">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">{{ __('Customer') }}:</span>
                                <span class="fw-bold" id="payInvoiceCustomer">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">{{ __('Total Amount') }}:</span>
                                <span class="fw-bold" id="payInvoiceTotal">0.00</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">{{ __('Remaining Balance') }}:</span>
                                <span class="fw-bold text-danger fs-6" id="payInvoiceRemaining">0.00</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold text-success" for="pay_amount">* {{ __('Payment Amount (SAR)') }}</label>
                            <input type="number" step="0.01" min="0.01" class="form-control form-control-lg" id="pay_amount" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="pay_date">{{ __('Payment Date') }}</label>
                            <input type="date" class="form-control" id="pay_date" name="payment_date" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="pay_notes">{{ __('Payment Notes / Reference') }}</label>
                            <textarea class="form-control" id="pay_notes" name="notes" rows="2" placeholder="{{ __('e.g. Bank transfer, receipt number...') }}"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-success" id="btnSubmitPayment">
                            <i class="ti ti-check me-1"></i> {{ __('Confirm Payment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <!-- Modal: Edit Customer Invoice -->
    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <div class="modal fade" id="editCustomerInvoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header py-3 bg-light">
                    <h5 class="modal-title fw-bold text-primary mb-0">
                        <i class="ti ti-edit me-1"></i> {{ __('Edit Invoice Details') }} - <span id="editInvoiceNumberTitle"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <form id="formEditCustomerInvoice" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="edit_invoice_id">
                    <input type="hidden" id="edit_is_force_edit" name="is_force_edit" value="0">

                    <div class="modal-body py-3">
                        <!-- Force Edit Warning Banner (shown only when editing approved invoice) -->
                        <div id="editForceEditBanner" class="alert alert-danger py-2 mb-3 border-danger shadow-xs" style="display: none;">
                            <div class="d-flex align-items-center">
                                <i class="ti ti-shield-bolt ti-md me-2 text-danger"></i>
                                <div>
                                    <strong class="d-block text-danger">{{ __('Force Edit on Approved Invoice') }}</strong>
                                    <small class="text-dark">{{ __('Warning: This invoice has already been approved. Any changes will be audited and permanently logged in Activity Logs under Force Edit.') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info py-2 mb-3" id="editNormalInfoAlert">
                            <i class="ti ti-info-circle me-1"></i>
                            {{ __('Note: Updating the due date will automatically propagate to all linked wallet transactions.') }}
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Force Edit Justification Reason & Admin Password Confirmation -->
                            <div class="col-md-12" id="editForceEditReasonContainer" style="display: none;">
                                <div class="row g-2">
                                    <div class="col-md-7">
                                        <label class="form-label text-danger fw-bold" for="edit_force_edit_reason">
                                            <i class="ti ti-alert-triangle me-1"></i> * {{ __('Force Edit Justification / Reason') }}
                                        </label>
                                        <textarea class="form-control border-danger" id="edit_force_edit_reason" name="force_edit_reason" rows="2" placeholder="{{ __('State the reason for modifying this approved invoice...') }}"></textarea>
                                        <small class="text-muted">{{ __('This reason will be recorded in the system audit logs alongside your identity.') }}</small>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label text-danger fw-bold" for="edit_force_edit_password">
                                            <i class="ti ti-lock me-1"></i> * {{ __('Admin Password Confirmation') }}
                                        </label>
                                        <input type="password" class="form-control border-danger" id="edit_force_edit_password" name="password" autocomplete="current-password" placeholder="{{ __('Enter your account password...') }}">
                                        <small class="text-muted">{{ __('Required to authorize force modification') }}</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="edit_accounting_reference_no">{{ __('Accounting Reference Number') }}</label>
                                <input type="text" class="form-control" id="edit_accounting_reference_no" name="accounting_reference_no">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-danger fw-bold" for="edit_due_date">* {{ __('Due Date (Maturity)') }}</label>
                                <input type="date" class="form-control border-primary" id="edit_due_date" name="due_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_issue_date">{{ __('Issue Date') }}</label>
                                <input type="date" class="form-control" id="edit_issue_date" name="issue_date">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="edit_attachment">{{ __('Replace Invoice Attachment File') }}</label>
                                <input type="file" class="form-control" id="edit_attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                <div id="editCurrentAttachmentPreview" class="mt-1 small"></div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label" for="edit_notes">{{ __('Notes') }}</label>
                                <textarea class="form-control" id="edit_notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- Linked Transactions Box -->
                        <div class="card border mb-2">
                            <div class="card-header bg-white border-bottom py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h6 class="mb-0 fw-semibold text-secondary">
                                    <i class="ti ti-list-check me-1"></i> {{ __('Linked Debit Transactions') }}
                                </h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleAddMoreTransactions">
                                    <i class="ti ti-plus me-1"></i> {{ __('Add More Uninvoiced Transactions') }}
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                                    <table class="table table-hover table-sm align-middle mb-0">
                                        <thead class="bg-white border-bottom sticky-top">
                                            <tr>
                                                <th style="width: 40px; background-color: #fff;" class="text-center">#</th>
                                                <th style="background-color: #fff;">{{ __('Sequence / ID') }}</th>
                                                <th style="background-color: #fff;">{{ __('Task #') }}</th>
                                                <th style="background-color: #fff;">{{ __('Delivery Number') }}</th>
                                                <th style="background-color: #fff;">{{ __('Description') }}</th>
                                                <th style="background-color: #fff;" class="text-end">{{ __('Amount (SAR)') }}</th>
                                                <th style="background-color: #fff;" class="text-center">{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="editInvoiceTransactionsBody"></tbody>
                                    </table>
                                </div>

                                <!-- Uninvoiced available -->
                                <div id="editAvailableUninvoicedSection" class="border-top p-2" style="display: none; background-color: #fff;">
                                    <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                        <small class="fw-bold text-primary">
                                            <i class="ti ti-plus me-1"></i> {{ __('Select Transactions to Add to Invoice') }}
                                        </small>
                                    </div>
                                    <div class="table-responsive border rounded" style="max-height: 160px; overflow-y: auto;">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="bg-white border-bottom sticky-top">
                                                <tr>
                                                    <th style="width: 40px; background-color: #fff;" class="text-center">
                                                        <input type="checkbox" class="form-check-input" id="checkAllAvailableForEdit">
                                                    </th>
                                                    <th style="background-color: #fff;">{{ __('Sequence') }}</th>
                                                    <th style="background-color: #fff;">{{ __('Task #') }}</th>
                                                    <th style="background-color: #fff;">{{ __('Delivery Number') }}</th>
                                                    <th style="background-color: #fff;">{{ __('Description') }}</th>
                                                    <th style="background-color: #fff;" class="text-end">{{ __('Amount (SAR)') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="editAvailableUninvoicedBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitEditInvoice">
                            <i class="ti ti-check me-1"></i> {{ __('Save Changes') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <!-- Modal: Preview Attachment -->
    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <div class="modal fade" id="previewAttachmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header py-2 bg-light">
                    <h5 class="modal-title fw-bold" id="previewAttachmentTitle">
                        <i class="ti ti-file-text me-1"></i> {{ __('Attachment Preview') }}
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="#" target="_blank" class="btn btn-sm btn-outline-primary" id="btnDownloadAttachment">
                            <i class="ti ti-download me-1"></i> {{ __('Download') }}
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                    </div>
                </div>
                <div class="modal-body p-0 text-center bg-dark" style="min-height: 500px;">
                    <div id="attachmentPreviewContainer" class="w-100 h-100 d-flex justify-content-center align-items-center" style="min-height: 500px;">
                        <!-- Embed or Image dynamically rendered -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('page-script')
    <script>
        const canViewCustomerInvoices = {{ auth()->user()->can('view_customer_invoices') ? 'true' : 'false' }};
        const canCreateCustomerInvoices = {{ auth()->user()->can('create_customer_invoices') ? 'true' : 'false' }};
        const canEditCustomerInvoices = {{ auth()->user()->can('edit_customer_invoices') ? 'true' : 'false' }};
        const canForceEditCustomerInvoices = {{ auth()->user()->can('force_edit_customer_invoices') ? 'true' : 'false' }};
        const canPayCustomerInvoices = {{ auth()->user()->can('pay_customer_invoices') ? 'true' : 'false' }};
        const canApproveCustomerInvoices = {{ auth()->user()->can('approve_customer_invoices') ? 'true' : 'false' }};
        const canCancelCustomerInvoices = {{ auth()->user()->can('cancel_customer_invoices') ? 'true' : 'false' }};
    </script>
    @vite(['resources/js/admin/customer_invoices/index.js'])
@endsection

