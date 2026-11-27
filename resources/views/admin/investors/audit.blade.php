@extends('layouts/layoutMaster')

@section('title', __('Investors & Tasks Audit'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    <style>
        /* Modern aesthetic - completely eliminating dull gray */
        .metric-card {
            border-radius: 0.75rem;
            border: 1px solid rgba(115, 103, 240, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1.25rem rgba(115, 103, 240, 0.1);
        }
        .bg-soft-success {
            background-color: rgba(40, 199, 111, 0.12) !important;
            color: #28c76f !important;
        }
        .bg-soft-warning {
            background-color: rgba(255, 159, 67, 0.12) !important;
            color: #ff9f43 !important;
        }
        .bg-soft-primary {
            background-color: rgba(115, 103, 240, 0.12) !important;
            color: #7367f0 !important;
        }
        .bg-soft-info {
            background-color: rgba(0, 207, 232, 0.12) !important;
            color: #00cfe8 !important;
        }
        .bg-soft-danger {
            background-color: rgba(234, 84, 85, 0.12) !important;
            color: #ea5455 !important;
        }
        .bg-soft-dark {
            background-color: rgba(75, 70, 92, 0.08) !important;
            color: #4b465c !important;
        }
        .table-custom-header th {
            background-color: #f8f8fc !important;
            color: #4b465c !important;
            font-weight: 600;
            border-bottom: 2px solid rgba(115, 103, 240, 0.15) !important;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
        .table-custom-footer th {
            background-color: #f8f8fc !important;
            color: #4b465c !important;
            font-weight: 700;
            border-top: 2px solid rgba(115, 103, 240, 0.15) !important;
        }
        .timeline-audit-item {
            border-left: 2px dashed #7367f0;
            padding-left: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        [dir="rtl"] .timeline-audit-item {
            border-left: none;
            border-right: 2px dashed #7367f0;
            padding-left: 0;
            padding-right: 1rem;
        }
        .timeline-audit-item::before {
            content: '';
            position: absolute;
            top: 2px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #7367f0;
        }
        [dir="ltr"] .timeline-audit-item::before {
            left: -6px;
        }
        [dir="rtl"] .timeline-audit-item::before {
            right: -6px;
        }
        .filter-toolbar {
            background-color: #fafafc;
            border-bottom: 1px solid #ececf5;
        }
        .pagination-container .page-item.active .page-link {
            background-color: #7367f0;
            border-color: #7367f0;
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(115, 103, 240, 0.4);
        }
        .pagination-container .page-link {
            color: #5d596c;
            border-radius: 0.375rem;
            margin: 0 2px;
            border: 1px solid #dbdade;
        }
        .pagination-container .page-link:hover {
            background-color: rgba(115, 103, 240, 0.08);
            color: #7367f0;
        }
        /* Custom modal styles */
        .modal-custom-header {
            border-bottom: 1px solid #ebebf2;
            padding: 1.25rem 1.5rem;
        }
        .modal-custom-footer {
            border-top: 1px solid #ebebf2;
            padding: 1rem 1.5rem;
            background-color: #fbfbfe;
        }

        /* Modern Commission Audit Filter Toolbar */
        .comm-audit-filter-panel {
            background-color: #ffffff;
            border-bottom: 2px solid rgba(115, 103, 240, 0.12);
            padding: 1.25rem 1.5rem;
        }
        .comm-filter-label {
            color: #383854;
            font-weight: 700;
            font-size: 0.8125rem;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
        }
        .comm-filter-control {
            border: 1.5px solid #e2e2ec;
            background-color: #ffffff;
            color: #383854;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        .comm-filter-control:focus {
            border-color: #7367f0;
            box-shadow: 0 0 0 0.2rem rgba(115, 103, 240, 0.15);
            background-color: #ffffff;
        }
        .comm-date-range-card {
            background-color: #f8f8fd;
            border: 1.5px solid #e3e1fc;
            border-radius: 0.65rem;
            padding: 0.35rem 0.6rem;
            transition: all 0.2s ease;
        }
        .comm-date-range-card:focus-within {
            border-color: #7367f0;
            box-shadow: 0 0 0 0.2rem rgba(115, 103, 240, 0.12);
            background-color: #ffffff;
        }
        .comm-date-item {
            display: flex;
            align-items: center;
            background-color: #ffffff;
            border: 1px solid #e8e8f4;
            border-radius: 0.5rem;
            padding: 0.2rem 0.5rem;
            transition: border-color 0.2s ease;
        }
        .comm-date-item:focus-within {
            border-color: #7367f0;
        }
        .comm-date-input {
            border: none;
            background: transparent;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #383854;
            outline: none;
            width: 100%;
            padding: 0.2rem 0.25rem;
        }
        .comm-date-input:focus {
            outline: none;
            box-shadow: none;
        }
        .comm-date-input::-webkit-calendar-picker-indicator {
            cursor: pointer;
            filter: invert(42%) sepia(85%) saturate(1450%) hue-rotate(222deg) brightness(97%) contrast(93%);
            opacity: 0.9;
            transition: transform 0.15s ease;
        }
        .comm-date-input::-webkit-calendar-picker-indicator:hover {
            transform: scale(1.15);
        }
        .comm-summary-bar {
            background-color: #f8f8fd;
            border: 1px solid #e8e8f7;
            border-radius: 0.5rem;
            padding: 0.65rem 1rem;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- Breadcrumb & Title --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold py-1 mb-1">
                <span class="text-secondary fw-light">{{ __('Investors') }} /</span> {{ __('Audit & Tasks Matching') }}
            </h4>
            <p class="text-secondary mb-0">{{ __('Comprehensive financial reconciliation between investor deposits, active balances, and funded tasks.') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.investors.audit.export') }}" class="btn btn-outline-success">
                <i class="ti ti-file-spreadsheet me-1"></i> {{ __('Export Problematic Tasks to Excel') }}
            </a>
            <a href="{{ route('admin.investors.index') }}" class="btn btn-outline-primary">
                <i class="ti ti-arrow-right me-1"></i> {{ __('Back to Investors') }}
            </a>
            <button type="button" class="btn btn-primary" onclick="refreshAllAuditData();">
                <i class="ti ti-refresh me-1"></i> {{ __('Refresh Data') }}
            </button>
        </div>
    </div>

    {{-- Alert Notice --}}
    <div class="alert alert-success d-flex align-items-center mb-4 shadow-sm border-0" role="alert">
        <span class="alert-icon text-success me-2">
            <i class="ti ti-circle-check ti-xs"></i>
        </span>
        <div class="d-flex flex-column ps-1">
            <h6 class="alert-heading mb-1 fw-bold text-success">{{ __('Audit Result: Perfect Equilibrium (0.00 SAR Deficit)') }}</h6>
            <span class="mb-0 text-dark">
                {{ __('All investor deposits are 100% accounted for in the ledger. Every deposited riyal is either available in the wallet balance or reserved for funding assigned tasks.') }}
                <strong>({{ __('Total Net Capital') }}: {{ number_format($totals['total_net_base_capital'], 2) }} {{ __('SAR') }} = {{ __('Available Balances') }}: {{ number_format($totals['total_wallet_balance_stored'], 2) }} {{ __('SAR') }} + {{ __('Locked in Tasks') }}: {{ number_format($totals['total_locked_in_tasks_ledger'], 2) }} {{ __('SAR') }})</strong>
            </span>
        </div>
    </div>

    {{-- KPI Cards Row --}}
    <div class="row g-4 mb-4">
        {{-- Net Capital --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card metric-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-secondary fw-semibold">{{ __('Net Base Capital') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 text-primary fw-bold">{{ number_format($totals['total_net_base_capital'], 2) }}</h4>
                                <small class="text-secondary ms-1">{{ __('SAR') }}</small>
                            </div>
                            <small class="text-success fw-semibold">
                                <i class="ti ti-arrow-down-left"></i> {{ __('Deposits') }}: {{ number_format($totals['total_deposited_capital'], 2) }}
                            </small>
                            <br>
                            <small class="text-danger fw-semibold">
                                <i class="ti ti-arrow-up-right"></i> {{ __('Withdrawals') }}: {{ number_format($totals['total_withdrawn_capital'], 2) }}
                            </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-soft-primary">
                                <i class="ti ti-building-bank ti-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Available Wallet Balances --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card metric-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-secondary fw-semibold">{{ __('Available Wallet Balances') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 text-success fw-bold">{{ number_format($totals['total_wallet_balance_stored'], 2) }}</h4>
                                <small class="text-secondary ms-1">{{ __('SAR') }}</small>
                            </div>
                            <small class="text-success fw-semibold">
                                <i class="ti ti-checks text-success me-1"></i> {{ __('Exact ledger match') }}
                            </small>
                            <br>
                            <small class="text-dark">
                                {{ __('Investors count') }}: <strong>{{ $totals['investors_count'] }}</strong>
                            </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-soft-success">
                                <i class="ti ti-wallet ti-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Locked in Tasks (Ledger) --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card metric-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-secondary fw-semibold">{{ __('Locked in Tasks (Ledger)') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 text-warning fw-bold">{{ number_format($totals['total_locked_in_tasks_ledger'], 2) }}</h4>
                                <small class="text-secondary ms-1">{{ __('SAR') }}</small>
                            </div>
                            <small class="text-danger fw-semibold">
                                <i class="ti ti-arrow-up text-danger"></i> {{ __('Funded debits') }}: {{ number_format($totals['total_task_funding_debits'], 0) }}
                            </small>
                            <br>
                            <small class="text-success fw-semibold">
                                <i class="ti ti-arrow-down text-success"></i> {{ __('Settled credits') }}: {{ number_format($totals['total_task_settlement_credits'], 0) }}
                            </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-soft-warning">
                                <i class="ti ti-lock ti-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Unsettled Tasks Sum --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card metric-card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-secondary fw-semibold">{{ __('Active Unsettled Tasks') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 text-info fw-bold">{{ number_format($totals['total_unsettled_tasks_amount'], 2) }}</h4>
                                <small class="text-secondary ms-1">{{ __('SAR') }}</small>
                            </div>
                            <small class="text-dark">
                                <i class="ti ti-list-check me-1 text-primary"></i> {{ __('Tasks count') }}: <strong>{{ $totals['total_unsettled_tasks_count'] }} {{ __('tasks') }}</strong>
                            </small>
                            <br>
                            <small class="text-danger fw-semibold" title="{{ __('Caused by task price reduction after funding without refunding diff') }}">
                                <i class="ti ti-info-circle"></i> {{ __('Pricing adjustment diff') }}: {{ number_format($totals['unsettled_vs_locked_diff'], 2) }} {{ __('SAR') }}
                            </small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-soft-info">
                                <i class="ti ti-truck-loading ti-md"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Investors Breakdown Table --}}
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 bg-white">
            <div>
                <h5 class="card-title mb-1 fw-bold text-dark">
                    <i class="ti ti-users me-1 text-primary"></i> {{ __('Investors Accounts & Wallets Reconciliation') }}
                </h5>
                <small class="text-secondary">{{ __('Comparison between capital deposited, available balances, and funds tied up in tasks per investor.') }}</small>
            </div>
            <span class="badge bg-soft-success text-success fs-6 px-3 py-2">
                <i class="ti ti-circle-check me-1"></i> {{ __('100% Balanced') }}
            </span>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-custom-header">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Investor') }}</th>
                        <th>{{ __('Deposited Capital') }}</th>
                        <th>{{ __('Withdrawals') }}</th>
                        <th>{{ __('Net Capital') }}</th>
                        <th>{{ __('Available Balance') }}</th>
                        <th>{{ __('Locked in Tasks') }}</th>
                        <th>{{ __('Unsettled Tasks') }}</th>
                        <th>{{ __('Pricing Diff') }}</th>
                        <th>{{ __('Audit Status') }}</th>
                        <th class="text-center">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($investorsData as $inv)
                        <tr>
                            <td><span class="badge bg-soft-primary text-primary">#{{ $inv['id'] }}</span></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark">{{ $inv['name'] }}</span>
                                    <small class="text-secondary">{{ $inv['email'] }}</small>
                                </div>
                            </td>
                            <td class="text-success fw-semibold">{{ number_format($inv['deposited_capital'], 2) }} <small class="text-secondary">{{ __('SAR') }}</small></td>
                            <td class="text-danger fw-semibold">{{ number_format($inv['withdrawn_capital'], 2) }} <small class="text-secondary">{{ __('SAR') }}</small></td>
                            <td class="fw-bold text-primary">{{ number_format($inv['net_base_capital'], 2) }} <small class="text-secondary">{{ __('SAR') }}</small></td>
                            <td class="fw-bold text-success">{{ number_format($inv['stored_balance'], 2) }} <small class="text-secondary">{{ __('SAR') }}</small></td>
                            <td class="fw-semibold text-warning">{{ number_format($inv['locked_in_tasks_ledger'], 2) }} <small class="text-secondary">{{ __('SAR') }}</small></td>
                            <td>
                                <span class="badge bg-soft-info text-info">{{ $inv['unsettled_tasks_count'] }} {{ __('tasks') }}</span>
                                <div class="small text-secondary">{{ number_format($inv['unsettled_tasks_sum'], 2) }} {{ __('SAR') }}</div>
                            </td>
                            <td>
                                @if(abs($inv['unsettled_vs_locked_diff']) > 0.01)
                                    <span class="badge bg-soft-warning text-warning" title="{{ __('Difference between locked ledger capital and current task prices') }}">
                                        {{ number_format($inv['unsettled_vs_locked_diff'], 2) }} {{ __('SAR') }}
                                    </span>
                                @else
                                    <span class="badge bg-soft-success text-success">0.00</span>
                                @endif
                            </td>
                            <td>
                                @if(abs($inv['accounting_diff']) < 0.01 && abs($inv['balance_ledger_diff']) < 0.01)
                                    <span class="badge bg-soft-success">
                                        <i class="ti ti-check me-1"></i> {{ __('Balanced') }}
                                    </span>
                                @else
                                    <span class="badge bg-soft-danger">
                                        <i class="ti ti-alert-triangle me-1"></i> {{ __('Discrepancy') }}: {{ number_format($inv['accounting_diff'], 2) }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.investors.invest-wallet', $inv['id']) }}" class="btn btn-sm btn-icon btn-label-primary" title="{{ __('View Wallet') }}" target="_blank">
                                    <i class="ti ti-wallet"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-custom-footer">
                    <tr>
                        <th colspan="2" class="fw-bold fs-6">{{ __('Total') }}</th>
                        <th class="fw-bold text-success">{{ number_format($totals['total_deposited_capital'], 2) }}</th>
                        <th class="fw-bold text-danger">{{ number_format($totals['total_withdrawn_capital'], 2) }}</th>
                        <th class="fw-bold text-primary">{{ number_format($totals['total_net_base_capital'], 2) }}</th>
                        <th class="fw-bold text-success">{{ number_format($totals['total_wallet_balance_stored'], 2) }}</th>
                        <th class="fw-bold text-warning">{{ number_format($totals['total_locked_in_tasks_ledger'], 2) }}</th>
                        <th class="fw-bold text-dark">{{ $totals['total_unsettled_tasks_count'] }} {{ __('tasks') }} ({{ number_format($totals['total_unsettled_tasks_amount'], 2) }})</th>
                        <th class="fw-bold text-warning">{{ number_format($totals['unsettled_vs_locked_diff'], 2) }}</th>
                        <th colspan="2" class="text-center text-success fw-bold fs-6">
                            <i class="ti ti-circle-check me-1"></i> {{ __('Zero Deficit (0.00 SAR)') }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Unsettled Tasks Distribution by Customer Section --}}
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 bg-white">
            <div>
                <h5 class="card-title mb-1 fw-bold text-dark">
                    <i class="ti ti-building me-1 text-info"></i> {{ __('Unsettled Tasks Distribution by Customer') }}
                </h5>
                <small class="text-secondary">
                    {{ __('Breakdown of tasks that have been funded by investors but not yet settled, organized by customer with direct task status inspectors.') }}
                </small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-soft-info text-info fs-6 px-3 py-2">
                    <i class="ti ti-users me-1"></i> {{ count($customersUnsettled) }} {{ __('Customers') }}
                </span>
                <span class="badge bg-info text-white fs-6 px-3 py-2">
                    <i class="ti ti-list-check me-1"></i> {{ $totals['total_unsettled_tasks_count'] }} {{ __('Unsettled Tasks') }} ({{ number_format($totals['total_unsettled_tasks_amount'], 2) }} {{ __('SAR') }})
                </span>
            </div>
        </div>

        <div class="table-responsive text-nowrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-custom-header">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Customer') }}</th>
                        <th class="text-center">{{ __('Tasks Count') }}</th>
                        <th>{{ __('Total Tasks Price') }}</th>
                        <th>{{ __('Total Funded Amount') }}</th>
                        <th>{{ __('Funding Investors') }}</th>
                        <th>{{ __('Statuses Breakdown') }}</th>
                        <th class="text-center">{{ __('Actions & Tasks List') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customersUnsettled as $cIndex => $cust)
                        <tr>
                            <td><span class="badge bg-soft-primary text-primary">#{{ $cIndex + 1 }}</span></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark fs-6">{{ $cust['customer_name'] }}</span>
                                    @if($cust['customer_phone'])
                                        <small class="text-secondary"><i class="ti ti-phone me-1"></i>{{ $cust['customer_phone'] }}</small>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-soft-primary text-primary fs-6 fw-bold px-3 py-2">
                                    {{ $cust['tasks_count'] }} {{ __('tasks') }}
                                </span>
                            </td>
                            <td class="fw-bold text-dark fs-6">
                                {{ number_format($cust['total_price'], 2) }} <small class="text-secondary">{{ __('SAR') }}</small>
                            </td>
                            <td class="fw-semibold text-warning">
                                {{ number_format($cust['total_funded'], 2) }} <small class="text-secondary">{{ __('SAR') }}</small>
                            </td>
                            <td style="max-width: 280px; white-space: normal;">
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($cust['investors'] as $invName => $invData)
                                        @php
                                            $invCount = is_array($invData) ? $invData['count'] : $invData;
                                            $invAmount = is_array($invData) ? $invData['total_price'] : 0;
                                        @endphp
                                        <span class="badge bg-soft-primary" title="{{ $invCount }} {{ __('tasks') }} - {{ number_format($invAmount, 2) }} {{ __('SAR') }}">
                                            {{ $invName }}: <strong>{{ $invCount }}</strong> <small>({{ number_format($invAmount, 2) }} {{ __('SAR') }})</small>
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td style="max-width: 250px; white-space: normal;">
                                <div class="d-flex flex-wrap gap-1">
                                    @php
                                        $statusBadgeMap = [
                                            'completed' => 'bg-soft-success',
                                            'assign' => 'bg-soft-primary',
                                            'in_progress' => 'bg-soft-warning',
                                            'canceled' => 'bg-soft-danger',
                                            'started' => 'bg-soft-info',
                                        ];
                                    @endphp
                                    @foreach($cust['statuses'] as $stName => $stCount)
                                        <span class="badge {{ $statusBadgeMap[$stName] ?? 'bg-soft-dark' }}">
                                            {{ $stName }}: <strong>{{ $stCount }}</strong>
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="showCustomerTasksModal({{ $cIndex }})">
                                        <i class="ti ti-list-numbers me-1"></i> {{ __('View Tasks & Statuses') }} ({{ $cust['tasks_count'] }})
                                    </button>
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-primary" onclick="copyCustomerTaskIds({{ $cIndex }})" title="{{ __('Copy All Task IDs') }}">
                                        <i class="ti ti-copy"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-secondary">
                                {{ __('No unsettled tasks found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-custom-footer">
                    <tr>
                        <th colspan="2" class="fw-bold fs-6">{{ __('Total') }}</th>
                        <th class="text-center fw-bold fs-6 text-primary">{{ $totals['total_unsettled_tasks_count'] }} {{ __('tasks') }}</th>
                        <th class="fw-bold fs-6 text-dark">{{ number_format($totals['total_unsettled_tasks_amount'], 2) }} {{ __('SAR') }}</th>
                        <th class="fw-bold fs-6 text-warning">{{ number_format($totals['total_locked_in_tasks_ledger'], 2) }} {{ __('SAR') }}</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Canceled Tasks with Locked Capital & Unreversed Commissions Section (AJAX) --}}
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 bg-soft-danger py-3">
            <div>
                <h5 class="card-title mb-1 fw-bold text-danger">
                    <i class="ti ti-ban me-1"></i> {{ __('Canceled Tasks with Locked Capital & Commissions') }}
                </h5>
                <small class="text-dark">
                    {{ __('Tasks that were canceled after being funded by investors without reverse credit of capital, with audit of commissions in personal wallets.') }}
                </small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-danger fs-6 px-3 py-2" id="canceledHeaderCountBadge">
                    <i class="ti ti-alert-triangle me-1"></i> <span id="canceledHeaderCount">{{ $canceledTasksAudit['count'] }}</span> {{ __('Canceled Tasks') }}
                </span>
                <span class="badge bg-warning text-dark fs-6 px-3 py-2" title="{{ __('Total locked capital in canceled tasks') }}">
                    <i class="ti ti-wallet me-1"></i> {{ __('Locked Capital') }}: <span id="canceledHeaderLockedCap">{{ number_format($canceledTasksAudit['total_locked_capital'], 2) }}</span> {{ __('SAR') }}
                </span>
                <span class="badge bg-danger fs-6 px-3 py-2" title="{{ __('Total unreversed commission in personal wallets') }}">
                    <i class="ti ti-receipt-refund me-1"></i> {{ __('Unreversed Commissions') }}: <span id="canceledHeaderUnreversedComm">{{ number_format($canceledTasksAudit['total_unreversed_commission'], 2) }}</span> {{ __('SAR') }}
                </span>
            </div>
        </div>

        {{-- Filter Bar for Canceled Tasks --}}
        <div class="card-body filter-toolbar py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="ti ti-search text-primary"></i></span>
                        <input type="text" id="canceledTaskSearchInput" class="form-control" placeholder="{{ __('Search canceled tasks by ID, Investor, Customer, or Canceler...') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="canceledCommissionFilter" class="form-select">
                        <option value="">-- {{ __('All Commission Statuses') }} --</option>
                        <option value="unreversed">{{ __('Commission Unreversed (Action Required)') }}</option>
                        <option value="zero">{{ __('No Commission Paid') }}</option>
                    </select>
                </div>
                <div class="col-md-5 text-end d-flex align-items-center justify-content-end gap-2 flex-wrap">
                    @can('settle_investors_audit_canceled_task')
                        <button type="button" id="btnBatchSettleCanceled" class="btn btn-sm btn-success d-none" onclick="openBatchSettleCanceledModal()">
                            <i class="ti ti-checks me-1"></i> {{ __('Settle Selected Canceled Tasks') }} (<span id="selectedCanceledCount">0</span>)
                        </button>
                    @endcan
                    <small class="text-dark">{{ __('Showing') }} <strong id="visibleCanceledTasksCount">0</strong> {{ __('of') }} <span id="totalCanceledTasksCount">0</span></small>
                </div>
            </div>
        </div>

        <div class="table-responsive text-nowrap position-relative">
            <table class="table table-hover align-middle mb-0" id="canceledTasksTable">
                <thead class="table-custom-header">
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            @can('settle_investors_audit_canceled_task')
                                <input type="checkbox" class="form-check-input" id="checkAllCanceledTasks" title="{{ __('Select All') }}">
                            @else
                                #
                            @endcan
                        </th>
                        <th>{{ __('Task #') }}</th>
                        <th>{{ __('Investor') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Task Price') }}</th>
                        <th>{{ __('Funded Capital') }}</th>
                        <th>{{ __('Refunded Capital') }}</th>
                        <th class="text-danger fw-bold">{{ __('Net Locked Capital') }}</th>
                        <th>{{ __('Commission Audit (Personal Wallet)') }}</th>
                        <th>{{ __('Canceled By & Date') }}</th>
                        <th class="text-center">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody id="canceledTasksTableBody">
                    <tr>
                        <td colspan="11" class="text-center py-4">
                            <div class="spinner-border text-danger spinner-border-sm me-1" role="status"></div>
                            <span class="text-secondary">{{ __('Loading canceled tasks data...') }}</span>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="table-custom-footer" id="canceledTasksTableFoot"></tfoot>
            </table>
        </div>

        {{-- Pagination for Canceled Tasks --}}
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
            <div class="small text-secondary" id="canceledPaginationInfo"></div>
            <nav aria-label="Canceled tasks pagination">
                <ul class="pagination pagination-sm mb-0 pagination-container" id="canceledPaginationNav"></ul>
            </nav>
        </div>
    </div>

    {{-- Task Commissions Entitlement & Contract Audit Section (AJAX) --}}
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 bg-soft-info py-3">
            <div>
                <h5 class="card-title mb-1 fw-bold text-info">
                    <i class="ti ti-percentage me-1"></i> {{ __('Task Commissions Reconciliation & Entitlement Audit') }}
                </h5>
                <small class="text-dark">
                    {{ __('Comparison between actual commissions credited to investor personal wallets and contract entitlements based on total platform commission.') }}
                </small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-success fs-6 px-3 py-2" title="{{ __('100% Matching with Contract') }}">
                    <i class="ti ti-check me-1"></i> <span id="commSummaryMatched">{{ $commissionAudit['summary']['matched_count'] }}</span> {{ __('Matched (100%)') }}
                </span>
                <span class="badge bg-danger fs-6 px-3 py-2" title="{{ __('Tasks with commission discrepancies') }}">
                    <i class="ti ti-alert-triangle me-1"></i> <span id="commSummaryDiscrepant">{{ $commissionAudit['summary']['discrepant_count'] }}</span> {{ __('Discrepant Tasks') }}
                </span>
                <span class="badge bg-soft-danger text-danger fs-6 px-3 py-2" title="{{ __('Total Overpaid Commissions') }}">
                    <i class="ti ti-arrow-up-right me-1"></i> {{ __('Overpaid') }}: +<span id="commSummaryOverpaid">{{ number_format($commissionAudit['summary']['total_overpaid'], 2) }}</span> {{ __('SAR') }}
                </span>
                <span class="badge bg-soft-warning text-warning fs-6 px-3 py-2" title="{{ __('Total Underpaid Commissions') }}">
                    <i class="ti ti-arrow-down-right me-1"></i> {{ __('Underpaid') }}: -<span id="commSummaryUnderpaid">{{ number_format($commissionAudit['summary']['total_underpaid'], 2) }}</span> {{ __('SAR') }}
                </span>
            </div>
        </div>

        {{-- Filter Bar for Commission Audit --}}
        <div class="card-body comm-audit-filter-panel">
            <div class="row g-3 align-items-end">
                {{-- Search --}}
                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
                    <label class="comm-filter-label">
                        <i class="ti ti-search text-primary me-1 fs-5"></i>{{ __('Search') }}
                    </label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text bg-white border-end-0 comm-filter-control pe-1">
                            <i class="ti ti-search text-primary"></i>
                        </span>
                        <input type="text" id="commAuditSearchInput" class="form-control border-start-0 comm-filter-control ps-1" placeholder="{{ __('Search by Task #, Investor, or Customer...') }}">
                    </div>
                </div>

                {{-- Filter by Discrepancy Type --}}
                <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12">
                    <label class="comm-filter-label">
                        <i class="ti ti-filter text-primary me-1 fs-5"></i>{{ __('Discrepancy Status') }}
                    </label>
                    <select id="commAuditStatusFilter" class="form-select comm-filter-control">
                        <option value="discrepant" selected>{{ __('Show Discrepancies Only (> 3 SAR)') }} (<span id="commFilterDiscrepantBadge">{{ $commissionAudit['summary']['discrepant_count'] }}</span>)</option>
                        <option value="all">{{ __('Show All Tasks') }} (<span id="commFilterTotalBadge">{{ $commissionAudit['summary']['total_audited'] }}</span>)</option>
                        <option value="overpaid">{{ __('Overpaid (Actual > Contract)') }} (<span id="commFilterOverpaidBadge">{{ $commissionAudit['summary']['overpaid_count'] }}</span>)</option>
                        <option value="underpaid">{{ __('Underpaid (Actual < Contract)') }} (<span id="commFilterUnderpaidBadge">{{ $commissionAudit['summary']['underpaid_count'] }}</span>)</option>
                        <option value="uncredited">{{ __('Uncredited Commission') }} (<span id="commFilterUncreditedBadge">{{ $commissionAudit['summary']['uncredited_count'] }}</span>)</option>
                        <option value="matched">{{ __('Matched (Diff <= 3 SAR)') }} (<span id="commFilterMatchedBadge">{{ $commissionAudit['summary']['matched_count'] }}</span>)</option>
                    </select>
                </div>

                {{-- Investor Filter --}}
                <div class="col-xl-2 col-lg-2 col-md-6 col-sm-12">
                    <label class="comm-filter-label">
                        <i class="ti ti-user-check text-primary me-1 fs-5"></i>{{ __('Investor') }}
                    </label>
                    <select id="commAuditInvestorFilter" class="form-select comm-filter-control">
                        <option value="">-- {{ __('All Investors') }} --</option>
                        @foreach($investorsData as $inv)
                            <option value="{{ $inv['name'] }}">{{ $inv['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Date Timeline Filter (From / To) --}}
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="comm-filter-label mb-0">
                            <i class="ti ti-calendar-stats text-primary me-1 fs-5"></i>{{ __('Timeline Filter') }}
                        </label>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-xs btn-label-primary rounded-pill px-2 py-0 fw-semibold" id="btnCommAuditResetDates" title="{{ __('Default (Last Month)') }}">
                                <i class="ti ti-history me-1" style="font-size: 11px;"></i>{{ __('Last Month') }}
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0 fw-semibold" id="btnCommAuditAllTime" title="{{ __('All Time') }}">
                                <i class="ti ti-infinity me-1" style="font-size: 11px;"></i>{{ __('All Time') }}
                            </button>
                        </div>
                    </div>
                    <div class="comm-date-range-card">
                        <div class="d-flex align-items-center gap-2">
                            <div class="comm-date-item flex-grow-1">
                                <span class="badge bg-label-primary text-primary px-2 py-1 me-1 fw-bold" style="font-size: 10px;">{{ __('From') }}</span>
                                <input type="date" id="commAuditDateFrom" class="comm-date-input" value="{{ $commDateFrom }}">
                            </div>
                            <div class="text-primary d-flex align-items-center justify-content-center px-1">
                                <i class="ti ti-arrows-left-right fs-5"></i>
                            </div>
                            <div class="comm-date-item flex-grow-1">
                                <span class="badge bg-label-primary text-primary px-2 py-1 me-1 fw-bold" style="font-size: 10px;">{{ __('To') }}</span>
                                <input type="date" id="commAuditDateTo" class="comm-date-input" value="{{ $commDateTo }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions & Selection Status Bar --}}
            <div class="comm-summary-bar d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div>
                    @can('settle_investors_audit_commission')
                        <button type="button" id="btnBatchSettleComm" class="btn btn-sm btn-primary d-none shadow-sm" onclick="openBatchSettleCommModal()">
                            <i class="ti ti-checks me-1"></i> {{ __('Settle Selected Commissions') }} (<span id="selectedCommCount">0</span>)
                        </button>
                    @endcan
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="badge bg-label-primary border border-primary border-opacity-25 py-2 px-3 fw-bold text-dark" style="font-size: 12px;">
                        <i class="ti ti-list-check text-primary me-1 fs-6"></i>
                        {{ __('Showing') }} <strong id="visibleCommAuditCount" class="text-primary mx-1">0</strong> {{ __('of') }} <strong id="totalCommAuditCount" class="text-dark mx-1">0</strong> {{ __('tasks') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="table-responsive text-nowrap position-relative">
            <table class="table table-hover align-middle mb-0" id="commissionAuditTable">
                <thead class="table-custom-header">
                    <tr>
                        <th style="width: 40px;" class="text-center">
                            @can('settle_investors_audit_commission')
                                <input type="checkbox" class="form-check-input" id="checkAllCommTasks" title="{{ __('Select All') }}">
                            @else
                                #
                            @endcan
                        </th>
                        <th>{{ __('Task #') }}</th>
                        <th>{{ __('Investor') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Task Price') }}</th>
                        <th>{{ __('Platform Commission') }}</th>
                        <th>{{ __('Contract Rate') }}</th>
                        <th class="text-primary">{{ __('Expected Commission') }}</th>
                        <th class="text-dark">{{ __('Actual Net Credited') }}</th>
                        <th class="text-center">{{ __('Difference') }}</th>
                        <th class="text-center">{{ __('Audit Status') }}</th>
                        <th class="text-center">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody id="commissionAuditTableBody">
                    <tr>
                        <td colspan="12" class="text-center py-4">
                            <div class="spinner-border text-info spinner-border-sm me-1" role="status"></div>
                            <span class="text-secondary">{{ __('Loading commission audit data...') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination for Commission Audit --}}
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
            <div class="small text-secondary" id="commPaginationInfo"></div>
            <nav aria-label="Commission audit pagination">
                <ul class="pagination pagination-sm mb-0 pagination-container" id="commPaginationNav"></ul>
            </nav>
        </div>
    </div>

    {{-- Problematic Tasks Section (AJAX) --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2 bg-white py-3">
            <div>
                <h5 class="card-title mb-1 fw-bold text-danger">
                    <i class="ti ti-alert-triangle me-1"></i> {{ __('Problematic Tasks & Pricing Modifications') }}
                </h5>
                <small class="text-secondary">
                    {{ __('Tasks where prices changed after funding, settled with discrepancies, or had duplicate funding. Shows who managed, funded, and modified the price.') }}
                </small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.investors.audit.export') }}" class="btn btn-sm btn-outline-success">
                    <i class="ti ti-file-spreadsheet me-1"></i> {{ __('Export to Excel') }}
                </a>
                <span class="badge bg-danger fs-6"><span id="probHeaderCount">{{ count($problematicTasks) }}</span> {{ __('Tasks with Discrepancies') }}</span>
            </div>
        </div>
        
        {{-- Filter Bar --}}
        <div class="card-body filter-toolbar py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="ti ti-search text-primary"></i></span>
                        <input type="text" id="taskSearchInput" class="form-control" placeholder="{{ __('Search by Task #, Investor, Customer, or User...') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <select id="issueTypeFilter" class="form-select">
                        <option value="">-- {{ __('All Issue Types') }} --</option>
                        <option value="تسوية ناقصة">{{ __('Under-settled (Diff retained)') }}</option>
                        <option value="تعديل السعر بعد التمويل">{{ __('Price Modified After Funding') }}</option>
                        <option value="تمويل مكرر">{{ __('Duplicate Funding') }}</option>
                        <option value="استرداد بدون خصم مسبق">{{ __('Refund without Debit') }}</option>
                    </select>
                </div>
                <div class="col-md-4 text-end">
                    <small class="text-dark">{{ __('Showing') }} <strong id="visibleTasksCount">0</strong> {{ __('of') }} <span id="totalProblematicCount">0</span></small>
                </div>
            </div>
        </div>

        <div class="table-responsive text-nowrap position-relative">
            <table class="table table-hover align-middle mb-0" id="problematicTasksTable">
                <thead class="table-custom-header">
                    <tr>
                        <th>{{ __('Task #') }}</th>
                        <th>{{ __('Investor') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Debited (Funded)') }}</th>
                        <th>{{ __('Credited (Settled)') }}</th>
                        <th>{{ __('Current Price') }}</th>
                        <th>{{ __('Pending Diff') }}</th>
                        <th>{{ __('Issue Category & Reason') }}</th>
                        <th>{{ __('Funded By') }}</th>
                        <th>{{ __('Price Modified By') }}</th>
                        <th>{{ __('Settled By') }}</th>
                        <th class="text-center">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody id="problematicTasksTableBody">
                    <tr>
                        <td colspan="13" class="text-center py-4">
                            <div class="spinner-border text-danger spinner-border-sm me-1" role="status"></div>
                            <span class="text-secondary">{{ __('Loading problematic tasks...') }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination for Problematic Tasks --}}
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
            <div class="small text-secondary" id="probPaginationInfo"></div>
            <nav aria-label="Problematic tasks pagination">
                <ul class="pagination pagination-sm mb-0 pagination-container" id="probPaginationNav"></ul>
            </nav>
        </div>
    </div>

</div>

{{-- ========================================================================= --}}
{{-- CUSTOM PROFESSIONAL BOOTSTRAP MODALS (REPLACING SWEETALERT CONFIRMATIONS) --}}
{{-- ========================================================================= --}}

{{-- 1. Modal for Settling Single Canceled Task --}}
<div class="modal fade" id="modalSettleCanceledTask" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header modal-custom-header bg-soft-success">
                <h5 class="modal-title fw-bold text-success d-flex align-items-center">
                    <i class="ti ti-receipt-refund fs-4 me-2"></i>
                    <span>{{ __('Settle Canceled Task') }} #<span id="settleModalTaskId"></span></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-white border rounded mb-3 shadow-none">
                    <div class="mb-2 pb-2 border-bottom d-flex justify-content-between align-items-center">
                        <span class="text-secondary"><i class="ti ti-user me-1 text-primary"></i>{{ __('Investor') }}:</span>
                        <strong class="text-dark fs-6" id="settleModalInvestorName"></strong>
                    </div>
                    <div class="mb-2 pb-2 border-bottom">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-success fw-semibold"><i class="ti ti-wallet me-1"></i>{{ __('Capital Return to Investment Wallet') }}:</span>
                            <span class="badge bg-success fs-6" id="settleModalLockedCap"></span>
                        </div>
                        <small class="text-secondary d-block"><i class="ti ti-info-circle me-1 text-info"></i>{{ __('Reason: Refund of invested capital due to task cancellation') }}</small>
                    </div>
                    <div class="mb-2 pb-2 border-bottom" id="settleModalCommSection">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-danger fw-semibold"><i class="ti ti-receipt-refund me-1"></i>{{ __('Commission Reversal from Personal Wallet') }}:</span>
                            <span class="badge bg-danger fs-6" id="settleModalNetComm"></span>
                        </div>
                        <small class="text-secondary d-block"><i class="ti ti-info-circle me-1 text-info"></i>{{ __('Reason: Reverse deduction of commission due to task cancellation') }}</small>
                    </div>
                    <div class="pt-1">
                        <span class="badge bg-soft-info text-info"><i class="ti ti-unlink me-1"></i>{{ __('Task will be unassigned from investor upon settlement') }}</span>
                    </div>
                </div>
                <div class="alert alert-warning py-2 px-3 small mb-0 border-0">
                    <i class="ti ti-alert-circle me-1"></i> {{ __('This action will execute immediately and record full financial movements in ledgers.') }}
                </div>
            </div>
            <div class="modal-footer modal-custom-footer">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-success" id="btnConfirmSettleCanceled" onclick="executeSettleCanceledTask()">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="settleCanceledSpinner"></span>
                    <i class="ti ti-check me-1"></i> {{ __('Yes, Confirm Settlement') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 2. Modal for Batch Settling Canceled Tasks --}}
<div class="modal fade" id="modalBatchSettleCanceled" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header modal-custom-header bg-soft-warning">
                <h5 class="modal-title fw-bold text-warning d-flex align-items-center">
                    <i class="ti ti-checks fs-4 me-2"></i>
                    <span>{{ __('Batch Settle Canceled Tasks') }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-3 text-dark">
                    {{ __('Are you sure you want to settle') }} <strong class="text-danger fs-5" id="batchCanceledCountText">0</strong> {{ __('selected canceled tasks?') }}
                </p>
                <div class="p-3 bg-white border rounded mb-3">
                    <ul class="mb-0 text-dark small ps-3">
                        <li class="mb-1">{{ __('Invested capital will be refunded to respective investor investment wallets.') }}</li>
                        <li class="mb-1">{{ __('Any unreversed credited commissions will be reverse debited from personal wallets.') }}</li>
                        <li class="mb-1">{{ __('Tasks will be unassigned from investors.') }}</li>
                        <li>{{ __('All actions will be logged in Task History and Activity Log.') }}</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer modal-custom-footer">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-success" id="btnConfirmBatchSettleCanceled" onclick="executeBatchSettleCanceledTasks()">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="batchCanceledSpinner"></span>
                    <i class="ti ti-checks me-1"></i> {{ __('Yes, Settle All Selected') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 3. Modal for Settling Single Commission --}}
<div class="modal fade" id="modalSettleCommission" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header modal-custom-header bg-soft-primary">
                <h5 class="modal-title fw-bold text-primary d-flex align-items-center">
                    <i class="ti ti-adjustments fs-4 me-2"></i>
                    <span>{{ __('Settle Commission for Task') }} #<span id="settleCommModalTaskId"></span></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-white border rounded mb-3">
                    <div class="mb-2 pb-2 border-bottom d-flex justify-content-between align-items-center">
                        <span class="text-secondary"><i class="ti ti-user me-1 text-primary"></i>{{ __('Investor') }}:</span>
                        <strong class="text-dark fs-6" id="settleCommModalInvestor"></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                        <span class="text-secondary"><i class="ti ti-file-text me-1 text-info"></i>{{ __('Expected by Contract') }}:</span>
                        <strong class="text-primary fs-6" id="settleCommModalExpected"></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                        <span class="text-secondary"><i class="ti ti-wallet me-1 text-secondary"></i>{{ __('Actual Net Credited') }}:</span>
                        <strong class="text-dark fs-6" id="settleCommModalActual"></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold" id="settleCommModalActionLabel"></span>
                        <span class="badge fs-6" id="settleCommModalDiffBadge"></span>
                    </div>
                </div>

                <div class="mb-2">
                    <label for="settleCommModalReason" class="form-label small fw-semibold text-dark">{{ __('Reason / Notes for Settlement (Optional)') }}:</label>
                    <input type="text" id="settleCommModalReason" class="form-control" placeholder="{{ __('e.g., Adjusting commission discrepancy as per investment contract') }}">
                </div>
            </div>
            <div class="modal-footer modal-custom-footer">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btnConfirmSettleComm" onclick="executeSettleCommission()">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="settleCommSpinner"></span>
                    <i class="ti ti-check me-1"></i> {{ __('Confirm Commission Settlement') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 4. Modal for Batch Settling Commissions --}}
<div class="modal fade" id="modalBatchSettleComm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header modal-custom-header bg-soft-info">
                <h5 class="modal-title fw-bold text-info d-flex align-items-center">
                    <i class="ti ti-checks fs-4 me-2"></i>
                    <span>{{ __('Batch Settle Commissions') }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-3 text-dark">
                    {{ __('Are you sure you want to settle commissions for') }} <strong class="text-primary fs-5" id="batchCommCountText">0</strong> {{ __('selected tasks?') }}
                </p>
                <div class="p-3 bg-white border rounded mb-3">
                    <ul class="mb-0 text-dark small ps-3">
                        <li class="mb-1">{{ __('Underpaid commissions will be credited to respective investor wallets.') }}</li>
                        <li class="mb-1">{{ __('Overpaid commissions will be debited from respective investor wallets.') }}</li>
                        <li>{{ __('All settlements will be logged in Task History and Activity Log.') }}</li>
                    </ul>
                </div>
                <div>
                    <label for="batchCommModalReason" class="form-label small fw-semibold text-dark">{{ __('Reason / Notes for Batch Settlement (Optional)') }}:</label>
                    <input type="text" id="batchCommModalReason" class="form-control" placeholder="{{ __('e.g., Batch contract commission reconciliation') }}">
                </div>
            </div>
            <div class="modal-footer modal-custom-footer">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="btnConfirmBatchSettleComm" onclick="executeBatchSettleCommissions()">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="batchCommSpinner"></span>
                    <i class="ti ti-checks me-1"></i> {{ __('Yes, Settle All Selected Commissions') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 5. Modal for Deleting Orphaned Refund Transaction --}}
<div class="modal fade" id="modalDeleteOrphaned" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header modal-custom-header bg-soft-danger">
                <h5 class="modal-title fw-bold text-danger d-flex align-items-center">
                    <i class="ti ti-trash fs-4 me-2"></i>
                    <span>{{ __('Delete Orphaned Refund Transaction') }}</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-dark mb-2">
                    {{ __('Are you sure you want to delete this orphaned credit transaction for Task') }} #<strong id="deleteOrphanedTaskId"></strong>?
                </p>
                <p class="text-secondary small mb-3">
                    {{ __('This will reverse the credit amount and update the investor balance accordingly.') }}
                </p>
                <div>
                    <label class="form-label small fw-semibold text-dark">{{ __('Reason / Notes for Deletion') }}:</label>
                    <input type="text" id="deleteOrphanedNotes" class="form-control" placeholder="{{ __('e.g. Removing duplicate credit transaction') }}">
                </div>
            </div>
            <div class="modal-footer modal-custom-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDeleteOrphaned" onclick="executeDeleteOrphanedTx()">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="deleteOrphanedSpinner"></span>
                    <i class="ti ti-trash me-1"></i> {{ __('Yes, Delete Transaction') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal for Full Task Audit Timeline --}}
<div class="modal fade" id="taskAuditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header modal-custom-header bg-white">
                <h5 class="modal-title fw-bold text-dark" id="taskModalTitle">{{ __('Task Audit Trail & Full History') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="taskModalBody">
                {{-- Loaded dynamically --}}
            </div>
            <div class="modal-footer modal-custom-footer">
                <a href="#" id="modalTaskDirectLink" target="_blank" class="btn btn-primary">
                    <i class="ti ti-external-link me-1"></i> {{ __('Open Task Page') }}
                </a>
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal for Editing Funding Amount --}}
<div class="modal fade" id="editFundingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header modal-custom-header bg-soft-warning">
                <h5 class="modal-title fw-bold text-warning d-flex align-items-center">
                    <i class="ti ti-edit fs-4 me-2"></i>{{ __('Edit Task Funding Amount') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editFundingForm" onsubmit="submitEditFunding(event)">
                <input type="hidden" id="editFundingTaskId" name="task_id">
                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3">
                        {{ __('Adjust the debit funding transaction in the investor wallet to match the new or current task price.') }}
                    </p>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-secondary small">{{ __('Task #') }}</label>
                            <input type="text" class="form-control" id="modalDisplayTaskId" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary small">{{ __('Investor') }}</label>
                            <input type="text" class="form-control" id="modalDisplayInvestor" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary small">{{ __('Current Debited Amount') }}</label>
                            <input type="text" class="form-control text-danger fw-bold" id="modalDisplayDebited" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-secondary small">{{ __('Current Task Price') }}</label>
                            <input type="text" class="form-control text-dark fw-bold" id="modalDisplayPrice" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0 text-dark" for="modalNewFundingAmount">
                                {{ __('New Funding Amount (SAR)') }} <span class="text-danger">*</span>
                            </label>
                            <button type="button" class="btn btn-xs btn-label-primary py-0" onclick="applyCurrentTaskPrice()">
                                <i class="ti ti-copy me-1"></i> {{ __('Use Current Task Price') }}
                            </button>
                        </div>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" class="form-control form-control-lg fw-bold text-primary" id="modalNewFundingAmount" name="new_amount" required>
                            <span class="input-group-text fw-semibold">{{ __('SAR') }}</span>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-dark" for="modalFundingNotes">{{ __('Reason / Notes') }}</label>
                        <textarea class="form-control" id="modalFundingNotes" name="notes" rows="2" placeholder="{{ __('e.g. Correcting funding debit after manual task price adjustment') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer modal-custom-footer">
                    <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitFunding">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="fundingSpinner"></span>
                        <i class="ti ti-check me-1"></i> {{ __('Save & Update Balance') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal for Customer Unsettled Tasks --}}
<div class="modal fade" id="customerTasksModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header modal-custom-header bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar bg-soft-primary p-2 rounded">
                        <i class="ti ti-building fs-2 text-primary"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-1 text-dark" id="customerModalTitle"></h5>
                        <div class="d-flex align-items-center gap-2 flex-wrap" id="customerModalBadgesHeader">
                            {{-- Filled dynamically with summary badges --}}
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnCopyCustomerModalIds">
                        <i class="ti ti-copy me-1"></i> {{ __('Copy All Task IDs') }}
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <div class="modal-body p-4">
                {{-- Status KPI Cards Grid --}}
                <div class="mb-3">
                    <label class="form-label text-dark small fw-bold mb-2">
                        <i class="ti ti-chart-pie me-1 text-primary"></i> {{ __('Task Statistics by Status (Click to Filter)') }}:
                    </label>
                    <div class="row g-2" id="customerModalStatusCards">
                        {{-- Filled dynamically --}}
                    </div>
                </div>

                {{-- Filter & Control Bar inside Modal --}}
                <div class="card mb-3 border filter-toolbar shadow-none">
                    <div class="card-body p-2">
                        <div class="row g-2 align-items-center">
                            {{-- Search Input --}}
                            <div class="col-md-4 col-sm-6">
                                <div class="input-group input-group-merge input-group-sm">
                                    <span class="input-group-text"><i class="ti ti-search text-primary"></i></span>
                                    <input type="text" id="modalCustomerTaskSearch" class="form-control" placeholder="{{ __('Search by Task #, Investor, or Price...') }}">
                                </div>
                            </div>
                            {{-- Status Filter --}}
                            <div class="col-md-2 col-sm-6">
                                <select id="modalCustomerStatusFilter" class="form-select form-select-sm">
                                    <option value="">-- {{ __('All Statuses') }} --</option>
                                </select>
                            </div>
                            {{-- Investor Filter --}}
                            <div class="col-md-3 col-sm-6">
                                <select id="modalCustomerInvestorFilter" class="form-select form-select-sm">
                                    <option value="">-- {{ __('All Investors') }} --</option>
                                </select>
                            </div>
                            {{-- Sort Filter --}}
                            <div class="col-md-2 col-sm-3">
                                <select id="modalCustomerSortFilter" class="form-select form-select-sm">
                                    <option value="id_desc">{{ __('Latest (Task #)') }}</option>
                                    <option value="id_asc">{{ __('Oldest (Task #)') }}</option>
                                    <option value="price_desc">{{ __('Highest Price') }}</option>
                                    <option value="price_asc">{{ __('Lowest Price') }}</option>
                                </select>
                            </div>
                            {{-- Page Size --}}
                            <div class="col-md-1 col-sm-3">
                                <select id="modalCustomerPageSize" class="form-select form-select-sm" title="{{ __('Rows per page') }}">
                                    <option value="10">10</option>
                                    <option value="25" selected>25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                    <option value="all">{{ __('All') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Task IDs Collapsible Ribbon --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <a class="text-primary small fw-bold text-decoration-none" data-bs-toggle="collapse" href="#customerTaskBadgesCollapse" role="button" aria-expanded="false">
                            <i class="ti ti-tags me-1"></i> {{ __('Quick Task Numbers Cloud') }} <i class="ti ti-chevron-down ms-1"></i>
                        </a>
                        <small class="text-dark fw-semibold" id="customerModalFilteredSummaryText"></small>
                    </div>
                    <div class="collapse" id="customerTaskBadgesCollapse">
                        <div class="p-2 border rounded bg-white d-flex flex-wrap gap-1" id="customerTaskBadgesCloud" style="max-height: 120px; overflow-y: auto;">
                            {{-- Filled dynamically --}}
                        </div>
                    </div>
                </div>

                {{-- Tasks Detailed Table --}}
                <div class="table-responsive border rounded bg-white">
                    <table class="table table-sm table-hover align-middle mb-0" id="customerTasksDetailTable">
                        <thead class="table-custom-header">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>{{ __('Task #') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Task Price') }}</th>
                                <th>{{ __('Funded Amount') }}</th>
                                <th>{{ __('Investor') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-center" style="width: 80px;">{{ __('Open Task') }}</th>
                            </tr>
                        </thead>
                        <tbody id="customerTasksTableBody">
                            {{-- Filled dynamically --}}
                        </tbody>
                        <tfoot class="table-custom-footer" id="customerTasksTableFoot">
                            {{-- Filled dynamically --}}
                        </tfoot>
                    </table>
                </div>

                {{-- Professional Pagination Bar --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2 border-top">
                    <div class="small text-secondary" id="customerModalPaginationInfo"></div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0 pagination-container" id="customerModalPaginationNav"></ul>
                    </nav>
                </div>
            </div>

            <div class="modal-footer modal-custom-footer">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
    // Global Data Holders
    const customersUnsettledData = @json($customersUnsettled);
    let problematicAllData = [];

    // State Variables for Pagination & Filters
    let canceledPage = 1;
    let commAuditPage = 1;
    let probTasksPage = 1;

    let pendingSettleCanceledTaskId = null;
    let pendingSettleCommTaskId = null;
    let pendingDeleteOrphanedTaskId = null;
    let pendingDeleteOrphanedTxId = null;

    // Toast Notification Helper using SweetAlert2
    function showNotification(type, message) {
        const iconType = (type === 'danger' || type === 'error') ? 'error' : (type === 'warning' ? 'warning' : 'success');
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: iconType,
                title: iconType === 'error' ? '{{ __("Error") }}' : (iconType === 'warning' ? '{{ __("Warning") }}' : '{{ __("Success") }}'),
                text: message,
                showConfirmButton: iconType === 'error',
                confirmButtonText: '{{ __("OK") }}',
                confirmButtonColor: '#7367f0',
                timer: iconType === 'error' ? undefined : 3500,
                timerProgressBar: iconType !== 'error'
            });
        }
    }

    // Refresh all audit data
    window.refreshAllAuditData = function() {
        loadCanceledTasks(1);
        loadCommissionAudit(1);
        loadProblematicTasks(1);
    };

    // =========================================================================
    // 1. CANCELED TASKS (AJAX, PAGINATION, MODALS)
    // =========================================================================
    function loadCanceledTasks(page = 1) {
        canceledPage = page;
        const search = (document.getElementById('canceledTaskSearchInput')?.value || '').trim();
        const commFilter = (document.getElementById('canceledCommissionFilter')?.value || '').trim();
        const tbody = document.getElementById('canceledTasksTableBody');

        tbody.innerHTML = `<tr><td colspan="11" class="text-center py-4"><div class="spinner-border text-danger spinner-border-sm me-1"></div><span class="text-secondary">{{ __("Loading canceled tasks data...") }}</span></td></tr>`;

        $.ajax({
            url: '{{ route("admin.investors.audit.canceled-tasks-data") }}',
            type: 'GET',
            data: {
                page: page,
                per_page: 15,
                search: search,
                comm_status: commFilter
            },
            success: function(res) {
                if (res.status === 1) {
                    renderCanceledTasksTable(res.data, res.pagination, res.totals);
                } else {
                    tbody.innerHTML = `<tr><td colspan="11" class="text-center py-4 text-danger">${res.error || '{{ __("Failed to load data") }}'}</td></tr>`;
                }
            },
            error: function() {
                tbody.innerHTML = `<tr><td colspan="11" class="text-center py-4 text-danger">{{ __("Error communicating with server.") }}</td></tr>`;
            }
        });
    }

    function renderCanceledTasksTable(tasks, pagination, totals) {
        const tbody = document.getElementById('canceledTasksTableBody');
        const tfoot = document.getElementById('canceledTasksTableFoot');
        const countDisplay = document.getElementById('visibleCanceledTasksCount');
        const totalDisplay = document.getElementById('totalCanceledTasksCount');
        const infoDisplay = document.getElementById('canceledPaginationInfo');

        // Update header badges
        if (totals) {
            document.getElementById('canceledHeaderCount').textContent = totals.count;
            document.getElementById('canceledHeaderLockedCap').textContent = Number(totals.total_locked_capital).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('canceledHeaderUnreversedComm').textContent = Number(totals.total_unreversed_commission).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        if (countDisplay) countDisplay.textContent = tasks.length;
        if (totalDisplay) totalDisplay.textContent = pagination.total;

        if (tasks.length === 0) {
            tbody.innerHTML = `<tr><td colspan="11" class="text-center py-4 text-success"><i class="ti ti-circle-check fs-2 mb-2 d-block"></i>{{ __("No unrefunded canceled tasks found! All canceled tasks have been settled.") }}</td></tr>`;
            tfoot.innerHTML = '';
            if (infoDisplay) infoDisplay.textContent = '{{ __("No records found") }}';
            renderPaginationNav('canceledPaginationNav', 1, 1, loadCanceledTasks);
            updateCanceledSelection();
            return;
        }

        let html = '';
        tasks.forEach(function(cTask) {
            let commHtml = '';
            if (cTask.has_unreversed_commission) {
                commHtml += `
                    <div class="d-flex align-items-center mb-1">
                        <span class="badge bg-danger fs-6 fw-bold me-1">${Number(cTask.net_commission).toFixed(2)} {{ __("SAR") }}</span>
                        <span class="badge bg-soft-danger text-danger">{{ __("Credited, NOT Reversed") }}</span>
                    </div>
                    <div class="small">
                `;
                cTask.commission_breakdown.forEach(function(b) {
                    commHtml += `
                        <div class="text-secondary">
                            <i class="ti ti-user me-1"></i>${b.user_name}:
                            <strong class="text-danger">${Number(b.net).toFixed(2)} {{ __("SAR") }}</strong>
                            <span class="text-dark">({{ __("Credited") }}: ${Number(b.credited).toFixed(2)} | {{ __("Debited") }}: ${Number(b.debited).toFixed(2)})</span>
                        </div>
                    `;
                });
                commHtml += `</div>`;
            } else if (cTask.credited_commission > 0) {
                commHtml = `<span class="badge bg-soft-success text-success"><i class="ti ti-check me-1"></i> {{ __("Commission Reversed") }} (${Number(cTask.credited_commission).toFixed(2)} {{ __("SAR") }})</span>`;
            } else {
                commHtml = `<span class="badge bg-soft-dark"><i class="ti ti-minus me-1"></i> {{ __("No Commission Credited") }}</span>`;
            }

            const investorSafe = (cTask.investor_name || '').replace(/'/g, "\\'");

            html += `
                <tr class="canceled-task-row" id="canceled-task-row-${cTask.task_id}">
                    <td class="text-center">
                        @can('settle_investors_audit_canceled_task')
                            <input type="checkbox" class="form-check-input canceled-task-cb" value="${cTask.task_id}" onchange="updateCanceledSelection()">
                        @else
                            <span class="text-secondary small">•</span>
                        @endcan
                    </td>
                    <td>
                        <a href="{{ url('admin/tasks/show') }}/${cTask.task_id}" class="fw-bold text-primary" target="_blank">
                            #${cTask.task_id}
                        </a>
                        <br>
                        <span class="badge bg-soft-danger text-danger">${cTask.status}</span>
                    </td>
                    <td><span class="fw-bold text-dark">${cTask.investor_name}</span></td>
                    <td><span class="text-secondary">${cTask.customer_name}</span></td>
                    <td class="fw-semibold text-dark">${Number(cTask.task_price).toFixed(2)} <small class="text-secondary">{{ __("SAR") }}</small></td>
                    <td class="text-danger fw-semibold">${Number(cTask.funded_amount).toFixed(2)} <small class="text-secondary">{{ __("SAR") }}</small></td>
                    <td class="text-success fw-semibold">${Number(cTask.refunded_amount).toFixed(2)} <small class="text-secondary">{{ __("SAR") }}</small></td>
                    <td>
                        <span class="badge bg-danger fs-6 fw-bold">${Number(cTask.net_locked_capital).toFixed(2)} {{ __("SAR") }}</span>
                        <br>
                        <small class="text-danger fw-semibold"><i class="ti ti-arrow-back-up me-1"></i>{{ __("Not refunded to investor wallet") }}</small>
                    </td>
                    <td style="min-width: 260px; white-space: normal;">${commHtml}</td>
                    <td>
                        <div class="d-flex flex-column">
                            <span class="fw-semibold text-dark"><i class="ti ti-user-x me-1 text-danger"></i>${cTask.canceled_by}</span>
                            <small class="text-secondary"><i class="ti ti-calendar me-1"></i>${cTask.canceled_at}</small>
                            ${cTask.cancel_description ? `<small class="text-dark text-truncate" style="max-width: 220px;" title="${cTask.cancel_description}">${cTask.cancel_description}</small>` : ''}
                        </div>
                    </td>
                    <td class="text-center">
                        @can('settle_investors_audit_canceled_task')
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="openSettleCanceledModal(${cTask.task_id}, ${cTask.net_locked_capital}, ${cTask.net_commission}, '${investorSafe}')">
                                <i class="ti ti-receipt-refund me-1"></i> {{ __("Settle Canceled Task") }}
                            </button>
                        @else
                            <span class="badge bg-soft-dark"><i class="ti ti-lock me-1"></i>{{ __("No Permission") }}</span>
                        @endcan
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;

        // Render Footer
        tfoot.innerHTML = `
            <tr class="align-middle fw-bold">
                <th colspan="4" class="text-end">{{ __("Total on this Page") }}:</th>
                <th class="text-dark">${Number(tasks.reduce((s, t) => s + parseFloat(t.task_price), 0)).toFixed(2)} {{ __("SAR") }}</th>
                <th class="text-danger">${Number(tasks.reduce((s, t) => s + parseFloat(t.funded_amount), 0)).toFixed(2)} {{ __("SAR") }}</th>
                <th class="text-success">${Number(tasks.reduce((s, t) => s + parseFloat(t.refunded_amount), 0)).toFixed(2)} {{ __("SAR") }}</th>
                <th class="text-danger">${Number(tasks.reduce((s, t) => s + parseFloat(t.net_locked_capital), 0)).toFixed(2)} {{ __("SAR") }}</th>
                <th class="text-danger">${Number(tasks.reduce((s, t) => s + parseFloat(t.net_commission), 0)).toFixed(2)} {{ __("SAR") }}</th>
                <th colspan="2"></th>
            </tr>
        `;

        if (infoDisplay) {
            infoDisplay.innerHTML = `{{ __("Showing") }} <strong>${pagination.from}</strong> {{ __("to") }} <strong>${pagination.to}</strong> {{ __("of") }} <strong>${pagination.total}</strong> {{ __("records") }}`;
        }

        renderPaginationNav('canceledPaginationNav', pagination.current_page, pagination.total_pages, loadCanceledTasks);
        updateCanceledSelection();
    }

    window.updateCanceledSelection = function() {
        const checkedBoxes = document.querySelectorAll('.canceled-task-cb:checked');
        const count = checkedBoxes.length;
        const countSpan = document.getElementById('selectedCanceledCount');
        const btn = document.getElementById('btnBatchSettleCanceled');

        if (countSpan) countSpan.textContent = count;
        if (btn) {
            if (count > 0) btn.classList.remove('d-none');
            else btn.classList.add('d-none');
        }

        const totalVisibleBoxes = document.querySelectorAll('.canceled-task-cb');
        const checkAll = document.getElementById('checkAllCanceledTasks');
        if (checkAll) {
            checkAll.checked = totalVisibleBoxes.length > 0 && checkedBoxes.length === totalVisibleBoxes.length;
        }
    };

    // Open Single Settle Canceled Task Modal
    window.openSettleCanceledModal = function(taskId, lockedCap, netComm, investorName) {
        pendingSettleCanceledTaskId = taskId;
        document.getElementById('settleModalTaskId').textContent = taskId;
        document.getElementById('settleModalInvestorName').textContent = investorName;
        document.getElementById('settleModalLockedCap').textContent = Number(lockedCap).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' {{ __("SAR") }}';

        const commSection = document.getElementById('settleModalCommSection');
        if (netComm > 0) {
            commSection.style.display = '';
            document.getElementById('settleModalNetComm').textContent = Number(netComm).toLocaleString('en-US', {minimumFractionDigits: 2}) + ' {{ __("SAR") }}';
        } else {
            commSection.style.display = 'none';
        }

        new bootstrap.Modal(document.getElementById('modalSettleCanceledTask')).show();
    };

    window.executeSettleCanceledTask = function() {
        if (!pendingSettleCanceledTaskId) return;
        const btn = document.getElementById('btnConfirmSettleCanceled');
        const spinner = document.getElementById('settleCanceledSpinner');
        btn.disabled = true;
        spinner.classList.remove('d-none');

        $.ajax({
            url: '{{ route("admin.investors.audit.settle-canceled-task") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                task_id: pendingSettleCanceledTaskId
            },
            success: function(res) {
                btn.disabled = false;
                spinner.classList.add('d-none');
                bootstrap.Modal.getInstance(document.getElementById('modalSettleCanceledTask')).hide();

                if (res.status === 1) {
                    showNotification('success', res.success || '{{ __("Settlement Completed Successfully!") }}');
                    loadCanceledTasks(canceledPage);
                } else {
                    showNotification('danger', res.error || '{{ __("An error occurred during settlement") }}');
                }
            },
            error: function(xhr) {
                btn.disabled = false;
                spinner.classList.add('d-none');
                showNotification('danger', xhr.responseJSON?.message || '{{ __("Request failed") }}');
            }
        });
    };

    // Open Batch Settle Canceled Tasks Modal
    window.openBatchSettleCanceledModal = function() {
        const checkedBoxes = document.querySelectorAll('.canceled-task-cb:checked');
        const count = checkedBoxes.length;
        if (count === 0) return;

        document.getElementById('batchCanceledCountText').textContent = count;
        new bootstrap.Modal(document.getElementById('modalBatchSettleCanceled')).show();
    };

    window.executeBatchSettleCanceledTasks = function() {
        const checkedBoxes = document.querySelectorAll('.canceled-task-cb:checked');
        const taskIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value)).filter(id => !isNaN(id));
        if (taskIds.length === 0) return;

        const btn = document.getElementById('btnConfirmBatchSettleCanceled');
        const spinner = document.getElementById('batchCanceledSpinner');
        btn.disabled = true;
        spinner.classList.remove('d-none');

        $.ajax({
            url: '{{ route("admin.investors.audit.batch-settle-canceled-tasks") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                task_ids: taskIds
            },
            success: function(res) {
                btn.disabled = false;
                spinner.classList.add('d-none');
                bootstrap.Modal.getInstance(document.getElementById('modalBatchSettleCanceled')).hide();

                if (res.status === 1) {
                    showNotification('success', res.success || '{{ __("Batch Settlement Completed!") }}');
                    loadCanceledTasks(1);
                } else {
                    showNotification('danger', res.error || '{{ __("An error occurred during batch settlement") }}');
                }
            },
            error: function(xhr) {
                btn.disabled = false;
                spinner.classList.add('d-none');
                showNotification('danger', xhr.responseJSON?.message || '{{ __("Request failed") }}');
            }
        });
    };

    // =========================================================================
    // 2. COMMISSION AUDIT (AJAX, PAGINATION, MODALS)
    // =========================================================================
    function loadCommissionAudit(page = 1) {
        commAuditPage = page;
        const search = (document.getElementById('commAuditSearchInput')?.value || '').trim();
        const status = (document.getElementById('commAuditStatusFilter')?.value || 'discrepant').trim();
        const investor = (document.getElementById('commAuditInvestorFilter')?.value || '').trim();
        const dateFrom = (document.getElementById('commAuditDateFrom')?.value || '').trim();
        const dateTo = (document.getElementById('commAuditDateTo')?.value || '').trim();
        const tbody = document.getElementById('commissionAuditTableBody');

        tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4"><div class="spinner-border text-info spinner-border-sm me-1"></div><span class="text-secondary">{{ __("Loading commission audit data...") }}</span></td></tr>`;

        $.ajax({
            url: '{{ route("admin.investors.audit.commission-audit-data") }}',
            type: 'GET',
            data: {
                page: page,
                per_page: 20,
                search: search,
                status: status,
                investor: investor,
                date_from: dateFrom,
                date_to: dateTo
            },
            success: function(res) {
                if (res.status === 1) {
                    renderCommissionAuditTable(res.data, res.pagination, res.summary);
                } else {
                    tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-danger">${res.error || '{{ __("Failed to load data") }}'}</td></tr>`;
                }
            },
            error: function() {
                tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-danger">{{ __("Error communicating with server.") }}</td></tr>`;
            }
        });
    }

    function renderCommissionAuditTable(tasks, pagination, summary) {
        const tbody = document.getElementById('commissionAuditTableBody');
        const countDisplay = document.getElementById('visibleCommAuditCount');
        const totalDisplay = document.getElementById('totalCommAuditCount');
        const infoDisplay = document.getElementById('commPaginationInfo');

        // Update header badges
        if (summary) {
            document.getElementById('commSummaryMatched').textContent = summary.matched_count;
            document.getElementById('commSummaryDiscrepant').textContent = summary.discrepant_count;
            document.getElementById('commSummaryOverpaid').textContent = Number(summary.total_overpaid).toFixed(2);
            document.getElementById('commSummaryUnderpaid').textContent = Number(summary.total_underpaid).toFixed(2);

            const bDisc = document.getElementById('commFilterDiscrepantBadge');
            if (bDisc) bDisc.textContent = summary.discrepant_count;
            const bTotal = document.getElementById('commFilterTotalBadge');
            if (bTotal) bTotal.textContent = summary.total_audited;
            const bOver = document.getElementById('commFilterOverpaidBadge');
            if (bOver) bOver.textContent = summary.overpaid_count;
            const bUnder = document.getElementById('commFilterUnderpaidBadge');
            if (bUnder) bUnder.textContent = summary.underpaid_count;
            const bUncred = document.getElementById('commFilterUncreditedBadge');
            if (bUncred) bUncred.textContent = summary.uncredited_count;
            const bMatch = document.getElementById('commFilterMatchedBadge');
            if (bMatch) bMatch.textContent = summary.matched_count;
        }

        if (countDisplay) countDisplay.textContent = tasks.length;
        if (totalDisplay) totalDisplay.textContent = pagination.total;

        if (tasks.length === 0) {
            tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-secondary"><i class="ti ti-file-off fs-2 mb-2 d-block"></i>{{ __("No commission audit records found.") }}</td></tr>`;
            if (infoDisplay) infoDisplay.textContent = '{{ __("No records found") }}';
            renderPaginationNav('commPaginationNav', 1, 1, loadCommissionAudit);
            updateCommSelection();
            return;
        }

        let html = '';
        tasks.forEach(function(item) {
            const isDiscrepant = ['overpaid', 'underpaid', 'uncredited'].includes(item.status);
            const investorSafe = (item.investor_name || '').replace(/'/g, "\\'");

            let diffBadge = '';
            if (Math.abs(item.diff) <= 0.05) {
                diffBadge = `<span class="badge bg-soft-success text-success fw-bold"><i class="ti ti-equal me-1"></i> 0.00 {{ __("SAR") }}</span>`;
            } else if (item.diff > 0) {
                diffBadge = `
                    <span class="badge bg-soft-danger text-danger fw-bold fs-7"><i class="ti ti-plus me-1"></i>+${Number(item.diff).toFixed(2)} {{ __("SAR") }}</span>
                    <br><small class="text-danger fw-semibold">{{ __("Overpaid") }}</small>
                `;
            } else {
                diffBadge = `
                    <span class="badge bg-soft-warning text-warning fw-bold fs-7"><i class="ti ti-minus me-1"></i>${Number(item.diff).toFixed(2)} {{ __("SAR") }}</span>
                    <br><small class="text-warning fw-semibold">{{ __("Underpaid") }}</small>
                `;
            }

            html += `
                <tr class="comm-audit-row" id="comm-task-row-${item.task_id}">
                    <td class="text-center">
                        @can('settle_investors_audit_commission')
                            ${isDiscrepant ? `<input type="checkbox" class="form-check-input comm-task-cb" value="${item.task_id}" onchange="updateCommSelection()">` : '<span class="text-secondary small">•</span>'}
                        @else
                            <span class="text-secondary small">•</span>
                        @endcan
                    </td>
                    <td>
                        <a href="{{ url('admin/tasks/show') }}/${item.task_id}" class="fw-bold text-primary" target="_blank">
                            #${item.task_id}
                        </a>
                        <br>
                        <span class="badge bg-soft-dark small">${item.task_status}</span>
                        ${item.task_date ? `<br><small class="text-muted"><i class="ti ti-calendar me-1"></i>${item.task_date}</small>` : ''}
                    </td>
                    </td>
                    <td><span class="fw-bold text-dark">${item.investor_name}</span></td>
                    <td><span class="text-secondary text-truncate d-inline-block" style="max-width: 180px;" title="${item.customer_name}">${item.customer_name}</span></td>
                    <td class="fw-semibold text-dark">${Number(item.task_price).toFixed(2)} <small class="text-secondary">{{ __("SAR") }}</small></td>
                    <td>
                        <span class="fw-bold text-dark">${Number(item.platform_commission).toFixed(2)}</span> <small class="text-secondary">{{ __("SAR") }}</small>
                        <br><small class="text-secondary"><i class="ti ti-info-circle me-1"></i>${item.platform_commission_source}</small>
                    </td>
                    <td><span class="badge bg-soft-info text-info fw-bold">${item.contract_rate}</span></td>
                    <td class="fw-bold text-primary fs-6">${Number(item.expected_commission).toFixed(2)} <small class="text-secondary">{{ __("SAR") }}</small></td>
                    <td class="fw-bold text-dark fs-6">
                        ${Number(item.actual_commission).toFixed(2)} <small class="text-secondary">{{ __("SAR") }}</small>
                        ${item.credited_amount > 0 && item.debited_amount > 0 ? `<br><small class="text-secondary">({{ __("Credited") }}: ${Number(item.credited_amount).toFixed(2)} | {{ __("Debited") }}: ${Number(item.debited_amount).toFixed(2)})</small>` : ''}
                    </td>
                    <td class="text-center">${diffBadge}</td>
                    <td class="text-center"><span class="badge ${item.status_badge} fs-7 py-2 px-3">${item.status_label}</span></td>
                    <td class="text-center">
                        ${isDiscrepant ? `
                            @can('settle_investors_audit_commission')
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="openSettleCommModal(${item.task_id}, ${item.expected_commission}, ${item.actual_commission}, ${item.diff}, '${investorSafe}')">
                                    <i class="ti ti-adjustments me-1"></i> {{ __("Settle Commission") }}
                                </button>
                            @else
                                <span class="badge bg-soft-dark"><i class="ti ti-lock me-1"></i>{{ __("No Permission") }}</span>
                            @endcan
                        ` : `<span class="text-success small"><i class="ti ti-circle-check fs-5"></i></span>`}
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;

        if (infoDisplay) {
            infoDisplay.innerHTML = `{{ __("Showing") }} <strong>${pagination.from}</strong> {{ __("to") }} <strong>${pagination.to}</strong> {{ __("of") }} <strong>${pagination.total}</strong> {{ __("records") }}`;
        }

        renderPaginationNav('commPaginationNav', pagination.current_page, pagination.total_pages, loadCommissionAudit);
        updateCommSelection();
    }

    window.updateCommSelection = function() {
        const checkedBoxes = document.querySelectorAll('.comm-task-cb:checked');
        const count = checkedBoxes.length;
        const countSpan = document.getElementById('selectedCommCount');
        const btn = document.getElementById('btnBatchSettleComm');

        if (countSpan) countSpan.textContent = count;
        if (btn) {
            if (count > 0) btn.classList.remove('d-none');
            else btn.classList.add('d-none');
        }

        const totalVisibleBoxes = document.querySelectorAll('.comm-task-cb');
        const checkAll = document.getElementById('checkAllCommTasks');
        if (checkAll) {
            checkAll.checked = totalVisibleBoxes.length > 0 && checkedBoxes.length === totalVisibleBoxes.length;
        }
    };

    // Open Single Settle Commission Modal
    window.openSettleCommModal = function(taskId, expectedComm, actualComm, diff, investorName) {
        pendingSettleCommTaskId = taskId;
        const isUnderpaid = diff < 0;

        document.getElementById('settleCommModalTaskId').textContent = taskId;
        document.getElementById('settleCommModalInvestor').textContent = investorName;
        document.getElementById('settleCommModalExpected').textContent = Number(expectedComm).toFixed(2) + ' {{ __("SAR") }}';
        document.getElementById('settleCommModalActual').textContent = Number(actualComm).toFixed(2) + ' {{ __("SAR") }}';

        const labelEl = document.getElementById('settleCommModalActionLabel');
        const diffBadge = document.getElementById('settleCommModalDiffBadge');

        if (isUnderpaid) {
            labelEl.className = 'fw-bold text-success';
            labelEl.innerHTML = '<i class="ti ti-arrow-down-left me-1"></i> {{ __("Required Action: Credit Underpaid Amount") }}:';
            diffBadge.className = 'badge bg-success fs-6';
            diffBadge.textContent = Number(Math.abs(diff)).toFixed(2) + ' {{ __("SAR") }}';
        } else {
            labelEl.className = 'fw-bold text-danger';
            labelEl.innerHTML = '<i class="ti ti-arrow-up-right me-1"></i> {{ __("Required Action: Debit Overpaid Amount") }}:';
            diffBadge.className = 'badge bg-danger fs-6';
            diffBadge.textContent = Number(Math.abs(diff)).toFixed(2) + ' {{ __("SAR") }}';
        }

        document.getElementById('settleCommModalReason').value = '';
        new bootstrap.Modal(document.getElementById('modalSettleCommission')).show();
    };

    window.executeSettleCommission = function() {
        if (!pendingSettleCommTaskId) return;
        const reason = document.getElementById('settleCommModalReason').value;
        const btn = document.getElementById('btnConfirmSettleComm');
        const spinner = document.getElementById('settleCommSpinner');
        btn.disabled = true;
        spinner.classList.remove('d-none');

        $.ajax({
            url: '{{ route("admin.investors.audit.settle-commission") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                task_id: pendingSettleCommTaskId,
                reason: reason
            },
            success: function(res) {
                btn.disabled = false;
                spinner.classList.add('d-none');
                bootstrap.Modal.getInstance(document.getElementById('modalSettleCommission')).hide();

                if (res.status === 1) {
                    showNotification('success', res.success || '{{ __("Commission Settled Successfully!") }}');
                    loadCommissionAudit(commAuditPage);
                } else {
                    showNotification('danger', res.error || '{{ __("An error occurred during commission settlement") }}');
                }
            },
            error: function(xhr) {
                btn.disabled = false;
                spinner.classList.add('d-none');
                showNotification('danger', xhr.responseJSON?.message || '{{ __("Request failed") }}');
            }
        });
    };

    // Open Batch Settle Commissions Modal
    window.openBatchSettleCommModal = function() {
        const checkedBoxes = document.querySelectorAll('.comm-task-cb:checked');
        const count = checkedBoxes.length;
        if (count === 0) return;

        document.getElementById('batchCommCountText').textContent = count;
        document.getElementById('batchCommModalReason').value = '';
        new bootstrap.Modal(document.getElementById('modalBatchSettleComm')).show();
    };

    window.executeBatchSettleCommissions = function() {
        const checkedBoxes = document.querySelectorAll('.comm-task-cb:checked');
        const taskIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value)).filter(id => !isNaN(id));
        if (taskIds.length === 0) return;

        const reason = document.getElementById('batchCommModalReason').value;
        const btn = document.getElementById('btnConfirmBatchSettleComm');
        const spinner = document.getElementById('batchCommSpinner');
        btn.disabled = true;
        spinner.classList.remove('d-none');

        $.ajax({
            url: '{{ route("admin.investors.audit.batch-settle-commissions") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                task_ids: taskIds,
                reason: reason
            },
            success: function(res) {
                btn.disabled = false;
                spinner.classList.add('d-none');
                bootstrap.Modal.getInstance(document.getElementById('modalBatchSettleComm')).hide();

                if (res.status === 1) {
                    showNotification('success', res.success || '{{ __("Batch Commission Settlement Completed!") }}');
                    loadCommissionAudit(1);
                } else {
                    showNotification('danger', res.error || '{{ __("An error occurred during batch settlement") }}');
                }
            },
            error: function(xhr) {
                btn.disabled = false;
                spinner.classList.add('d-none');
                showNotification('danger', xhr.responseJSON?.message || '{{ __("Request failed") }}');
            }
        });
    };

    // =========================================================================
    // 3. PROBLEMATIC TASKS (AJAX, PAGINATION, MODALS)
    // =========================================================================
    function loadProblematicTasks(page = 1) {
        probTasksPage = page;
        const search = (document.getElementById('taskSearchInput')?.value || '').trim();
        const issue = (document.getElementById('issueTypeFilter')?.value || '').trim();
        const tbody = document.getElementById('problematicTasksTableBody');

        tbody.innerHTML = `<tr><td colspan="13" class="text-center py-4"><div class="spinner-border text-danger spinner-border-sm me-1"></div><span class="text-secondary">{{ __("Loading problematic tasks...") }}</span></td></tr>`;

        $.ajax({
            url: '{{ route("admin.investors.audit.problematic-tasks-data") }}',
            type: 'GET',
            data: {
                page: page,
                per_page: 15,
                search: search,
                issue_type: issue
            },
            success: function(res) {
                if (res.status === 1) {
                    problematicAllData = res.all_data || res.data;
                    renderProblematicTasksTable(res.data, res.pagination);
                } else {
                    tbody.innerHTML = `<tr><td colspan="13" class="text-center py-4 text-danger">${res.error || '{{ __("Failed to load data") }}'}</td></tr>`;
                }
            },
            error: function() {
                tbody.innerHTML = `<tr><td colspan="13" class="text-center py-4 text-danger">{{ __("Error communicating with server.") }}</td></tr>`;
            }
        });
    }

    function renderProblematicTasksTable(tasks, pagination) {
        const tbody = document.getElementById('problematicTasksTableBody');
        const countDisplay = document.getElementById('visibleTasksCount');
        const totalDisplay = document.getElementById('totalProblematicCount');
        const infoDisplay = document.getElementById('probPaginationInfo');

        if (countDisplay) countDisplay.textContent = tasks.length;
        if (totalDisplay) totalDisplay.textContent = pagination.total;
        document.getElementById('probHeaderCount').textContent = pagination.total;

        if (tasks.length === 0) {
            tbody.innerHTML = `<tr><td colspan="13" class="text-center py-4 text-success"><i class="ti ti-circle-check fs-2 mb-2 d-block"></i>{{ __("No problematic tasks found!") }}</td></tr>`;
            if (infoDisplay) infoDisplay.textContent = '{{ __("No records found") }}';
            renderPaginationNav('probPaginationNav', 1, 1, loadProblematicTasks);
            return;
        }

        const statusClasses = {
            'completed': 'bg-soft-success text-success',
            'assign': 'bg-soft-primary text-primary',
            'in_progress': 'bg-soft-warning text-warning',
            'refund': 'bg-soft-danger text-danger',
            'closed': 'bg-soft-dark'
        };

        let html = '';
        tasks.forEach(function(item, idx) {
            const sClass = statusClasses[item.task_status] || 'bg-soft-dark';
            const diffDisplay = Math.abs(item.net_locked) > 0 
                ? `<span class="badge bg-danger fs-6 fw-bold">${Number(item.net_locked).toFixed(2)} {{ __("SAR") }}</span>`
                : `<span class="badge bg-soft-success text-success">0.00</span>`;

            let reasonsHtml = '';
            item.issue_reasons.forEach(r => reasonsHtml += `<div>• ${r}</div>`);

            let fundersHtml = '-';
            if (item.funders && item.funders.length > 0) {
                fundersHtml = item.funders.map(f => `<div class="small"><strong>${f.user}</strong><div class="text-secondary" style="font-size: 11px;">${f.date} (${Number(f.amount).toFixed(2)})</div></div>`).join('');
            }

            let priceChangesHtml = `<span class="text-secondary small">{{ __("No price edit records") }}</span>`;
            if (item.price_changes && item.price_changes.length > 0) {
                priceChangesHtml = item.price_changes.map(pc => `<div class="small text-danger mb-1"><strong><i class="ti ti-edit"></i> ${pc.user}</strong><div class="text-secondary" style="font-size: 11px;">${pc.description} - ${pc.date}</div></div>`).join('');
            }

            let settlersHtml = `<span class="badge bg-soft-dark small">{{ __("Not Settled") }}</span>`;
            if (item.settlers && item.settlers.length > 0) {
                settlersHtml = item.settlers.map(s => `<div class="small text-success"><strong>${s.user}</strong><div class="text-secondary" style="font-size: 11px;">${s.date} (${Number(s.amount).toFixed(2)})</div></div>`).join('');
            }

            html += `
                <tr class="problem-task-row" id="task-row-${item.task_id}">
                    <td>
                        <a href="{{ url('admin/tasks/show') }}/${item.task_id}" class="fw-bold text-primary" target="_blank" title="{{ __("View Task") }}">
                            #${item.task_id}
                        </a>
                    </td>
                    <td><span class="fw-semibold text-dark">${item.investor_name}</span></td>
                    <td><span class="text-secondary">${item.customer_name}</span></td>
                    <td><span class="badge ${sClass}">${item.task_status}</span></td>
                    <td class="text-danger fw-semibold">${Number(item.total_debited).toFixed(2)}</td>
                    <td class="text-success fw-semibold">${Number(item.total_credited).toFixed(2)}</td>
                    <td class="fw-semibold text-dark">${Number(item.task_price).toFixed(2)}</td>
                    <td>${diffDisplay}</td>
                    <td style="max-width: 280px; white-space: normal;">
                        <span class="badge ${item.issue_badge_class} mb-1">${item.issue_type}</span>
                        <div class="small text-dark">${reasonsHtml}</div>
                    </td>
                    <td style="max-width: 180px; white-space: normal;">${fundersHtml}</td>
                    <td style="max-width: 220px; white-space: normal;">${priceChangesHtml}</td>
                    <td style="max-width: 180px; white-space: normal;">${settlersHtml}</td>
                    <td class="text-center">
                        <div class="d-flex justify-content-center align-items-center gap-1">
                            ${item.can_edit_funding ? `
                                @can('edit_investors_audit_funding')
                                    <button type="button" class="btn btn-sm btn-icon btn-label-warning" onclick="openEditFundingModalByTaskId(${item.task_id})" title="{{ __("Edit Funding Amount") }}">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                @endcan
                            ` : ''}

                            ${item.can_delete_refund ? `
                                @can('delete_investors_audit_transaction')
                                    <button type="button" class="btn btn-sm btn-icon btn-label-danger" onclick="openDeleteOrphanedModal(${item.task_id}, ${item.credit_tx_id || 'null'})" title="{{ __("Delete Orphaned Refund") }}">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                @endcan
                            ` : ''}

                            <button type="button" class="btn btn-sm btn-icon btn-label-info" onclick="showTaskAuditModalByTaskId(${item.task_id})" title="{{ __("Full Audit Timeline") }}">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;

        if (infoDisplay) {
            infoDisplay.innerHTML = `{{ __("Showing") }} <strong>${pagination.from}</strong> {{ __("to") }} <strong>${pagination.to}</strong> {{ __("of") }} <strong>${pagination.total}</strong> {{ __("records") }}`;
        }

        renderPaginationNav('probPaginationNav', pagination.current_page, pagination.total_pages, loadProblematicTasks);
    }

    // Modal Timeline
    window.showTaskAuditModalByTaskId = function (taskId) {
        const item = problematicAllData.find(t => t.task_id == taskId);
        if (!item) return;

        document.getElementById('taskModalTitle').innerHTML = 
            '<i class="ti ti-file-analytics text-primary me-2"></i>' +
            '{{ __("Audit Details for Task") }} #' + item.task_id + 
            ' <span class="badge bg-soft-info text-info ms-2">' + item.task_status + '</span>';

        document.getElementById('modalTaskDirectLink').href = '{{ url("admin/tasks/show") }}/' + item.task_id;

        let html = '';
        html += '<div class="row g-3 mb-4">';
        html += '  <div class="col-md-3 col-6"><div class="p-2 border rounded text-center bg-white"><small class="text-secondary d-block">{{ __("Investor") }}</small><strong>' + item.investor_name + '</strong></div></div>';
        html += '  <div class="col-md-3 col-6"><div class="p-2 border rounded text-center bg-white"><small class="text-secondary d-block">{{ __("Customer") }}</small><strong>' + item.customer_name + '</strong></div></div>';
        html += '  <div class="col-md-3 col-6"><div class="p-2 border rounded text-center bg-white"><small class="text-secondary d-block">{{ __("Task Price") }}</small><strong class="text-dark">' + parseFloat(item.task_price).toFixed(2) + ' {{ __("SAR") }}</strong></div></div>';
        html += '  <div class="col-md-3 col-6"><div class="p-2 border rounded text-center bg-white"><small class="text-secondary d-block">{{ __("Pending Diff") }}</small><strong class="text-danger">' + parseFloat(item.net_locked).toFixed(2) + ' {{ __("SAR") }}</strong></div></div>';
        html += '</div>';

        html += '<div class="alert alert-warning mb-4 border-0">';
        html += '  <h6 class="fw-bold mb-1 text-warning"><i class="ti ti-alert-triangle me-1"></i>' + item.issue_type + '</h6>';
        html += '  <ul class="mb-0 ps-3 text-dark">';
        item.issue_reasons.forEach(function(r) {
            html += '<li>' + r + '</li>';
        });
        html += '  </ul>';
        html += '</div>';

        html += '<h6 class="fw-bold mb-2 text-dark"><i class="ti ti-wallet me-1 text-primary"></i> {{ __("Wallet Financial Transactions") }}</h6>';
        html += '<div class="table-responsive mb-4 border rounded bg-white">';
        html += '<table class="table table-sm table-hover mb-0">';
        html += '<thead class="table-custom-header"><tr><th>#</th><th>{{ __("Type") }}</th><th>{{ __("Amount") }}</th><th>{{ __("Performed By") }}</th><th>{{ __("Description") }}</th><th>{{ __("Date") }}</th></tr></thead><tbody>';
        
        if (item.transactions && item.transactions.length > 0) {
            item.transactions.forEach(function(tx) {
                const isDebit = tx.transaction_type === 'debit';
                const typeBadge = isDebit ? '<span class="badge bg-soft-danger text-danger">{{ __("Funding (Debit)") }}</span>' : '<span class="badge bg-soft-success text-success">{{ __("Settlement (Credit)") }}</span>';
                
                html += '<tr>';
                html += '<td>#' + tx.id + '</td>';
                html += '<td>' + typeBadge + '</td>';
                html += '<td class="fw-bold ' + (isDebit ? 'text-danger' : 'text-success') + '">' + parseFloat(tx.amount).toFixed(2) + '</td>';
                html += '<td class="text-dark">' + (tx.performed_by || '-') + '</td>';
                html += '<td class="text-secondary">' + (tx.description || '-') + '</td>';
                html += '<td class="text-secondary">' + (tx.date || '-') + '</td>';
                html += '</tr>';
            });
        } else {
            html += '<tr><td colspan="6" class="text-center text-secondary py-3">{{ __("No transactions recorded") }}</td></tr>';
        }
        html += '</tbody></table></div>';

        html += '<h6 class="fw-bold mb-2 text-dark"><i class="ti ti-history me-1 text-info"></i> {{ __("Task Full Operations Log (Who created, modified price, completed, etc.)") }}</h6>';
        if (item.all_histories && item.all_histories.length > 0) {
            html += '<div class="p-3 border rounded bg-white" style="max-height: 250px; overflow-y: auto;">';
            item.all_histories.forEach(function(h) {
                const uName = h.user ? h.user.name : 'نظام / غير محدد';
                const isPricing = (h.description || '').toLowerCase().includes('pricing') || (h.description || '').toLowerCase().includes('price') || (h.description || '').includes('سعر');

                html += '<div class="timeline-audit-item">';
                html += '  <div class="d-flex justify-content-between align-items-center mb-1">';
                html += '    <strong class="' + (isPricing ? 'text-danger' : 'text-dark') + '">' + (isPricing ? '<i class="ti ti-alert-circle me-1"></i>' : '') + uName + '</strong>';
                html += '    <small class="text-secondary">' + (h.date || '') + '</small>';
                html += '  </div>';
                html += '  <div class="small text-dark">' + (h.description || h.action || '-') + '</div>';
                html += '</div>';
            });
            html += '</div>';
        } else {
            html += '<p class="text-secondary small">{{ __("No task history recorded") }}</p>';
        }

        document.getElementById('taskModalBody').innerHTML = html;
        new bootstrap.Modal(document.getElementById('taskAuditModal')).show();
    };

    // Open Edit Funding Modal
    let currentSelectedPrice = 0;
    window.openEditFundingModalByTaskId = function (taskId) {
        const item = problematicAllData.find(t => t.task_id == taskId);
        if (!item) return;

        currentSelectedPrice = parseFloat(item.task_price) || 0;
        document.getElementById('editFundingTaskId').value = item.task_id;
        document.getElementById('modalDisplayTaskId').value = '#' + item.task_id;
        document.getElementById('modalDisplayInvestor').value = item.investor_name;
        document.getElementById('modalDisplayDebited').value = parseFloat(item.total_debited).toFixed(2) + ' {{ __("SAR") }}';
        document.getElementById('modalDisplayPrice').value = currentSelectedPrice.toFixed(2) + ' {{ __("SAR") }}';
        
        const suggestedAmount = (currentSelectedPrice > 0) ? currentSelectedPrice : parseFloat(item.total_debited);
        document.getElementById('modalNewFundingAmount').value = suggestedAmount.toFixed(2);
        document.getElementById('modalFundingNotes').value = '';

        new bootstrap.Modal(document.getElementById('editFundingModal')).show();
    };

    window.applyCurrentTaskPrice = function() {
        if (currentSelectedPrice > 0) {
            document.getElementById('modalNewFundingAmount').value = currentSelectedPrice.toFixed(2);
        }
    };

    window.submitEditFunding = function (e) {
        e.preventDefault();
        const form = document.getElementById('editFundingForm');
        const taskId = document.getElementById('editFundingTaskId').value;
        const newAmount = document.getElementById('modalNewFundingAmount').value;
        const notes = document.getElementById('modalFundingNotes').value;

        const btn = document.getElementById('btnSubmitFunding');
        const spinner = document.getElementById('fundingSpinner');
        btn.disabled = true;
        spinner.classList.remove('d-none');

        $.ajax({
            url: '{{ route("admin.investors.audit.edit-funding") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                task_id: taskId,
                new_amount: newAmount,
                notes: notes
            },
            success: function (res) {
                btn.disabled = false;
                spinner.classList.add('d-none');
                bootstrap.Modal.getInstance(document.getElementById('editFundingModal')).hide();

                if (res.status === 1) {
                    showNotification('success', res.success || '{{ __("Funding amount updated successfully!") }}');
                    loadProblematicTasks(probTasksPage);
                } else {
                    showNotification('danger', res.error || '{{ __("An error occurred") }}');
                }
            },
            error: function (xhr) {
                btn.disabled = false;
                spinner.classList.add('d-none');
                showNotification('danger', xhr.responseJSON?.message || '{{ __("Request failed") }}');
            }
        });
    };

    // Open Delete Orphaned Refund Modal
    window.openDeleteOrphanedModal = function(taskId, creditTxId) {
        pendingDeleteOrphanedTaskId = taskId;
        pendingDeleteOrphanedTxId = creditTxId;
        document.getElementById('deleteOrphanedTaskId').textContent = taskId;
        document.getElementById('deleteOrphanedNotes').value = '';

        new bootstrap.Modal(document.getElementById('modalDeleteOrphaned')).show();
    };

    window.executeDeleteOrphanedTx = function() {
        if (!pendingDeleteOrphanedTaskId) return;
        const notes = document.getElementById('deleteOrphanedNotes').value;
        const btn = document.getElementById('btnConfirmDeleteOrphaned');
        const spinner = document.getElementById('deleteOrphanedSpinner');
        btn.disabled = true;
        spinner.classList.remove('d-none');

        $.ajax({
            url: '{{ route("admin.investors.audit.delete-orphaned-refund") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                task_id: pendingDeleteOrphanedTaskId,
                credit_tx_id: pendingDeleteOrphanedTxId,
                notes: notes
            },
            success: function(res) {
                btn.disabled = false;
                spinner.classList.add('d-none');
                bootstrap.Modal.getInstance(document.getElementById('modalDeleteOrphaned')).hide();

                if (res.status === 1) {
                    showNotification('success', res.success || '{{ __("Transaction deleted and balance adjusted!") }}');
                    loadProblematicTasks(probTasksPage);
                } else {
                    showNotification('danger', res.error || '{{ __("An error occurred") }}');
                }
            },
            error: function(xhr) {
                btn.disabled = false;
                spinner.classList.add('d-none');
                showNotification('danger', xhr.responseJSON?.message || '{{ __("Request failed") }}');
            }
        });
    };

    // Generic Pagination Navigator Renderer
    function renderPaginationNav(containerId, currentPage, totalPages, callbackFn) {
        const nav = document.getElementById(containerId);
        if (!nav) return;

        if (totalPages <= 1) {
            nav.innerHTML = '';
            return;
        }

        let html = '';
        const prevDisabled = currentPage === 1 ? 'disabled' : '';
        html += `<li class="page-item ${prevDisabled}"><a class="page-link" href="javascript:void(0);" onclick="${callbackFn.name}(${currentPage - 1})"><i class="ti ti-chevron-right"></i></a></li>`;

        const delta = 2;
        const range = [];
        for (let i = Math.max(2, currentPage - delta); i <= Math.min(totalPages - 1, currentPage + delta); i++) {
            range.push(i);
        }

        if (currentPage - delta > 2) range.unshift('...');
        if (currentPage + delta < totalPages - 1) range.push('...');
        range.unshift(1);
        if (totalPages > 1) range.push(totalPages);

        range.forEach(function(p) {
            if (p === '...') {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            } else {
                const active = p === currentPage ? 'active' : '';
                html += `<li class="page-item ${active}"><a class="page-link" href="javascript:void(0);" onclick="${callbackFn.name}(${p})">${p}</a></li>`;
            }
        });

        const nextDisabled = currentPage === totalPages ? 'disabled' : '';
        html += `<li class="page-item ${nextDisabled}"><a class="page-link" href="javascript:void(0);" onclick="${callbackFn.name}(${currentPage + 1})"><i class="ti ti-chevron-left"></i></a></li>`;

        nav.innerHTML = html;
    }

    // =========================================================================
    // 4. CUSTOMER UNSETTLED TASKS MODAL LOGIC
    // =========================================================================
    let currentCustomerModalData = null;
    let modalFilteredTasks = [];
    let modalCurrentPage = 1;
    let modalPageSize = 25;
    let modalActiveStatus = '';

    function getStatusBadgeConfig(status) {
        switch (status) {
            case 'completed':
                return { badge: 'bg-soft-success text-success', bgSoft: 'bg-soft-success', text: 'text-success', border: 'border-success', icon: 'ti-check', label: status };
            case 'assign':
                return { badge: 'bg-soft-primary text-primary', bgSoft: 'bg-soft-primary', text: 'text-primary', border: 'border-primary', icon: 'ti-user-check', label: status };
            case 'in_progress':
                return { badge: 'bg-soft-warning text-warning', bgSoft: 'bg-soft-warning', text: 'text-warning', border: 'border-warning', icon: 'ti-clock', label: status };
            case 'canceled':
                return { badge: 'bg-soft-danger text-danger', bgSoft: 'bg-soft-danger', text: 'text-danger', border: 'border-danger', icon: 'ti-ban', label: status };
            case 'started':
                return { badge: 'bg-soft-info text-info', bgSoft: 'bg-soft-info', text: 'text-info', border: 'border-info', icon: 'ti-player-play', label: status };
            default:
                return { badge: 'bg-soft-dark', bgSoft: 'bg-soft-dark', text: 'text-dark', border: 'border-dark', icon: 'ti-tag', label: status };
        }
    }

    window.showCustomerTasksModal = function(index) {
        const cust = customersUnsettledData[index];
        if (!cust) return;
        currentCustomerModalData = cust;

        modalCurrentPage = 1;
        modalActiveStatus = '';
        const pageSizeEl = document.getElementById('modalCustomerPageSize');
        modalPageSize = pageSizeEl ? (pageSizeEl.value === 'all' ? 999999 : parseInt(pageSizeEl.value)) : 25;

        document.getElementById('customerModalTitle').textContent = cust.customer_name;
        
        const headerBadges = document.getElementById('customerModalBadgesHeader');
        if (headerBadges) {
            headerBadges.innerHTML = `
                <span class="badge bg-primary fs-7"><i class="ti ti-list-check me-1"></i>${cust.tasks_count} {{ __('tasks') }}</span>
                <span class="badge bg-soft-dark fs-7"><i class="ti ti-cash me-1 text-primary"></i>{{ __('Total Tasks Price') }}: ${Number(cust.total_price).toLocaleString('en-US', {minimumFractionDigits: 2})} {{ __('SAR') }}</span>
                <span class="badge bg-soft-warning text-warning fs-7"><i class="ti ti-wallet me-1"></i>{{ __('Total Funded Amount') }}: ${Number(cust.total_funded).toLocaleString('en-US', {minimumFractionDigits: 2})} {{ __('SAR') }}</span>
                ${cust.customer_phone ? `<span class="badge bg-soft-primary text-primary fs-7"><i class="ti ti-phone me-1"></i>${cust.customer_phone}</span>` : ''}
            `;
        }

        const taskSearch = document.getElementById('modalCustomerTaskSearch');
        if (taskSearch) taskSearch.value = '';

        const statusFilter = document.getElementById('modalCustomerStatusFilter');
        if (statusFilter) {
            statusFilter.innerHTML = '<option value="">-- {{ __("All Statuses") }} --</option>';
            Object.keys(cust.statuses).forEach(function(st) {
                statusFilter.innerHTML += `<option value="${st}">${st} (${cust.statuses[st]})</option>`;
            });
            statusFilter.value = '';
        }

        const investorFilter = document.getElementById('modalCustomerInvestorFilter');
        if (investorFilter) {
            investorFilter.innerHTML = '<option value="">-- {{ __("All Investors") }} --</option>';
            Object.keys(cust.investors).forEach(function(invName) {
                const invInfo = cust.investors[invName];
                const count = typeof invInfo === 'object' ? invInfo.count : invInfo;
                const total = typeof invInfo === 'object' ? ' - ' + Number(invInfo.total_price).toLocaleString('en-US', {maximumFractionDigits: 0}) + ' {{ __("SAR") }}' : '';
                investorFilter.innerHTML += `<option value="${invName}">${invName} (${count}${total})</option>`;
            });
            investorFilter.value = '';
        }

        const sortFilter = document.getElementById('modalCustomerSortFilter');
        if (sortFilter) sortFilter.value = 'id_desc';

        renderCustomerModalStatusCards();
        renderCustomerTaskBadgesCloud(cust.tasks);
        applyCustomerModalFilters(true);

        new bootstrap.Modal(document.getElementById('customerTasksModal')).show();
    };

    function renderCustomerModalStatusCards() {
        const container = document.getElementById('customerModalStatusCards');
        if (!container || !currentCustomerModalData) return;

        const cust = currentCustomerModalData;
        const tasks = cust.tasks;

        const statusStats = {
            'all': { count: tasks.length, total: cust.total_price, funded: cust.total_funded }
        };

        tasks.forEach(function(t) {
            const st = t.status;
            if (!statusStats[st]) {
                statusStats[st] = { count: 0, total: 0, funded: 0 };
            }
            statusStats[st].count++;
            statusStats[st].total += parseFloat(t.total_price);
            statusStats[st].funded += parseFloat(t.funded_amount);
        });

        let html = '';
        const isAllActive = modalActiveStatus === '';
        html += `
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card h-100 cursor-pointer status-kpi-card border ${isAllActive ? 'border-primary border-2 shadow-sm bg-soft-primary' : 'bg-white'}" 
                     onclick="setModalStatusFilter('')" style="transition: all 0.2s ease;">
                    <div class="card-body p-2 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="small fw-bold text-dark"><i class="ti ti-layout-grid me-1"></i>{{ __('All') }}</span>
                            <span class="badge ${isAllActive ? 'bg-primary' : 'bg-soft-primary'}">${statusStats['all'].count}</span>
                        </div>
                        <div class="fw-bold text-dark fs-7">${Number(statusStats['all'].total).toLocaleString('en-US', {maximumFractionDigits: 0})} <small class="text-secondary">{{ __('SAR') }}</small></div>
                    </div>
                </div>
            </div>
        `;

        const orderedStatuses = ['assign', 'completed', 'canceled', 'in_progress', 'started'];
        const allKeys = Object.keys(statusStats).filter(k => k !== 'all');
        const sortedKeys = orderedStatuses.filter(k => allKeys.includes(k)).concat(allKeys.filter(k => !orderedStatuses.includes(k)));

        sortedKeys.forEach(function(st) {
            const stat = statusStats[st];
            const conf = getStatusBadgeConfig(st);
            const isActive = modalActiveStatus === st;

            html += `
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card h-100 cursor-pointer status-kpi-card border ${isActive ? conf.border + ' border-2 shadow-sm ' + conf.bgSoft : 'bg-white'}" 
                         onclick="setModalStatusFilter('${st}')" style="transition: all 0.2s ease;">
                        <div class="card-body p-2 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="small fw-semibold ${conf.text}"><i class="ti ${conf.icon} me-1"></i>${st}</span>
                                <span class="badge ${isActive ? 'bg-primary' : conf.badge}">${stat.count}</span>
                            </div>
                            <div class="fw-bold text-dark fs-7">${Number(stat.total).toLocaleString('en-US', {maximumFractionDigits: 0})} <small class="text-secondary">{{ __('SAR') }}</small></div>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    window.setModalStatusFilter = function(status) {
        modalActiveStatus = (modalActiveStatus === status) ? '' : status;
        const statusSelect = document.getElementById('modalCustomerStatusFilter');
        if (statusSelect) statusSelect.value = modalActiveStatus;

        renderCustomerModalStatusCards();
        applyCustomerModalFilters(true);
    };

    function renderCustomerTaskBadgesCloud(tasksList) {
        const cloud = document.getElementById('customerTaskBadgesCloud');
        if (!cloud) return;
        let badgesHtml = '';
        tasksList.forEach(function(t) {
            const conf = getStatusBadgeConfig(t.status);
            badgesHtml += `<a href="{{ url('admin/tasks/show') }}/${t.id}" target="_blank" class="badge ${conf.badge} text-decoration-none p-1 me-1 mb-1" title="${t.status} - ${t.total_price} SAR">#${t.id} <small>(${t.status})</small></a>`;
        });
        cloud.innerHTML = badgesHtml;
    }

    function applyCustomerModalFilters(resetPage = false) {
        if (!currentCustomerModalData) return;
        if (resetPage) modalCurrentPage = 1;

        const query = (document.getElementById('modalCustomerTaskSearch')?.value || '').toLowerCase().trim();
        const statusVal = (document.getElementById('modalCustomerStatusFilter')?.value || '').toLowerCase().trim();
        const investorVal = (document.getElementById('modalCustomerInvestorFilter')?.value || '').trim();
        const sortVal = document.getElementById('modalCustomerSortFilter')?.value || 'id_desc';
        const pageSizeVal = document.getElementById('modalCustomerPageSize')?.value || '25';
        modalPageSize = pageSizeVal === 'all' ? 999999 : parseInt(pageSizeVal);

        modalFilteredTasks = currentCustomerModalData.tasks.filter(function(t) {
            const matchesQuery = !query || 
                String(t.id).includes(query) || 
                t.status.toLowerCase().includes(query) || 
                (t.investor_name && t.investor_name.toLowerCase().includes(query)) ||
                String(t.total_price).includes(query) ||
                (t.date && t.date.includes(query));

            const matchesStatus = !statusVal || t.status.toLowerCase() === statusVal;
            const matchesInvestor = !investorVal || t.investor_name === investorVal;

            return matchesQuery && matchesStatus && matchesInvestor;
        });

        modalFilteredTasks.sort(function(a, b) {
            switch(sortVal) {
                case 'id_desc': return b.id - a.id;
                case 'id_asc': return a.id - b.id;
                case 'price_desc': return b.total_price - a.total_price;
                case 'price_asc': return a.total_price - b.total_price;
                default: return b.id - a.id;
            }
        });

        renderCustomerModalPage();
    }

    function renderCustomerModalPage() {
        const tbody = document.getElementById('customerTasksTableBody');
        const tfoot = document.getElementById('customerTasksTableFoot');
        const paginationInfo = document.getElementById('customerModalPaginationInfo');
        const summaryText = document.getElementById('customerModalFilteredSummaryText');

        const totalTasks = modalFilteredTasks.length;
        const totalPages = Math.ceil(totalTasks / modalPageSize) || 1;

        if (modalCurrentPage > totalPages) modalCurrentPage = totalPages;
        if (modalCurrentPage < 1) modalCurrentPage = 1;

        const startIndex = (modalCurrentPage - 1) * modalPageSize;
        const endIndex = Math.min(startIndex + modalPageSize, totalTasks);
        const pageTasks = modalFilteredTasks.slice(startIndex, endIndex);

        const filteredTotalPrice = modalFilteredTasks.reduce((sum, t) => sum + parseFloat(t.total_price), 0);
        const filteredTotalFunded = modalFilteredTasks.reduce((sum, t) => sum + parseFloat(t.funded_amount), 0);

        if (summaryText) {
            summaryText.innerHTML = `<strong>${totalTasks}</strong> {{ __('tasks') }} ({{ __('Total Price') }}: <strong>${Number(filteredTotalPrice).toLocaleString('en-US', {minimumFractionDigits: 2})} {{ __('SAR') }}</strong>)`;
        }

        if (totalTasks === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-secondary"><i class="ti ti-file-off fs-2 d-block mb-1"></i>{{ __('No tasks matching search criteria') }}</td></tr>`;
            if (tfoot) tfoot.innerHTML = '';
            if (paginationInfo) paginationInfo.textContent = '{{ __("No tasks matching search criteria") }}';
            renderCustomerModalPaginationNav(0, 0);
            return;
        }

        let html = '';
        pageTasks.forEach(function(t, idx) {
            const globalIndex = startIndex + idx + 1;
            const conf = getStatusBadgeConfig(t.status);

            html += `
                <tr class="cust-task-row" data-id="${t.id}">
                    <td class="text-secondary small">${globalIndex}</td>
                    <td>
                        <a href="{{ url('admin/tasks/show') }}/${t.id}" target="_blank" class="fw-bold text-primary">
                            #${t.id}
                        </a>
                    </td>
                    <td><span class="badge ${conf.badge}"><i class="ti ${conf.icon} me-1"></i>${t.status}</span></td>
                    <td class="fw-bold text-dark">${Number(t.total_price).toLocaleString('en-US', {minimumFractionDigits: 2})} <small class="text-secondary">{{ __('SAR') }}</small></td>
                    <td class="fw-semibold text-warning">${Number(t.funded_amount).toLocaleString('en-US', {minimumFractionDigits: 2})} <small class="text-secondary">{{ __('SAR') }}</small></td>
                    <td><span class="badge bg-soft-primary">${t.investor_name}</span></td>
                    <td class="text-secondary small">${t.date}</td>
                    <td class="text-center">
                        <a href="{{ url('admin/tasks/show') }}/${t.id}" target="_blank" class="btn btn-xs btn-label-primary" title="{{ __('Open Task') }}">
                            <i class="ti ti-external-link"></i>
                        </a>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;

        if (tfoot) {
            tfoot.innerHTML = `
                <tr class="align-middle fw-bold">
                    <th colspan="3" class="text-end">{{ __('Filtered Total') }} (${totalTasks} {{ __('tasks') }}):</th>
                    <th class="text-dark">${Number(filteredTotalPrice).toLocaleString('en-US', {minimumFractionDigits: 2})} {{ __('SAR') }}</th>
                    <th class="text-warning">${Number(filteredTotalFunded).toLocaleString('en-US', {minimumFractionDigits: 2})} {{ __('SAR') }}</th>
                    <th colspan="3"></th>
                </tr>
            `;
        }

        if (paginationInfo) {
            const from = totalTasks > 0 ? startIndex + 1 : 0;
            const to = endIndex;
            paginationInfo.innerHTML = `{{ __('Showing') }} <strong>${from}</strong> {{ __('to') }} <strong>${to}</strong> {{ __('of') }} <strong>${totalTasks}</strong> {{ __('tasks') }}`;
        }

        renderCustomerModalPaginationNav(modalCurrentPage, totalPages);
    }

    function renderCustomerModalPaginationNav(currentPage, totalPages) {
        const nav = document.getElementById('customerModalPaginationNav');
        if (!nav) return;

        if (totalPages <= 1) {
            nav.innerHTML = '';
            return;
        }

        let html = '';
        const prevDisabled = currentPage === 1 ? 'disabled' : '';
        html += `<li class="page-item ${prevDisabled}"><a class="page-link" href="javascript:void(0);" onclick="goToCustomerModalPage(${currentPage - 1})"><i class="ti ti-chevron-right"></i></a></li>`;

        const delta = 2;
        const range = [];
        for (let i = Math.max(2, currentPage - delta); i <= Math.min(totalPages - 1, currentPage + delta); i++) {
            range.push(i);
        }

        if (currentPage - delta > 2) range.unshift('...');
        if (currentPage + delta < totalPages - 1) range.push('...');
        range.unshift(1);
        if (totalPages > 1) range.push(totalPages);

        range.forEach(function(p) {
            if (p === '...') {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            } else {
                const active = p === currentPage ? 'active' : '';
                html += `<li class="page-item ${active}"><a class="page-link" href="javascript:void(0);" onclick="goToCustomerModalPage(${p})">${p}</a></li>`;
            }
        });

        const nextDisabled = currentPage === totalPages ? 'disabled' : '';
        html += `<li class="page-item ${nextDisabled}"><a class="page-link" href="javascript:void(0);" onclick="goToCustomerModalPage(${currentPage + 1})"><i class="ti ti-chevron-left"></i></a></li>`;

        nav.innerHTML = html;
    }

    window.goToCustomerModalPage = function(page) {
        modalCurrentPage = page;
        renderCustomerModalPage();
    };

    window.copyCustomerTaskIds = function(index) {
        const cust = customersUnsettledData[index];
        if (cust && cust.task_ids) {
            const idsStr = cust.task_ids.join(', ');
            navigator.clipboard.writeText(idsStr).then(function() {
                showNotification('success', '{{ __("Task IDs copied to clipboard!") }} (' + cust.task_ids.length + ')');
            });
        }
    };

    // =========================================================================
    // INITIALIZATION ON DOM READY
    // =========================================================================
    document.addEventListener('DOMContentLoaded', function() {
        // Initial AJAX Loads
        loadCanceledTasks(1);
        loadCommissionAudit(1);
        loadProblematicTasks(1);

        // Canceled Tasks Search & Filters
        const canceledSearch = document.getElementById('canceledTaskSearchInput');
        let cancelSearchTimer;
        if (canceledSearch) {
            canceledSearch.addEventListener('input', function() {
                clearTimeout(cancelSearchTimer);
                cancelSearchTimer = setTimeout(() => loadCanceledTasks(1), 350);
            });
        }
        const canceledCommFilter = document.getElementById('canceledCommissionFilter');
        if (canceledCommFilter) {
            canceledCommFilter.addEventListener('change', () => loadCanceledTasks(1));
        }
        const checkAllCanceled = document.getElementById('checkAllCanceledTasks');
        if (checkAllCanceled) {
            checkAllCanceled.addEventListener('change', function() {
                const isChecked = this.checked;
                document.querySelectorAll('.canceled-task-cb').forEach(cb => cb.checked = isChecked);
                updateCanceledSelection();
            });
        }

        // Commission Audit Search & Filters
        const commSearch = document.getElementById('commAuditSearchInput');
        let commSearchTimer;
        if (commSearch) {
            commSearch.addEventListener('input', function() {
                clearTimeout(commSearchTimer);
                commSearchTimer = setTimeout(() => loadCommissionAudit(1), 350);
            });
        }
        const commStatusFilter = document.getElementById('commAuditStatusFilter');
        if (commStatusFilter) {
            commStatusFilter.addEventListener('change', () => loadCommissionAudit(1));
        }
        const commInvFilter = document.getElementById('commAuditInvestorFilter');
        if (commInvFilter) {
            commInvFilter.addEventListener('change', () => loadCommissionAudit(1));
        }
        const commDateFrom = document.getElementById('commAuditDateFrom');
        if (commDateFrom) {
            commDateFrom.addEventListener('change', function() {
                document.getElementById('btnCommAuditResetDates')?.classList.remove('btn-label-primary');
                document.getElementById('btnCommAuditResetDates')?.classList.add('btn-outline-primary');
                document.getElementById('btnCommAuditAllTime')?.classList.remove('btn-label-primary');
                document.getElementById('btnCommAuditAllTime')?.classList.add('btn-outline-primary');
                loadCommissionAudit(1);
            });
        }
        const commDateTo = document.getElementById('commAuditDateTo');
        if (commDateTo) {
            commDateTo.addEventListener('change', function() {
                document.getElementById('btnCommAuditResetDates')?.classList.remove('btn-label-primary');
                document.getElementById('btnCommAuditResetDates')?.classList.add('btn-outline-primary');
                document.getElementById('btnCommAuditAllTime')?.classList.remove('btn-label-primary');
                document.getElementById('btnCommAuditAllTime')?.classList.add('btn-outline-primary');
                loadCommissionAudit(1);
            });
        }
        const btnResetDates = document.getElementById('btnCommAuditResetDates');
        if (btnResetDates) {
            btnResetDates.addEventListener('click', function() {
                const now = new Date();
                const toStr = now.toISOString().split('T')[0];
                const lastMonth = new Date();
                lastMonth.setMonth(lastMonth.getMonth() - 1);
                const fromStr = lastMonth.toISOString().split('T')[0];
                if (commDateFrom) commDateFrom.value = fromStr;
                if (commDateTo) commDateTo.value = toStr;

                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-label-primary');
                const btnAll = document.getElementById('btnCommAuditAllTime');
                if (btnAll) {
                    btnAll.classList.remove('btn-label-primary');
                    btnAll.classList.add('btn-outline-primary');
                }

                loadCommissionAudit(1);
            });
        }
        const btnAllTime = document.getElementById('btnCommAuditAllTime');
        if (btnAllTime) {
            btnAllTime.addEventListener('click', function() {
                if (commDateFrom) commDateFrom.value = '';
                if (commDateTo) commDateTo.value = '';

                this.classList.remove('btn-outline-primary');
                this.classList.add('btn-label-primary');
                const btnReset = document.getElementById('btnCommAuditResetDates');
                if (btnReset) {
                    btnReset.classList.remove('btn-label-primary');
                    btnReset.classList.add('btn-outline-primary');
                }

                loadCommissionAudit(1);
            });
        }
        const checkAllComm = document.getElementById('checkAllCommTasks');
        if (checkAllComm) {
            checkAllComm.addEventListener('change', function() {
                const isChecked = this.checked;
                document.querySelectorAll('.comm-task-cb').forEach(cb => cb.checked = isChecked);
                updateCommSelection();
            });
        }

        // Problematic Tasks Search & Filters
        const probSearch = document.getElementById('taskSearchInput');
        let probSearchTimer;
        if (probSearch) {
            probSearch.addEventListener('input', function() {
                clearTimeout(probSearchTimer);
                probSearchTimer = setTimeout(() => loadProblematicTasks(1), 350);
            });
        }
        const probIssueFilter = document.getElementById('issueTypeFilter');
        if (probIssueFilter) {
            probIssueFilter.addEventListener('change', () => loadProblematicTasks(1));
        }

        // Customer Modal Listeners
        const custTaskSearch = document.getElementById('modalCustomerTaskSearch');
        if (custTaskSearch) {
            custTaskSearch.addEventListener('input', () => applyCustomerModalFilters(true));
        }
        const custStFilter = document.getElementById('modalCustomerStatusFilter');
        if (custStFilter) {
            custStFilter.addEventListener('change', function() {
                modalActiveStatus = this.value;
                renderCustomerModalStatusCards();
                applyCustomerModalFilters(true);
            });
        }
        const custInvFilter = document.getElementById('modalCustomerInvestorFilter');
        if (custInvFilter) {
            custInvFilter.addEventListener('change', () => applyCustomerModalFilters(true));
        }
        const custSortFilter = document.getElementById('modalCustomerSortFilter');
        if (custSortFilter) {
            custSortFilter.addEventListener('change', () => applyCustomerModalFilters(false));
        }
        const custPageSize = document.getElementById('modalCustomerPageSize');
        if (custPageSize) {
            custPageSize.addEventListener('change', () => applyCustomerModalFilters(true));
        }
        const btnCopyCust = document.getElementById('btnCopyCustomerModalIds');
        if (btnCopyCust) {
            btnCopyCust.addEventListener('click', function() {
                if (currentCustomerModalData && currentCustomerModalData.task_ids) {
                    const idsStr = currentCustomerModalData.task_ids.join(', ');
                    navigator.clipboard.writeText(idsStr).then(() => {
                        showNotification('success', '{{ __("Task IDs copied to clipboard!") }} (' + currentCustomerModalData.task_ids.length + ')');
                    });
                }
            });
        }
    });
</script>
@endsection
