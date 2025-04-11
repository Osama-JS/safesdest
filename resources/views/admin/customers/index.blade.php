@extends('layouts/layoutMaster')

@section('title', __('Customers'))

<!-- Vendor Styles -->
@section('vendor-style')

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

    @vite(['resources/css/app.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])

@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/customers.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])
@endsection

@section('content')

    <div class="row g-6 mb-6">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Customers') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total"></h4>
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
                            <span class="text-heading">{{ __('Active Customers') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total-active"></h4>
                                <p class="text-success mb-0">
                                </p>
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
                            <span class="text-heading">{{ __('Unverified Customers') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total-verified"></h4>
                                <p class="text-success mb-0">

                                </p>

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
                            <span class="text-heading">{{ __('Blocked Customers') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total-blocked"></h4>
                                <p class="text-success mb-0">
                                </p>

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
            <h5 class="card-title mb-0">{{ __('Customers') }}</h5>
            <button class="add-new btn btn-primary waves-effect waves-light mt-5 mx-4" data-bs-toggle="modal"
                data-bs-target="#submitModal">
                <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                <span class="d-none d-sm-inline-block"> {{ __('Add New Customer') }}</span>
            </button>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-users table">
                <thead class="border-top">
                    <tr>
                        <th></th>
                        <th>#</th>
                        <th>{{ __('name') }}</th>

                        <th>{{ __('email') }}</th>
                        <th>{{ __('phone') }}</th>
                        <th>{{ __('role') }}</th>
                        <th>{{ __('tags') }}</th>
                        <th>{{ __('status') }}</th>
                        <th>{{ __('created at') }}</th>
                        <th>{{ __('actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>

    <div class="modal fade " id="submitModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ __('Add New Customer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('customers.create') }}"
                    enctype="multipart/form-data">
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
                                                {{ __('Additional ') }}</span></button>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="navs-justified-home" role="tabpanel">
                                        <input type="hidden" name="id" id="customer_id" autocomplete="false">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="mb-6">
                                                    <img src="{{ url(asset('assets/img/person.png')) }}"
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
                                                            <label class="form-label" for="customer-fullname">*
                                                                {{ __('Full Name') }}</label>
                                                            <input type="text" class="form-control"
                                                                id="customer-fullname"
                                                                placeholder="{{ __('Full Name') }}" name="name"
                                                                aria-label="{{ __('Full Name') }}" />
                                                            <span class="name-error text-danger text-error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="customer-email">*
                                                                {{ __('Email') }}</label>
                                                            <input type="text" id="customer-email"
                                                                class="form-control"
                                                                placeholder="{{ __('example@example.com') }}"
                                                                aria-label="{{ __('example@example.com') }}"
                                                                name="email" />
                                                            <span class="email-error text-danger text-error"></span>

                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="customer-phone">*
                                                                {{ __('Phone') }}</label>
                                                            <div class="input-group">
                                                                <select id="country-code" name="phone_code"
                                                                    class="form-select" required
                                                                    style="max-width: 120px;">
                                                                    <option value="+966">🇸🇦 +966</option>
                                                                    <option value="+971">🇦🇪 +971</option>
                                                                    <option value="+20">🇪🇬 +20</option>
                                                                    <option value="+1">🇺🇸 +1</option>
                                                                </select>
                                                                <input type="tel" id="customer-phone"
                                                                    class="form-control"
                                                                    placeholder="{{ __('Enter phone number') }}"
                                                                    name="phone" />
                                                            </div>
                                                            <span class="phone-error text-danger text-error"></span>
                                                            <span
                                                                class="phone_code_code-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="customer-role">
                                                                {{ __('Customer Role') }}</label>
                                                            <select id="customer-role" class="form-select"
                                                                name="role">
                                                                <option value="">-- {{ __('Select Role') }}</option>
                                                                @foreach ($roles as $key)
                                                                    <option value="{{ $key->id }}">
                                                                        {{ $key->name }}</option>
                                                                @endforeach
                                                            </select>
                                                            <span class="role-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="customer-password">*
                                                                {{ __('Password') }}</label>
                                                            <input type="password" id="customer-password"
                                                                class="form-control" name="password" />
                                                            <span class="password-error text-danger text-error"></span>

                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-6">
                                                            <label class="form-label" for="customer-re-password">*
                                                                {{ __('Confirm Password') }}</label>
                                                            <input type="password" id="customer-re-password"
                                                                class="form-control" name="confirm-password" />
                                                            <span
                                                                class="confirm-password-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="divider text-start">
                                                        <div class="divider-text">
                                                            <strong>{{ __('Company Info') }}</strong>
                                                        </div>
                                                    </div>


                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <label class="form-label" for="customer-c_name">
                                                                {{ 'Company Name' }}</label>
                                                            <input type="text" name="c_name" class="form-control"
                                                                id="customer-c_name"
                                                                placeholder="{{ __('enter company name') }}" />
                                                            <span class="c_name-error text-danger text-error"></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-4">
                                                            <label class="form-label" for="customer-c_address">
                                                                {{ 'Company Address' }}</label>
                                                            <input type="text" name="c_address" class="form-control"
                                                                id="customer-c_address"
                                                                placeholder="{{ __('enter company address') }}" />
                                                            <span class="c_address-error text-danger text-error"></span>
                                                        </div>
                                                    </div>


                                                    <div class="col-md-6">
                                                        <div class="  mb-6">
                                                            <label class="form-label"
                                                                for="customer-tags">{{ __('Tags') }}</label>
                                                            <select name="tags[]" id="customer-tags"
                                                                class="select2 form-select" multiple>
                                                                <option value=""></option>
                                                                @foreach ($tags as $key)
                                                                    <option value="{{ $key->id }}">
                                                                        {{ $key->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <span class="tags-error text-danger text-error"></span>
                                                        </div>
                                                    </div>


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
@endsection
