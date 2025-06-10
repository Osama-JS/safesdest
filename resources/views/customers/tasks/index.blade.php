@php

    use Illuminate\Support\Facades\Session;
    $guard = Session::get('guard');

@endphp
@extends('layouts/layoutMaster')

@section('title', 'Customer Dashboard')

@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

@endsection

@section('page-style')
    <!-- Page -->
    @vite(['resources/assets/vendor/scss/pages/cards-advance.scss'])

@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')

    @vite(['resources/js/driver/tasks.js'])
    @vite(['resources/js/ajax.js'])


@endsection

@section('content')

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">{{ __('Tasks') }}</h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-tasks table ">
                <thead class="border ">
                    <tr>
                        <th></th>
                        <th>{{ __('task id') }}</th>
                        <th>{{ __('price') }}</th>
                        <th>{{ __('owner') }}</th>
                        <th>{{ __('pickup address') }}</th>
                        <th>{{ __('start before') }}</th>
                        <th>{{ __('complete before') }}</th>
                        <th>{{ __('status') }}</th>
                        <th>{{ __('closed') }}</th>
                        <th>{{ __('action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

@endsection
