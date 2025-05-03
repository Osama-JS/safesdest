@php
    use Illuminate\Support\Facades\Route;
    $configData = Helper::appClasses();
    $customizerHidden = 'customizer-hide';
    $configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Register Page')

@section('page-style')
    <!-- Page -->
    @vite('resources/assets/vendor/scss/pages/page-auth.scss')
@endsection

@section('page-script')
    <script>
        const CustomerTemplate = {!! json_encode($customer_template) !!}
        const DriverTemplate = {!! json_encode($driver_template) !!}
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
          <span class="vehicles-{index}-vehicle-error text-danger text-error"></span>

        </div>
        <div class="col-md-4">
          <label class="form-label">* Vehicle Type</label>
          <select class="form-select vehicle-type-select" name="vehicles[{index}][vehicle_type]" disabled>
            <option value="">Select a vehicle type</option>
          </select>
          <span class="vehicles-{index}-vehicle_type-error text-danger text-error"></span>

        </div>
        <div class="col-md-4">
          <label class="form-label">* Vehicle Size</label>
          <select class="form-select vehicle-size-select" name="vehicle" disabled>
            <option value="">Select a vehicle size</option>
          </select>
          <span class="vehicles-{index}-vehicle_size-error text-danger text-error"></span>

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
                        <ul class="nav nav-tabs nav-fill" role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-customers" aria-controls="navs-customers" aria-selected="true">
                                    <span class="d-none d-sm-block">

                                        <i class="fas fa-user fa-2x text-primary me-1_5"></i> Register As a Customer
                                    </span>
                                    <i class="fas fa-user fa-2x text-primary d-sm-none"></i>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                    data-bs-target="#navs-drivers" aria-controls="navs-drivers" aria-selected="false">
                                    <span class="d-none d-sm-block">

                                        <i class="fas fa-car fa-2x text-success me-1_5"></i> Register As a Driver
                                    </span>
                                    <i class="fas fa-car fa-2x text-success d-sm-none"></i>
                                </button>
                            </li>
                        </ul>

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
                                                            placeholder="{{ __('Enter phone number') }}" name="phone" />
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
@endsection
