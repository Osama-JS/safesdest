<!-- Real-Time In-App Notification Toaster Container & Styles -->
<style>
/* Notification Toaster Container */
.admin-toast-container {
    position: fixed !important;
    bottom: 24px !important;
    z-index: 109999 !important;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 420px;
    width: calc(100vw - 48px);
    pointer-events: none;
}

/* RTL: Arabic -> Bottom Left */
html[dir="rtl"] .admin-toast-container,
[dir="rtl"] .admin-toast-container {
    left: 24px !important;
    right: auto !important;
}

/* LTR: English -> Bottom Right */
html[dir="ltr"] .admin-toast-container,
[dir="ltr"] .admin-toast-container,
html:not([dir="rtl"]) .admin-toast-container {
    right: 24px !important;
    left: auto !important;
}

/* Notification Toast Card */
.app-notification-toast {
    pointer-events: auto;
    background: #ffffff;
    border: 1px solid rgba(75, 70, 92, 0.12);
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12), 0 4px 12px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    position: relative;
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    opacity: 0;
    transform: translateY(20px) scale(0.96);
}

/* Dark theme support */
[data-bs-theme="dark"] .app-notification-toast,
.dark-style .app-notification-toast {
    background: #2f3349;
    border-color: rgba(255, 255, 255, 0.12);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
}

.app-notification-toast.toast-show {
    opacity: 1;
    transform: translateY(0) scale(1);
}

.app-notification-toast.toast-hide {
    opacity: 0;
    transform: translateY(20px) scale(0.92);
}

/* 6-Second Progress Bar */
.app-notification-toast .toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: linear-gradient(90deg, #7367f0, #9e95f5);
    width: 100%;
    transform-origin: left;
    animation: toastProgressBar 6s linear forwards;
}

html[dir="rtl"] .app-notification-toast .toast-progress,
[dir="rtl"] .app-notification-toast .toast-progress {
    right: 0;
    left: auto;
    transform-origin: right;
}

@keyframes toastProgressBar {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}

.app-notification-toast:hover .toast-progress {
    animation-play-state: paused;
}
</style>

<!-- In-App Notification Toast Container -->
<div id="adminToastContainer" class="admin-toast-container" aria-live="polite"></div>

<!-- Fallback container for legacy element check -->
<div id="inAppNotificationModal" style="display:none;" aria-hidden="true"></div>

