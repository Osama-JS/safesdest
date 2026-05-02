@extends('layouts/layoutMaster')

@section('title', __('Withdrawal Requests'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/block-ui/block-ui.js'])
@endsection

@section('page-script')
    <script>
        const withdrawalDataUrl = "{{ route('wallets.withdrawals.data') }}";
        const processWithdrawalUrl = "{{ route('wallets.withdrawals.process', ':id') }}";
    </script>
    @vite(['resources/js/admin/withdrawals/withdrawals.js'])
@endsection

@section('content')
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Pending Requests') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['pending_amount'] }} {{ __('SAR') }}</h4>
                                <span class="text-warning">({{ $stats['pending_count'] }})</span>
                            </div>
                            <small class="mb-0">{{ __('Waiting for approval') }}</small>
                        </div>
                        <span class="badge bg-label-warning rounded p-2">
                            <i class="ti ti-clock ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Processing') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['processing_amount'] }} {{ __('SAR') }}</h4>
                                <span class="text-info">({{ $stats['processing_count'] }})</span>
                            </div>
                            <small class="mb-0">{{ __('Initiated (HyperPay)') }}</small>
                        </div>
                        <span class="badge bg-label-info rounded p-2">
                            <i class="ti ti-refresh ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Approved Requests') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['approved_amount'] }} {{ __('SAR') }}</h4>
                                <span class="text-success">({{ $stats['approved_count'] }})</span>
                            </div>
                            <small class="mb-0">{{ __('Successfully processed') }}</small>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="ti ti-check ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span>{{ __('Rejected Requests') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['rejected_count'] }}</h4>
                            </div>
                            <small class="mb-0">{{ __('Total rejected') }}</small>
                        </div>
                        <span class="badge bg-label-danger rounded p-2">
                            <i class="ti ti-x ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Filter by Driver') }}</label>
                    <select id="filter_driver" class="select2 form-select" data-allow-clear="true">
                        <option value="">{{ __('All Drivers') }}</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Filter by Status') }}</label>
                    <select id="filter_status" class="select2 form-select" data-allow-clear="true">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="processing">{{ __('Processing') }}</option>
                        <option value="completed">{{ __('Approved') }}</option>
                        <option value="rejected">{{ __('Rejected') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">
                <i class="tf-icons ti ti-cash-banknote me-2 fs-3 text-white bg-warning rounded p-1"></i>
                {{ __('Withdrawal Requests') }}
            </h5>
            <p class="text-muted mb-0">{{ __('Manage driver cash withdrawal requests') }}</p>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-withdrawals table font-small">
                <thead class="border-top">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Driver') }}</th>
                        <th>{{ __('Requested') }}</th>
                        <th>{{ __('Approved') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Action By') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Process Withdrawal Modal -->
    <div class="modal fade" id="processWithdrawalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Process Withdrawal Request') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="processWithdrawalForm" enctype="multipart/form-data">
                    <input type="hidden" id="withdrawal_id" name="id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">{{ __('Action') }}</label>
                                <select name="action" id="withdrawal_action" class="form-select" required>
                                    <option value="approve">{{ __('Approve') }}</option>
                                    <option value="reject">{{ __('Reject') }}</option>
                                </select>
                            </div>

                            <div class="col-12 approve-fields">
                                <label class="form-label">{{ __('Amount to Pay') }}</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="form-control" required>
                                    <span class="input-group-text">{{ __('SAR') }}</span>
                                </div>
                                <small class="text-muted">{{ __('Requested:') }} <span id="requested_amount_display">0</span></small>
                            </div>

                            <div class="col-12 approve-fields">
                                <div class="bg-label-info p-3 rounded mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="mb-0 fw-bold">{{ __('Driver Financial Info') }}</h6>
                                        <span class="badge bg-primary">{{ __('Wallet Balance') }}: <span id="driver_wallet_balance">0</span> SAR</span>
                                    </div>
                                    <div id="driver_bank_info" class="small" style="display: none;">
                                        <div class="row g-2">
                                            <div class="col-6 mb-1"><strong>{{ __('Beneficiary') }}:</strong> <span id="info_beneficiary"></span></div>
                                            <div class="col-6 mb-1"><strong>{{ __('BIC/Swift') }}:</strong> <span id="info_bic"></span></div>
                                            <div class="col-12 mb-1"><strong>{{ __('IBAN') }}:</strong> <span id="info_iban" class="text-break text-primary fw-bold"></span></div>
                                            <hr class="my-1">
                                            <div class="col-12 mb-1"><strong>{{ __('Address') }}:</strong> <span id="info_address"></span></div>
                                            <div class="col-6"><strong>{{ __('City') }}:</strong> <span id="info_city"></span></div>
                                            <div class="col-6"><strong>{{ __('Country') }}:</strong> <span id="info_country"></span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 approve-fields">
                                <label class="form-label">{{ __('Payment Method') }}</label>
                                <select name="payment_method" id="payment_method" class="form-select select2">
                                    <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                                    <option value="cash">{{ __('Cash Handover') }}</option>
                                    <option value="hyperpay">{{ __('HyperPay HyperSplits (Auto)') }}</option>
                                </select>
                            </div>

                            <div class="col-12 approve-fields" id="receipt_field_container">
                                <label class="form-label">{{ __('Receipt Image') }}</label>
                                <input type="file" name="receipt" class="form-control" accept="image/*,application/pdf">
                            </div>

                            <div class="col-12">
                                <label class="form-label">{{ __('Notes') }}</label>
                                <textarea name="admin_notes" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="submitProcessBtn">{{ __('Process Request') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Withdrawal Details Modal -->
    <div class="modal fade" id="viewWithdrawalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-label-primary">
                    <h5 class="modal-title">{{ __('Withdrawal Request Details') }} #<span id="view_request_id"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Driver Info -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold">{{ __('Driver Information') }}</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="ps-0" width="40%">{{ __('Name') }}:</td>
                                    <td id="view_driver_name" class="fw-bold"></td>
                                </tr>
                                <tr>
                                    <td class="ps-0">{{ __('Wallet ID') }}:</td>
                                    <td id="view_wallet_id"></td>
                                </tr>
                            </table>
                        </div>
                        <!-- Request Info -->
                        <div class="col-md-6">
                            <h6 class="fw-semibold">{{ __('Request Information') }}</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="ps-0" width="40%">{{ __('Amount') }}:</td>
                                    <td id="view_amount_requested" class="fw-bold text-primary"></td>
                                </tr>
                                <tr>
                                    <td class="ps-0">{{ __('Created At') }}:</td>
                                    <td id="view_created_at"></td>
                                </tr>
                                <tr>
                                    <td class="ps-0">{{ __('Status') }}:</td>
                                    <td id="view_status"></td>
                                </tr>
                            </table>
                        </div>

                        <hr class="my-0">

                        <!-- Processing Info -->
                        <div class="col-12" id="processing_details_section">
                            <h6 class="fw-semibold mt-3">{{ __('Processing Details') }}</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="ps-0" width="40%">{{ __('Action By') }}:</td>
                                            <td id="view_processed_by"></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0">{{ __('Processed At') }}:</td>
                                            <td id="view_processed_at"></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="ps-0" width="40%">{{ __('Approved Amount') }}:</td>
                                            <td id="view_amount_paid" class="fw-bold text-success"></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0">{{ __('Payment Method') }}:</td>
                                            <td id="view_payment_method"></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-12">
                                    <label class="fw-semibold">{{ __('Admin Notes') }}:</label>
                                    <p id="view_admin_notes" class="text-muted fst-italic p-2 bg-label-secondary rounded mb-0"></p>
                                </div>
                                <div class="col-12" id="view_receipt_container">
                                    <label class="fw-semibold">{{ __('Receipt Image') }}:</label>
                                    <div class="mt-2">
                                        <a href="#" target="_blank" id="view_receipt_link">
                                            <img src="" id="view_receipt_img" class="img-fluid rounded border p-1" style="max-height: 200px;" alt="Receipt">
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection
