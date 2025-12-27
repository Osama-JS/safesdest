{{-- Modal لإرسال إشعار يدوي --}}
<div class="modal fade" id="sendNotificationModal" tabindex="-1" aria-labelledby="sendNotificationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header ">
                <h5 class="modal-title" id="sendNotificationModalLabel">
                    <i class="ti ti-bell me-2"></i>
                    إرسال إشعار
                </h5>
                <button type="button" class="btn-close " data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="notificationForm">
                    @csrf
                    <input type="hidden" id="notification_target_id" name="target_id">
                    <input type="hidden" id="notification_target_type" name="target_type">
                    <input type="hidden" id="notification_endpoint" name="endpoint">

                    <div class="mb-3">
                        <label for="notification_recipient" class="form-label fw-bold">
                            <i class="ti ti-user me-1"></i>
                            المستلم
                        </label>
                        <input type="text" class="form-control" id="notification_recipient" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="notification_title" class="form-label fw-bold">
                            <i class="ti ti-heading me-1"></i>
                            عنوان الإشعار <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="notification_title"
                               name="title" required maxlength="255"
                               placeholder="أدخل عنوان الإشعار">
                        <div class="invalid-feedback">
                            يرجى إدخال عنوان الإشعار
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notification_body" class="form-label fw-bold">
                            <i class="ti ti-align-left me-1"></i>
                            محتوى الإشعار <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="notification_body"
                                  name="body" rows="4" required maxlength="500"
                                  placeholder="أدخل محتوى الإشعار"></textarea>
                        <small class="text-muted">
                            <span id="char_count">0</span> / 500 حرف
                        </small>
                        <div class="invalid-feedback">
                            يرجى إدخال محتوى الإشعار
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notification_type" class="form-label fw-bold">
                            <i class="ti ti-tag me-1"></i>
                            نوع الإشعار
                        </label>
                        <select class="form-select" id="notification_type" name="type">
                            <option value="admin_message">رسالة إدارية</option>
                            <option value="important">مهم</option>
                            <option value="reminder">تذكير</option>
                            <option value="warning">تحذير</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>
                    إلغاء
                </button>
                <button type="button" class="btn btn-primary" id="sendNotificationBtn" onclick="sendNotification()">
                    <i class="ti ti-send me-1"></i>
                    <span id="btnText">إرسال الإشعار</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm d-none" role="status">
                        <span class="visually-hidden">جاري الإرسال...</span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * فتح نافذة إرسال الإشعار
 * @param {number} targetId - معرف الهدف (سائق، عميل، مهمة)
 * @param {string} targetType - نوع الهدف (driver, customer, task, task-customer)
 * @param {string} targetName - اسم المستلم
 * @param {string} endpoint - نقطة النهاية للإرسال
 */
window.openNotificationModal = function(targetId, targetType, targetName = '', endpoint = '') {
    console.log('Opening notification modal for:', targetId, targetType, targetName);

    // تعيين القيم المخفية
    document.getElementById('notification_target_id').value = targetId;
    document.getElementById('notification_target_type').value = targetType;
    document.getElementById('notification_endpoint').value = endpoint;

    // تعيين اسم المستلم
    document.getElementById('notification_recipient').value = targetName || 'غير محدد';

    // تعيين عنوان افتراضي
    if (targetName) {
        document.getElementById('notification_title').value = 'رسالة إلى ' + targetName;
    } else {
        document.getElementById('notification_title').value = '';
    }

    // مسح المحتوى السابق
    document.getElementById('notification_body').value = '';
    document.getElementById('char_count').textContent = '0';
    document.getElementById('notification_type').value = 'admin_message';

    // إزالة حالات التحقق السابقة
    document.getElementById('notificationForm').classList.remove('was-validated');

    // فتح النافذة
    const modalElement = document.getElementById('sendNotificationModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        console.error('Modal element not found!');
    }
}

/**
 * إرسال الإشعار
 */
window.sendNotification = function() {
    const form = document.getElementById('notificationForm');
    const btn = document.getElementById('sendNotificationBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');

    // التحقق من صحة النموذج
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }

    // تعطيل الزر ومنع الإرسال المتكرر
    btn.disabled = true;
    btnText.classList.add('d-none');
    btnSpinner.classList.remove('d-none');

    const formData = new FormData(form);
    const targetType = document.getElementById('notification_target_type').value;
    const targetId = document.getElementById('notification_target_id').value;
    let endpoint = document.getElementById('notification_endpoint').value;

    // تحديد الـ endpoint بناءً على نوع الهدف إذا لم يتم تحديده
    if (!endpoint) {
        if (targetType === 'driver') {
            endpoint = '{{ route("admin.notifications.send.driver") }}';
            formData.append('driver_id', targetId);
        } else if (targetType === 'customer') {
            endpoint = '{{ route("admin.notifications.send.customer") }}';
            formData.append('customer_id', targetId);
        } else if (targetType === 'task') {
            endpoint = '{{ route("admin.notifications.send.task.driver") }}';
            formData.append('task_id', targetId);
        } else if (targetType === 'task-customer') {
            endpoint = '{{ route("admin.notifications.send.task.customer") }}';
            formData.append('task_id', targetId);
        }
    }

    console.log('Sending notification to:', endpoint);

    // إرسال الطلب
    fetch(endpoint, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        // إعادة تفعيل الزر
        btn.disabled = false;
        btnText.classList.remove('d-none');
        btnSpinner.classList.add('d-none');

        if (data.success) {
            // إغلاق النافذة
            const modal = bootstrap.Modal.getInstance(document.getElementById('sendNotificationModal'));
            if (modal) {
                modal.hide();
            }

            // عرض رسالة نجاح
            showNotificationAlert('success', data.message || 'تم إرسال الإشعار بنجاح');

            // إعادة تعيين النموذج
            form.reset();
            form.classList.remove('was-validated');
        } else {
            // عرض رسالة خطأ
            showNotificationAlert('error', data.message || 'حدث خطأ أثناء إرسال الإشعار');
        }
    })
    .catch(error => {
        console.error('Error:', error);

        // إعادة تفعيل الزر
        btn.disabled = false;
        btnText.classList.remove('d-none');
        btnSpinner.classList.add('d-none');

        // عرض رسالة خطأ
        showNotificationAlert('error', 'حدث خطأ أثناء إرسال الإشعار. يرجى المحاولة مرة أخرى.');
    });
}

/**
 * عرض تنبيه للمستخدم
 * @param {string} type - نوع التنبيه (success, error, warning, info)
 * @param {string} message - رسالة التنبيه
 */
window.showNotificationAlert = function(type, message) {
    // استخدام SweetAlert2 إذا كان متاحاً
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type === 'error' ? 'error' : 'success',
            title: type === 'error' ? 'خطأ' : 'نجاح',
            text: message,
            confirmButtonText: 'حسناً',
            timer: 3000
        });
    } else {
        // استخدام alert عادي كبديل
        alert(message);
    }
}

// عداد الأحرف
document.addEventListener('DOMContentLoaded', function() {
    const bodyTextarea = document.getElementById('notification_body');
    const charCount = document.getElementById('char_count');

    if (bodyTextarea && charCount) {
        bodyTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
});
</script>

<style>


#sendNotificationModal .form-label {
    color: #495057;
}

#sendNotificationModal .form-control:focus,
#sendNotificationModal .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

#sendNotificationModal .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

#sendNotificationModal .btn-primary:hover {
    background: linear-gradient(135deg, #5568d3 0%, #6a3f8f 100%);
}
</style>
