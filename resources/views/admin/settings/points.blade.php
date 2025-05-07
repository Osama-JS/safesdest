@extends('layouts/layoutMaster')

@section('title', __('Points'))

@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css"
        rel="stylesheet" />
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss'])

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

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">{{ __('Settings') }} | {{ __('Points') }}</h5>
            {{-- <p>{{ __('Add new roles with customized permissions as per your requirement') }}. </p> --}}
            <button class="add-new btn btn-primary waves-effect waves-light mb-5 mx-4" data-bs-toggle="modal"
                data-bs-target="#submitModal">
                <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                <span class="d-none d-sm-inline-block"> {{ __('Add New Point') }}</span>
            </button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                                <button type="button" title="إدخال يدوي" id="point-manual-btn"
                                                    class="input-group-text bg-white">
                                                    <i class="fas fa-globe text-secondary"></i>
                                                </button>
                                                <button type="button" title="موقعي الحالي" id="point-getCurrentLocation"
                                                    class="input-group-text bg-white">
                                                    <i class="fas fa-location-crosshairs text-secondary"></i>
                                                </button>
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
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">Submit</button>

                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection
