@extends('layouts/layoutMaster')

@section('title', __('Pricing Matrix') . ' — ' . $company->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
<script>
$(function () {

    // ── Highlight empty cells ──────────────────────────────────────
    function refreshCellColors() {
        $('.price-cell').each(function () {
            const hasPrice = $(this).find('.has-price').length > 0;
            $(this).toggleClass('table-warning', !hasPrice).toggleClass('table-success', hasPrice);
        });
    }
    refreshCellColors();

    // ── Open pricing modal ─────────────────────────────────────────
    $(document).on('click', '.set-price-btn', function () {
        const warehouseId  = $(this).data('warehouse');
        const provinceId   = $(this).data('province');
        const warehouseName = $(this).data('warehouse-name');
        const provinceName  = $(this).data('province-name');
        const routeId       = $(this).data('route-id') || '';

        $('#modal-warehouse-id').val(warehouseId);
        $('#modal-province-id').val(provinceId);
        $('#modal-route-id').val(routeId);
        $('#modal-route-label').text(warehouseName + ' ← ' + provinceName);

        // Reset vehicle prices
        $('.vehicle-price-input').val('');

        // If existing route, load vehicle prices
        if (routeId) {
            $.get(`{{ url('admin/b2b/pricing/routes') }}/${routeId}`, function(data) {
                $('#modal-default-price').val(data.default_price || '');
                if (data.vehicle_prices) {
                    data.vehicle_prices.forEach(function(vp) {
                        $(`.vehicle-price-input[data-vehicle="${vp.vehicle_size_id}"]`).val(vp.price);
                    });
                }
            });
        } else {
            $('#modal-default-price').val('');
        }

        $('#pricingModal').modal('show');
    });

        // Save Route Pricing
        $('#pricing-form').on('submit', function (e) {
            e.preventDefault();
            
            // Clear previous errors
            $('.is-invalid').removeClass('is-invalid');
            $('.text-error').text('');

            const vehiclePrices = [];
            $('.vehicle-price-input').each(function () {
                const price = $(this).val();
                if (price !== '' && price !== null) {
                    vehiclePrices.push({
                        vehicle_size_id: $(this).data('vehicle'),
                        price: parseFloat(price)
                    });
                }
            });

            const payload = {
                _token: "{{ csrf_token() }}",
                company_id: {{ $company->id }},
                warehouse_id: $('#modal-warehouse-id').val(),
                destination_province_id: $('#modal-province-id').val(),
                default_price: $('#modal-default-price').val() || null,
                vehicle_prices: vehiclePrices
            };

            $.post("{{ route('b2b.pricing.routes.store') }}", payload, function (res) {
                if (res.status === 1) {
                    Swal.fire({ icon: 'success', title: '✅', text: res.success, timer: 1800, showConfirmButton: false });
                    $('#pricingModal').modal('hide');
                    setTimeout(() => location.reload(), 1800);
                } else if (res.status === 0) {
                    // Validation Errors
                    if (typeof res.error === 'object') {
                        $.each(res.error, function (key, messages) {
                            var field = $(`[name="${key}"]`);
                            if(key === 'default_price') {
                                $('#modal-default-price').addClass('is-invalid');
                                $(`.default_price-error`).text(messages[0]);
                            }
                        });
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: 'Please check the highlighted fields.',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    } else {
                        Swal.fire('خطأ', res.error, 'error');
                    }
                } else {
                    Swal.fire('خطأ', res.error || 'Something went wrong', 'error');
                }
            });
        });

    // ── Delete Route ───────────────────────────────────────────────
    $(document).on('click', '.delete-route-btn', function () {
        const routeId = $(this).data('route-id');
        Swal.fire({
            title: '{{ __("Delete this route pricing?") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __("Yes, delete") }}'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/b2b/pricing/routes') }}/${routeId}`,
                    type: 'DELETE',
                    data: { _token: "{{ csrf_token() }}" },
                    success: function (res) {
                        Swal.fire({ icon: 'success', title: '✅', text: res.success, timer: 1500, showConfirmButton: false });
                        setTimeout(() => location.reload(), 1500);
                    }
                });
            }
        });
    });

});
</script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">
        <a href="{{ route('b2b.companies') }}">{{ __('B2B Module') }}</a> /
        {{ $company->name }} /
    </span>
    {{ __('Pricing Matrix') }}
    <a href="{{ route('b2b.config.index', $company->id) }}" class="btn btn-sm btn-outline-secondary ms-2">
        <i class="ti ti-settings me-1"></i>{{ __('Commission & VAT Config') }}
    </a>
</h4>

@if($warehouses->isEmpty())
    <div class="alert alert-warning">
        <i class="ti ti-alert-triangle me-1"></i>
        {{ __('No active warehouses for this company. Please add warehouses first.') }}
        <a href="{{ route('b2b.warehouses') }}?company_id={{ $company->id }}" class="alert-link ms-2">{{ __('Manage Warehouses') }}</a>
    </div>
