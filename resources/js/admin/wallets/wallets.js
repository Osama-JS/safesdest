/**
 * Page User List
 */

'use strict';
import { deleteRecord, showFormModal } from '../../ajax';

$(function () {
  var dt_data_table = $('.datatables-users');

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/wallets/data',
        data: function (d) {
          d.search = $('#searchFilter').val();
          d.status = $('#statusFilter').val();
        }
      },
      columns: [
        { data: '' },
        { data: 'fake_id' },
        { data: 'name' },
        { data: 'balance' },
        { data: 'debt_ceiling' },
        { data: 'status' },
        { data: 'preview' },
        { data: 'last_transaction' },
        { data: null }
      ],
      columnDefs: [
        {
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 1,
          targets: 0,
          render: function () {
            return '';
          }
        },
        {
          targets: 1,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `<span>${full.fake_id}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return `<span>${full.name}</span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span class="${full.balance < 0 ? 'text-danger' : 'text-success'}">${full.balance}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span>${full.debt_ceiling}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            var html = `<label class="switch switch-success">
              <input type="checkbox" class="switch-input edit_status" data-id=${full['id']} ${full['status'] == 1 ? 'checked' : ''} />
              <span class="switch-toggle-slider">
                <span class="switch-on">
                  <i class="ti ti-check"></i>
                </span>
                <span class="switch-off">
                  <i class="ti ti-x"></i>
                </span>
              </span>
            </label>`;
            return html;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            var html = `<label class="switch switch-success">
            <input type="checkbox" class="switch-input edit_preview" data-id=${full['id']} ${full['preview'] == 1 ? 'checked' : ''} />
            <span class="switch-toggle-slider">
              <span class="switch-on">
                <i class="ti ti-check"></i>
              </span>
              <span class="switch-off">
                <i class="ti ti-x"></i>
              </span>
            </span>
          </label>`;
            return html;
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            return `<span>${full.last_transaction}</span>`;
          }
        },

        {
          targets: 8,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `
              <div class="d-flex align-items-center gap-2">
              <a href="${baseUrl + 'admin/wallets/transaction/show/' + full.id + '/' + full.name}" class="btn btn-sm btn-icon " data-id="${full.id}"  data-name="${full.name}">
                  <i class="ti ti-eye"></i>
                </a>
                <button class="btn btn-sm btn-icon edit-record " data-id="${full.id}" data-name="${full.name}" data-debt="${full.debt_ceiling}" >
                  <i class="ti ti-edit"></i>
                </button>



              </div>`;
          }
        }
      ],
      order: [[1, 'asc']],
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
          <option value="customer">Customers</option>
          <option value="driver">Drivers</option>
        </select>
      </label>`,
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="${__('search')}..." />
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

  $(document).on('click', '.edit-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const debtCeiling = $(this).data('debt');
    const fields = `
        <input type="hidden" name="id" value="${id}">
        <div class="mb-3">
          <label for="name" class="form-label text-start">Debt Ceiling</label>
          <input type="number" class="form-control" min=0 step="any" name="debt" value="${debtCeiling}" required>
        </div>
      `;

    showFormModal({
      title: `Update Debt Ceiling For Customer: <h4> <span class="bg-info p-0 px-2 rounded text-white"> ${name} </span> </h4>`,
      icon: 'info',
      fields: fields,
      url: `${baseUrl}admin/wallets/update`,
      method: 'POST',
      dataTable: dt_data // إعادة تحميل الجدول إذا موجود
    });
  });

  $(document).on('change', '.edit_status', function () {
    var Id = $(this).data('id');
    $.ajax({
      url: `${baseUrl}admin/wallets/status/${Id}`,
      type: 'post',

      success: function (response) {
        if (response.status != 1) {
          showAlert('error', data.error, 10000, true);
        }
      },
      error: function () {
        showAlert('Error!', 'Failed Request', 'error');
      }
    });
  });

  $(document).on('change', '.edit_preview', function () {
    var Id = $(this).data('id');
    $.ajax({
      url: `${baseUrl}admin/wallets/preview/${Id}`,
      type: 'post',

      success: function (response) {
        if (response.status != 1) {
          showAlert('error', data.error, 10000, true);
        }
      },
      error: function () {
        showAlert('Error!', 'Failed Request', 'error');
      }
    });
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $(this).find('form')[0].reset();
    $('.text-error').html('');
    $('#tag_id').val('');
    $('#modelTitle').html('Add New Tag');
  });
});
