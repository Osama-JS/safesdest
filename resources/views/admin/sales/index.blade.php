@extends('layouts/layoutMaster')

@section('title', __('Sales & Invoicing'))

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
<link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
<link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css" rel="stylesheet" />
<style>
    .product-card {
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-color: #696cff;
    }
    .product-card.selected {
        border-color: #696cff;
        background-color: #f5f5ff;
    }
    .product-card img {
        height: 150px;
        object-fit: cover;
    }
    #deliveryMap {
        height: 400px;
        width: 100%;
    }
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
    }
    .step-indicator .step {
        flex: 1;
        text-align: center;
        padding: 10px;
        position: relative;
    }
    .step-indicator .step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 20px;
        right: -50%;
        width: 100%;
        height: 2px;
        background: #ddd;
        z-index: -1;
    }
    .step-indicator .step.active {
        color: #696cff;
        font-weight: bold;
    }
    .step-indicator .step.active::after {
        background: #696cff;
    }
    .step-indicator .step-number {
        display: inline-block;
        width: 40px;
        height: 40px;
        line-height: 40px;
        border-radius: 50%;
        background: #ddd;
        color: #fff;
        margin-bottom: 5px;
    }
    .step-indicator .step.active .step-number {
        background: #696cff;
    }
    .step-indicator .step.completed .step-number {
        background: #28a745;
    }
