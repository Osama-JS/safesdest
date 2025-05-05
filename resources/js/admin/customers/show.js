/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, generateFields, showFormModal } from '../../ajax';

// Datatable (jquery)
$(function () {
  // Variable declaration for table
  var dt_data_table = $('.datatables-users-tasks');

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
        url: baseUrl + 'admin/customers/tasks',
        data: function (d) {
          d.status = $('#statusFilter').val();
          d.search = $('#searchFilter').val();
          d.customer = customerID;
        }
      },
      columns: [
        { data: '' },
        { data: 'task_id' },
        { data: 'status' },
        { data: 'price' },
        { data: 'created_at' },
        { data: null }
      ],
      columnDefs: [
        {
          targets: 0,
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 1,
          render: function () {
            return '';
          }
        },
        {
          targets: 1,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `<span>${full.task_id}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return `<span>${full.status}</span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span>${full.price}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span>${full.created_at}</span>`;
          }
        },
        {
          targets: 5,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `
              <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-icon edit-record " data-id="${full.id}" data-bs-toggle="modal" data-bs-target="#submitModal">
                  <i class="ti ti-edit"></i>
                </button>
                <button class="btn btn-sm btn-icon delete-record " data-id="${full.id}"  data-name="${full.task_id}">
                  <i class="ti ti-trash"></i>
                </button>
              </div>`;
          }
        }
      ],
      order: [[1, 'desc']],
      dom:
        '<"row"' +
        '<"col-md-2"l>' +
        '<"col-md-10 d-flex justify-content-end"fB>' +
        '>t' +
        '<"row mt-3"' +
        '<"col-md-6"i>' +
        '<"col-md-6"p>' +
        '>',
      lengthMenu: [10, 25, 50, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search...',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="ti ti-chevron-right"></i>',
          previous: '<i class="ti ti-chevron-left"></i>'
        }
      },
      buttons: [
        `<label class='me-2'>
          <select id='statusFilter' class='form-select d-inline-block w-auto ms-2 mt-5'>
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="verified">Unverified</option>
            <option value="blocked">Blocked</option>
          </select>
        </label>`,
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="Search driver" />
          </label>`
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data.name;
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col) {
              return col.title
                ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                      <td>${col.title}:</td>
                      <td>${col.data}</td>
                   </tr>`
                : '';
            }).join('');
            return $('<table class="table"/><tbody />').append(data);
          }
        }
      }
    });

    $('#statusFilter').on('change', function () {
      dt_data.draw();
    });

    $('#searchFilter').on('input', function () {
      dt_data.draw();
    });

    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_data }));
  }

  $('.dataTables_filter').hide();

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');
    $('.preview-image').attr('src', baseUrl + 'assets/img/person.png');
    $('#additional-form').html('');
    $('#select-template').val('');
    $('#customer-tags').val([]).trigger('change');

    setTimeout(() => {
      $('#submitModal').modal('hide');
    }, 2000);

    if (dt_data) {
      dt_data.draw();
    }
  });

  document.addEventListener('deletedSuccess', function (event) {
    if (dt_data) {
      dt_data.draw();
    }
  });

  $(document).on('click', '.edit-record', function () {
    var data_id = $(this).data('id'),
      dtrModal = $('.dtr-bs-modal.show');
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }
    $.get(`${baseUrl}admin/customers/edit/${data_id}`, function (data) {
      $('.form_submit').trigger('reset');

      $('.text-error').html('');
      $('#customer_id').val(data.id);
      $('#customer-fullname').val(data.name);
      $('#customer-email').val(data.email);
      $('#customer-phone').val(data.phone);
      $('#phone-code').val(data.phone_code);
      $('#customer-role').val(data.role_id);
      $('#customer-c_name').val(data.company_name);
      $('#customer-c_address').val(data.company_address);
      $('#customer-tags').val(data.tagsIds).trigger('change');
      if (data.img !== null) {
        $('.preview-image').attr('src', data.img);
      }
      $('#additional-form').html('');
      $('#select-template').val(data.form_template_id);

      if (data.form_template_id === null) {
        $('#select-template').val(templateId).trigger('change');
      }

      generateFields(data.fields, data.additional_data);

      $('#modelTitle').html(`Edit User: <span class="bg-info text-white px-2 rounded">${data.name}</span>`);
    });
  });

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/customers/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  $(document).on('click', '.status-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const status = $(this).data('status');

    const fields = `
      <input type="hidden" name="id" value="${id}">
      <select class="form-select" name="status">
        <option value="active" ${status === 'active' ? 'selected' : ''}>Active</option>
        <option value="verified" ${status === 'verified' ? 'selected' : ''}>Unverified</option>
        <option value="blocked" ${status === 'blocked' ? 'selected' : ''}>Blocked</option>
      </select>
    `;

    showFormModal({
      title: `Change Customer: ${name} Status`,
      icon: 'info',
      fields: fields,
      url: `${baseUrl}admin/customers/status`,
      method: 'POST',
      dataTable: dt_data // إعادة تحميل الجدول إذا موجود
    });
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    $('.preview-image').attr('src', baseUrl + 'assets/img/person.png');
    $('#customer-tags').val([]).trigger('change');
    $('.text-error').html('');
    $('#customer_id').val('');
    $('#modelTitle').html('Add New Customer');
    $('#additional-form').html('');
    $('#select-template').val('');
  });
});
