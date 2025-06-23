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
@section('page-script')
    <script>
        function openReport() {
            const reportWindow = window.open('{{ route('tasks.report', $task->id) }}', '_blank');
        }
    </script>
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

                        <div>
                            <a href="#" class="mx-2" onclick="openReport()">download task status report</a>

                            <a href="{{ route('tasks.list') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bx bx-arrow-back"></i> {{ __('Back to Tasks') }}
                            </a>

                        </div>

                    </div>

                    <div class="card-body mt-3">
                        <p><strong>{{ __('owner') }}:</strong>
                            {{ $task->owner }}</p>
                        <p></strong>
                            {{ $task->owner == 'admin' ? $task->user->name : $task->customer->name }}</p>
                        <p><strong>{{ __('phone') }}:</strong>
                            {{ $task->owner == 'admin' ? $task->user->phone : $task->customer->phone }}</p>

                        @if ($task->driver_id && $task->driver)
                            <div class="card mb-4 shadow-sm">
                                <div
                                    class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-white">
                                        <i class="fas fa-user me-2 "></i> {{ __('Assigned Driver') }}
                                    </h6>
                                    <a href="{{ route('drivers.show', [$task->driver->id, $task->driver->name]) }}"
                                        class="btn btn-light btn-sm">
                                        {{ __('View Profile') }}
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center">
                                            @if ($task->driver->image)
                                                <img src="{{ asset('storage/' . $task->driver->image) }}"
                                                    alt="{{ $task->driver->name }}"
                                                    class="img-fluid rounded-circle shadow-sm mb-2"
                                                    style="width: 80px; height: 80px; object-fit: cover;">
                                            @else
                                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width: 80px; height: 80px;">
                                                    <i class="fas fa-user fa-2x"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-10">
                                            <h5 class="mb-1">{{ $task->driver->name }}</h5>
                                            <p class="mb-1">
                                                <i class="fas fa-phone-alt me-1 text-success"></i>
                                                <a href="tel:{{ $task->driver->phone }}">{{ $task->driver->phone }}</a>
                                            </p>
                                            @if ($task->driver->email)
                                                <p class="mb-1">
                                                    <i class="fas fa-envelope me-1 text-warning"></i>
                                                    <a
                                                        href="mailto:{{ $task->driver->email }}">{{ $task->driver->email }}</a>
                                                </p>
                                            @endif
                                            @if ($task->driver->vehicle_size)
                                                <p class="mb-1">
                                                    <i class="fas fa-truck me-1 text-primary"></i>
                                                    {{ $task->driver->vehicle_size->name }}
                                                </p>
                                            @endif
                                            @if ($task->driver->team)
                                                <p class="mb-0">
                                                    <i class="fas fa-users me-1 text-info"></i>
                                                    {{ __('Team') }}: {{ $task->driver->team->name }}
                                                </p>
                                            @endif

                                            @if ($task->driver->longitude && $task->driver->altitude)
                                                <p class="mt-2 mb-0">
                                                    <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                                    <a href="https://www.google.com/maps?q={{ $task->driver->altitude }},{{ $task->driver->longitude }}"
                                                        target="_blank">
                                                        {{ __('View on Map') }}
                                                    </a>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                {{ __('Driver not assigned yet') }}
                            </div>
                        @endif


                        <div
                            class="border rounded p-3 d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 shadow-sm border">

                            {{-- Price --}}
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-dollar-sign text-success fs-5"></i>
                                <div>
                                    <small class="text-muted d-block">{{ __('Price') }}</small>
                                    <strong class="text-success">
                                        {{ $task->total_price ? number_format($task->total_price, 2) : '0.00' }}
                                        SAR
                                    </strong>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-dollar-sign text-primary fs-5"></i>
                                <div>
                                    <small class="text-muted d-block">{{ __('Commission') }}</small>
                                    <strong class="text-primary">
                                        {{ $task->total_price ? number_format($task->commission, 2) : '0.00' }}
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
                                        <li><strong>Email:</strong> {{ optional($task->pickup)->contact_emil }}</li>
                                        <li><strong>Note:</strong> {{ optional($task->pickup)->note }}</li>
                                        @if (optional($task->pickup)->scheduled_time)
                                            <li><strong>Pickup Before:</strong>
                                                {{ \Carbon\Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                    </ul>

                                    @if (optional($task->pickup)->image)
                                        <div class="mt-3">
                                            <strong>Image:</strong>
                                            <div class="mt-2 border rounded overflow-hidden" style=" width: 150px;">
                                                <img src="{{ asset($task->pickup->image) }}" alt="Pickup Image"
                                                    class="img-fluid rounded">
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
                                        <li><strong>Email:</strong> {{ optional($task->delivery)->contact_emil }}</li>
                                        <li><strong>Note:</strong> {{ optional($task->delivery)->note }}</li>
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
                                    @foreach ($task->additional_data as $key => $field)
                                        <div class="col-md-6 mb-4">
                                            <div class="border rounded p-3 h-100 ">
                                                <h6 class="text-muted mb-2">

                                                    {{ $field['label'] }}
                                                </h6>

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

                                                    @case('file_with_text')
                                                        @if ($field['value'])
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

                                                            <div class="d-flex align-items-center mb-2">
                                                                <a href="{{ asset('storage/' . $field['value']) }}"
                                                                    target="_blank"
                                                                    class="d-flex align-items-center text-decoration-none">
                                                                    <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                                    <span
                                                                        class="text-truncate">{{ basename($field['value']) }}</span>
                                                                </a>
                                                            </div>
                                                        @endif

                                                        @if (isset($field['text']) && $field['text'])
                                                            <div class="mt-2">
                                                                <small class="text-muted">Additional Info:</small>
                                                                <p class="mb-0 fw-medium">{{ $field['text'] }}</p>
                                                            </div>
                                                        @endif
                                                    @break

                                                    @case('file_expiration_date')
                                                        @if ($field['value'])
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

                                                            <div class="d-flex align-items-center mb-2">
                                                                <a href="{{ asset('storage/' . $field['value']) }}"
                                                                    target="_blank"
                                                                    class="d-flex align-items-center text-decoration-none">
                                                                    <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                                    <span
                                                                        class="text-truncate">{{ basename($field['value']) }}</span>
                                                                </a>
                                                            </div>
                                                        @endif

                                                        @if (isset($field['expiration']) && $field['expiration'])
                                                            <div class="mt-2">
                                                                <small class="text-muted">Expires:</small>
                                                                <p class="mb-0 fw-medium">
                                                                    {{ \Carbon\Carbon::parse($field['expiration'])->format('Y-m-d') }}
                                                                </p>
                                                            </div>
                                                        @endif
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
