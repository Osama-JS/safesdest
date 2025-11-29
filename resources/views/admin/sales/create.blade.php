@extends('layouts/layoutMaster')
@section('title')
    {{ __('Create Purchase Order') }}
@endsection
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('New Purchase Order') }}</h3>
        </div>
        <form action="{{ route('sales.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('Customer') }} <span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-control select2" required>
                                <option value="">{{ __('Select Customer') }}</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('Payment Method') }}</label>
                            <select name="payment_method" class="form-control">
                                <option value="">{{ __('Select Method') }}</option>
                                <option value="cash">{{ __('Cash') }}</option>
                                <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                                <option value="wallet">{{ __('Wallet') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>
                <h4>{{ __('Product Details') }}</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>{{ __('Product') }} <span class="text-danger">*</span></label>
                            <select name="product_id" id="product_id" class="form-control select2" required>
                                <option value="">{{ __('Select Product') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('Unit Price') }}</label>
                            <input type="text" id="unit_price" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('Quantity') }} <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="1" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-right">
                        <h3>{{ __('Total') }}: <span id="total_display">0.00</span></h3>
                    </div>
                </div>

                <div class="form-group">
                    <label>{{ __('Notes') }}</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">{{ __('Create Invoice') }}</button>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $('.select2').select2();

            function calculateTotal() {
                var price = parseFloat($('#product_id option:selected').data('price')) || 0;
                var qty = parseInt($('#quantity').val()) || 0;
                var total = price * qty;

                $('#unit_price').val(price.toFixed(2));
                $('#total_display').text(total.toFixed(2));
            }

            $('#product_id, #quantity').on('change keyup', calculateTotal);
        });
    </script>
@endsection
