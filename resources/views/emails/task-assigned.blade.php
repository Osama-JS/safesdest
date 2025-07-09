@extends('emails.notification')

@section('content')
<div class="greeting">
    مرحباً {{ $user_name }}،
</div>

<div class="content">
    تم تعيين مهمة جديدة لك في نظام {{ config('app.name') }}.
</div>

@if(isset($additional_data))
    <div class="info-box">
        <h3>تفاصيل المهمة</h3>
        @foreach($additional_data as $key => $value)
            <div class="info-item">
                <span class="info-label">{{ $key }}:</span>
                <span class="info-value">{{ $value }}</span>
            </div>
        @endforeach
    </div>
@endif

<div class="content">
    يرجى مراجعة تفاصيل المهمة والبدء في تنفيذها في الوقت المحدد.
</div>

@if(isset($action_url))
    <div class="action-section">
        <a href="{{ $action_url }}" class="action-button">
            عرض تفاصيل المهمة
        </a>
    </div>
@endif
@endsection
