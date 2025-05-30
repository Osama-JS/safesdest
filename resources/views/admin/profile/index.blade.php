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
                            src="{{ $user->image ? asset($user->image) : asset('assets/img/person.png') }}" alt="User Avatar"
                            style="width: 180px;">
                        <h5>{{ $user->name }}</h5>
                        <span class="badge bg-label-info">{{ $user->role->name ?? 'No Role' }}</span>
                    </div>

                    <hr class="my-4" />

                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="ti ti-phone-call me-2"></i><strong>Phone:</strong>
                            {{ $user->phone_code }} {{ $user->phone }}</li>
                        <li class="mb-2"><i class="ti ti-mail me-2"></i><strong>Email:</strong> {{ $user->email }}</li>
                        <li class="mb-2"><i class="ti ti-circle-check me-2"></i><strong>Status:</strong> <span
                                class="badge bg-{{ $user->status == 'active' ? 'success' : 'secondary' }}">{{ $user->status }}</span>
                        </li>
                        <li class="mb-2"><i class="ti ti-login me-2"></i><strong>Last Login:</strong>
                            {{ $user->last_login ? $user->last_login->format('Y-m-d H:i') : 'Not available' }}</li>
                    </ul>


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
                                @if ($user->additional_data && count($user->additional_data))
                                    @foreach ($user->additional_data as $field)
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
                            <form class="form_submit" action="{{ route('user.profile.update') }}" method="POST">
                                @csrf


                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">* Name</label>
                                        <input type="text" name="name" value="{{ $user->name }}"
                                            class="form-control" required />
                                        <span class="name-error text-danger text-error"></span>

                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">* email</label>
                                        <input type="email" name="email" value="{{ $user->email }}"
                                            class="form-control" required />
                                        <span class="email-error text-danger text-error"></span>

                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="user-phone">*
                                            {{ __('Phone') }}</label>
                                        <div class="input-group">
                                            <select id="phone-code" name="phone_code" class="form-select" required
                                                style="max-width: 120px;">
                                                <option value="+966" {{ $user->phone == '+966' ? 'selected' : '' }}>🇸🇦
                                                    +966</option>
                                                <option value="+971" {{ $user->phone == '+971' ? 'selected' : '' }}>🇦🇪
                                                    +971</option>
                                                <option value="+20" {{ $user->phone == '+20' ? 'selected' : '' }}>🇪🇬
                                                    +20</option>
                                                <option value="+1" {{ $user->phone == '+1' ? 'selected' : '' }}>🇺🇸 +1
                                                </option>
                                            </select>
                                            <input type="tel" id="user-phone" class="form-control"
                                                placeholder="{{ __('Enter phone number') }}" name="phone"
                                                value="{{ $user->phone }}" />
                                        </div>
                                        <span
                                            class="phone-error
                                                text-danger text-error"></span>
                                        <span class="phone_code-error text-danger text-error"></span>

                                    </div>
                                    <div class="col-md-6"></div>
                                    <div class="col-md-6">
                                        <label class="form-label">* Password (New)</label>
                                        <input type="password" name="password" class="form-control" />
                                        <span class="password-error text-danger text-error"></span>

                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">* Confirm Password (New)</label>
                                        <input type="password" name="confirm-password" class="form-control" />
                                        <span class="confirm-password-error text-danger text-error"></span>

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
