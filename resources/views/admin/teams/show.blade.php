@extends('layouts/layoutMaster')

@section('title', __('Teams'))

<!-- Vendor Styles -->
@section('vendor-style')

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

    @vite(['resources/css/app.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    <script>
        const templateId = {{ $driver_template->value ?? 0 }}
        const teamID = {{ $data->id }}
    </script>
    <script type="text/template" id="vehicle-row-template">
      <div class="row vehicle-row mb-3 " data-index="{index}">
        <div class="col-md-4">
          <label class="form-label">* Vehicle</label>
          <select class="form-select vehicle-select" name="vehicles[{index}][vehicle]">
            <option value="">Select a vehicle</option>
            @foreach ($vehicles as $vehicle)
              <option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>
            @endforeach
          </select>

        </div>
        <div class="col-md-4">
          <label class="form-label">* Vehicle Type</label>
          <select class="form-select vehicle-type-select" name="vehicles[{index}][vehicle_type]" disabled>
            <option value="">Select a vehicle type</option>
          </select>

        </div>
        <div class="col-md-4">
          <label class="form-label">* Vehicle Size</label>
          <select class="form-select vehicle-size-select" name="vehicle" disabled>
            <option value="">Select a vehicle size</option>
          </select>
          <span class="vehicle-error text-danger text-error"></span>

        </div>


      </div>
    </script>

@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/teams/show.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])
@endsection

@section('content')

    <div class="mb-4">
        <h5>{{ $data->name }} [{{ $data->id }}]</h5>
        <p>
            <i class="tf-icons ti  ti-location"></i>
            {{ $data->address }}
        </p>
        <span class="badge bg-label-secondary">
            <i class="tf-icons ti  ti-steering-wheel"></i>
            <b>{{ $data->drivers->count() }}</b>
        </span>
        <span class="badge bg-label-secondary">
            <i class=" tf-icons ti ti-truck-delivery"></i>
            <b>{{ $data->drivers->count() }}</b>
        </span>
    </div>
    <div class="mt-3">
        <div class="nav-align-top nav-tabs-shadow mb-6">
            <ul class="nav nav-tabs nav-fill  " role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active py-4" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-drivers" aria-controls="navs-drivers" aria-selected="true"><span
                            class="d-none d-sm-block"><i class="tf-icons ti  ti-steering-wheel"></i> Drivers <span
                                class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-danger ms-1_5 pt-50">{{ $data->drivers->count() }}</span></span><i
                            class="ti  ti-steering-wheel ti-sm d-sm-none"></i></button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link py-4" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-tasks" aria-controls="navs-tasks" aria-selected="false"><span
                            class="d-none d-sm-block"><i class="tf-icons ti ti-truck-delivery ti-sm me-1_5"></i> Tasks <span
                                class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-danger ms-1_5 pt-50">{{ $data->tasks->count() }}</span></span><i
                            class="ti ti-truck-delivery ti-sm d-sm-none"></i></button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link py-4" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-wallet" aria-controls="navs-wallet" aria-selected="false"><span
                            class="d-none d-sm-block"><i class="tf-icons ti ti-wallet ti-sm me-1_5"></i> Wallet</span><i
                            class="ti ti-wallet ti-sm d-sm-none"></i></button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="navs-drivers" role="tabpanel">
                    <div class="card-datatable table-responsive">
                        <table class="datatables-users table">
                            <thead class="border-top">
                                <tr>
                                    <th></th>
                                    <th>#</th>
                                    <th>{{ __('name') }}</th>
                                    <th>{{ __('username') }}</th>
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
                <div class="tab-pane fade" id="navs-tasks" role="tabpanel">
                    <div class="card-datatable table-responsive">
                        <table class="datatables-tasks table ">
                            <thead class="border ">
                                <tr>
                                    <th></th>
                                    <th>{{ __('task id') }}</th>
                                    <th>{{ __('price') }}</th>
                                    <th>{{ __('owner') }}</th>
                                    <th>{{ __('pickup address') }}</th>
                                    <th>{{ __('start before') }}</th>
                                    <th>{{ __('complete before') }}</th>
                                    <th>{{ __('status') }}</th>
                                    <th>{{ __('closed') }}</th>
                                    <th>{{ __('action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="navs-wallet" role="tabpanel">
                    <p>
                        Oat cake chupa chups dragée donut toffee. Sweet cotton candy jelly beans macaroon gummies cupcake
                        gummi
                        bears
                        cake chocolate.
                    </p>
                    <p class="mb-0">
                        Cake chocolate bar cotton candy apple pie tootsie roll ice cream apple pie brownie cake. Sweet roll
                        icing
                        sesame snaps caramels danish toffee. Brownie biscuit dessert dessert. Pudding jelly jelly-o tart
                        brownie
                        jelly.
                    </p>
                </div>
            </div>
        </div>
    </div>



@endsection
