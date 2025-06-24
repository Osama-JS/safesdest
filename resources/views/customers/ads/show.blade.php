@extends('layouts/layoutMaster')

@section('title', __('Ad Details & Offers'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    <script>
        function acceptOffer(adId, offerId, driverName, price) {
            Swal.fire({
                title: '{{ __('Accept Offer?') }}',
                html: `{{ __('Are you sure you want to accept this offer?') }}<br><br>
                       <strong>{{ __('Driver') }}:</strong> ${driverName}<br>
                       <strong>{{ __('Price') }}:</strong> ${price} SAR`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '{{ __('Yes, Accept') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit form to accept offer
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action =
                        `{{ route('customer.ads.accept-offer', ['ad' => $ad->id, 'offer' => '__OFFER_ID__']) }}`
                        .replace('__OFFER_ID__', offerId);

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);

                    const offerInput = document.createElement('input');
                    offerInput.type = 'hidden';
                    offerInput.name = 'offer_id';
                    offerInput.value = offerId;
                    form.appendChild(offerInput);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        function rejectOffer(adId, offerId, driverName) {
            Swal.fire({
                title: '{{ __('Reject Offer?') }}',
                html: `{{ __('Are you sure you want to reject this offer from') }} <strong>${driverName}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('Yes, Reject') }}',
                cancelButtonText: '{{ __('Cancel') }}',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit form to reject offer
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action =
                        `{{ route('customer.ads.reject-offer', ['ad' => $ad->id, 'offer' => '__OFFER_ID__']) }}`
                        .replace('__OFFER_ID__', offerId);

                    const csrfToken = document.createElement('input');
                    csrfToken.type = 'hidden';
                    csrfToken.name = '_token';
                    csrfToken.value = '{{ csrf_token() }}';
                    form.appendChild(csrfToken);

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
@endsection

@section('content')
    <div class="row">
        <!-- Ad & Task Information -->
        <div class="col-xl-8 col-lg-7 col-md-7">
            <!-- Ad Details -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-speakerphone me-2"></i>{{ __('Advertisement Details') }} #{{ $ad->id }}
                    </h5>
                    <div>
                        <span
                            class="badge bg-label-{{ $ad->task->status === 'completed' ? 'success' : ($ad->task->status === 'advertised' ? 'warning' : 'info') }}">
                            {{ ucfirst($ad->task->status) }}
                        </span>
                        @if ($ad->task->closed)
                            <span class="badge bg-label-secondary ms-1">{{ __('Closed') }}</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">{{ __('Task Information') }}</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <span class="h6">{{ __('Task ID') }}:</span>
                                    <span>#{{ $ad->task->id }}</span>
                                </li>
                                <li class="mb-2">
                                    <span class="h6">{{ __('Vehicle Required') }}:</span>
                                    <span>
                                        @if ($ad->task->vehicle_size)
                                            {{ $ad->task->vehicle_size->type->vehicle->name }} -
                                            {{ $ad->task->vehicle_size->type->name }} -
                                            {{ $ad->task->vehicle_size->name }}
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </li>
                                <li class="mb-2">
                                    <span class="h6">{{ __('Created At') }}:</span>
                                    <span>{{ $ad->created_at->format('Y-m-d H:i') }}</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">{{ __('Price Range') }}</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <span class="h6">{{ __('Minimum Price') }}:</span>
                                    <span class="text-success fw-bold">{{ number_format($ad->lowest_price, 2) }} SAR</span>
                                </li>
                                <li class="mb-2">
                                    <span class="h6">{{ __('Maximum Price') }}:</span>
                                    <span class="text-danger fw-bold">{{ number_format($ad->highest_price, 2) }} SAR</span>
                                </li>
                                @if ($ad->description)
                                    <li class="mb-2">
                                        <span class="h6">{{ __('Description') }}:</span>
                                        <span>{{ $ad->description }}</span>
                                    </li>
                                @endif
                            </ul>
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
                                @if ($ad->task->pickup)
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <strong>{{ __('Address') }}:</strong><br>
                                            {{ $ad->task->pickup->address }}
                                        </li>
                                        <li class="mb-2">
                                            <strong>{{ __('Contact') }}:</strong>
                                            {{ $ad->task->pickup->contact_name ?? 'N/A' }}
                                        </li>
                                        @if ($ad->task->pickup->scheduled_time)
                                            <li class="mb-2">
                                                <strong>{{ __('Scheduled Time') }}:</strong>
                                                {{ \Carbon\Carbon::parse($ad->task->pickup->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                    </ul>
                                @endif
                            </div>
                        </div>

                        <!-- Delivery Point -->
                        <div class="col-md-6">
                            <div class="border rounded p-3">
                                <h6 class="text-danger mb-3">
                                    <i class="ti ti-map-pin me-2"></i>{{ __('Delivery Point') }}
                                </h6>
                                @if ($ad->task->delivery)
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <strong>{{ __('Address') }}:</strong><br>
                                            {{ $ad->task->delivery->address }}
                                        </li>
                                        <li class="mb-2">
                                            <strong>{{ __('Contact') }}:</strong>
                                            {{ $ad->task->delivery->contact_name ?? 'N/A' }}
                                        </li>
                                        @if ($ad->task->delivery->scheduled_time)
                                            <li class="mb-2">
                                                <strong>{{ __('Scheduled Time') }}:</strong>
                                                {{ \Carbon\Carbon::parse($ad->task->delivery->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information (Customer Visible Only) -->
            @if ($ad->task->customer_visible_additional_data && count($ad->task->customer_visible_additional_data) > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="ti ti-info-circle me-2"></i>{{ __('Additional Information') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($ad->task->customer_visible_additional_data as $field)
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

        <!-- Driver Offers -->
        <div class="col-xl-4 col-lg-5 col-md-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-users me-2"></i>{{ __('Driver Offers') }}
                    </h5>
                    <span class="badge bg-label-primary">{{ $ad->offers->count() }} {{ __('Offers') }}</span>
                </div>
                <div class="card-body">
                    @if ($ad->offers && $ad->offers->count() > 0)
                        @foreach ($ad->offers as $offer)
                            <div
                                class="card border mb-3
                                @if ($offer->status === 'accepted') border-success
                                @elseif($offer->status === 'rejected') border-danger
                                @else border-warning @endif">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="avatar avatar-sm me-2">
                                            <img src="{{ $offer->driver->image ? asset($offer->driver->image) : asset('assets/img/avatars/default-avatar.png') }}"
                                                alt="Driver Avatar" class="rounded-circle">
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">{{ $offer->driver->name }}</h6>
                                            <small class="text-muted">{{ $offer->driver->phone_code }}
                                                {{ $offer->driver->phone }}</small>
                                        </div>
                                        <span
                                            class="badge bg-label-{{ $offer->status === 'accepted' ? 'success' : ($offer->status === 'rejected' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($offer->status) }}
                                        </span>
                                    </div>

                                    <div class="mb-2">
                                        <strong class="text-primary fs-5">{{ number_format($offer->price, 2) }}
                                            SAR</strong>
                                    </div>

                                    @if ($offer->message)
                                        <div class="mb-2">
                                            <small class="text-muted">{{ __('Message') }}:</small>
                                            <p class="mb-0 small">{{ $offer->message }}</p>
                                        </div>
                                    @endif

                                    <div class="mb-2">
                                        <small class="text-muted">
                                            {{ __('Submitted') }}: {{ $offer->created_at->format('M d, H:i') }}
                                        </small>
                                    </div>

                                    @if ($offer->driver->full_whatsapp_number)
                                        <div class="mb-2">
                                            <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $offer->driver->full_whatsapp_number) }}"
                                                target="_blank" class="btn btn-sm btn-outline-success">
                                                <i class="ti ti-brand-whatsapp me-1"></i>{{ __('WhatsApp') }}
                                            </a>
                                        </div>
                                    @endif

                                    @if ($offer->status === 'pending' && $ad->task->status === 'advertised' && !$ad->task->closed)
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-success btn-sm flex-grow-1"
                                                onclick="acceptOffer({{ $ad->id }}, {{ $offer->id }}, '{{ $offer->driver->name }}', '{{ number_format($offer->price, 2) }}')">
                                                <i class="ti ti-check me-1"></i>{{ __('Accept') }}
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                onclick="rejectOffer({{ $ad->id }}, {{ $offer->id }}, '{{ $offer->driver->name }}')">
                                                <i class="ti ti-x me-1"></i>{{ __('Reject') }}
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="ti ti-inbox display-4 text-muted mb-3"></i>
                            <h6 class="text-muted">{{ __('No Offers Yet') }}</h6>
                            <p class="text-muted">{{ __('Drivers will submit their offers here. Check back later!') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
