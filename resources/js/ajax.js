/**
 * ===================================================================
 * AJAX UTILITIES AND FORM HANDLING
 * ===================================================================
 * This file contains utility functions for handling AJAX requests,
 * form submissions, alerts, and dynamic field generation.
 */

// ===================================================================
// FORM SUBMISSION HANDLER
// ===================================================================

/**
 * معالج إرسال النماذج العام
 * يتعامل مع جميع النماذج التي تحمل class "form_submit"
 * يدعم رفع الملفات، معالجة الأخطاء، وعرض الرسائل
 */
$(document)
  .off('submit', '.form_submit')
  .on('submit', '.form_submit', function (e) {
    e.preventDefault();
    const $this = $(this);

    // منع التكرار إذا تم الضغط أكثر من مرة
    if ($this.hasClass('submitting')) return;
    $this.addClass('submitting');

    const contentElement = document.querySelector('#content');
    const contentResetElement = document.querySelector('.content_reset');
    const imgElement = document.querySelector('.reset_image');

    // إذا كان هناك محتوى CKEditor، احصل على البيانات
    if (contentElement && CKEDITOR.instances['content']) {
      const sec = CKEDITOR.instances['content'].getData();
      $('#content').val(sec);
    }

    // عرض رسالة "جاري المعالجة..."
    $this.block({
      message:
        '<div class="d-flex justify-content-center"><p class="mb-0">Please wait...</p> <div class="sk-wave m-0"><div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div></div> </div>',
      css: {
        backgroundColor: 'transparent',
        color: '#fff',
        border: '0'
      },
      overlayCSS: {
        opacity: 0.5
      }
    });

    // إرسال الطلب Ajax
    $.ajax({
      url: $this.attr('action'),
      method: $this.attr('method'),
      data: new FormData(this),
      processData: false,
      dataType: 'json',
      contentType: false,
      success: function (data) {
        console.log(data);
        $('span.text-error').text(''); // إعادة تعيين الأخطاء

        $this.unblock({
          onUnblock: function () {
            $this.removeClass('submitting'); // إتاحة الإرسال مرة أخرى

            if (data.status === 0) {
              console.log(data.error);
              handleErrors(data.error);
              showBlockAlert('warning', 'يجب عليك التأكد من جميع البيانات المدخلة');
            } else if (data.status === 1) {
              resetCKEditor(contentElement, contentResetElement);
              resetImage(imgElement);
              document.dispatchEvent(new CustomEvent('formSubmitted', { detail: data }));
              showBlockAlert('success', data.success, 1700);
              showAlert('success', data.success, 5000, true);
            } else if (data.status === 2) {
              showAlert('error', data.error, 10000, true);
            }
          }
        });
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $this.unblock({
          onUnblock: function () {
            $this.removeClass('submitting'); // إتاحة الإرسال مرة أخرى
            console.log(errorThrown);
            showAlert('error', `فشل الطلب: ${textStatus}, ${errorThrown}`);
          }
        });
      }
    });
  });

// ===================================================================
// DELETE RECORD FUNCTION
// ===================================================================

/**
 * دالة حذف السجلات مع تأكيد المستخدم
 * تعرض نافذة تأكيد قبل الحذف وتتعامل مع الاستجابة
 * @param {string} name - اسم العنصر المراد حذفه
 * @param {string} url - رابط API للحذف
 */
export function deleteRecord(name, url) {
  Swal.fire({
    title: `Delete ${name} ?`,
    text: 'You will not be able to undo this action!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete it!',
    customClass: {
      confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
      cancelButton: 'btn btn-label-secondary waves-effect waves-light'
    },
    buttonsStyling: false
  }).then(result => {
    if (result.isConfirmed) {
      $.ajax({
        url: url,
        type: 'DELETE',
        success: function (response) {
          if (response.status === 1) {
            showAlert('success', response.success, 10000, true);
            document.dispatchEvent(new CustomEvent('deletedSuccess'));
          } else {
            showAlert('error', response.error, 10000, true);
          }
        },
        error: function () {
          showAlert('error', 'Field to delete the Recode', 10000, true);
        }
      });
    }
  });
}

