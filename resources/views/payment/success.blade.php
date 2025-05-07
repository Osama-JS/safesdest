@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Content navbar - Layouts')

@section('content')

    <div class="text-center mt-10">
        <h1 class="text-green-600 text-2xl font-bold">✅ تم الدفع بنجاح!</h1>
        <p class="mt-4">شكراً لك، تم تنفيذ العملية بنجاح.</p>
        <a href="/" class="mt-4 inline-block text-blue-600 underline">العودة للرئيسية</a>
    </div>
@endsection
