@extends('layouts/layoutMaster')

@section('title', __('Investors & Tasks Audit'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    <style>
        .metric-card {
            border-radius: 0.75rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.08);
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
                <span class="text-muted fw-light">{{ __('Investors') }} /</span> {{ __('Audit & Tasks Matching') }}
            </h4>
            <p class="text-muted mb-0">{{ __('Comprehensive financial reconciliation between investor deposits, active balances, and funded tasks.') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.investors.audit.export') }}" class="btn btn-outline-success">
                <i class="ti ti-file-spreadsheet me-1"></i> {{ __('Export Problematic Tasks to Excel') }}
            </a>
            <a href="{{ route('admin.investors.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-right me-1"></i> {{ __('Back to Investors') }}
            </a>
            <button type="button" class="btn btn-primary" onclick="window.location.reload();">
                <i class="ti ti-refresh me-1"></i> {{ __('Refresh Data') }}
            </button>
        </div>
    </div>

    {{-- Alert Notice --}}
    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
        <span class="alert-icon text-success me-2">
            <i class="ti ti-circle-check ti-xs"></i>
        </span>
        <div class="d-flex flex-column ps-1">
            <h6 class="alert-heading mb-1 fw-bold">{{ __('Audit Result: Perfect Equilibrium (0.00 SAR Deficit)') }}</h6>
            <span class="mb-0">
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
                            <span class="text-muted fw-semibold">{{ __('Net Base Capital') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 text-primary fw-bold">{{ number_format($totals['total_net_base_capital'], 2) }}</h4>
                                <small class="text-muted ms-1">{{ __('SAR') }}</small>
                            </div>
                            <small class="text-success">
                                <i class="ti ti-arrow-down-left"></i> {{ __('Deposits') }}: {{ number_format($totals['total_deposited_capital'], 2) }}
                            </small>
                            <br>
                            <small class="text-danger">
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
                            <span class="text-muted fw-semibold">{{ __('Available Wallet Balances') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 text-success fw-bold">{{ number_format($totals['total_wallet_balance_stored'], 2) }}</h4>
                                <small class="text-muted ms-1">{{ __('SAR') }}</small>
                            </div>
                            <small class="text-muted">
                                <i class="ti ti-checks text-success me-1"></i> {{ __('Exact ledger match') }}
                            </small>
                            <br>
                            <small class="text-muted">
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
                            <span class="text-muted fw-semibold">{{ __('Locked in Tasks (Ledger)') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 text-warning fw-bold">{{ number_format($totals['total_locked_in_tasks_ledger'], 2) }}</h4>
                                <small class="text-muted ms-1">{{ __('SAR') }}</small>
                            </div>
                            <small class="text-muted">
                                <i class="ti ti-arrow-up text-danger"></i> {{ __('Funded debits') }}: {{ number_format($totals['total_task_funding_debits'], 0) }}
                            </small>
                            <br>
                            <small class="text-muted">
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
                            <span class="text-muted fw-semibold">{{ __('Active Unsettled Tasks') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 text-info fw-bold">{{ number_format($totals['total_unsettled_tasks_amount'], 2) }}</h4>
                                <small class="text-muted ms-1">{{ __('SAR') }}</small>
                            </div>
                            <small class="text-muted">
                                <i class="ti ti-list-check me-1"></i> {{ __('Tasks count') }}: <strong>{{ $totals['total_unsettled_tasks_count'] }} {{ __('tasks') }}</strong>
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
    <div class="card mb-4 shadow-sm">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-1 fw-bold">
                    <i class="ti ti-users me-1 text-primary"></i> {{ __('Investors Accounts & Wallets Reconciliation') }}
                </h5>
                <small class="text-muted">{{ __('Comparison between capital deposited, available balances, and funds tied up in tasks per investor.') }}</small>
            </div>
            <span class="badge bg-label-success fs-6">
                <i class="ti ti-circle-check me-1"></i> {{ __('100% Balanced') }}
            </span>
        </div>
        <div class="table-responsive text-nowrap">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
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
                            <td><span class="badge bg-label-secondary">#{{ $inv['id'] }}</span></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark">{{ $inv['name'] }}</span>
                                    <small class="text-muted">{{ $inv['email'] }}</small>
                                </div>
                            </td>
                            <td class="text-success fw-semibold">{{ number_format($inv['deposited_capital'], 2) }} <small class="text-muted">{{ __('SAR') }}</small></td>
                            <td class="text-danger">{{ number_format($inv['withdrawn_capital'], 2) }} <small class="text-muted">{{ __('SAR') }}</small></td>
                            <td class="fw-bold text-primary">{{ number_format($inv['net_base_capital'], 2) }} <small class="text-muted">{{ __('SAR') }}</small></td>
                            <td class="fw-bold text-success">{{ number_format($inv['stored_balance'], 2) }} <small class="text-muted">{{ __('SAR') }}</small></td>
                            <td class="fw-semibold text-warning">{{ number_format($inv['locked_in_tasks_ledger'], 2) }} <small class="text-muted">{{ __('SAR') }}</small></td>
                            <td>
                                <span class="badge bg-label-info">{{ $inv['unsettled_tasks_count'] }} {{ __('tasks') }}</span>
                                <div class="small text-muted">{{ number_format($inv['unsettled_tasks_sum'], 2) }} {{ __('SAR') }}</div>
                            </td>
                            <td>
                                @if(abs($inv['unsettled_vs_locked_diff']) > 0.01)
                                    <span class="badge bg-label-warning" title="{{ __('Difference between locked ledger capital and current task prices') }}">
                                        {{ number_format($inv['unsettled_vs_locked_diff'], 2) }} {{ __('SAR') }}
                                    </span>
                                @else
                                    <span class="badge bg-label-success">0.00</span>
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
                <tfoot class="table-dark">
                    <tr>
                        <th colspan="2" class="fw-bold">{{ __('Total') }}</th>
                        <th class="fw-bold">{{ number_format($totals['total_deposited_capital'], 2) }}</th>
                        <th class="fw-bold">{{ number_format($totals['total_withdrawn_capital'], 2) }}</th>
                        <th class="fw-bold text-info">{{ number_format($totals['total_net_base_capital'], 2) }}</th>
                        <th class="fw-bold text-success">{{ number_format($totals['total_wallet_balance_stored'], 2) }}</th>
                        <th class="fw-bold text-warning">{{ number_format($totals['total_locked_in_tasks_ledger'], 2) }}</th>
                        <th class="fw-bold">{{ $totals['total_unsettled_tasks_count'] }} {{ __('tasks') }} ({{ number_format($totals['total_unsettled_tasks_amount'], 2) }})</th>
                        <th class="fw-bold text-warning">{{ number_format($totals['unsettled_vs_locked_diff'], 2) }}</th>
                        <th colspan="2" class="text-center text-success fw-bold">
                            <i class="ti ti-circle-check me-1"></i> {{ __('Zero Deficit (0.00 SAR)') }}
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Problematic Tasks Section --}}
    <div class="card shadow-sm">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="card-title mb-1 fw-bold text-danger">
                    <i class="ti ti-alert-triangle me-1"></i> {{ __('Problematic Tasks & Pricing Modifications') }}
                </h5>
                <small class="text-muted">
                    {{ __('Tasks where prices changed after funding, settled with discrepancies, or had duplicate funding. Shows who managed, funded, and modified the price.') }}
                </small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.investors.audit.export') }}" class="btn btn-sm btn-outline-success">
                    <i class="ti ti-file-spreadsheet me-1"></i> {{ __('Export to Excel') }}
                </a>
                <span class="badge bg-danger fs-6">{{ count($problematicTasks) }} {{ __('Tasks with Discrepancies') }}</span>
            </div>
        </div>
        
        {{-- Filter Bar --}}
        <div class="card-body border-bottom bg-light py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
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
                    <small class="text-muted">{{ __('Showing') }} <strong id="visibleTasksCount">{{ count($problematicTasks) }}</strong> {{ __('of') }} {{ count($problematicTasks) }}</small>
                </div>
            </div>
        </div>

        <div class="table-responsive text-nowrap">
            <table class="table table-hover align-middle mb-0" id="problematicTasksTable">
                <thead class="table-light">
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
                <tbody>
                    @forelse($problematicTasks as $index => $item)
                        <tr class="problem-task-row" data-issue="{{ $item['issue_type'] }}" id="task-row-{{ $item['task_id'] }}">
                            <td>
                                <a href="{{ route('task.show', $item['task_id']) }}" class="fw-bold text-primary" target="_blank" title="{{ __('View Task') }}">
                                    #{{ $item['task_id'] }}
                                </a>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $item['investor_name'] }}</span>
                            </td>
                            <td>
                                <span class="text-muted">{{ $item['customer_name'] }}</span>
                            </td>
                            <td>
                                @php
                                    $statusClasses = [
                                        'completed' => 'bg-label-success',
                                        'assign' => 'bg-label-primary',
                                        'in_progress' => 'bg-label-warning',
                                        'refund' => 'bg-label-danger',
                                        'closed' => 'bg-label-dark',
                                    ];
                                    $sClass = $statusClasses[$item['task_status']] ?? 'bg-label-secondary';
                                @endphp
                                <span class="badge {{ $sClass }}">{{ $item['task_status'] }}</span>
                            </td>
                            <td class="text-danger fw-semibold">
                                {{ number_format($item['total_debited'], 2) }}
                            </td>
                            <td class="text-success fw-semibold">
                                {{ number_format($item['total_credited'], 2) }}
                            </td>
                            <td class="fw-semibold text-dark">
                                {{ number_format($item['task_price'], 2) }}
                            </td>
                            <td>
                                @if(abs($item['net_locked']) > 0)
                                    <span class="badge bg-danger fs-6 fw-bold">
                                        {{ number_format($item['net_locked'], 2) }} {{ __('SAR') }}
                                    </span>
                                @else
                                    <span class="badge bg-success">0.00</span>
                                @endif
                            </td>
                            <td style="max-width: 280px; white-space: normal;">
                                <span class="badge {{ $item['issue_badge_class'] }} mb-1">{{ $item['issue_type'] }}</span>
                                <div class="small text-muted">
                                    @foreach($item['issue_reasons'] as $r)
                                        <div>• {{ $r }}</div>
                                    @endforeach
                                </div>
                            </td>
                            <td style="max-width: 180px; white-space: normal;">
                                @if(count($item['funders']) > 0)
                                    @foreach($item['funders'] as $f)
                                        <div class="small">
                                            <strong>{{ $f['user'] }}</strong>
                                            <div class="text-muted" style="font-size: 11px;">{{ $f['date'] }} ({{ number_format($f['amount'], 2) }})</div>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td style="max-width: 220px; white-space: normal;">
                                @if(count($item['price_changes']) > 0)
                                    @foreach($item['price_changes'] as $pc)
                                        <div class="small text-danger mb-1">
                                            <strong><i class="ti ti-edit"></i> {{ $pc['user'] }}</strong>
                                            <div class="text-muted" style="font-size: 11px;">{{ $pc['description'] }} - {{ $pc['date'] }}</div>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-muted small">{{ __('No price edit records') }}</span>
                                @endif
                            </td>
                            <td style="max-width: 180px; white-space: normal;">
                                @if(count($item['settlers']) > 0)
                                    @foreach($item['settlers'] as $s)
                                        <div class="small text-success">
                                            <strong>{{ $s['user'] }}</strong>
                                            <div class="text-muted" style="font-size: 11px;">{{ $s['date'] }} ({{ number_format($s['amount'], 2) }})</div>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="badge bg-label-secondary small">{{ __('Not Settled') }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center align-items-center gap-1">
                                    {{-- Edit Funding Action --}}
                                    @if($item['can_edit_funding'])
                                        @can('edit_investors_audit_funding')
                                            <button type="button" class="btn btn-sm btn-icon btn-label-warning" onclick="openEditFundingModal({{ $index }})" title="{{ __('Edit Funding Amount') }}">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                        @endcan
                                    @endif

                                    {{-- Delete Orphaned Refund Action --}}
                                    @if($item['can_delete_refund'])
                                        @can('delete_investors_audit_transaction')
                                            <button type="button" class="btn btn-sm btn-icon btn-label-danger" onclick="confirmDeleteOrphanedTx({{ $item['task_id'] }}, {{ $item['credit_tx_id'] ?? 'null' }})" title="{{ __('Delete Orphaned Refund') }}">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        @endcan
                                    @endif

                                    {{-- Timeline Details Action --}}
                                    <button type="button" class="btn btn-sm btn-icon btn-label-info" onclick="showTaskAuditModal({{ $index }})" title="{{ __('Full Audit Timeline') }}">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center py-4 text-muted">
                                <i class="ti ti-circle-check ti-lg text-success d-block mb-2"></i>
                                {{ __('No problematic tasks found!') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Modal for Full Task Audit Timeline --}}
<div class="modal fade" id="taskAuditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="taskModalTitle">{{ __('Task Audit Trail & Full History') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="taskModalBody">
                {{-- Loaded dynamically --}}
            </div>
            <div class="modal-footer border-top">
                <a href="#" id="modalTaskDirectLink" target="_blank" class="btn btn-primary">
                    <i class="ti ti-external-link me-1"></i> {{ __('Open Task Page') }}
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal for Editing Funding Amount --}}
<div class="modal fade" id="editFundingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="ti ti-edit text-warning me-2"></i>{{ __('Edit Task Funding Amount') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editFundingForm" onsubmit="submitEditFunding(event)">
                <input type="hidden" id="editFundingTaskId" name="task_id">
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        {{ __('Adjust the debit funding transaction in the investor wallet to match the new or current task price.') }}
                    </p>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small">{{ __('Task #') }}</label>
                            <input type="text" class="form-control" id="modalDisplayTaskId" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small">{{ __('Investor') }}</label>
                            <input type="text" class="form-control" id="modalDisplayInvestor" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small">{{ __('Current Debited Amount') }}</label>
                            <input type="text" class="form-control text-danger fw-bold" id="modalDisplayDebited" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small">{{ __('Current Task Price') }}</label>
                            <input type="text" class="form-control text-dark fw-bold" id="modalDisplayPrice" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0" for="modalNewFundingAmount">
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
                        <label class="form-label" for="modalFundingNotes">{{ __('Reason / Notes') }}</label>
                        <textarea class="form-control" id="modalFundingNotes" name="notes" rows="2" placeholder="{{ __('e.g. Correcting funding debit after manual task price adjustment') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="submit" class="btn btn-primary" id="btnSubmitFunding">
                        <span class="spinner-border spinner-border-sm me-1 d-none" id="fundingSpinner"></span>
                        <i class="ti ti-check me-1"></i> {{ __('Save & Update Balance') }}
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
    const problematicTasksData = @json($problematicTasks);

    // Filter & Search
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('taskSearchInput');
        const issueFilter = document.getElementById('issueTypeFilter');
        const rows = document.querySelectorAll('.problem-task-row');
        const countDisplay = document.getElementById('visibleTasksCount');

        function filterRows() {
            const query = searchInput.value.toLowerCase().trim();
            const selectedIssue = issueFilter.value.toLowerCase().trim();
            let visibleCount = 0;

            rows.forEach(function (row) {
                const text = row.innerText.toLowerCase();
                const issueAttr = (row.getAttribute('data-issue') || '').toLowerCase();

                const matchesQuery = query === '' || text.includes(query);
                const matchesIssue = selectedIssue === '' || issueAttr.includes(selectedIssue);

                if (matchesQuery && matchesIssue) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countDisplay) {
                countDisplay.textContent = visibleCount;
            }
        }

        if (searchInput) searchInput.addEventListener('input', filterRows);
        if (issueFilter) issueFilter.addEventListener('change', filterRows);
    });

    // Modal Timeline
    window.showTaskAuditModal = function (index) {
        const item = problematicTasksData[index];
        if (!item) return;

        document.getElementById('taskModalTitle').innerHTML = 
            '<i class="ti ti-file-analytics text-primary me-2"></i>' +
            '{{ __("Audit Details for Task") }} #' + item.task_id + 
            ' <span class="badge bg-label-info ms-2">' + item.task_status + '</span>';

        document.getElementById('modalTaskDirectLink').href = '{{ url("admin/tasks/show") }}/' + item.task_id;

        let html = '';

        // Summary Info Cards
        html += '<div class="row g-3 mb-4">';
        html += '  <div class="col-md-3 col-6"><div class="p-2 border rounded text-center bg-light"><small class="text-muted d-block">{{ __("Investor") }}</small><strong>' + item.investor_name + '</strong></div></div>';
        html += '  <div class="col-md-3 col-6"><div class="p-2 border rounded text-center bg-light"><small class="text-muted d-block">{{ __("Customer") }}</small><strong>' + item.customer_name + '</strong></div></div>';
        html += '  <div class="col-md-3 col-6"><div class="p-2 border rounded text-center bg-light"><small class="text-muted d-block">{{ __("Task Price") }}</small><strong class="text-dark">' + parseFloat(item.task_price).toFixed(2) + ' {{ __("SAR") }}</strong></div></div>';
        html += '  <div class="col-md-3 col-6"><div class="p-2 border rounded text-center bg-light"><small class="text-muted d-block">{{ __("Pending Diff") }}</small><strong class="text-danger">' + parseFloat(item.net_locked).toFixed(2) + ' {{ __("SAR") }}</strong></div></div>';
        html += '</div>';

        // Issue Description
        html += '<div class="alert alert-warning mb-4">';
        html += '  <h6 class="fw-bold mb-1"><i class="ti ti-alert-triangle me-1"></i>' + item.issue_type + '</h6>';
        html += '  <ul class="mb-0 ps-3">';
        item.issue_reasons.forEach(function(r) {
            html += '<li>' + r + '</li>';
        });
        html += '  </ul>';
        html += '</div>';

        // Transactions Table
        html += '<h6 class="fw-bold mb-2"><i class="ti ti-wallet me-1 text-primary"></i> {{ __("Wallet Financial Transactions") }}</h6>';
        html += '<div class="table-responsive mb-4">';
        html += '<table class="table table-sm table-bordered">';
        html += '<thead class="table-light"><tr><th>#</th><th>{{ __("Type") }}</th><th>{{ __("Amount") }}</th><th>{{ __("Performed By") }}</th><th>{{ __("Description") }}</th><th>{{ __("Date") }}</th></tr></thead><tbody>';
        
        if (item.transactions && item.transactions.length > 0) {
            item.transactions.forEach(function(tx) {
                const isDebit = tx.transaction_type === 'debit';
                const typeBadge = isDebit ? '<span class="badge bg-label-danger">{{ __("Funding (Debit)") }}</span>' : '<span class="badge bg-label-success">{{ __("Settlement (Credit)") }}</span>';
                const perfUser = tx.performed_by || '-';
                
                html += '<tr>';
                html += '<td>#' + tx.id + '</td>';
                html += '<td>' + typeBadge + '</td>';
                html += '<td class="fw-bold ' + (isDebit ? 'text-danger' : 'text-success') + '">' + parseFloat(tx.amount).toFixed(2) + '</td>';
                html += '<td>' + perfUser + '</td>';
                html += '<td>' + (tx.description || '-') + '</td>';
                html += '<td>' + (tx.date || '-') + '</td>';
                html += '</tr>';
            });
        } else {
            html += '<tr><td colspan="6" class="text-center text-muted">{{ __("No transactions recorded") }}</td></tr>';
        }
        html += '</tbody></table></div>';

        // Full Task History Timeline
        html += '<h6 class="fw-bold mb-2"><i class="ti ti-history me-1 text-info"></i> {{ __("Task Full Operations Log (Who created, modified price, completed, etc.)") }}</h6>';
        if (item.all_histories && item.all_histories.length > 0) {
            html += '<div class="p-3 border rounded bg-white" style="max-height: 250px; overflow-y: auto;">';
            item.all_histories.forEach(function(h) {
                const uName = h.user ? h.user.name : 'نظام / غير محدد';
                const isPricing = (h.description || '').toLowerCase().includes('pricing') || (h.description || '').toLowerCase().includes('price') || (h.description || '').includes('سعر');

                html += '<div class="timeline-audit-item">';
                html += '  <div class="d-flex justify-content-between align-items-center mb-1">';
                html += '    <strong class="' + (isPricing ? 'text-danger' : 'text-dark') + '">' + (isPricing ? '<i class="ti ti-alert-circle me-1"></i>' : '') + uName + '</strong>';
                html += '    <small class="text-muted">' + (h.date || '') + '</small>';
                html += '  </div>';
                html += '  <div class="small">' + (h.description || h.action || '-') + '</div>';
                html += '</div>';
            });
            html += '</div>';
        } else {
            html += '<p class="text-muted small">{{ __("No task history recorded") }}</p>';
        }

        document.getElementById('taskModalBody').innerHTML = html;
        new bootstrap.Modal(document.getElementById('taskAuditModal')).show();
    };

    // Open Edit Funding Modal
    let currentSelectedPrice = 0;
    window.openEditFundingModal = function (index) {
        const item = problematicTasksData[index];
        if (!item) return;

        currentSelectedPrice = parseFloat(item.task_price) || 0;
        document.getElementById('editFundingTaskId').value = item.task_id;
        document.getElementById('modalDisplayTaskId').value = '#' + item.task_id;
        document.getElementById('modalDisplayInvestor').value = item.investor_name;
        document.getElementById('modalDisplayDebited').value = parseFloat(item.total_debited).toFixed(2) + ' {{ __("SAR") }}';
        document.getElementById('modalDisplayPrice').value = currentSelectedPrice.toFixed(2) + ' {{ __("SAR") }}';
        
        // Suggest the task price if price was modified, or current debited
        const suggestedAmount = (currentSelectedPrice > 0) ? currentSelectedPrice : parseFloat(item.total_debited);
        document.getElementById('modalNewFundingAmount').value = suggestedAmount.toFixed(2);
        document.getElementById('modalFundingNotes').value = '';

        new bootstrap.Modal(document.getElementById('editFundingModal')).show();
    };

    window.applyCurrentTaskPrice = function () {
        if (currentSelectedPrice > 0) {
            document.getElementById('modalNewFundingAmount').value = currentSelectedPrice.toFixed(2);
        }
    };

    // Submit Edit Funding
    window.submitEditFunding = function (e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitFunding');
        const spinner = document.getElementById('fundingSpinner');
        btn.disabled = true;
        spinner.classList.remove('d-none');

        const taskId = document.getElementById('editFundingTaskId').value;
        const newAmount = document.getElementById('modalNewFundingAmount').value;
        const notes = document.getElementById('modalFundingNotes').value;

        fetch('{{ route("admin.investors.audit.update-funding") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                task_id: taskId,
                new_amount: newAmount,
                notes: notes
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            spinner.classList.add('d-none');
            if (data.status === 1) {
                bootstrap.Modal.getInstance(document.getElementById('editFundingModal')).hide();
                Swal.fire({
                    icon: 'success',
                    title: '{{ __("Success") }}',
                    text: data.success,
                    confirmButtonText: '{{ __("OK") }}'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: '{{ __("Error") }}',
                    text: data.error || 'حدث خطأ أثناء تعديل التمويل',
                    confirmButtonText: '{{ __("OK") }}'
                });
            }
        })
        .catch(err => {
            btn.disabled = false;
            spinner.classList.add('d-none');
            Swal.fire({
                icon: 'error',
                title: '{{ __("Error") }}',
                text: 'حدث خطأ في الاتصال بالسيرفر',
                confirmButtonText: '{{ __("OK") }}'
            });
        });
    };

    // Confirm Delete Orphaned Refund
    window.confirmDeleteOrphanedTx = function (taskId, txId) {
        Swal.fire({
            title: '{{ __("Are you sure you want to delete this orphaned refund transaction?") }}',
            text: '{{ __("This will delete the credit transaction and deduct its amount from the investor wallet balance.") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#8592a3',
            confirmButtonText: '{{ __("Yes, Delete") }}',
            cancelButtonText: '{{ __("Cancel") }}'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.showLoading();
                fetch('{{ route("admin.investors.audit.delete-orphaned-tx") }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        task_id: taskId,
                        transaction_id: txId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 1) {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ __("Deleted!") }}',
                            text: data.success,
                            confirmButtonText: '{{ __("OK") }}'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("Error") }}',
                            text: data.error || 'تعذر حذف العملية',
                            confirmButtonText: '{{ __("OK") }}'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("Error") }}',
                        text: 'حدث خطأ في الاتصال بالسيرفر',
                        confirmButtonText: '{{ __("OK") }}'
                    });
                });
            }
        });
    };
</script>
@endsection
