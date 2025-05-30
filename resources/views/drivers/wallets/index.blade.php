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
    @vite(['resources/js/driver/wallet.js'])
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
                        {{ __('Wallets') }}
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
                            <th>{{ __('Task') }}</th>
                            <th>{{ __('Created At') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>


    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">{{ __('View the image') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('close') }}"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid rounded shadow" alt="{{ __('image') }}" />
                </div>
            </div>
        </div>
    </div>


@endsection
