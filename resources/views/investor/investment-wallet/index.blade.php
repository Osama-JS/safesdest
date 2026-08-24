@extends('layouts/layoutMaster')

@section('title', __('Investment Wallet'))

@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- رأس الصفحة --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-wallet me-2 text-warning"></i>{{ __('Investment Wallet') }}</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('investor.dashboard') }}">{{ __('Home') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Investment Wallet') }}</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex align-items-center gap-3">
            @if($investorWallet && $investorWallet->balance < 500)
                <div class="alert alert-label-warning d-flex align-items-center mb-0 py-2" role="alert">
                    <span class="alert-icon me-2"><i class="ti ti-alert-triangle ti-xs"></i></span>
                    {{ __('Your balance is low') }}
                </div>
            @endif
            <a href="{{ route('investor.investment-wallet.export', request()->query()) }}" class="btn btn-success d-flex align-items-center shadow-sm py-2 px-4">
                <i class="ti ti-file-spreadsheet me-2 ti-sm"></i>
                <span class="fw-bold">{{ __('Export Excel') }}</span>
            </a>
            @if(($investorWallet?->balance ?? 0) > 0)
            <button type="button" class="btn btn-label-danger d-flex align-items-center shadow-sm py-2 px-4" data-bs-toggle="modal" data-bs-target="#requestCapitalWithdrawalModal">
                <i class="ti ti-arrow-up-right me-2 ti-sm"></i>
                <span class="fw-bold">{{ __('Request Capital Withdrawal') }}</span>
            </button>
            @endif
            <button type="button" class="btn btn-primary d-flex align-items-center shadow-sm py-2 px-4" data-bs-toggle="modal" data-bs-target="#depositModal" style="background: linear-gradient(135deg, #7367f0 0%, #a098f5 100%); border: none;">
                <i class="ti ti-credit-card me-2 ti-sm"></i>
                <span class="fw-bold">{{ __('Top Up Investment Wallet') }}</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible mb-4" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible mb-4" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            {{ session('error') }}
        </div>
    @endif

    {{-- بطاقات الإحصائيات --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i class="ti ti-wallet ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($investorWallet?->balance ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-medium">{{ __('Current Available Balance') }}</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-warning me-1">{{ __('SAR') }}</span> {{ __('Ready for investment') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="ti ti-arrow-down-left ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($investorWallet?->credit ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-medium">{{ __('Total Capital') }}</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-success me-1">{{ __('SAR') }}</span> {{ __('Deposited as capital') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="ti ti-arrow-back-up ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($investorWallet?->returned_capital ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-medium">{{ __('Total Capital Returned') }}</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-info me-1">{{ __('SAR') }}</span> {{ __('Returned after task settlement') }}
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-danger"><i class="ti ti-arrow-up-right ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0">{{ number_format($investorWallet?->debit ?? 0, 2) }}</h4>
                    </div>
                    <p class="mb-1 fw-medium">{{ __('Total Fundings') }}</p>
                    <p class="mb-0 small text-muted">
                        <span class="text-danger me-1">{{ __('SAR') }}</span> {{ __('Spent on tasks') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- فلاتر البحث --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-muted small">{{ __('Filter Results') }}</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Task #') }}</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="{{ __('Search') }}...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Operation type') }}</label>
                    <select name="type" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>{{ __('Credit (Deposit)') }}</option>
                        <option value="debit"  {{ request('type') === 'debit'  ? 'selected' : '' }}>{{ __('Debit (Funding)') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Date Range') }}</label>
                    <input type="text" id="dateRange" class="form-control" placeholder="{{ __('Select Date Range') }}">
                    <input type="hidden" name="from" value="{{ request('from') }}">
                    <input type="hidden" name="to" value="{{ request('to') }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>{{ __('Filter') }}</button>
                    <a href="{{ route('investor.investment-wallet') }}" class="btn btn-label-secondary w-100">{{ __('Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- جدول الحركات --}}
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ __('Wallet Transactions Log') }}</h5>
            <span class="badge bg-label-secondary">{{ __('Total operations') }}: {{ $transactions->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-muted small">{{ __('Operation type') }}</th>
                        <th class="text-muted small">{{ __('Amount') }}</th>
                        <th class="text-muted small">{{ __('Balance After') }}</th>
                        <th class="text-muted small">{{ __('Task ID') }}</th>
                        <th class="text-muted small">{{ __('Description') }}</th>
                        <th class="text-muted small">{{ __('Date and Time') }}</th>
                        <th class="text-muted small">{{ __('Attachment') }}</th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    @forelse($transactions as $tx)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($tx->transaction_type === 'credit')
                                    @if($tx->source_type === 'refund')
                                        <div class="avatar avatar-xs me-2">
                                            <span class="avatar-initial rounded-circle bg-label-info"><i class="ti ti-arrow-back-up"></i></span>
                                        </div>
                                        <span class="fw-medium text-info">{{ __('Capital Return') }}</span>
                                    @elseif(str_contains($tx->description ?? '', __('Profit reinvestment to investment wallet')))
                                        <div class="avatar avatar-xs me-2">
                                            <span class="avatar-initial rounded-circle bg-label-primary"><i class="ti ti-refresh"></i></span>
                                        </div>
                                        <span class="fw-medium text-primary">{{ __('Profit Reinvestment') }}</span>
                                    @else
                                        <div class="avatar avatar-xs me-2">
                                            <span class="avatar-initial rounded-circle bg-label-success"><i class="ti ti-arrow-down-left"></i></span>
                                        </div>
                                        <span class="fw-medium text-success">{{ __('Capital Deposit') }}</span>
                                    @endif
                                @else
                                    <div class="avatar avatar-xs me-2">
                                        <span class="avatar-initial rounded-circle bg-label-danger"><i class="ti ti-arrow-up-right"></i></span>
                                    </div>
                                    <span class="fw-medium text-danger">{{ __('Task Funding') }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="fw-bold">
                            {{ $tx->transaction_type === 'credit' ? '+' : '-' }}{{ number_format($tx->amount, 2) }} {{ __('SAR') }}
                        </td>
                        <td>{{ number_format($tx->balance_after, 2) }} {{ __('SAR') }}</td>
                        <td>
                            @if($tx->task_id)
                                <a href="javascript:void(0)" class="badge bg-label-primary">#{{ $tx->task_id }}</a>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-truncate" style="max-width: 200px;">{{ $tx->description ?? '—' }}</td>
                        <td class="small">{{ $tx->created_at->format('Y-m-d') }} <br> <span class="text-muted">{{ $tx->created_at->format('H:i') }}</span></td>
                        <td>
                            @if($tx->attachment)
                                <button type="button" class="btn btn-sm btn-label-primary" title="{{ __('View Attachment') }}" onclick="openAttachmentModal('{{ asset('storage/' . $tx->attachment) }}')">
                                    <i class="ti ti-file-symlink"></i>
                                </button>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <img src="{{ asset('assets/img/illustrations/empty-state.png') }}" alt="Empty state" width="120" class="mb-3 opacity-50">
                            <p class="text-muted">{{ __('No transactions yet.') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer px-4">
            {{ $transactions->appends(request()->input())->links('investor.partials.pagination') }}
        </div>
        @endif
    </div>

    {{-- قسم سجل طلبات سحب رأس المال --}}
    @if(isset($withdrawalRequests) && $withdrawalRequests->count() > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">
                <i class="ti ti-history me-2 text-danger"></i>{{ __('Capital Withdrawal Requests') }}
            </h5>
            <span class="badge bg-label-primary">{{ $withdrawalRequests->total() }} {{ __('registered requests') }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Requested Amount') }}</th>
                        <th>{{ __('Request Date') }}</th>
                        <th>{{ __('Scheduled Disbursement Date (after 3 months)') }}</th>
                        <th>{{ __('Remaining Duration') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Notes') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($withdrawalRequests as $reqItem)
                    <tr>
                        <td><strong>#{{ $reqItem->id }}</strong></td>
                        <td class="fw-bold text-danger">{{ number_format($reqItem->amount, 2) }} {{ __('SAR') }}</td>
                        <td>{{ $reqItem->request_date->format('Y-m-d H:i') }}</td>
                        <td>
                            <span class="badge bg-label-info">
                                <i class="ti ti-calendar me-1"></i>{{ $reqItem->scheduled_disbursement_date ? $reqItem->scheduled_disbursement_date->format('Y-m-d') : '—' }}
                            </span>
                        </td>
                        <td>
                            @if($reqItem->status === 'completed')
                                <span class="badge bg-label-success">{{ __('Disbursed') }}</span>
                            @elseif($reqItem->status === 'rejected')
                                <span class="badge bg-label-danger">{{ __('Rejected') }}</span>
                            @elseif($reqItem->is_due_for_disbursement)
                                <span class="badge bg-success">{{ __('Due for Disbursement') }}</span>
                            @else
                                <span class="text-muted fw-medium">{{ $reqItem->remaining_duration_human }}</span>
                            @endif
                        </td>
                        <td>{!! $reqItem->status_badge !!}</td>
                        <td class="small text-muted">
                            {{ $reqItem->investor_notes ?? '—' }}
                            @if($reqItem->admin_notes)
                                <br><strong class="text-dark">{{ __('Admin Response:') }}</strong> {{ $reqItem->admin_notes }}
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($withdrawalRequests->hasPages())
        <div class="card-footer px-4">
            {{ $withdrawalRequests->links('investor.partials.pagination') }}
        </div>
        @endif
    </div>
    @endif

    {{-- Modal Request Capital Withdrawal --}}
    <div class="modal fade" id="requestCapitalWithdrawalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="ti ti-arrow-up-right me-1"></i>{{ __('Request Capital Withdrawal') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('investor.investment-wallet.withdraw-request') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <!-- Alert Disclaimer -->
                        <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
                            <h6 class="alert-heading fw-bold mb-1">
                                <i class="ti ti-alert-triangle me-1"></i>{{ __('Important Notice Regarding Capital Withdrawal') }}
                            </h6>
                            <p class="mb-0 small" style="line-height: 1.6;">
                                {{ __('Capital Withdrawal Policy Notice') }}
                            </p>
                        </div>

                        <!-- Available Balance Display -->
                        <div class="d-flex justify-content-between align-items-center p-3 rounded bg-label-primary mb-3">
                            <span class="fw-medium">{{ __('Currently Available Balance for Withdrawal:') }}</span>
                            <span class="fw-bold fs-5 text-primary">{{ number_format($investorWallet?->balance ?? 0, 2) }} {{ __('SAR') }}</span>
                        </div>

                        <!-- Amount Input -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="withdraw_amount">{{ __('Amount to Withdraw (SAR)') }} <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg border-2 rounded-3">
                                <input type="number" name="amount" id="withdraw_amount" class="form-control" placeholder="0.00" min="1" max="{{ $investorWallet?->balance ?? 0 }}" step="0.01" required>
                                <span class="input-group-text">{{ __('SAR') }}</span>
                            </div>
                            <div class="form-text text-muted">{{ __('Max available balance note') }} ({{ number_format($investorWallet?->balance ?? 0, 2) }} {{ __('SAR') }}).</div>
                        </div>

                        <!-- Notes Input -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="investor_notes">{{ __('Notes / Reason for Withdrawal (Optional)') }}</label>
                            <textarea name="investor_notes" id="investor_notes" class="form-control" rows="2" placeholder="{{ __('Enter any notes you wish to attach with the request...') }}"></textarea>
                        </div>

                        <!-- Mandatory Checkbox -->
                        <div class="form-check p-3 rounded bg-label-danger border border-danger mb-2">
                            <div class="d-flex align-items-start">
                                <input class="form-check-input ms-0 me-2 mt-1" type="checkbox" name="agreed_terms" id="agreed_terms" value="1" required>
                                <label class="form-check-label fw-bold text-danger small" for="agreed_terms" style="cursor: pointer; line-height: 1.5;">
                                    {{ __('I acknowledge and agree that capital return takes place 3 months after the request date') }}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pb-4">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger px-4 shadow">
                            <i class="ti ti-send me-1"></i>{{ __('Confirm and Submit Request') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Deposit --}}
    <div class="modal fade" id="depositModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold">{{ __('Top up wallet title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('investor.investment-wallet.deposit.initiate') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-4 text-center">
                            <p class="text-muted">{{ __('Enter amount to add via payment gateway') }}</p>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">{{ __('Top up amount (SAR)') }}</label>
                            <div class="input-group input-group-lg border-2 rounded-3">
                                <input type="number" name="amount" class="form-control" placeholder="0.00" min="10" step="0.01" required>
                                <span class="input-group-text">{{ __('SAR') }}</span>
                            </div>
                            <div class="form-text text-warning"><i class="ti ti-info-circle me-1"></i>{{ __('Minimum amount is 10 SAR') }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block fw-semibold mb-3">{{ __('Choose payment method') }}</label>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="form-check custom-option custom-option-icon border-2">
                                        <label class="form-check-label custom-option-content" for="brandVisa">
                                            <span class="custom-option-body">
                                                <i class="ti ti-brand-visa ti-lg mb-2 text-primary"></i>
                                                <span class="custom-option-title fw-bold">Visa / Master</span>
                                            </span>
                                            <input name="brand" class="form-check-input" type="radio" value="VISA" id="brandVisa" />
                                        </label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check custom-option custom-option-icon border-2">
                                        <label class="form-check-label custom-option-content" for="brandMada">
                                            <span class="custom-option-body">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fb/Mada_Logo.svg/1280px-Mada_Logo.svg.png" alt="Mada" width="45" class="mb-2">
                                                <span class="custom-option-title fw-bold">{{ __('Mada card') }}</span>
                                            </span>
                                            <input name="brand" class="form-check-input" type="radio" value="MADA" id="brandMada" checked />
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pb-4">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary px-5 shadow">{{ __('Proceed to secure payment') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Attachment Modal -->
    <div class="modal fade" id="viewAttachmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('View Attachment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0" id="attachmentModalBody">
                    <!-- Dynamic content will be injected here -->
                </div>
                <div class="modal-footer">
                    <a href="#" id="attachmentDownloadBtn" class="btn btn-primary" download>
                        <i class="ti ti-download me-1"></i> {{ __('Download') }}
                    </a>
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

</div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        window.openAttachmentModal = function(url) {
            const modalBody = document.getElementById('attachmentModalBody');
            const downloadBtn = document.getElementById('attachmentDownloadBtn');
            const ext = url.split('.').pop().toLowerCase();
            
            modalBody.innerHTML = '';
            downloadBtn.href = url;

            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                modalBody.innerHTML = `<img src="${url}" class="img-fluid" style="max-height: 70vh;" alt="Attachment">`;
            } else if (ext === 'pdf') {
                modalBody.innerHTML = `<iframe src="${url}" width="100%" height="500px" style="border: none;"></iframe>`;
            } else {
                modalBody.innerHTML = `
                    <div class="py-5">
                        <i class="ti ti-file-text display-1 text-muted mb-3"></i>
                        <h5>{{ __('File preview not available') }}</h5>
                        <p class="text-muted">{{ __('Please download the file to view it.') }}</p>
                    </div>`;
            }
            
            new bootstrap.Modal(document.getElementById('viewAttachmentModal')).show();
        };
    });
</script>
@vite(['resources/js/investor/wallet.js'])
@endsection