// ===================================================================
// TEAM CONNECTION FUNCTION
// ===================================================================

/**
 * دالة ربط السجل بالفريق مع تأكيد المستخدم
 * تعرض نافذة تأكيد قبل الربط وتتعامل مع الاستجابة
 * @param {string} name - اسم العنصر المراد ربطه
 * @param {string} url - رابط API للربط
 */
export function connectTeam(name, url) {
  Swal.fire({
    title: `Connect ${name} ?`,
    text: 'This record will be connected to the team!',
    icon: 'info',
    showCancelButton: true,
    confirmButtonText: 'Yes, Connect yo the Team!',
    customClass: {
      confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
      cancelButton: 'btn btn-label-secondary waves-effect waves-light'
    },
    buttonsStyling: false
  }).then(result => {
    if (result.isConfirmed) {
      $.ajax({
        url: url,
        type: 'DELETE',
        success: function (response) {
          if (response.status === 1) {
            showAlert('success', response.success, 10000, true);
            document.dispatchEvent(new CustomEvent('deletedSuccess'));
          } else {
            showAlert('error', response.error, 10000, true);
          }
        },
        error: function () {
          showAlert('error', 'Field to delete the Recode', 10000, true);
        }
      });
    }
  });
}

// ===================================================================
// MODAL FORM FUNCTION
// ===================================================================

/**
 * دالة عرض نموذج في نافذة منبثقة مع إرسال البيانات
 * تستخدم لتحديث الحالات والبيانات السريعة
 * @param {Object} options - خيارات النافذة المنبثقة
 * @param {string} options.title - عنوان النافذة
 * @param {string} options.icon - أيقونة النافذة
 * @param {string} options.fields - حقول HTML للنموذج
 * @param {string} options.url - رابط الإرسال
 * @param {string} options.method - طريقة الإرسال
 * @param {Object} options.dataTable - جدول البيانات للتحديث
 * @param {Object} options.extraData - بيانات إضافية
 */
export function showFormModal(options) {
  const {
    title = 'Update Status',
    icon = 'info',
    fields = '',
    url = '',
    method = 'POST',
    dataTable = null,
    extraData = {},
    confirmButtonText = 'Confirm!',
    cancelButtonText = 'Cancel'
  } = options;

  Swal.fire({
    title: title,
    icon: icon,
    html: `
      <form id="universal-form" class="pt-0">
        ${fields}
      </form>
    `,
    showCloseButton: true,
    showCancelButton: true,
    focusConfirm: false,
    confirmButtonText: confirmButtonText,
    cancelButtonText: cancelButtonText,
    customClass: {
      confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
      cancelButton: 'btn btn-label-secondary waves-effect waves-light'
    },
    buttonsStyling: false
  }).then(result => {
    if (result.isConfirmed) {
      const formData = $('#universal-form').serializeArray();
      // دمج البيانات الإضافية إذا كانت موجودة
      for (const key in extraData) {
        formData.push({ name: key, value: extraData[key] });
      }

      $.ajax({
        url: url,
        type: method,
        data: $.param(formData),
        success: function (response) {
          showAlert(response.type, response.message, 10000, true);
          if (response.status == 1 && dataTable) {
            dataTable.draw();
          }
          document.dispatchEvent(new CustomEvent('statusChange'));
        },
        error: function (xhr, status, error) {
          showAlert('error', 'Something went wrong! : ' + error, 10000, true);
        }
      });
    }
  });
}

// ===================================================================
// ALERT AND NOTIFICATION FUNCTIONS
// ===================================================================

/**
 * دالة عرض تنبيه مؤقت على النموذج
 * تعرض رسالة ملونة فوق النموذج لفترة محددة
 * @param {string} type - نوع التنبيه (success, warning, error)
 * @param {string} message - نص الرسالة
 * @param {number} timer - مدة العرض بالميلي ثانية
 */
