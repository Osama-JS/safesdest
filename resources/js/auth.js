import { handleErrors, showBlockAlert, showAlert } from './ajax';

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

// generateFields(fields);

/* ================  Select Vehicles Code   =============== */
let vehicleIndex = 0;
const selectedTypes = new Set();

function createVehicleRow(index) {
  return $('#vehicle-row-template').html().replaceAll('{index}', index);
}

function updateVehicleRowEvents($row) {
  const $vehicleSelect = $row.find('.vehicle-select');
  const $typeSelect = $row.find('.vehicle-type-select');
  const $sizeSelect = $row.find('.vehicle-size-select');

  $vehicleSelect.on('change', function () {
    const vehicleId = $(this).val();
    $typeSelect.prop('disabled', true).empty().append('<option>Loading...</option>');
    $sizeSelect.prop('disabled', true).empty().append('<option>Select a vehicle size</option>');

    if (vehicleId) {
      $.get(`${baseUrl}chosen/vehicles/types/${vehicleId}`, function (types) {
        $typeSelect.empty().append('<option value="">Select a vehicle type</option>');
        types.forEach(type => {
          if (!selectedTypes.has(type.id.toString())) {
            $typeSelect.append(`<option value="${type.id}">${type.name}</option>`);
          }
        });
        $typeSelect.prop('disabled', false);
      });
    }
  });

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

const $newRow = $(createVehicleRow(vehicleIndex++));
$('#vehicle-selection-container').append($newRow);
updateVehicleRowEvents($newRow);

/* ================  Template Fields Generate Code   =============== */

if (CustomerTemplate != null) {
  generateFields(CustomerTemplate, 'additional-customer-form');
}
if (DriverTemplate != null) {
  generateFields(DriverTemplate, 'additional-driver-form');
}

export function generateFields(fields, generateSection) {
  fields.forEach(field => {
    var inputField = '';

    if (field.driver_can == 'write' || field.customer_can == 'write') {
      switch (field.type) {
        case 'string':
          inputField = `<input type="text" name="additional_fields[${field.name}]"  class="form-control" placeholder="Enter ${field.name}" ${field.required ? 'required' : ''}>`;
          break;
        case 'number':
          inputField = `<input type="number" name="additional_fields[${field.name}]"  class="form-control" placeholder="Enter ${field.name}" ${field.required ? 'required' : ''}>`;
          break;
        case 'email':
          inputField = `<input type="email" name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}" ${field.required ? 'required' : ''}>`;
          break;
        case 'date':
          inputField = `<input type="date" name="additional_fields[${field.name}]" class="form-control" ${field.required ? 'required' : ''}>`;
          break;
        case 'textarea':
          inputField = `<textarea name="additional_fields[${field.name}]" class="form-control" placeholder="Enter ${field.name}" ${field.required ? 'required' : ''}></textarea>`;
          break;
        case 'file':
          inputField = `<input type="file" name="additional_fields[${field.name}]"  class="form-control" ${field.required ? 'required' : ''}>`;
          break;
        case 'select':
          inputField = `<select name="additional_fields[${field.name}]" class="form-select" ${field.required ? 'required' : ''}>
          ${(() => {
            try {
              const options = JSON.parse(field.value || '[]');
              return options
                .map(
                  option =>
                    `<option value="${option.value}" >
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
      $(`#${generateSection}`).append(`
        <div class="mb-4  col-md-6">
          <label class="form-label">${field.required ? '*' : ''} ${field.label}</label>
          ${inputField}
          <span class="additional_fields-${field.name}-error text-danger text-error"></span>
        </div>
      `);
    }
  });
}
