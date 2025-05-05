@extends('layouts/layoutMaster')

@section('title', __('Blockages'))

<!-- Vendor Styles -->
@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css"
        rel="stylesheet" />
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
    @vite(['resources/js/admin/blockage.js'])

@endsection

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">{{ __('Settings') }} | {{ __('Blockages') }}</h5>
            <p>Add new roles with customized permissions as per your requirement. </p>

            <button class="add-new btn btn-primary waves-effect waves-light mb-5 mx-4" data-bs-toggle="modal"
                data-bs-target="#submitModal">
                <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                <span class="d-none d-sm-inline-block">{{ __('Add a new Blockage') }}</span>
            </button>
        </div>

        <div class="card-datatable table-responsive">
            <table class="datatables-blockages table table-hover">
                <thead class="border-top">
                    <tr>
                        <th></th>
                        <th>#</th>
                        <th>{{ __('type') }}</th>
                        <th>{{ __('description') }}</th>
                        <th>{{ __('coordinates') }}</th>
                        <th>{{ __('status') }}</th>
                        <th>{{ __('created at') }}</th>
                        <th>{{ __('actions') }}</th>
                    </tr>
                </thead>

            </table>
        </div>
    </div>

    <!-- مودال إنشاء إغلاق جديد -->
    <div class="modal fade" id="submitModal" tabindex="-1" aria-labelledby="createBlockageModalLabel" aria-hidden="true">
        <div class="modal-dialog ">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Add a new Blockage') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form class="form_submit" method="POST" action="{{ route('settings.blockages.store') }}">
                    @csrf
                    <div class="modal-body">

                        <input type="hidden" name="id" id="block_id">
                        <div class="mb-3">
                            <label for="type" class="form-label">* {{ __('Block Type') }}</label>
                            <select class="form-select" id="block-type" name="type" required>
                                <option value="">-- {{ __('Select Type') }} --</option>
                                <option value="point">{{ __('Point Closed') }}</option>
                                <option value="line">{{ __('Line Closed') }}</option>
                            </select>
                            <span class="type-error text-danger text-error"></span>
                        </div>
                        <div class="mb-3">
                            <label for="type" class="form-label">{{ __('Description') }}</label>
                            <input type="text" name="description" id="block-description" class="form-control"
                                placeholder="{{ __('Block Description') }} ({{ __('optional') }})">
                            <span class="description-error text-danger text-error"></span>

                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('draw the Points on the map') }}</label>
                            <div id="map" class="w-100" style="height: 300px;"></div>
                        </div>

                        <input type="hidden" id="coordinates" name="coordinates">
                        <span class="coordinates-error text-danger text-error"></span>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">حفظ</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection
