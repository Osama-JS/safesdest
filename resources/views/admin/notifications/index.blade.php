@extends('layouts/layoutMaster')

@section('title', __('جميع الإشعارات'))

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
            <li class="breadcrumb-item active">{{ __('مركز الإشعارات') }}</li>
        </ol>
    </nav>

    <!-- Hero Header Banner -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.02) 100%);">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-xl bg-primary text-white rounded-3 shadow-sm d-flex align-items-center justify-content-center p-2">
                        <i class="ti ti-bell-ringing fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <h4 class="fw-bold mb-0 text-heading">{{ __('مركز إشعارات المنصة') }}</h4>
                            <span class="badge bg-label-primary rounded-pill px-3 py-1 fs-tiny fw-semibold">
                                <i class="ti ti-inbox me-1"></i> {{ __('صندوق الوارد المباشر') }}
                            </span>
                        </div>
                        <p class="text-muted mb-0">
                            {{ __('استعراض وإدارة جميع التنبيهات التشغيلية والمالية التي وردت إلى حسابك في لوحة التحكم مع إمكانية الفرز والتصفية.') }}
                        </p>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @can('general_settings')
                    <a href="{{ route('admin.settings.notifications.index') }}" class="btn btn-label-primary shadow-sm">
                        <i class="ti ti-settings me-1"></i> {{ __('تخصيص الإعدادات') }}
                    </a>
                    @endcan
                    @if($unreadCount > 0)
                    <button type="button" class="btn btn-primary shadow-sm" id="btnMarkAllRead">
                        <i class="ti ti-mail-opened me-1"></i> {{ __('تحديد الكل كمقروء') }}
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
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted d-block small mb-1">{{ __('إجمالي الإشعارات') }}</span>
                            <h4 class="fw-bold mb-0 text-heading">{{ number_format($totalCount) }}</h4>
                            <small class="text-primary"><i class="ti ti-bell me-1"></i>{{ __('في سجلك') }}</small>
                        </div>
                        <div class="avatar avatar-md bg-label-primary rounded">
                            <i class="ti ti-bell fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unread Notifications -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted d-block small mb-1">{{ __('غير مقروءة') }}</span>
                            <h4 class="fw-bold mb-0 text-heading {{ $unreadCount > 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($unreadCount) }}
                            </h4>
                            <small class="{{ $unreadCount > 0 ? 'text-danger' : 'text-success' }}">
                                <i class="ti {{ $unreadCount > 0 ? 'ti-alert-circle' : 'ti-circle-check' }} me-1"></i>
                                {{ $unreadCount > 0 ? __('تتطلب الانتباه') : __('تم قراءة الكل') }}
                            </small>
                        </div>
                        <div class="avatar avatar-md bg-label-danger rounded">
                            <i class="ti ti-mail-spark fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Notifications -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
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
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted d-block small mb-1">{{ __('العمليات المالية') }}</span>
                            <h4 class="fw-bold mb-0 text-heading">{{ number_format($financialCount) }}</h4>
                            <small class="text-warning"><i class="ti ti-wallet me-1"></i>{{ __('سحوبات ودفعات') }}</small>
                        </div>
                        <div class="avatar avatar-md bg-label-warning rounded">
                            <i class="ti ti-wallet fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('system.notifications.all') }}" id="notificationsFilterForm">
                <div class="row g-2 align-items-center">
                    <!-- Search Input -->
                    <div class="col-12 col-md-4">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control border-start-0" 
                                placeholder="{{ __('بحث في العنوان أو النص...') }}" 
                                value="{{ request('search') }}">
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="col-6 col-md-2">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">{{ __('جميع الحالات') }}</option>
                            <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>{{ __('غير مقروءة فقط') }}</option>
                            <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>{{ __('مقروءة فقط') }}</option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="col-6 col-md-3">
                        <select name="category" class="form-select" onchange="this.form.submit()">
                            <option value="">{{ __('جميع الأقسام والأحداث') }}</option>
                            <option value="financial" {{ request('category') === 'financial' ? 'selected' : '' }}>{{ __('العمليات المالية (سحوبات، Payout)') }}</option>
                            <option value="tasks" {{ request('category') === 'tasks' ? 'selected' : '' }}>{{ __('المهام والرحلات') }}</option>
                            <option value="users" {{ request('category') === 'users' ? 'selected' : '' }}>{{ __('المستخدمين والشركاء') }}</option>
                            <option value="system" {{ request('category') === 'system' ? 'selected' : '' }}>{{ __('النظام والوثائق') }}</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12 col-md-3 d-flex gap-2 justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-filter me-1"></i> {{ __('تصفية') }}
                        </button>
                        @if(request()->hasAny(['search', 'status', 'category']))
                        <a href="{{ route('system.notifications.all') }}" class="btn btn-label-secondary" title="{{ __('إعادة تعيين الفلاتر') }}">
                            <i class="ti ti-refresh"></i>
                        </a>
                        @endif
                        <button type="button" class="btn btn-label-danger" id="btnDeleteAllRead" title="{{ __('حذف كافة المقروءة') }}">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="card border-0 shadow-sm">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 d-flex align-items-center">
                <i class="ti ti-list-details text-primary me-2"></i>
                {{ __('سجل الإشعارات') }}
                <span class="badge bg-label-primary ms-2">{{ $notifications->total() }}</span>
            </h5>
            <small class="text-muted">
                {{ __('يتم تحديث الحالة تلقائياً عند النقر على الإشعار') }}
            </small>
        </div>

        <div class="card-body p-0">
            @if($notifications->isEmpty())
                <div class="text-center py-5">
                    <div class="avatar avatar-xl bg-label-secondary mx-auto mb-3" style="width: 80px; height: 80px;">
                        <i class="ti ti-bell-off fs-1 text-muted"></i>
                    </div>
                    <h5 class="fw-semibold mb-1">{{ __('لا توجد أي إشعارات مطابقة') }}</h5>
                    <p class="text-muted mb-3">{{ __('لم تتلقَ أي إشعارات مطابقة لمعايير البحث الحالية.') }}</p>
                    @if(request()->hasAny(['search', 'status', 'category']))
                        <a href="{{ route('system.notifications.all') }}" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-rotate-clockwise me-1"></i> {{ __('عرض كافة الإشعارات') }}
                        </a>
                    @endif
                </div>
            @else
                <div class="list-group list-group-flush" id="notificationsListGroup">
                    @foreach($notifications as $item)
                        @php
                            $notif = $item->notification;
                            $isRead = $item->status;
                            $eventKey = $notif?->event_key;
                            
                            // Determine category label and badge
                            $categoryBadge = match(true) {
                                in_array($eventKey, ['driver_withdrawal_requested', 'payout_approval_required', 'payout_status_updated']) => ['label' => __('مالية'), 'class' => 'bg-label-warning'],
                                in_array($eventKey, ['task_created', 'task_cancellation_requested', 'task_status_changed', 'task_offer_created', 'task_offer_accepted']) => ['label' => __('مهام'), 'class' => 'bg-label-info'],
                                in_array($eventKey, ['customer_registered', 'driver_registered', 'team_created']) => ['label' => __('مستخدمين'), 'class' => 'bg-label-success'],
                                default => ['label' => __('نظام'), 'class' => 'bg-label-secondary']
                            };
                            
                            $icon = $notif?->icon ?: 'ti-bell text-primary';
                        @endphp

                        <div class="list-group-item list-group-item-action p-3 notification-row {{ !$isRead ? 'bg-label-primary bg-opacity-10 border-start border-3 border-primary' : '' }}" 
                             data-id="{{ $item->id }}" 
                             id="notif-row-{{ $item->id }}">
                            <div class="d-flex align-items-start gap-3">
                                <!-- Icon Avatar -->
                                <div class="avatar avatar-md flex-shrink-0 mt-1">
                                    <span class="avatar-initial rounded-circle {{ !$isRead ? 'bg-primary text-white shadow-sm' : 'bg-label-secondary text-secondary' }}">
                                        <i class="ti {{ $icon }} fs-4"></i>
                                    </span>
                                </div>

                                <!-- Body -->
                                <div class="flex-grow-1">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-1 mb-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <h6 class="mb-0 fw-bold text-heading fs-6">
                                                {{ $notif?->title ?? __('إشعار من النظام') }}
                                            </h6>
                                            <span class="badge {{ $categoryBadge['class'] }} rounded-pill px-2 py-0 fs-tiny">
                                                {{ $categoryBadge['label'] }}
                                            </span>
                                            @if(!$isRead)
                                                <span class="badge bg-danger rounded-pill px-2 py-0 fs-tiny">
                                                    {{ __('جديد') }}
                                                </span>
                                            @endif
                                        </div>
                                        <small class="text-muted d-flex align-items-center" title="{{ $item->created_at->format('Y-m-d H:i:s') }}">
                                            <i class="ti ti-clock me-1"></i>
                                            {{ $item->created_at->diffForHumans() }}
                                            <span class="d-none d-lg-inline ms-1">({{ $item->created_at->format('h:i A - Y/m/d') }})</span>
                                        </small>
                                    </div>

                                    <p class="mb-2 text-body small" style="white-space: pre-line; line-height: 1.6;">
                                        {{ $notif?->message ?? '' }}
                                    </p>

                                    <!-- Footer Actions -->
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top border-light">
                                        <div>
                                            @if($notif?->action_url)
                                                <a href="{{ $notif->action_url }}" class="btn btn-xs btn-primary btn-action-go me-2" data-id="{{ $item->id }}">
                                                    <i class="ti ti-external-link me-1"></i> {{ __('عرض التفاصيل') }}
                                                </a>
                                            @endif
                                        </div>

                                        <div class="d-flex align-items-center gap-1">
                                            <button type="button" 
                                                    class="btn btn-xs {{ $isRead ? 'btn-label-secondary' : 'btn-label-primary' }} btn-toggle-read" 
                                                    data-id="{{ $item->id }}" 
                                                    title="{{ $isRead ? __('تحديد كغير مقروء') : __('تحديد كمقروء') }}">
                                                <i class="ti {{ $isRead ? 'ti-mail' : 'ti-mail-opened' }} me-1"></i>
                                                <span class="toggle-text">{{ $isRead ? __('تحديد كغير مقروء') : __('تحديد كمقروء') }}</span>
                                            </button>

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
                    @endforeach
                </div>
            @endif
        </div>

        @if($notifications->hasPages())
            <div class="card-footer border-top py-3 d-flex justify-content-center">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
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
            text: "{{ __('هل أنت متأكد من رغبتك في تحديد جميع الإشعارات غير المقروءة كمقروءة؟') }}",
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
            text: "{{ __('هل تريد حذف جميع الإشعارات التي قمت بقراءتها مسبقاً؟') }}",
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

        $.ajax({
            url: `${apiBase}/${id}/toggle-read`,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function (res) {
                if (res.success) {
                    if (res.is_read) {
                        row.removeClass('bg-label-primary bg-opacity-10 border-start border-3 border-primary');
                        row.find('.badge.bg-danger').remove();
                        btn.removeClass('btn-label-primary').addClass('btn-label-secondary');
                        btn.find('i').removeClass('ti-mail-opened').addClass('ti-mail');
                        btn.find('.toggle-text').text("{{ __('تحديد كغير مقروء') }}");
                    } else {
                        row.addClass('bg-label-primary bg-opacity-10 border-start border-3 border-primary');
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
            text: "{{ __('هل أنت متأكد من حذف هذا الإشعار من سجلك؟') }}",
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
