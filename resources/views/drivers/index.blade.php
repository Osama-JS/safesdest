@php

    use Illuminate\Support\Facades\Session;
    $guard = Session::get('guard');

@endphp
@extends('layouts/layoutMaster')

@section('title', 'Driver Dashboard')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss'])
@endsection

@section('page-style')
    <!-- Page -->
    @vite(['resources/assets/vendor/scss/pages/cards-advance.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
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
            {{ auth()->user()->tasks }}

            @foreach ($data as $task)
                <div class="mb-4">

                    <div id="task-details-view" class=" bg-white shadow-lg p-0 overflow-auto">
                        <div class="card-header bg-white border-bottom-0 p-3">
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
                        <div class="nav-align-top  overflow-auto p-0 " style="min-height: 75vh">
                            <ul class="nav nav-tabs nav-fill bg-white border-bottom sticky-top"
                                style="top: 0; z-index: 1030;" role="tablist">
                                <li class="nav-item">
                                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                        data-bs-target="#navs-justified-details" aria-controls="navs-justified-home"
                                        aria-selected="true"><span class="d-none d-sm-block">
                                            {{ __('details') }}</span><i class="ti ti-home ti-sm d-sm-none"></i></button>
                                </li>

                                <li class="nav-item">
                                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                        data-bs-target="#navs-justified-history" aria-controls="navs-justified-messages"
                                        aria-selected="false"><span class="d-none d-sm-block">
                                            {{ __('history') }}</span><i
                                            class="ti ti-message-dots ti-sm d-sm-none"></i></button>
                                </li>
                            </ul>

                            <div class="tab-content p-0 m-0" style="max-height: calc(75vh - 60px); overflow-y: auto;">
                                <div class="tab-pane fade show active" id="navs-justified-details" role="tabpanel">

                                    <div id="task-details-content">
                                        <div class="card shadow-sm border-0">

                                            {{-- الخريطة --}}
                                            <div class="map-container
                                    rounded-top"
                                                id="map-{{ $task->id }}" style="height: 100px;">
                                            </div>

                                            <div class="card-body">
                                                {{-- بيانات العميل --}}
                                                <div class="mb-3">
                                                    <p class="mb-1"><strong>Owner Type:</strong>
                                                        {{ ucfirst($task->owner) }}</p>
                                                    @if ($task->owner === 'customer' && $task->customer)
                                                        <p class="mb-0"><strong>Customer Name:</strong>
                                                            {{ $task->customer->name }}
                                                        </p>
                                                        <p class="mb-0"><strong>Customer Phone:</strong>
                                                            {{ $task->customer->phone ?? 'N/A' }}</p>
                                                    @elseif ($task->owner === 'admin' && $task->user)
                                                        <p class="mb-0"><strong>Admin:</strong> {{ $task->user->name }}
                                                        </p>
                                                    @endif
                                                    <div class="row mt-3">
                                                        <div class="col-md-6 ">
                                                            <p class="border p-2 rounded"><strong>Price:</strong>
                                                                {{ $task->total_price - auth()->user()->calculateCommission($task->total_price) }}
                                                                SAR
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- بيانات النقاط --}}
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-2">
                                                            <strong>{{ __('Pickup Address') }}:</strong>
                                                            <p class="mb-0 text-muted">
                                                                {{ optional($task->pickup)->address ?? 'Not set' }}
                                                            </p>
                                                            <strong>{{ __('Pickup Contact Name') }}:</strong>
                                                            <p class="mb-0 text-muted">
                                                                {{ optional($task->pickup)->contact_name ?? 'Not set' }}
                                                            </p>
                                                            <strong>{{ __('Pickup Contact phone') }}:</strong>
                                                            <p class="mb-0 text-muted">
                                                                {{ optional($task->pickup)->contact_phone ?? 'Not set' }}
                                                            </p>
                                                            <strong>{{ __('Pickup Contact eamil') }}:</strong>
                                                            <p class="mb-0 text-muted">
                                                                {{ optional($task->pickup)->contact_email ?? 'Not set' }}
                                                            </p>
                                                            <strong>{{ __('Pickup Note') }}:</strong>
                                                            <p class="mb-0 text-muted">
                                                                {{ optional($task->pickup)->note ?? 'Not set' }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-2">
                                                            <strong>{{ __('Delivery address') }}:</strong>
                                                            <p class="mb-0 text-muted">
                                                                {{ optional($task->delivery)->address ?? 'Not set' }}
                                                            </p>
                                                            <strong>{{ __('Delivery Contact Name') }}:</strong>
                                                            <p class="mb-0 text-muted">
                                                                {{ optional($task->delivery)->contact_name ?? 'Not set' }}
                                                            </p>
                                                            <strong>{{ __('Delivery Contact phone') }}:</strong>
                                                            <p class="mb-0 text-muted">
                                                                {{ optional($task->delivery)->contact_phone ?? 'Not set' }}
                                                            </p>
                                                            <strong>{{ __('Delivery Contact eamil') }}:</strong>
                                                            <p class="mb-0 text-muted">
                                                                {{ optional($task->delivery)->contact_email ?? 'Not set' }}
                                                            </p>
                                                            <strong>{{ __('Delivery Note') }}:</strong>
                                                            <p class="mb-0 text-muted">
                                                                {{ optional($task->delivery)->note ?? 'Not set' }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- معلومات إضافية --}}

                                            </div>

                                            <div class="card-footer bg-white border-top-0 text-end d-flex ">
                                                <form action="{{ route('driver.respond.task') }}" method="POST"
                                                    class="form_submit">
                                                    <input type="hidden" name="task_id" value="{{ $task->id }}">
                                                    <input type="hidden" name="response" value="accept">
                                                    <button type="submit" class="btn btn-primary mx-2">Accept</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade " id="navs-justified-history" role="tabpanel">

                                    <div class="my-3 p-5">
                                        <form action="{{ route('task-histories.store') }}" method="POST"
                                            class="form_submit" enctype="multipart/form-data"
                                            class="card p-4 shadow-sm border-0 mb-4">
                                            @csrf
                                            <input type="hidden" name="task" id="task_id"
                                                value="{{ $task->id }}">
                                            <span class="task-error text-danger text-error"></span>
                                            {{-- حقل وصف الملاحظة --}}
                                            <div class="mb-3">
                                                <label for="description" class="form-label">{{ __('Add Note') }}</label>
                                                <textarea name="description" id="description" class="form-control" rows="3"
                                                    placeholder="{{ __('Type the note here') }}..."></textarea>
                                                <span class="description-error text-danger text-error"></span>

                                            </div>

                                            {{-- رفع ملف --}}
                                            <div class="mb-3">
                                                <label for="file" class="form-label">{{ __('upload file') }}
                                                    ({{ __('optional') }})
                                                </label>
                                                <input type="file" name="file" id="file"
                                                    class="form-control">
                                                <span class="file-error text-danger text-error"></span>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                {{ __('Submit') }}
                                            </button>
                                        </form>
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
