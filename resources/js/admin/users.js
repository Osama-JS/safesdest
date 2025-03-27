/**
 * Page User List
 */

'use strict';

// Datatable (jquery)
$(function () {
  // Variable declaration for table
  var dt_user_table = $('.datatables-users'),
    userView = baseUrl + 'app/user/view/account',
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
        url: baseUrl + 'admin/users/data'
      },
      columns: [
        // columns according to JSON
        { data: '' },
        { data: 'id' },
        { data: 'name' },
        { data: 'email' },
        { data: 'phone' },
        { data: 'role' },
        { data: 'status' },
        { data: 'action' }
      ],
      rowCallback: function (row, data) {
        if (data.id === 1) {
          $(row).addClass('table-light');
        }
      },
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
            var $name = full['name'];

            // For Avatar badge
            var stateNum = Math.floor(Math.random() * 6);
            var states = ['success', 'danger', 'warning', 'info', 'dark', 'primary', 'secondary'];
            var $state = states[stateNum],
              $name = full['name'],
              $initials = $name.match(/\b\w/g) || [],
              $output;
            $initials = (($initials.shift() || '') + ($initials.pop() || '')).toUpperCase();
            $output = '<span class="avatar-initial rounded-circle bg-label-' + $state + '">' + $initials + '</span>';

            // Creates full output for row
            var $row_output =
              '<div class="d-flex justify-content-start align-items-center user-name">' +
              '<div class="avatar-wrapper">' +
              '<div class="avatar avatar-sm me-4">' +
              $output +
              '</div>' +
              '</div>' +
              '<div class="d-flex flex-column">' +
              '<a href="' +
              userView +
              '" class="text-heading text-truncate"><span class="fw-medium">' +
              $name +
              '</span></a>' +
              '</div>' +
              '</div>';
            return $row_output;
          }
        },
        {
          // User email
          targets: 3,
          render: function (data, type, full, meta) {
            var $email = full['email'];

            return '<span class="user-email">' + $email + '</span>';
          }
        },
        {
          // User phone
          targets: 4,
          render: function (data, type, full, meta) {
            var $phone = full['phone'];

            return '<span class="user-phone">' + $phone + '</span>';
          }
        },
        {
          // User phone
          targets: 5,
          render: function (data, type, full, meta) {
            var $role = full['role'];

            return '<span class="user-role alert alert-info">' + $role + '</span>';
          }
        },
        {
          // status
          targets: 6,
          className: 'text-center',
          render: function (data, type, full, meta) {
            var $status = full['status'];
            var html = '<span class="user-status">' + $status + '</span>';
            switch ($status) {
              case 'active':
                html += '<i class="ti fs-4 ti-shield-check text-success"></i>';
                break;
              case 'inactive':
                html += '<i class="ti fs-4 ti-shield-x text-danger"></i>';
                break;
              case 'pending':
                html += '<i class="ti fs-4 ti-hourglass text-warning"></i>';
                break;
            }
            return html;
          }
        },
        {
          // Actions
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return full['id'] === 1
              ? ''
              : '<div class="d-flex align-items-center gap-50">' +
                  `<button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}" data-bs-toggle="modal" data-bs-target="#largeModal"><i class="ti ti-edit"></i></button>` +
                  `<button class="btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}"><i class="ti ti-trash"></i></button>` +
                  '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>' +
                  '<div class="dropdown-menu dropdown-menu-end m-0">' +
                  '<a href="' +
                  userView +
                  '" class="dropdown-item">View</a>' +
                  `<a href="javascript:;" class="dropdown-item status-record" data-id="${full['id']}" data-name="${full['name']}" data-status="${full['status']}">change status</a>` +
                  '</div>' +
                  '</div>';
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
    if (dt_user) {
      dt_user.draw();
    }
  });

  $(document).on('click', '.edit-record', function () {
    var user_id = $(this).data('id'),
      dtrModal = $('.dtr-bs-modal.show');

    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    $('#modelTitle').html('Edit User');

    // get data
    $.get(`${baseUrl}user-list\/${user_id}\/edit`, function (data) {
      $('.text-error').html('');
      $('#user_id').val(data.id);
      $('#user-fullname').val(data.name);
      $('#user-email').val(data.email);
      $('#user-phone').val(data.phone);
      $('#user-role').val(data.role_id);
    });
  });

  $(document).on('click', '.status-record', function () {
    var id = $(this).data('id');
    var name = $(this).data('name');
    var status = $(this).data('status');

    // استخدام SweetAlert لعرض النموذج
    Swal.fire({
      title: `Change User: ${name} Status`,
      icon: 'info',
      html: `
            <form class="add-new-user pt-0 form_status" method="POST" action="${baseUrl + 'admin/users/status'}">
                <input type="hidden" value="${id}" name="id">
                <select class="form-select" name="status">
                    <option value="active" ${status === 'active' ? 'selected' : ''}>Active</option>
                    <option value="inactive" ${status === 'inactive' ? 'selected' : ''}>Inactive</option>
                    <option value="pending" ${status === 'pending' ? 'selected' : ''}>Pending</option>
                </select>
            </form>
        `,
      showCloseButton: true,
      showCancelButton: true,
      focusConfirm: false,
      confirmButtonText: 'Confirm!',
      confirmButtonAriaLabel: 'Thumbs up, great!',
      cancelButtonText: 'Cancel',
      cancelButtonAriaLabel: 'Thumbs down',
      customClass: {
        confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
        cancelButton: 'btn btn-label-secondary waves-effect waves-light'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        // AJAX request for form submission
        var formData = $('.form_status').serialize(); // استخدام البيانات من النموذج في SweetAlert

        $.ajax({
          url: $('.form_status').attr('action'),
          type: 'POST',
          data: formData,
          success: function (response) {
            Swal.fire({
              icon: response.type,
              title: response.message,
              showConfirmButton: false,
              timer: 1500
            });
            if (response.status == 1) {
              // إذا كان يوجد جدول بيانات يتم تحديثه
              if (dt_user) {
                dt_user.draw();
              }
            }
          },
          error: function (xhr, status, error) {
            // معالجة الخطأ في حال وجود مشكلة في إرسال البيانات
            Swal.fire({
              icon: 'error',
              title: 'Something went wrong!',
              text: 'Please try again later.'
            });
          }
        });
      }
    });
  });

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
});
