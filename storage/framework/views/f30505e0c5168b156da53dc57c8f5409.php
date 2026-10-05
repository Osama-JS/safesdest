<?php if(auth()->check() && auth()->user()->can('view_whatsapp_chat')): ?>
<!-- WhatsApp Draggable Floating Widget -->
<div id="whatsapp-floating-container" dir="<?php echo e(app()->getLocale() == 'ar' ? 'rtl' : 'ltr'); ?>">

    <!-- Mini Floating Toast Notification (Shows on incoming message) -->
    <div id="whatsapp-fab-toast" class="whatsapp-fab-toast d-none">
        <div class="d-flex align-items-center gap-2">
            <span class="whatsapp-toast-avatar" id="whatsapp-toast-avatar">💬</span>
            <div class="whatsapp-toast-content">
                <strong id="whatsapp-toast-title">رسالة جديدة</strong>
                <p id="whatsapp-toast-body" class="mb-0 text-truncate"></p>
            </div>
            <button type="button" class="btn-close btn-close-white btn-sm ms-auto" id="whatsapp-toast-close" aria-label="Close"></button>
        </div>
    </div>

    <!-- Draggable Floating Action Button (FAB) -->
    <div id="whatsapp-floating-fab" class="whatsapp-floating-fab" title="محادثات واتساب الفورية (اضغط للفتح أو اسحب للتحريك)">
        <div class="whatsapp-fab-pulse" id="whatsapp-fab-pulse"></div>
        <div class="whatsapp-fab-inner">
            <svg class="whatsapp-fab-icon" viewBox="0 0 24 24" width="32" height="32" fill="currentColor">
                <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.634.073-1.026-.067-.534-.19-1.229-.537-2.071-1.378-.842-.841-1.189-1.536-1.379-2.07-.14-.392-.112-.714-.067-1.026.05-.333.419-1.026.824-1.17.135-.048.271-.034.378.016.14.065.234.195.297.351.144.357.489 1.192.532 1.28.043.088.072.19.014.305-.057.116-.086.189-.172.29-.086.1-.182.223-.26.299-.086.084-.176.175-.076.347.1.172.444.733.953 1.242.656.656 1.209.86 1.381.96.172.1.273.086.375-.031.102-.117.439-.512.556-.687.117-.175.234-.146.39-.088.156.058.988.465 1.158.55.17.085.284.127.326.199.043.072.043.418-.101.823zM12 2C6.477 2 2 6.477 2 12c0 1.891.526 3.66 1.438 5.168L2 22l4.98-1.393A9.957 9.957 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18.167c-1.615 0-3.125-.494-4.382-1.343l-.314-.212-2.923.818.835-2.846-.231-.336A8.125 8.125 0 0 1 3.833 12c0-4.503 3.664-8.167 8.167-8.167 4.503 0 8.167 3.664 8.167 8.167 0 4.503-3.664 8.167-8.167 8.167z"/>
            </svg>
            <span class="whatsapp-fab-badge d-none" id="whatsapp-fab-badge">0</span>
        </div>
    </div>

    <!-- Draggable Floating Chat Window -->
    <div id="whatsapp-floating-window" class="whatsapp-floating-window d-none">
        <!-- Window Header (Drag Handle) -->
        <div class="whatsapp-window-header" id="whatsapp-window-header">
            <div class="d-flex align-items-center gap-2">
                <i class="ti ti-grip-vertical text-white opacity-75 whatsapp-drag-icon"></i>
                <div class="whatsapp-header-brand d-flex align-items-center gap-2">
                    <span class="whatsapp-header-dot"></span>
                    <h6 class="mb-0 text-white fw-bold">محادثات واتساب الفورية</h6>
                    <span class="badge bg-white text-success rounded-pill fw-bold ms-1" id="whatsapp-window-unread-badge">0</span>
                </div>
            </div>
            <div class="whatsapp-header-actions d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-icon text-white rounded-circle" id="whatsapp-window-refresh" title="تحديث المحادثات">
                    <i class="ti ti-refresh ti-xs"></i>
                </button>
                <a href="<?php echo e(route('admin.whatsapp-chat.index')); ?>" target="_blank" class="btn btn-sm btn-icon text-white rounded-circle" title="فتح في صفحة كاملة">
                    <i class="ti ti-external-link ti-xs"></i>
                </a>
                <button type="button" class="btn btn-sm btn-icon text-white rounded-circle" id="whatsapp-window-compact" title="تصغير النافذة (وضع شاشات الجوال/العرض المصغر)">
                    <i class="ti ti-device-mobile ti-xs"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon text-white rounded-circle" id="whatsapp-window-maximize" title="تكبير / استعادة">
                    <i class="ti ti-arrows-maximize ti-xs"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon text-white rounded-circle" id="whatsapp-window-close" title="إغلاق">
                    <i class="ti ti-x ti-xs"></i>
                </button>
            </div>
        </div>

        <!-- Window Body (Dual Pane / Master-Detail) -->
        <div class="whatsapp-window-body">
            <!-- Sidebar: Conversations List -->
            <div class="whatsapp-widget-sidebar" id="whatsapp-widget-sidebar">
                <!-- Search & Filters -->
                <div class="p-2 border-bottom bg-white">
                    <div class="input-group input-group-merge input-group-sm mb-2">
                        <span class="input-group-text"><i class="ti ti-search ti-xs"></i></span>
                        <input type="text" class="form-control" id="whatsapp-widget-search" placeholder="بحث بالاسم أو الرقم...">
                        <button class="btn btn-outline-secondary btn-sm d-none" type="button" id="whatsapp-widget-search-clear">
                            <i class="ti ti-x ti-xs"></i>
                        </button>
                    </div>
                    <div class="d-flex gap-1 overflow-auto whatsapp-filters-bar pb-1">
                        <button type="button" class="btn btn-xs btn-primary rounded-pill flex-shrink-0 active filter-btn" data-filter="all">الكل</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill flex-shrink-0 filter-btn" data-filter="unread">غير المقروء</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill flex-shrink-0 filter-btn" data-filter="customers">العملاء</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill flex-shrink-0 filter-btn" data-filter="drivers">السائقين</button>
                    </div>
                </div>

                <!-- Conversations Scroll List -->
                <div class="whatsapp-conversations-list" id="whatsapp-widget-conversations-list">
                    <div class="text-center py-4 text-muted" id="whatsapp-conversations-loading">
                        <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                        <p class="mt-2 mb-0 small">جاري تحميل المحادثات...</p>
                    </div>
                </div>
            </div>

            <!-- Chat Pane: Active Thread -->
            <div class="whatsapp-widget-chat-pane" id="whatsapp-widget-chat-pane">
                <!-- Empty State (When no conversation active) -->
                <div class="whatsapp-chat-empty text-center p-4 my-auto" id="whatsapp-chat-empty-state">
                    <div class="whatsapp-empty-icon mb-3">
                        <i class="ti ti-brand-whatsapp text-success" style="font-size: 3.5rem;"></i>
                    </div>
                    <h6 class="fw-bold mb-1">حدد محادثة للبدء</h6>
                    <p class="text-muted small mb-0">اختر من قائمة المحادثات على اليمين للمراسلة الفورية وقراءة الردود.</p>
                </div>

                <!-- Active Chat Container (Hidden until conversation clicked) -->
                <div class="whatsapp-chat-active-container d-none flex-column h-100" id="whatsapp-chat-active-container">
                    <!-- Chat Header -->
                    <div class="whatsapp-chat-header p-2 border-bottom bg-white d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <!-- Back Button for Mobile view / Compact Mode -->
                            <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-circle whatsapp-back-btn" id="whatsapp-mobile-back" title="الرجوع لقائمة المحادثات">
                                <i class="ti ti-arrow-right"></i>
                            </button>
                            <div class="whatsapp-user-avatar" id="whatsapp-active-avatar">
                                <span class="avatar-initials bg-label-success rounded-circle">👤</span>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold small text-truncate" id="whatsapp-active-name" style="max-width: 170px;">اسم العميل</h6>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="text-muted small" id="whatsapp-active-phone">0500000000</span>
                                    <span class="badge bg-label-primary rounded-pill px-1" id="whatsapp-active-badge">عميل</span>
                                </div>
                            </div>
                        </div>

                        <!-- 24H Window Badge -->
                        <div class="text-end">
                            <span class="badge bg-label-success rounded-pill" id="whatsapp-window-status-badge" title="نافذة الـ 24 ساعة للمراسلة الحرة">
                                <i class="ti ti-clock-check ti-xs me-1"></i>
                                <span>نافذة مفتوحة</span>
                            </span>
                        </div>
                    </div>

                    <!-- Messages Stream Container -->
                    <div class="whatsapp-messages-stream flex-grow-1 p-3 overflow-auto" id="whatsapp-messages-stream">
                        <div class="text-center py-4" id="whatsapp-messages-loading">
                            <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                        </div>
                    </div>

                    <!-- Input Bar -->
                    <div class="whatsapp-chat-footer p-2 border-top bg-white">
                        <form id="whatsapp-widget-reply-form" class="d-flex align-items-center gap-2">
                            <!-- Quick Template Dropdown Button -->
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary rounded-circle" data-bs-toggle="dropdown" aria-expanded="false" title="إرسال قالب واتساب معتمد">
                                    <i class="ti ti-template ti-xs"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" id="whatsapp-templates-dropdown" style="max-height: 250px; overflow-y: auto;">
                                    <li class="dropdown-header text-uppercase small">القوالب المعتمدة</li>
                                    <li><span class="dropdown-item text-muted small">لا توجد قوالب</span></li>
                                </ul>
                            </div>

                            <!-- Text Input -->
                            <input type="text" class="form-control form-control-sm rounded-pill" id="whatsapp-widget-message-input" placeholder="اكتب ردك هنا..." autocomplete="off" required>

                            <!-- Send Button -->
                            <button type="submit" class="btn btn-sm btn-success rounded-circle btn-icon flex-shrink-0" id="whatsapp-widget-send-btn" title="إرسال">
                                <i class="ti ti-send ti-xs"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ─────────────────────────────────────────────────────────────
   WhatsApp Draggable Floating Widget Styles
   ───────────────────────────────────────────────────────────── */
