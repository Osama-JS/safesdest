@extends('layouts/layoutMaster')

@section('title', __('Users'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss'])
    <style>
        .permissions-container {
            max-height: 400px;
            overflow-y: auto;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .permissions-container .form-check {
            margin-bottom: 5px;
            background: white;
            border-radius: 4px;
        }

        .nav-pills .nav-link {
            border-radius: 0.375rem;
            transition: background-color 0.3s ease-in-out;
        }

        .nav-pills .nav-link.active {
            background-color: #007bff;
            color: white;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/block-ui/block-ui.js'])
@endsection

@section('page-script')
    @vite(['resources/js/admin/roles.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
@endsection

@section('content')

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">{{ __('Roles & Permissions') }}</h5>
            <p>{{ __('Add new roles with customized permissions as per your requirement') }}. </p>
            <button class="add-new btn btn-primary waves-effect waves-light mb-5 mx-4" data-bs-toggle="modal"
                data-bs-target="#largeModal">
                <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                <span class="d-none d-sm-inline-block"> {{ __('Add New Role') }}</span>
            </button>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-users table">
                <thead class="border-top">
                    <tr>
                        <th></th>
                        <th>#</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('Created At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>

    <div class="modal fade " id="largeModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ __('add new role') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('role.create') }}">
                    <div class="modal-body">

                        <input type="hidden" name="id" id="role_id">
                        <span class="id-error text-danger text-error"></span>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-6">
                                    <label class="form-label" for="role-name">* {{ __('role') }}</label>
                                    <input type="text" name="name" class="form-control" id="role-name"
                                        placeholder="{{ __('role name') }}" aria-label="{{ __('role name') }}" />
                                    <span class="name-error text-danger text-error"></span>

                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-6">
                                    <label class="form-label" for="role-guard">* {{ __('guard') }}</label>
                                    <select name="guard" id="role-guard" class="form-select ">
                                        <option value="web">{{ __('Administrator') }}</option>
                                        <option value="driver">{{ __('Driver') }}</option>
                                        <option value="customer">{{ __('Customer') }}</option>
                                    </select>
                                    <span class="guard-error text-danger text-error"></span>

                                </div>
                            </div>

                            <div class="col-xl-12">
                                <h6 class="text-muted">* {{ __('permissions') }}</h6>
                                <span class="permissions-error text-danger text-error"></span>
                                <div class="nav-align-left mb-6">
                                    <ul class="nav nav-pills me-4" role="tablist" id="permissions_types">
                                        <li class="nav-item">
                                            <button type="button" class="nav-link active" role="tab"
                                                data-bs-toggle="tab" data-bs-target="#navs-pills-left-home"
                                                aria-controls="navs-pills-left-home"
                                                aria-selected="true">{{ __('Home') }}</button>
                                        </li>
                                        <li class="nav-item">
                                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                                data-bs-target="#navs-pills-left-profile"
                                                aria-controls="navs-pills-left-profile"
                                                aria-selected="false">{{ __('Profile') }}</button>
                                        </li>
                                        <li class="nav-item">
                                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                                                data-bs-target="#navs-pills-left-messages"
                                                aria-controls="navs-pills-left-messages"
                                                aria-selected="false">{{ __('Messages') }}</button>
                                        </li>
                                    </ul>

                                    <div class="tab-content permissions-container" id="permissions_container">
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
