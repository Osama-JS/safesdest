@extends('layouts/layoutMaster')

@section('title', 'محادثات الواتساب — لوحة التحكم')

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/toastr/toastr.scss'
])
<style>
    .wa-app-card {
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #e7e7e7;
        box-shadow: 0 4px 24px 0 rgba(34, 41, 47, 0.08);
    }
    .wa-chat-container {
        height: 720px;
        background: #fdfdfd;
    }
    .wa-sidebar {
        border-left: 1px solid #ebe9f1;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .wa-sidebar-header {
        padding: 1.25rem 1rem;
        background: #ffffff;
        border-bottom: 1px solid #ebe9f1;
    }
    .wa-search-box {
        position: relative;
    }
    .wa-search-box input {
        border-radius: 20px;
        padding-right: 2.5rem;
        font-size: 0.88rem;
        background-color: #f8f9fa;
        border: 1px solid #e0e0e0;
    }
    .wa-search-box .search-icon {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #a1acb8;
    }
    .wa-filter-tabs {
        display: flex;
        gap: 6px;
        padding: 0.5rem 1rem;
        background: #fdfdfd;
        border-bottom: 1px solid #f0f0f0;
        overflow-x: auto;
    }
    .wa-filter-pill {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #64748b;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .wa-filter-pill.active, .wa-filter-pill:hover {
        background: #25D366;
        color: #ffffff;
        border-color: #25D366;
    }
    .wa-conversations-list {
        flex-grow: 1;
        overflow-y: auto;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .wa-conversation-item {
        padding: 12px 14px;
        border-bottom: 1px solid #f4f4f4;
        cursor: pointer;
        display: flex;
        align-items: center;
        transition: background-color 0.15s ease;
        position: relative;
    }
    .wa-conversation-item:hover {
        background-color: #f7fafc;
    }
    .wa-conversation-item.active {
        background-color: #eefbf3;
        border-right: 4px solid #25D366;
    }
    .wa-avatar {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.1rem;
        color: #fff;
        flex-shrink: 0;
        position: relative;
    }
    .wa-avatar .role-dot {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 13px;
        height: 13px;
        border-radius: 50%;
        border: 2px solid #fff;
    }
    .wa-avatar.customer { background: linear-gradient(135deg, #10b981, #059669); }
    .wa-avatar.driver { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .wa-avatar.unregistered { background: linear-gradient(135deg, #9ca3af, #6b7280); }

    /* Chat Area */
    .wa-chat-main {
        display: flex;
        flex-direction: column;
        height: 100%;
        background-color: #efeae2;
        background-image: radial-gradient(#d1d7db 0.75px, transparent 0.75px);
        background-size: 16px 16px;
        position: relative;
    }
    .wa-chat-header {
        padding: 12px 18px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        z-index: 5;
    }
    .wa-window-badge {
        font-size: 0.78rem;
        padding: 5px 10px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .wa-chat-history {
        flex-grow: 1;
        overflow-y: auto;
        padding: 1.5rem 2rem;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .wa-date-divider {
        display: flex;
        justify-content: center;
        margin: 10px 0;
    }
    .wa-date-divider span {
        background: rgba(255, 255, 255, 0.9);
        padding: 4px 14px;
        border-radius: 8px;
        font-size: 0.75rem;
        color: #54656f;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    .wa-bubble-row {
        display: flex;
        width: 100%;
    }
    .wa-bubble-row.inbound {
        justify-content: flex-start;
    }
    .wa-bubble-row.outbound {
        justify-content: flex-end;
    }
    .wa-bubble {
        max-width: 68%;
        min-width: 120px;
        padding: 9px 13px;
        border-radius: 9px;
        box-shadow: 0 1px 1.5px rgba(0,0,0,0.12);
        position: relative;
        font-size: 0.92rem;
        line-height: 1.45;
        word-wrap: break-word;
    }
    .wa-bubble-row.inbound .wa-bubble {
        background: #ffffff;
        color: #111b21;
        border-top-right-radius: 2px;
    }
    .wa-bubble-row.outbound .wa-bubble {
        background: #d9fdd3;
        color: #111b21;
        border-top-left-radius: 2px;
    }
    .wa-bubble-meta {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
        margin-top: 4px;
        font-size: 0.7rem;
        color: #667781;
    }
    .wa-bubble-tag {
        font-size: 0.7rem;
        background: rgba(0,0,0,0.05);
        padding: 2px 6px;
        border-radius: 4px;
        margin-bottom: 4px;
        display: inline-block;
        color: #008069;
        font-weight: 600;
    }
    .wa-chat-footer {
        padding: 12px 18px;
        background: #f0f2f5;
        border-top: 1px solid #d1d7db;
    }
    .wa-input-box {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .wa-message-input {
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 24px;
        padding: 10px 18px;
        font-size: 0.92rem;
        box-shadow: none;
    }
    .wa-message-input:focus {
        border-color: #25D366;
        box-shadow: 0 0 0 0.15rem rgba(37, 211, 102, 0.15);
    }
    .wa-send-btn {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background-color: #25D366;
        border: none;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        transition: transform 0.15s, background-color 0.15s;
        flex-shrink: 0;
    }
    .wa-send-btn:hover {
        background-color: #20ba5a;
        transform: scale(1.05);
        color: #fff;
    }
    .wa-send-btn:disabled {
        background-color: #94d3a2;
        cursor: not-allowed;
    }

    /* Media Messages */
    .wa-media-preview {
        max-width: 280px;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 6px;
    }
    .wa-media-preview img {
        max-width: 100%;
        max-height: 240px;
        border-radius: 6px;
        cursor: pointer;
        display: block;
        transition: opacity 0.2s;
    }
    .wa-media-preview img:hover {
        opacity: 0.9;
    }
    .wa-doc-card {
        background: rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 8px;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        color: #2b343b;
        margin-bottom: 4px;
        transition: background 0.15s;
    }
    .wa-doc-card:hover {
        background: rgba(0, 0, 0, 0.08);
        color: #111b21;
    }

    /* Right Profile Drawer */
    .wa-profile-drawer {
        width: 320px;
        background: #ffffff;
        border-right: 1px solid #ebe9f1;
        overflow-y: auto;
        display: none;
        flex-direction: column;
        padding: 1.5rem;
    }
    .wa-profile-drawer.open {
        display: flex;
    }

    .pulse-green {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #25D366;
        box-shadow: 0 0 0 rgba(37, 211, 102, 0.4);
        animation: pulse 1.8s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.5); }
        70% { box-shadow: 0 0 0 8px rgba(37, 211, 102, 0); }
        100% { box-shadow: 0 0 0 0 rgba(37, 211, 102, 0); }
    }

    /* Highlight animation when conversation moves to top (like WhatsApp) */
    @keyframes wa-highlight-fade {
        0%   { background-color: #d9fdd3; }
        100% { background-color: transparent; }
    }
    .wa-conversation-item.wa-item-highlight {
        animation: wa-highlight-fade 1.5s ease-out forwards;
    }
</style>
@endsection

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1 text-heading fw-bold">
            <i class="ti ti-brand-whatsapp text-success me-2 fs-2 align-middle"></i> مركز محادثات الواتساب الموحد
        </h4>
        <p class="text-muted mb-0">مراسلة العملاء والسائقين عبر رقم المنصة المعتمد لدى ميتا (SAEI / Meta Cloud)</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-success d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#newChatModal">
            <i class="ti ti-message-plus me-1"></i> محادثة جديدة
        </button>
        <a href="{{ route('admin.whatsapp-templates.index') }}" class="btn btn-outline-secondary d-flex align-items-center">
            <i class="ti ti-template me-1"></i> إدارة القوالب
        </a>
        <a href="{{ route('admin.whatsapp-otp-test.index') }}" class="btn btn-outline-info d-flex align-items-center">
            <i class="ti ti-shield-check me-1"></i> اختبار OTP (ساعي)
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted d-block mb-1">إجمالي المحادثات</span>
                    <h3 class="mb-0 fw-bold text-heading">{{ number_format($stats['total_conversations']) }}</h3>
                </div>
                <div class="avatar avatar-md bg-label-primary rounded-circle p-2">
                    <i class="ti ti-messages ti-md"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted d-block mb-1">رسائل غير مقروءة</span>
                    <h3 class="mb-0 fw-bold text-danger">{{ number_format($stats['unread_messages']) }}</h3>
                </div>
                <div class="avatar avatar-md bg-label-danger rounded-circle p-2">
                    <i class="ti ti-bell-ringing ti-md"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted d-block mb-1">رسائل واردة (اليوم)</span>
                    <h3 class="mb-0 fw-bold text-success">{{ number_format($stats['messages_received_today']) }}</h3>
                </div>
                <div class="avatar avatar-md bg-label-success rounded-circle p-2">
                    <i class="ti ti-arrow-down-left ti-md"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted d-block mb-1">رسائل صادرة (اليوم)</span>
                    <h3 class="mb-0 fw-bold text-info">{{ number_format($stats['messages_sent_today']) }}</h3>
                </div>
                <div class="avatar avatar-md bg-label-info rounded-circle p-2">
                    <i class="ti ti-arrow-up-right ti-md"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main WhatsApp Chat Container -->
<div class="card wa-app-card">
    <div class="row g-0 wa-chat-container">
        <!-- Sidebar (Conversations List) -->
        <div class="col-12 col-md-5 col-lg-4 wa-sidebar">
            <div class="wa-sidebar-header">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0 fw-bold text-heading">
                        <i class="ti ti-messages text-success me-1"></i> المحادثات
                    </h5>
                    <span class="badge bg-label-success d-flex align-items-center">
                        <span class="pulse-green me-1"></span> متصل
                    </span>
                </div>
                <!-- Search Box -->
                <div class="wa-search-box">
                    <i class="ti ti-search search-icon"></i>
                    <input type="text" id="conversation-search" class="form-control" placeholder="بحث بالاسم أو رقم الهاتف..." value="{{ $search }}">
                </div>
            </div>

            <!-- Filter Pills -->
            <div class="wa-filter-tabs">
                <div class="wa-filter-pill {{ $filter === 'all' ? 'active' : '' }}" data-filter="all">الكل</div>
                <div class="wa-filter-pill {{ $filter === 'customers' ? 'active' : '' }}" data-filter="customers">العملاء</div>
                <div class="wa-filter-pill {{ $filter === 'drivers' ? 'active' : '' }}" data-filter="drivers">السائقين</div>
                <div class="wa-filter-pill {{ $filter === 'unread' ? 'active' : '' }}" data-filter="unread">
                    غير مقروءة @if($stats['unread_messages'] > 0) <span class="badge bg-danger rounded-pill ms-1">{{ $stats['unread_messages'] }}</span> @endif
                </div>
            </div>

            <!-- List -->
            <ul class="wa-conversations-list" id="conversations-ul">
                @forelse($conversations as $conv)
                @php
                    $uType = $conv->user_type ?? 'unregistered';
                    $roleClass = match($uType) {
                        'customer' => 'customer',
                        'driver' => 'driver',
                        default => 'unregistered'
                    };
                    $roleLabel = match($uType) {
                        'customer' => 'عميل',
                        'driver' => 'سائق',
                        default => 'غير مسجل'
                    };
                    $initials = mb_substr($conv->user_name, 0, 1);
                @endphp
                <li class="wa-conversation-item {{ $activeConversationId == $conv->id ? 'active' : '' }}" 
                    data-id="{{ $conv->id }}" 
                    data-type="{{ $uType }}" 
                    data-phone="{{ $conv->phone_number }}"
                    data-name="{{ $conv->user_name }}">
                    <div class="wa-avatar {{ $roleClass }} me-3">
                        {{ $initials }}
                        <span class="role-dot bg-{{ $uType === 'customer' ? 'success' : ($uType === 'driver' ? 'primary' : 'secondary') }}"></span>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0 text-truncate fw-semibold" style="font-size: 0.95rem;">
                                {{ $conv->user_name }}
                            </h6>
                            <small class="text-muted text-nowrap ms-1" style="font-size: 0.72rem;">
                                {{ $conv->last_message_time ? $conv->last_message_time->diffForHumans(null, true) : '' }}
                            </small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="mb-0 text-truncate text-muted text-preview" style="font-size: 0.82rem; max-width: 80%;">
                                {{ $conv->last_message_preview ?: 'بدء المحادثة...' }}
                            </p>
                            @if($conv->unread_count > 0)
                                <span class="badge bg-danger rounded-pill badge-unread">{{ $conv->unread_count }}</span>
                            @endif
                        </div>
                        <div class="mt-1 d-flex align-items-center gap-1">
                            <span class="badge bg-label-{{ $uType === 'customer' ? 'success' : ($uType === 'driver' ? 'info' : 'secondary') }}" style="font-size: 0.68rem; padding: 2px 6px;">
                                {{ $roleLabel }}
                            </span>
                            <span class="text-muted" style="font-size: 0.72rem; direction: ltr;">
                                +{{ ltrim($conv->phone_number, '+') }}
                            </span>
                        </div>
                    </div>
                </li>
                @empty
                <li class="p-5 text-center text-muted">
                    <i class="ti ti-messages-off mb-3" style="font-size: 3rem; opacity: 0.4;"></i>
                    <p class="mb-0">لا توجد محادثات مسجلة حتى الآن</p>
                    <small>ستظهر المحادثات هنا فور تلقي أو بدء أي رسالة واتساب</small>
                </li>
                @endforelse
            </ul>
        </div>

        <!-- Chat Main Area -->
        <div class="col-12 col-md-7 col-lg-8 d-flex" style="height: 100%;">
            <div class="wa-chat-main flex-grow-1">
                <!-- Header -->
                <div class="wa-chat-header d-none" id="chat-header">
                    <div class="d-flex align-items-center">
                        <div class="wa-avatar customer me-3" id="active-chat-avatar">
                            <i class="ti ti-user"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="mb-0 fw-bold" id="active-chat-name">-</h6>
                                <span class="badge bg-label-primary" id="active-chat-badge">-</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="text-muted small" id="active-chat-phone" style="direction: ltr;">-</span>
                                <button type="button" class="btn btn-xs btn-outline-secondary p-0 px-1" id="copy-phone-btn" title="نسخ الرقم">
                                    <i class="ti ti-copy" style="font-size: 0.75rem;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Actions -->
                    <div class="d-flex align-items-center gap-2">
                        <!-- 24h Window Badge -->
                        <span id="window-status-badge" class="wa-window-badge bg-label-secondary">
                            <i class="ti ti-clock"></i> جاري التحقق...
                        </span>
                        
                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary" id="open-template-btn" title="إرسال قالب رسمي">
                            <i class="ti ti-template"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" id="refresh-chat-btn" title="تحديث المحادثة">
                            <i class="ti ti-refresh"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-icon btn-outline-info" id="toggle-profile-btn" title="معلومات الملف الشخصي">
                            <i class="ti ti-id"></i>
                        </button>
                    </div>
                </div>

                <!-- History -->
                <div class="wa-chat-history" id="chat-messages">
                    <!-- Placeholder -->
                    <div class="m-auto text-center text-muted" id="chat-placeholder">
                        <div class="avatar avatar-xl bg-label-success rounded-circle mx-auto mb-3 p-3" style="width: 80px; height: 80px;">
                            <i class="ti ti-brand-whatsapp" style="font-size: 2.8rem;"></i>
                        </div>
                        <h4 class="fw-bold text-heading">محادثات الواتساب عبر ساعي (SAEI)</h4>
                        <p class="text-muted mb-3" style="max-width: 420px; margin: 0 auto;">
                            اختر إحدى المحادثات من القائمة الجانبية للتواصل الفوري مع العميل أو السائق عبر رقم المنصة المعتمد
                        </p>
                        <button type="button" class="btn btn-success d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#newChatModal">
                            <i class="ti ti-plus me-1"></i> بدء محادثة جديدة برقم هاتف
                        </button>
                    </div>
                </div>

                <!-- 24hr Window Closed Warning Alert -->
                <div class="p-3 bg-light-warning border-top d-none" id="window-closed-banner" style="background-color: #fff9e6; border-color: #ffe69c;">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-alert-triangle text-warning fs-3 me-2"></i>
                            <div>
                                <strong class="text-warning-dark d-block">نافذة الـ 24 ساعة مغلقة</strong>
                                <small class="text-muted">مرت أكثر من 24 ساعة منذ آخر رسالة واردة من الطرف الآخر. سياسات ميتا تمنع النص الحر وتتطلب إرسال قالب رسمي أولاً لإعادة فتح النافذة.</small>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-warning d-flex align-items-center text-nowrap ms-2" data-bs-toggle="modal" data-bs-target="#sendTemplateModal">
                            <i class="ti ti-template me-1"></i> إرسال قالب لإعادة التفعيل
                        </button>
                    </div>
                </div>

                <!-- Input Footer -->
                <div class="wa-chat-footer d-none" id="chat-footer">
                    <form id="chat-form">
                        @csrf
                        <div class="wa-input-box">
                            <button type="button" class="btn btn-icon btn-light rounded-circle" data-bs-toggle="modal" data-bs-target="#sendTemplateModal" title="إرسال قالب رسمي">
                                <i class="ti ti-template text-muted"></i>
                            </button>
                            <button type="button" class="btn btn-icon btn-light rounded-circle" id="chat-attach-btn" title="إرفاق ملف أو صورة">
                                <i class="ti ti-paperclip text-muted"></i>
                            </button>
                            <input type="file" id="chat-file-input" class="d-none" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                            <input type="text" class="form-control wa-message-input" id="chat-input" placeholder="اكتب رسالتك هنا... (اضغط Enter للإرسال)" autocomplete="off">
                            <button type="submit" class="wa-send-btn" id="send-btn" title="إرسال الرسالة">
                                <i class="ti ti-send"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Profile Info Drawer -->
            <div class="wa-profile-drawer" id="profile-drawer">
                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                    <h6 class="mb-0 fw-bold text-heading">بيانات الحساب بالمنصة</h6>
                    <button type="button" class="btn-close" id="close-profile-btn"></button>
                </div>
                <div class="text-center mb-4">
                    <div class="wa-avatar customer mx-auto mb-3" id="drawer-avatar" style="width: 72px; height: 72px; font-size: 1.8rem;">
                        <i class="ti ti-user"></i>
                    </div>
                    <h5 class="mb-1 fw-bold text-heading" id="drawer-name">-</h5>
                    <span class="badge bg-label-primary mb-2" id="drawer-type-badge">-</span>
                    <p class="text-muted small mb-0" id="drawer-phone" style="direction: ltr;">-</p>
                    <p class="text-muted small" id="drawer-email">-</p>
                </div>
                
                <div class="card bg-light border-0 mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">رصيد المحفظة:</span>
                            <span class="fw-bold text-success" id="drawer-wallet">0.00 ر.س</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">إجمالي الطلبات / المهام:</span>
                            <span class="fw-bold text-primary" id="drawer-tasks">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">تاريخ الانضمام:</span>
                            <span class="text-muted small" id="drawer-registered">-</span>
                        </div>
                    </div>
                </div>

                <div class="mt-auto d-grid gap-2">
                    <a href="#" target="_blank" class="btn btn-outline-primary d-flex align-items-center justify-content-center" id="drawer-profile-link">
                        <i class="ti ti-external-link me-1"></i> فتح الملف في لوحة التحكم
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Start New Chat -->
<div class="modal fade" id="newChatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="ti ti-message-plus text-success me-2"></i> بدء محادثة جديدة عبر واتساب
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="new-chat-form">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">رقم الهاتف (مع رمز الدولة الدولي) <span class="text-danger">*</span></label>
                        <div class="input-group" style="direction: ltr;">
                            <span class="input-group-text"><i class="ti ti-phone"></i></span>
                            <input type="text" class="form-control text-start" id="new-chat-phone" placeholder="9665xxxxxxxx" required>
                        </div>
                        <small class="text-muted">مثال: 966501234567 (أرقام فقط بدون رمز +)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">القالب المبدئي المعتمد لبدء المحادثة <span class="text-danger">*</span></label>
                        <select class="form-select" id="new-chat-template" required>
                            @foreach($approvedTemplates as $tpl)
                                <option value="{{ $tpl->template_name }}">{{ $tpl->template_name }} ({{ $tpl->language }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">تتطلب سياسة ميتا إرسال قالب رسمي لبدء أي محادثة لأول مرة مع مستخدم جديد.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success d-flex align-items-center" id="new-chat-submit-btn">
                        <i class="ti ti-send me-1"></i> إرسال وبدء المحادثة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Send Official Template -->
<div class="modal fade" id="sendTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">
                    <i class="ti ti-template text-primary me-2"></i> إرسال قالب رسمي معتمد (Meta Approved)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="template-send-form">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">اختر القالب</label>
                        <select class="form-select" id="selected-template-name" required>
                            @foreach($approvedTemplates as $tpl)
                                <option value="{{ $tpl->template_name }}" data-body="{{ $tpl->body_text }}">
                                    {{ $tpl->template_name }} ({{ $tpl->language }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small">معاينة نص القالب:</label>
                        <div class="p-3 bg-light rounded border text-muted small" id="template-preview-box" style="white-space: pre-wrap;">
                            -
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary d-flex align-items-center" id="submit-template-btn">
                        <i class="ti ti-send me-1"></i> إرسال القالب الآن
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/toastr/toastr.js'
])
@endsection

@section('page-script')
<script type="module">
$(document).ready(function() {
    let currentConversationId = null;
    let pollInterval = null;
    let highestMessageId = 0;
    let isWindowOpen = false;

    // Web Audio Chime generator (No external MP3 file needed)
    function playNotificationSound() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.35);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.35);
        } catch (e) {
            // AudioContext not allowed or not supported
        }
    }

    // Filter pills click
    $('.wa-filter-pill').on('click', function() {
        $('.wa-filter-pill').removeClass('active');
        $(this).addClass('active');
        let filter = $(this).data('filter');
        filterConversations(filter, $('#conversation-search').val());
    });

    // Search input
    $('#conversation-search').on('input', function() {
        let activeFilter = $('.wa-filter-pill.active').data('filter') || 'all';
        filterConversations(activeFilter, $(this).val());
    });

    function filterConversations(filter, query) {
        query = (query || '').toLowerCase().trim();
        $('.wa-conversation-item').each(function() {
            let item = $(this);
            let type = item.data('type');
            let phone = String(item.data('phone') || '').toLowerCase();
            let name = String(item.data('name') || '').toLowerCase();
            let unread = item.find('.badge-unread').length > 0;

            let matchesFilter = true;
            if (filter === 'customers') matchesFilter = (type === 'customer');
            else if (filter === 'drivers') matchesFilter = (type === 'driver');
            else if (filter === 'unread') matchesFilter = unread;

            let matchesQuery = true;
            if (query) {
                matchesQuery = phone.includes(query) || name.includes(query);
            }

            if (matchesFilter && matchesQuery) {
                item.removeClass('d-none');
            } else {
                item.addClass('d-none');
            }
        });
    }

    // Select Conversation
    $(document).on('click', '.wa-conversation-item', function() {
        let id = $(this).data('id');
        openConversation(id);
    });

    function openConversation(id) {
        if (!id) return;
        currentConversationId = id;

        // UI active state
        $('.wa-conversation-item').removeClass('active');
        let activeItem = $(`.wa-conversation-item[data-id="${id}"]`);
        activeItem.addClass('active');

        // Optimistically remove badge from the item and decrease counters immediately
        let itemBadge = activeItem.find('.badge-unread');
        if (itemBadge.length) {
            let unreadOnItem = parseInt(itemBadge.text()) || 0;
            itemBadge.remove();

            let currentKpi = parseInt($('#kpi-unread-count').text().replace(/,/g, '')) || 0;
            let updatedKpi = Math.max(0, currentKpi - unreadOnItem);
            $('#kpi-unread-count').text(updatedKpi);

            if (updatedKpi > 0) {
                $('#pill-unread-badge').text(updatedKpi).removeClass('d-none');
            } else {
                $('#pill-unread-badge').addClass('d-none');
            }
        }

        // Clear previous poll
        if (pollInterval) clearInterval(pollInterval);

        // Show header & footer
        $('#chat-header').removeClass('d-none');
        $('#chat-footer').removeClass('d-none');
        let chatArea = $('#chat-messages');
        chatArea.html('<div class="m-auto text-center py-5"><div class="spinner-border text-success" role="status"></div><p class="mt-2 text-muted small">جاري تحميل المحادثة...</p></div>');

        $.ajax({
            url: "{{ url('admin/whatsapp-chat') }}/" + id + "/messages",
            type: "GET",
            success: function(res) {
                if (res.status !== 'success') return;

                let conv = res.conversation;
                let user = res.user_info;

                // Sync accurate unread count from server
                if (res.unread_stats) {
                    let totalUnread = res.unread_stats.unread_messages;
                    $('#kpi-unread-count').text(totalUnread);
                    if (totalUnread > 0) {
                        $('#pill-unread-badge').text(totalUnread).removeClass('d-none');
                    } else {
                        $('#pill-unread-badge').addClass('d-none');
                    }
                }

                // Refresh navbar notifications
                if (typeof window.refreshAdminNotifications === 'function') {
                    window.refreshAdminNotifications();
                } else {
                    $(document).trigger('admin:refresh-notifications');
                }

                // Update Header
                $('#active-chat-name').text(conv.user_name);
                $('#active-chat-phone').text('+' + conv.phone_number.replace(/^\+/, ''));
                $('#active-chat-badge').text(conv.user_type_label)
                    .removeClass('bg-label-success bg-label-info bg-label-secondary')
                    .addClass(conv.user_type === 'customer' ? 'bg-label-success' : (conv.user_type === 'driver' ? 'bg-label-info' : 'bg-label-secondary'));
                
                let avatarElem = $('#active-chat-avatar');
                avatarElem.removeClass('customer driver unregistered').addClass(conv.user_type || 'unregistered');
                avatarElem.text(conv.user_name.charAt(0));

                // Update 24h Window
                isWindowOpen = conv.is_window_open;
                updateWindowUI(conv.is_window_open, conv.window_remaining_hours);

                // Update Profile Drawer
                updateProfileDrawer(user);

                // Render Messages
                highestMessageId = 0;
                renderMessages(res.messages);

                // Start Auto-polling every 4 seconds
                pollInterval = setInterval(function() {
                    pollNewMessages();
                }, 4000);

                // Mark conversation as read on server & notify Saei
                $.post("{{ url('admin/whatsapp-chat') }}/" + id + "/mark-read", {
                    _token: "{{ csrf_token() }}"
                });
            },
            error: function() {
                chatArea.html('<div class="m-auto text-center text-danger"><p class="bg-white p-3 rounded shadow-sm">حدث خطأ أثناء تحميل الرسائل</p></div>');
            }
        });
    }

    function updateWindowUI(isOpen, remainingHours) {
        let badge = $('#window-status-badge');
        let banner = $('#window-closed-banner');

        if (isOpen) {
            badge.removeClass('bg-label-secondary bg-label-warning')
                .addClass('bg-label-success')
                .html(`<i class="ti ti-circle-check text-success me-1"></i> النافذة مفتوحة (متبقي ${remainingHours} س)`);
            banner.addClass('d-none');
            $('#chat-input').prop('disabled', false).attr('placeholder', 'اكتب رسالتك هنا... (اضغط Enter للإرسال)');
            $('#send-btn').prop('disabled', false);
        } else {
            badge.removeClass('bg-label-secondary bg-label-success')
                .addClass('bg-label-warning')
                .html(`<i class="ti ti-alert-triangle text-warning me-1"></i> النافذة مغلقة (يلزم قالب)`);
            banner.removeClass('d-none');
            $('#chat-input').prop('disabled', true).attr('placeholder', 'النافذة مغلقة - اختر قالباً رسمياً لإعادة فتحها');
            $('#send-btn').prop('disabled', true);
        }
    }

    function buildMessageRowHtml(msg) {
        let isOut = (msg.direction === 'outbound');
        let alignment = isOut ? 'outbound' : 'inbound';

        let statusIcon = '';
        if (isOut) {
            if (msg.status === 'pending') statusIcon = '<i class="ti ti-clock text-muted"></i>';
            else if (msg.status === 'sent') statusIcon = '<i class="ti ti-check text-muted"></i>';
            else if (msg.status === 'delivered') statusIcon = '<i class="ti ti-checks text-muted"></i>';
            else if (msg.status === 'read') statusIcon = '<i class="ti ti-checks text-primary"></i>';
            else if (msg.status === 'failed') statusIcon = '<i class="ti ti-alert-circle text-danger" title="فشل الإرسال"></i>';
        }

        let tagHtml = '';
        if (msg.message_type === 'template') {
            tagHtml = `<span class="wa-bubble-tag"><i class="ti ti-template me-1"></i>قالب رسمي</span>`;
        }

        let mediaHtml = '';
        if (msg.media_url) {
            let isImg = msg.message_type === 'image' || /\.(jpg|jpeg|png|webp|gif)$/i.test(msg.media_url);
            let isVid = msg.message_type === 'video' || /\.(mp4|mov|webm)$/i.test(msg.media_url);
            let isAud = msg.message_type === 'audio' || /\.(mp3|ogg|wav|m4a)$/i.test(msg.media_url);

            if (isImg) {
                mediaHtml = `
                    <div class="wa-media-preview">
                        <img src="${msg.media_url}" alt="مرفق" onclick="window.open('${msg.media_url}', '_blank')">
                    </div>
                `;
            } else if (isVid) {
                mediaHtml = `
                    <div class="wa-media-preview">
                        <video src="${msg.media_url}" controls class="w-100 rounded" style="max-height: 220px;"></video>
                    </div>
                `;
            } else if (isAud) {
                mediaHtml = `
                    <div class="mb-2">
                        <audio src="${msg.media_url}" controls class="w-100" style="height: 38px;"></audio>
                    </div>
                `;
            } else {
                let fname = msg.media_filename || 'مستند مرفق';
                mediaHtml = `
                    <a href="${msg.media_url}" target="_blank" class="wa-doc-card">
                        <i class="ti ti-file-text fs-3 text-primary"></i>
                        <span class="text-truncate fw-semibold small flex-grow-1" style="max-width: 200px;">${escapeHtml(fname)}</span>
                        <i class="ti ti-download text-muted fs-5"></i>
                    </a>
                `;
            }
        }

        let errorHtml = '';
        if (msg.status === 'failed' && msg.error_code) {
            errorHtml = `<div class="text-danger small mt-1" style="font-size: 11px;"><i class="ti ti-alert-triangle ti-xs me-1"></i>${escapeHtml(msg.error_code)}</div>`;
        }

        let contentHtml = (msg.content && msg.content !== msg.media_filename)
            ? `<div class="wa-bubble-content">${escapeHtml(msg.content).replace(/\n/g, '<br>')}</div>`
            : '';

        return `
            <div class="wa-bubble-row ${alignment}" data-msg-id="${msg.id}">
                <div class="wa-bubble">
                    ${tagHtml}
                    ${mediaHtml}
                    ${contentHtml}
                    ${errorHtml}
                    <div class="wa-bubble-meta">
                        <span>${msg.time}</span>
                        ${statusIcon}
                    </div>
                </div>
            </div>
        `;
    }

    function renderMessages(messages) {
        let chatArea = $('#chat-messages');
        if (!messages || messages.length === 0) {
            chatArea.html(`
                <div class="m-auto text-center text-muted py-5">
                    <div class="avatar avatar-md bg-label-secondary rounded-circle mx-auto mb-2 p-2">
                        <i class="ti ti-message-dots"></i>
                    </div>
                    <h6>لا توجد رسائل سابقة في هذه المحادثة</h6>
                    <small>يمكنك بدء المراسلة عبر الصندوق بالأسفل</small>
                </div>
            `);
            return;
        }

        let html = '';
        let lastDate = '';

        messages.forEach(function(msg) {
            if (msg.id > highestMessageId) {
                highestMessageId = msg.id;
            }

            // Date divider
            if (msg.date !== lastDate) {
                let displayDate = msg.is_today ? 'اليوم' : msg.date;
                html += `
                    <div class="wa-date-divider">
                        <span>${displayDate}</span>
                    </div>
                `;
                lastDate = msg.date;
            }

            html += buildMessageRowHtml(msg);
        });

        chatArea.html(html);
        scrollToBottom();
    }

    function appendNewMessages(messages) {
        let chatArea = $('#chat-messages');
        let playAudio = false;

        messages.forEach(function(msg) {
            if (msg.id > highestMessageId) {
                highestMessageId = msg.id;
            }

            if (msg.direction === 'inbound') {
                playAudio = true;
            }

            chatArea.append(buildMessageRowHtml(msg));
        });

        if (playAudio) {
            playNotificationSound();
        }

        scrollToBottom();
    }

    // =========================================================================
    // Move conversation to top of list (like WhatsApp real-time reorder)
    // =========================================================================
    function moveConversationToTop(convId, previewText, timeLabel) {
        let item = $(`.wa-conversation-item[data-id="${convId}"]`);
        if (!item.length) return;

        let list = item.closest('ul, div.wa-conversations-list, .list-group');
        if (!list.length) return;

        // Update preview text & time label
        if (previewText) {
            item.find('.text-preview').text(previewText);
        }
        if (timeLabel) {
            item.find('small.text-nowrap').text(timeLabel);
        }

        // Only move if not already first
        if (item.index() !== 0) {
            item.detach().prependTo(list);
            // Brief highlight flash to show it moved
            item.addClass('wa-item-highlight');
            setTimeout(function() { item.removeClass('wa-item-highlight'); }, 1500);
        }
    }

    // Periodic full sidebar refresh (keeps background conversations in order)
    let sidebarRefreshInterval = null;
    function startSidebarRefresh() {
        if (sidebarRefreshInterval) clearInterval(sidebarRefreshInterval);
        sidebarRefreshInterval = setInterval(refreshSidebarConversations, 30000);
    }

    function refreshSidebarConversations() {
        let activeFilter = $('.wa-filter-pill.active').data('filter') || 'all';
        let search = $('#conversation-search').val() || '';

        $.get("{{ url('admin/whatsapp-chat/widget-summary') }}", {
            filter: activeFilter,
            search: search
        }, function(res) {
            if (res.status !== 'success' || !res.conversations) return;

            let convs = res.conversations;
            let list = $('.wa-conversations-list');
            if (!list.length) return;

            // Build a map of existing DOM items
            let existingItems = {};
            list.find('.wa-conversation-item').each(function() {
                existingItems[$(this).data('id')] = $(this);
            });

            // Re-append items in sorted order from server, updating previews
            convs.forEach(function(c) {
                let item = existingItems[c.id];
                if (item) {
                    // Update preview and time
                    item.find('.text-preview').text(c.last_message_preview || '');
                    item.find('small.text-nowrap').text(c.last_message_time || '');

                    // Update unread badge
                    item.find('.badge-unread').remove();
                    if (c.unread_count > 0) {
                        item.find('.d-flex.justify-content-between.align-items-center').last()
                            .append(`<span class="badge bg-danger rounded-pill badge-unread">${c.unread_count}</span>`);
                    }
                    list.append(item.detach());
                }
            });
        });
    }

    function pollNewMessages() {
        if (!currentConversationId) return;

        $.ajax({
            url: "{{ url('admin/whatsapp-chat') }}/" + currentConversationId + "/poll?after_id=" + highestMessageId,
            type: "GET",
            success: function(res) {
                if (res.status === 'success') {
                    if (res.has_new && res.messages.length > 0) {
                        appendNewMessages(res.messages);

                        // Check if any new inbound message arrived - move conversation to top
                        let hasInbound = res.messages.some(m => m.direction === 'inbound');
                        let lastMsg = res.messages[res.messages.length - 1];
                        if (lastMsg) {
                            moveConversationToTop(
                                currentConversationId,
                                lastMsg.content ? lastMsg.content.substring(0, 60) : null,
                                'الآن'
                            );
                        }
                    }
                    if (res.unread_stats) {
                        let totalUnread = res.unread_stats.unread_messages;
                        $('#kpi-unread-count').text(totalUnread);
                        if (totalUnread > 0) {
                            $('#pill-unread-badge').text(totalUnread).removeClass('d-none');
                        } else {
                            $('#pill-unread-badge').addClass('d-none');
                        }
                    }
                    if (res.is_window_open !== undefined) {
                        updateWindowUI(res.is_window_open, res.window_remaining_hours);
                    }
                }
            }
        });
    }

    // Start periodic sidebar refresh to keep background conversations sorted
    startSidebarRefresh();

    function scrollToBottom() {
        let chatArea = $('#chat-messages');
        chatArea.scrollTop(chatArea[0].scrollHeight);
    }

    function updateProfileDrawer(user) {
        if (!user) return;
        $('#drawer-name').text(user.name || '-');
        $('#drawer-phone').text('+' + (user.phone || '').replace(/^\+/, ''));
        $('#drawer-email').text(user.email || 'لا يوجد بريد مسجل');
        $('#drawer-type-badge').text(user.type_label);
        $('#drawer-wallet').text(parseFloat(user.wallet_balance || 0).toFixed(2) + ' ر.س');
        $('#drawer-tasks').text(user.tasks_count || 0);
        $('#drawer-registered').text(user.registered_at || '-');

        let avatar = $('#drawer-avatar');
        avatar.removeClass('customer driver unregistered').addClass(user.type || 'unregistered');
        avatar.text((user.name || 'U').charAt(0));

        if (user.profile_url) {
            $('#drawer-profile-link').attr('href', user.profile_url).removeClass('d-none');
        } else {
            $('#drawer-profile-link').addClass('d-none');
        }
    }

    // Toggle Profile Drawer
    $('#toggle-profile-btn, #close-profile-btn').on('click', function() {
        $('#profile-drawer').toggleClass('open');
    });

    // Refresh Button
    $('#refresh-chat-btn').on('click', function() {
        if (currentConversationId) {
            openConversation(currentConversationId);
        }
    });

    // Copy Phone
    $('#copy-phone-btn').on('click', function() {
        let phone = $('#active-chat-phone').text();
        navigator.clipboard.writeText(phone).then(function() {
            toastr.success('تم نسخ الرقم');
        });
    });

    // Send Normal Message
    $('#chat-form').on('submit', function(e) {
        e.preventDefault();
        let input = $('#chat-input');
        let text = input.val().trim();
        if (!text || !currentConversationId) return;

        let btn = $('#send-btn');
        btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin"></i>');

        $.ajax({
            url: "{{ url('admin/whatsapp-chat') }}/" + currentConversationId + "/send",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                message: text
            },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="ti ti-send"></i>');
                if (res.status === 'success') {
                    input.val('');
                    let tempBubble = `
                        <div class="wa-bubble-row outbound">
                            <div class="wa-bubble">
                                <div class="wa-bubble-content">${escapeHtml(text).replace(/\n/g, '<br>')}</div>
                                <div class="wa-bubble-meta">
                                    <span>${res.time || 'الآن'}</span>
                                    <i class="ti ti-check text-muted"></i>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#chat-messages').append(tempBubble);
                    scrollToBottom();
                    moveConversationToTop(currentConversationId, text.substring(0, 55), 'الآن');
                } else if (res.code === 'window_closed') {
                    updateWindowUI(false, 0);
                    Swal.fire({
                        title: 'نافذة الـ 24 ساعة مغلقة',
                        text: res.message,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'إرسال قالب معتمد',
                        cancelButtonText: 'إلغاء',
                        customClass: {
                            confirmButton: 'btn btn-primary me-3',
                            cancelButton: 'btn btn-label-secondary'
                        },
                        buttonsStyling: false
                    }).then(function(result) {
                        if (result.value) {
                            $('#sendTemplateModal').modal('show');
                        }
                    });
                } else {
                    toastr.error(res.message || 'فشل إرسال الرسالة');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="ti ti-send"></i>');
                toastr.error('حدث خطأ في الاتصال بالخادم');
            }
        });
    });

    // Attach File Trigger & Upload
    $('#chat-attach-btn').on('click', function() {
        if (!currentConversationId) return;
        $('#chat-file-input').trigger('click');
    });

    $('#chat-file-input').on('change', function() {
        let file = this.files[0];
        if (!file || !currentConversationId) return;

        let tempId = 'temp_file_' + Date.now();
        let tempBubble = `
            <div class="wa-bubble-row outbound" id="${tempId}">
                <div class="wa-bubble">
                    <div class="d-flex align-items-center gap-2">
                        <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                        <span class="small">جاري إرسال ${escapeHtml(file.name)}...</span>
                    </div>
                    <div class="wa-bubble-meta">
                        <span>الآن</span>
                        <i class="ti ti-clock text-muted"></i>
                    </div>
                </div>
            </div>
        `;
        $('#chat-messages').append(tempBubble);
        scrollToBottom();

        let formData = new FormData();
        formData.append('file', file);
        formData.append('_token', "{{ csrf_token() }}");

        $.ajax({
            url: "{{ url('admin/whatsapp-chat') }}/" + currentConversationId + "/send-media",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status === 'success') {
                    $(`#${tempId}`).replaceWith(buildMessageRowHtml({
                        id: Date.now(),
                        direction: 'outbound',
                        message_type: res.media_type || 'document',
                        media_url: res.media_url,
                        media_filename: res.media_filename,
                        content: res.caption || res.media_filename,
                        status: 'sent',
                        time: res.time || 'الآن'
                    }));
                    moveConversationToTop(currentConversationId, (res.caption || res.media_filename || 'ملف مرفق').substring(0, 55), 'الآن');
                } else {
                    $(`#${tempId} .wa-bubble-meta`).html(`<span class="text-danger small">${res.message || 'فشل الإرسال'}</span>`);
                }
            },
            error: function(xhr) {
                let errMsg = xhr.responseJSON ? xhr.responseJSON.message : 'فشل رفع الملف';
                $(`#${tempId} .wa-bubble-meta`).html(`<span class="text-danger small">${errMsg}</span>`);
            },
            complete: function() {
                $('#chat-file-input').val('');
            }
        });
    });

    // Template Preview in Modal
    function updateTemplatePreview() {
        let opt = $('#selected-template-name option:selected');
        let body = opt.data('body') || opt.val();
        $('#template-preview-box').text(body);
    }
    $('#selected-template-name').on('change', updateTemplatePreview);
    updateTemplatePreview();

    // Send Template Form
    $('#template-send-form').on('submit', function(e) {
        e.preventDefault();
        if (!currentConversationId) return;

        let templateName = $('#selected-template-name').val();
        let btn = $('#submit-template-btn');
        btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i> إرسال...');

        $.ajax({
            url: "{{ url('admin/whatsapp-chat') }}/" + currentConversationId + "/send-template",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                template_name: templateName
            },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> إرسال القالب الآن');
                $('#sendTemplateModal').modal('hide');

                if (res.status === 'success') {
                    toastr.success(res.message);
                    openConversation(currentConversationId);
                } else {
                    toastr.error(res.message || 'فشل إرسال القالب');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> إرسال القالب الآن');
                toastr.error('حدث خطأ أثناء إرسال القالب');
            }
        });
    });

    // Start New Chat Form
    $('#new-chat-form').on('submit', function(e) {
        e.preventDefault();
        let phone = $('#new-chat-phone').val().replace(/[^0-9]/g, '');
        let template = $('#new-chat-template').val();
        let btn = $('#new-chat-submit-btn');

        if (!phone) {
            toastr.error('يرجى إدخال رقم الهاتف');
            return;
        }

        btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i> إرسال...');

        $.ajax({
            url: "{{ route('admin.whatsapp-chat.start-new') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                phone: phone,
                template_name: template
            },
            success: function(res) {
                btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> إرسال وبدء المحادثة');
                if (res.status === 'success') {
                    $('#newChatModal').modal('hide');
                    $('#new-chat-phone').val('');
                    toastr.success(res.message);
                    if (res.conversation_id) {
                        let existingItem = $(`.wa-conversation-item[data-id="${res.conversation_id}"]`);
                        if (existingItem.length) {
                            openConversation(res.conversation_id);
                        } else {
                            setTimeout(() => {
                                window.location.href = "{{ url('admin/whatsapp-chat') }}?conversation_id=" + res.conversation_id;
                            }, 400);
                        }
                    }
                } else {
                    toastr.error(res.message || 'فشل بدء المحادثة');
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> إرسال وبدء المحادثة');
                let errMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'فشل بدء المحادثة';
                toastr.error(errMsg);
            }
        });
    });

    function escapeHtml(text) {
        if (!text) return '';
        let map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Auto open conversation if activeConversationId provided in URL
    let autoId = "{{ $activeConversationId }}";
    if (autoId) {
        openConversation(autoId);
    }
});
</script>
@endsection
