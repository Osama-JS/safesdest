@extends('layouts/layoutMaster')

@section('title', __('Points'))

@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css"
        rel="stylesheet" />
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss'])

    <style>
        .parent-row {
            background-color: #f8f9fa !important;
            font-weight: bold;
            cursor: pointer;
            border-left: 4px solid #0d6efd !important;
        }

        .parent-row:hover {
            background-color: #e9ecef !important;
        }

        .child-row {
            background-color: #ffffff !important;
            border-left: 4px solid #dee2e6 !important;
        }

        .child-row td:first-child {
            padding-right: 40px !important;
        }

        .toggle-icon {
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .toggle-icon.collapsed {
            transform: rotate(-90deg);
        }

        .customer-badge {
            background: linear-gradient(45deg, #0d6efd, #6610f2);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .general-badge {
            background: linear-gradient(45deg, #198754, #20c997);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .point-name {
            color: #495057;
            font-weight: 500;
        }

        .point-address {
            color: #6c757d;
            font-size: 13px;
        }

        .status-active {
            background-color: #d1edff;
            color: #0c63e4;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-inactive {
            background-color: #ffe0e6;
            color: #d63384;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .points-count {
            background-color: #ffffff;
            color: #6c757d;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-right: 10px;
        }
    </style>
@endsection

@section('vendor-script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/block-ui/block-ui.js'])
@endsection

@section('page-script')

    @vite(['resources/js/admin/points.js'])
    @vite(['resources/js/ajax.js'])
@endsection

@section('content')
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item">
                <a href="{{ url('admin') }}"><i class="ti ti-home-2 me-1"></i>{{ __('الرئيسية') }}</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">{{ __('الإعدادات') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('النقاط') }}</li>
        </ol>
    </nav>

    <!-- Hero Header Banner -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.02) 100%);">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-xl bg-primary text-white rounded-3 shadow-sm d-flex align-items-center justify-content-center p-2">
                        <i class="ti ti-map-pin fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <h4 class="fw-bold mb-0 text-heading">{{ __('إدارة النقاط والمواقع الجغرافية') }}</h4>
                            <span class="badge bg-label-primary rounded-pill px-3 py-1 fs-tiny fw-semibold">
                                <i class="ti ti-current-location me-1"></i> {{ __('عرض هرمي حسب العملاء') }}
                            </span>
                        </div>
                        <p class="text-muted mb-0">
                            {{ __('إدارة نقاط الاستلام والتسليم المعتمدة مصنفة ومجمعة حسب العملاء مع دعم الإحداثيات والخرائط التفاعلية.') }}
                        </p>
                    </div>
                </div>
                <div>
                    <button class="add-new btn btn-primary waves-effect waves-light shadow-sm" data-bs-toggle="modal"
                        data-bs-target="#submitModal">
                        <i class="ti ti-plus me-1"></i>
                        <span>{{ __('إضافة نقطة جديدة') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header border-bottom py-3">
            <h5 class="card-title mb-0 fw-bold">
                <i class="ti ti-map-pins text-primary me-2"></i>{{ __('قائمة النقاط الجغرافية المسجلة') }}
            </h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-users table">
                <thead class="border-top">
                    <tr>
                        <th></th>
                        <th>#</th>
                        <th>{{ __('name') }}</th>
                        <th>{{ __('address') }}</th>
                        <th>{{ __('customer') }}</th>
                        <th>{{ __('status') }}</th>
                        <th>{{ __('actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>

    <div class="modal fade " id="submitModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ __('Add New Point') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('Close') }}"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('settings.points.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="col-xl-12">

                            <div class="nav-align-top  mb-6">

                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <input type="hidden" name="id" id="point_id">
                                        <span class="id-error text-danger text-error"></span>
                                        <div class="mb-3">
                                            <label class="form-label" for="point-name">* {{ __('name') }}</label>
                                            <input type="text" name="name" class="form-control" id="point-name"
                                                placeholder="{{ __('enter the point name') }}" />
                                            <span class="name-error text-danger text-error"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="point-address">* {{ __('address') }}</label>
                                            <input type="text" name="address" class="form-control" id="point-address"
                                                placeholder="{{ __('enter the point address') }}" />
                                            <span class="address-error text-danger text-error"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label for="point-location">*
                                                {{ __('Location') }}</label>
                                            <div class="input-group mb-2">
                                                <div class="form-control p-0" id="point-geocoder">
                                                </div>
                                                <button type="button" title="تحليل رابط موقع" id="point-toggle-link-input"
                                                    class="input-group-text bg-white">
                                                    <i class="fas fa-link text-secondary"></i>
                                                </button>
                                                <button type="button" title="إدخال يدوي" id="point-manual-btn"
                                                    class="input-group-text bg-white">
                                                    <i class="fas fa-globe text-secondary"></i>
                                                </button>
                                                <button type="button" title="موقعي الحالي" id="point-getCurrentLocation"
                                                    class="input-group-text bg-white">
                                                    <i class="fas fa-location-crosshairs text-secondary"></i>
                                                </button>
                                            </div>
                                            <div id="point-link-input-wrapper" class="mt-2" style="display: none;">
                                                <div class="input-group">
                                                    <input type="text" id="point-map-link" class="form-control"
                                                        placeholder="ألصق رابط الموقع هنا" />
                                                    <button type="button" id="point-parse-link" class="btn btn-secondary">
                                                        تحليل الرابط
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- Map Container -->
                                            <div id="point-map-container"
                                                class="position-relative rounded overflow-hidden border"
                                                style="height: 200px; display: none;">
                                                <div class="row mb-2 position-absolute top-0 start-0 m-2 z-3">
                                                    <div class="col">
                                                        <input type="number" name="longitude" step="any"
                                                            id="point-longitude" class="form-control"
                                                            placeholder="(Longitude)">
                                                    </div>
                                                    <div class="col">
                                                        <input type="number" name="latitude" step="any"
                                                            id="point-latitude" class="form-control"
                                                            placeholder="(Latitude)">
                                                    </div>

                                                </div>
                                                <button id="confirm-location" type="button"
                                                    class="btn btn-primary btn-sm position-absolute top-0 end-0 m-2 z-3"
                                                    style="display: none;">
                                                    {{ __('confirm location') }}
                                                </button>
                                                <div id="point-map" class="w-100 h-100" style="display: none;"></div>
                                                <!-- Hidden Final Address -->
                                                <input type="hidden" id="point_address" name="point_address" />
                                            </div>

                                            <span class="longitude-error text-danger text-error"></span>
                                            <span class="latitude-error text-danger text-error"></span>



                                        </div>
                                        <div class="border rounded p-3">
                                            <div class="divider text-start">
                                                <div class="divider-text"><strong>Optional data</strong></div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="point-customer">
                                                    {{ __('Customer') }}</label>
                                                <select name="customer" id="point-customer" class="form-select select2">

                                                </select>
                                                <span class="customer-error text-danger text-error"></span>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="point-contact_name">
                                                    {{ __('Contact name') }}</label>
                                                <input type="text" name="contact_name" class="form-control"
                                                    id="point-contact_name"
                                                    placeholder="{{ __('enter the point contact name') }}" />
                                                <span class="contact_name-error text-danger text-error"></span>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label" for="point-contact_phone">
                                                    {{ __('Contact phone') }}</label>
                                                <input type="text" name="contact_phone" class="form-control"
                                                    id="point-contact_phone"
                                                    placeholder="{{ __('enter the point contact phone') }}" />
                                                <span class="contact_phone-error text-danger text-error"></span>
                                            </div>
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
