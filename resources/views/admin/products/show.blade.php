@extends('layouts/layoutMaster')

@section('title', __('Product Details'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    @vite(['resources/css/app.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    @vite(['resources/js/admin/products/show.js'])
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/spical.js'])
@endsection

@section('products-isactive')
    active
@endsection

@section('content')
    <div class="row">
        <!-- Product Information -->
        <div class="col-xl-4 col-lg-5 col-md-5">
            <div class="card mb-6">
                <div class="card-body">
                    <div class="user-avatar-section">
                        <div class="d-flex align-items-center flex-column">
                            @if ($data->image)
                                <img class="img-fluid rounded mb-4" src="{{ url($data->image) }}" height="120" width="120"
                                    alt="Product Image" />
                            @else
                                <div class="avatar avatar-xl mb-4">
                                    <span class="avatar-initial rounded bg-label-secondary">
                                        <i class="ti ti-package ti-lg"></i>
                                    </span>
                                </div>
                            @endif
                            <div class="user-info text-center">
                                <h5 class="mb-2">{{ $data->name }}</h5>
                                <span class="badge bg-label-info">{{ $data->code }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-around flex-wrap my-6 gap-0 gap-md-3 gap-lg-4">
                        <div class="d-flex align-items-center me-5 gap-4">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded">
                                    <i class="ti ti-currency-dollar ti-lg"></i>
                                </div>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $data->price }} SAR</h5>
                                <span>{{ __('Default Price') }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-4">
                            <div class="avatar">
                                <div class="avatar-initial bg-label-success rounded">
                                    <i class="ti ti-package ti-lg"></i>
                                </div>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ __('Min') }} {{ $data->minimum_order }} {{ $data->unit }}</h5>
                                <span>{{ __('Increase') }} ({{ $data->increase }} - {{ $data->unit }})</span>
                            </div>
                        </div>
                    </div>
                    <h5 class="pb-4 border-bottom mb-4">{{ __('Details') }}</h5>
                    <div class="info-container">
                        <ul class="list-unstyled mb-6">
                            <li class="mb-2">
                                <span class="h6">{{ __('Status') }}:</span>
                                <span class="badge bg-label-{{ $data->status ? 'success' : 'danger' }}">
                                    {{ $data->status ? __('Active') : __('Inactive') }}
                                </span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">{{ __('Unit') }}:</span>
                                <span>{{ $data->unit }}</span>
                            </li>
                            @if ($data->contact_name)
                                <li class="mb-2">
                                    <span class="h6">{{ __('Contact Name') }}:</span>
                                    <span>{{ $data->contact_name }}</span>
                                </li>
                            @endif
                            @if ($data->contact_phone)
                                <li class="mb-2">
                                    <span class="h6">{{ __('Contact Phone') }}:</span>
                                    <span>{{ $data->contact_phone }}</span>
                                </li>
                            @endif
                            @if ($data->contact_email)
                                <li class="mb-2">
                                    <span class="h6">{{ __('Contact Email') }}:</span>
                                    <span>{{ $data->contact_email }}</span>
                                </li>
                            @endif
                            @if ($data->address)
                                <li class="mb-2">
                                    <span class="h6">{{ __('Address') }}:</span>
                                    <span>{{ $data->address }}</span>
                                </li>
                            @endif
                            @if ($data->latitude && $data->longitude)
                                <li class="mb-2">
                                    <span class="h6">{{ __('Location') }}:</span>
                                    <a href="https://www.google.com/maps?q={{ $data->latitude }},{{ $data->longitude }}"
                                        target="_blank">
                                        {{ __('View on Map') }}
                                    </a>
                                </li>
                            @endif
                            <li class="mb-2">
                                <span class="h6">{{ __('Created At') }}:</span>
                                <span>{{ $data->created_at->format('Y-m-d H:i') }}</span>
                            </li>
                        </ul>
                        @if ($data->description)
                            <div class="mb-4">
                                <h6>{{ __('Description') }}:</h6>
                                <p>{{ $data->description }}</p>
                            </div>
                        @endif
                        @if ($data->notes)
                            <div class="mb-4">
                                <h6>{{ __('Notes') }}:</h6>
                                <p>{{ $data->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Management Tabs -->
        <div class="col-xl-8 col-lg-7 col-md-7">
            <div class="nav-align-top">
                <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-2 gap-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#vehicles">
                            <i class="ti ti-truck me-2"></i>{{ __('Vehicle Management') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#pricing">
                            <i class="ti ti-currency-dollar me-2"></i>{{ __('Customer Pricing') }}
                        </a>
                    </li>
                </ul>
                <div class="tab-content">
                    <!-- Vehicle Management Tab -->
                    <div class="tab-pane fade show active" id="vehicles">
                        <div class="p-3">
                            <div class="mb-5 d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">{{ __('Vehicle Management') }}</h5>
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#vehicleModal">
                                    <i class="ti ti-plus me-1"></i>{{ __('Add Vehicle') }}
                                </button>
                            </div>
                            <div class="card-datatable table-responsive">
                                <table class="datatables-vehicles table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Vehicle Size') }}</th>
                                            <th>{{ __('Maximum Order') }}</th>
                                            <th>{{ __('Notes') }}</th>
                                            <th>{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Pricing Tab -->
                    <div class="tab-pane fade" id="pricing">
                        <div class="p-3">
                            <div class="mb-5 d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">{{ __('Customer Pricing') }}</h5>
                                <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#pricingModal">
                                    <i class="ti ti-plus me-1"></i>{{ __('Add Customer Price') }}
                                </button>
                            </div>
                            <div class="card-datatable table-responsive">
                                <table class="datatables-pricing table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Customer') }}</th>
                                            <th>{{ __('Special Price') }}</th>
                                            <th>{{ __('Notes') }}</th>
                                            <th>{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle Modal -->
    <div class="modal fade" id="vehicleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="vehicleModalTitle">{{ __('Add Vehicle') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="form_submit" method="POST" action="{{ route('products.vehicles.store') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="product_id" value="{{ $data->id }}">
                        <input type="hidden" name="vehicle_id" id="vehicle_id">

                        <div class="mb-3">
                            <label class="form-label" for="vehicle_size_id">* {{ __('Vehicle Size') }}</label>
                            <select name="vehicle_size_id" id="vehicle_size_id" class="form-select select2" required>
                                <option value="">-- {{ __('Select Vehicle Size') }} --</option>
                            </select>
                            <span class="vehicle_size_id-error text-danger text-error"></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="maximum_order">* {{ __('Maximum Order') }}</label>
                            <input type="number" step="0.01" name="maximum_order" id="maximum_order"
                                class="form-control" required>
                            <span class="maximum_order-error text-danger text-error"></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="vehicle_notes">{{ __('Notes') }}</label>
                            <textarea name="notes" id="vehicle_notes" class="form-control" rows="3"></textarea>
                            <span class="notes-error text-danger text-error"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pricing Modal -->
    <div class="modal fade" id="pricingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pricingModalTitle">{{ __('Add Customer Price') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="form_submit" method="POST" action="{{ route('products.pricing.store') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="product_id" value="{{ $data->id }}">
                        <input type="hidden" name="pricing_id" id="pricing_id">

                        <div class="mb-3">
                            <label class="form-label" for="customer_id">* {{ __('Customer') }}</label>
                            <select name="customer_id" id="customer_id" class="form-select select2" required>
                                <option value="">-- {{ __('Select Customer') }} --</option>
                            </select>
                            <span class="customer_id-error text-danger text-error"></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="special_price">* {{ __('Special Price') }}</label>
                            <input type="number" step="0.01" name="price" id="special_price" class="form-control"
                                required>
                            <span class="price-error text-danger text-error"></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="pricing_notes">{{ __('Notes') }}</label>
                            <textarea name="notes" id="pricing_notes" class="form-control" rows="3"></textarea>
                            <span class="notes-error text-danger text-error"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
