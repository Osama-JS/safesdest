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
@endsection

@section('content')

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
        }
    </style>

    @php
        $balance = ($totals['debit'] ?? 0) - ($totals['credit'] ?? 0);
        $credit = $totals['credit'] ?? 0;
        $debit = $totals['debit'] ?? 0;

        $balanceClass = $balance < 0 ? 'text-danger' : 'text-success';
        $balanceSign = $balance < 0 ? '-' : '+';

    @endphp


    <div class="mb-4">
        <h5>{{ $data->name }} [{{ $data->id }}]</h5>
        <p>
            <i class="tf-icons ti  ti-location"></i>
            {{ $data->address }}
        </p>
        <span class="badge bg-label-secondary">
            <i class="tf-icons ti  ti-steering-wheel"></i>
            <b>{{ $data->drivers->count() }}</b>
        </span>
        <span class="badge bg-label-secondary">
            <i class=" tf-icons ti ti-truck-delivery"></i>
            <b>{{ $data->drivers->count() }}</b>
        </span>
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
                            class="d-none d-sm-block"><i class="tf-icons ti ti-truck-delivery ti-sm me-1_5"></i> Tasks <span
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
                                                                <option value="">-- {{ __('Select Role') }}</option>
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
                                    <span class="input-group-text">SAR</span>
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