export function showBlockAlert(type, message, timer = 700) {
  let bgColor = type === 'success' ? 'bg-success' : 'warning' ? 'bg-warning' : 'bg-danger';

  $('.form_submit').block({
    message: `<div class="p-3 text-white ${bgColor}" style="border-radius: 5px;">${message}</div>`,
    timeout: timer,
    css: {
      backgroundColor: 'transparent',
      border: '0'
    },
    overlayCSS: {
      opacity: 0.5
    }
  });

  // فك الحظر بعد 2 ثانية للسماح للمستخدم برؤية الرسالة
  setTimeout(() => {
    $('.form_submit').unblock();
  }, 2000);
}

/**
 * دالة عرض إشعار توست
 * تعرض إشعار في أعلى الصفحة مع تأثيرات بصرية
 * @param {string} icon - نوع الإشعار (success, error, warning, info)
 * @param {string} title - نص الإشعار
 * @param {number} timer - مدة العرض بالميلي ثانية
 * @param {boolean} showConfirmButton - عرض زر التأكيد (غير مستخدم حالياً)
 */
export function showAlert(icon, title, timer, showConfirmButton = false) {
  toastr.options = {
    closeButton: true,
    progressBar: true,
    timeOut: timer || 5000, // زمن الإغلاق التلقائي
    extendedTimeOut: 5000,
    positionClass: 'toast-top-center',
    preventDuplicates: true,
    showMethod: 'fadeIn', // تأثير عند الظهور
    hideMethod: 'fadeOut', // تأثير عند الاختفاء
    showEasing: 'swing',
    hideEasing: 'linear'
  };

  // تحديد نوع التوست حسب الأيقونة
  let toastType = icon === 'success' ? 'success' : icon === 'error' ? 'error' : icon === 'warning' ? 'warning' : 'info';

  // عرض الإشعار
  let $toast = toastr[toastType](title);

  // إضافة تأثير tada بعد ظهور التوست
  if ($toast) {
    $toast.addClass('animate__animated animate__tada');
  }
}

// ===================================================================
// ERROR HANDLING FUNCTION
// ===================================================================

/**
 * دالة معالجة وعرض أخطاء التحقق من النماذج
 * تعرض رسائل الخطأ تحت الحقول المناسبة
 * @param {Object} errors - كائن الأخطاء من الخادم
 * @param {string} prefix - بادئة لأسماء الحقول (اختيارية)
 */
export function handleErrors(errors, prefix = '') {
  $('span.text-error').text(''); // إعادة تعيين الأخطاء

  $.each(errors, function (key, val) {
    // التعامل مع الحقول بالشكل: fields.0.name
    const fieldMatch = key.match(/^fields\.(\d+)\.(\w+)$/);
    if (fieldMatch) {
      const index = fieldMatch[1];
      const field = fieldMatch[2];
      const selector = 'span.field-' + index + '-' + field + '-error';
      $(selector).text(val[0]);
      return;
    }

    // التعامل مع حقول file_expiration_date بشكل خاص
    const fileExpirationMatch = key.match(/^additional_fields\.(.+)_(file|expiration)$/);
    if (fileExpirationMatch) {
      const fieldName = fileExpirationMatch[1];
      const fieldType = fileExpirationMatch[2];
      const selector = 'span.additional_fields-' + fieldName + '_' + fieldType + '-error';
      $(selector).text(val[0]);
      return;
    }

    // التعامل مع params.2.0.price أو أي تركيبة مشابهة
    const parts = key.split('.');
    if (parts.length >= 2) {
      const selector = 'span.' + prefix + parts.join('-') + '-error';
      $(selector).text(val[0]);
    } else {
      // الحقول الثابتة مثل name, description
      const selector = 'span.' + prefix + key + '-error';
      $(selector).text(val[0]);
    }
  });
}

// ===================================================================
// HELPER FUNCTIONS
// ===================================================================

/**
 * دالة إعادة تعيين محتوى محرر CKEditor
 * تمسح محتوى المحرر بعد الإرسال الناجح
 * @param {Element} contentElement - عنصر المحتوى
 * @param {Element} contentResetElement - عنصر إعادة التعيين
 */
