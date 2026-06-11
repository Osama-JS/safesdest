@extends('layouts/layoutMaster')

@section('title', __('Tasks Map'))

<!-- Vendor Styles -->
@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css"
        rel="stylesheet" />
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    @vite('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss')

    @vite(['resources/css/app.css'])

    <style>
        .custom-marker:hover {
            transform: scale(1.1);
            transition: transform 0.2s ease;
        }

        .task-info-box {
            backdrop-filter: blur(4px);
            background-color: rgba(255, 255, 255, 0.9);
            border-left: 5px solid #0d6efd;
        }
    </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    @vite('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')



@endsection

<!-- Page Scripts -->
@section('page-script')
    <script>
        window.taskData = {
            pickup: @json($pickup),
            dropoff: @json($dropoff),
            driver: @json($driver)
        };
    </script>

    @vite(['resources/js/mapbox-helper.js'])
    @vite(['resources/js/admin/tasks/tracking.js'])
@endsection

@section('content')

    <div class="position-relative" style="height: 80vh;">
        <div id="taskMap" class="w-100 h-100"></div>
        <div class="task-info-box position-absolute bg-white rounded shadow p-3"
            style="top: 20px; left: 20px; z-index: 10;  max-width: 300px;">
            <h5 class="mb-2">{{ __('Task Information') }}</h5>
            <ul class="list-unstyled mb-2">
                <li><strong>{{ __('ID:') }}</strong> {{ $task->id }}</li>
                <li><strong>{{ __('Status:') }}</strong> {{ $task->status }}</li>
                <li><strong>{{ __('From:') }}</strong> {{ $task->pickup->address }}</li>
                <li><strong>{{ __('To:') }}</strong> {{ $task->delivery->address }}</li>
            </ul>

            @if ($task->driver)
                <h6 class="mt-3 mb-2">{{ __('Driver') }}</h6>
                <ul class="list-unstyled">
                    <li><strong>{{ __('Name:') }}</strong> {{ $task->driver->name }}</li>
                    <li><strong>{{ __('Phone:') }}</strong> {{ $task->driver->phone }}</li>
                </ul>
            @else
                <p class="text-muted">{{ __('No Driver assigned yet') }}</p>
            @endif
        </div>
    </div>





@endsection
