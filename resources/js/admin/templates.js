/**
 * Page User List
 */

'use strict';

// Datatable (jquery)
$(function () {
  // Variable declaration for table
  var dt_user_table = $('.datatables-users'),
    offCanvasForm = $('#largeModal');

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Users datatable
  if (dt_user_table.length) {
    var dt_user = dt_user_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/settings/templates/data',
        data: function (d) {
          d.guard = $('#roleFilter').val();
        }
      },
      columns: [
        // columns according to JSON
        { data: '' },
        { data: 'id' },
        { data: 'name' },
        { data: 'description' },
        { data: 'created_at' },
        { data: 'action' }
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
          // Actions
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center gap-50">' +
              `<a href="${baseUrl + 'admin/settings/templates/edit/' + full['id']}" class="btn btn-sm btn-icon  btn-text-secondary rounded-pill waves-effect"  ><i class="ti ti-eye"></i></a>` +
              `<button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}" data-name="${full['name']}"  data-description="${full['description']}"  ><i class="ti ti-edit"></i></button>` +
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
      lengthMenu: [7, 10, 20, 50, 70, 100], //for length of menu
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search User',
        info: 'Displaying _START_ to _END_ of _TOTAL_ entries',
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
              return 'Details of ' + data['name'];
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
    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_user }));
  }

  document.addEventListener('formSubmitted', function (event) {
    dt_user.draw();
    setTimeout(() => {
      $('#submitModal').modal('hide');
    }, 2000);
  });

  $(document).on('click', '.edit-record', function () {
    var id = $(this).data('id'),
      name = $(this).data('name'),
      description = $(this).data('description');

    $('#modelTitle').html(`Edit Template: <span class="bg-info text-white px-2 rounded">${name}</span>`);
    $('#submitModal').modal('show');

    $('#pricing_id').val(id);
    $('#template-name').val(name);
    $('#template-description').val(description);
  });

  $(document).on('click', '.delete-record', function () {
    var Id = $(this).data('id');
    var name = $(this).data('name');

    Swal.fire({
      title: `Delete ${name}?`,
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
          url: `${baseUrl}admin/settings/pricing/delete/${Id}`,
          type: 'post',

          success: function (response) {
            if (response.status === 1) {
              Swal.fire({
                title: response.success,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
              });

              dt_user.draw();
            } else {
              Swal.fire('Error!', response.error, 'error');
            }
          },
          error: function () {
            Swal.fire('Error!', 'Failed to delete the method', 'error');
          }
        });
      }
    });
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    document.querySelector('.form_submit').reset();
    $('.text-error').html('');
    $('#pricing-distance').attr('checked', false);
    $('#modelTitle').html('Add New Method');
  });

  function showAlert(icon, title, timer, showConfirmButton = false) {
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
    let toastType =
      icon === 'success' ? 'success' : icon === 'error' ? 'error' : icon === 'warning' ? 'warning' : 'info';

    // عرض الإشعار
    let $toast = toastr[toastType](title);

    // إضافة تأثير tada بعد ظهور التوست
    if ($toast) {
      $toast.addClass('animate__animated animate__tada');
    }
  }

  $(document).ready(function () {
    $('#add_field').click(function () {
      $('#fields_table').append(`
            <tr class="form-field-row">
            <td class="drag-handle" style="cursor: grab;">☰</td>

                <td><input type="text" class="form-control field-name-input"></td>
                <td><input type="text" class="form-control field-label-input"></td>
                <td>
                    <select class="form-control field-manager">
                        <option value="hidden">Hidden</option>
                        <option value="read">Read Only</option>
                        <option value="write">Read & Write</option>
                    </select>
                </td>
                <td>
                    <select class="form-control field-customer-can-select">
                        <option value="hidden">Hidden</option>
                        <option value="read">Read Only</option>
                        <option value="write">Read & Write</option>
                    </select>
                </td>
                <td>
                    <select class="form-control field-type-select">
                        <option value="string">نص</option>
                        <option value="number">رقم</option>
                        <option value="email">بريد إلكتروني</option>
                        <option value="date">تاريخ</option>
                        <option value="select">اختيار</option>
                    </select>
                </td>
                <td><input type="text" class="form-control field-value-input"></td>
                <td>
                    <select class="form-control field-required-select">
                        <option value="0">اختياري</option>
                        <option value="1">إلزامي</option>
                    </select>
                </td>
                <td><button class="btn btn-sm btn-icon text-danger remove-field"><i class="ti ti-trash"></i></button></td>
            </tr>
        `);
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

      if ($(this).val() === 'select') {
        if (nextRow.length === 0) {
          row.after(`
                  <tr class="select-values-table connected-row">
                      <td colspan="8">
                          <div class="p-2 border rounded bg-light shadow-sm">
                              <h6 class="text-primary">🔗 قيم الاختيار</h6>
                              <table class="table table-bordered">
                                  <thead>
                                      <tr>
                                          <th>القيمة</th>
                                          <th>الاسم الظاهر</th>
                                          <th>إجراء</th>
                                      </tr>
                                  </thead>
                                  <tbody class="select-values-body"></tbody>
                              </table>
                              <button type="button" class="btn btn-sm btn-primary add-select-value">➕ إضافة قيمة</button>
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
              <td><button type="button" class="btn btn-sm btn-danger remove-select-value">❌</button></td>
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
          if (response.status == 2) {
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
});
