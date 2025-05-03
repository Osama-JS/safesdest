@extends('errors::minimal')

@section('title', __('System Closed'))
@section('code', '405')
@section('message', __('System Closed'))
@section('desc', __('The system is currently closed. If you find that this is unusual, please contact the system
    administrator.'))
@section('content')
    <a class="dropdown-item text-danger" href="#"
        onclick="event.preventDefault();
                    document.getElementById('form_logout').submit();"><i
            class="ri-shut-down-line align-middle me-1 text-danger"></i> {{ __('LOGOUT') }} </a>


    <form method="POST" action="{{ route('logout') }}" id="form_logout">
        @csrf
    </form>
@endsection
