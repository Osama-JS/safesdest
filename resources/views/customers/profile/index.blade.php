@extends('layouts.layoutMaster')

@section('title', 'Profile')

<!-- Vendor Styles -->
@section('vendor-style')

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])

@endsection

<!-- Vendor Scripts -->
@section('vendor-script')

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])

@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])
    @vite(['resources/js/customers/profile.js'])

@endsection
@section('dashboard-isactive', 'active')
@section('content')
    <div class="row">
        <!-- Sidebar -->
        <div class="col-xl-4 col-lg-5 order-1 order-md-0">
            <div class="card mb-4">
                <div class="card-body pt-4">
                    <div class="text-center mt-5">
                        <img class="img-fluid rounded mb-3"
                            src="{{ $data->image ? asset($data->image) : asset('assets/img/person.png') }}" alt="User Avatar"
                            style="width: 180px;">
                        <h5>{{ $data->name }}</h5>
                        <span class="badge bg-label-secondary">{{ $data->company_name }}</span>
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

                    <hr class="my-4" />

                    <ul class="list-unstyled">

                        <li class="mb-2"><i class="ti ti-phone-call me-2"></i><strong>Phone:</strong>
                            {{ $data->phone_code }} {{ $data->phone }}</li>
                        <li class="mb-2"><i class="ti ti-mail me-2"></i><strong>Email:</strong> {{ $data->email }}</li>
                        <li class="mb-2"><i class="ti ti-circle-check me-2"></i><strong>Status:</strong> <span
                                class="badge bg-{{ $data->status == 'active' ? 'success' : 'secondary' }}">{{ $data->status }}</span>
                        </li>
                        <li class="mb-2"><i
                                class="ti ti-location me-2"></i><strong>Address:</strong>{{ $data->company_address }}
                        </li>

                    </ul>

                    <button class="brn btn-danger form-control" id="delete_account_btn"
                        data-id="{{ $data->id }}">{{ __('Delete Your Account') }}</button>



                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-xl-8 col-lg-7 order-0 order-md-1">
            <div class="nav-align-top mb-4">
                <ul class="nav nav-pills mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profileOverview"
                            type="button"><i class="ti ti-details ti-sm me-1_5"></i>More Information</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profileEdit" type="button"><i
                                class="ti ti-edit ti-sm me-1_5"></i>Edit
                            Profile</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Overview Tab -->
                    <div class="tab-pane fade show active" id="profileOverview">

                        <div class="card-body">
                            <h5 class="mb-3">More Information</h5>
                            <div class="row">
                                @if ($data->additional_data && count($data->additional_data))
                                    @foreach ($data->additional_data as $field)
                                        <div class="col-md-6 mb-3">
                                            <div class="card shadow-sm">
                                                <div class="card-body">
                                                    <small class="text-muted">{{ $field['label'] }}</small>
                                                    <div class="mt-2">
                                                        @if ($field['type'] === 'image')
                                                            <img src="{{ asset('storage/' . $field['value']) }}"
                                                                class="img-fluid rounded" />
                                                        @elseif($field['type'] === 'file')
                                                            <a href="{{ asset('storage/' . $field['value']) }}"
                                                                target="_blank">{{ basename($field['value']) }}</a>
                                                        @elseif($field['type'] === 'file_with_text')
                                                            @if ($field['value'])
                                                                <div class="mb-2">
                                                                    <a href="{{ asset('storage/' . $field['value']) }}"
                                                                        target="_blank">
                                                                        {{ basename($field['value']) }}
                                                                    </a>
                                                                </div>
                                                            @endif
                                                            @if (isset($field['text']) && $field['text'])
                                                                <div class="mt-1">
                                                                    <small class="text-muted">Info:</small>
                                                                    <p class="mb-0">{{ $field['text'] }}</p>
                                                                </div>
                                                            @endif
                                                        @elseif($field['type'] === 'file_expiration_date')
                                                            @if ($field['value'])
                                                                <div class="mb-2">
                                                                    <a href="{{ asset('storage/' . $field['value']) }}"
                                                                        target="_blank">
                                                                        {{ basename($field['value']) }}
                                                                    </a>
                                                                </div>
                                                            @endif
                                                            @if (isset($field['expiration']) && $field['expiration'])
                                                                <div class="mt-1">
                                                                    <small class="text-muted">Expires:</small>
                                                                    <p class="mb-0">
                                                                        {{ \Carbon\Carbon::parse($field['expiration'])->format('Y-m-d') }}
                                                                    </p>
                                                                </div>
                                                            @endif
                                                        @else
                                                            <p>{{ $field['value'] }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="col-12">
                                        <div class="alert alert-info">There is no additional data</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>

                    <!-- Edit Tab -->
                    <div class="tab-pane fade" id="profileEdit">

                        <div class="card-body">
                            <form class="form_submit" action="{{ route('customer.profile.update') }}" method="POST">
                                @csrf


                                <div class="col-xl-12">
                                    <div class="nav-align-top  mb-6">

                                        <input type="hidden" name="id" id="driver_id">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-6">
                                                    <img src="{{ $data->image ? asset($data->image) : asset('assets/img/person.png') }}"
                                                        data-image="{{ url(asset('assets/img/person.png')) }}"
                                                        alt="" id="image"
                                                        style="width: 100%;    height: 222px;
                                                        object-fit: cover;"
                                                        class="rounded preview-image image-input">

                                                    <input type="file" class="form-control file-input-image"
                                                        id="driver-image" name="image" style="display: none" />
                                                    <span class="image-error text-danger text-error"></span>

                                                </div>
                                            </div>
                                            <div class="col-md-9">

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-fullname">*
                                                                {{ __('Full Name') }}</label>
                                                            <input type="text" class="form-control"
                                                                id="driver-fullname" value="{{ $data->name }}"
                                                                placeholder="{{ __('Full Name') }}" name="name"
                                                                aria-label="{{ __('Full Name') }}" />
                                                            <span class="name-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-phone">*
                                                                {{ __('Phone') }}</label>
                                                            <div class="input-group">
                                                                <select id="phone-code" name="phone_code"
                                                                    class="form-select" required
                                                                    style="max-width: 120px;">
                                                                    <option value="+966"
                                                                        {{ $data->phone == '+966' ? 'selected' : '' }}>🇸🇦
                                                                        +966</option>
                                                                    <option value="+971"
                                                                        {{ $data->phone == '+971' ? 'selected' : '' }}>🇦🇪
                                                                        +971</option>
                                                                    <option value="+20"
                                                                        {{ $data->phone == '+20' ? 'selected' : '' }}>🇪🇬
                                                                        +20</option>
                                                                    <option value="+1"
                                                                        {{ $data->phone == '+1' ? 'selected' : '' }}>🇺🇸
                                                                        +1
                                                                    </option>
                                                                </select>
                                                                <input type="tel" id="user-phone"
                                                                    class="form-control"
                                                                    placeholder="{{ __('Enter phone number') }}"
                                                                    name="phone" value="{{ $data->phone }}" />
                                                            </div>
                                                            <span class="phone-error text-danger text-error"></span>
                                                            <span
                                                                class="phone_code_code-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <label class="form-label" for="customer-c_name">
                                                                {{ __('Company Name') }}</label>
                                                            <input type="text" name="c_name" class="form-control"
                                                                id="customer-c_name" value="{{ $data->company_name }}"
                                                                placeholder="{{ __('enter company name') }}" />
                                                            <span class="c_name-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <label class="form-label" for="customer-c_address">
                                                                {{ __('Company Address') }}</label>
                                                            <input type="text" name="c_address"
                                                                value="{{ $data->company_address }}" class="form-control"
                                                                id="customer-c_address"
                                                                placeholder="{{ __('enter company address') }}" />
                                                            <span class="c_address-error text-danger text-error"></span>
                                                        </div>
                                                    </div>


                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-password">*
                                                                {{ __('Password') }} (New)</label>
                                                            <input type="password" id="driver-password"
                                                                class="form-control" name="password" />
                                                            <span class="password-error text-danger text-error"></span>

                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="driver-re-password">*
                                                                {{ __('Confirm Password') }} (New)</label>
                                                            <input type="password" id="driver-re-password"
                                                                class="form-control" name="confirm-password" />
                                                            <span
                                                                class="confirm-password-error text-danger text-error"></span>
                                                        </div>
                                                    </div>





                                                </div>
                                            </div>
                                        </div>


                                    </div>
                                </div>

                                <div class="mt-4 text-end">
                                    <button class="btn btn-success">save updates</button>
                                </div>
                            </form>
                        </div>

                    </div>
                    <!-- End Edit -->
                </div>
            </div>
        </div>
    </div>
@endsection