function resetCKEditor(contentElement, contentResetElement) {
  if (contentElement && contentResetElement && CKEDITOR.instances['content']) {
    CKEDITOR.instances['content'].setData('');
  }
}

/**
 * دالة إعادة تعيين الصورة إلى الحالة الافتراضية
 * تستعيد الصورة الافتراضية بعد الإرسال الناجح
 * @param {Element} imgElement - عنصر الصورة
 */
function resetImage(imgElement) {
  if (imgElement) {
    $(imgElement).attr('src', $(imgElement).attr('data-image'));
  }
}

// ===================================================================
// TEMPLATE SELECTION HANDLER
// ===================================================================

/**
 * معالج تغيير القالب المحدد
 * يجلب حقول القالب ويعرضها ديناميكياً
 */
$('#select-template')
  .off('change')
  .on('change', function () {
    var templateId = $(this).val();

    // تنظيف الحقول الإضافية السابقة
    $('#additional-form').html('');

    if (templateId) {
      // استرجاع الحقول الخاصة بالقالب المحدد عبر AJAX
      $.ajax({
        url: baseUrl + 'admin/settings/templates/fields', // تأكد من المسار الصحيح
        type: 'GET',
        data: { id: templateId },
        success: function (response) {
          generateFields(response.fields);
          console.log(response.fields);
        },
        error: function () {
          console.log('Error loading template fields.');
        }
      });
    }
  });

// ===================================================================
// DYNAMIC FIELD GENERATION FUNCTION
// ===================================================================

/**
 * دالة إنشاء الحقول الديناميكية بناءً على القالب المحدد
 * تدعم أنواع مختلفة من الحقول مع البيانات المحفوظة مسبقاً
 * @param {Array} fields - مصفوفة حقول القالب
 * @param {Object} storedData - البيانات المحفوظة مسبقاً (للتعديل)
 */
