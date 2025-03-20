@extends('layouts/layoutMaster')

@section('title', __('Geo-fence'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/leaflet/leaflet.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">

@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/leaflet/leaflet.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])


@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/admin/geofences.js'])
    {{-- @vite(['resources/assets/js/maps-leaflet.js']) --}}

@endsection

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">{{ __('Settings') }} | {{ __('Geo-fence') }}</h5>
            <p>It allows you to categorize Manager and simplifies the process of task assignment by letting you create
                virtual boundaries.
            </p>

            <div class="col-md-12">
                <button class="add-new btn btn-primary waves-effect waves-light mb-5 mx-4" data-bs-toggle="modal"
                    data-bs-target="#submitModal">
                    <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                    <span class="d-none d-sm-inline-block"> Add New Geo-fence</span>
                </button>
                <input type="text" id="search-team" class="form-control " placeholder="🔍 Search Team">

            </div>
        </div>
    </div>



    <div class="row mt-6">
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">

                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card mb-6">

                <div class="leaflet-map" id="shapehMap"></div>

            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card mb-6">
            <h5 class="card-header">Marker Circle & Polygon</h5>
            <div class="card-body">
                <div class="leaflet-map" id="shapeMap"></div>
            </div>
        </div>
    </div>

    <div class="modal fade " id="submitModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">

                <div class="row">

                    <div class="col-md-4">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modelTitle">{{ __('Add Geo-fence') }}</h5>

                        </div>
                        <div class=" mt-6">
                            <div class="px-3">
                                <form action="{{ route('settings.geofences.store') }}" method="POST" class="form_submit">
                                    @csrf
                                    <input type="hidden" id="geo-id" name="id">

                                    <input type="hidden" id="geo-coordinates" name="coordinates">
                                    <span class="coordinates-error text-danger text-error"></span>

                                    <div class="form-group">
                                        <label for="geo-name">* {{ __('Name') }}</label>
                                        <input type="text" class="form-control" id="geo-name" name="name"
                                            placeholder="Enter name">
                                        <span class="name-error text-danger text-error"></span>

                                    </div>
                                    <div class="form-group mt-3">
                                        <label for="geo-description">{{ __('Description') }}</label>
                                        <textarea class="form-control" id="geo-description" name="description" rows="2" placeholder="Enter description"></textarea>
                                        <span class="description-error text-danger text-error"></span>

                                    </div>

                                    <div class="form-group mt-3 mb-5">
                                        <label for="geo-teams">{{ __('Teams') }}</label>
                                        <select name="teams" id="geo-teams" class="select2 form-select" multiple>
                                            <option value=""></option>
                                            @foreach ($data as $key)
                                                <option value="{{ $key->id }}">{{ $key->name }}</option>
                                            @endforeach
                                        </select>
                                        <span class="teams-error text-danger text-error"></span>
                                    </div>

                                    <button type="submit" class="btn btn-primary me-3 data-submit">Submit</button>

                                    <button type="button" class="btn btn-label-secondary"
                                        data-bs-dismiss="modal">Close</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="p-3">
                            <div class="leaflet-map" id="submit-map"></div>
                        </div>

                    </div>
                </div>


            </div>
        </div>
    </div>






@endsection