@else

{{-- Pricing Matrix Table --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">
            <i class="ti ti-table me-1 text-primary"></i>
            {{ __('Route Pricing Matrix (Warehouse → Province)') }}
        </h5>
        <small class="text-muted">
            <span class="badge bg-label-success me-1">{{ __('Price Set') }}</span>
            <span class="badge bg-label-warning">{{ __('No Price') }}</span>
        </small>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0" style="min-width: {{ 220 + $provinces->count() * 130 }}px;">
            <thead class="table-dark">
                <tr>
                    <th style="min-width:200px; position:sticky; left:0; z-index:2; background:#1e293b;">
                        {{ __('Warehouse \\ Province') }}
                    </th>
                    @foreach($provinces as $province)
                        <th class="text-center" style="min-width:120px;">{{ $province->name_ar }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($warehouses as $warehouse)
                <tr>
                    <td class="fw-bold" style="position:sticky; left:0; z-index:1; background:#f8fafc;">
                        <div>{{ $warehouse->name }}</div>
                        <small class="text-muted">{{ $warehouse->province->name_ar ?? '' }}</small>
                    </td>
                    @foreach($provinces as $province)
                        @php
                            $key   = $warehouse->id . '_' . $province->id;
                            $route = $matrix->get($key);
                        @endphp
                        <td class="text-center p-1 price-cell">
                            @if($route)
                                <div class="has-price">
                                    <div class="fw-bold text-success small">
                                        {{ number_format($route->default_price, 0) }} {{ __('SAR') }}
                                    </div>
                                    @if($route->vehiclePrices->count())
                                        <div class="text-muted" style="font-size:0.7rem;">
                                            {{ $route->vehiclePrices->count() }} {{ __('vehicle prices') }}
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-center gap-1 mt-1">
                                        <button class="btn btn-xs btn-icon btn-outline-primary set-price-btn"
                                            data-warehouse="{{ $warehouse->id }}"
                                            data-province="{{ $province->id }}"
                                            data-warehouse-name="{{ $warehouse->name }}"
                                            data-province-name="{{ $province->name_ar }}"
                                            data-route-id="{{ $route->id }}"
                                            title="{{ __('Edit') }}">
                                            <i class="ti ti-edit" style="font-size:0.8rem;"></i>
                                        </button>
                                        <button class="btn btn-xs btn-icon btn-outline-danger delete-route-btn"
                                            data-route-id="{{ $route->id }}"
                                            title="{{ __('Delete') }}">
                                            <i class="ti ti-trash" style="font-size:0.8rem;"></i>
                                        </button>
                                    </div>
                                </div>
                            @else
                                <button class="btn btn-xs btn-outline-secondary set-price-btn"
                                    data-warehouse="{{ $warehouse->id }}"
                                    data-province="{{ $province->id }}"
                                    data-warehouse-name="{{ $warehouse->name }}"
                                    data-province-name="{{ $province->name_ar }}"
                                    data-route-id="">
                                    <i class="ti ti-plus me-1"></i>{{ __('Set') }}
                                </button>
                            @endif
                        </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endif {{-- end warehouses check --}}

{{-- Pricing Modal --}}
<div class="modal fade" id="pricingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Set Route Price') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    <i class="ti ti-route me-1"></i>
                    <span id="modal-route-label"></span>
                </p>
                <form id="pricing-form">
                    <input type="hidden" id="modal-warehouse-id" name="warehouse_id">
                    <input type="hidden" id="modal-province-id" name="destination_province_id">
                    <input type="hidden" id="modal-route-id">

                    <div class="mb-4">
                        <label class="form-label fw-bold">{{ __('Default Price (SAR)') }}</label>
                        <input type="number" name="default_price" id="modal-default-price"
                            class="form-control" placeholder="{{ __('Applies if no vehicle-specific price matches') }}" min="0" step="0.01">
                        <span class="default_price-error text-danger small text-error"></span>
                        <small class="text-muted d-block mt-1">{{ __('Layer 4: Used when no specific vehicle price is configured.') }}</small>
                    </div>

                    <div class="divider">
                        <div class="divider-text fw-bold">{{ __('Vehicle-Specific Prices (Layer 3)') }}</div>
                    </div>
                    <p class="small text-muted mb-3">{{ __('Override the default price for specific vehicle sizes.') }}</p>

                    <div class="row">
                        @foreach($vehicleSizes as $v)
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">{{ $v->name }}</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control vehicle-price-input"
                                    data-vehicle="{{ $v->id }}" placeholder="{{ __('Price') }}" min="0" step="0.01">
                                <span class="input-group-text">{{ __('SAR') }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="ti ti-device-floppy me-1"></i>{{ __('Save Pricing') }}
                        </button>
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
