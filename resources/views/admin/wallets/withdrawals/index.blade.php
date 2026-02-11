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
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">
                <i class="tf-icons ti ti-cash-banknote me-2 fs-3 text-white bg-warning rounded p-1"></i>
                {{ __('Withdrawal Requests') }}
            </h5>
            <p class="text-muted mb-0">{{ __('Manage driver cash withdrawal requests') }}</p>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-withdrawals table">
                <thead class="border-top">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Driver') }}</th>
                        <th>{{ __('Requested Amount') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Payment Method') }}</th>
                        <th>{{ __('Date') }}</th>
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
                                <label class="form-label">{{ __('Payment Method') }}</label>
                                <select name="payment_method" id="payment_method" class="form-select select2">
                                    <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                                    <option value="cash">{{ __('Cash Handover') }}</option>
                                    <option value="stc_pay">STC Pay</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </select>
                            </div>

                            <div class="col-12 approve-fields" id="receipt_field">
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
@endsection
