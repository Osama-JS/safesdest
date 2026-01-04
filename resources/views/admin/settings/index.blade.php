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
            <h5 class="card-title ">
                <i class="tf-icons ti ti-adjustments me-2 fs-3 text-white bg-primary rounded p-1"></i>

                {{ __('Settings') }} | {{ __('General Settings') }}
            </h5>
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
                                <option value="">{{ __('--- Select Template') }}</option>
                            @endif
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ $settings['customer_template']['value'] == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                            @if (!empty($settings['customer_template']['value']))
                                <option value="">{{ __('--- Select Template') }}</option>
                            @endif
                        </select>
                        <span class="customer-error text-danger"></span>
                    </div>
                    <div class="form-group mb-9">
                        <label for="driver-template" class="mb-2">{{ __('Default Driver Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="driver_template" id="driver-template">
                            @if (empty($settings['driver_template']['value']) || empty($templates))
                                <option value="">{{ __('--- Select Template') }}</option>
                            @endif
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ $settings['driver_template']['value'] == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                            @if (!empty($settings['customer_template']['value']))
                                <option value="">{{ __('--- Select Template') }}</option>
                            @endif
                        </select>
                        <span class="driver-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="user-template" class="mb-2">{{ __('Default User Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="user_template" id="user-template">
                            @if (empty($settings['user_template']['value']) || empty($templates))
                                <option value="">{{ __('--- Select Template') }}</option>
                            @endif
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ $settings['user_template']['value'] == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                            @if (!empty($settings['customer_template']['value']))
                                <option value="">{{ __('--- Select Template') }}</option>
                            @endif
                        </select>
                        <span class="user-error text-danger"></span>
                    </div>
                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2">{{ __('Default Task Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="task_template" id="task-template">
                            <option value="">{{ __('--- Select Template') }}</option>
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ $settings['task_template']['value'] == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2">{{ __('Default Task (From Port) Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="task_from_port_template"
                            id="task-from-port-template">
                            <option value="">{{ __('--- Select Template') }}</option>
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ $settings['task_from_port_template']['value'] == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2">{{ __('Default Task (To Port) Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="task_to_port_template"
                            id="task-to-port-template">
                            <option value="">{{ __('--- Select Template') }}</option>
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ $settings['task_to_port_template']['value'] == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2">{{ __('Default Customs Clearances Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="customs_clearance_template"
                            id="task-to-port-template">
                            <option value="">{{ __('--- Select Template') }}</option>
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ $settings['customs_clearance_template']['value'] == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="task-template"
                            class="mb-2">{{ __('Default Customs Clearances Agent Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="customs_clearance_agent_template"
                            id="task-to-port-template">
                            <option value="">{{ __('--- Select Template') }}</option>
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ $settings['customs_clearance_agent_template']['value'] == $val->id ? 'selected' : '' }}>
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
        <div class="col-md-4">

            <div class="card border ">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong>{{ __('System Management') }}</strong></div>
                    </div>
                </div>

                <div class="card-body text-center">

                    <h5 class="card-title">{{ __('Backup Management') }}</h5>
                    <p class="card-text text-muted">
                        {{ __('Manage database backups and uploaded files with advanced encryption') }}
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('settings.backup') }}" class="btn btn-primary">
                            <i class="ti ti-settings me-1"></i>
                            {{ __('Manage Backups') }}
                        </a>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <!-- Task Distribution Settings -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong>{{ __('Task Distribution Settings') }}</strong></div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label class="mb-2 d-flex justify-content-between align-items-center">
                            {{ __('Auto Distribution Enabled') }}
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input update-setting-checkbox" type="checkbox"
                                    data-key="auto_distribution_enabled"
                                    {{ ($settings['auto_distribution_enabled']['value'] ?? '0') == '1' ? 'checked' : '' }}>
                            </div>
                        </label>
                        <p class="text-muted small">{{ $settings['auto_distribution_enabled']['description'] ?? '' }}</p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="distribution_mode" class="mb-2">{{ __('Distribution Mode') }}</label>
                        <select class="form-select update-setting-select" data-key="distribution_mode">
                            <option value="sequential" {{ ($settings['distribution_mode']['value'] ?? 'sequential') == 'sequential' ? 'selected' : '' }}>
                                {{ __('Sequential (One by one)') }}
                            </option>
                            <option value="broadcast" {{ ($settings['distribution_mode']['value'] ?? '') == 'broadcast' ? 'selected' : '' }}>
                                {{ __('Broadcast (Top 5 nearby)') }}
                            </option>
                        </select>
                        <p class="text-muted small">{{ $settings['distribution_mode']['description'] ?? '' }}</p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="max_distribution_distance" class="mb-2">{{ __('Max Distribution Distance (Meters)') }}</label>
                        <input type="number" data-key="max_distribution_distance"
                            value="{{ $settings['max_distribution_distance']['value'] ?? '1000' }}"
                            class="form-control update-setting-input">
                        <p class="text-muted small">{{ $settings['max_distribution_distance']['description'] ?? '' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- App Update Settings -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong>{{ __('Driver App Update Settings') }}</strong></div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label for="min_driver_app_version" class="mb-2">{{ __('Minimum App Version') }}</label>
                        <input type="text" data-key="min_driver_app_version"
                            value="{{ $settings['min_driver_app_version']['value'] ?? '1.0.0' }}"
                            class="form-control update-setting-input" placeholder="e.g., 1.0.5">
                        <p class="text-muted small">{{ $settings['min_driver_app_version']['description'] ?? '' }}</p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="driver_app_update_url" class="mb-2">{{ __('App Update URL') }}</label>
                        <input type="url" data-key="driver_app_update_url"
                            value="{{ $settings['driver_app_update_url']['value'] ?? '' }}"
                            class="form-control update-setting-input" placeholder="https://play.google.com/store/apps/details?id=...">
                        <p class="text-muted small">{{ $settings['driver_app_update_url']['description'] ?? '' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
