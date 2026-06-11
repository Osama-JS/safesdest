@extends('layouts/layoutMaster')

@section('title', __('Teams'))

<!-- Vendor Styles -->
@section('vendor-style')

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

    @vite(['resources/css/app.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    <script>
        const templateId = {{ $driver_template->value ?? 0 }}
        const teamID = {{ $data->id }}
    </script>
    <script type="text/template" id="vehicle-row-template">
      <div class="row vehicle-row mb-3 " data-index="{index}">
        <div class="col-md-4">
          <label class="form-label">* Vehicle</label>
          <select class="form-select vehicle-select" name="vehicles[{index}][vehicle]">
            <option value="">Select a vehicle</option>
            @foreach ($vehicles as $vehicle)
              <option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>
            @endforeach
          </select>

        </div>
        <div class="col-md-4">
          <label class="form-label">* Vehicle Type</label>
          <select class="form-select vehicle-type-select" name="vehicles[{index}][vehicle_type]" disabled>
            <option value="">Select a vehicle type</option>
          </select>

        </div>
        <div class="col-md-4">
          <label class="form-label">* Vehicle Size</label>
          <select class="form-select vehicle-size-select" name="vehicle" disabled>
            <option value="">Select a vehicle size</option>
          </select>
          <span class="vehicle-error text-danger text-error"></span>

        </div>


      </div>
    </script>

@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/teams/show.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])

    <script>
        // Team Dashboard Button Interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add click animation to dashboard buttons
            const dashboardButtons = document.querySelectorAll('.team-dashboard-btn');

            dashboardButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Add loading state
                    const icon = this.querySelector('i');
                    const originalIcon = icon.className;

                    icon.className = 'ti ti-loader ti-spin fs-1 mb-2';

                    // Restore icon after a short delay (for visual feedback)
                    setTimeout(() => {
                        icon.className = originalIcon;
                    }, 1000);
                });

                // Add ripple effect on click
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;

                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.classList.add('ripple');

                    this.appendChild(ripple);

                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });
    </script>

    <style>
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    </style>
@endsection

