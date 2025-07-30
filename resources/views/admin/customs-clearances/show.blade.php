@extends('layouts/layoutMaster')

@section('title', __('Customs Clearance Details'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Page Styles -->
@section('page-style')
    @vite(['resources/css/app.css'])
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

        .info-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .status-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }

        .detail-row {
            border-bottom: 1px solid #f0f0f0;
            padding: 0.75rem 0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            min-width: 150px;
        }

        .detail-value {
            color: #6c757d;
        }

        .file-icon {
            font-size: 3rem;
            color: #6c757d;
        }

        .gradient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-header-custom {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stats-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            color: white;
        }

        .delivery-section {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            border: none;
            color: white;
        }
    </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/admin/customs-clearances/show.js'])
@endsection

@section('content')
    <div class="container my-4">
        <div class="row">
            <!-- تفاصيل التخليص الجمركي -->
            <div class="col-lg-8 col-md-12">
                <div class="card mb-4 info-card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark">
                            <i class="ti ti-file-text me-2 text-primary"></i>
                            {{ __('Customs Clearance Details') }} #{{ $data->id }}
                        </h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.customs-clearances.index') }}" class="btn btn-outline-secondary">
                                <i class="ti ti-arrow-left me-1"></i>
                                Back to List
                            </a>

                        </div>
                    </div>

                    <div class="card-body">
                        <!-- معلومات أساسية -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="detail-row d-flex">
                                    <span class="detail-label">{{ __('Owner') }}:</span>
                                    <span class="detail-value">{{ __('Administrator') }}</span>
                                </div>



                                <div class="detail-row d-flex">
                                    <span class="detail-label">{{ __('Name') }}:</span>
                                    <span class="detail-value">{{ $data->owner->name }}</span>
                                </div>

                            </div>
                            <div class="col-md-6">
                                <div class="detail-row d-flex">
                                    <span class="detail-label">{{ __('Created At') }}:</span>
                                    <span class="detail-value">{{ $data->created_at->format('Y-m-d H:i') }}</span>
                                </div>
                                <div class="detail-row d-flex">
                                    <span class="detail-label">{{ __('Updated At') }}:</span>
                                    <span class="detail-value">{{ $data->updated_at->format('Y-m-d H:i') }}</span>
                                </div>
                                @if ($data->clearance_agent_id && $data->clearanceAgent)
                                    <div class="detail-row d-flex">
                                        <span class="detail-label">{{ __('Assigned Agent') }}:</span>
                                        <span class="detail-value">{{ $data->clearanceAgent->name }}</span>
                                    </div>
                                @endif
                                @if ($data->price)
                                    <div class="detail-row d-flex">
                                        <span class="detail-label">{{ __('Price') }}:</span>
                                        <span class="detail-value">{{ number_format($data->price, 2) }}
                                            {{ __('SAR') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @if ($data->clearance_agent_id && $data->clearanceAgent)
                            <div class="card mb-4 shadow-sm">
                                <div
                                    class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-dark">
                                        <i class="fas fa-user me-2 text-primary"></i> {{ __('Assigned Driver') }}
                                    </h6>
                                    {{-- <a href="{{ route('drivers.show', [$data->clearanceAgent->id, $data->clearanceAgent->name]) }}"
                                        class="btn btn-primary btn-sm">
                                        {{ __('View Profile') }}
                                    </a> --}}
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center">
                                            @if ($data->clearanceAgent->image)
                                                <img src="{{ asset('storage/' . $data->clearanceAgent->image) }}"
                                                    alt="{{ $data->clearanceAgent->name }}"
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
                                            <h5 class="mb-1">{{ $data->clearanceAgent->name }}</h5>
                                            <p class="mb-1">
                                                <i class="fas fa-phone-alt me-1 text-success"></i>
                                                <a
                                                    href="tel:{{ $data->clearanceAgent->phone }}">{{ $data->clearanceAgent->phone }}</a>
                                            </p>
                                            @if ($data->clearanceAgent->email)
                                                <p class="mb-1">
                                                    <i class="fas fa-envelope me-1 text-warning"></i>
                                                    <a
                                                        href="mailto:{{ $data->clearanceAgent->email }}">{{ $data->clearanceAgent->email }}</a>
                                                </p>
                                            @endif
                                            @if ($data->clearanceAgent->vehicle_size)
                                                <p class="mb-1">
                                                    <i class="fas fa-truck me-1 text-primary"></i>
                                                    {{ $data->clearanceAgent->vehicle_size->name }}
                                                </p>
                                            @endif
                                            @if ($data->clearanceAgent->team)
                                                <p class="mb-0">
                                                    <i class="fas fa-users me-1 text-info"></i>
                                                    {{ __('Team') }}: {{ $data->clearanceAgent->team->name }}
                                                </p>
                                            @endif


                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                {{ __('Clearance agent not assigned yet') }}
                            </div>
                        @endif

                        <!-- إحصائيات المهمة -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3 col-sm-6">
                                <div class="card stats-card text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-dollar-sign fs-2 mb-2"></i>
                                        <h6 class="card-title">{{ __('Total Price') }}</h6>
                                        <h4 class="mb-0">
                                            {{ $data->total_price ? number_format($data->total_price, 2) : '0.00' }} SAR
                                        </h4>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="card bg-warning text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-percentage fs-2 mb-2"></i>
                                        <h6 class="card-title">{{ __('Commission') }}</h6>
                                        <h4 class="mb-0">
                                            {{ $data->commission ? number_format($data->commission, 2) : '0.00' }} SAR
                                        </h4>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="card bg-info text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-info-circle fs-2 mb-2"></i>
                                        <h6 class="card-title">{{ __('Status') }}</h6>
                                        <span class="status-badge bg-white text-info fw-bold">
                                            {{ ucfirst($data->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="card bg-{{ $data->closed ? 'danger' : 'success' }} text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-lock{{ $data->closed ? '' : '-open' }} fs-2 mb-2"></i>
                                        <h6 class="card-title">{{ __('Task Status') }}</h6>
                                        <span
                                            class="status-badge bg-white text-{{ $data->closed ? 'danger' : 'success' }} fw-bold">
                                            {{ $data->closed ? __('Closed') : __('Open') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- العروض -->
                        @if ($data->offers && $data->offers->count() > 0)
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h6 class="mb-3">
                                        <i class="ti ti-currency-dollar me-2"></i>{{ __('Offers') }}
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>{{ __('Agent') }}</th>
                                                    <th>{{ __('Price') }}</th>
                                                    <th>{{ __('Description') }}</th>
                                                    <th>{{ __('Date') }}</th>
                                                    <th>{{ __('Status') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($data->offers as $offer)
                                                    <tr>
                                                        <td>{{ $offer->clearanceAgent->name ?? __('N/A') }}</td>
                                                        <td>{{ number_format($offer->price, 2) }} {{ __('SAR') }}</td>
                                                        <td>{{ $offer->description ?? __('N/A') }}</td>
                                                        <td>{{ $offer->created_at->format('Y-m-d H:i') }}</td>
                                                        <td>

                                                            <span
                                                                class="badge {{ $offer->accepted ? 'bg-success' : ($offer->accepted === 'rejected' ? 'bg-danger' : 'bg-warning') }}">
                                                                {{ $offer->accepted ? __('Accepted') : ($offer->accepted === 'rejected' ? __('Rejected') : __('Pending')) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>


                <!-- بيانات إضافية -->
                @if ($data->additional_data)
                    <div class="card mb-4 info-card">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-layer-group me-2 text-primary"></i>
                                {{ __('Additional Data') }}
                            </h5>
                        </div>

                        <div class="card-body mt-3">
                            @if (is_array($data->additional_data) && count($data->additional_data) > 0)
                                <div class="row">
                                    @foreach ($data->additional_data as $key => $field)
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
                <div class="card info-card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark">
                            <i class="fas fa-history me-2 text-primary"></i>
                            {{ __('Customs Clearance History') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        @if ($data->history->count())
                            <ul class="timeline">
                                @foreach ($data->history->sortByDesc('created_at') as $entry)
                                    <li class="timeline-item">
                                        <span class="timeline-point"></span>
                                        <div class="timeline-event">
                                            <div class="d-flex justify-content-between mb-1">
                                                <strong>{{ ucfirst($entry->action_type) }}</strong>
                                                <small
                                                    class="text-muted">{{ $entry->created_at->format('Y-m-d H:i') }}</small>
                                            </div>
                                            @if ($entry->user_id && $entry->clearance_agent_id)
                                                <p>
                                                    <small class="text-muted">{{ __('By') }}:
                                                        {{ $entry->user?->name }}</small>
                                                </p>
                                                <p>
                                                    <small class="text-muted">{{ __('To') }}:
                                                        {{ $entry->clearanceAgent->name }}</small>
                                                </p>
                                            @elseif ($entry->user_id)
                                                <p>
                                                    <small class="text-muted">{{ __('By') }}:
                                                        {{ $entry->user?->name }}</small>
                                                </p>
                                            @elseif ($entry->clearance_agent_id)
                                                <p>
                                                    <small class="text-muted">{{ __('By') }}:
                                                        {{ $entry->clearanceAgent->name }}</small>
                                                </p>
                                            @endif

                                            <p class="mb-1">{{ $entry->description }}</p>
                                            @if ($entry->file_path)
                                                <a href="{{ asset('storage/' . $entry->file_path) }}" target="_blank"
                                                    class="btn btn-sm btn-outline-primary">{{ __('download file') }}</a>
                                            @endif


                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted">{{ __('No History Recorded Yet') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endsection
