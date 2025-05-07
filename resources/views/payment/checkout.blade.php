@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Content navbar - Layouts')

@section('content')

    <div class="container">
        <h2>اختيار طريقة الدفع</h2>
        <form action="{{ route('payment.initiate') }}" method="POST">
            @csrf
            <label for="amount">المبلغ:</label>
            <input type="number" name="amount" required>

            <button type="submit">إتمام الدفع</button>
        </form>
    </div>
@endsection