@section('content')

    <!-- Breadcrumb Navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb breadcrumb-style1">
            <li class="breadcrumb-item">
                <a href="{{ route('teams.teams') }}">
                    <i class="ti ti-users-group me-1"></i>{{ __('Teams') }}
                </a>
            </li>
            <li class="breadcrumb-item active">{{ $data->name }}</li>
        </ol>
    </nav>

    <style>
        /* Payment Controls Styling */
        #payment-controls {
            border-left: 4px solid #28a745;
            box-shadow: 0 2px 10px rgba(40, 167, 69, 0.1);
        }

        #payment-controls .badge {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }

        /* Checkbox Styling */
        .transaction-checkbox {
            transform: scale(1.2);
            cursor: pointer;
        }

        .transaction-checkbox:checked {
            background-color: #28a745;
            border-color: #28a745;
        }

        /* Modal Styling */
        #paymentModal .modal-content {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }



        /* Payment Summary Card */
        #paymentModal .card.bg-light {
            border: 1px solid #e9ecef;
            border-radius: 10px;
        }

        /* Selected Transactions Table */
        #selectedTransactionsTable {
            font-size: 0.875rem;
        }

        #selectedTransactionsTable th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }

        #selectedTransactionsTable .payment-amount {
            font-weight: 600;
            color: #28a745;
        }

        /* Button Styling */
        .remove-transaction {
            border-radius: 50%;
            width: 30px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }



        /* Loading Animation */
        .ti-spin {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Team Dashboard Button Styling */
        .team-dashboard-btn {
            border: 2px solid;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            min-height: 140px;
            position: relative;
            overflow: hidden;
        }

        .team-dashboard-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            text-decoration: none;
        }

        .team-dashboard-btn:hover i {
            transform: scale(1.1);
            transition: transform 0.3s ease;
        }

        .team-dashboard-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .team-dashboard-btn:hover::before {
            left: 100%;
        }

        /* Responsive Improvements */
        @media (max-width: 768px) {
            #payment-controls .row {
                flex-direction: column;
                gap: 1rem;
            }

            #payment-controls .text-end {
                text-align: start !important;
            }

            #selectedTransactionsTable {
                font-size: 0.75rem;
            }

            .modal-lg {
                max-width: 95%;
            }

            .team-dashboard-btn {
                min-height: 120px;
            }
        }
    </style>

    @php
        $balance = ($totals['debit'] ?? 0) - ($totals['credit'] ?? 0);
        $credit = $totals['credit'] ?? 0;
        $debit = $totals['debit'] ?? 0;

        $balanceClass = $balance < 0 ? 'text-danger' : 'text-success';
        $balanceSign = $balance < 0 ? '-' : '+';

    @endphp


    <!-- Team Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                <div>
                    <h4 class="mb-1">
                        <i class="tf-icons ti ti-users-group me-2 text-primary"></i>
                        {{ $data->name }} [{{ $data->id }}]
                    </h4>
                    <p class="text-muted mb-2">
                        <i class="tf-icons ti ti-location me-1"></i>
                        {{ $data->address }}
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-label-primary">
                            <i class="tf-icons ti ti-steering-wheel me-1"></i>
                            {{ $data->drivers->count() }} {{ __('Drivers') }}
                        </span>
                        <span class="badge bg-label-info">
                            <i class="tf-icons ti ti-truck-delivery me-1"></i>
                            {{ $data->tasks->count() }} {{ __('Tasks') }}
                        </span>
                    </div>
                </div>
                <div class="mt-3 mt-md-0">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('teams.dashboard.index', $data->id) }}" class="btn btn-primary">
                            <i class="ti ti-dashboard me-1"></i>{{ __('New Dashboard') }}
                        </a>
                        <a href="{{ route('teams.teams') }}" class="btn btn-outline-secondary">
                            <i class="ti ti-arrow-left me-1"></i>{{ __('Back to Teams') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Management Links -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ti ti-settings me-2 text-primary"></i>{{ __('Team Management Dashboard') }}
                </h5>
                <span class="badge bg-label-primary">{{ __('New Features') }}</span>
            </div>
            <p class="text-muted mb-0 mt-2">{{ __('Access comprehensive team management tools and analytics') }}</p>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('teams.dashboard.index', $data->id) }}"
                        class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-4 team-dashboard-btn">
                        <i class="ti ti-dashboard fs-1 mb-2 text-primary"></i>
                        <span class="fw-bold">{{ __('Dashboard') }}</span>
                        <small class="text-muted">{{ __('Overview & Stats') }}</small>
                    </a>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('teams.dashboard.drivers', $data->id) }}"
                        class="btn btn-outline-success w-100 h-100 d-flex flex-column align-items-center justify-content-center py-4 team-dashboard-btn">
                        <i class="ti ti-steering-wheel fs-1 mb-2 text-success"></i>
                        <span class="fw-bold">{{ __('Drivers') }}</span>
                        <small class="text-muted">
                            <span class="badge bg-success rounded-pill">{{ $data->drivers->count() }}</span>
                            {{ __('Active') }}
                        </small>
                    </a>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('teams.dashboard.tasks', $data->id) }}"
                        class="btn btn-outline-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-4 team-dashboard-btn">
                        <i class="ti ti-truck-delivery fs-1 mb-2 text-info"></i>
                        <span class="fw-bold">{{ __('Tasks') }}</span>
                        <small class="text-muted">
                            <span class="badge bg-info rounded-pill">{{ $data->tasks->count() }}</span>
                            {{ __('Total') }}
                        </small>
                    </a>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('teams.dashboard.wallet', $data->id) }}"
                        class="btn btn-outline-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center py-4 team-dashboard-btn">
                        <i class="ti ti-wallet fs-1 mb-2 text-warning"></i>
                        <span class="fw-bold">{{ __('Wallet') }}</span>
                        <small class="text-muted">{{ __('Transactions') }}</small>
                    </a>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('teams.dashboard.task-distribution', $data->id) }}"
                        class="btn btn-outline-secondary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-4 team-dashboard-btn">
                        <i class="ti ti-send fs-1 mb-2 text-secondary"></i>
                        <span class="fw-bold">{{ __('Assign Tasks') }}</span>
                        <small class="text-muted">{{ __('Distribution') }}</small>
                    </a>
                </div>
                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                    <a href="{{ route('teams.dashboard.analytics', $data->id) }}"
                        class="btn btn-outline-dark w-100 h-100 d-flex flex-column align-items-center justify-content-center py-4 team-dashboard-btn">
                        <i class="ti ti-chart-bar fs-1 mb-2 text-dark"></i>
                        <span class="fw-bold">{{ __('Analytics') }}</span>
                        <small class="text-muted">{{ __('Reports') }}</small>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Legacy Content Notice -->
    <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
        <i class="ti ti-info-circle me-3 fs-4"></i>
        <div>
            <h6 class="alert-heading mb-1">{{ __('Legacy Team Management Interface') }}</h6>
            <p class="mb-2">
                {{ __('You are viewing the legacy team management interface. For enhanced features and better user experience, please use the new dashboard above.') }}
            </p>
            <a href="{{ route('teams.dashboard.index', $data->id) }}" class="btn btn-sm btn-primary">
                <i class="ti ti-external-link me-1"></i>{{ __('Switch to New Dashboard') }}
            </a>
        </div>
    </div>

    <div class="mb-4">
        <div class="d-flex flex-column flex-sm-row gap-3 text-nowrap mt-3">

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
    <div class="mt-3">
        <div class="nav-align-top nav-tabs-shadow mb-6">
            <ul class="nav nav-tabs nav-fill  " role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active py-4" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-drivers" aria-controls="navs-drivers" aria-selected="true"><span
                            class="d-none d-sm-block"><i class="tf-icons ti  ti-steering-wheel"></i> Drivers <span
                                class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-danger ms-1_5 pt-50">{{ $data->drivers->count() }}</span></span><i
                            class="ti  ti-steering-wheel ti-sm d-sm-none"></i></button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link py-4" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-tasks" aria-controls="navs-tasks" aria-selected="false"><span
                            class="d-none d-sm-block"><i class="tf-icons ti ti-truck-delivery ti-sm me-1_5"></i> Tasks
                            <span
                                class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-danger ms-1_5 pt-50">{{ $data->tasks->count() }}</span></span><i
                            class="ti ti-truck-delivery ti-sm d-sm-none"></i></button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link py-4" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-wallet" aria-controls="navs-wallet" aria-selected="false"><span
                            class="d-none d-sm-block"><i class="tf-icons ti ti-wallet ti-sm me-1_5"></i> Wallet</span><i
                            class="ti ti-wallet ti-sm d-sm-none"></i></button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="navs-drivers" role="tabpanel">
                    <div class="card-datatable table-responsive">
                        <table class="datatables-users table">
                            <thead class="table-light">
                                <tr>
                                    <th></th>
                                    <th>#</th>
                                    <th>{{ __('name') }}</th>
                                    <th>{{ __('username') }}</th>
                                    <th>{{ __('email') }}</th>
                                    <th>{{ __('phone') }}</th>
                                    <th>{{ __('role') }}</th>
                                    <th>{{ __('tags') }}</th>
                                    <th>{{ __('status') }}</th>
                                    <th>{{ __('created at') }}</th>

                                    <th>{{ __('actions') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="navs-tasks" role="tabpanel">
                    <div class="card-datatable table-responsive">
                        <table class="datatables-tasks table table-hover align-middle mb-0  ">
                            <thead class="table-light ">
                                <tr>
                                    <th></th>
                                    <th>{{ __('task id') }}</th>
                                    <th>{{ __('price') }}</th>
                                    <th>{{ __('driver') }}</th>
                                    <th>{{ __('pickup address') }}</th>
                                    <th>{{ __('start before') }}</th>
                                    <th>{{ __('complete before') }}</th>
                                    <th>{{ __('status') }}</th>
                                    <th>{{ __('closed') }}</th>
                                    <th>{{ __('action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="navs-wallet" role="tabpanel">
                    <!-- Payment Controls -->
                    <div class="card mb-3" id="payment-controls" style="display: none;">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-check-box text-primary me-2 fs-4"></i>
                                        <span class="fw-semibold">{{ __('Selected Transactions') }}:</span>
                                        <span class="badge bg-primary ms-2" id="selected-count">0</span>
                                        <span class="fw-semibold ms-3">{{ __('Total Amount') }}:</span>
                                        <span class="badge bg-success ms-2" id="selected-total">0.00 SAR</span>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="button" class="btn btn-outline-secondary me-2" id="clear-selection">
                                        <i class="ti ti-x me-1"></i>{{ __('Clear Selection') }}
                                    </button>
                                    <button type="button" class="btn btn-success" id="process-payment"
                                        data-bs-toggle="modal" data-bs-target="#paymentModal">
                                        <i class="ti ti-credit-card me-1"></i>{{ __('Process Payment') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-datatable table-responsive">
                        <table class="table table-hover align-middle mb-0 datatables-transactions">
                            <thead class="table-light">
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="select-all-transactions" class="form-check-input">
                                    </th>
                                    <th></th>
                                    <th>#</th>
                                    <th>{{ __('Amount') }}</th>
                                    <th>{{ __('Driver') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Maturity') }}</th>
                                    <th>{{ __('Task') }}</th>
                                    <th>{{ __('status') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade " id="submitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ __('Add new Driver') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('Close') }}"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('drivers.create') }}">
                    <div class="modal-body">
                        <div class="col-xl-12">
                            <div class="nav-align-top  mb-6">
                                <ul class="nav nav-tabs " role="tablist">
                                    <li class="nav-item">
                                        <button type="button" class="nav-link active" role="tab"
                                            data-bs-toggle="tab" data-bs-target="#navs-justified-home"
                                            aria-controls="navs-justified-home" aria-selected="true"><span
                                                class="d-none d-sm-block"><i
                                                    class="tf-icons ti ti-grid-dots ti-sm me-1_5"></i> {{ __('Main') }}
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                            data-bs-target="#navs-justified-profile"
                                            aria-controls="navs-justified-profile" aria-selected="false"><span
                                                class="d-none d-sm-block"><i
                                                    class="tf-icons ti ti-file-plus ti-sm me-1_5"></i>
                                                {{ __('Additional ') }}</span></button>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="navs-justified-home" role="tabpanel">
                                        <input type="hidden" name="id" id="driver_id">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-6">
                                                    <img src="{{ url(asset('assets/img/person.png')) }}"
                                                        data-image="{{ url(asset('assets/img/person.png')) }}"
                                                        alt="" id="image"
                                                        style="width: 100%;    height: 222px;
                                                        object-fit: cover;"
                                                        class="rounded preview-image image-input">

                                                    <input type="file" class="form-control file-input-image"
                                                        id="driver-image" name="image" style="display: none" />
                                                    <span class="image-error text-danger text-error"></span>

                                                </div>
                                            </div>
                                            <div class="col-md-9">

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-fullname">*
                                                                {{ __('Full Name') }}</label>
                                                            <input type="text" class="form-control"
                                                                id="driver-fullname" placeholder="{{ __('Full Name') }}"
                                                                name="name" aria-label="{{ __('Full Name') }}" />
                                                            <span class="name-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-username">*
                                                                {{ __('Username') }}</label>
                                                            <input type="text" class="form-control"
                                                                id="driver-username" placeholder="{{ __('Username') }}"
                                                                name="username" aria-label="{{ __('Username') }}" />
                                                            <span class="username-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-email">*
                                                                {{ __('Email') }}</label>
                                                            <input type="text" id="driver-email" class="form-control"
                                                                placeholder="{{ __('example@example.com') }}"
                                                                aria-label="{{ __('example@example.com') }}"
                                                                name="email" />
                                                            <span class="email-error text-danger text-error"></span>

                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-phone">*
                                                                {{ __('Phone') }}</label>
                                                            <div class="input-group">
                                                                <select id="country-code" name="phone_code"
                                                                    class="form-select" required
                                                                    style="max-width: 120px;">
                                                                    <option value="+966">🇸🇦 +966</option>
                                                                    <option value="+971">🇦🇪 +971</option>
                                                                    <option value="+20">🇪🇬 +20</option>
                                                                    <option value="+1">🇺🇸 +1</option>
                                                                </select>
                                                                <input type="tel" id="driver-phone"
                                                                    class="form-control"
                                                                    placeholder="{{ __('Enter phone number') }}"
                                                                    name="phone" />
                                                            </div>
                                                            <span class="phone-error text-danger text-error"></span>
                                                            <span
                                                                class="phone_code_code-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-password">*
                                                                {{ __('Password') }}</label>
                                                            <input type="password" id="driver-password"
                                                                class="form-control" name="password" />
                                                            <span class="password-error text-danger text-error"></span>

                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-re-password">*
                                                                {{ __('Confirm Password') }}</label>
                                                            <input type="password" id="driver-re-password"
                                                                class="form-control" name="confirm-password" />
                                                            <span
                                                                class="confirm-password-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-role">*
                                                                {{ __('Driver Role') }}</label>
                                                            <select id="driver-role" class="form-select" name="role">
                                                                <option value="">-- {{ __('Select Role') }}
                                                                </option>
                                                                @foreach ($roles as $key)
                                                                    <option value="{{ $key->id }}">
                                                                        {{ $key->name }}</option>
                                                                @endforeach
                                                            </select>
                                                            <span class="role-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <label class="form-label" for="driver-address">*
                                                                {{ __('Home Address') }}</label>
                                                            <input type="text" name="address" class="form-control"
                                                                id="driver-address"
                                                                placeholder="{{ __('enter home address') }}" />
                                                            <span class="address-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <label class="form-label" for="driver-commission-type">
                                                                {{ __('Commission') }}</label>
                                                            <div class="input-group">
                                                                <select name="commission_type" id="driver-commission-type"
                                                                    class="form-select">
                                                                    <option value="">
                                                                        {{ __('Select Commission Type') }}</option>
                                                                    <option value="rate">{{ __('ٌRate') }}</option>
                                                                    <option value="fixed">{{ __('Fixed Amount') }}
                                                                    </option>
                                                                    <option value="subscription">
                                                                        {{ __('Subscription Monthly') }}</option>
                                                                </select>
                                                                <input type="number" name="commission"
                                                                    class="form-control" step="1"
                                                                    id="driver-commission"
                                                                    placeholder="{{ __('Commission Amount') }}" />
                                                            </div>
                                                            <span
                                                                class="commission_type-error text-danger text-error"></span>
                                                            <span class="commission-error text-danger text-error"></span>


                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>



                                        <div class="mb-3">
                                            <div class="divider text-start">
                                                <div class="divider-text"><strong>{{ __('Vehicle Selection') }}</strong>
                                                </div>
                                            </div>

                                            <div id="vehicle-selection-container">
                                                <!-- سيتم توليد السطور ديناميكيًا هنا -->
                                            </div>
                                        </div>


                                    </div>
                                    <div class="tab-pane fade" id="navs-justified-profile" role="tabpanel">
                                        <div class="form-group">
                                            <label for="select-template">{{ __('Select Template') }}</label>
                                            <select name="template" id="select-template" class="form-select w-auto">
                                                <option value="">{{ __('-- Select Template') }}</option>
                                                @foreach ($templates as $key)
                                                    <option value="{{ $key->id }}"
                                                        {{ $driver_template->value == $key->id ? 'selected' : '' }}>
                                                        {{ $key->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div id="additional-form" class="row mt-4">

                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>



                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">{{ __('Submit') }}</button>

                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">
                        <i class="ti ti-credit-card me-2"></i>{{ __('Process Team Payment') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        @csrf
                        <input type="hidden" name="team_id" value="{{ $data->id }}">
                        <!-- Total Amount Section -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="totalPaymentAmount" class="form-label fw-semibold">
                                    <i class="ti ti-calculator me-1"></i>{{ __('Total Payment Amount') }}
                                </label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="totalPaymentAmount"
                                        name="total_amount" step="0.01" min="0" required>
                                    <span class="input-group-text">{{ __('SAR') }}</span>
                                </div>
                                <small class="text-muted">{{ __('Maximum amount') }}: <span id="maxAmountDisplay"
                                        class="fw-bold text-primary">0.00 SAR</span></small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Payment Summary') }}</label>
                                <div class="card ">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between">
                                            <span>{{ __('Selected Transactions') }}:</span>
                                            <span class="fw-bold" id="modal-selected-count">0</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>{{ __('Original Total') }}:</span>
                                            <span class="fw-bold" id="modal-original-total">0.00 SAR</span>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold">{{ __('Payment Amount') }}:</span>
                                            <span class="fw-bold text-success" id="modal-payment-total">0.00 SAR</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Selected Transactions Table -->
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3">
                                <i class="ti ti-list me-1"></i>{{ __('Selected Transactions for Payment') }}
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="60">#</th>
                                            <th>{{ __('Driver') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th>{{ __('Original Amount') }}</th>
                                            <th>{{ __('Payment Amount') }}</th>
                                            <th width="60">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedTransactionsTable">
                                        <!-- Dynamic content will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Payment Notes -->
                        <div class="mb-3">
                            <label for="paymentNotes" class="form-label">
                                <i class="ti ti-note me-1"></i>{{ __('Payment Notes') }}
                                <small class="text-muted">({{ __('Optional') }})</small>
                            </label>
                            <textarea class="form-control" id="paymentNotes" name="notes" rows="3"
                                placeholder="{{ __('Add any notes about this payment (e.g., payment method, reference number, special instructions)...') }}"></textarea>
                            <small class="text-muted">
                                <i class="ti ti-info-circle me-1"></i>
                                {{ __('These notes will be included in the payment description for each driver\'s wallet transaction') }}
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i>{{ __('Cancel') }}
                    </button>
                    <button type="button" class="btn btn-success" id="confirmPayment">
                        <i class="ti ti-check me-1"></i>{{ __('Confirm Payment') }}
                    </button>
                </div>
            </div>
        </div>
    </div>


@endsection
