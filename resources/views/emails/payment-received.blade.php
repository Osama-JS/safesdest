@extends('emails.notification')

@section('content')
<div class="greeting">
    مرحباً {{ $user_name }}،
</div>

<div class="content">
    تم استلام دفعة جديدة في حسابك بنجاح.
</div>

@if(isset($additional_data))
    <div class="info-box">
        <h3>تفاصيل الدفعة</h3>
        @foreach($additional_data as $key => $value)
            <div class="info-item">
                <span class="info-label">{{ $key }}:</span>
                <span class="info-value">{{ $value }}</span>
            </div>
        @endforeach
    </div>
@endif

<div class="content">
    يمكنك مراجعة تفاصيل المعاملة في قسم المحفظة الخاص بك.
</div>

@if(isset($action_url))
    <div class="action-section">
        <a href="{{ $action_url }}" class="action-button">
            عرض تفاصيل المحفظة
        </a>
    </div>
@endif
@endsection
