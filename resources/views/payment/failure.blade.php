@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Content navbar - Layouts')

@section('content')

    <div class="text-center mt-10">
        <h1 class="text-red-600 text-2xl font-bold">❌ فشل في عملية الدفع</h1>
        <p class="mt-4">نأسف، لم نتمكن من تنفيذ العملية. حاول مرة أخرى.</p>
        @if ($errors->has('msg'))
            <div class="mt-6 alert alert-danger text-center font-semibold">
                {{ $errors->first('msg') }}
            </div>
        @endif

        <a href="/" class="mt-4 inline-block text-blue-600 underline">العودة للرئيسية</a>
    </div>
@endsection
