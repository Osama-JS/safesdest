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


@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/drivers/drivers.js'])

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
            <ul class="nav nav-tabs nav-fill" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-justified-home" aria-controls="navs-justified-home" aria-selected="true"><span
                            class="d-none d-sm-block"><i class="tf-icons ti  ti-steering-wheel"></i> Drivers <span
                                class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-danger ms-1_5 pt-50">{{ $data->drivers->count() }}</span></span><i
                            class="ti  ti-steering-wheel ti-sm d-sm-none"></i></button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-justified-profile" aria-controls="navs-justified-profile"
                        aria-selected="false"><span class="d-none d-sm-block"><i
                                class="tf-icons ti ti-truck-delivery ti-sm me-1_5"></i> Tasks</span><i
                            class="ti ti-truck-delivery ti-sm d-sm-none"></i></button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" role="tab" data-bs-toggle="tab"
                        data-bs-target="#navs-justified-messages" aria-controls="navs-justified-messages"
                        aria-selected="false"><span class="d-none d-sm-block"><i
                                class="tf-icons ti ti-wallet ti-sm me-1_5"></i> Wallet</span><i
                            class="ti ti-wallet ti-sm d-sm-none"></i></button>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="navs-justified-home" role="tabpanel">
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
                <div class="tab-pane fade" id="navs-justified-profile" role="tabpanel">
                    <p>
                        Donut dragée jelly pie halvah. Danish gingerbread bonbon cookie wafer candy oat cake ice cream.
                        Gummies
                        halvah
                        tootsie roll muffin biscuit icing dessert gingerbread. Pastry ice cream cheesecake fruitcake.
                    </p>
                    <p class="mb-0">
                        Jelly-o jelly beans icing pastry cake cake lemon drops. Muffin muffin pie tiramisu halvah cotton
                        candy
                        liquorice caramels.
                    </p>
                </div>
                <div class="tab-pane fade" id="navs-justified-messages" role="tabpanel">
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
