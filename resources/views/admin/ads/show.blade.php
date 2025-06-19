@extends('layouts/layoutMaster')

@section('title', __('Tasks Ads'))

<!-- Vendor Styles -->
@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css" />
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    /* تنسيق البطاقة */


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

<!-- Vendor Scripts -->
@section('vendor-script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    <script>
        const adId = {{ $ad->id }}
    </script>
    @vite(['resources/js/admin/offers.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
@endsection
@section('ad-isactive', 'active')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <!-- الجانب الأيسر: بيانات الإعلان والـ Task -->
            <div class="col-lg-7 col-md-12">
                <!-- تفاصيل الإعلان -->
                <div class="card mb-4 shadow">
                    <div class="card-header border-bottom mb-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bx bx-bullhorn me-1"></i> {{ __('Ad Details') }}
                        </h5>
                        <a href="{{ route('driver.task.list') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-arrow-back"></i> {{ __('Back to Tasks') }}
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- معلومات المالك -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <strong class="text-muted">{{ __('Owner') }}:</strong><br>
                                <span>{{ $task->owner == 'admin' ? $task->user->name : $task->customer->name }}</span>
                            </div>
                            <div class="col-md-6">
                                <strong class="text-muted">{{ __('Phone') }}:</strong><br>
                                <span>{{ $task->owner == 'admin' ? $task->user->phone : $task->customer->phone }}</span>
                            </div>
                        </div>

                        <!-- معلومات الأسعار والحالة -->
                        <div class="border rounded p-3 d-flex flex-wrap gap-4 justify-content-between mb-4 shadow-sm">
                            @if ($ad->highest_price)
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fas fa-arrow-up text-danger fs-4"></i>
                                    <div>
                                        <small class="text-muted">{{ __('Highest price') }}</small>
                                        <div class="fw-bold text-danger">{{ number_format($ad->highest_price, 2) }} SAR
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($ad->lowest_price)
                                <div class="d-flex align-items-center gap-3">
                                    <i class="fas fa-arrow-down text-success fs-4"></i>
                                    <div>
                                        <small class="text-muted">{{ __('Lowest price') }}</small>
                                        <div class="fw-bold text-success">{{ number_format($ad->lowest_price, 2) }} SAR
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="d-flex align-items-center gap-3">
                                <i class="fas fa-info-circle text-primary fs-4"></i>
                                <div>
                                    <small class="text-muted">{{ __('Status') }}</small><br>
                                    <span class="badge bg-primary">{{ $ad->status }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- وصف الإعلان -->
                        <div>
                            <h6 class="text-muted">{{ __('Notes') }}</h6>
                            <p class="mb-0">{{ $ad->description }}</p>
                        </div>
                    </div>
                </div>

                <!-- نقطة الاستلام والتسليم -->
                <div class="card mb-4">
                    <div class="card-header border-bottom ">
                        <h6 class="mb-0">{{ __('Pickup & Delivery Points') }}</h6>
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
                                        <li><strong>Notes:</strong> {{ optional($task->pickup)->note }}</li>
                                        @if (optional($task->pickup)->scheduled_time)
                                            <li><strong>Pickup Before:</strong>
                                                {{ \Carbon\Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                    </ul>
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
                                        <li><strong>Notes:</strong> {{ optional($task->delivery)->note }}</li>
                                        @if (optional($task->delivery)->scheduled_time)
                                            <li><strong>Delivery Before:</strong>
                                                {{ \Carbon\Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- بيانات إضافية -->
                @if ($task->additional_data)
                    <div class="card mb-4">
                        <div class="card-header border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-layer-group me-2 text-primary"></i> {{ __('Additional Data') }}
                            </h5>
                        </div>
                        <div class="card-body mt-3">
                            @if (is_array($task->additional_data) && count($task->additional_data) > 0)
                                <div class="row">
                                    @foreach ($task->additional_data as $key => $field)
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

            <!-- الجانب الأيمن: العروض -->
            <div class="col-md-5">
                <div class="card mb-5" style="min-height: 80vh">
                    <div class="card-header border-bottom  mb-4 d-flex justify-content-between">
                        <strong>{{ __('Submitted Offers') }} (<span id="total-offers-counter">0</span>)</strong>
                    </div>
                    <div class="card-body" id="offers-container">
                        <div class="text-center text-muted">جارٍ تحميل العروض...</div>
                    </div>
                </div>


            </div>
        </div>
    </div>

    <div class="modal fade " id="offerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ $offer ? __('Update your offer') : __('Add your offer') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('Close') }}"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('driver.offers.store') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" value="{{ $offer ? $offer->id : '' }}">
                        <input type="hidden" name="ad" value="{{ $ad->id }}">
                        <span class="ad-error text-danger text-error"></span>

                        <div class="mb-2">
                            <label class="form-label" for="price">* {{ __('Your Price') }}</label>

                            <input type="number" step="any" name="price"
                                value="{{ $offer ? $offer->price : '' }}" min="0.00" id="offer-price"
                                class="form-control" placeholder="{{ __('Offer Price') }}">
                            <span class="price-error text-danger text-error"></span>

                        </div>
                        <div class="mb-2">
                            <label class="form-label" for="description">* {{ __('Notes') }}</label>

                            <textarea name="description" id="description" class="form-control"
                                placeholder="{{ __('Write your Notes or Description') }}" rows="2">{{ $offer ? $offer->description : '' }}</textarea>
                            <span class="description-error text-danger text-error"></span>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">{{ __('Submit') }}</button>

                    </div>
                </form>

            </div>
        </div>
    </div>


@endsection
