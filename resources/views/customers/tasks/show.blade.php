@extends('layouts/layoutMaster')

@section('title', __('Task Details'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
    <div class="row">
        <!-- Task Information -->
        <div class="col-xl-8 col-lg-7 col-md-7">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-clipboard-list me-2"></i>{{ __('Task Details') }} #{{ $task->id }}
                    </h5>
                    <div>
                        @if (
                            !$task->closed &&
                                in_array($task->status, [
                                    'assign',
                                    'started',
                                    'in pickup point',
                                    'loading',
                                    'in the way',
                                    'in delivery point',
                                    'unloading',
                                ]))
                            <a href="{{ route('customer.tasks.track', $task->id) }}" class="btn btn-primary btn-sm">
                                <i class="ti ti-map-pin me-1"></i>{{ __('Track Task') }}
                            </a>
                        @endif
                        @if ($task->payment_status === 'paid' || $task->payment_status === 'completed' || $task->status === 'completed')
                             <a href="javascript:void(0);" onclick="downloadInvoice({{ $task->id }})" class="btn btn-outline-primary btn-sm ms-2">
                                <i class="ti ti-file-invoice me-1"></i>{{ __('Download Invoice') }}
                            </a>
                        @endif
                        <span
                            class="badge bg-label-{{ $task->status === 'completed' ? 'success' : ($task->status === 'cancelled' ? 'danger' : 'warning') }} ms-2">
                            {{ ucfirst($task->status) }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">{{ __('Basic Information') }}</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <span class="h6">{{ __('Order ID') }}:</span>
                                    <span>{{ $task->order_id ?? 'N/A' }}</span>
                                </li>
                                <li class="mb-2">
                                    <span class="h6">{{ __('Total Price') }}:</span>
                                    <span
                                        class="text-success fw-bold">{{ $task->total_price ? number_format($task->total_price, 2) . ' SAR' : 'N/A' }}</span>
                                </li>
                                <li class="mb-2">
                                    <span class="h6">{{ __('Payment Status') }}:</span>
                                    <span
                                        class="badge bg-label-{{ $task->payment_status === 'paid' ? 'success' : 'warning' }}">
                                        {{ ucfirst($task->payment_status ?? 'pending') }}
                                    </span>
                                </li>
                                <li class="mb-2">
                                    <span class="h6">{{ __('Vehicle') }}:</span>
                                    <span>
                                        @if ($task->vehicle_size)
                                            {{ $task->vehicle_size->type->vehicle->name }} -
                                            {{ $task->vehicle_size->type->name }} -
                                            {{ $task->vehicle_size->name }}
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </li>
                                <li class="mb-2">
                                    <span class="h6">{{ __('Created At') }}:</span>
                                    <span>{{ $task->created_at->format('Y-m-d H:i') }}</span>
                                </li>
                                @if ($task->conditions)
                                    <li class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        {{ $task->conditions }}
                                    </li>
                                @endif
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">{{ __('Driver Information') }}</h6>
                            @if ($task->driver)
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <span class="h6">{{ __('Driver Name') }}:</span>
                                        <span>{{ $task->driver->name }}</span>
                                    </li>
                                    <li class="mb-2">
                                        <span class="h6">{{ __('Phone') }}:</span>
                                        <span>{{ $task->driver->phone_code }} {{ $task->driver->phone }}</span>
                                    </li>
                                    @if ($task->driver->full_whatsapp_number)
                                        <li class="mb-2">
                                            <span class="h6">
                                                <i class="ti ti-brand-whatsapp text-success me-1"></i>{{ __('WhatsApp') }}:
                                            </span>
                                            <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $task->driver->full_whatsapp_number) }}"
                                                target="_blank" class="text-success text-decoration-none">
                                                {{ $task->driver->whatsapp_display }}
                                                <i class="ti ti-external-link ms-1"></i>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            @else
                                <p class="text-muted">{{ __('No driver assigned yet') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pickup & Delivery Points -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-map-pins me-2"></i>{{ __('Pickup & Delivery Points') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Pickup Point -->
                        <div class="col-md-6">
                            <div class="border rounded p-3 mb-3 mb-md-0">
                                <h6 class="text-success mb-3">
                                    <i class="ti ti-map-pin me-2"></i>{{ __('Pickup Point') }}
                                </h6>
                                @if ($task->pickup)
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <strong>{{ __('Address') }}:</strong><br>
                                            {{ $task->pickup->address }}
                                        </li>
                                        <li class="mb-2">
                                            <strong>{{ __('Contact Name') }}:</strong>
                                            {{ $task->pickup->contact_name ?? 'N/A' }}
                                        </li>
                                        <li class="mb-2">
                                            <strong>{{ __('Contact Phone') }}:</strong>
                                            {{ $task->pickup->contact_phone ?? 'N/A' }}
                                        </li>
                                        @if ($task->pickup->scheduled_time)
                                            <li class="mb-2">
                                                <strong>{{ __('Scheduled Time') }}:</strong>
                                                {{ \Carbon\Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                        @if ($task->pickup->note)
                                            <li class="mb-2">
                                                <strong>{{ __('Note') }}:</strong>
                                                {{ $task->pickup->note }}
                                            </li>
                                        @endif
                                    </ul>
                                @else
                                    <p class="text-muted">{{ __('No pickup information available') }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Delivery Point -->
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-danger mb-3">
                                    <i class="ti ti-map-pin me-2"></i>{{ __('Delivery Point') }}
                                </h6>
                                @if ($task->delivery)
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <strong>{{ __('Address') }}:</strong><br>
                                            {{ $task->delivery->address }}
                                        </li>
                                        <li class="mb-2">
                                            <strong>{{ __('Contact Name') }}:</strong>
                                            {{ $task->delivery->contact_name ?? 'N/A' }}
                                        </li>
                                        <li class="mb-2">
                                            <strong>{{ __('Contact Phone') }}:</strong>
                                            {{ $task->delivery->contact_phone ?? 'N/A' }}
                                        </li>
                                        @if ($task->delivery->scheduled_time)
                                            <li class="mb-2">
                                                <strong>{{ __('Scheduled Time') }}:</strong>
                                                {{ \Carbon\Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                        @if ($task->delivery->note)
                                            <li class="mb-2">
                                                <strong>{{ __('Note') }}:</strong>
                                                {{ $task->delivery->note }}
                                            </li>
                                        @endif
                                    </ul>
                                @else
                                    <p class="text-muted">{{ __('No delivery information available') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information (Customer Visible Only) -->
            @if ($task->customer_visible_additional_data && count($task->customer_visible_additional_data) > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="ti ti-info-circle me-2"></i>{{ __('Additional Information') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($task->customer_visible_additional_data as $field)
                                <div class="col-md-6 mb-3">
                                    <strong>{{ $field['label'] }}:</strong><br>
                                    @if ($field['type'] === 'file' || $field['type'] === 'image')
                                        @if ($field['value'])
                                            <a href="{{ asset($field['value']) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-download me-1"></i>{{ __('Download File') }}
                                            </a>
                                        @else
                                            <span class="text-muted">{{ __('No file uploaded') }}</span>
                                        @endif
                                    @elseif($field['type'] === 'file_expiration_date')
                                        @if ($field['value'])
                                            <a href="{{ asset($field['value']) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-download me-1"></i>{{ __('Download File') }}
                                            </a>
                                            @if (isset($field['expiration']))
                                                <br><small class="text-muted">{{ __('Expires') }}:
                                                    {{ $field['expiration'] }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">{{ __('No file uploaded') }}</span>
                                        @endif
                                    @else
                                        <span>{{ $field['value'] ?? 'N/A' }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Task Status & History -->
        <div class="col-xl-4 col-lg-5 col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="ti ti-history me-2"></i>{{ __('Task History') }}
                    </h5>
                </div>
                <div class="card-body">
                    @if ($task->history && $task->history->count() > 0)
                        <div class="timeline">
                            @foreach ($task->history as $history)
                                <div class="timeline-item">
                                    <div class="timeline-point timeline-point-primary"></div>
                                    <div class="timeline-event">
                                        <div class="timeline-header mb-1">
                                            <h6 class="mb-0">{{ ucfirst(str_replace('_', ' ', $history->action_type)) }}
                                            </h6>
                                            <small
                                                class="text-muted">{{ $history->created_at->format('Y-m-d H:i') }}</small>
                                        </div>
                                        <p class="mb-2">{{ $history->description }}</p>
                                        @if ($history->user)
                                            <small class="text-muted">{{ __('By') }}:
                                                {{ $history->user->name }}</small>
                                        @elseif($history->driver)
                                            <small class="text-muted">{{ __('By Driver') }}:
                                                {{ $history->driver->name }}</small>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">{{ __('No history available') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        function downloadInvoice(taskId) {
            Swal.fire({
                title: '{{ __('Generating Invoice...') }}',
                text: '{{ __('Please wait while we generate your invoice.') }}',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Construct the URL dynamically
            const url = "{{ route('customer.tasks.invoice', ':id') }}".replace(':id', taskId);

            fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            throw new Error(data.error || '{{ __('Failed to generate invoice.') }}');
                        });
                    }
                    return response.blob();
                })
                .then(blob => {
                    // Create a link element, hide it, push it onto the page
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    // Attempt to extract filename from headers or default
                    a.download = `invoice_${taskId}.pdf`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);

                    Swal.close();
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __('Success') }}',
                        text: '{{ __('Invoice downloaded successfully.') }}',
                        timer: 2000,
                        showConfirmButton: false
                    });
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Error') }}',
                        text: error.message
                    });
                });
        }
    </script>
@endsection
