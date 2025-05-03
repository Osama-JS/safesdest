@php
    use Illuminate\Support\Facades\Route;
    $configData = Helper::appClasses();
    $customizerHidden = 'customizer-hide';
    $configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Forgot Password')

@section('page-style')
    <!-- Page -->
    @vite('resources/assets/vendor/scss/pages/page-auth.scss')
@endsection

@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <!-- Forgot Password -->
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
                        <h4 class="mb-1">Forgot Password? 🔒</h4>
                        <p class="mb-6">Enter your email and we'll send you instructions to reset your password</p>
                        <form method="POST" action="{{ route('password.reset.request') }}">
                            @csrf
                            <div class="form-group mb-4">
                                <label for="type">Account Type</label>
                                <select name="type" required class="form-select ">
                                    <option value="customer">Customer</option>
                                    <option value="driver">Driver</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="email"> Your Email</label>
                                <input type="email" name="email" placeholder="john@example.com" class="form-control"
                                    required>

                            </div>

                            <button type="submit" class="btn btn-primary w-100 my-6">Send Reset Link</button>
                        </form>
                        @if (session('status'))
                            <p class="text-success">{{ session('status') }}</p>
                        @endif
                        @error('email')
                            <p class="alert alert-danger">{{ $message }}</p>
                        @enderror
                        <div class="text-center">
                            <a href="{{ url('login') }}" class="d-flex justify-content-center">
                                <i class="ti ti-chevron-left scaleX-n1-rtl me-1_5"></i>
                                Back to login
                            </a>
                        </div>
                    </div>
                </div>
                <!-- /Forgot Password -->
            </div>
        </div>
    </div>
@endsection
