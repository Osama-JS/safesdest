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
        url: baseUrl + 'admin/roles/data',
        data: function (d) {
          d.guard = $('#roleFilter').val();
        }
      },
      columns: [
        // columns according to JSON
        { data: '' },
        { data: 'id' },
        { data: 'name' },
        { data: 'created_at' },
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
              $name = full['name'];

            return $name;
          }
        },
        {
          // User email
          targets: 3,
          render: function (data, type, full, meta) {
            var $date = full['created_at'];

            return '<span class="user-email">' + $date + '</span>';
          }
        },

        {
          // Actions
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return full['id'] == 1
              ? ''
              : '<div class="d-flex align-items-center gap-50">' +
                  `<button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}" data-name="${full['name']}"  data-guard="${full['guard']}" data-bs-toggle="modal" data-bs-target="#largeModal"><i class="ti ti-edit"></i></button>` +
                  `<button class="btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}" data-name="${full['name']}"><i class="ti ti-trash"></i></button> </div>`;
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
      setTimeout(() => {
        $('#largeModal').modal('hide');
      }, 2000);
    }
  });

  $('.dataTables_filter').hide();

  $('.dataTables_filter').parent().append(`
    <label class="me-2">
      <select id="roleFilter" class="form-select d-inline-block w-auto ms-2 mt-5">
        <option value="web">Administrator</option>
        <option value="driver">Driver</option>
        <option value="customer">Customer</option>
      </select>
    </label>
  `);

  $(document).on('change', '#roleFilter', function () {
    dt_user.ajax.reload();
  });

  $(document).on('click', '.edit-record', function () {
    var role_id = $(this).data('id'),
      role_name = $(this).data('name'),
      role_guard = $(this).data('guard'),
      dtrModal = $('.dtr-bs-modal.show');

    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    $('#modelTitle').html(`Edit Role: <span class="bg-info text-white px-2 rounded">${role_name}</span>`);

    $('.form_submit').attr('action', `${baseUrl}admin/roles/edit`);
    $('#role_id').val(role_id);
    $('#role-name').val(role_name);
    $('#role-guard').val(role_guard);

    getPermissions(role_guard, role_id);
  });

  $(document).on('click', '.delete-record', function () {
    var roleId = $(this).data('id');
    var roleName = $(this).data('name');

    Swal.fire({
      title: `Delete ${roleName} ?`,
      text: 'You will not be able to undo this action!',
      icon: 'warning',
      showCancelButton: false,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, Delete!'
    }).then(result => {
      if (result.isConfirmed) {
        $.ajax({
          url: `${baseUrl}admin/roles/delete/${roleId}`,
          type: 'DELETE',

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
            Swal.fire('Error!', 'Field to delete the role', 'error');
          }
        });
      }
    });
  });

  $('#largeModal').on('hidden.bs.modal', function () {
    $(this).find('form')[0].reset();
    $('.text-error').html('');
    $('#role_id').val('');
    $('#modelTitle').html('Add New Role');
    $('.form_submit').attr('action', `${baseUrl}admin/roles`);
  });

  function getPermissions(guard, role_id = null) {
    let url = `${baseUrl}admin/roles/permissions/${guard}`;
    if (role_id) {
      url += `?role_id=${role_id}`;
    }

    $.get(url, function (response) {
      console.log(response);

      if (response.permissions.length > 0) {
        var permissionsHtml = '';
        var tabContentHtml = '';
        let rolePermissions = response.rolePermissions || [];

        response.permissions.forEach(function (permission, index) {
          let groupId = `group-${permission.name.replace(/\s+/g, '-')}`;

          // زر التبويب لكل مجموعة أذونات
          permissionsHtml += `
                    <li class="nav-item">
                        <button type="button" class="nav-link ${index === 0 ? 'active' : ''}" role="tab"
                            data-bs-toggle="tab" data-bs-target="#${groupId}"
                            aria-controls="${groupId}" aria-selected="${index === 0 ? 'true' : 'false'}">
                            ${permission.name}
                        </button>
                    </li>
                `;

          // قسم الأذونات مع خيار تحديد الكل
          tabContentHtml += `
                    <div class="tab-pane fade ${index === 0 ? 'show active' : ''}" id="${groupId}" role="tabpanel">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input select-all" id="select-all-${groupId}">
                            <label class="form-check-label fw-bold" for="select-all-${groupId}">
                                تحديد الكل
                            </label>
                        </div>
                        <div class="permissions-list">
                `;

          // إنشاء قائمة الصلاحيات
          let permissions = permission.permissions
            .map(function (perm) {
              let isChecked = rolePermissions.includes(perm.id) ? 'checked' : ''; // الصلاحيات المحفوظة مسبقًا
              let lockClass = isChecked ? 'text-success ti-lock-open' : 'text-primary ti-lock'; // تحديث الأيقونة

              return `
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input perm-checkbox ${groupId}"
                                id="perm_${perm.id}" name="permissions[]" value="${perm.id}" ${isChecked}>
                            <label class="form-check-label fw-bold" for="perm_${perm.id}">
                                <i class="ti ${lockClass} lock-icon"></i> ${perm.d_name}
                            </label>
                        </div>
                    `;
            })
            .join('');

          tabContentHtml += permissions + `</div></div>`;
        });

        // تعيين التبويبات والمحتوى
        $('#permissions_types').html(permissionsHtml);
        $('#permissions_container').html(tabContentHtml);

        // ✅ تحديث أيقونات القفل عند تحميل الصفحة
        $('.perm-checkbox').each(function () {
          let icon = $(this).siblings('label').find('.lock-icon');
          if ($(this).prop('checked')) {
            icon.removeClass('text-primary ti-lock').addClass('text-success ti-lock-open'); // قفل مفتوح أخضر
          } else {
            icon.removeClass('text-success ti-lock-open').addClass('text-primary ti-lock'); // قفل مغلق أزرق
          }
        });

        // ✅ عند النقر على أي صلاحية، غيّر شكل القفل
        $(document).on('change', '.perm-checkbox', function () {
          let icon = $(this).siblings('label').find('.lock-icon');
          if ($(this).prop('checked')) {
            icon.removeClass('text-primary ti-lock').addClass('text-success ti-lock-open'); // قفل مفتوح أخضر
          } else {
            icon.removeClass('text-success ti-lock-open').addClass('text-primary ti-lock'); // قفل مغلق أزرق
          }
        });

        // ✅ إضافة حدث "تحديد الكل"
        $(document).on('change', '.select-all', function () {
          let groupClass = $(this).attr('id').replace('select-all-', '');
          let isChecked = $(this).prop('checked');

          $(`.${groupClass}`).prop('checked', isChecked).trigger('change'); // تحديث حالة الصلاحيات
        });
      } else {
        $('#permissions_types').html('<p class="text-muted">لا توجد أذونات متاحة لهذا الدور.</p>');
        $('#permissions_container').html('');
      }
    }).fail(function () {
      $('#permissions_types').html('<p class="text-danger">حدث خطأ أثناء جلب الأذونات.</p>');
    });
  }

  getPermissions('web');

  $(document).on('change', '#add-role-guard', function (e) {
    getPermissions($(this).val());
  });
});
