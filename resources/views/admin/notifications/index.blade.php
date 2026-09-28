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
                        <button type="button" class="btn btn-primary shadow-sm" id="btnMarkAllRead" data-url="{{ route('system.notifications.mark-all-read') }}">
                            <i class="ti ti-mail-opened me-1"></i> {{ __('تحديد الكل كمقروء') }}
                        </button>
                    @endif
                    @if($readCount > 0)
                        <button type="button" class="btn btn-label-danger shadow-sm" id="btnDeleteAllRead" data-url="{{ route('system.notifications.delete-all-read') }}">
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
                <i class="ti ti-info-circle me-1"></i>{{ __('انقر على أزرار الإجراء لتمييز الإشعار أو حذفه') }}
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
                                                    <a href="{{ $notif->action_url }}" class="btn btn-xs btn-primary btn-action-go shadow-xs me-2" data-id="{{ $item->id }}" data-url="{{ route('system.notifications.read', $item->id) }}">
                                                        <i class="ti ti-arrow-left me-1"></i> {{ __('عرض التفاصيل') }}
                                                    </a>
                                                @endif
                                            </div>

                                            <div class="d-flex align-items-center gap-1">
                                                <!-- Toggle Read/Unread -->
                                                <button type="button" 
                                                        class="btn btn-xs {{ $isRead ? 'btn-label-secondary' : 'btn-label-primary' }} btn-toggle-read" 
                                                        data-id="{{ $item->id }}" 
                                                        data-url="{{ route('system.notifications.toggle-read', $item->id) }}"
                                                        title="{{ $isRead ? __('تحديد كغير مقروء') : __('تحديد كمقروء') }}">
                                                    <i class="ti {{ $isRead ? 'ti-mail' : 'ti-mail-opened' }} me-1"></i>
                                                    <span class="toggle-text">{{ $isRead ? __('تحديد كغير مقروء') : __('تحديد كمقروء') }}</span>
                                                </button>

                                                <!-- Delete Notification -->
                                                <button type="button" 
                                                        class="btn btn-xs btn-label-danger btn-delete-notif" 
                                                        data-id="{{ $item->id }}" 
                                                        data-url="{{ route('system.notifications.delete', $item->id) }}"
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

