/**
 * ===================================================================
 * AUTHENTICATION AND REGISTRATION UTILITIES
 * ===================================================================
 * This file handles authentication forms, registration processes,
 * and user account creation functionality.
 */

// استيراد الدوال المساعدة من ملف ajax
import { handleErrors, showBlockAlert, showAlert } from './ajax';

// ===================================================================
// AUTHENTICATION FORM HANDLER
// ===================================================================

/**
 * معالج نماذج المصادقة (تسجيل الدخول والتسجيل)
 * يتعامل مع جميع النماذج التي تحمل class "form_auth"
 * يدعم إعادة التوجيه التلقائي بعد النجاح
 */
$(document)
  .off('submit', '.form_auth')
  .on('submit', '.form_auth', function (e) {
    e.preventDefault();
    const $this = $(this);

    // منع التكرار إذا تم الضغط أكثر من مرة
    if ($this.hasClass('submitting')) return;
    $this.addClass('submitting');

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
        $('span.text-error').text('');
        $this.unblock({
          onUnblock: function () {
            $this.removeClass('submitting'); // إتاحة الإرسال مرة أخرى
            if (data.status === 0) {
              handleErrors(data.error);
              console.log(data.error);
              showAlert('warning', 'يجب عليك التأكد من جميع البيانات المدخلة', 5000, true);
              showBlockAlert('warning', 'حدث خطأ أثناء الإرسال!');
            } else if (data.status === 1) {
              showBlockAlert('success', data.success, 1700);
              showAlert('success', data.success, 5000, true);
              setTimeout(() => {
                window.location.href = data.url;
              }, 1000);
              console.log(data.url);
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
// COMMENTED CODE - DRIVER FIELDS TOGGLE (FOR FUTURE USE)
// ===================================================================

/**
 * الكود التالي معطل حالياً - يمكن استخدامه لإظهار/إخفاء حقول السائق
 * في نموذج التسجيل حسب نوع الحساب المختار
 */

// import { generateFields } from './ajax';
// $(document).ready(function () {
//   function toggleDriverFields() {
//     if ($('#driver').is(':checked')) {
//       $('#driver-fields').slideDown(); // أو .show() لو تفضلها
//     } else {
//       $('#driver-fields').slideUp(); // أو .hide()
//     }
//   }

//   // نفذها عند تحميل الصفحة
//   toggleDriverFields();

//   // وعند تغيير أي اختيار
//   $('input[name="account_type"]').on('change', function () {
//     toggleDriverFields();
//   });
// });

/**
 * الكود التالي معطل حالياً - يمكن استخدامه لجلب حقول القالب
 * وإنشاؤها ديناميكياً في النموذج
 */

// $.ajax({
//   url: baseUrl + 'admin/settings/templates/fields', // استبدل بالمسار الفعلي لاسترجاع الحقول
//   type: 'GET',
//   data: { id: 1 },
//   success: function (response) {
//     // توليد الحقول في #additional-form
//     console.log(response.fields);
//     generateFields(response.fields);
//   },
//   error: function () {
//     console.log('Error loading template fields.');
//   }
// });

// ===================================================================
// VEHICLE SELECTION FUNCTIONALITY
// ===================================================================

/**
 * متغيرات عامة لإدارة اختيار المركبات
 */
let vehicleIndex = 0; // فهرس المركبة الحالي
const selectedTypes = new Set(); // مجموعة الأنواع المختارة

/**
 * دالة إنشاء صف جديد لاختيار المركبة
 * تستبدل المتغيرات في القالب بالفهرس المحدد
 * @param {number} index - فهرس المركبة
 * @returns {string} HTML للصف الجديد
 */
function createVehicleRow(index) {
  return $('#vehicle-row-template').html().replaceAll('{index}', index);
}

/**
 * دالة ربط الأحداث بصف المركبة
 * تتعامل مع تغيير المركبة والنوع والحجم
 * @param {jQuery} $row - عنصر الصف
 */
function updateVehicleRowEvents($row) {
  const $vehicleSelect = $row.find('.vehicle-select');
  const $typeSelect = $row.find('.vehicle-type-select');
  const $sizeSelect = $row.find('.vehicle-size-select');

  // معالج تغيير المركبة - يجلب الأنواع المتاحة
  $vehicleSelect.on('change', function () {
    const vehicleId = $(this).val();
    $typeSelect.prop('disabled', true).empty().append('<option>Loading...</option>');
    $sizeSelect.prop('disabled', true).empty().append('<option>Select a vehicle size</option>');

    if (vehicleId) {
      $.get(`${baseUrl}chosen/vehicles/types/${vehicleId}`, function (types) {
        $typeSelect.empty().append('<option value="">Select a vehicle type</option>');
        types.forEach(type => {
          // if (!selectedTypes.has(type.id.toString())) {
          $typeSelect.append(`<option value="${type.id}">${type.name}</option>`);
          // }
        });
        $typeSelect.prop('disabled', false);
      });
    }
  });

  // معالج تغيير النوع - يجلب الأحجام المتاحة
  $typeSelect.on('change', function () {
    const typeId = $(this).val();
    $sizeSelect.prop('disabled', true).empty().append('<option>Loading...</option>');

    if (typeId) {
      selectedTypes.add(typeId);
      $.get(`${baseUrl}chosen/vehicles/sizes/${typeId}`, function (sizes) {
        $sizeSelect.empty().append('<option value="">Select a vehicle size</option>');
        sizes.forEach(size => {
          $sizeSelect.append(`<option value="${size.id}">${size.name}</option>`);
        });
        $sizeSelect.prop('disabled', false);
      });
    }
  });
}

/**
 * إنشاء الصف الأول لاختيار المركبة وربط الأحداث
 */
const $newRow = $(createVehicleRow(vehicleIndex++));
$('#vehicle-selection-container').append($newRow);
updateVehicleRowEvents($newRow);

// ===================================================================
// TEMPLATE FIELDS GENERATION
// ===================================================================

/**
 * إنشاء حقول القوالب المختلفة حسب نوع المستخدم
 * يتم استدعاء هذه الدوال عند تحميل الصفحة إذا كانت القوالب متوفرة
 */

// إنشاء حقول قالب العميل
if (CustomerTemplate != null) {
  generateFields(CustomerTemplate, 'additional-customer-form');
}

// إنشاء حقول قالب السائق
if (DriverTemplate != null) {
  generateFields(DriverTemplate, 'additional-driver-form');
}

// إنشاء حقول قالب الوسيط
if (BrokerTemplate != null) {
  generateFields(BrokerTemplate, 'additional-broker-form');
}

/**
 * دالة إنشاء الحقول الديناميكية للتسجيل
 * تختلف عن دالة ajax.js في أنها تتحقق من صلاحيات الكتابة
 * @param {Array} fields - مصفوفة حقول القالب
 * @param {string} generateSection - معرف القسم المراد إضافة الحقول إليه
 */
export function generateFields(fields, generateSection) {
  fields.forEach(field => {
    var inputField = '',
      inputSpan = '';

    // التحقق من صلاحية الكتابة للحقل
    if (field.driver_can == 'write' || field.customer_can == 'write') {
      // إنشاء الحقل حسب النوع
      switch (field.type) {
        case 'string':
          inputField = `<input type="text" name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}">`;
          break;
        case 'number':
          inputField = `<input type="number" name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}">`;
          break;
        case 'email':
          inputField = `<input type="email" name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}">`;
          break;
        case 'date':
          inputField = `<input type="date" name="additional_fields[${field.name}]" class="form-control">`;
          break;
        case 'textarea':
          inputField = `<textarea name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}"></textarea>`;
          break;
        case 'file':
          inputField = `<input type="file" name="additional_fields[${field.name}]" class="form-control">`;
          break;
        // حقل ملف مع تاريخ انتهاء صلاحية
        case 'file_expiration_date':
          inputField = `
            <input type="file" name="additional_fields[${field.name}_file]" class="form-control">
            <label class="p-0">expiration date</label>
            <input type="date" name="additional_fields[${field.name}_expiration]" class="form-control">
          `;
          break;

        // حقل رابط URL
        case 'url':
          inputField = `<input type="url" name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}" ${field.required ? 'required' : ''}>`;
          break;

        // حقل صورة
        case 'image':
          inputField = `<input type="file" name="additional_fields[${field.name}]" class="form-control">`;
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
                    `<option value="${option.value}">
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

      // إنشاء عناصر عرض الأخطاء
      if (field.type === 'file_expiration_date') {
        inputSpan = `
          <span class="additional_fields-${field.name}_file-error text-danger text-error"></span>
          <span class="additional_fields-${field.name}_expiration-error text-danger text-error"></span>
        `;
      } else {
        inputSpan = `<span class="additional_fields-${field.name}-error text-danger text-error"></span>`;
      }

      // إضافة الحقل إلى القسم المحدد
      $(`#${generateSection}`).append(`
        <div class="mb-4 col-md-6">
          <label class="form-label">${field.required ? '*' : ''} ${field.label}</label>
          ${inputField}
          ${inputSpan}
        </div>
      `);
    }
  });
}

// ===================================================================
// WHATSAPP FUNCTIONALITY FOR REGISTRATION
// ===================================================================

/**
 * وظائف إدارة حقول WhatsApp في نموذج التسجيل
 * تتيح للمستخدم استخدام نفس رقم الهاتف لـ WhatsApp أو إدخال رقم منفصل
 */
$(document).ready(function () {
  /**
   * دالة إظهار/إخفاء حقول WhatsApp المنفصلة
   * تخفي الحقول إذا كان رقم الهاتف هو نفسه WhatsApp
   */
  function toggleWhatsAppFieldsReg() {
    const isPhoneWhatsApp = $('#phone-is-whatsapp-reg').is(':checked');
    const whatsappFields = $('#whatsapp-fields-reg');

    if (isPhoneWhatsApp) {
      whatsappFields.hide();
      // مسح حقول WhatsApp عند استخدام رقم الهاتف
      $('#whatsapp-country-code-reg').val('');
      $('#whatsapp-number-reg').val('');
    } else {
      whatsappFields.show();
    }
  }

  // تهيئة حالة حقول WhatsApp عند تحميل الصفحة
  toggleWhatsAppFieldsReg();

  // معالج تغيير حالة مربع الاختيار
  $('#phone-is-whatsapp-reg').on('change', function () {
    toggleWhatsAppFieldsReg();
  });
});
