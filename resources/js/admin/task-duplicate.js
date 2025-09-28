/**
 * Task Duplicate Functionality
 * يحتوي على الدوال المطلوبة لتكرار المهام
 */

/**
 * تكرار مهمة
 * @param {number} taskId - معرف المهمة المراد تكرارها
 * @param {function} onSuccess - دالة يتم استدعاؤها عند النجاح (اختيارية)
 * @param {function} onError - دالة يتم استدعاؤها عند الخطأ (اختيارية)
 */
function duplicateTask(taskId, onSuccess = null, onError = null) {
    // التأكد من وجود معرف المهمة
    if (!taskId) {
        console.error('Task ID is required');
        if (onError) onError('Task ID is required');
        return;
    }

    // رسالة التأكيد
    if (!confirm('هل تريد تكرار هذه المهمة؟\n\nسيتم إنشاء مهمة جديدة بنفس البيانات مع إعادة تعيين الحالة والسائق.')) {
        return;
    }

    // إظهار مؤشر التحميل
    const loadingHtml = `
        <div class="d-flex align-items-center">
            <div class="spinner-border spinner-border-sm me-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            جاري تكرار المهمة...
        </div>
    `;

    // البحث عن الزر وتعطيله مؤقتاً
    const duplicateBtn = document.querySelector(`[onclick*="duplicateTask(${taskId})"]`);
    let originalBtnContent = '';
    
    if (duplicateBtn) {
        originalBtnContent = duplicateBtn.innerHTML;
        duplicateBtn.innerHTML = loadingHtml;
        duplicateBtn.disabled = true;
    }

    // إرسال الطلب
    fetch(`/admin/tasks/${taskId}/duplicate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        // إعادة تفعيل الزر
        if (duplicateBtn) {
            duplicateBtn.innerHTML = originalBtnContent;
            duplicateBtn.disabled = false;
        }

        if (data.status === 1) {
            // نجح التكرار
            const message = data.message || 'تم تكرار المهمة بنجاح';
            
            // إظهار رسالة النجاح
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تم بنجاح!',
                    text: message,
                    icon: 'success',
                    confirmButtonText: 'عرض المهمة الجديدة',
                    showCancelButton: true,
                    cancelButtonText: 'البقاء هنا'
                }).then((result) => {
                    if (result.isConfirmed && data.task_id) {
                        // الانتقال إلى المهمة الجديدة
                        window.location.href = `/admin/tasks/show/${data.task_id}`;
                    } else {
                        // إعادة تحميل الصفحة الحالية
                        if (typeof dt_task !== 'undefined' && dt_task.ajax) {
                            dt_task.ajax.reload();
                        } else {
                            location.reload();
                        }
                    }
                });
            } else {
                // استخدام alert عادي إذا لم يكن SweetAlert متاحاً
                const goToNewTask = confirm(message + '\n\nهل تريد الانتقال إلى المهمة الجديدة؟');
                if (goToNewTask && data.task_id) {
                    window.location.href = `/admin/tasks/show/${data.task_id}`;
                } else {
                    if (typeof dt_task !== 'undefined' && dt_task.ajax) {
                        dt_task.ajax.reload();
                    } else {
                        location.reload();
                    }
                }
            }

            // استدعاء دالة النجاح المخصصة
            if (onSuccess) {
                onSuccess(data);
            }

        } else {
            // فشل التكرار
            const errorMessage = data.message || 'حدث خطأ أثناء تكرار المهمة';
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'خطأ!',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'موافق'
                });
            } else {
                alert('خطأ: ' + errorMessage);
            }

            // استدعاء دالة الخطأ المخصصة
            if (onError) {
                onError(data);
            }
        }
    })
    .catch(error => {
        console.error('Error duplicating task:', error);
        
        // إعادة تفعيل الزر
        if (duplicateBtn) {
            duplicateBtn.innerHTML = originalBtnContent;
            duplicateBtn.disabled = false;
        }

        const errorMessage = 'حدث خطأ في الاتصال بالخادم';
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'خطأ في الاتصال!',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'موافق'
            });
        } else {
            alert('خطأ: ' + errorMessage);
        }

        // استدعاء دالة الخطأ المخصصة
        if (onError) {
            onError(error);
        }
    });
}

/**
 * إضافة زر التكرار إلى جدول المهام
 * @param {number} taskId - معرف المهمة
 * @param {string} buttonClass - فئة CSS للزر (اختيارية)
 * @returns {string} HTML للزر
 */
function getDuplicateButton(taskId, buttonClass = 'btn btn-sm btn-outline-primary') {
    return `
        <button type="button" 
                class="${buttonClass}" 
                onclick="duplicateTask(${taskId})"
                title="تكرار المهمة"
                data-bs-toggle="tooltip">
            <i class="bi bi-files"></i>
        </button>
    `;
}

/**
 * إضافة عنصر قائمة التكرار إلى dropdown menu
 * @param {number} taskId - معرف المهمة
 * @returns {string} HTML لعنصر القائمة
 */
function getDuplicateMenuItem(taskId) {
    return `
        <li>
            <a class="dropdown-item" 
               href="javascript:void(0)" 
               onclick="duplicateTask(${taskId})">
                <i class="bi bi-files me-2"></i>
                تكرار المهمة
            </a>
        </li>
    `;
}

/**
 * تهيئة tooltips للأزرار الجديدة
 */
function initializeDuplicateTooltips() {
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// تهيئة tooltips عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    initializeDuplicateTooltips();
});

// إعادة تهيئة tooltips عند إعادة تحميل DataTable
if (typeof $ !== 'undefined') {
    $(document).on('draw.dt', function() {
        initializeDuplicateTooltips();
    });
}
