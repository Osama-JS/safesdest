@extends('layouts/layoutMaster')

@section('title', __('Drives'))

<!-- Vendor Styles -->
@section('vendor-style')

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

    @vite(['resources/css/app.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])

    <script>
        const templateId = {{ $driver_template?->value ?? 0 }}
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
        <script type="text/template" id="driver-broker-row-template">
          <div class="row broker-row mb-3 align-items-end" data-index="{index}">
            <div class="col-md-3">
              <label class="form-label">Broker</label>
              <select class="form-select select2 broker-select" name="brokers[{index}][broker_id]" required>
                <option value="">Select a broker</option>
                @foreach ($brokers as $broker)
                      <option value="{{ $broker->id }}">{{ $broker->name }} ({{ $broker->username }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Commission Type</label>
              <select class="form-select" name="brokers[{index}][commission_type]" required>
                <option value="percentage">Percentage</option>
                <option value="fixed">Fixed</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Commission Value</label>
              <input type="number" class="form-control" name="brokers[{index}][commission_value]" step="0.01" min="0" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Start Date</label>
              <input type="date" class="form-control" name="brokers[{index}][commission_start_date]">
            </div>
            <div class="col-md-1">
              <button type="button" class="btn btn-sm btn-icon btn-danger remove-broker-row"><i class="ti ti-trash"></i></button>
            </div>
          </div>
        </script>
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/drivers/drivers.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])
@endsection

@section('content')

    <div class="row g-6 mb-6">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Drivers') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total"></h4>

                            </div>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-user ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Active Drivers') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total-active"></h4>
                                <p class="text-success mb-0">
                                </p>
                            </div>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Pending Drivers') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total-pending"></h4>
                                <p class="text-success mb-0">
                                </p>

                                </p>
                            </div>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-user-search ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Blocked Drivers') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total-blocked"></h4>
                                <p class="text-success mb-0">
                                </p>

                                </p>
                            </div>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-users ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Unverified Drivers') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total-verified"></h4>
                                <p class="text-success mb-0">
                                </p>

                                </p>
                            </div>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-secondary">
                                <i class="ti ti-hourglass ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Users List Table -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">
                <i class="tf-icons ti ti-steering-wheel me-2 fs-3 text-white bg-primary rounded p-1"></i>
                {{ __('Drivers') }}
            </h5>
            <button class="add-new btn btn-primary waves-effect waves-light mt-5 mx-4" data-bs-toggle="modal"
                data-bs-target="#submitModal">
                <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                <span class="d-none d-sm-inline-block"> {{ __('Add New Driver') }}</span>
            </button>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-users table">
                <thead class="border-top">
                    <tr>
                        <th></th>
                        <th>#</th>
                        <th>{{ __('Driver Code') }}</th>
                        <th>{{ __('name') }}</th>
                        <th>{{ __('username') }}</th>
                        <th>{{ __('email') }}</th>
                        <th>{{ __('phone') }}</th>
                        <th><i class="ti ti-brand-whatsapp text-success me-1"></i>{{ __('WhatsApp') }}</th>
                        <th>{{ __('team') }}</th>
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

    <div class="modal fade " id="submitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ __('Add new Driver') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('drivers.create') }}">
                    <div class="modal-body">
                        <div class="col-xl-12">
                            <div class="nav-align-top  mb-6">
                                <ul class="nav nav-tabs " role="tablist">
                                    <li class="nav-item">
                                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                            data-bs-target="#navs-justified-home" aria-controls="navs-justified-home"
                                            aria-selected="true"><span class="d-none d-sm-block"><i
                                                    class="tf-icons ti ti-grid-dots ti-sm me-1_5"></i> {{ __('Main') }}
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                            data-bs-target="#navs-justified-profile" aria-controls="navs-justified-profile"
                                            aria-selected="false"><span class="d-none d-sm-block"><i
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
                                                        data-image="{{ url(asset('assets/img/person.png')) }}" alt=""
                                                        id="image" style="width: 100%;    height: 222px;
                                                            object-fit: cover;" class="rounded preview-image image-input">

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
                                                            <input type="text" class="form-control" id="driver-fullname"
                                                                placeholder="{{ __('Full Name') }}" name="name"
                                                                aria-label="{{ __('Full Name') }}" />
                                                            <span class="name-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-username">*
                                                                {{ __('Username') }}</label>
                                                            <input type="text" class="form-control" id="driver-username"
                                                                placeholder="{{ __('Username') }}" name="username"
                                                                aria-label="{{ __('Username') }}" />
                                                            <span class="username-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-email">*
                                                                {{ __('Email') }}</label>
                                                            <input type="text" id="driver-email" class="form-control"
                                                                placeholder="{{ __('example@example.com') }}"
                                                                aria-label="{{ __('example@example.com') }}" name="email" />
                                                            <span class="email-error text-danger text-error"></span>

                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-phone">*
                                                                {{ __('Phone') }}</label>
                                                            <div class="input-group">
                                                                <select id="country-code" name="phone_code"
                                                                    class="form-select" required style="max-width: 120px;">
                                                                    <option value="+966">🇸🇦 +966</option>
                                                                    <option value="+971">🇦🇪 +971</option>
                                                                    <option value="+20">🇪🇬 +20</option>
                                                                    <option value="+1">🇺🇸 +1</option>
                                                                </select>
                                                                <input type="tel" id="driver-phone" class="form-control"
                                                                    placeholder="{{ __('Enter phone number') }}"
                                                                    name="phone" />
                                                            </div>
                                                            <span class="phone-error text-danger text-error"></span>
                                                            <span
                                                                class="phone_code_code-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <!-- WhatsApp Section -->
                                                    <div class="col-md-12">
                                                        <div class="mb-4">
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="phone_is_whatsapp" id="phone-is-whatsapp"
                                                                    value="1">
                                                                <label class="form-check-label" for="phone-is-whatsapp">
                                                                    <i class="ti ti-brand-whatsapp me-1 text-success"></i>
                                                                    {{ __('Phone number is WhatsApp number') }}
                                                                </label>
                                                            </div>
                                                            <small class="form-text text-muted">
                                                                <i class="ti ti-info-circle me-1"></i>
                                                                {{ __('Check this if the phone number above is also the WhatsApp number') }}
                                                            </small>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6" id="whatsapp-fields">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="whatsapp-number">
                                                                <i class="ti ti-brand-whatsapp me-1 text-success"></i>
                                                                {{ __('WhatsApp Number') }}
                                                            </label>
                                                            <div class="input-group">
                                                                <select id="whatsapp-country-code"
                                                                    name="whatsapp_country_code" class="form-select"
                                                                    style="max-width: 120px;">
                                                                    <option value="">{{ __('Code') }}</option>
                                                                    <option value="+966">🇸🇦 +966</option>
                                                                    <option value="+971">🇦🇪 +971</option>
                                                                    <option value="+20">🇪🇬 +20</option>
                                                                    <option value="+1">🇺🇸 +1</option>
                                                                </select>
                                                                <input type="tel" id="whatsapp-number" class="form-control"
                                                                    placeholder="{{ __('Enter WhatsApp number') }}"
                                                                    name="whatsapp_number" />
                                                            </div>
                                                            <span
                                                                class="whatsapp_number-error text-danger text-error"></span>
                                                            <span
                                                                class="whatsapp_country_code-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-password">*
                                                                {{ __('Password') }}</label>
                                                            <input type="password" id="driver-password" class="form-control"
                                                                name="password" />
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
                                                            <label class="form-label" for="driver-team">*
                                                                {{ __('Team') }}</label>
                                                            <select id="driver-team" class="form-select" name="team">
                                                                <option value="">-- {{ __('Select Team') }}</option>
                                                                @foreach ($teams as $key)
                                                                    <option value="{{ $key->id }}">
                                                                        {{ $key->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <span class="team-error text-danger text-error"></span>
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
                                                                        {{ $key->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <span class="role-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-can-edit-profile">
                                                                {{ __('Can Edit Profile') }}</label>
                                                            <select id="driver-can-edit-profile" class="form-select" name="can_edit_profile">
                                                                <option value="1">{{ __('Yes') }}</option>
                                                                <option value="0">{{ __('No') }}</option>
                                                            </select>
                                                            <span class="can-edit-profile-error text-danger text-error"></span>
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
                                                                        {{ __('Select Commission Type') }}
                                                                    </option>
                                                                    <option value="rate">{{ __('ٌRate') }}</option>
                                                                    <option value="fixed">{{ __('Fixed Amount') }}
                                                                    </option>
                                                                    <option value="subscription">
                                                                        {{ __('Subscription Monthly') }}
                                                                    </option>
                                                                </select>
                                                                <input type="number" name="commission" class="form-control"
                                                                    step="1" id="driver-commission"
                                                                    placeholder="{{ __('Commission Amount') }}" />
                                                            </div>
                                                            <span
                                                                class="commission_type-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-12">
                                                        <div class="divider text-start">
                                                            <div class="divider-text">
                                                                <strong><i
                                                                        class="ti ti-truck me-2"></i>{{ __('Truck Broker Details') }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12"><div id="driver-brokers-container"></div><button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-driver-broker-row"><i class="ti ti-plus me-1"></i> {{ __("Add Broker") }}</button></div>

                                                    <!-- Bank Details Section -->
                                                    <div class="col-md-12">
                                                        <div class="divider text-start">
                                                            <div class="divider-text">
                                                                <strong><i
                                                                        class="ti ti-building-bank me-2"></i>{{ __('Bank Details') }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="driver-bank-name">{{ __('Bank Name') }}</label>
                                                            <select name="bank_name" id="driver-bank-name"
                                                                class="form-select">
                                                                <option value="">{{ __('Select Bank') }}</option>
                                                                @foreach($banks as $bank)
                                                                    <option value="{{ $bank->name }}"
                                                                        data-code="{{ $bank->code }}">{{ $bank->name }}</option>
                                                                @endforeach
                                                            </select>
                                                            <span class="bank_name-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4" id="driver-custom-bank-field"
                                                        style="display: none;">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="driver-custom-bank-name">{{ __('Custom Bank Name') }}</label>
                                                            <input type="text" name="custom_bank_name"
                                                                id="driver-custom-bank-name" class="form-control"
                                                                placeholder="{{ __('Enter bank name') }}">
                                                            <span
                                                                class="custom_bank_name-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="driver-account-number">{{ __('Account Number') }}</label>
                                                            <input type="text" name="account_number"
                                                                id="driver-account-number" class="form-control"
                                                                placeholder="1234567890" pattern="[0-9]{8,20}" minlength="8"
                                                                maxlength="20">
                                                            <div class="form-text">
                                                                <small
                                                                    class="text-muted">{{ __('Numbers only, 8-20 digits') }}</small>
                                                            </div>
                                                            <span
                                                                class="account_number-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="driver-iban-number">{{ __('IBAN Number') }}</label>
                                                            <input type="text" name="iban_number" id="driver-iban-number"
                                                                class="form-control"
                                                                placeholder="SA12 3456 7890 1234 5678 90" maxlength="29"
                                                                pattern="SA(?:[0-9]{2}\s?){11}">
                                                            <div class="form-text">
                                                                <small
                                                                    class="text-muted">{{ __('Format: SA + 22 digits') }}</small>
                                                            </div>
                                                            <span class="iban_number-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="driver-bic-code">{{ __('BIC Code (Bank Identifier)') }}</label>
                                                            <input type="text" name="bic_code" id="driver-bic-code"
                                                                class="form-control" placeholder="RJHISARI" maxlength="20">
                                                            <div class="form-text">
                                                                <small
                                                                    class="text-muted">{{ __('e.g., RJHISARI for Al-Rajhi') }}</small>
                                                            </div>
                                                            <span class="bic_code-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-8">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="driver-beneficiary-name">{{ __('Beneficiary Name (Official)') }}</label>
                                                            <input type="text" name="beneficiary_name"
                                                                id="driver-beneficiary-name" class="form-control"
                                                                placeholder="{{ __('Full name as per bank records') }}">
                                                            <span
                                                                class="beneficiary_name-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="driver-bank-address1">{{ __('Bank Address 1') }}</label>
                                                            <input type="text" name="bank_address1"
                                                                id="driver-bank-address1" class="form-control"
                                                                placeholder="{{ __('Street address') }}">
                                                            <span class="bank_address1-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="driver-bank-address2">{{ __('Bank Address 2') }}</label>
                                                            <input type="text" name="bank_address2"
                                                                id="driver-bank-address2" class="form-control"
                                                                placeholder="{{ __('Additional details') }}">
                                                            <span class="bank_address2-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="driver-bank-city">{{ __('Bank City') }}</label>
                                                            <input type="text" name="bank_city" id="driver-bank-city"
                                                                class="form-control" placeholder="{{ __('City') }}">
                                                            <span class="bank_city-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <label class="form-label"
                                                                for="driver-bank-country">{{ __('Bank Country') }}</label>
                                                            <select name="bank_country" id="driver-bank-country"
                                                                class="form-select">
                                                                <option value="SA" selected>Saudi Arabia (SA)</option>
                                                                <option value="AE">United Arab Emirates (AE)</option>
                                                                <option value="EG">Egypt (EG)</option>
                                                            </select>
                                                            <span class="bank_country-error text-danger text-error"></span>
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
                                                    <option value="{{ $key->id }}" {{ $driver_template?->value == $key->id ? 'selected' : '' }}>
                                                        {{ $key->name }}
                                                    </option>
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

    {{-- Include Notification Modal --}}
    @include('admin.partials.notification-modal')

    {{-- Include Signature Modal --}}
    @include('admin.partials.signature-modal')
@endsection
