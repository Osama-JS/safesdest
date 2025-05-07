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
    @vite(['resources/assets/js/dashboards-analytics.js'])
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
        <!-- User Sidebar -->
        <div class="col-xl-4 col-lg-5 order-1 order-md-0">
            <!-- User Card -->
            <div class="card mb-6">
                <div class="card-body pt-12">
                    <div class="user-avatar-section">
                        <div class=" d-flex align-items-center flex-column">
                            <img class="img-fluid rounded mb-4"
                                src="{{ auth()->user()->image ? asset(auth()->user()->image) : asset('assets/img/person.png') }}"
                                style="width: 200px;" alt="User avatar" />
                            <div class="user-info text-center">
                                <h5>{{ auth()->user()->name }}</h5> <span
                                    class="badge bg-label-secondary">{{ auth()->user()->team->name ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-around flex-wrap my-6 gap-0 gap-md-3 gap-lg-4">
                        <div class="d-flex align-items-center me-5 gap-4">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded">
                                    <i class='ti ti-checkbox ti-lg'></i>
                                </div>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ auth()->user()->tasks()->where('status', 'completed')->count() }}</h5>
                                <span>{{ __('Task Done') }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-4">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded">
                                    <i class='ti ti-truck-delivery ti-lg'></i>
                                </div>
                            </div>
                            <div>
                                <h5 class="mb-0">
                                    {{ auth()->user()->tasks()->where('status', '!=', 'completed')->where('status', '!=', 'canceled')->count() }}
                                </h5>
                                <span>{{ __('Running Tasks') }}</span>
                            </div>
                        </div>
                    </div>
                    <h5 class="pb-4 border-bottom mb-4">{{ __('Details') }}</h5>
                    <div class="info-container">
                        <ul class="list-unstyled mb-6">
                            <li class="mb-2">
                                <span class="h6">{{ __('username') }}:</span>
                                <span>{{ auth()->user()->username }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('Phone') }}:</span>
                                <span>{{ auth()->user()->phone }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('Email') }}:</span>
                                <span>{{ auth()->user()->email }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('address') }}:</span>
                                <span>{{ auth()->user()->address }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('Status') }}:</span>
                                <span>{{ auth()->user()->status }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('Role') }}:</span>
                                <span>{{ auth()->user()->role }}</span>
                            </li>

                        </ul>

                    </div>
                </div>
            </div>
            <!-- /User Card -->

        </div>
        <!--/ User Sidebar -->


        <!-- User Content -->
        <div class="col-xl-8 col-lg-7 order-0 order-md-1">


            @foreach (auth()->user()->possible_tasks as $task)
                <div class="mb-4">
                    <div class="card shadow-sm border-0">
                        {{-- الخريطة --}}
                        <div class="map-container rounded-top" id="map-{{ $task->id }}" style="height: 200px;"></div>

                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
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

                            {{-- بيانات العميل --}}
                            <div class="mb-3">
                                <p class="mb-1"><strong>Owner Type:</strong> {{ ucfirst($task->owner) }}</p>
                                @if ($task->owner === 'customer' && $task->customer)
                                    <p class="mb-0"><strong>Customer Name:</strong> {{ $task->customer->name }}</p>
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
                                        <strong>Pickup:</strong>
                                        <p class="mb-0 text-muted">{{ optional($task->pickup)->address ?? 'Not set' }}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <strong>Delivery:</strong>
                                        <p class="mb-0 text-muted">{{ optional($task->delivery)->address ?? 'Not set' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- معلومات إضافية --}}
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <p><strong>Price:</strong> {{ $task->total_price ?? '—' }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Payment:</strong> {{ ucfirst($task->payment_status) ?? '—' }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Payment Method:</strong> {{ ucfirst($task->payment_method) ?? '—' }}</p>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer bg-white border-top-0 text-end">
                            <a href="" class="btn btn-outline-primary">View
                                Details</a>
                        </div>
                    </div>
                </div>
            @endforeach


        </div>
        <!--/ User Content -->
    </div>

@endsection
