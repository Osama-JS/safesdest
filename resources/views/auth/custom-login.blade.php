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
        .card-select {
            position: relative;
            width: 48%;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid transparent;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .card-select input {
            display: none;
        }


        .card-select label {
            display: block;
            text-align: center;
            gap: 16px;
            padding: 20px;
            background-color: #ffffff;
            color: #333;
            transition: all 0.3s ease-in-out;
            border-radius: 12px;
            height: 100%;
            cursor: pointer;
        }

        .card-select:hover label {
            background-color: #f5f7fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 86, 179, 0.1);
        }

        .card-select input:checked+label {
            border: 2px solid #081a2e;
            background-color: #ccdffc;
            box-shadow: 0 0 0 4px rgba(0, 86, 179, 0.2);
        }


        .card-title {
            font-size: 16px;
            font-weight: 600;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            flex-shrink: 0;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/pages-auth.js'])
    @vite(['resources/js/auth.js'])
@endsection

@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <!-- Login -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-6">
                            <a href="{{ url('/') }}" class="app-brand-link">
                                <span class="app-brand-logo demo">@include('_partials.macros', ['height' => 20, 'withbg' => 'fill: #fff;'])</span>
                                <span
                                    class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
                            </a>
                        </div>
                        <!-- /Logo -->
                        <h4 class="mb-1">Welcome to {{ config('variables.templateName') }}! 👋</h4>
                        <p class="mb-6">Please sign-in to your account and start the adventure</p>

                        <form id="formAuthentication" class="mb-6" action="{{ route('login') }}" method="POST">
                            @csrf

                            <!-- Account Type Selection -->
                            <div class="mb-6">

                                <div class="d-flex justify-content-between">
                                    <div class="card-select w-48 text-center">
                                        <input type="radio" id="customer" name="account_type" value="customer"
                                            class="d-none" checked />
                                        <label for="customer" class="card p-4 shadow-sm border border-light rounded-3">
                                            <i class="fas fa-user fa-2x text-primary mb-3"></i>
                                            <span class="card-title">Customer</span>
                                        </label>
                                    </div>
                                    <div class="card-select w-48 text-center">
                                        <input type="radio" id="driver" name="account_type" value="driver"
                                            class="d-none" />
                                        <label for="driver" class="card p-4 shadow-sm border border-light rounded-3">
                                            <i class="fas fa-car fa-2x text-success mb-3"></i>
                                            <span class="card-title">Driver</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-6">
                                <div id="driver-fields" style="display: none; margin-top: 10px;">
                                    <label for="team" class="form-label">Team Code</label>
                                    <input type="text" id="team" name="team_code" autocomplete="off"
                                        class="form-control" placeholder="0189...">
                                </div>
                            </div>
                            <div class="mb-6">
                                <label for="login-email" class="form-label">Email</label>
                                <input type="text" class="form-control @error('email') is-invalid @enderror"
                                    id="login-email" name="email" autocomplete="off" placeholder="john@example.com"
                                    autofocus value="{{ old('email') }}">
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <span class="fw-medium">{!! $message !!}</span>
                                    </span>
                                @enderror
                            </div>
                            <div class="mb-6 form-password-toggle">
                                <label class="form-label" for="login-password">Password</label>
                                <div class="input-group input-group-merge @error('password') is-invalid @enderror">
                                    <input type="password" id="login-password"
                                        class="form-control @error('password') is-invalid @enderror" name="password"
                                        autocomplete="off"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                                    <span class="input-group-text cursor-pointer"><i class="ti ti-eye-off"></i></span>
                                </div>
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <span class="fw-medium">{{ $message }}</span>
                                    </span>
                                @enderror
                            </div>



                            <div class="my-8">
                                <div class="d-flex justify-content-between">
                                    <div class="form-check mb-0 ms-2">
                                        <input class="form-check-input" type="checkbox" id="remember-me" name="remember"
                                            {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="remember-me">Remember Me</label>
                                    </div>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}">
                                            <p class="mb-0">Forgot Password?</p>
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <button class="btn btn-primary d-grid w-100" type="submit">Sign in</button>
                        </form>


                        <p class="text-center">
                            <span>New on our platform?</span>
                            <a href="{{ route('auth.register') }}">
                                <span>Create an account</span>
                            </a>
                        </p>
                    </div>
                </div>
                <!-- /Register -->
            </div>
        </div>
    </div>
@endsection
