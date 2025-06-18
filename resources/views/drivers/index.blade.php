@php

    use Illuminate\Support\Facades\Session;
    $guard = Session::get('guard');

@endphp
@extends('layouts/layoutMaster')

@section('title', 'Driver Dashboard')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

@endsection

@section('page-style')
    <!-- Page -->
    @vite(['resources/assets/vendor/scss/pages/cards-advance.scss'])
    <style>
        .stepper-container {
            overflow-x: auto;
            padding-bottom: 10px;
        }

        .stepper {
            min-width: 650px;
            gap: 0;
        }

        .step-form {
            position: relative;
            z-index: 1;
        }

        .step-circle {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            font-weight: bold;
            padding: 0;
            transition: 0.3s;
        }

        .step-line {
            flex-grow: 1;
            height: 3px;
            margin: 0 5px;
            z-index: 0;
            transition: 0.3s;
            border-radius: 2px;
        }

        .step-label {
            width: 80px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 576px) {
            .stepper {
                min-width: auto;
                flex-wrap: nowrap;
                overflow-x: auto;
            }

            .step-label {
                font-size: 10px;
                width: 60px;
            }
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')

    @vite(['resources/js/driver/index.js'])
    @vite(['resources/js/ajax.js'])
    @php
        $taskMapData = auth()
            ->user()
            ->possible_tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'longitude' => optional($task->pickup)->longitude,
                    'latitude' => optional($task->pickup)->latitude,
                ];
            })
            ->values()
            ->toArray();
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tasks = @json($taskMapData);

            tasks.forEach(task => {
                if (task.longitude && task.latitude) {
                    initMapForAd(task.id, [task.longitude, task.latitude]);
                }
            });
        });
    </script>
    <script>
        function updateDriverLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    fetch('{{ route('driver.location') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            longitude: position.coords.longitude,
                            altitude: position.coords.latitude
                        })
                    });
                });
            }
        }

        // تحديث كل دقيقة
        setInterval(updateDriverLocation, 60000);
        updateDriverLocation(); // أول مرة
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endsection