#whatsapp-floating-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 0;
    height: 0;
    z-index: 99999; /* Ensure it stays above navbar, modals, and offcanvas */
    pointer-events: none; /* Allows click-through outside elements */
}

/* Floating Action Button (FAB) */
.whatsapp-floating-fab {
    position: fixed;
    bottom: 25px;
    left: 25px; /* Default RTL position */
    width: 58px;
    height: 58px;
    border-radius: 50%;
    cursor: grab;
    pointer-events: auto;
    user-select: none;
    touch-action: none;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 24px rgba(37, 211, 102, 0.45);
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
}

[dir="ltr"] .whatsapp-floating-fab {
    left: auto;
    right: 25px;
}

.whatsapp-floating-fab:hover {
    transform: scale(1.08);
    box-shadow: 0 12px 28px rgba(37, 211, 102, 0.6);
}

.whatsapp-floating-fab:active {
    cursor: grabbing;
    transform: scale(0.96);
}

.whatsapp-fab-inner {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    position: relative;
}

.whatsapp-fab-pulse {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background: rgba(37, 211, 102, 0.4);
    animation: whatsapp-pulse 2s infinite;
    pointer-events: none;
    display: none;
}

@keyframes whatsapp-pulse {
    0% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.4); opacity: 0; }
    100% { transform: scale(1.4); opacity: 0; }
}

