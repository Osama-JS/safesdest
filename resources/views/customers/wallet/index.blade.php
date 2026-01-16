@extends('layouts/layoutMaster')

@section('title', __('Wallets') . ':' . $data->id)

@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss'])

    <style>
        .wallet-stat-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .wallet-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
        }

        .wallet-stat-card.balance-positive {
            border-left-color: #28a745;
        }

        .wallet-stat-card.balance-negative {
            border-left-color: #dc3545;
        }

        .wallet-stat-card.credit-card {
            border-left-color: #28a745;
        }

        .wallet-stat-card.debit-card {
            border-left-color: #dc3545;
        }

        .progress-bar-animated {
            animation: progress-bar-stripes 1s linear infinite;
        }

        @keyframes progress-bar-stripes {
            0% {
                background-position: 1rem 0;
            }

            100% {
                background-position: 0 0;
            }
        }

        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        .avatar-initial {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .dropdown-item {
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/block-ui/block-ui.js'])
@endsection

@section('page-script')
    <script>
        const walletId = "{{ $data->id }}";
    </script>
    @vite(['resources/js/customers/wallet.js'])
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

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-12 mb-3">
            <div class="card border-0 shadow-sm  ">
                <div class="card-body py-4">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg me-3">
                            <span class="avatar-initial rounded-circle  ">
                                <i class="ti ti-wallet fs-2 "></i>
                            </span>
                        </div>
                        <div>
                            <h4 class="card-title mb-1  fw-bold">
                                {{ __('My Wallet') }}
                            </h4>
                            <p class="mb-0 text-black-50">
                                {{ __('Wallet ID') }}: {{ $data->id }} | {{ $data->owner->name }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Balance Card -->
        <div class="col-xl-3 col-md-4 col-sm-12 mb-3">
            <div
                class="card border-0 shadow-sm h-100 wallet-stat-card {{ $balance < 0 ? 'balance-negative' : 'balance-positive' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md me-3">
                            <span
                                class="avatar-initial rounded-circle {{ $balance < 0 ? 'bg-label-danger' : 'bg-label-success' }}">
                                <i class="ti ti-wallet fs-4 {{ $balanceClass }}"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-0 text-muted">{{ __('Current Balance') }}</h6>
                                    <h4 class="mb-0 {{ $balanceClass }} fw-bold">
                                        {{ $balanceSign }}{{ number_format(abs($balance), 2) }}
                                    </h4>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">{{ __('SAR') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Credit Card -->
        <div class="col-xl-3 col-md-4 col-sm-12 mb-3">
            <div class="card border-0 shadow-sm h-100 wallet-stat-card credit-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md me-3">
                            <span class="avatar-initial rounded-circle bg-label-success">
                                <i class="ti ti-arrow-up-right fs-4 text-success"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-0 text-muted">{{ __('Total Credit') }}</h6>
                                    <h4 class="mb-0 text-success fw-bold">{{ number_format($credit, 2) }}</h4>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">{{ __('SAR') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Debit Card -->
        <div class="col-xl-3 col-md-4 col-sm-12 mb-3">
            <div class="card border-0 shadow-sm h-100 wallet-stat-card debit-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md me-3">
                            <span class="avatar-initial rounded-circle bg-label-danger">
                                <i class="ti ti-arrow-down-left fs-4 text-danger"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="mb-0 text-muted">{{ __('Total Debit') }}</h6>
                                    <h4 class="mb-0 text-danger fw-bold">{{ number_format($debit, 2) }}</h4>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">{{ __('SAR') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Statistics Cards -->
    <div class="row mb-4">

    </div>

    <!-- Debt Ceiling Card -->
    @if ($debtCeiling > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <span class="avatar-initial rounded-circle bg-label-warning">
                                        <i class="ti ti-alert-triangle fs-5 text-warning"></i>
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ __('Debt Usage') }}</h6>
                                    <small class="text-muted">{{ $usedDebt }} / {{ $debtCeiling }}
                                        {{ __('SAR') }}</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span
                                    class="badge bg-{{ $debtPercent < 50 ? 'success' : ($debtPercent < 80 ? 'warning' : 'danger') }}">
                                    {{ $debtPercent }}%
                                </span>
                            </div>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar {{ $progressBarClass }} progress-bar-striped progress-bar-animated"
                                role="progressbar" style="width: {{ $debtPercent }}%;"
                                aria-valuenow="{{ $debtPercent }}" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Transactions Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header  border-bottom">
            <div
                class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm me-3">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                            <i class="ti ti-list fs-5 text-primary"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">{{ __('Transaction History') }}</h5>
                        <small class="text-muted">{{ __('All wallet transactions') }}</small>
                    </div>
                </div>

            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 datatables-users">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">
                                <i class="ti ti-hash fs-6 text-muted"></i>
                            </th>
                            <th class="text-center" style="width: 80px;">{{ __('ID') }}</th>
                            <th>
                                {{ __('Amount') }}
                            </th>
                            <th>
                                <i class="ti ti-file-description me-1"></i>{{ __('Description') }}
                            </th>
                            <th>
                                <i class="ti ti-calendar-due me-1"></i>{{ __('Maturity') }}
                            </th>
                            <th>
                                <i class="ti ti-truck-delivery me-1"></i>{{ __('Task') }}
                            </th>
                            <th>
                                <i class="ti ti-clock me-1"></i>{{ __('Created At') }}
                            </th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>


    </div>


    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                            <span class="avatar-initial rounded-circle bg-label-primary">
                                <i class="ti ti-photo fs-5 text-primary"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0" id="imageModalLabel">{{ __('View the image') }}</h5>
                            <small class="text-muted">{{ __('Transaction attachment') }}</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('close') }}"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <div class="position-relative">
                        <img id="modalImage" src="" class="img-fluid rounded shadow-sm"
                            alt="{{ __('image') }}" style="max-height: 500px; object-fit: contain;" />
                        <div class="position-absolute top-0 end-0 m-2">
                            <button class="btn btn-sm btn-outline-light" onclick="downloadImage()"
                                title="{{ __('Download') }}">
                                <i class="ti ti-download"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>{{ __('Close') }}
                    </button>
                    <button type="button" class="btn btn-primary" onclick="downloadImage()">
                        <i class="ti ti-download me-1"></i>{{ __('Download') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced wallet page functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Filter functionality
            const filterItems = document.querySelectorAll('[data-filter]');
            filterItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const filter = this.getAttribute('data-filter');
                    applyFilter(filter);
                });
            });

            // Hover effects for stat cards
            const statCards = document.querySelectorAll('.wallet-stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Update total transactions count when table loads
            updateTransactionCount();
        });

        function applyFilter(filterType) {
            // This function will be enhanced by the existing wallet.js
            console.log('Applying filter:', filterType);
            // The actual filtering logic should be in wallet.js
        }

        function updateTransactionCount() {
            // Update the total transactions count
            const totalElement = document.getElementById('total-transactions');
            if (totalElement) {
                // This will be updated by DataTables when it loads
                totalElement.textContent = '...';
            }
        }

        function downloadImage() {
            const modalImage = document.getElementById('modalImage');
            if (modalImage && modalImage.src) {
                const link = document.createElement('a');
                link.href = modalImage.src;
                link.download = 'transaction-image.jpg';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        // Add smooth scrolling to cards
        function scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        }
    </script>

@endsection
