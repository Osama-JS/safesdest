@extends('layouts/layoutMaster')
@section('title')
    {{ __('Invoice') }} #{{ $invoice->invoice_number }}
@endsection
@section('content')
    <div class="invoice p-3 mb-3">
        <!-- title row -->
        <div class="row">
            <div class="col-12">
                <h4>
                    <i class="fas fa-globe"></i> {{ config('app.name') }}
                    <small class="float-right">{{ __('Date') }}: {{ $invoice->created_at->format('d/m/Y') }}</small>
                </h4>
            </div>
        </div>
        <!-- info row -->
        <div class="row invoice-info">
            <div class="col-sm-4 invoice-col">
                {{ __('From') }}
                <address>
                    <strong>{{ config('app.name') }}</strong><br>
                    {{-- Add company address here --}}
                </address>
            </div>
            <div class="col-sm-4 invoice-col">
                {{ __('To') }}
                <address>
                    <strong>{{ $invoice->customer->name }}</strong><br>
                    {{ $invoice->customer->address }}<br>
                    {{ __('Phone') }}: {{ $invoice->customer->phone }}<br>
                    {{ __('Email') }}: {{ $invoice->customer->email }}
                </address>
            </div>
            <div class="col-sm-4 invoice-col">
                <b>{{ __('Invoice #') }}{{ $invoice->invoice_number }}</b><br>
                <br>
                <b>{{ __('Order ID') }}:</b> {{ $invoice->id }}<br>
                <b>{{ __('Payment Due') }}:</b> {{ $invoice->created_at->format('d/m/Y') }}<br>
                <b>{{ __('Status') }}:</b>
                <span class="badge badge-{{ $invoice->status == 'paid' ? 'success' : ($invoice->status == 'pending' ? 'warning' : 'danger') }}">
                    {{ ucfirst($invoice->status) }}
                </span>
            </div>
        </div>

        <!-- Table row -->
        <div class="row">
            <div class="col-12 table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Qty') }}</th>
                            <th>{{ __('Unit Price') }}</th>
                            <th>{{ __('Subtotal') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->details as $detail)
                            <tr>
                                <td>{{ $detail->product_name }}</td>
                                <td>{{ $detail->quantity }}</td>
                                <td>{{ number_format($detail->unit_price, 2) }}</td>
                                <td>{{ number_format($detail->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <p class="lead">{{ __('Payment Methods') }}:</p>
                <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                    {{ $invoice->payment_method ?? __('Not Selected') }}
                </p>
                @if($invoice->notes)
                    <p class="lead">{{ __('Notes') }}:</p>
                    <p class="text-muted well well-sm shadow-none">
                        {{ $invoice->notes }}
                    </p>
                @endif
            </div>
            <div class="col-6">
                <p class="lead">{{ __('Amount Due') }} {{ $invoice->created_at->format('d/m/Y') }}</p>

                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th style="width:50%">{{ __('Subtotal') }}:</th>
                            <td>{{ number_format($invoice->total_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Tax') }}:</th>
                            <td>{{ number_format($invoice->tax_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Delivery Fee') }}:</th>
                            <td>{{ number_format($invoice->delivery_fee, 2) }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('Total') }}:</th>
                            <td>{{ number_format($invoice->final_total, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- this row will not appear when printing -->
        <div class="row no-print">
            <div class="col-12">
                <a href="javascript:window.print()" class="btn btn-default"><i class="fas fa-print"></i> {{ __('Print') }}</a>

                @if($invoice->status == 'pending')
                    <form action="{{ route('sales.status', $invoice->id) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="status" value="paid">
                        <button type="submit" class="btn btn-success float-right"><i class="far fa-credit-card"></i> {{ __('Submit Payment') }}</button>
                    </form>
                @endif

                @if($invoice->status == 'paid' && $invoice->tasks->count() == 0)
                    <a href="{{ route('sales.create_task', $invoice->id) }}" class="btn btn-primary float-right" style="margin-right: 5px;">
                        <i class="fas fa-truck"></i> {{ __('Create Delivery Task') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection
