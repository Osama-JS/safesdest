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
        $('span.text-error').text(''); // إعادة تعيين الأخطاء

        $this.unblock({
          onUnblock: function () {
            $this.removeClass('submitting'); // إتاحة الإرسال مرة أخرى

            if (data.status === 0) {
              console.log(data.error);
              handleErrors(data.error);
              showBlockAlert('warning', 'حدث خطأ أثناء الإرسال!');
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
        },
        error: function (xhr, status, error) {
          showAlert('error', 'Something went wrong! : ' + error, 10000, true);
        }
      });
    }
  });
}

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

// دالة لإعادة تعيين محتوى CKEditor
function resetCKEditor(contentElement, contentResetElement) {
  if (contentElement && contentResetElement && CKEDITOR.instances['content']) {
    CKEDITOR.instances['content'].setData('');
  }
}

// دالة لإعادة تعيين الصورة
function resetImage(imgElement) {
  if (imgElement) {
    $(imgElement).attr('src', $(imgElement).attr('data-image'));
  }
}

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
        },
        error: function () {
          console.log('Error loading template fields.');
        }
      });
    }
  });

export function generateFields(fields, storedData = {}) {
  fields.forEach(field => {
    var inputField = '';
    const storedValue = storedData[field.name]?.value || ''; // هنا نجلب القيمة المخزنة إذا وجدت

    switch (field.type) {
      case 'string':
        inputField = `<input type="text" name="additional_fields[${field.name}]" value="${storedValue}" class="form-control" placeholder="Enter ${field.name}" ${field.required ? 'required' : ''}>`;
        break;
      case 'number':
        inputField = `<input type="number" name="additional_fields[${field.name}]" value="${storedValue}" class="form-control" placeholder="Enter ${field.name}" ${field.required ? 'required' : ''}>`;
        break;
      case 'email':
        inputField = `<input type="email" name="additional_fields[${field.name}]" value="${storedValue}" class="form-control" placeholder="Enter ${field.name}" ${field.required ? 'required' : ''}>`;
        break;
      case 'date':
        inputField = `<input type="date" name="additional_fields[${field.name}]" value="${storedValue}" class="form-control" ${field.required ? 'required' : ''}>`;
        break;
      case 'textarea':
        inputField = `<textarea name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}" ${field.required ? 'required' : ''}>${storedValue}</textarea>`;
        break;
      case 'file':
        inputField = `<input type="file" name="additional_fields[${field.name}]"  class="form-control" >`;
        break;
      case 'image':
        inputField = `<input type="file" name="additional_fields[${field.name}]"  class="form-control" >`;
        break;
      case 'select':
        inputField = `<select name="additional_fields[${field.name}]" class="form-select" ${field.required ? 'required' : ''}>
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

    $('#additional-form').append(`
      <div class="mb-3 col-md-6">
        <label class="form-label">${field.required ? '*' : ''} ${field.label}</label>
        ${inputField}
        <span class="additional_fields-${field.name}-error text-danger text-error"></span>
      </div>
    `);
  });
}
