@extends('layouts/layoutMaster')

@section('title', __('Users'))

<!-- Vendor Styles -->
@section('vendor-style')

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])

@endsection

<!-- Vendor Scripts -->
@section('vendor-script')

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
    <script>
        const templateId = {{ $user_template->value ?? 0 }}
    </script>
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/users.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
@endsection

@section('content')

    <div class="row g-6 mb-6">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Users') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total">0</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-user ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Active Users') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total-active">0</h4>
                                <p class="text-success mb-0">(0.0%)</p>
                            </div>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Inactive Users') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total-inactive">0</h4>
                                <p class="text-success mb-0">(0.0%)</p>

                                </p>
                            </div>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-users ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Pending Users') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total-pending">0</h4>
                                <p class="text-success mb-0">(0.0%)</p>

                                </p>
                            </div>

                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-user-search ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Users List Table -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">{{ __('Users') }}</h5>
            @can('save_admins')
                <button class="add-new btn btn-primary waves-effect waves-light mt-5 mx-4" data-bs-toggle="modal"
                    data-bs-target="#submitModal">
                    <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                    <span class="d-none d-sm-inline-block"> {{ __('Add New User') }}</span>
                </button>
            @endcan

        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-users table">
                <thead class="border-top">
                    <tr>
                        <th></th>
                        <th>{{ __('#') }}</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Reset Password') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>

    @can('save_admins')
        <div class="modal fade " id="submitModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modelTitle">{{ __('Add new User') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="{{ __('Close') }}"></button>
                    </div>
                    <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('user.create') }}">
                        <div class="modal-body">
                            <div class="col-xl-12">

                                <div class="nav-align-top  mb-6">
                                    <ul class="nav nav-tabs " role="tablist">
                                        <li class="nav-item">
                                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                                                data-bs-target="#navs-justified-home" aria-controls="navs-justified-home"
                                                aria-selected="true"><span class="d-none d-sm-block"><i
                                                        class="tf-icons ti ti-grid-dots ti-sm me-1_5"></i> {{ __('Main') }}
                                            </button>
                                        </li>
                                        <li class="nav-item">
                                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                                data-bs-target="#navs-justified-profile"
                                                aria-controls="navs-justified-profile" aria-selected="false"><span
                                                    class="d-none d-sm-block"><i
                                                        class="tf-icons ti ti-file-plus ti-sm me-1_5"></i>
                                                    {{ __('Additional') }}</span></button>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="navs-justified-home" role="tabpanel">
                                            <input type="hidden" name="id" id="user_id">
                                            <div class="mb-6">
                                                <label class="form-label" for="add-user-fullname">*
                                                    {{ __('Full Name') }}</label>
                                                <input type="text" class="form-control" id="user-fullname"
                                                    placeholder="{{ __('Full Name') }}" name="name"
                                                    aria-label="{{ __('Full Name') }}" />
                                                <span class="name-error text-danger text-error"></span>

                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-6">
                                                        <label class="form-label" for="add-user-email">*
                                                            {{ __('Email') }}</label>
                                                        <input type="text" id="user-email" class="form-control"
                                                            placeholder="{{ __('example@example.com') }}"
                                                            aria-label="{{ __('example@example.com') }}" name="email" />
                                                        <span class="email-error text-danger text-error"></span>

                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-6">
                                                        <label class="form-label" for="user-phone">*
                                                            {{ __('Phone') }}</label>
                                                        <div class="input-group">
                                                            <select id="phone-code" name="phone_code" class="form-select"
                                                                required style="max-width: 120px;">
                                                                <option value="+966">🇸🇦 +966</option>
                                                                <option value="+971">🇦🇪 +971</option>
                                                                <option value="+20">🇪🇬 +20</option>
                                                                <option value="+1">🇺🇸 +1</option>
                                                            </select>
                                                            <input type="tel" id="user-phone" class="form-control"
                                                                placeholder="{{ __('Enter phone number') }}"
                                                                name="phone" />
                                                        </div>
                                                        <span class="phone-error text-danger text-error"></span>
                                                        <span class="phone_code-error text-danger text-error"></span>

                                                    </div>



                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-6">
                                                        <label class="form-label" for="add-user-password">*
                                                            {{ __('Password') }}</label>
                                                        <input type="password" id="add-user-password" class="form-control"
                                                            name="password" />
                                                        <span class="password-error text-danger text-error"></span>

                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-6">
                                                        <label class="form-label" for="add-user-password">*
                                                            {{ __('Confirm Password') }}</label>
                                                        <input type="password" id="add-user-password" class="form-control"
                                                            name="confirm-password" />
                                                        <span class="confirm-password-error text-danger text-error"></span>

                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-6">
                                                        <label class="form-label" for="user-role">*
                                                            {{ __('User Role') }}</label>
                                                        <select id="user-role" class="form-select" name="role">
                                                            @foreach ($roles as $key)
                                                                <option value="{{ $key->id }}">{{ $key->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <span class="role-error text-danger text-error"></span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="  mb-6">
                                                        <label class="form-label"
                                                            for="user-teams">{{ __('Teams') }}</label>
                                                        <select name="teams[]" id="user-teams"
                                                            class="select-teams form-select" multiple>
                                                            <option value=""></option>
                                                            @foreach ($teams as $key)
                                                                <option value="{{ $key->id }}">{{ $key->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <span class="teams-error text-danger text-error"></span>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="  mb-6">
                                                        <label class="form-label"
                                                            for="user-customers">{{ __('Customers') }}</label>
                                                        <select name="customers[]" id="user-customers"
                                                            class="select-customers form-select" multiple>
                                                            <option value=""></option>
                                                            @foreach ($customers as $key)
                                                                <option value="{{ $key->id }}">{{ $key->name }}
                                                                    ({{ $key->users->count() }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <span class="customers-error text-danger text-error"></span>
                                                    </div>
                                                </div>
                                            </div>


                                        </div>
                                        <div class="tab-pane fade" id="navs-justified-profile" role="tabpanel">
                                            <div class="form-group">
                                                <label for="select-template">{{ __('Select Template') }}</label>
                                                <select name="template" id="select-template" class="form-select w-auto">
                                                    <option value="">{{ __('-- Select Template') }}</option>
                                                    @foreach ($templates as $key)
                                                        <option value="{{ $key->id }}">{{ $key->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div id="additional-form" class="row mt-4">

                                            </div>
                                        </div>

                                    </div>
                                </div>
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
    @endcan

@endsection
