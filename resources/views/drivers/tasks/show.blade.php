@extends('layouts.layoutMaster')

@section('title', 'تفاصيل المهمة')

@section('page-style')
    <style>
        .timeline {
            list-style: none;
            padding: 0;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #ccc;
        }

        .timeline-item {
            position: relative;
            padding-left: 40px;
            margin-bottom: 20px;
        }

        .timeline-point {
            position: absolute;
            left: 7px;
            top: 5px;
            width: 16px;
            height: 16px;
            background: #0d6efd;
            border-radius: 50%;
        }
    </style>
@endsection
@section('task-isactive', 'active')
@section('content')
    <div class="container my-4">
        <div class="row">
            <!-- تفاصيل المهمة -->
            <div class="col-lg-8 col-md-12">
                <div class="card mb-4">
                    <div class="card-header border-bottom text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('Task Details') }} #{{ $task->id }}</h5>

                        <a href="{{ route('driver.task.list') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-arrow-back"></i> {{ __('Back to Tasks') }}
                        </a>
                    </div>

                    <div class="card-body mt-3">
                        <div
                            class="border rounded p-3 d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 shadow-sm border">

                            {{-- Price --}}
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-dollar-sign text-success fs-5"></i>
                                <div>
                                    <small class="text-muted d-block">{{ __('Price') }}</small>
                                    <strong class="text-success">
                                        {{ $task->total_price ? number_format($task->total_price - $task->commission, 2) : '0.00' }}
                                        SAR
                                    </strong>
                                </div>
                            </div>

                            {{-- Status --}}
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-info-circle text-primary fs-5"></i>
                                <div>
                                    <small class="text-muted d-block">{{ __('Status') }}</small>
                                    <span class="badge bg-primary">{{ ucfirst($task->status) }}</span>
                                </div>
                            </div>

                            {{-- Closed --}}
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-lock{{ $task->closed ? '' : '-open' }} text-danger fs-5"></i>
                                <div>
                                    <small class="text-muted d-block">{{ __('Closed?') }}</small>
                                    <span class="badge bg-{{ $task->closed ? 'danger' : 'success' }}">
                                        {{ $task->closed ? __('Yes') : __('No') }}
                                    </span>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                <!-- نقطة الاستلام والتسليم -->
                <div class="card mb-4">
                    <div class="card-header border-bottom text-white">
                        <h6 class="mb-0">{{ 'Pickup & Delivery Points' }}</h6>
                    </div>
                    <div class="card-body mt-4">
                        <div class="row g-4">
                            <!-- Pickup -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 shadow-sm bg-white">
                                    <h6 class="mb-3 text-primary d-flex align-items-center justify-content-between">
                                        <span>
                                            <i class="fas fa-map-marker-alt me-1"></i> {{ __('Pickup') }}
                                        </span>
                                        @if (optional($task->pickup)->latitude && optional($task->pickup)->longitude)
                                            <a href="https://www.google.com/maps?q={{ $task->pickup->latitude }},{{ $task->pickup->longitude }}"
                                                target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-map-location-dot me-1"></i>
                                            </a>
                                        @endif
                                    </h6>


                                    <ul class="list-unstyled mb-3">
                                        <li><strong>Address:</strong> {{ optional($task->pickup)->address }}</li>
                                        <li><strong>Contact Name:</strong> {{ optional($task->pickup)->contact_name }}</li>
                                        <li><strong>Phone:</strong> {{ optional($task->pickup)->contact_phone }}</li>
                                        <li><strong>Email:</strong> {{ optional($task->pickup)->email }}</li>
                                        <li><strong>Notes:</strong> {{ optional($task->pickup)->note }}</li>
                                        @if (optional($task->pickup)->scheduled_time)
                                            <li><strong>Pickup Before:</strong>
                                                {{ \Carbon\Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                    </ul>

                                    @if (optional($task->pickup)->image_path)
                                        <div class="mt-3">
                                            <strong>Image:</strong>
                                            <div class="mt-2 border rounded overflow-hidden" style="max-height: 250px;">
                                                <img src="{{ asset('storage/' . $task->pickup->image_path) }}"
                                                    alt="Pickup Image" class="img-fluid rounded">
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Delivery -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 shadow-sm bg-white">
                                    <h6 class="mb-3 text-success d-flex align-items-center justify-content-between">
                                        <span>
                                            <i class="fas fa-truck me-1"></i> {{ __('Delivery') }}
                                        </span>
                                        @if (optional($task->delivery)->latitude && optional($task->delivery)->longitude)
                                            <a href="https://www.google.com/maps?q={{ $task->delivery->latitude }},{{ $task->delivery->longitude }}"
                                                target="_blank" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-map-location-dot me-1"></i>
                                            </a>
                                        @endif
                                    </h6>


                                    <ul class="list-unstyled mb-3">
                                        <li><strong>Address:</strong> {{ optional($task->delivery)->address }}</li>
                                        <li><strong>Contact Name:</strong> {{ optional($task->delivery)->contact_name }}
                                        </li>
                                        <li><strong>Phone:</strong> {{ optional($task->delivery)->contact_phone }}</li>
                                        <li><strong>Email:</strong> {{ optional($task->delivery)->email }}</li>
                                        <li><strong>Notes:</strong> {{ optional($task->delivery)->note }}</li>
                                        @if (optional($task->delivery)->scheduled_time)
                                            <li><strong>Delivery Before:</strong>
                                                {{ \Carbon\Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                    </ul>

                                    @if (optional($task->delivery)->image_path)
                                        <div class="mt-3">
                                            <strong>Image:</strong>
                                            <div class="mt-2 border rounded overflow-hidden" style="max-height: 250px;">
                                                <img src="{{ asset('storage/' . $task->delivery->image_path) }}"
                                                    alt="Delivery Image" class="img-fluid rounded">
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- بيانات إضافية -->
                @if ($task->additional_data)
                    <div class="card mb-4">
                        <div class="card-header border-bottom ">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-layer-group me-2 text-primary"></i> {{ __('Additional Data') }}
                            </h5>
                        </div>

                        <div class="card-body mt-3">
                            @if (is_array($task->additional_data) && count($task->additional_data) > 0)
                                <div class="row">
                                    @foreach ($task->driver_visible_additional_data as $key => $field)
                                        <div class="col-md-6 mb-4">
                                            <div class="border rounded p-3 h-100">
                                                <h6 class="text-muted mb-2">{{ $field['label'] }}</h6>
                                                @switch($field['type'])
                                                    @case('text')
                                                    @case('string')

                                                    @case('number')
                                                        <p class="mb-0">{{ $field['value'] }}</p>
                                                    @break

                                                    @case('image')
                                                        <img src="{{ asset('storage/' . $field['value']) }}"
                                                            alt="{{ $field['label'] }}" class="img-fluid rounded border"
                                                            style="max-height: 200px; object-fit: cover;">
                                                    @break

                                                    @case('file')
                                                        @php
                                                            $ext = strtolower(
                                                                pathinfo($field['value'], PATHINFO_EXTENSION),
                                                            );
                                                            $icons = [
                                                                'pdf' => 'ti ti-file-text',
                                                                'doc' => 'ti ti-file-description',
                                                                'docx' => 'ti ti-file-description',
                                                                'xls' => 'ti ti-file-spreadsheet',
                                                                'xlsx' => 'ti ti-file-spreadsheet',
                                                                'ppt' => 'ti ti-presentation',
                                                                'pptx' => 'ti ti-presentation',
                                                            ];
                                                            $iconClass = $icons[$ext] ?? 'ti ti-file';
                                                        @endphp
                                                        <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                                            class="d-flex align-items-center text-decoration-none mt-1">
                                                            <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                            <span class="text-truncate">{{ basename($field['value']) }}</span>
                                                        </a>
                                                    @break

                                                    @case('file_expiration_date')
                                                        <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                                            class="d-flex align-items-center text-decoration-none mt-1">
                                                            <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                            <span class="text-truncate">{{ basename($field['value']) }}</span>
                                                        </a>
                                                        <p class="mt-3">expiration date: {{ $field['expiration'] }}</p>
                                                    @break

                                                    @default
                                                        <p class="mb-0">{{ $field['value'] }}</p>
                                                @endswitch
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info" role="alert">
                                    {{ __('No additional data found for this customer.') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif




            </div>

            <!-- سجل الأحداث -->
            <div class="col-lg-4 col-md-12">
                <div class="card">
                    <div class="card-header border-bottom bg-info ">
                        <h5 class="mb-0 text-white">{{ __('Task History') }}</h5>
                    </div>
                    <div class="card-body">
                        @if ($task->history->count())
                            <ul class="timeline">
                                @foreach ($task->history->sortByDesc('created_at') as $entry)
                                    <li class="timeline-item">
                                        <span class="timeline-point"></span>
                                        <div class="timeline-event">
                                            <div class="d-flex justify-content-between mb-1">
                                                <strong>{{ ucfirst($entry->action_type) }}</strong>
                                                <small
                                                    class="text-muted">{{ $entry->created_at->format('Y-m-d H:i') }}</small>
                                            </div>
                                            <p class="mb-1">{{ $entry->description }}</p>
                                            @if ($entry->file_path)
                                                <a href="{{ asset('storage/' . $entry->file_path) }}" target="_blank"
                                                    class="btn btn-sm btn-outline-primary">عرض المرفق</a>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted">لا توجد أحداث مسجلة.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
