/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, handleErrors } from '../ajax';

// Datatable (jquery)
$(function () {
  // Variable declaration for table
  var dt_data_table = $('.datatables-users');

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Users datatable
  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/settings/templates/data'
      },
      columns: [
        { data: '' },
        { data: 'id' },
        { data: 'name' },
        { data: 'description' },
        { data: 'usage_summary' },
        { data: 'created_at' },
        { data: null }
      ],
      columnDefs: [
        {
          // For Responsive
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          searchable: false,
          orderable: false,
          targets: 1,
          render: function (data, type, full, meta) {
            return `<span>${full.fake_id}</span>`;
          }
        },
        {
          // User full name
          targets: 2,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            return full['name'];
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return full['description'];
          }
        },
        {
          // Usage column
          targets: 4,
          title: __('Usage'),
          searchable: false,
          orderable: true,
          render: function (data, type, full, meta) {
            if (type === 'display') {
              let badgeClass = 'badge-light-secondary';
              if (full['total_usage'] > 0) {
                badgeClass = 'badge-light-success';
              }

              return `
                <div class="d-flex flex-column">
                  <span class="badge ${badgeClass} mb-1">
                    <i class="ti ti-chart-bar me-1"></i>
                    ${__('Total')}: ${full['total_usage']}
                  </span>
                  <small class="text-muted">${full['usage_summary']}</small>
                </div>
              `;
            }
            return full['total_usage'];
          }
        },

        {
          // Actions
          targets: -1,
          title: __('Actions'),
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center gap-50">' +
              `<a href="${baseUrl + 'admin/settings/templates/edit/' + full['id']}" class="btn btn-sm btn-icon  btn-text-secondary rounded-pill waves-effect"  ><i class="ti ti-eye"></i></a>` +
              `<button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}" data-name="${full['name']}"  data-description="${full['description']}"  ><i class="ti ti-edit"></i></button>` +
              `<button class="btn btn-sm btn-icon duplicate-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}" data-name="${full['name']}" title="Duplicate Template"><i class="ti ti-copy"></i></button>` +
              `<button class="btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}" data-name="${full['name']}"><i class="ti ti-trash"></i></button> </div>`
            );
          }
        }
      ],
      order: [[2, 'desc']],
      dom:
        '<"row"' +
        '<"col-md-2"<"ms-n2"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0"fB>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      lengthMenu: [10, 20, 50, 100], //for length of menu
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: __('Search User'),
        info: __('Displaying _START_ to _END_ of _TOTAL_ entries'),
        paginate: {
          next: '<i class="ti ti-chevron-right ti-sm"></i>',
          previous: '<i class="ti ti-chevron-left ti-sm"></i>'
        }
      },
      // Buttons
      buttons: [],
      // For responsive popup
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return __('Details of') + ' ' + data['name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });
    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_data }));
  }

  document.addEventListener('formSubmitted', function (event) {
    dt_data.draw();
    setTimeout(() => {
      $('#submitModal').modal('hide');
    }, 2000);
  });

  document.addEventListener('deletedSuccess', function (event) {
    dt_data.draw();
  });

  $(document).on('click', '.edit-record', function () {
    var id = $(this).data('id'),
      name = $(this).data('name'),
      description = $(this).data('description');

    $('#modelTitle').html(`Edit Template: <span class="bg-info text-white px-2 rounded">${name}</span>`);
    $('#submitModal').modal('show');

    $('#template_id').val(id);
    $('#template-name').val(name);
    $('#template-description').val(description);
  });

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/settings/templates/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  $(document).on('click', '.duplicate-record', function () {
    let templateId = $(this).data('id');
    let templateName = $(this).data('name');

    // Show confirmation dialog
    Swal.fire({
      title: __('Duplicate Template'),
      text: `Are you sure you want to duplicate "${templateName}"?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: __('Yes, duplicate it!'),
      customClass: {
        confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
        cancelButton: 'btn btn-label-secondary waves-effect waves-light'
      }
    }).then(result => {
      if (result.isConfirmed) {
        // Show loading
        showAlert('info', 'Duplicating Template...');
        // Make AJAX request to duplicate
        $.ajax({
          url: baseUrl + 'admin/settings/templates/duplicate/' + templateId,
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.status === 1) {
              showAlert('success', response.success, 10000, true);
              dt_data.draw();
            } else {
              showAlert('error', response.error, 10000, true);
            }
          },
          error: function (xhr) {
            Swal.fire({
              title: __('Error!'),
              text: __('An error occurred while duplicating the template'),
              icon: 'error',
              confirmButtonText: __('OK')
            });
          }
        });
      }
    });
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    document.querySelector('.form_submit').reset();
    $('.text-error').html('');
    $('#pricing-distance').attr('checked', false);
    $('#modelTitle').html(__('Add New Method'));
  });

  $(document).ready(function () {
    $('#add_field').click(function () {
      $('#fields_table').append(`
            <tr class="form-field-row">
            <td class="drag-handle" style="cursor: grab;">☰</td>
                <td>
                  <input type="text" class="form-control field-name-input">
                  <span class="field-${fieldIndex}-name-error text-danger text-error"></span>
                </td>
                <td>
                  <input type="text" class="form-control field-label-input">
                  <span class="field-${fieldIndex}-label-error text-danger text-error"></span>
                </td>
                <td>
                    <select class="form-control field-manager">
                        <option value="hidden">Hidden</option>
                        <option value="read">Read Only</option>
                        <option value="write">Read & Write</option>
                    </select>
                  <span class="field-${fieldIndex}-driver_can-error text-danger text-error"></span>

                </td>
                <td>
                    <select class="form-control field-customer-can-select">
                        <option value="hidden">Hidden</option>
                        <option value="read">Read Only</option>
                        <option value="write">Read & Write</option>
                    </select>
                  <span class="field-${fieldIndex}-customer_can-error text-danger text-error"></span>

                </td>
                <td>
                    <select class="form-control field-type-select">
                        <option value="string">text</option>
                        <option value="number">number</option>
                        <option value="email">email</option>
                        <option value="date">date</option>
                        <option value="file">file</option>
                        <option value="file_expiration_date">file with expiration date</option>
                        <option value="file_with_text">file with text/number</option>
                        <option value="image">image</option>
                        <option value="url">url</option>
                        <option value="select">select</option>
                    </select>
                  <span class="field-${fieldIndex}-type-error text-danger text-error"></span>

                </td>
                <td>
                  <input type="text" class="form-control field-value-input" placeholder="For composite fields: label for companion field">
                  <span class="field-${fieldIndex}-value-error text-danger text-error"></span>
                </td>
                <td>
                    <select class="form-control field-required-select">
                        <option value="0">NO</option>
                        <option value="1">YES</option>
                    </select>
                  <span class="field-${fieldIndex}-required-error text-danger text-error"></span>

                </td>
                <td><button class="btn btn-sm btn-icon text-danger remove-field"><i class="ti ti-trash"></i></button></td>
            </tr>
        `);
      fieldIndex++;
    });

    let sortable = new Sortable(document.getElementById('fields_table'), {
      handle: '.drag-handle',
      animation: 150, // تأثير التحريك
      ghostClass: 'sortable-ghost',
      group: 'fields', // كلاس للصف أثناء السحب
      onStart: function (evt) {
        let item = $(evt.item);
        let nextRow = item.next('.select-values-table');

        if (nextRow.length > 0) {
          nextRow.addClass('dragging');
          item.addClass('dragging');
        }
      },

      onEnd: function (evt) {
        console.log('Item moved:', evt.item);
        console.log('New index:', evt.newIndex);
        console.log('Old index:', evt.oldIndex);
        let item = $(evt.item);
        let itemId = item.attr('data-id');
        let nextRow = $('.select-values-table[data-id="' + itemId + '"]');

        if (nextRow.length > 0) {
          nextRow.insertAfter(item);
        }

        $('.dragging').removeClass('dragging');
        updateFieldOrder();
      }
    });

    function updateFieldOrder() {
      $('.form-field-row').each(function (index) {
        $(this).attr('data-order', index + 1);
      });
    }

    // عند تغيير نوع الحقل
    $(document).on('change', '.field-type-select', function () {
      let row = $(this).closest('tr');
      let nextRow = row.next('.select-values-table');
      let valueInput = row.find('.field-value-input');
      let fieldType = $(this).val();

      // تحديث placeholder حسب نوع الحقل
      if (fieldType === 'file_expiration_date') {
        valueInput.attr('placeholder', 'Label for expiration date (e.g., "Expiry Date", "Valid Until")');
      } else if (fieldType === 'file_with_text') {
        valueInput.attr('placeholder', 'Label for text field (e.g., "License Number", "Document ID")');
      } else if (fieldType === 'select') {
        valueInput.attr('placeholder', 'Select options will be managed below');
      } else {
        valueInput.attr('placeholder', 'Default value or placeholder text');
      }

      if (fieldType === 'select') {
        if (nextRow.length === 0) {
          row.after(`
                  <tr class="select-values-table connected-row">
                      <td colspan="4">
                          <div class="p-2 border rounded shadow-sm">
                              <h6 class="text-primary">🔗 قيم الاختيار</h6>
                              <table class="table ">
                                  <thead>
                                      <tr>
                                          <th>القيمة</th>
                                          <th>الاسم الظاهر</th>
                                          <th>إجراء</th>
                                      </tr>
                                  </thead>
                                  <tbody class="select-values-body"></tbody>
                              </table>
                              <button type="button" class="btn btn-sm btn-icon text-primary add-select-value"> <i
                                                    class="ti ti-plus me-0 me-sm-1 ti-xs"></i></button>
                          </div>
                      </td>
                  </tr>
              `);
        }
      } else {
        nextRow.remove();
      }
    });

    // عند إضافة قيمة جديدة لقائمة الاختيار
    $(document).on('click', '.add-select-value', function () {
      let tableBody = $(this).siblings('table').find('.select-values-body');
      let newRow = `
          <tr>
              <td><input type="text" class="form-control select-value-input" placeholder="أدخل القيمة"></td>
              <td><input type="text" class="form-control select-name-input" placeholder="أدخل الاسم الظاهر"></td>
              <td><button type="button" class="btn btn-sm btn-icon text-danger remove-select-value"><i
                                                                        class="ti ti-trash"></i></button></td>
          </tr>`;
      tableBody.append(newRow);
    });

    // حذف قيمة من قائمة الاختيار
    $(document).on('click', '.remove-select-value', function () {
      $(this)
        .closest('tr')
        .fadeOut(500, function () {
          $(this).remove(); // بعد انتهاء التأثير، يتم حذف السطر
        });
    });

    $(document).on('click', '.remove-field', function () {
      $(this)
        .closest('tr')
        .fadeOut(500, function () {
          $(this).remove(); // بعد انتهاء التأثير، يتم حذف السطر
        });
    });

    // عند حفظ النموذج
    $('#save_template').click(function () {
      let templateData = {
        id: $('#template_id').val(),
        fields: []
      };

      $('.form-field-row').each(function () {
        let type = $(this).find('.field-type-select').val();
        let selectValues = [];

        if (type === 'select') {
          $(this)
            .next('.select-values-table')
            .find('.select-values-body tr')
            .each(function () {
              let value = $(this).find('.select-value-input').val().trim();
              let name = $(this).find('.select-name-input').val().trim();

              if (value !== '' && name !== '') {
                selectValues.push({ value: value, name: name });
              }
            });
        }

        templateData.fields.push({
          id: $(this).data('id') || null,
          name: $(this).find('.field-name-input').val(),
          label: $(this).find('.field-label-input').val(),
          type: type,
          required: $(this).find('.field-required-select').val(),
          value: type === 'select' ? JSON.stringify(selectValues) : $(this).find('.field-value-input').val(),
          driver_can: $(this).find('.field-manager').val(),
          customer_can: $(this).find('.field-customer-can-select').val()
        });
      });

      console.log('Template Data:', templateData);

      $.ajax({
        url: baseUrl + `admin/settings/templates/update`,
        method: 'POST',
        data: JSON.stringify(templateData),
        contentType: 'application/json',
        success: function (response) {
          $('span.text-error').text(''); // إعادة تعيين الأخطاء

          if (response.status === 0) {
            console.log(response.error);
            handleErrors(response.error);
            showAlert('error', response.message);
          } else if (response.status == 2) {
            showAlert('error', response.error);
          } else {
            showAlert('success', response.success);
            console.log(response.data);
          }
        },
        error: function (xhr) {
          alert('❌ حدث خطأ أثناء الحفظ: ' + xhr.responseText);
        }
      });
    });
  });

  document.getElementById('pricing_method_select').addEventListener('change', function () {
    let methodId = this.value;
    let customizePricingDiv = document.getElementById('customize_pricing');
    customizePricingDiv.innerHTML = ''; // Clear existing content

    if (methodId) {
      let html = '';
      if (methodId == 1) {
        // Example: Distance-based pricing
        html = `
                <label class="form-label">Define Distance-Based Pricing</label>
                <div class="row mb-3" id="distance_pricing">
                    <div class="col-md-4">
                        <input type="number" class="form-control" name="from_val[]" placeholder="From (km)">
                    </div>
                    <div class="col-md-4">
                        <input type="number" class="form-control" name="to_val[]" placeholder="To (km)">
                    </div>
                    <div class="col-md-4">
                        <input type="number" class="form-control" name="price[]" placeholder="Price per km">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-success" onclick="addDistancePricing()">Add More</button>
            `;
      } else if (methodId == 2) {
        // Example: Point-to-Point pricing
        html = `
                <label class="form-label">Define Point-to-Point Pricing</label>
                <div class="row mb-3" id="point_pricing">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="from_point[]" placeholder="From Location">
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="to_point[]" placeholder="To Location">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" name="price[]" placeholder="Price">
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-success" onclick="addPointPricing()">Add More</button>
            `;
      }
      customizePricingDiv.innerHTML = html;
    }
  });

  function addDistancePricing() {
    let div = document.createElement('div');
    div.classList.add('row', 'mb-3');
    div.innerHTML = `
        <div class="col-md-4">
            <input type="number" class="form-control" name="from_val[]" placeholder="From (km)">
        </div>
        <div class="col-md-4">
            <input type="number" class="form-control" name="to_val[]" placeholder="To (km)">
        </div>
        <div class="col-md-4">
            <input type="number" class="form-control" name="price[]" placeholder="Price per km">
        </div>
    `;
    document.getElementById('distance_pricing').appendChild(div);
  }

  function addPointPricing() {
    let div = document.createElement('div');
    div.classList.add('row', 'mb-3');
    div.innerHTML = `
        <div class="col-md-5">
            <input type="text" class="form-control" name="from_point[]" placeholder="From Location">
        </div>
        <div class="col-md-5">
            <input type="text" class="form-control" name="to_point[]" placeholder="To Location">
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control" name="price[]" placeholder="Price">
        </div>
    `;
    document.getElementById('point_pricing').appendChild(div);
  }
});
