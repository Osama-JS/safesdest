@extends('layouts/layoutMaster')

@section('title', __('إشعاراتي'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item">
                <a href="{{ url('admin') }}"><i class="ti ti-home-2 me-1"></i>{{ __('الرئيسية') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('إشعاراتي') }}</li>
        </ol>
    </nav>

    <!-- Header Banner (Personal Notifications Inbox) -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, rgba(115, 103, 240, 0.09) 0%, rgba(115, 103, 240, 0.02) 100%);">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-xl bg-primary text-white rounded-3 shadow-sm d-flex align-items-center justify-content-center p-2">
                        <i class="ti ti-bell-ringing fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <h4 class="fw-bold mb-0 text-heading">{{ __('صندوق إشعاراتي') }}</h4>
                            @if($unreadCount > 0)
                                <span class="badge bg-danger rounded-pill px-3 py-1 fs-tiny fw-semibold animate__animated animate__pulse animate__infinite">
                                    <i class="ti ti-mail-spark me-1"></i> {{ $unreadCount }} {{ __('غير مقروء') }}
                                </span>
                            @else
                                <span class="badge bg-label-success rounded-pill px-3 py-1 fs-tiny fw-semibold">
                                    <i class="ti ti-circle-check me-1"></i> {{ __('تمت قراءة جميع الإشعارات') }}
                                </span>
                            @endif
                        </div>
                        <p class="text-muted mb-0">
                            {{ __('سجل التنبيهات والإشعارات التشغيلية والمالية التي وردت إلى حسابك مع إمكانية البحث والفرز الفوري.') }}
                        </p>
                    </div>
                </div>

                <!-- Quick Action Buttons -->
                <div class="d-flex gap-2 flex-wrap">
                    @if($unreadCount > 0)
                        <button type="button" class="btn btn-primary shadow-sm" id="btnMarkAllRead">
                            <i class="ti ti-mail-opened me-1"></i> {{ __('تحديد الكل كمقروء') }}
                        </button>
                    @endif
                    @if($readCount > 0)
                        <button type="button" class="btn btn-label-danger shadow-sm" id="btnDeleteAllRead">
                            <i class="ti ti-trash me-1"></i> {{ __('حذف المقروءة') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards Row -->
    <div class="row g-3 mb-4">
        <!-- Total Notifications -->
        <div class="col-6 col-md-3">
            <a href="{{ route('system.notifications.all') }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 stat-card {{ !request('status') && !request('category') ? 'border-primary border-bottom border-3' : '' }}">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted d-block small mb-1">{{ __('إجمالي إشعاراتي') }}</span>
                                <h4 class="fw-bold mb-0 text-heading">{{ number_format($totalCount) }}</h4>
                                <small class="text-primary"><i class="ti ti-inbox me-1"></i>{{ __('كافة التنبيهات') }}</small>
                            </div>
                            <div class="avatar avatar-md bg-label-primary rounded">
                                <i class="ti ti-bell fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Unread Notifications -->
        <div class="col-6 col-md-3">
            <a href="{{ route('system.notifications.all', ['status' => 'unread']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 stat-card {{ request('status') === 'unread' ? 'border-danger border-bottom border-3' : '' }}">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted d-block small mb-1">{{ __('غير مقروءة') }}</span>
                                <h4 class="fw-bold mb-0 {{ $unreadCount > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($unreadCount) }}
                                </h4>
                                <small class="{{ $unreadCount > 0 ? 'text-danger' : 'text-success' }}">
                                    <i class="ti {{ $unreadCount > 0 ? 'ti-alert-circle' : 'ti-circle-check' }} me-1"></i>
                                    {{ $unreadCount > 0 ? __('تنتظر اطلاعك') : __('سجلك محدث') }}
                                </small>
                            </div>
                            <div class="avatar avatar-md bg-label-danger rounded">
                                <i class="ti ti-mail-spark fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Today's Notifications -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 stat-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted d-block small mb-1">{{ __('إشعارات اليوم') }}</span>
                            <h4 class="fw-bold mb-0 text-heading">{{ number_format($todayCount) }}</h4>
                            <small class="text-info"><i class="ti ti-calendar me-1"></i>{{ __('آخر 24 ساعة') }}</small>
                        </div>
                        <div class="avatar avatar-md bg-label-info rounded">
                            <i class="ti ti-calendar-event fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Notifications -->
        <div class="col-6 col-md-3">
            <a href="{{ route('system.notifications.all', ['category' => 'financial']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm h-100 stat-card {{ request('category') === 'financial' ? 'border-warning border-bottom border-3' : '' }}">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted d-block small mb-1">{{ __('إشعارات مالية') }}</span>
                                <h4 class="fw-bold mb-0 text-heading">{{ number_format($financialCount) }}</h4>
                                <small class="text-warning"><i class="ti ti-wallet me-1"></i>{{ __('سحب ودفعات') }}</small>
                            </div>
                            <div class="avatar avatar-md bg-label-warning rounded">
                                <i class="ti ti-wallet fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('system.notifications.all') }}" id="notificationsFilterForm">
                <div class="row g-2 align-items-center">
                    <!-- Status Filter Segment Pills -->
                    <div class="col-12 col-lg-4">
                        <div class="btn-group w-100" role="group" aria-label="Status filter">
                            <a href="{{ route('system.notifications.all', array_merge(request()->except(['status', 'page']), [])) }}" 
                               class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-primary' }}">
                                <i class="ti ti-layout-grid me-1"></i>{{ __('الكل') }} ({{ $totalCount }})
                            </a>
                            <a href="{{ route('system.notifications.all', array_merge(request()->except(['status', 'page']), ['status' => 'unread'])) }}" 
                               class="btn btn-sm {{ request('status') === 'unread' ? 'btn-danger' : 'btn-outline-danger' }}">
                                <i class="ti ti-mail me-1"></i>{{ __('غير مقروءة') }} ({{ $unreadCount }})
                            </a>
                            <a href="{{ route('system.notifications.all', array_merge(request()->except(['status', 'page']), ['status' => 'read'])) }}" 
                               class="btn btn-sm {{ request('status') === 'read' ? 'btn-success' : 'btn-outline-success' }}">
                                <i class="ti ti-mail-opened me-1"></i>{{ __('مقروءة') }} ({{ $readCount }})
                            </a>
                        </div>
                    </div>

                    <!-- Category Filter Dropdown -->
                    <div class="col-6 col-lg-3">
                        <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">{{ __('جميع التصنيفات والأحداث') }}</option>
                            <option value="financial" {{ request('category') === 'financial' ? 'selected' : '' }}>
                                💰 {{ __('عمليات مالية (سحب، دفعات)') }}
                            </option>
                            <option value="tasks" {{ request('category') === 'tasks' ? 'selected' : '' }}>
                                📦 {{ __('المهام والطلبات') }}
                            </option>
                            <option value="users" {{ request('category') === 'users' ? 'selected' : '' }}>
                                👥 {{ __('المستخدمين والسائقين') }}
                            </option>
                            <option value="system" {{ request('category') === 'system' ? 'selected' : '' }}>
                                ⚙️ {{ __('النظام والتنبيهات العامة') }}
                            </option>
                        </select>
                    </div>

                    <!-- Search Input -->
                    <div class="col-6 col-lg-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                   placeholder="{{ __('بحث في العنوان أو النص...') }}" 
                                   value="{{ request('search') }}">
                            @if(request('search'))
                                <a href="{{ route('system.notifications.all', request()->except('search')) }}" class="input-group-text bg-transparent border-start-0 text-muted" title="{{ __('مسح البحث') }}">
                                    <i class="ti ti-x fs-6"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Submit / Reset Buttons -->
                    <div class="col-12 col-lg-2 d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                            <i class="ti ti-filter me-1"></i> {{ __('تصفية') }}
                        </button>
                        @if(request()->hasAny(['search', 'status', 'category']))
                            <a href="{{ route('system.notifications.all') }}" class="btn btn-sm btn-label-secondary" title="{{ __('إعادة تعيين الفلاتر') }}">
                                <i class="ti ti-refresh"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications List Container -->
    <div class="card border-0 shadow-sm">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center bg-transparent">
            <div class="d-flex align-items-center gap-2">
                <i class="ti ti-list text-primary fs-4"></i>
                <h5 class="card-title mb-0 fw-bold text-heading">{{ __('قائمة الإشعارات') }}</h5>
                <span class="badge bg-label-primary rounded-pill">{{ $notifications->total() }}</span>
            </div>
            <div class="d-none d-sm-block text-muted small">
                <i class="ti ti-info-circle me-1"></i>{{ __('انقر على الإشعار لتمييزه أو عرض تفاصيله') }}
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            @if($notifications->isEmpty())
                <!-- Clean Empty State -->
                <div class="text-center py-5">
                    <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px;">
                        <i class="ti ti-bell-off fs-1 text-muted"></i>
                    </div>
                    <h5 class="fw-bold mb-1 text-heading">{{ __('لا توجد إشعارات مطابقة') }}</h5>
                    <p class="text-muted mb-3 fs-6">
                        @if(request()->hasAny(['search', 'status', 'category']))
                            {{ __('لم يتم العثور على أي نتائج تطابق معايير الفلترة المحددة.') }}
                        @else
                            {{ __('صندوق إشعاراتك فارغ حالياً، ستظهر هنا جميع الإشعارات فور وصولها.') }}
                        @endif
                    </p>
                    @if(request()->hasAny(['search', 'status', 'category']))
                        <a href="{{ route('system.notifications.all') }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-rotate-clockwise me-1"></i> {{ __('عرض كافة الإشعارات') }}
                        </a>
                    @endif
                </div>
            @else
                <!-- Notification Cards Grid / List -->
                <div class="row g-3" id="notificationsListGroup">
                    @foreach($notifications as $item)
                        @php
                            $notif = $item->notification;
                            $isRead = $item->status;
                            $eventKey = $notif?->event_key;
                            
                            // Category Badge & Theme
                            $categoryInfo = match(true) {
                                in_array($eventKey, ['driver_withdrawal_requested', 'payout_approval_required', 'payout_status_updated']) => [
                                    'label' => __('مالية'),
                                    'badge_class' => 'bg-label-warning',
                                    'avatar_bg' => 'bg-label-warning text-warning',
                                    'icon' => 'ti-wallet',
                                    'border_accent' => 'border-warning'
                                ],
                                in_array($eventKey, ['task_created', 'task_cancellation_requested', 'task_status_changed', 'task_offer_created', 'task_offer_accepted']) => [
                                    'label' => __('المهام'),
                                    'badge_class' => 'bg-label-info',
                                    'avatar_bg' => 'bg-label-info text-info',
                                    'icon' => 'ti-package',
                                    'border_accent' => 'border-info'
                                ],
                                in_array($eventKey, ['customer_registered', 'driver_registered', 'team_created']) => [
                                    'label' => __('المستخدمين'),
                                    'badge_class' => 'bg-label-success',
                                    'avatar_bg' => 'bg-label-success text-success',
                                    'icon' => 'ti-user',
                                    'border_accent' => 'border-success'
                                ],
                                default => [
                                    'label' => __('عام'),
                                    'badge_class' => 'bg-label-secondary',
                                    'avatar_bg' => 'bg-label-secondary text-secondary',
                                    'icon' => 'ti-bell',
                                    'border_accent' => 'border-secondary'
                                ]
                            };

                            $displayIcon = $notif?->icon ?: $categoryInfo['icon'];
                        @endphp

                        <div class="col-12" id="notif-row-{{ $item->id }}">
                            <div class="card notif-card h-100 transition-all border {{ !$isRead ? 'border-primary border-start border-3 bg-label-primary bg-opacity-10 shadow-sm' : 'border-light shadow-none bg-body' }} p-3">
                                <div class="d-flex align-items-start gap-3">
                                    <!-- Avatar Icon -->
                                    <div class="avatar avatar-md flex-shrink-0 mt-1">
                                        <span class="avatar-initial rounded-circle {{ !$isRead ? 'bg-primary text-white shadow-sm' : $categoryInfo['avatar_bg'] }}">
                                            <i class="ti {{ $displayIcon }} fs-4"></i>
                                        </span>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-1 mb-1">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <h6 class="mb-0 fw-bold text-heading fs-6 text-truncate">
                                                    {{ $notif?->title ?? __('تنبيه جديد') }}
                                                </h6>
                                                <span class="badge {{ $categoryInfo['badge_class'] }} rounded-pill px-2 py-0 fs-tiny fw-semibold">
                                                    {{ $categoryInfo['label'] }}
                                                </span>
                                                @if(!$isRead)
                                                    <span class="badge bg-danger rounded-pill px-2 py-0 fs-tiny unread-pill">
                                                        <i class="ti ti-point-filled"></i> {{ __('جديد') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <small class="text-muted d-flex align-items-center flex-shrink-0" title="{{ $item->created_at->format('Y-m-d H:i:s') }}">
                                                <i class="ti ti-clock me-1 fs-tiny"></i>
                                                <span>{{ $item->created_at->diffForHumans() }}</span>
                                                <span class="d-none d-lg-inline ms-1 text-muted opacity-75">({{ $item->created_at->format('h:i A') }})</span>
                                            </small>
                                        </div>

                                        <p class="mb-3 text-body small" style="white-space: pre-line; line-height: 1.6;">
                                            {{ $notif?->message ?? '' }}
                                        </p>

                                        <!-- Card Footer Actions -->
                                        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light">
                                            <div>
                                                @if($notif?->action_url)
                                                    <a href="{{ $notif->action_url }}" class="btn btn-xs btn-primary btn-action-go shadow-xs me-2" data-id="{{ $item->id }}">
                                                        <i class="ti ti-arrow-left me-1"></i> {{ __('عرض التفاصيل') }}
                                                    </a>
                                                @endif
                                            </div>

                                            <div class="d-flex align-items-center gap-1">
                                                <!-- Toggle Read/Unread -->
                                                <button type="button" 
                                                        class="btn btn-xs {{ $isRead ? 'btn-label-secondary' : 'btn-label-primary' }} btn-toggle-read" 
                                                        data-id="{{ $item->id }}" 
                                                        title="{{ $isRead ? __('تحديد كغير مقروء') : __('تحديد كمقروء') }}">
                                                    <i class="ti {{ $isRead ? 'ti-mail' : 'ti-mail-opened' }} me-1"></i>
                                                    <span class="toggle-text">{{ $isRead ? __('تحديد كغير مقروء') : __('تحديد كمقروء') }}</span>
                                                </button>

                                                <!-- Delete Notification -->
                                                <button type="button" 
                                                        class="btn btn-xs btn-label-danger btn-delete-notif" 
                                                        data-id="{{ $item->id }}" 
                                                        title="{{ __('حذف الإشعار') }}">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Modern Pagination Footer -->
        @if($notifications->hasPages())
            <div class="card-footer border-top py-3 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 bg-transparent">
                <div class="text-muted small">
                    {{ __('عرض') }} 
                    <span class="fw-semibold">{{ $notifications->firstItem() }}</span> 
                    {{ __('إلى') }} 
                    <span class="fw-semibold">{{ $notifications->lastItem() }}</span> 
                    {{ __('من أصل') }} 
                    <span class="fw-semibold">{{ $notifications->total() }}</span> 
                    {{ __('إشعار') }}
                </div>
                <div>
                    {{ $notifications->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>

<style>
/* Notification Cards Styling */
.notif-card {
    border-radius: 0.625rem;
    transition: all 0.2s ease-in-out;
}
.notif-card:hover {
    box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.08) !important;
    transform: translateY(-2px);
}
.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.75rem rgba(115, 103, 240, 0.15) !important;
}
.unread-pill {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>
@endsection

@section('page-script')
<script>
$(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const apiBase = "{{ url('admin/system-notifications') }}";

    // 1. Mark All As Read
    $('#btnMarkAllRead').on('click', function () {
        Swal.fire({
            title: "{{ __('تحديد الكل كمقروء') }}",
            text: "{{ __('هل أنت متأكد من رغبتك في تحديد جميع إشعاراتك كمقروءة؟') }}",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: "{{ __('نعم، حدد الكل') }}",
            cancelButtonText: "{{ __('إلغاء') }}",
            customClass: {
                confirmButton: 'btn btn-primary me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${apiBase}/mark-all-read`,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function (res) {
                        if (res.success) {
                            window.location.reload();
                        }
                    }
                });
            }
        });
    });

    // 2. Delete All Read
    $('#btnDeleteAllRead').on('click', function () {
        Swal.fire({
            title: "{{ __('حذف الإشعارات المقروءة') }}",
            text: "{{ __('هل تريد حذف جميع الإشعارات التي قمت بقراءتها من صندوقك؟') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: "{{ __('نعم، احذف المقروء') }}",
            cancelButtonText: "{{ __('إلغاء') }}",
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${apiBase}/delete-all-read`,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function (res) {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    }
                });
            }
        });
    });

    // 3. Toggle Single Read / Unread
    $(document).on('click', '.btn-toggle-read', function () {
        const btn = $(this);
        const id = btn.data('id');
        const row = $(`#notif-row-${id}`);
        const card = row.find('.notif-card');

        $.ajax({
            url: `${apiBase}/${id}/toggle-read`,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (res.success) {
                    if (res.is_read) {
                        card.removeClass('border-primary border-start border-3 bg-label-primary bg-opacity-10 shadow-sm')
                            .addClass('border-light shadow-none bg-body');
                        card.find('.unread-pill').remove();
                        btn.removeClass('btn-label-primary').addClass('btn-label-secondary');
                        btn.find('i').removeClass('ti-mail-opened').addClass('ti-mail');
                        btn.find('.toggle-text').text("{{ __('تحديد كغير مقروء') }}");
                    } else {
                        card.removeClass('border-light shadow-none bg-body')
                            .addClass('border-primary border-start border-3 bg-label-primary bg-opacity-10 shadow-sm');
                        btn.removeClass('btn-label-secondary').addClass('btn-label-primary');
                        btn.find('i').removeClass('ti-mail').addClass('ti-mail-opened');
                        btn.find('.toggle-text').text("{{ __('تحديد كمقروء') }}");
                    }
                }
            }
        });
    });

    // 4. Delete Single Notification
    $(document).on('click', '.btn-delete-notif', function () {
        const btn = $(this);
        const id = btn.data('id');
        const row = $(`#notif-row-${id}`);

        Swal.fire({
            title: "{{ __('حذف الإشعار') }}",
            text: "{{ __('هل أنت متأكد من حذف هذا الإشعار من صندوقك؟') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: "{{ __('نعم، احذف') }}",
            cancelButtonText: "{{ __('إلغاء') }}",
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-label-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `${apiBase}/${id}/delete`,
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken },
                    success: function (res) {
                        if (res.success) {
                            row.fadeOut(300, function () {
                                $(this).remove();
                            });
                        }
                    }
                });
            }
        });
    });

    // 5. Click Action Button (mark read before redirect)
    $(document).on('click', '.btn-action-go', function () {
        const id = $(this).data('id');
        if (id) {
            $.ajax({
                url: `${apiBase}/${id}/read`,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
        }
    });
});
</script>
@endsection