</style>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/jquery/jquery.js'])
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
<script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
<script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Sales & Invoicing') }}</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createOrderModal">
            <i class="ti ti-plus me-1"></i>{{ __('Create New Order') }}
        </button>
    </div>
    <div class="card-datatable table-responsive">
        <table class="datatables-sales table border-top" id="salesTable">
            <thead>
                <tr>
                    <th>{{ __('Invoice #') }}</th>
                    <th>{{ __('Customer') }}</th>
                    <th>{{ __('Total') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Created By') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Create Order Modal -->
<div class="modal fade" id="createOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Create New Order') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active" data-step="1">
                        <div class="step-number">1</div>
                        <div>{{ __('Select Product') }}</div>
                    </div>
                    <div class="step" data-step="2">
                        <div class="step-number">2</div>
                        <div>{{ __('Order Details') }}</div>
                    </div>
                    <div class="step" data-step="3">
                        <div class="step-number">3</div>
                        <div>{{ __('Delivery Setup') }}</div>
                    </div>
                    <div class="step" data-step="4">
                        <div class="step-number">4</div>
                        <div>{{ __('Confirmation') }}</div>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Tab 1: Product Selection -->
                    <div class="tab-pane fade show active" id="step1" role="tabpanel">
                        <h6 class="mb-3">{{ __('Select a Product') }}</h6>
                        <div class="row" id="productsContainer">
                            <!-- Products will be loaded here via AJAX -->
                        </div>
                    </div>

                    <!-- Tab 2: Order Details -->
                    <div class="tab-pane fade" id="step2" role="tabpanel">
                        <h6 class="mb-3">{{ __('Order Details') }}</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Customer') }} <span class="text-danger">*</span></label>
                                    <select class="form-select select2" id="customerId" name="customer_id" required>
                                        <option value="">{{ __('Select Customer') }}</option>
                                        @foreach(\App\Models\Customer::where('status', 'active')->get() as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Template') }} <span class="text-danger">*</span></label>
                                    <select class="form-select select2" id="templateId" name="template_id" required>
                                        <option value="">{{ __('Select Template') }}</option>
                                        @foreach(\App\Models\Form_Template::all() as $template)
                                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info" id="productInfo" style="display:none;">
                            <h6>{{ __('Selected Product') }}</h6>
                            <p class="mb-1"><strong>{{ __('Name') }}:</strong> <span id="infoProductName"></span></p>
                            <p class="mb-1"><strong>{{ __('Unit') }}:</strong> <span id="infoProductUnit"></span></p>
                            <p class="mb-0"><strong>{{ __('Minimum Order') }}:</strong> <span id="infoProductMinOrder"></span></p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Quantity') }} <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                    <div class="mt-2">
                                        <strong>{{ __('Total Price') }}:</strong> <span id="totalPrice" class="text-primary fs-5">0.00</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">{{ __('Vehicle') }} <span class="text-danger">*</span></label>
                                    <select class="form-select" id="vehicleSizeId" name="vehicle_size_id" required>
                                        <option value="">{{ __('Select Vehicle') }}</option>
                                    </select>
                                    <small class="text-muted">{{ __('Vehicles will appear based on product and quantity') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Delivery Setup -->
                    <div class="tab-pane fade" id="step3" role="tabpanel">
                        <h6 class="mb-3">{{ __('Delivery Setup') }}</h6>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Pricing Type') }} <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_pricing_type" id="pricingManual" value="manual" checked>
                                <label class="form-check-label" for="pricingManual">{{ __('Manual Pricing') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_pricing_type" id="pricingAuto" value="auto">
                                <label class="form-check-label" for="pricingAuto">{{ __('Automatic Pricing') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_pricing_type" id="pricingAd" value="ad">
                                <label class="form-check-label" for="pricingAd">{{ __('Advertisement') }}</label>
                            </div>
                        </div>

                        <div id="manualPriceContainer" class="mb-3">
                            <label class="form-label">{{ __('Delivery Price') }}</label>
                            <input type="number" class="form-control" id="manualDeliveryPrice" name="manual_delivery_price" min="0" step="0.01">
                        </div>

                        <div id="adPriceContainer" class="mb-3" style="display:none;">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Minimum Price') }}</label>
                                    <input type="number" class="form-control" id="adMinPrice" name="ad_min_price" min="0" step="0.01">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Maximum Price') }}</label>
                                    <input type="number" class="form-control" id="adMaxPrice" name="ad_max_price" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="mt-2">
                                <label class="form-label">{{ __('Ad Notes') }}</label>
                                <textarea class="form-control" id="adNotes" name="ad_notes" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Delivery Location') }} <span class="text-danger">*</span></label>
                            <div id="deliveryMap"></div>
                            <input type="hidden" id="deliveryLat" name="delivery_lat">
                            <input type="hidden" id="deliveryLng" name="delivery_lng">
                            <input type="hidden" id="deliveryAddress" name="delivery_address">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Conditions') }}</label>
                            <textarea class="form-control" id="conditions" name="conditions" rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Tab 4: Summary -->
                    <div class="tab-pane fade" id="step4" role="tabpanel">
                        <h6 class="mb-3">{{ __('Order Summary') }}</h6>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">{{ __('Purchase Details') }}</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>{{ __('Product') }}:</strong></td>
                                        <td id="summaryProduct"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('Quantity') }}:</strong></td>
                                        <td id="summaryQuantity"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('Unit Price') }}:</strong></td>
                                        <td id="summaryUnitPrice"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('Total') }}:</strong></td>
                                        <td class="text-primary fs-5" id="summaryTotal"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">{{ __('Delivery Details') }}</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless">
                                    <tr>
                                        <td><strong>{{ __('Vehicle') }}:</strong></td>
                                        <td id="summaryVehicle"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('Pricing Type') }}:</strong></td>
                                        <td id="summaryPricingType"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('Pickup') }}:</strong></td>
                                        <td id="summaryPickup"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('Delivery') }}:</strong></td>
                                        <td id="summaryDelivery"></td>
                                    </tr>
                                    <tr>
                                        <td><strong>{{ __('Delivery Fee') }}:</strong></td>
                                        <td class="text-success fs-5" id="summaryDeliveryFee"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="mb-0">
                                    {{ __('Grand Total') }}: <span class="text-primary" id="summaryGrandTotal"></span>
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="btnPrevious" style="display:none;">
                    <i class="ti ti-arrow-left me-1"></i>{{ __('Previous') }}
                </button>
                <button type="button" class="btn btn-primary" id="btnNext">
                    {{ __('Next') }}<i class="ti ti-arrow-right ms-1"></i>
                </button>
                <button type="button" class="btn btn-success" id="btnSubmit" style="display:none;">
                    <i class="ti ti-check me-1"></i>{{ __('Create Order') }}
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
// Pass Laravel data to JavaScript
window.salesRoutes = {
    data: '{{ route('sales.data') }}',
    products: '{{ route('sales.products') }}',
    calculatePrice: '{{ route('sales.calculate_price') }}',
    matchingVehicles: '{{ route('sales.matching_vehicles') }}',
    store: '{{ route('sales.store') }}',
    baseUrl: '{{ url('admin/sales') }}'
};

window.csrfToken = '{{ csrf_token() }}';
window.mapboxToken = '{{ config('services.mapbox.public_token') ?? "pk.eyJ1Ijoib3NhbWE5NjMiLCJhIjoiY2xrbm9kM245MGFqbjNmbzhzb3d3a3I1NiJ9.A3W5yXnQ_JgDk6d-6hXjXQ" }}';

window.translations = {
    price: '{{ __('Price') }}',
    minOrder: '{{ __('Min Order') }}',
    selectVehicle: '{{ __('Select Vehicle') }}',
    max: '{{ __('Max') }}',
    pleaseSelectProduct: '{{ __('Please select a product') }}',
    pleaseSelectCustomer: '{{ __('Please select a customer') }}',
    pleaseEnterQuantity: '{{ __('Please enter quantity') }}',
    pleaseSelectVehicle: '{{ __('Please select a vehicle') }}',
    pleaseSelectTemplate: '{{ __('Please select a template') }}',
    pleaseEnterDeliveryPrice: '{{ __('Please enter delivery price') }}',
    pleaseEnterMinMaxPrice: '{{ __('Please enter min and max price for advertisement') }}',
    pleaseSelectDeliveryLocation: '{{ __('Please select delivery location on map') }}'
};
</script>
@vite(['resources/js/admin/sales/sales-order.js'])
@endsection