<script>
(function () {
    const csrfToken = '{{ csrf_token() }}';

    function showConfirm(title, text, confirmText, isDanger, onConfirm) {
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({
                title: title,
                text: text,
                icon: isDanger ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: '{{ __("إلغاء") }}',
                customClass: {
                    confirmButton: isDanger ? 'btn btn-danger me-2' : 'btn btn-primary me-2',
                    cancelButton: 'btn btn-label-secondary'
                },
                buttonsStyling: false
            }).then(function (result) {
                if (result.isConfirmed) {
                    onConfirm();
                }
            });
        } else {
            if (window.confirm(text || title)) {
                onConfirm();
            }
        }
    }

    function showFeedback(icon, message, callback) {
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({
                icon: icon,
                title: message,
                timer: 1500,
                showConfirmButton: false
            }).then(function () {
                if (callback) callback();
            });
        } else {
            if (callback) callback();
        }
    }

    // Event delegation on document (Zero dependency on jQuery load order!)
    document.addEventListener('click', function (e) {
        // 1. Mark All As Read
        const markAllBtn = e.target.closest('#btnMarkAllRead');
        if (markAllBtn) {
            e.preventDefault();
            const url = markAllBtn.getAttribute('data-url');
            showConfirm(
                '{{ __("تحديد الكل كمقروء") }}',
                '{{ __("هل أنت متأكد من رغبتك في تحديد جميع إشعاراتك كمقروءة؟") }}',
                '{{ __("نعم، حدد الكل") }}',
                false,
                function () {
                    markAllBtn.disabled = true;
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ _token: csrfToken })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showFeedback('success', data.message || '{{ __("تم تحديد الكل كمقروء") }}', function () {
                                window.location.reload();
                            });
                        } else {
                            alert(data.message || 'Error');
                            markAllBtn.disabled = false;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        markAllBtn.disabled = false;
                        alert('حدث خطأ في الاتصال بالخادم');
                    });
                }
            );
            return;
        }

        // 2. Delete All Read
        const deleteAllReadBtn = e.target.closest('#btnDeleteAllRead');
        if (deleteAllReadBtn) {
            e.preventDefault();
            const url = deleteAllReadBtn.getAttribute('data-url');
            showConfirm(
                '{{ __("حذف الإشعارات المقروءة") }}',
                '{{ __("هل تريد حذف جميع الإشعارات التي قمت بقراءتها من صندوقك؟") }}',
                '{{ __("نعم، احذف المقروء") }}',
                true,
                function () {
                    deleteAllReadBtn.disabled = true;
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ _token: csrfToken })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            showFeedback('success', data.message || '{{ __("تم حذف جميع الإشعارات المقروءة بنجاح") }}', function () {
                                window.location.reload();
                            });
                        } else {
                            alert(data.message || 'Error');
                            deleteAllReadBtn.disabled = false;
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        deleteAllReadBtn.disabled = false;
                        alert('حدث خطأ في الاتصال بالخادم');
                    });
                }
            );
            return;
        }

        // 3. Toggle Single Read / Unread
        const toggleBtn = e.target.closest('.btn-toggle-read');
        if (toggleBtn) {
            e.preventDefault();
            const url = toggleBtn.getAttribute('data-url');
            const id = toggleBtn.getAttribute('data-id');
            const row = document.getElementById('notif-row-' + id);
            const card = row ? row.querySelector('.notif-card') : null;
            const originalHtml = toggleBtn.innerHTML;

            toggleBtn.disabled = true;
            toggleBtn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width: 12px; height: 12px;"></span>';

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ _token: csrfToken })
            })
            .then(res => res.json())
            .then(data => {
                toggleBtn.disabled = false;
                if (data.success) {
                    if (data.is_read) {
                        if (card) {
                            card.classList.remove('border-primary', 'border-start', 'border-3', 'bg-label-primary', 'bg-opacity-10', 'shadow-sm');
                            card.classList.add('border-light', 'shadow-none', 'bg-body');
                            const unreadPill = card.querySelector('.unread-pill');
                            if (unreadPill) unreadPill.remove();
                        }
                        toggleBtn.className = 'btn btn-xs btn-label-secondary btn-toggle-read';
                        toggleBtn.title = '{{ __("تحديد كغير مقروء") }}';
                        toggleBtn.innerHTML = '<i class="ti ti-mail me-1"></i><span class="toggle-text">{{ __("تحديد كغير مقروء") }}</span>';
                    } else {
                        if (card) {
                            card.classList.remove('border-light', 'shadow-none', 'bg-body');
                            card.classList.add('border-primary', 'border-start', 'border-3', 'bg-label-primary', 'bg-opacity-10', 'shadow-sm');
                        }
                        toggleBtn.className = 'btn btn-xs btn-label-primary btn-toggle-read';
                        toggleBtn.title = '{{ __("تحديد كمقروء") }}';
                        toggleBtn.innerHTML = '<i class="ti ti-mail-opened me-1"></i><span class="toggle-text">{{ __("تحديد كمقروء") }}</span>';
                    }
                } else {
                    toggleBtn.innerHTML = originalHtml;
                    alert(data.message || 'Error');
                }
            })
            .catch(err => {
                console.error(err);
                toggleBtn.disabled = false;
                toggleBtn.innerHTML = originalHtml;
                alert('حدث خطأ في تحديث حالة الإشعار');
            });
            return;
        }

        // 4. Delete Single Notification
        const deleteBtn = e.target.closest('.btn-delete-notif');
        if (deleteBtn) {
            e.preventDefault();
            const url = deleteBtn.getAttribute('data-url');
            const id = deleteBtn.getAttribute('data-id');
            const row = document.getElementById('notif-row-' + id);

            showConfirm(
                '{{ __("حذف الإشعار") }}',
                '{{ __("هل أنت متأكد من حذف هذا الإشعار من صندوقك؟") }}',
                '{{ __("نعم، احذف") }}',
                true,
                function () {
                    deleteBtn.disabled = true;
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ _token: csrfToken, _method: 'DELETE' })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (row) {
                                row.style.transition = 'all 0.3s ease';
                                row.style.opacity = '0';
                                row.style.transform = 'scale(0.95)';
                                setTimeout(function () {
                                    row.remove();
                                    const list = document.getElementById('notificationsListGroup');
                                    if (list && list.children.length === 0) {
                                        window.location.reload();
                                    }
                                }, 300);
                            }
                        } else {
                            deleteBtn.disabled = false;
                            alert(data.message || 'Error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        deleteBtn.disabled = false;
                        alert('حدث خطأ أثناء محاولة حذف الإشعار');
                    });
                }
            );
            return;
        }

        // 5. Action Go Button (mark read and let link proceed)
        const actionGoBtn = e.target.closest('.btn-action-go');
        if (actionGoBtn) {
            const url = actionGoBtn.getAttribute('data-url');
            if (url) {
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ _token: csrfToken })
                }).catch(() => {});
            }
        }
    });
})();
</script>
@endsection