export function generateFields(fields, storedData = {}, targetSelector = '#additional-form') {
  fields.forEach(field => {
    var inputField = '';
    var inputSpan = '';
    const storedValue = storedData[field.name]?.value || ''; // هنا نجلب القيمة المحزنة إذا وجدت
    console.log(storedData);

    // إنشاء الحقل حسب النوع
    switch (field.type) {
      case 'string':
        inputField = `<input type="text" name="additional_fields[${field.name}]" value="${storedValue}" class="form-control" placeholder="Enter ${field.name}">`;
        break;
      case 'number':
        inputField = `<input type="number" name="additional_fields[${field.name}]" value="${storedValue}" class="form-control" placeholder="Enter ${field.name}">`;
        break;
      case 'email':
        inputField = `<input type="email" name="additional_fields[${field.name}]" value="${storedValue}" class="form-control" placeholder="Enter ${field.name}">`;
        break;
      case 'date':
        inputField = `<input type="date" name="additional_fields[${field.name}]" value="${storedValue}" class="form-control">`;
        break;
      case 'textarea':
        inputField = `<textarea name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}" >${storedValue}</textarea>`;
        break;
      case 'file':
        inputField = `
        <a href="${baseUrl + 'storage/' + storedValue}">${storedValue}</a>
        <input type="file" name="additional_fields[${field.name}]"  class="form-control" >`;
        break;
      // حقل ملف مع تاريخ انتهاء صلاحية
      case 'file_expiration_date':
        const currentFile = storedData[field.name]?.value || '';
        const currentExpiration = storedData[field.name]?.expiration || '';
        const fileDisplay = currentFile
          ? `<div class="mb-2">
            <small class="text-muted">Current file:</small><br>
            <a href="${baseUrl + 'storage/' + currentFile}" target="_blank" class="text-primary">
              <i class="ti ti-file me-1"></i>${currentFile.split('/').pop()}
            </a>
          </div>`
          : '';

        // استخدام حقل value كعنوان للتاريخ إذا كان متوفراً
        const expirationLabel = field.value && field.value.trim() !== '' ? field.value : 'Expiration Date';

        inputField = `
          ${fileDisplay}
          <div class="">
            <input type="file" name="additional_fields[${field.name}_file]" class="form-control"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.jpeg,.png,.jpg,.webp,.gif">
          </div>
          <div class="mb-3">
            <label class="form-label">${expirationLabel}</label>
            <input type="date" name="additional_fields[${field.name}_expiration]"
                   value="${currentExpiration}" class="form-control" min="${new Date().toISOString().split('T')[0]}"
                   placeholder="Enter ${expirationLabel.toLowerCase()}">
            <small class="text-muted">Max size: 10MB. Allowed types: PDF, DOC, DOCX, XLS, XLSX, TXT, CSV, Images</small>
          </div>
        `;
        break;
      // حقل ملف مع نص
      case 'file_with_text':
        const currentFileWithText = storedData[field.name]?.value || '';
        const currentText = storedData[field.name]?.text || '';
        const fileWithTextDisplay = currentFileWithText
          ? `<div class="mb-2">
            <small class="text-muted">Current file:</small><br>
            <a href="${baseUrl + 'storage/' + currentFileWithText}" target="_blank" class="text-primary">
              <i class="ti ti-file me-1"></i>${currentFileWithText.split('/').pop()}
            </a>
          </div>`
          : '';

        // استخدام حقل value كعنوان للنص إذا كان متوفراً
        const textLabel = field.value && field.value.trim() !== '' ? field.value : 'Text/Number';

        inputField = `
          ${fileWithTextDisplay}
          <div class="mb-3">
            <label class="form-label">Upload File</label>
            <input type="file" name="additional_fields[${field.name}_file]" class="form-control"
                   accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.jpeg,.png,.jpg,.webp,.gif">
            <small class="text-muted">Max size: 10MB. Allowed types: PDF, DOC, DOCX, XLS, XLSX, TXT, CSV, Images</small>
          </div>
          <div class="mb-3">
            <label class="form-label">${textLabel}</label>
            <input type="text" name="additional_fields[${field.name}_text]"
                   value="${currentText}" class="form-control" placeholder="Enter ${textLabel.toLowerCase()}">
          </div>
        `;
        break;

      // حقل رابط URL
      case 'url':
        inputField = `<input type="url" name="additional_fields[${field.name}]" value="${storedValue}" class="form-control" placeholder="Enter ${field.name}" >`;
        break;

      // حقل صورة
      case 'image':
        inputField = `
        <img src="${baseUrl + 'storage/' + storedValue}">${storedValue}</a>
        <input type="file" name="additional_fields[${field.name}]"  class="form-control" >`;
        break;

      // حقل قائمة اختيار
      case 'select':
        inputField = `<select name="additional_fields[${field.name}]" class="form-select">
          ${(() => {
            try {
              const options = JSON.parse(field.value || '[]');
              return options
                .map(
                  option =>
                    `<option value="${option.value}" ${storedValue === option.value ? 'selected' : ''}>
                  ${option.name}
                </option>`
                )
                .join('');
            } catch (error) {
              console.error('Error parsing options:', error);
              return '';
            }
          })()}
        </select>`;
        break;
    }

    // إنشاء عناصر عرض الأخطاء حسب نوع الحقل
    if (field.type === 'file_expiration_date') {
      inputSpan = `
        <span class="additional_fields-${field.name}_file-error text-danger text-error d-block"></span>
        <span class="additional_fields-${field.name}_expiration-error text-danger text-error d-block"></span>
      `;
    } else if (field.type === 'file_with_text') {
      inputSpan = `
        <span class="additional_fields-${field.name}_file-error text-danger text-error d-block"></span>
        <span class="additional_fields-${field.name}_text-error text-danger text-error d-block"></span>
      `;
    } else {
      inputSpan = `<span class="additional_fields-${field.name}-error text-danger text-error"></span>`;
    }

    // إضافة الحقل إلى النموذج
    $(targetSelector).append(`
      <div class="mb-3 col-md-6">
        <label class="form-label">${field.required ? '*' : ''} ${field.label}</label>
        ${inputField}
        ${inputSpan}
      </div>
    `);
  });
}

