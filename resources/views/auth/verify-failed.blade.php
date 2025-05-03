@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Auth;
    $configData = Helper::appClasses();
    $customizerHidden = 'customizer-hide';
    $configData = Helper::appClasses();
@endphp

@extends('layouts/blankLayout')

@section('title', 'Verify Email')

@section('page-style')
    <!-- Page -->
    @vite('resources/assets/vendor/scss/pages/page-auth.scss')
@endsection

@section('content')
    <div class="authentication-wrapper authentication-basic px-6">
        <div class="authentication-inner py-6">
            <!-- Verify Email -->
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
                    <div class="container py-5 text-center">
                        <h2 class="text-danger mb-3">Verification Failed ❌</h2>
                        <p class="lead">The verification link is invalid or has expired.</p>
                        <p>If you're having trouble, you can request a new verification email.</p>


                    </div>

                    <button type="submit" class="btn btn-warning w-100">Try to Resend Verification Email Again</button>

                    <a class="btn btn-primary w-100 my-6" href="{{ url('/login') }}">
                        Go to Login Page
                    </a>
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->has('email'))
                        <div class="alert alert-danger">
                            {{ $errors->first('email') }}
                        </div>
                    @endif

                </div>
            </div>
            <!-- /Verify Email -->
        </div>
    </div>
@endsection
