@extends('layouts/layoutMaster')

@section('title', __('Track Task'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    <script>
        // Auto refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
@endsection

@section('content')
    <div class="row">
        <!-- Task Tracking Information -->
        <div class="col-xl-8 col-lg-7 col-md-7">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-map-pin me-2"></i>{{ __('Track Task') }} #{{ $task->id }}
                    </h5>
                    <div>
                        <a href="{{ route('customer.tasks.show', $task->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="ti ti-eye me-1"></i>{{ __('View Details') }}
                        </a>
                        <span class="badge bg-label-{{ $task->status === 'completed' ? 'success' : 'warning' }} ms-2">
                            {{ ucfirst($task->status) }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Task Progress -->
                    <div class="mb-4">
                        <h6 class="mb-3">{{ __('Task Progress') }}</h6>
                        <div class="progress-wrapper">
                            <div class="progress progress-lg">
                                @php
                                    $progressPercentage = 0;
                                    switch ($task->status) {
                                        case 'assign':
                                            $progressPercentage = 10;
                                            break;
                                        case 'started':
                                            $progressPercentage = 20;
                                            break;
                                        case 'in pickup point':
                                            $progressPercentage = 40;
                                            break;
                                        case 'loading':
                                            $progressPercentage = 50;
                                            break;
                                        case 'in the way':
                                            $progressPercentage = 70;
                                            break;
                                        case 'in delivery point':
                                            $progressPercentage = 85;
                                            break;
                                        case 'unloading':
                                            $progressPercentage = 95;
                                            break;
                                        case 'completed':
                                            $progressPercentage = 100;
                                            break;
                                    }
                                @endphp
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                    style="width: {{ $progressPercentage }}%" aria-valuenow="{{ $progressPercentage }}"
                                    aria-valuemin="0" aria-valuemax="100">
                                    {{ $progressPercentage }}%
                                </div>
                            </div>
                            <small class="text-muted mt-1">{{ __('Current Status') }}:
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}</small>
                        </div>
                    </div>

                    <!-- Driver Information -->
                    @if ($task->driver)
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted">{{ __('Driver Information') }}</h6>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-lg me-3">
                                        <img src="{{ $task->driver->image ? asset($task->driver->image) : asset('assets/img/avatars/default-avatar.png') }}"
                                            alt="Driver Avatar" class="rounded-circle">
                                    </div>
                                    <div>
                                        <h6 class="mb-1">{{ $task->driver->name }}</h6>
                                        <p class="mb-0 text-muted">{{ $task->driver->phone_code }}
                                            {{ $task->driver->phone }}</p>
                                        @if ($task->driver->full_whatsapp_number)
                                            <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $task->driver->full_whatsapp_number) }}"
                                                target="_blank" class="btn btn-sm btn-success mt-1">
                                                <i class="ti ti-brand-whatsapp me-1"></i>{{ __('WhatsApp') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">{{ __('Driver Status') }}</h6>
                                <div class="d-flex align-items-center">
                                    <span
                                        class="badge bg-label-{{ $task->driver->online ? 'success' : 'secondary' }} me-2">
                                        <i class="ti ti-circle-filled me-1"></i>
                                        {{ $task->driver->online ? __('Online') : __('Offline') }}
                                    </span>
                                    @if ($task->driver->last_seen_at)
                                        <small class="text-muted">
                                            {{ __('Last seen') }}:
                                            {{ \Carbon\Carbon::parse($task->driver->last_seen_at)->diffForHumans() }}
                                        </small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Pickup & Delivery Summary -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="border rounded p-3 mb-3 mb-md-0">
                                <h6 class="text-success mb-2">
                                    <i class="ti ti-map-pin me-2"></i>{{ __('Pickup') }}
                                </h6>
                                @if ($task->pickup)
                                    <p class="mb-1"><strong>{{ $task->pickup->address }}</strong></p>
                                    @if ($task->pickup->contact_name)
                                        <p class="mb-1 text-muted">{{ __('Contact') }}: {{ $task->pickup->contact_name }}
                                        </p>
                                    @endif
                                    @if ($task->pickup->scheduled_time)
                                        <p class="mb-0 text-muted">
                                            <i class="ti ti-clock me-1"></i>
                                            {{ \Carbon\Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i') }}
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-danger mb-2">
                                    <i class="ti ti-map-pin me-2"></i>{{ __('Delivery') }}
                                </h6>
                                @if ($task->delivery)
                                    <p class="mb-1"><strong>{{ $task->delivery->address }}</strong></p>
                                    @if ($task->delivery->contact_name)
                                        <p class="mb-1 text-muted">{{ __('Contact') }}:
                                            {{ $task->delivery->contact_name }}</p>
                                    @endif
                                    @if ($task->delivery->scheduled_time)
                                        <p class="mb-0 text-muted">
                                            <i class="ti ti-clock me-1"></i>
                                            {{ \Carbon\Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i') }}
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map Section (Placeholder) -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-map me-2"></i>{{ __('Live Tracking') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-5">
                        <i class="ti ti-map-pin display-4 text-muted mb-3"></i>
                        <h6 class="text-muted">{{ __('Map Integration Coming Soon') }}</h6>
                        <p class="text-muted">{{ __('Real-time driver location tracking will be available here') }}</p>

                        <!-- Current Location Info -->
                        @if ($task->driver && ($task->driver->longitude || $task->driver->altitude))
                            <div class="alert alert-info mt-3">
                                <h6 class="alert-heading">{{ __('Driver Last Known Location') }}</h6>
                                <p class="mb-0">
                                    <strong>{{ __('Coordinates') }}:</strong>
                                    {{ $task->driver->altitude ?? 'N/A' }}, {{ $task->driver->longitude ?? 'N/A' }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Status Timeline -->
        <div class="col-xl-4 col-lg-5 col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-history me-2"></i>{{ __('Status Updates') }}
                    </h5>
                    <small class="text-muted">{{ __('Auto-refreshes every 30 seconds') }}</small>
                </div>
                <div class="card-body">
                    @if ($task->history && $task->history->count() > 0)
                        <div class="timeline">
                            @foreach ($task->history->take(10) as $history)
                                <div class="timeline-item">
                                    <div
                                        class="timeline-point
                                        @if ($history->action_type === 'completed') timeline-point-success
                                        @elseif($history->action_type === 'cancelled') timeline-point-danger
                                        @elseif(in_array($history->action_type, ['started', 'assign'])) timeline-point-warning
                                        @else timeline-point-primary @endif">
                                    </div>
                                    <div class="timeline-event">
                                        <div class="timeline-header mb-1">
                                            <h6 class="mb-0">{{ ucfirst(str_replace('_', ' ', $history->action_type)) }}
                                            </h6>
                                            <small
                                                class="text-muted">{{ $history->created_at->format('M d, H:i') }}</small>
                                        </div>
                                        <p class="mb-1">{{ $history->description }}</p>
                                        @if ($history->driver)
                                            <small class="text-muted">{{ __('By') }}:
                                                {{ $history->driver->name }}</small>
                                        @elseif($history->user)
                                            <small class="text-muted">{{ __('By') }}:
                                                {{ $history->user->name }}</small>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">{{ __('No status updates available') }}</p>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-settings me-2"></i>{{ __('Quick Actions') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('customer.tasks.show', $task->id) }}" class="btn btn-outline-primary">
                            <i class="ti ti-eye me-2"></i>{{ __('View Full Details') }}
                        </a>

                        @if ($task->driver && $task->driver->full_whatsapp_number)
                            <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $task->driver->full_whatsapp_number) }}"
                                target="_blank" class="btn btn-outline-success">
                                <i class="ti ti-brand-whatsapp me-2"></i>{{ __('Contact Driver') }}
                            </a>
                        @endif

                        <button type="button" class="btn btn-outline-info" onclick="location.reload()">
                            <i class="ti ti-refresh me-2"></i>{{ __('Refresh Status') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
