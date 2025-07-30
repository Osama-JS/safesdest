@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Str;
    $configData = Helper::appClasses();
    $customizerHidden = 'customizer-hide';
    $configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Register Page')

@section('page-style')
    <!-- Page -->
    @vite('resources/assets/vendor/scss/pages/page-auth.scss')
    <style>
        .account-types-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }

        .account-type-card {
            position: relative;
            cursor: pointer;
        }

        .account-type-input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .account-type-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.25rem 0.75rem;
            background: #ffffff;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: #495057;
            position: relative;
            overflow: hidden;
        }

        .account-type-label::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }

        .account-type-card:hover .account-type-label::before {
            left: 100%;
        }

        .account-type-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            transition: transform 0.3s ease;
        }

        .account-type-title {
            font-size: 0.875rem;
            font-weight: 600;
            text-align: center;
            margin: 0;
        }

        .account-type-card:hover .account-type-label {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: #dee2e6;
        }

        .account-type-card:hover .account-type-icon {
            transform: scale(1.1);
        }

        .account-type-input:checked+.account-type-label {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .account-type-input:checked+.account-type-label .account-type-icon,
        .account-type-input:checked+.account-type-label .account-type-title {
            color: white;
            transform: scale(1.15);
        }

        /* Customer Type Colors */
        .customer-icon {
            color: #007bff;
        }

        .driver-icon {
            color: #28a745;
        }

        .broker-icon {
            color: #6f42c1;
        }

        /* Form Enhancements */
        .form-floating {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-floating .form-control {
            height: 3.5rem;
            padding: 1rem 0.75rem 0.25rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .form-floating .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }

        .form-floating label {
            padding: 1rem 0.75rem;
            color: #6c757d;
            font-weight: 500;
        }


        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }



        .forgot-password-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-password-link:hover {
            color: #764ba2;
        }

        .register-link {
            text-align: center;
            margin-top: 1rem;
            padding-top: 5px;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-link a:hover {
            color: #764ba2;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .account-types-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .account-type-label {
                flex-direction: row;
                justify-content: flex-start;
                padding: 1rem;
                text-align: left;
            }

            .account-type-icon {
                margin-bottom: 0;
                margin-right: 1rem;
                font-size: 1.5rem;
            }

            .login-card {
                margin: 1rem;
                border-radius: 15px;
            }
        }

        /* Loading Animation */
        .btn-login.loading {
            pointer-events: none;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endsection

@section('page-script')
    <script>
        const CustomerTemplate = {!! json_encode($customer_template) !!}
        const DriverTemplate = {!! json_encode($driver_template) !!}
        const BrokerTemplate = {!! json_encode($broker_template) !!}
    </script>
    @vite(['resources/js/auth.js'])


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
@section('content')
    <div class="authentication-wrapper authentication-cover">
        <!-- Logo -->
        <a href="{{ url('/') }}" class="app-brand auth-cover-brand">
            <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20, 'withbg' => 'fill: #fff;'])</span>
            <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
        </a>
        <!-- /Logo -->
        <div class="authentication-inner row m-0">
            <!-- /Left Text -->
            <div class="d-none d-lg-flex col-lg-7 p-0">
                <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center position-sticky"
                    style="top: 0;">
                    <img src="{{ asset('assets/img/illustrations/auth-register-illustration-' . $configData['style'] . '.png') }}"
                        alt="auth-register-cover" class="my-5 auth-illustration"
                        data-app-light-img="illustrations/auth-register-illustration-light.png"
                        data-app-dark-img="illustrations/auth-register-illustration-dark.png">

                    <img src="{{ asset('assets/img/illustrations/bg-shape-image-' . $configData['style'] . '.png') }}"
                        alt="auth-register-cover" class="platform-bg"
                        data-app-light-img="illustrations/bg-shape-image-light.png"
                        data-app-dark-img="illustrations/bg-shape-image-dark.png">
                </div>
            </div>

            <!-- /Left Text -->

            <!-- Register -->
            <div class="d-flex col-12 col-lg-5 align-items-center authentication-bg p-sm-12 p-3">
                <div class="w-px-500 mx-auto mt-12 pt-5">
                    <h4 class="mb-1">Adventure starts here 🚀</h4>
                    <p class="mb-6">Make your app management easy and fun!</p>
                    <div class="nav-align-top mb-6">
                        <div class="account-types-grid" role="tablist">
                            <div class="account-type-card">
                                <input type="radio" id="customer" name="account_type" value="customer"
                                    class="account-type-input" checked />
                                <label for="customer" class="account-type-label active" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-customers" aria-controls="navs-customers" aria-selected="true">
                                    <i class="ti ti-user account-type-icon customer-icon"></i>
                                    <span class="account-type-title">Customer</span>
                                </label>
                            </div>

                            <div class="account-type-card">
                                <input type="radio" id="driver" name="account_type" value="driver"
                                    class="account-type-input" />
                                <label for="driver" class="account-type-label" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-drivers" aria-controls="navs-drivers" aria-selected="false">
                                    <i class="ti ti-car account-type-icon driver-icon"></i>
                                    <span class="account-type-title">Driver</span>
                                </label>
                            </div>

                            <div class="account-type-card">
                                <input type="radio" id="broker" name="account_type" value="broker"
                                    class="account-type-input" />
                                <label for="broker" class="account-type-label" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-brokers" aria-controls="navs-brokers" aria-selected="false">
                                    <i class="ti ti-building account-type-icon broker-icon"></i>
                                    <span class="account-type-title">Customs Broker</span>
                                </label>
                            </div>
                        </div>


                        <div class="tab-content">
                            <!-- Customer Tab -->
                            <div class="tab-pane fade show active" id="navs-customers" role="tabpanel">
                                <form class=" form_auth" action="{{ route('register.customer') }}" method="POST">
                                    @csrf
                                    <div class="nav-align-top mb-6">
                                        <input type="hidden" name="template" value="{{ $customer->value }}">
                                        <div class="row" id="additional-customer-form">
                                            <!-- Full Name -->
                                            <div class="col-md-12">
                                                <div class="mb-6">
                                                    <label class="form-label" for="customer-fullname">
                                                        * {{ __('Full Name') }}
                                                    </label>
                                                    <input type="text" class="form-control" id="customer-fullname"
                                                        placeholder="{{ __('Full Name') }}" name="name"
                                                        aria-label="{{ __('Full Name') }}" />
                                                    <span class="name-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Email -->
                                            <div class="col-md-6">
                                                <div class="mb-6">
                                                    <label class="form-label" for="customer-email">
                                                        * {{ __('Email') }}
                                                    </label>
                                                    <input type="text" id="customer-email" class="form-control"
                                                        placeholder="{{ __('example@example.com') }}"
                                                        aria-label="{{ __('example@example.com') }}" name="email" />
                                                    <span class="email-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Phone -->
                                            <div class="col-md-6">
                                                <div class="mb-6">
                                                    <label class="form-label" for="customer-phone">*
                                                        {{ __('Phone') }}</label>
                                                    <div class="input-group">
                                                        <select id="country-code" name="phone_code" class="form-select"
                                                            required style="max-width: 120px;">
                                                            <option value="+966">🇸🇦 +966</option>
                                                            <option value="+971">🇦🇪 +971</option>
                                                            <option value="+20">🇪🇬 +20</option>
                                                            <option value="+1">🇺🇸 +1</option>
                                                        </select>
                                                        <input type="tel" id="customer-phone" class="form-control"
                                                            placeholder="{{ __('Enter phone number') }}"
                                                            name="phone" />
                                                    </div>
                                                    <span class="phone-error text-danger text-error"></span>
                                                    <span class="phone_code_code-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Password -->
                                            <div class="col-md-6">
                                                <div class="mb-6">
                                                    <label class="form-label" for="customer-password">*
                                                        {{ __('Password') }}</label>
                                                    <input type="password" id="customer-password" class="form-control"
                                                        name="password" />
                                                    <span class="password-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Confirm Password -->
                                            <div class="col-md-6">
                                                <div class="mb-6">
                                                    <label class="form-label" for="customer-re-password">*
                                                        {{ __('Confirm Password') }}</label>
                                                    <input type="password" id="customer-re-password" class="form-control"
                                                        name="confirm-password" />
                                                    <span class="confirm-password-error text-danger text-error"></span>
                                                </div>
                                            </div>



                                            <!-- Company Name -->
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label class="form-label" for="customer-c_name">
                                                        {{ 'Company Name' }}</label>
                                                    <input type="text" name="c_name" class="form-control"
                                                        id="customer-c_name"
                                                        placeholder="{{ __('enter company name') }}" />
                                                    <span class="c_name-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Company Address -->
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label class="form-label" for="customer-c_address">
                                                        {{ 'Company Address' }}</label>
                                                    <input type="text" name="c_address" class="form-control"
                                                        id="customer-c_address"
                                                        placeholder="{{ __('enter company address') }}" />
                                                    <span class="c_address-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            {{-- <div id="additional-customer-form" class="row ">

                                            </div> --}}
                                        </div>
                                    </div>
                                    <div class="mb-6">
                                        @error('recaptcha')
                                            <span class="invalid-feedback" role="alert">
                                                <span class="fw-medium">{{ $message }}</span>
                                            </span>
                                        @enderror
                                        <label class="form-label" for="login-password">confirm that you ar not a
                                            robot</label>
                                        {!! htmlFormSnippet() !!}

                                    </div>
                                    {{-- <div class="mb-6">

                                        <div class="form-group">
                                            <label class="form-label" for="login-password">Enter the code in the
                                                image</label>
                                            <div class="captcha mb-2">
                                                <img src="{{ captcha_src() }}" alt="captcha" id="captcha-image"
                                                    style="height: 60px;">
                                                <button type="button"
                                                    class="btn btn-outline-seconde btn-refresh">↻</button>
                                            </div>


                                            <input type="text" class="form-control" name="captcha" required>
                                            <span class="captcha-error text-danger text-error"></span>

                                        </div>

                                    </div> --}}


                                    <!-- Terms -->
                                    <div id="additional-form" class="row mt-4">
                                        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                                            <div class="mb-6 mt-8">
                                                <div class="form-check mb-8 ms-2 @error('terms') is-invalid @enderror">
                                                    <input class="form-check-input @error('terms') is-invalid @enderror"
                                                        type="checkbox" id="terms" name="terms" />
                                                    <label class="form-check-label" for="terms">
                                                        I agree to the
                                                        <a href="{{ route('policy.show') }}" target="_blank">privacy
                                                            policy</a> &
                                                        <a href="{{ route('terms.show') }}" target="_blank">terms</a>
                                                    </label>
                                                </div>
                                                @error('terms')
                                                    <div class="invalid-feedback" role="alert">
                                                        <span class="fw-medium">{{ $message }}</span>
                                                    </div>
                                                @enderror
                                            </div>
                                        @endif
                                    </div>

                                    <button type="submit" class="btn btn-primary d-grid w-100">Sign up</button>
                                </form>
                            </div>

                            <!-- Driver Tab -->
                            <div class="tab-pane fade" id="navs-drivers" role="tabpanel">
                                <form id="formAuthentication" class="mb-6 form_auth"
                                    action="{{ route('register.driver') }}" method="POST">
                                    @csrf
                                    <div class="nav-align-top mb-6">


                                        <div class="row" id="additional-driver-form">
                                            <div class="col-md-12">
                                                <div class="mb-6">
                                                    <label class="form-label" for="driver-fullname">*
                                                        {{ __('Full Name') }}</label>
                                                    <input type="text" class="form-control" id="driver-fullname"
                                                        placeholder="{{ __('Full Name') }}" name="name"
                                                        aria-label="{{ __('Full Name') }}" />
                                                    <span class="name-error text-danger text-error"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
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
                                                        <select id="country-code" name="phone_code" class="form-select"
                                                            required style="max-width: 120px;">
                                                            <option value="+966">🇾🇪 +966</option>
                                                            <option value="+971">🇦🇪 +971</option>
                                                            <option value="+20">🇪🇬 +20</option>
                                                            <option value="+1">🇺🇸 +1</option>
                                                        </select>
                                                        <input type="tel" id="driver-phone" class="form-control"
                                                            placeholder="{{ __('Enter phone number') }}"
                                                            name="phone" />
                                                    </div>
                                                    <span class="phone-error text-danger text-error"></span>
                                                    <span class="phone_code_code-error text-danger text-error"></span>
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
                                                    <input type="password" id="driver-re-password" class="form-control"
                                                        name="confirm-password" />
                                                    <span class="confirm-password-error text-danger text-error"></span>
                                                </div>
                                            </div>


                                            <div class="col-md-12">
                                                <div class="mb-4">
                                                    <label class="form-label" for="driver-address">*
                                                        {{ 'Home Address' }}</label>
                                                    <input type="text" name="address" class="form-control"
                                                        id="driver-address"
                                                        placeholder="{{ __('enter home address') }}" />
                                                    <span class="address-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Team Selection -->
                                            <div class="col-md-12">
                                                <div class="mb-4">
                                                    <label class="form-label" for="driver-team">
                                                        <i class="ti ti-users me-1"></i>{{ __('Select Team') }}
                                                    </label>
                                                    <select name="team_id" class="form-select" id="driver-team">
                                                        <option value="">{{ __('Choose a team (Optional)') }}
                                                        </option>
                                                        @foreach ($public_teams as $team)
                                                            <option value="{{ $team->id }}">
                                                                {{ $team->name }}
                                                                @if ($team->address)
                                                                    - {{ Str::limit($team->address, 30) }}
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="form-text text-muted">
                                                        <i class="ti ti-info-circle me-1"></i>
                                                        {{ __('You can join a team now or later from your profile.') }}
                                                    </small>
                                                    <span class="team_id-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- WhatsApp Section -->
                                            <div class="col-md-12">
                                                <div class="mb-4">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="phone_is_whatsapp" id="phone-is-whatsapp-reg"
                                                            value="1">
                                                        <label class="form-check-label" for="phone-is-whatsapp-reg">
                                                            <i class="ti ti-brand-whatsapp me-1 text-success"></i>
                                                            {{ __('My phone number is also my WhatsApp number') }}
                                                        </label>
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        <i class="ti ti-info-circle me-1"></i>
                                                        {{ __('Check this if your phone number above is also your WhatsApp number') }}
                                                    </small>
                                                </div>
                                            </div>

                                            <div class="col-md-12" id="whatsapp-fields-reg">
                                                <div class="mb-4">
                                                    <label class="form-label" for="whatsapp-number-reg">
                                                        <i class="ti ti-brand-whatsapp me-1 text-success"></i>
                                                        {{ __('WhatsApp Number') }}
                                                    </label>
                                                    <div class="input-group">
                                                        <select id="whatsapp-country-code-reg"
                                                            name="whatsapp_country_code" class="form-select"
                                                            style="max-width: 120px;">
                                                            <option value="">{{ __('Code') }}</option>
                                                            <option value="+966">🇸🇦 +966</option>
                                                            <option value="+971">🇦🇪 +971</option>
                                                            <option value="+20">🇪🇬 +20</option>
                                                            <option value="+1">🇺🇸 +1</option>
                                                        </select>
                                                        <input type="tel" id="whatsapp-number-reg"
                                                            class="form-control"
                                                            placeholder="{{ __('Enter WhatsApp number') }}"
                                                            name="whatsapp_number" />
                                                    </div>
                                                    <small class="form-text text-muted">
                                                        <i class="ti ti-info-circle me-1"></i>
                                                        {{ __('Enter your WhatsApp number if different from phone number') }}
                                                    </small>
                                                    <span class="whatsapp_number-error text-danger text-error"></span>
                                                    <span
                                                        class="whatsapp_country_code-error text-danger text-error"></span>
                                                </div>
                                            </div>


                                        </div>


                                        <div class="mb-3">
                                            <div class="divider text-start">
                                                <div class="divider-text"><strong>Vehicle Selection</strong></div>
                                            </div>

                                            <div id="vehicle-selection-container">
                                                <!-- سيتم توليد السطور ديناميكيًا هنا -->
                                            </div>
                                        </div>


                                    </div>
                                    <div class="mb-6">
                                        @error('recaptcha')
                                            <span class="invalid-feedback" role="alert">
                                                <span class="fw-medium">{{ $message }}</span>
                                            </span>
                                        @enderror
                                        <label class="form-label" for="login-password">confirm that you ar not a
                                            robot</label>
                                        {!! htmlFormSnippet() !!}

                                    </div>
                                    {{-- <div class="mb-6">

                                        <div class="form-group">
                                            <label class="form-label" for="login-password">Enter the code in the
                                                image</label>
                                            <div class="captcha mb-2">
                                                <img src="{{ captcha_src() }}" alt="captcha" id="captcha-image"
                                                    style="height: 60px;">
                                                <button type="button"
                                                    class="btn btn-outline-seconde btn-refresh">↻</button>
                                            </div>


                                            <input type="text" class="form-control" name="captcha" required>
                                            <span class="captcha-error text-danger text-error"></span>

                                        </div>

                                    </div> --}}

                                    <!-- Terms -->
                                    <div id="additional-form" class="row mt-4">
                                        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                                            <div class="mb-6 mt-8">
                                                <div class="form-check mb-8 ms-2 @error('terms') is-invalid @enderror">
                                                    <input class="form-check-input @error('terms') is-invalid @enderror"
                                                        type="checkbox" id="terms" name="terms" />
                                                    <label class="form-check-label" for="terms">
                                                        I agree to the
                                                        <a href="{{ route('policy.show') }}" target="_blank">privacy
                                                            policy</a> &
                                                        <a href="{{ route('terms.show') }}" target="_blank">terms</a>
                                                    </label>
                                                </div>
                                                @error('terms')
                                                    <div class="invalid-feedback" role="alert">
                                                        <span class="fw-medium">{{ $message }}</span>
                                                    </div>
                                                @enderror
                                            </div>
                                        @endif
                                    </div>

                                    <button type="submit" class="btn btn-primary d-grid w-100">Sign up</button>
                                </form>
                            </div>


                            <!-- Broker Tab -->
                            <div class="tab-pane fade " id="navs-brokers" role="tabpanel">
                                <form class=" form_auth" action="{{ route('register.customer') }}" method="POST">
                                    @csrf
                                    <div class="nav-align-top mb-6">
                                        <input type="hidden" name="template" value="{{ $broker->value }}">
                                        <div class="row" id="additional-broker-form">
                                            <!-- Full Name -->
                                            <input type="hidden" name="broker" id="broker" value="1">
                                            <div class="col-md-12">
                                                <div class="mb-6">
                                                    <label class="form-label" for="customer-fullname">
                                                        * {{ __('Full Name') }}
                                                    </label>
                                                    <input type="text" class="form-control" id="customer-fullname"
                                                        placeholder="{{ __('Full Name') }}" name="name"
                                                        aria-label="{{ __('Full Name') }}" />
                                                    <span class="name-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Email -->
                                            <div class="col-md-6">
                                                <div class="mb-6">
                                                    <label class="form-label" for="customer-email">
                                                        * {{ __('Email') }}
                                                    </label>
                                                    <input type="text" id="customer-email" class="form-control"
                                                        placeholder="{{ __('example@example.com') }}"
                                                        aria-label="{{ __('example@example.com') }}" name="email" />
                                                    <span class="email-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Phone -->
                                            <div class="col-md-6">
                                                <div class="mb-6">
                                                    <label class="form-label" for="customer-phone">*
                                                        {{ __('Phone') }}</label>
                                                    <div class="input-group">
                                                        <select id="country-code" name="phone_code" class="form-select"
                                                            required style="max-width: 120px;">
                                                            <option value="+966">🇸🇦 +966</option>
                                                            <option value="+971">🇦🇪 +971</option>
                                                            <option value="+20">🇪🇬 +20</option>
                                                            <option value="+1">🇺🇸 +1</option>
                                                        </select>
                                                        <input type="tel" id="customer-phone" class="form-control"
                                                            placeholder="{{ __('Enter phone number') }}"
                                                            name="phone" />
                                                    </div>
                                                    <span class="phone-error text-danger text-error"></span>
                                                    <span class="phone_code_code-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Password -->
                                            <div class="col-md-6">
                                                <div class="mb-6">
                                                    <label class="form-label" for="customer-password">*
                                                        {{ __('Password') }}</label>
                                                    <input type="password" id="customer-password" class="form-control"
                                                        name="password" />
                                                    <span class="password-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Confirm Password -->
                                            <div class="col-md-6">
                                                <div class="mb-6">
                                                    <label class="form-label" for="customer-re-password">*
                                                        {{ __('Confirm Password') }}</label>
                                                    <input type="password" id="customer-re-password" class="form-control"
                                                        name="confirm-password" />
                                                    <span class="confirm-password-error text-danger text-error"></span>
                                                </div>
                                            </div>



                                            <!-- Company Name -->
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label class="form-label" for="customer-c_name">
                                                        {{ 'Company Name' }}</label>
                                                    <input type="text" name="c_name" class="form-control"
                                                        id="customer-c_name"
                                                        placeholder="{{ __('enter company name') }}" />
                                                    <span class="c_name-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            <!-- Company Address -->
                                            <div class="col-md-6">
                                                <div class="mb-4">
                                                    <label class="form-label" for="customer-c_address">
                                                        {{ 'Company Address' }}</label>
                                                    <input type="text" name="c_address" class="form-control"
                                                        id="customer-c_address"
                                                        placeholder="{{ __('enter company address') }}" />
                                                    <span class="c_address-error text-danger text-error"></span>
                                                </div>
                                            </div>

                                            {{-- <div id="additional-customer-form" class="row ">

                                            </div> --}}
                                        </div>
                                    </div>
                                    <div class="mb-6">
                                        @error('recaptcha')
                                            <span class="invalid-feedback" role="alert">
                                                <span class="fw-medium">{{ $message }}</span>
                                            </span>
                                        @enderror
                                        <label class="form-label" for="login-password">confirm that you ar not a
                                            robot</label>
                                        {!! htmlFormSnippet() !!}

                                    </div>
                                    {{-- <div class="mb-6">

                                        <div class="form-group">
                                            <label class="form-label" for="login-password">Enter the code in the
                                                image</label>
                                            <div class="captcha mb-2">
                                                <img src="{{ captcha_src() }}" alt="captcha" id="captcha-image"
                                                    style="height: 60px;">
                                                <button type="button"
                                                    class="btn btn-outline-seconde btn-refresh">↻</button>
                                            </div>


                                            <input type="text" class="form-control" name="captcha" required>
                                            <span class="captcha-error text-danger text-error"></span>

                                        </div>

                                    </div> --}}


                                    <!-- Terms -->
                                    <div id="additional-form" class="row mt-4">
                                        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                                            <div class="mb-6 mt-8">
                                                <div class="form-check mb-8 ms-2 @error('terms') is-invalid @enderror">
                                                    <input class="form-check-input @error('terms') is-invalid @enderror"
                                                        type="checkbox" id="terms" name="terms" />
                                                    <label class="form-check-label" for="terms">
                                                        I agree to the
                                                        <a href="{{ route('policy.show') }}" target="_blank">privacy
                                                            policy</a> &
                                                        <a href="{{ route('terms.show') }}" target="_blank">terms</a>
                                                    </label>
                                                </div>
                                                @error('terms')
                                                    <div class="invalid-feedback" role="alert">
                                                        <span class="fw-medium">{{ $message }}</span>
                                                    </div>
                                                @enderror
                                            </div>
                                        @endif
                                    </div>

                                    <button type="submit" class="btn btn-primary d-grid w-100">Sign up</button>
                                </form>
                            </div>

                        </div>
                    </div>



                    <p class="text-center ">
                        <span>Already have an account?</span>
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}">
                                <span>Sign in instead</span>
                            </a>
                        @endif
                    </p>
                </div>
            </div>
            <!-- /Register -->
        </div>
    </div>
    {!! htmlScriptTagJsApi() !!}

@endsection
