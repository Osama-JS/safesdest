@extends('layouts/layoutMaster')

@section('title', __('Vehicles'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
    @vite(['resources/js/admin/vehicles.js'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">{{ __('Settings') }} | {{ __('Vehicles') }}</h5>
            <p>Add new roles with customized permissions as per your requirement. </p>
        </div>
    </div>

    <div class="row mt-6">
        <div class="col-md-8">
            <div class="nav-align-top mb-6">
                <ul class="nav nav-pills mb-4" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-pills-top-home" aria-controls="navs-pills-top-home"
                            aria-selected="true">{{ __('Vehicles') }}</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-pills-top-profile" aria-controls="navs-pills-top-profile"
                            aria-selected="false">{{ __('Vehicles Types') }}</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                            data-bs-target="#navs-pills-top-messages" aria-controls="navs-pills-top-messages"
                            aria-selected="false">{{ __('Vehicles Sizes') }}</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="navs-pills-top-home" role="tabpanel">
                        <form action="{{ route('settings.vehicles.store') }}" method="post" class="form_submit">
                            @csrf
                            <div class="row">
                                <input type="hidden" name="id" id="vehicle-id">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehicle-name">* {{ __('name') }}</label>
                                        <input type="text" name="v_name" id="vehicle-name" class="form-control"
                                            placeholder="vehicle name">
                                        <span class="v_name-error text-danger text-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehicle-en-name">* {{ __('English name') }}</label>
                                        <input type="text" name="v_en_name" id="vehicle-en-name" class="form-control"
                                            placeholder="vehicle name">
                                        <span class="v_en_name-error text-danger text-error"></span>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3 mb-3">{{ __('save') }}</button>

                        </form>
                        <div class="card table-responsive">
                            <table class="table">
                                <thead class="border-top">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('name') }}</th>
                                        <th>{{ __('types') }}</th>
                                        <th>{{ __('actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="vehicle-table">


                                </tbody>
                            </table>
                        </div>

                    </div>
                    <div class="tab-pane fade" id="navs-pills-top-profile" role="tabpanel">
                        <form action="{{ route('settings.vehicles.store.type') }}" method="post" class="form_submit">
                            @csrf

                            <div class="row">
                                <input type="hidden" name="id" id="vehicle-type-id">

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehicle-type-name">* {{ __('name') }}</label>
                                        <input type="text" name="name" id="vehicle-type-name" class="form-control"
                                            placeholder="vehicle name">
                                        <span class="name-error text-danger text-error"></span>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehicle-type-en-name">* {{ __('English name') }}</label>
                                        <input type="text" name="en_name" id="vehicle-type-en-name" class="form-control"
                                            placeholder="vehicle name">
                                        <span class="en_name-error text-danger text-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehicle-type-vehicle">* {{ __('Select vehicle') }}</label>
                                        <select name="vehicle" id="vehicle-type-vehicle"
                                            class="form-select vehicle-type-vehicle">
                                            <option value="">-- {{ __('select vehicle') }}</option>
                                        </select>

                                        <span class="vehicle-error text-danger text-error"></span>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3 mb-3">{{ __('save') }}</button>

                        </form>
                        <div class="card table-responsive">
                            <div class="card-header">
                                <div class="form-group">
                                    <label for="type-vehicle-flitter">{{ __('Flitter by vehicle') }}</label>
                                    <select name="flitter-vehicle" id="type-vehicle-flitter"
                                        class="form-select w-auto vehicle-type-vehicle">
                                        <option value="">-- {{ __('all vehicle') }}</option>
                                    </select>
                                </div>
                            </div>
                            <table class=" table">
                                <thead class="border-top">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('vehicle') }}</th>
                                        <th>{{ __('type name') }}</th>
                                        <th>{{ __('sizes') }}</th>
                                        <th>{{ __('actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="types-table"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="navs-pills-top-messages" role="tabpanel">
                        <form action="{{ route('settings.vehicles.store.size') }}" method="post" class="form_submit">
                            @csrf

                            <div class="row">
                                <input type="hidden" name="id" id="vehicle-size-id">

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehicle-size-name">* {{ __('size') }}</label>
                                        <input type="text" name="name" id="vehicle-size-name"
                                            class="form-control " placeholder="vehicle name">
                                        <span class="name-error text-danger text-error"></span>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehicle-size-vehicle">* {{ __('Select vehicle') }}</label>
                                        <select name="vehicle" id="vehicle-size-vehicle"
                                            class="form-select vehicle-type-vehicle">
                                            <option value="">{{ __('select vehicle') }}</option>
                                        </select>
                                        <span class="vehicle-error text-danger text-error"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehicle-size-type">* {{ __('Select vehicle Type') }}</label>
                                        <select name="type" id="vehicle-size-type"
                                            class="form-select vehicle-sizes-vehicle">
                                            <option value="">-- {{ __('select vehicle') }}</option>
                                        </select>

                                        <span class="type-error text-danger text-error"></span>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3 mb-3">{{ __('save') }}</button>

                        </form>
                        <div class="card table-responsive">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="form-group  ">
                                            <label for="size-vehicle-flitter">{{ __('Flitter by vehicle') }}</label>
                                            <select name="flitter-vehicle" id="size-vehicle-flitter"
                                                class="form-select w-auto vehicle-type-vehicle">
                                                <option value="">-- {{ __('select vehicle') }}</option>
                                            </select>

                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group  ">
                                            <label for="size-type-flitter"> {{ __('Flitter by vehicle type') }}</label>
                                            <select name="flitter-type" id="size-type-flitter"
                                                class="form-select w-auto vehicle-sizes-vehicle">
                                                <option value="">-- {{ __('select vehicle type') }}</option>
                                            </select>

                                        </div>
                                    </div>
                                </div>



                            </div>
                            <table class=" table">
                                <thead class="border-top">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('vehicle') }}</th>
                                        <th>{{ __('vehicle type') }}</th>
                                        <th>{{ __('size') }}</th>
                                        <th>{{ __('actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="sizes-table"></tbody>

                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



@endsection
