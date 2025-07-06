@extends('layouts/layoutMaster')

@section('title', __('Team Drivers') . ' - ' . $team->name)
@section('teams-isactive')
    active
@endsection
<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    @vite(['resources/css/app.css'])

    <style>
        /* Wallet Statistics Cards Styling */
        .wallet-stats-card {
            transition: all 0.3s ease;
            border-radius: 12px;
            overflow: hidden;
        }

        .wallet-stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }

        .wallet-stats-card .avatar-initial {
            background: linear-gradient(135deg, var(--bs-primary), var(--bs-primary-dark));
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .wallet-stats-card .bg-success {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
        }

        .wallet-stats-card .bg-primary {
            background: linear-gradient(135deg, #007bff, #6610f2) !important;
        }

        .wallet-stats-card .bg-danger {
            background: linear-gradient(135deg, #dc3545, #e83e8c) !important;
        }

        .wallet-stats-card .progress {
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 3px;
        }

        .wallet-stats-card .progress-bar {
            border-radius: 3px;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.1));
        }

        .wallet-stats-card h3 {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .wallet-stats-card h3 {
                font-size: 1.5rem;
            }
        }
    </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    <script>
        const templateId = {{ $driver_template->value ?? 0 }}
        const teamID = {{ $team->id }}
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
    @vite(['resources/js/admin/teams/dashboard/drivers.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])
@endsection

@section('content')
    <!-- Breadcrumbs -->
    @include('admin.teams.dashboard.partials.breadcrumbs', ['team' => $team])

    <!-- Navigation -->
    @include('admin.teams.dashboard.partials.navigation', ['team' => $team])

    <!-- Drivers Wallet Statistics -->
    @php
        $totalCredit = $walletStats['total_credit'] ?? 0;
        $totalDebit = $walletStats['total_debit'] ?? 0;
        $netBalance = $walletStats['net_balance'] ?? 0;
        $balanceClass = $netBalance >= 0 ? 'success' : 'danger';
        $balanceIcon = $netBalance >= 0 ? 'ti-trending-up' : 'ti-trending-down';
    @endphp

    <div class="row g-4 mb-6">
        <!-- Total Credit Card -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100 wallet-stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg me-4">
                            <div class="avatar-initial bg-success rounded-circle">
                                <i class="ti ti-arrow-up-right ti-lg text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="mb-0 text-success">{{ __('Total Credit') }}</h6>
                                <i class="ti ti-info-circle text-muted" data-bs-toggle="tooltip"
                                    title="{{ __('Total amount credited to all drivers wallets') }}"></i>
                            </div>
                            <h3 class="mb-0 text-success fw-bold">{{ number_format($totalCredit, 2) }}</h3>
                            <small class="text-muted">
                                <i class="ti ti-currency-riyal me-1"></i>{{ __('Saudi Riyal') }}
                            </small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="ti ti-users me-1"></i>{{ __('All team drivers combined') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Debit Card -->
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm h-100 wallet-stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg me-4">
                            <div class="avatar-initial bg-primary rounded-circle">
                                <i class="ti ti-arrow-down-left ti-lg text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="mb-0 text-primary">{{ __('Total Debit') }}</h6>
                                <i class="ti ti-info-circle text-muted" data-bs-toggle="tooltip"
                                    title="{{ __('Total amount debited from all drivers wallets') }}"></i>
                            </div>
                            <h3 class="mb-0 text-primary fw-bold">{{ number_format($totalDebit, 2) }}</h3>
                            <small class="text-muted">
                                <i class="ti ti-currency-riyal me-1"></i>{{ __('Saudi Riyal') }}
                            </small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: 100%"></div>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="ti ti-wallet me-1"></i>{{ __('Available balance for drivers') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Net Balance Card -->
        <div class="col-xl-4 col-md-12">
            <div class="card border-0 shadow-sm h-100 wallet-stats-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg me-4">
                            <div class="avatar-initial bg-{{ $balanceClass }} rounded-circle">
                                <i class="ti {{ $balanceIcon }} ti-lg text-white"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="mb-0 text-{{ $balanceClass }}">{{ __('Net Balance') }}</h6>
                                <i class="ti ti-info-circle text-muted" data-bs-toggle="tooltip"
                                    title="{{ __('Difference between total debit and credit (Debit - Credit)') }}"></i>
                            </div>
                            <h3 class="mb-0 text-{{ $balanceClass }} fw-bold">
                                {{ $netBalance >= 0 ? '+' : '' }}{{ number_format($netBalance, 2) }}
                            </h3>
                            <small class="text-muted">
                                <i class="ti ti-currency-riyal me-1"></i>{{ __('Saudi Riyal') }}
                            </small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $balanceClass }}" style="width: 100%"></div>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="ti ti-calculator me-1"></i>
                            @if ($netBalance >= 0)
                                {{ __('Positive balance - drivers have funds') }}
                            @else
                                {{ __('Negative balance - drivers owe money') }}
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Drivers Management -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="ti ti-steering-wheel me-2"></i>{{ __('Team Drivers Management') }}
            </h5>

        </div>



        <!-- Drivers Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="datatables-users table">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>#</th>
                            <th>{{ __('name') }}</th>
                            <th>{{ __('balance') }}</th>
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
    </div>

    <!-- Add/Edit Driver Modal -->
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
                                        <div class="form-group d-none ">
                                            <label for="select-template">{{ __('Select Template') }}</label>
                                            <select name="template" id="select-template" class=" form-select w-auto">
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
@endsection
