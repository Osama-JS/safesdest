@php
    use Illuminate\Support\Facades\Route;
    $configData = Helper::appClasses();
    $customizerHidden = 'customizer-hide';
    $configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Login')

@section('page-style')
    <!-- Page -->
    @vite('resources/assets/vendor/scss/pages/page-auth.scss')
@endsection

@section('content')
    <!-- Language -->
    <div class="nav-item dropdown-language dropdown " style="position: fixed; z-index: 1000;bottom: 0;">

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
    <div class="authentication-wrapper authentication-cover">
        <!-- Logo -->
        <a href="{{ url('/') }}" class="app-brand auth-cover-brand">
            <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20, 'withbg' => 'fill: #fff;'])</span>
            <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
        </a>
        <!-- /Logo -->
        <div class="authentication-inner row m-0">
            <!-- /Left Text -->
            <div class="d-none d-lg-flex col-lg-8 p-0">
                <div class="auth-cover-bg auth-cover-bg-color d-flex justify-content-center align-items-center">
                    <img src="{{ asset('assets/img/illustrations/auth-login-illustration-' . $configData['style'] . '.png') }}"
                        alt="auth-login-cover" class="my-5 auth-illustration"
                        data-app-light-img="illustrations/auth-login-illustration-light.png"
                        data-app-dark-img="illustrations/auth-login-illustration-dark.png">

                    <img src="{{ asset('assets/img/illustrations/bg-shape-image-' . $configData['style'] . '.png') }}"
                        alt="auth-login-cover" class="platform-bg"
                        data-app-light-img="illustrations/bg-shape-image-light.png"
                        data-app-dark-img="illustrations/bg-shape-image-dark.png">
                </div>
            </div>
            <!-- /Left Text -->

            <!-- Login -->
            <div class="d-flex col-12 col-lg-4 align-items-center authentication-bg p-sm-12 p-6">
                <div class="w-px-400 mx-auto mt-12 pt-5">
                    <h4 class="mb-1">{{ __('Welcome to') }} {{ config('variables.templateName') }}! 👋</h4>
                    <p class="mb-6">{{ __('sign in to start manage the tasks') }}</p>

                    @if (session('status'))
                        <div class="alert alert-success mb-1 rounded-0" role="alert">
                            <div class="alert-body">
                                {{ session('status') }}
                            </div>
                        </div>
                    @endif

                    <form id="formAuthentication" class="mb-6" action="{{ route('login') }}" method="POST">
                        @csrf
                        <div class="mb-6">
                            <label for="login-email" class="form-label">{{ __('Email') }}</label>
                            <input type="text" class="form-control @error('email') is-invalid @enderror" id="login-email"
                                name="email" placeholder="john@example.com" autofocus>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <span class="fw-medium">{{ $message }}</span>
                                </span>
                            @enderror
                        </div>
                        <div class="mb-6 form-password-toggle">
                            <label class="form-label" for="login-password">{{ __('Password') }}</label>
                            <div class="input-group input-group-merge @error('password') is-invalid @enderror">
                                <input type="password" id="login-password"
                                    class="form-control @error('password') is-invalid @enderror" name="password"
                                    placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                    aria-describedby="password" />
                                <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                            </div>
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <span class="fw-medium">{{ $message }}</span>
                                </span>
                            @enderror
                        </div>
                        {{-- <div class="mb-6">
                            @error('recaptcha')
                                <span class="invalid-feedback" role="alert">
                                    <span class="fw-medium">{{ $message }}</span>
                                </span>
                            @enderror
                            <label class="form-label" for="login-password">confirm that you ar not a robot</label>
                            {!! htmlFormSnippet() !!}
                        </div> --}}

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
                        <button class="btn btn-primary d-grid w-100" type="submit">{{ __('Sign In') }}</button>
                    </form>

                </div>
            </div>
            <!-- /Login -->
        </div>
    </div>

    {!! htmlScriptTagJsApi() !!}

    <script>
        function refreshCaptcha() {
            const captchaImage = document.getElementById('captcha-image');
            if (captchaImage) {
                captchaImage.src = '{{ captcha_src() }}?' + Math.random();
            }
        }
    </script>

    {{-- <script>
        document.querySelector('.btn-refresh').addEventListener('click', function() {
            fetch("{{ route('captcha.refresh') }}", {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json()) // ← يتوقع JSON
                .then(data => {
                    document.getElementById('captcha-image').src = data.captcha + '?' + Date.now();
                });
        });
    </script> --}}



@endsection
