@extends('layouts/layoutMaster')

@section('title', __('General Settings'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
    @vite(['resources/js/admin/settings.js'])

@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header border-bottom">
            <h5 class="card-title ">{{ __('Settings') }} | {{ __('General Settings') }}</h5>
            <p>{{ __('You can manage the main and vital settings of the platform from here, so be careful.') }}</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong>{{ __('Templates') }}</strong>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-9">
                        <label for="customer-template" class="mb-2">{{ __('Default Customer Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="customer_template">
                            @if (empty($settings['customer_template']['value']) || empty($templates))
                                <option value="">--- {{ __('Select Template') }}</option>
                            @endif
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ $settings['customer_template']['value'] == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                            @if (!empty($settings['customer_template']['value']))
                                <option value="">--- {{ __('Select Template') }}</option>
                            @endif
                        </select>
                        <span class="customer-error text-danger"></span>
                    </div>
                    <div class="form-group mb-9">
                        <label for="driver-template" class="mb-2">{{ __('Default Driver Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="driver_template" id="driver-template">
                            @if (empty($settings['driver_template']['value']) || empty($templates))
                                <option value="">--- {{ __('Select Template') }}</option>
                            @endif
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ $settings['driver_template']['value'] == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                            @if (!empty($settings['customer_template']['value']))
                                <option value="">--- {{ __('Select Template') }}</option>
                            @endif
                        </select>
                        <span class="driver-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="user-template" class="mb-2">{{ __('Default User Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="user_template" id="user-template">
                            @if (empty($settings['user_template']['value']) || empty($templates))
                                <option value="">--- {{ __('Select Template') }}</option>
                            @endif
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ $settings['user_template']['value'] == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                            @if (!empty($settings['customer_template']['value']))
                                <option value="">--- {{ __('Select Template') }}</option>
                            @endif
                        </select>
                        <span class="user-error text-danger"></span>
                    </div>
                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2">{{ __('Default Task Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="task_template" id="task-template">
                            @if (empty($settings['task_template']['value']) || empty($templates))
                                <option value="">--- {{ __('Select Template') }}</option>
                            @endif
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ $settings['task_template']['value'] == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong>{{ __('Drivers Commission') }}</strong>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-9">
                        <label for="commission-type" class="mb-2">{{ __('Commission Type') }}</label>
                        <select class="form-select  update-setting-select" data-key="commission_type">

                            <option value="rate" {{ $settings['commission_type']['value'] == 'rate' ? 'selected' : '' }}>
                                {{ __('Rate') }}</option>
                            <option value="fixed"
                                {{ $settings['commission_type']['value'] == 'fixed' ? 'selected' : '' }}>
                                {{ __('Fixed') }}</option>
                        </select>
                        <span class="commission_type-error text-danger"></span>
                    </div>
                    <div class="form-group mb-9">
                        <label for="commission_rate" class="mb-2">{{ __('Commission Rate') }}</label>
                        <input type="number" data-key="commission_rate" max="100" min="0" step="any"
                            value={{ $settings['commission_rate']['value'] }} class="form-control update-setting-input">
                        <span class="commission_rate-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="commission_fixed" class="mb-2">{{ __('Commission fixed Amount') }}</label>
                        <input type="number" data-key="commission_fixed" min="0" step="any"
                            value={{ $settings['commission_fixed']['value'] }} class="form-control update-setting-input">
                        <span class="commission_fixed-error text-danger"></span>
                    </div>



                </div>
            </div>
        </div>
    </div>





@endsection