.whatsapp-fab-badge {
    position: absolute;
    top: -3px;
    right: -3px;
    background: #ea5455;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    min-width: 22px;
    height: 22px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 6px rgba(234, 84, 85, 0.5);
    animation: badge-bounce 0.4s ease;
}

@keyframes badge-bounce {
    0% { transform: scale(0); }
    80% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Toast Notification near FAB */
.whatsapp-fab-toast {
    position: fixed;
    bottom: 95px;
    left: 25px;
    max-width: 320px;
    background: rgba(18, 140, 126, 0.96);
    backdrop-filter: blur(8px);
    color: #ffffff;
    border-radius: 14px;
    padding: 10px 14px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    pointer-events: auto;
    z-index: 100001;
    animation: toast-slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

[dir="ltr"] .whatsapp-fab-toast {
    left: auto;
    right: 25px;
}

@keyframes toast-slide-up {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

.whatsapp-toast-content strong {
    font-size: 13px;
    display: block;
}
.whatsapp-toast-content p {
    font-size: 12px;
    opacity: 0.92;
    max-width: 220px;
}

/* Draggable Floating Chat Window */
.whatsapp-floating-window {
    position: fixed;
    bottom: 95px;
    left: 25px;
    width: 760px;
    max-width: calc(100vw - 30px);
    height: 580px;
    max-height: calc(100vh - 110px);
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.22), 0 0 0 1px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    pointer-events: auto;
    z-index: 100002;
    transition: width 0.25s ease, height 0.25s ease, border-radius 0.25s ease;
}

[dir="ltr"] .whatsapp-floating-window {
    left: auto;
    right: 25px;
}

/* Maximize / Fullscreen state */
.whatsapp-floating-window.maximized {
    top: 15px !important;
    left: 15px !important;
    right: 15px !important;
    bottom: 15px !important;
    width: calc(100vw - 30px) !important;
    height: calc(100vh - 30px) !important;
    max-width: none !important;
    max-height: none !important;
    border-radius: 14px !important;
}

/* Compact Mode (Single Column / Mobile View on Desktop) */
.whatsapp-floating-window.compact-mode {
    width: 380px !important;
    height: 560px !important;
    max-width: calc(100vw - 30px) !important;
    max-height: calc(100vh - 100px) !important;
}

.whatsapp-floating-window.compact-mode .whatsapp-widget-sidebar {
    width: 100% !important;
}

.whatsapp-floating-window.compact-mode .whatsapp-widget-chat-pane {
    width: 100% !important;
}

.whatsapp-floating-window.compact-mode .whatsapp-window-body.chat-active .whatsapp-widget-sidebar {
    display: none !important;
}

.whatsapp-floating-window.compact-mode .whatsapp-window-body:not(.chat-active) .whatsapp-widget-chat-pane {
    display: none !important;
}

/* Back button in chat header - hidden in normal dual pane, visible in compact/mobile */
.whatsapp-back-btn {
    display: none;
}

.whatsapp-floating-window.compact-mode .whatsapp-back-btn,
.whatsapp-floating-window.is-mobile-screen .whatsapp-back-btn {
    display: inline-flex !important;
}

/* Window Header */
.whatsapp-window-header {
    background: linear-gradient(135deg, #128C7E 0%, #075E54 100%);
    color: #ffffff;
    padding: 10px 14px;
    cursor: grab;
    user-select: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.whatsapp-window-header:active {
    cursor: grabbing;
}
.whatsapp-header-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #25D366;
    display: inline-block;
}

/* Window Body */
.whatsapp-window-body {
    flex: 1;
    display: flex;
    overflow: hidden;
    background: #f0f2f5;
}

/* Sidebar List */
.whatsapp-widget-sidebar {
    width: 290px;
    background: #ffffff;
    border-inline-end: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}
.whatsapp-conversations-list {
    flex: 1;
    overflow-y: auto;
}
.whatsapp-conv-item {
    padding: 10px 12px;
    border-bottom: 1px solid #f8f9fa;
    cursor: pointer;
    transition: background 0.15s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}
.whatsapp-conv-item:hover {
    background: #f8f9fa;
}
.whatsapp-conv-item.active {
    background: #e7f7ed;
    border-inline-start: 4px solid #25D366;
}
.whatsapp-conv-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
    font-size: 18px;
}
.whatsapp-conv-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Chat Pane */
.whatsapp-widget-chat-pane {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #efeae2;
    position: relative;
}
.whatsapp-messages-stream {
    background-image: radial-gradient(rgba(0,0,0,0.04) 1px, transparent 0);
    background-size: 16px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Message Bubbles */
.whatsapp-bubble {
    max-width: 78%;
    padding: 8px 12px;
    border-radius: 12px;
    font-size: 13px;
    line-height: 1.45;
    position: relative;
    word-break: break-word;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}
.whatsapp-bubble.inbound {
    align-self: flex-start;
    background: #ffffff;
    color: #111b21;
    border-top-right-radius: 3px;
}
.whatsapp-bubble.outbound {
    align-self: flex-end;
    background: #d9fdd3;
    color: #111b21;
    border-top-left-radius: 3px;
}
.whatsapp-bubble-time {
    font-size: 10px;
    color: #667781;
    margin-top: 3px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 3px;
}

/* Responsive styles for Small Screens (< 768px) */
@media (max-width: 767.98px) {
    .whatsapp-floating-fab {
        width: 52px;
        height: 52px;
        bottom: 20px;
        left: 20px;
    }
    [dir="ltr"] .whatsapp-floating-fab {
        left: auto;
        right: 20px;
    }
    .whatsapp-floating-fab svg {
        width: 28px;
        height: 28px;
    }

    .whatsapp-floating-window {
        width: 100vw !important;
        height: 90vh !important;
        bottom: 0 !important;
        left: 0 !important;
        right: 0 !important;
        top: auto !important;
        border-radius: 20px 20px 0 0 !important;
        max-width: 100vw !important;
        max-height: 90vh !important;
    }
    .whatsapp-widget-sidebar {
        width: 100% !important;
    }
    .whatsapp-widget-chat-pane {
        width: 100% !important;
    }
    .whatsapp-window-body.chat-active .whatsapp-widget-sidebar {
        display: none !important;
    }
    .whatsapp-window-body:not(.chat-active) .whatsapp-widget-chat-pane {
        display: none !important;
    }
    .whatsapp-back-btn {
        display: inline-flex !important;
    }
    /* Hide compact button on actual mobile screens since it's already full width */
    #whatsapp-window-compact {
        display: none !important;
    }
}
</style>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/layouts/sections/whatsapp/floating-chat.blade.php ENDPATH**/ ?>