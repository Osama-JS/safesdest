$('.form_submit').on('submit', function (e) {
  e.preventDefault();

  const $this = $(this);
  const contentElement = document.querySelector('#content');
  const contentResetElement = document.querySelector('.content_reset');
  const imgElement = document.querySelector('.reset_image');
  const noReset = document.querySelector('.no-reset');

  // إظهار رسالة "جاري المعالجة..." وبقاء الـ block حتى استجابة السيرفر
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

  // إذا كان هناك محتوى CKEditor، احصل على البيانات
  if (contentElement && CKEDITOR.instances['content']) {
    const sec = CKEDITOR.instances['content'].getData();
    $('#content').val(sec);
  }

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
          if (data.status === 0) {
            handleErrors(data.error);
            showBlockAlert('warning', 'حدث خطأ أثناء الإرسال!');
          } else if (data.status === 1) {
            const old_val = $('.no-reset').val();

            if (!noReset) {
              $this.trigger('reset');
            }
            $('.no-reset').val(old_val);
            resetCKEditor(contentElement, contentResetElement);
            resetImage(imgElement);

            document.dispatchEvent(new CustomEvent('formSubmitted', { detail: data }));

            showBlockAlert('success', data.success, 1700);
          } else if (data.status === 2) {
            // إبقاء استخدام SweetAlert2 لحالة الخطأ هنا فقط
            showAlert('error', data.error, 10000, true);
          }
        }
      });
    },
    error: function (jqXHR, textStatus, errorThrown) {
      $this.unblock({
        onUnblock: function () {
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

// دالة لإظهار التنبيه باستخدام block عند فك الحظر
function showBlockAlert(type, message, timer = 700) {
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

// دالة لمعالجة الأخطاء
function handleErrors(errors, prefix = '') {
  $.each(errors, function (key, val) {
    $('span.' + prefix + key + '-error').text(val[0]);
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

$('#select-template').on('change', function () {
  var templateId = $(this).val();

  // تنظيف الحقول الإضافية السابقة
  $('#additional-form').html('');

  if (templateId) {
    // استرجاع الحقول الخاصة بالقالب المحدد عبر AJAX
    $.ajax({
      url: baseUrl + 'admin/settings/templates/fields', // استبدل بالمسار الفعلي لاسترجاع الحقول
      type: 'GET',
      data: { id: templateId },
      success: function (response) {
        // توليد الحقول في #additional-form
        console.log(response.fields);

        generateFields(response.fields);
      },
      error: function () {
        console.log('Error loading template fields.');
      }
    });
  }
});

function generateFields(fields) {
  fields.forEach(field => {
    var inputField = '';

    switch (field.type) {
      case 'string':
        inputField = `<input type="text" name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}" required=${field.required}>`;
        break;
      case 'number':
        inputField = `<input type="number" name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}" required=${field.required}>`;
        break;
      case 'email':
        inputField = `<input type="email" name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}" required=${field.required}>`;
        break;
      case 'date':
        inputField = `<input type="date" name="additional_fields[${field.name}]" class="form-control" required=${field.required}>`;
        break;
      case 'textarea':
        inputField = `<textarea name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}" required=${field.required}></textarea>`;
        break;
      case 'select':
        inputField = `<select name="additional_fields[${field.name}]" class="form-select" required=${field.required}>
        ${(() => {
          try {
            const options = JSON.parse(field.value || '[]'); // إذا كانت فارغة، استخدم مصفوفة فارغة
            return options.map(option => `<option value="${option.value}">${option.name}</option>`).join('');
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
              <label class="form-label">${field.required ? '*' : ''} ${field.name}</label>
              ${inputField}
          </div>
      `);
  });
}
