/**
 * Page Roles
 */

'use strict';
import { showAlert } from '../ajax';
import { deleteRecord } from '../ajax';

// Datatable (jquery)
$(function () {
  // Variable declaration for table
  var dt_table = $('.datatables-data');

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Users datatable
  if (dt_table.length) {
    var dt_data = dt_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/roles/data',
        data: function (d) {
          d.type = $('#typeFilter').val();
        }
      },
      columns: [
        { data: '' },
        { data: 'id', render: (data, type, full) => `<span>${full.fake_id}</span>` },
        { data: 'name' },
        { data: 'created_at', render: data => `<span class="user-email">${data}</span>` },
        {
          data: 'action',
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: (data, type, full) =>
            full.id == 1
              ? ''
              : `<div class="d-flex align-items-center gap-50">
              <button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect"
                data-id="${full.id}" data-name="${full.name}" data-guard="${full.guard}"
                data-users=${full.users} data-bs-toggle="modal" data-bs-target="#formModal">
                <i class="ti ti-edit"></i>
              </button>
              <button class="btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect"
                data-id="${full.id}" data-name="${full.name}">
                <i class="ti ti-trash"></i>
              </button>
            </div>`
        }
      ],
      rowCallback: (row, data) => data.id === 1 && $(row).addClass('table-light'),
      columnDefs: [
        {
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: () => ''
        }
      ],
      order: [[2, 'desc']],
      dom: '<"row"<"col-md-2"l><"col-md-10 d-flex justify-content-end"fB>>t<"row"<"col-md-6"i><"col-md-6"p>>',
      lengthMenu: [7, 10, 20, 50, 70, 100],
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
      buttons: [
        ` <label class='me-2'>
          <select id='typeFilter' class='form-select d-inline-block w-auto ms-2 mt-5'>
            <option value='web'>Administrator</option>
            <option value='driver'>Driver</option>
            <option value='customer'>Customer</option>
          </select>
        </label>`
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: row => 'Details of ' + row.data().name
          }),
          type: 'column',
          renderer: (api, rowIdx, columns) => {
            let data = columns
              .filter(col => col.title)
              .map(
                col =>
                  `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                <td>${col.title}:</td>
                <td>${col.data}</td>
              </tr>`
              )
              .join('');
            return data ? $('<table class="table"/>').append('<tbody>' + data + '</tbody>') : false;
          }
        }
      }
    });

    $(document).on('change', '#typeFilter', function () {
      dt_data.draw();
    });
    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_data }));
  }

  document.addEventListener('formSubmitted', function (event) {
    if (dt_data) {
      dt_data.draw();
      setTimeout(() => {
        $('#formModal').modal('hide');
      }, 2000);
    }
  });
  document.addEventListener('deletedSuccess', function (event) {
    if (dt_data) {
      dt_data.draw();
    }
  });

  $('.dataTables_filter').hide();

  $(document).on('click', '.edit-record', function () {
    var role_id = $(this).data('id'),
      role_name = $(this).data('name'),
      role_guard = $(this).data('guard'),
      role_users = $(this).data('users'),
      dtrModal = $('.dtr-bs-modal.show');

    if (role_users > 0) {
      $('#check-guard').hide();
    }
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    $('#modelTitle').html(`Edit Role: <span class="bg-info text-white px-2 rounded">${role_name}</span>`);

    $('#role_id').val(role_id);
    $('#role-name').val(role_name);
    $('#role-guard').val(role_guard);

    getPermissions(role_guard, role_id);
  });

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/roles/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  function getPermissions(guard, role_id = null) {
    let url = `${baseUrl}admin/roles/permissions/${guard}`;
    if (role_id) {
      url += `?role_id=${role_id}`;
    }

    $.get(url, function (response) {
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

        //  تحديث أيقونات القفل عند تحميل الصفحة
        $('.perm-checkbox').each(function () {
          let icon = $(this).siblings('label').find('.lock-icon');
          if ($(this).prop('checked')) {
            icon.removeClass('text-primary ti-lock').addClass('text-success ti-lock-open'); // قفل مفتوح أخضر
          } else {
            icon.removeClass('text-success ti-lock-open').addClass('text-primary ti-lock'); // قفل مغلق أزرق
          }
        });

        //  عند النقر على أي صلاحية، غيّر شكل القفل
        $(document).on('change', '.perm-checkbox', function () {
          let icon = $(this).siblings('label').find('.lock-icon');
          if ($(this).prop('checked')) {
            icon.removeClass('text-primary ti-lock').addClass('text-success ti-lock-open'); // قفل مفتوح أخضر
          } else {
            icon.removeClass('text-success ti-lock-open').addClass('text-primary ti-lock'); // قفل مغلق أزرق
          }
        });

        //  إضافة حدث "تحديد الكل"
        $(document).on('change', '.select-all', function () {
          let groupClass = $(this).attr('id').replace('select-all-', '');
          let isChecked = $(this).prop('checked');

          $(`.${groupClass}`).prop('checked', isChecked).trigger('change'); // تحديث حالة الصلاحيات
        });
      } else {
        $('#permissions_types').html('<p class="text-muted">There is no Permissions found!</p>');
        $('#permissions_container').html('');
      }
    }).fail(function () {
      showAlert('error', 'Error!! can not fiche any Permission', 10000, true);
      $('#permissions_types').html('<p class="text-danger">Error!! can not fiche any Permission</p>');
    });
  }

  getPermissions('web');

  $(document).on('change', '#role-guard', function (e) {
    getPermissions($(this).val());
  });

  $('#formModal').on('hidden.bs.modal', function () {
    $(this).find('form')[0].reset();
    $('.text-error').html('');
    $('#role_id').val('');
    $('#modelTitle').html('Add New Role');
    $('#check-guard').show();
    getPermissions('web');
    $('.form_submit').attr('action', `${baseUrl}admin/roles`);
  });
});
