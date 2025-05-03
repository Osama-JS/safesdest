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
                    <h4 class="mb-1">Verify your email ✉️</h4>
                    <p class="text-start mb-0">
                        Account activation link sent to your email address: {{ $email }} Please follow the link
                        inside
                        to continue.
                    </p>
                    <a class="btn btn-primary w-100 my-6" href="{{ url('/login') }}">
                        Go to Login Page
                    </a>
                    <form action="{{ route('resend.verification') }}" method="post" id="resendEmail">
                        <input type="hidden" name="email" value="{{ $email }}">
                        @csrf
                        <p class="text-center mb-0">Didn't get the mail?
                            <a href="#"
                                onclick="event.preventDefault(); document.getElementById('resendEmail').submit();">
                                Resend
                            </a>

                        </p>
                    </form>
                    @if (session('remaining_attempts') !== null)
                        <div class="alert alert-info mt-3">
                            🔄 You have <strong>{{ session('remaining_attempts') }}</strong> verification attempt(s) left
                            today.
                        </div>
                    @endif

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
