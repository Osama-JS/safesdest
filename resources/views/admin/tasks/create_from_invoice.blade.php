@extends('layouts.admin')
@section('title')
    {{ __('Create Delivery Task') }}
@endsection
@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css" rel="stylesheet" />
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('Create Delivery Task for Invoice') }} #{{ $invoice->invoice_number }}</h3>
    </div>
    <form action="{{ route('tasks.create') }}" method="POST" id="createTaskForm">
        @csrf
        <input type="hidden" name="sales_invoice_id" value="{{ $invoice->id }}">
        <input type="hidden" name="customer" value="{{ $invoice->customer_id }}">
        <input type="hidden" name="owner" value="customer">

        <!-- Hidden fields for Pickup (Product Location) -->
        <input type="hidden" name="points[0][lat]" value="{{ $pickup_point['latitude'] }}">
        <input type="hidden" name="points[0][lng]" value="{{ $pickup_point['longitude'] }}">
        <input type="hidden" name="points[0][address]" value="{{ $pickup_point['address'] }}">
        <input type="hidden" name="points[0][type]" value="pickup">

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> {{ __('Order Details') }}</h5>
                        <p><strong>{{ __('Product') }}:</strong> {{ $product->name }}</p>
                        <p><strong>{{ __('Quantity') }}:</strong> {{ $detail->quantity ?? $invoice->details->sum('quantity') }}</p>
                        <p><strong>{{ __('Pickup Location') }}:</strong> {{ $pickup_point['address'] }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('Vehicle Size') }} <span class="text-danger">*</span></label>
                        <select name="vehicles[0][vehicle_size]" class="form-control select2" required>
                            <option value="">{{ __('Select Vehicle Size') }}</option>
                            @foreach($vehicles as $vehicle)
                                <optgroup label="{{ $vehicle->name }}">
                                    @foreach($vehicle->types as $type)
                                        @foreach($type->sizes as $size)
                                            <option value="{{ $size->id }}">{{ $size->name }}</option>
                                        @endforeach
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <input type="hidden" name="vehicles[0][quantity]" value="1">
                    </div>

                    <div class="form-group mt-3">
                        <label>{{ __('Template') }} <span class="text-danger">*</span></label>
                        <select name="template" class="form-control select2" required>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <hr>
            <h4>{{ __('Delivery Location') }}</h4>
            <div class="row">
                <div class="col-md-12">
                     <div id="map" style="height: 400px; width: 100%;"></div>
                     <input type="hidden" name="points[1][lat]" id="delivery_lat">
                     <input type="hidden" name="points[1][lng]" id="delivery_lng">
                     <input type="hidden" name="points[1][address]" id="delivery_address">
                     <input type="hidden" name="points[1][type]" value="delivery">
                </div>
            </div>

            <hr>
            <h4>{{ __('Pricing') }}</h4>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('Total Price') }}</label>
                        <input type="number" name="manual_total_pricing" class="form-control" value="{{ $invoice->delivery_fee > 0 ? $invoice->delivery_fee : '' }}" placeholder="Enter delivery price">
                        <small class="text-muted">{{ __('Leave empty to calculate automatically if configured') }}</small>
                    </div>
                </div>
            </div>

        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ __('Create Task') }}</button>
        </div>
    </form>
</div>
@endsection

@section('vendor-script')
    <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
    <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('script')
<script>
    $(document).ready(function() {
        $('.select2').select2();

        mapboxgl.accessToken = '{{ config('services.mapbox.public_token') ?? "pk.eyJ1Ijoib3NhbWE5NjMiLCJhIjoiY2xrbm9kM245MGFqbjNmbzhzb3d3a3I1NiJ9.A3W5yXnQ_JgDk6d-6hXjXQ" }}';
        var map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v11',
            center: [{{ $pickup_point['longitude'] ?? 46.6753 }}, {{ $pickup_point['latitude'] ?? 24.7136 }}],
            zoom: 10
        });

        var marker = new mapboxgl.Marker({
            draggable: true
        })
        .setLngLat([{{ $pickup_point['longitude'] ?? 46.6753 }}, {{ $pickup_point['latitude'] ?? 24.7136 }}])
        .addTo(map);

        function onDragEnd() {
            var lngLat = marker.getLngLat();
            $('#delivery_lat').val(lngLat.lat);
            $('#delivery_lng').val(lngLat.lng);

            // Reverse geocoding to get address (optional, simplified here)
            // You might want to use Mapbox Geocoding API here
        }

        marker.on('dragend', onDragEnd);

        // Initialize with center
        onDragEnd();

        map.addControl(new MapboxGeocoder({
            accessToken: mapboxgl.accessToken,
            mapboxgl: mapboxgl,
            marker: false,
        }));

        // Add click listener
        map.on('click', function(e) {
            marker.setLngLat(e.lngLat);
            onDragEnd();
        });
    });
</script>
@endsection