@section('content')

    <div class="row">
        <div class="col-lg-3 col-md-4 d-flex ">
            <img class="img-fluid rounded  mx-3"
                src="{{ auth()->user()->image ? asset(auth()->user()->image) : asset('assets/img/person.png') }}"
                style="height: 70px;" alt="Driver avatar" />
            <div class="user-info">
                <h5>{{ auth()->user()->name }}</h5> <span
                    class="badge bg-label-secondary">{{ auth()->user()->team->name ?? '' }}</span>
                @if (auth()->user()->online)
                    <span class="card-title mb-0"><span class="badge bg-success">Online</span></span>
                @else
                    <span class="card-title mb-0"><span class="badge bg-danger">Offline</span></span>
                @endif
                @if (auth()->user()->free)
                    <span class="card-title mb-0"><span class="badge bg-info">Free</span></span>
                @else
                    <span class="card-title mb-0"><span class="badge bg-secondary">Busy</span></span>
                @endif
            </div>
        </div>
        <div class="col-lg-9 col-md-8">
            <div class="row">
                @foreach (auth()->user()->possible_tasks as $task)
                    <div class="col-md-6">

                        <div class="mb-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-header bg-white border-bottom-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">Task #{{ $task->id }}</h5>
                                        <span
                                            class="badge bg-{{ match ($task->status) {
                                                'pending' => 'warning',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                default => 'secondary',
                                            } }}">
                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                        </span>

                                    </div>
                                </div>
                                {{-- الخريطة --}}
                                <div class="map-container
                                    rounded-top"
                                    id="map-{{ $task->id }}" style="height: 100px;">
                                </div>

                                <div class="card-body">
                                    {{-- بيانات العميل --}}
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Owner Type:</strong> {{ ucfirst($task->owner) }}</p>
                                        @if ($task->owner === 'customer' && $task->customer)
                                            <p class="mb-0"><strong>Customer Name:</strong> {{ $task->customer->name }}
                                            </p>
                                            <p class="mb-0"><strong>Customer Phone:</strong>
                                                {{ $task->customer->phone ?? 'N/A' }}</p>
                                        @elseif ($task->owner === 'admin' && $task->user)
                                            <p class="mb-0"><strong>Admin:</strong> {{ $task->user->name }}</p>
                                        @endif
                                    </div>

                                    {{-- بيانات النقاط --}}
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <strong>{{ __('Pickup address') }}:</strong>
                                                <p class="mb-0 text-muted">
                                                    {{ optional($task->pickup)->address ?? 'Not set' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <strong>{{ __('Delivery address') }}:</strong>
                                                <p class="mb-0 text-muted">
                                                    {{ optional($task->delivery)->address ?? 'Not set' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- معلومات إضافية --}}
                                    <div class="row mt-3">
                                        <div class="col-md-6 ">
                                            <p class="border p-2 rounded"><strong>Price:</strong>
                                                {{ $task->total_price - auth()->user()->calculateCommission($task->total_price) }}
                                                SAR
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-footer bg-white border-top-0 text-end d-flex ">
                                    <form action="{{ route('driver.respond.task') }}" method="POST" class="form_submit">
                                        <input type="hidden" name="task_id" value="{{ $task->id }}">
                                        <input type="hidden" name="response" value="accept">
                                        <button type="submit" class="btn btn-primary mx-2">Accept</button>
                                    </form>
                                    <form action="{{ route('driver.respond.task') }}" method="POST" class="form_submit">
                                        <input type="hidden" name="task_id" value="{{ $task->id }}">
                                        <input type="hidden" name="response" value="reject">
                                        <button type="submit" class="btn btn-outline-danger mx-2">reject</button>
                                    </form>

                                </div>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            @foreach ($data as $task)
                <div class="mb-5">
                    <div id="task-details-view" class="bg-white shadow rounded-3 overflow-hidden">
                        <div class="card-header p-4 border-bottom d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Task #{{ $task->id }}</h5>
                            <span
                                class="badge bg-{{ match ($task->status) {
                                    'pending' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    default => 'secondary',
                                } }}">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>
                        </div>

                        <div class="nav-align-top p-0" style="min-height: 75vh;">
                            <ul class="nav nav-tabs nav-fill bg-white border-bottom sticky-top"
                                style="top: 0; z-index: 1030;">
                                <li class="nav-item">
                                    <button type="button" class="nav-link active" data-bs-toggle="tab"
                                        data-bs-target="#navs-justified-details-{{ $task->id }}" role="tab">
                                        <span class="d-none d-sm-inline">{{ __('Details') }}</span>
                                        <i class="ti ti-info-circle ti-sm d-sm-none"></i>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button type="button" class="nav-link" data-bs-toggle="tab"
                                        data-bs-target="#navs-justified-history-{{ $task->id }}" role="tab">
                                        <span class="d-none d-sm-inline">{{ __('History') }}</span>
                                        <i class="ti ti-clock ti-sm d-sm-none"></i>
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content p-0 m-0" style="max-height: calc(75vh - 60px); overflow-y: auto;">
                                {{-- Details Tab --}}
                                <div class="tab-pane fade show active" id="navs-justified-details-{{ $task->id }}"
                                    role="tabpanel">
                                    <div id="task-details-content" class="p-4">
                                        <div class="  mb-4">
                                            <div id="map-{{ $task->id }}" class="rounded-top"
                                                style="height: 150px;"></div>
                                            <div class="mb-3">
                                                @php
                                                    $statuses = [
                                                        'started',
                                                        'in pickup point',
                                                        'loading',
                                                        'in the way',
                                                        'in delivery point',
                                                        'unloading',
                                                        'completed',
                                                    ];
                                                    $currentIndex = array_search($task->status, $statuses);
                                                @endphp

                                                <div class="stepper-container my-4">
                                                    <div
                                                        class="stepper d-flex justify-content-between align-items-center position-relative">
                                                        @foreach ($statuses as $index => $status)
                                                            @php
                                                                $isCompleted = $index < $currentIndex;
                                                                $isActive = $index == $currentIndex;
                                                                $isNext = $index == $currentIndex + 1;
                                                            @endphp

                                                            <form class="step-form text-center" method="POST"
                                                                action="{{ route('driver.task.updateStatus') }}">
                                                                @csrf
                                                                <input type="hidden" name="task_id"
                                                                    value="{{ $task->id }}">
                                                                <input type="hidden" name="status"
                                                                    value="{{ $status }}">

                                                                <button type="button"
                                                                    class="step-circle btn {{ $isActive ? 'btn-primary' : ($isNext ? 'btn-outline-primary' : 'btn-outline-secondary') }}"
                                                                    data-status="{{ $status }}"
                                                                    {{ !$isNext ? 'disabled' : '' }}
                                                                    title="{{ ucfirst($status) }}"
                                                                    style="width: 45px; height: 45px; border-radius: 50%;">
                                                                    {{ $index + 1 }}
                                                                </button>

                                                                <div class="step-label mt-1 small">
                                                                    <small
                                                                        class="{{ $isCompleted ? 'text-success' : ($isActive ? 'text-primary' : 'text-muted') }}">
                                                                        {{ __($status) }}
                                                                    </small>
                                                                </div>
                                                            </form>


                                                            @if ($index < count($statuses) - 1)
                                                                <div
                                                                    class="step-line {{ $index < $currentIndex ? 'bg-primary' : 'bg-secondary' }}">
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                            @if ($task->status == 'completed')
                                                <div class="alert alert-success" role="alert">
                                                    <b>{{ __('You need to wait for the responsible supervisor to check the task and close it, thank you') }}</b>
                                                </div>
                                            @endif

                                            <div class="">
                                                <h6 class="fw-bold mb-3">Client Information</h6>
                                                <input type="hidden" id="task-id-history" value="{{ $task->id }}">
                                                {{-- <p><strong>Owner Type:</strong> {{ ucfirst($task->owner) }}</p> --}}
                                                @if ($task->owner === 'customer' && $task->customer)
                                                    <p><strong>Customer Name:</strong> {{ $task->customer->name }}</p>
                                                    <p><strong>Customer Phone:</strong>
                                                        {{ $task->customer->phone ?? 'N/A' }}</p>
                                                @elseif ($task->owner === 'admin' && $task->user)
                                                    <p><strong>Admin:</strong> {{ $task->user->name }}</p>
                                                @endif

                                                <div class="row mt-3">
                                                    <div class="col-md-6">
                                                        <div class="border p-3 rounded">
                                                            <strong>Price:</strong>
                                                            {{ $task->total_price - auth()->user()->calculateCommission($task->total_price) }}
                                                            SAR
                                                        </div>
                                                    </div>






                                                </div>

                                                <hr class="my-4" />

                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <h6 class="fw-bold">{{ __('Pickup Information') }}</h6>
                                                        <p class="mb-1"><strong>{{ __('Address') }}:</strong>
                                                            {{ optional($task->pickup)->address ?? 'Not set' }}</p>
                                                        <p class="mb-1"><strong>{{ __('Contact Name') }}:</strong>
                                                            {{ optional($task->pickup)->contact_name ?? 'Not set' }}</p>
                                                        <p class="mb-1"><strong>{{ __('Phone') }}:</strong>
                                                            {{ optional($task->pickup)->contact_phone ?? 'Not set' }}</p>
                                                        <p class="mb-1"><strong>{{ __('Email') }}:</strong>
                                                            {{ optional($task->pickup)->contact_email ?? 'Not set' }}</p>
                                                        <p class="mb-0"><strong>{{ __('Note') }}:</strong>
                                                            {{ optional($task->pickup)->note ?? 'Not set' }}</p>
                                                    </div>

                                                    <div class="col-md-6 mb-3">
                                                        <h6 class="fw-bold">{{ __('Delivery Information') }}</h6>
                                                        <p class="mb-1"><strong>{{ __('Address') }}:</strong>
                                                            {{ optional($task->delivery)->address ?? 'Not set' }}</p>
                                                        <p class="mb-1"><strong>{{ __('Contact Name') }}:</strong>
                                                            {{ optional($task->delivery)->contact_name ?? 'Not set' }}</p>
                                                        <p class="mb-1"><strong>{{ __('Phone') }}:</strong>
                                                            {{ optional($task->delivery)->contact_phone ?? 'Not set' }}</p>
                                                        <p class="mb-1"><strong>{{ __('Email') }}:</strong>
                                                            {{ optional($task->delivery)->contact_email ?? 'Not set' }}</p>
                                                        <p class="mb-0"><strong>{{ __('Note') }}:</strong>
                                                            {{ optional($task->delivery)->note ?? 'Not set' }}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card-footer bg-white border-top-0 text-end">


                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- History Tab --}}
                                <div class="tab-pane fade" id="navs-justified-history-{{ $task->id }}"
                                    role="tabpanel">
                                    <div class="row m-0 p-4">
                                        <div class="col-md-6">
                                            <div id="task-history-container"></div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="sticky-top" style="top: 80px;">
                                                <form action="{{ route('task-histories.store') }}" method="POST"
                                                    class="card shadow-sm p-4 border-0 form_submit"
                                                    enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="task" id="task_id"
                                                        value="{{ $task->id }}">
                                                    <span class="task-error text-danger text-error"></span>

                                                    <div class="mb-3">
                                                        <label for="description"
                                                            class="form-label">{{ __('Add Note') }}</label>
                                                        <textarea name="description" id="description" class="form-control" rows="3"
                                                            placeholder="{{ __('Type the note here') }}..."></textarea>
                                                        <span class="description-error text-danger text-error"></span>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="file" class="form-label">{{ __('Upload File') }}
                                                            ({{ __('optional') }})
                                                        </label>
                                                        <input type="file" name="file" id="file"
                                                            class="form-control">
                                                        <span class="file-error text-danger text-error"></span>
                                                    </div>

                                                    <button type="submit"
                                                        class="btn btn-primary">{{ __('Submit') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>
    </div>





@endsection
