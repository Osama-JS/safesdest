@extends('layouts/layoutMaster')

@section('title', __('HyperPay Payouts') . ' : ' . $data->id)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    <script>
        const walletId = "{{ $data->id }}";
        const fetchUrl = "{{ route('wallets.data.payouts') }}";
        const checkStatusUrl = "{{ route('wallets.hyperpay_payouts.check_status', ':id') }}";
        const csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/js/admin/wallets/hyperpay-payouts.js'])
@endsection
@section('wallets-isactive')
    active
@endsection
@section('content')

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-4 px-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1 text-primary fw-bold">
                    <i class="tf-icons ti ti-brand-mastercard me-2 fs-3 text-white bg-primary rounded p-1"></i>
                    {{ __('HyperPay Payouts') }}
                    <span class="text-muted">| [{{ $data->id }}]</span>
                    <span class="text-dark">{{ $data->owner->name ?? '' }}</span>
                </h5>
            </div>
            <div>
                <a href="{{ route('admin.user-wallets.show', $data->owner->id) }}" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i>{{ __('Back to Wallet') }}
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 datatables-payouts">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>#</th>
                            <th>{{ __('Reference ID') }}</th>
                            <th>{{ __('Payout ID') }}</th>
                            <th>{{ __('Amount') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Initiated By') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Created At') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
