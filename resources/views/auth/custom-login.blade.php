@php
    use Illuminate\Support\Facades\Route;
    $configData = Helper::appClasses();
    $customizerHidden = 'customizer-hide';
    $configData = Helper::appClasses();
@endphp
@extends('layouts/blankLayout')

@section('title', 'Login Basic - Pages')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
    <style>
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 1;
            max-width: 480px;
            margin: 0 auto;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h4 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #6c757d;
            font-size: 0.95rem;
        }

        /* Account Type Selection */
        .account-type-section {
            margin-bottom: 2rem;
        }

        .account-type-title {
            text-align: center;
            color: #495057;
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 1rem;
        }

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

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
    {{-- @vite(['resources/assets/js/pages-auth.js']) --}}
@endsection

@section('content')
    <div class="container-xxl">
        <!-- Language -->
        <div class="nav-item dropdown-language dropdown " style="position: fixed; z-index: 1000;">

            <a class="nav-link btn btn-text-secondary   dropdown-toggle hide-arrow "
                style="margin: 20px;
                      background: white;
                      padding: 10px 20px;
                      border-radius: 10px;"
                href="javascript:void(0);" data-bs-toggle="dropdown">
                <i class='ti ti-language rounded-circle ti-md'></i>
                {{ app()->getLocale() === 'en' ? 'English' : 'عربي' }}
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{ url('lang/en') }}"
                        data-language="en" data-text-direction="ltr">
                        <span>English</span>
                    </a>
                </li>

                <li>
                    <a class="dropdown-item {{ app()->getLocale() === 'ar' ? 'active' : '' }}" href="{{ url('lang/ar') }}"
                        data-language="ar" data-text-direction="rtl">
                        <span>Arabic</span>
                    </a>
                </li>

            </ul>

        </div>
        <!--/ Language -->

        <div class="nav-item dropdown-language dropdown" style="position: fixed; right: 0px; top: 20px; z-index: 1000;">
            <button type="button" class="btn btn-light "
                style="background: white; padding: 10px 20px; border-radius: 10px;margin: 0 20px;" data-bs-toggle="modal"
                data-bs-target="#helpModal">
                <i class="fas fa-headset text-primary fs-5 mx-2"></i> {{ __('Help') }}
            </button>
        </div>

        <!-- Help Modal -->
        <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true"
            dir="rtl">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0 rounded-4">

                    <!-- Header -->
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-center" id="helpModalLabel">
                            <i class="fas fa-headset me-2"></i> {{ __('Contact, Complaints and Suggestions') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="{{ __('Close') }}"></button>
                    </div>

                    <!-- Body -->
                    <div class="modal-body text-center p-4">
                        <div class="row">
                            <div class="col-sm-6">
                                <!-- Website -->
                                <a href="https://www.safedest.com" target="_blank"
                                    class="d-block py-3 mb-3 text-decoration-none border rounded-3 shadow-sm contact-link">
                                    <i class="bi bi-globe2 text-success fs-3 mb-2 d-block"></i>
                                    <div class="fw-bold">{{ __('Website') }}</div>
                                    <small class="text-muted">www.safedest.com</small>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <!-- Email -->
                                <a href="mailto:Info@safedest.com"
                                    class="d-block py-3 mb-3 text-decoration-none border rounded-3 shadow-sm contact-link">
                                    <i class="bi bi-envelope-fill text-primary fs-3 mb-2 d-block"></i>
                                    <div class="fw-bold">{{ __('Email') }}</div>
                                    <small class="text-muted">Info@safedest.com</small>
                                </a>
                            </div>
                        </div>

                        <div class="divider text-center">
                            <div class="divider-text">
                                <strong>{{ __('Contact Us') }}</strong>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <!-- First Phone -->
                                <a href="tel:0556978782"
                                    class="d-block py-3 mb-2 text-decoration-none border rounded-3 shadow-sm contact-link">
                                    <i class="bi bi-telephone-fill text-danger fs-3 mb-2 d-block"></i>
                                    <div class="fw-bold">{{ __('First Number') }}</div>
                                    <small class="text-muted">0556978782</small>
                                </a>
                            </div>
                            <div class="col-sm-6">
                                <!-- Second Phone -->
                                <a href="tel:0545366466"
                                    class="d-block py-3 text-decoration-none border rounded-3 shadow-sm contact-link">
                                    <i class="bi bi-telephone-fill text-danger fs-3 mb-2 d-block"></i>
                                    <div class="fw-bold">{{ __('Second Number') }}</div>
                                    <small class="text-muted">0545366466</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">

                <!-- Login -->
                <div class="card login-card">
                    <div class="card-body p-5">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-4">
                            <a href="{{ url('/') }}" class="app-brand-link">
                                <span class="app-brand-logo demo">@include('_partials.macros', [
                                    'height' => 20,
                                    'withbg' => 'fill: #667eea;',
                                ])</span>
                                <span class="app-brand-text demo text-heading fw-bold"
                                    style="color: #2c3e50;">{{ config('variables.templateName') }}</span>
                            </a>
                        </div>
                        <!-- /Logo -->

                        <div class="login-header">
                            <h4>{{ __('Welcome to') }} {{ config('variables.templateName') }}! 👋</h4>
                            <p>{{ __('Welcome to the page dedicated to') }}</p>
                            <p>{{ __('companies, factories, brokers, transporters, and customs clearance agents') }}</p>
                        </div>

                        <form id="formAuthentication" action="{{ route('login') }}" method="POST">
                            @csrf

                            <!-- Account Type Selection -->
                            <div class="account-type-section">
                                <h6 class="account-type-title my-3">{{ __('Choose Your Account Type') }}</h6>
                                <div class="account-types-grid">
                                    <div class="account-type-card">
                                        <input type="radio" id="customer" name="account_type" value="customer"
                                            class="account-type-input" checked />
                                        <label for="customer" class="account-type-label">
                                            <i class="ti ti-user account-type-icon customer-icon"></i>
                                            <span class="account-type-title">{{ __('Customer') }}</span>
                                        </label>
                                    </div>

                                    <div class="account-type-card">
                                        <input type="radio" id="driver" name="account_type" value="driver"
                                            class="account-type-input" />
                                        <label for="driver" class="account-type-label">
                                            <i class="ti ti-car account-type-icon driver-icon"></i>
                                            <span class="account-type-title">{{ __('Driver') }}</span>
                                        </label>
                                    </div>

                                    <div class="account-type-card">
                                        <input type="radio" id="broker" name="account_type" value="broker"
                                            class="account-type-input" />
                                        <label for="broker" class="account-type-label">
                                            <i class="ti ti-building account-type-icon broker-icon"></i>
                                            <span class="account-type-title">{{ __('Customs Broker') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>



                            <!-- Email Field -->
                            <div class="form-floating">
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    id="login-email" name="email" placeholder="Email Address" autocomplete="email"
                                    autofocus value="{{ old('email') }}" required>
                                <label for="login-email">
                                    <i class="ti ti-mail me-2"></i>{{ __('Email Address') }}
                                </label>
                                <div class="validation-message">
                                    <i class="ti ti-alert-circle me-1"></i>{{ __('Please enter a valid email address') }}
                                </div>
                                @error('email')
                                    <div class="invalid-feedback">
                                        <i class="ti ti-alert-circle me-1"></i>{!! $message !!}
                                    </div>
                                @enderror
                            </div>

                            <!-- Password Field -->
                            <div class="form-floating">
                                <input type="password" id="login-password"
                                    class="form-control @error('password') is-invalid @enderror" name="password"
                                    placeholder="Password" autocomplete="current-password" required />

                                <label for="login-password">
                                    <i class="ti ti-lock me-2"></i>{{ __('Password') }}
                                </label>

                                <!-- زر إظهار كلمة المرور كأيقونة عائمة -->
                                <span class="position-absolute top-50 end-0 translate-middle-y pe-3 cursor-pointer"
                                    id="toggle-password">
                                    <i class="ti ti-eye-off" id="eye-icon"></i>
                                </span>

                                @error('password')
                                    <div class="invalid-feedback">
                                        <i class="ti ti-alert-circle me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>


                            <!-- reCAPTCHA Section -->
                            {{-- <div class="mb-4">
                                @error('recaptcha')
                                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                                        <i class="ti ti-alert-circle me-2"></i>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                                <div class="captcha-section">
                                    <label class="form-label mb-3">
                                        <i class="ti ti-shield-check me-2"></i>Security Verification
                                    </label>
                                    <div class="captcha-container">
                                        {!! htmlFormSnippet() !!}
                                    </div>
                                </div>
                            </div> --}}

                            {{-- Custom Captcha Section (commented for easy switching) --}}
                            <div class="mb-4">
                                @error('captcha')
                                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                                        <i class="ti ti-alert-circle me-2"></i>
                                        <span>{{ $message }}</span>
                                    </div>
                                @enderror
                                <div class="captcha-section">
                                    <label class="form-label mb-3">
                                        <i class="ti ti-shield-check me-2"></i>{{ __('Enter the code in the image') }}
                                    </label>
                                    <div class="captcha-container d-flex align-items-center gap-3 mb-3">
                                        <img src="{{ captcha_src() }}" alt="captcha" id="captcha-image"
                                            style="height: 60px; border-radius: 8px; border: 2px solid #e9ecef;">
                                        <button type="button" class="btn btn-outline-secondary btn-refresh"
                                            onclick="refreshCaptcha()">
                                            <i class="ti ti-refresh"></i>
                                        </button>
                                    </div>
                                    <input type="text" class="form-control @error('captcha') is-invalid @enderror"
                                        name="captcha" placeholder="Enter captcha code" required>
                                    @error('captcha')
                                        <div class="invalid-feedback">
                                            <i class="ti ti-alert-circle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Forgot Password Link -->
                            <div class="d-flex justify-content-end mb-4">
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="forgot-password-link">
                                        <i class="ti ti-help me-1"></i>{{ __('Forgot Password?') }}
                                    </a>
                                @endif
                            </div>

                            <!-- Login Button -->
                            <button id="btn-login" class="btn btn-primary btn-login d-grid w-100 mb-4" type="submit">
                                <span class="d-flex align-items-center justify-content-center">
                                    <i class="ti ti-login me-2"></i>
                                    {{ __('Sign In') }}
                                </span>
                            </button>
                        </form>

                        <!-- Register Link -->
                        <div class="register-link">
                            <span class="text-muted">{{ __('New on our platform?') }}</span>
                            <a href="{{ route('auth.register') }}">
                                <i class="ti ti-user-plus me-1"></i>{{ __('Create an account') }}
                            </a>
                        </div>

                        <!-- Team Leader Sign In Link -->

                    </div>
                </div>
                <div class="alert alert-info">

                </div>
                <!-- /Login -->
                <div class="text-center mt-3">
                    <span class="text-muted">{{ __('Are You Team Leader?') }}</span>
                    <a href="{{ url('admin-panel/login') }}">
                        {{ __('Sign In From Here') }}
                    </a>
                </div>
            </div>

        </div>

    </div>

    {!! htmlScriptTagJsApi() !!}

    <script>
        // Function to refresh captcha (for custom captcha)
        function refreshCaptcha() {
            const captchaImage = document.getElementById('captcha-image');
            if (captchaImage) {
                captchaImage.src = '{{ captcha_src() }}?' + Math.random();
            }
        }

        document.addEventListener('DOMContentLoaded', function() {


            // Password toggle functionality
            const togglePassword = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('login-password');

            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);

                    const icon = this.querySelector('i');
                    if (icon) {
                        if (type === 'password') {
                            icon.className = 'ti ti-eye-off';
                        } else {
                            icon.className = 'ti ti-eye';
                        }
                    }
                });
            }

            // Form submission with loading state
            const form = document.getElementById('formAuthentication');
            const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

            if (form && submitBtn) {
                console.log('submit');
                form.addEventListener('submit', function(e) {


                    // Show loading state
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;

                    // Re-enable after 10 seconds as fallback
                    setTimeout(() => {
                        submitBtn.classList.remove('loading');
                        submitBtn.disabled = false;
                    }, 10000);
                });
            }

            // Enhanced form validation feedback
            const allInputs = document.querySelectorAll('#formAuthentication input');
            if (allInputs && allInputs.length > 0) {
                allInputs.forEach(input => {
                    if (!input) return;

                    // Real-time validation on input
                    input.addEventListener('input', function() {
                        if (this.classList.contains('is-invalid')) {
                            if (this.type === 'email') {
                                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                if (this.value && this.value.trim() && emailRegex.test(this.value
                                        .trim())) {
                                    this.classList.remove('is-invalid');
                                }
                            } else if (this.value && this.value.trim() !== '') {
                                this.classList.remove('is-invalid');
                            }
                        }
                    });

                    // Validation on blur
                    input.addEventListener('blur', function() {
                        // Skip validation if input is not visible (like team code when not driver)
                        if (this.offsetParent === null) return;

                        if (this.hasAttribute('required') && (!this.value || !this.value.trim())) {
                            this.classList.add('is-invalid');
                        } else if (this.type === 'email' && this.value && this.value.trim()) {
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (!emailRegex.test(this.value.trim())) {
                                this.classList.add('is-invalid');
                            } else {
                                this.classList.remove('is-invalid');
                            }
                        } else if (this.value && this.value.trim()) {
                            this.classList.remove('is-invalid');
                        }
                    });
                });
            }

            // Add smooth animations for cards
            const cards = document.querySelectorAll('.account-type-card');
            if (cards.length > 0) {
                cards.forEach((card, index) => {
                    if (card) {
                        card.style.animationDelay = `${index * 0.1}s`;
                        card.classList.add('animate-in');
                    }
                });
            }

            // Handle server-side validation errors
            const serverErrors = document.querySelectorAll('.invalid-feedback');
            serverErrors.forEach(error => {
                const input = error.previousElementSibling;
                if (input && input.tagName === 'INPUT') {
                    input.classList.add('is-invalid');
                }
            });
        });
    </script>

    <style>
        /* Animation for cards */
        .animate-in {
            animation: slideInUp 0.6s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Form validation enhancements */
        .form-control.is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
            background-color: rgba(220, 53, 69, 0.05);
        }

        .form-control:valid:not(:placeholder-shown) {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.15);
        }

        .form-floating .form-control.is-invalid~label {
            color: #dc3545;
        }

        .form-floating .form-control:valid:not(:placeholder-shown)~label {
            color: #28a745;
        }

        /* Custom validation message */
        .validation-message {
            display: none;
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            padding-left: 0.75rem;
        }

        .form-control.is-invalid~.validation-message {
            display: block;
        }

        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        /* Loading button animation */
        .btn-login.loading {
            position: relative;
            color: transparent;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Custom Captcha Styles */
        .captcha-container {
            background: rgba(248, 249, 250, 0.8);
            border-radius: 10px;
            padding: 1rem;
            border: 1px solid #e9ecef;
        }

        .captcha-container img {
            transition: all 0.3s ease;
        }

        .captcha-container img:hover {
            transform: scale(1.02);
        }

        .btn-refresh {
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-refresh:hover {
            background-color: #667eea;
            border-color: #667eea;
            color: white;
            transform: rotate(180deg);
        }
    </style>

@endsection
