@extends('layouts/layoutMaster')

@section('title', 'Drives - Details')

@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

@endsection

@section('page-style')
    <!-- Page -->
    @vite(['resources/assets/vendor/scss/pages/cards-advance.scss'])

@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    <script>
        const customerID = {{ $data->id }};
    </script>
@endsection


@section('page-script')
    @vite(['resources/assets/js/app-user-view.js', 'resources/assets/js/app-user-view-account.js', 'resources/assets/js/pages-profile.js'])
    @vite(['resources/js/admin/drivers/show.js'])

@endsection
@section('drivers.isactive')
    active
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
                                src="{{ $data->image ? asset($data->image) : asset('assets/img/person.png') }}"
                                style="width: 200px;" alt="User avatar" />
                            <div class="user-info text-center">
                                <h5>{{ $data->name }}</h5>
                                <span class="badge bg-label-secondary">{{ $data->team->name ?? '' }}</span>
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
                                <h5 class="mb-0">{{ $data->tasks()->where('status', 'completed')->count() }}</h5>
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
                                    {{ $data->tasks()->where('status', '!=', 'completed')->where('status', '!=', 'canceled')->count() }}
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
                                <span>{{ $data->username }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('Phone') }}:</span>
                                <span>{{ $data->phone_code }} {{ $data->phone }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">
                                    <i class="ti ti-brand-whatsapp text-success me-1"></i>{{ __('WhatsApp') }}:
                                </span>
                                <span>
                                    @if ($data->full_whatsapp_number)
                                        <a href="https://wa.me/{{ str_replace(['+', ' ', '-'], '', $data->full_whatsapp_number) }}"
                                            target="_blank" class="text-success text-decoration-none">
                                            {{ $data->whatsapp_display }}
                                            <i class="ti ti-external-link ms-1"></i>
                                        </a>
                                        @if ($data->phone_is_whatsapp)
                                            <small class="text-muted d-block">
                                                <i class="ti ti-info-circle me-1"></i>Same as phone number
                                            </small>
                                        @endif
                                    @else
                                        <span class="text-muted">
                                            <i class="ti ti-minus me-1"></i>Not provided
                                        </span>
                                    @endif
                                </span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('Email') }}:</span>
                                <span>{{ $data->email }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('address') }}:</span>
                                <span>{{ $data->address }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('Status') }}:</span>
                                <span>{{ $data->status }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('Role') }}:</span>
                                <span>{{ $data->role }}</span>
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

            <div class="nav-align-top mb-6">
                <ul class="nav nav-pills mb-4" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-pills-top-home" aria-controls="navs-pills-top-home" aria-selected="true">
                            <i class="ti ti-truck-delivery ti-sm me-1_5"></i>{{ __('Tasks') }}</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-pills-top-profile" aria-controls="navs-pills-top-profile"
                            aria-selected="false"><i class="ti ti-details ti-sm me-1_5"></i>{{ __('Details') }}</button>
                    </li>

                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="navs-pills-top-home" role="tabpanel">
                        <div class="card-datatable table-responsive">
                            <table class="table datatables-users-tasks table-striped">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>#</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Issued Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="navs-pills-top-profile" role="tabpanel">
                        <h3 class="text-xl font-semibold mb-4">{{ __('More Details') }}</h3>
                        <div class="row g-4">
                            @if ($data->additional_data != null && count($data->additional_data) > 0)

                                @foreach ($data->additional_data as $key => $field)
                                    <div class="col-12 col-md-6">
                                        <div class="card h-100 shadow-sm">
                                            <div class="card-body">
                                                <h6 class="card-title text-muted">{{ $field['label'] }}</h6>

                                                @switch($field['type'])
                                                    @case('text')
                                                    @case('string')

                                                    @case('number')
                                                        <p class="card-text">{{ $field['value'] }}</p>
                                                    @break

                                                    @case('image')
                                                        <img src="{{ asset('storage/' . $field['value']) }}"
                                                            alt="{{ $field['label'] }}" class="img-fluid rounded border"
                                                            style="max-height: 250px; object-fit: cover;">
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
                                                            class="d-flex align-items-center text-decoration-none">
                                                            <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                            <span class="text-truncate">{{ basename($field['value']) }}</span>
                                                        </a>
                                                    @break

                                                    @case('file_with_text')
                                                        @if ($field['value'])
                                                            <div class="mb-2">
                                                                <a href="{{ asset('storage/' . $field['value']) }}"
                                                                    target="_blank" class="text-decoration-none">
                                                                    <i class="ti ti-file me-1"></i>{{ basename($field['value']) }}
                                                                </a>
                                                            </div>
                                                        @endif
                                                        @if (isset($field['text']) && $field['text'])
                                                            <div class="mt-2">
                                                                <small class="text-muted">Info:</small>
                                                                <p class="card-text fw-medium">{{ $field['text'] }}</p>
                                                            </div>
                                                        @endif
                                                    @break

                                                    @case('file_expiration_date')
                                                        @if ($field['value'])
                                                            <div class="mb-2">
                                                                <a href="{{ asset('storage/' . $field['value']) }}"
                                                                    target="_blank" class="text-decoration-none">
                                                                    <i class="ti ti-file me-1"></i>{{ basename($field['value']) }}
                                                                </a>
                                                            </div>
                                                        @endif
                                                        @if (isset($field['expiration']) && $field['expiration'])
                                                            <div class="mt-2">
                                                                <small class="text-muted">Expires:</small>
                                                                <p class="card-text fw-medium">
                                                                    {{ \Carbon\Carbon::parse($field['expiration'])->format('Y-m-d') }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                    @break

                                                    @default
                                                        <p class="card-text">{{ $field['value'] }}</p>
                                                @endswitch
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-12">
                                    <div class="alert alert-info" role="alert">
                                        {{ __('No additional data found for this Driver') }}
                                    </div>
                                </div>
                            @endif
                        </div>



                    </div>

                </div>
            </div>

        </div>
        <!--/ User Content -->
    </div>

    <!-- Modal -->
    @include('_partials/_modals/modal-edit-user')
    @include('_partials/_modals/modal-upgrade-plan')
    <!-- /Modal -->
@endsection
