/**
 * Page User List
 */

'use strict';
import { deleteRecord, showFormModal } from '../ajax';

$(function () {
  var dt_data_table = $('.datatables-users');

  function toggleMaturityTime() {
    if ($('#debit').is(':checked')) {
      $('.btn-credit').addClass('btn-outline-success').removeClass('btn-success');
      $('.btn-debit').addClass('btn-danger').removeClass('btn-outline-danger');

      $('#maturity-time-group').show();
    } else {
      $('.btn-credit').addClass('btn-success').removeClass('btn-outline-success');
      $('.btn-debit').addClass('btn-outline-danger').removeClass('btn-danger');

      $('#maturity-time-group').hide();
    }
  }

  $('#credit, #debit').on('change', toggleMaturityTime);

  // استدعاء أولي عند تحميل الصفحة
  toggleMaturityTime();

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  var start_from, end_to;

  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'customer/wallet/data',
        data: function (d) {
          d.from_date = start_from;
          d.to_date = end_to;
          d.search = $('#searchFilter').val();
          d.status = $('#statusFilter').val();
          d.wallet = walletId;
        }
      },
      columns: [
        { data: '' },
        { data: 'fake_id' },
        { data: 'amount' },
        { data: 'description' },
        { data: 'maturity' },
        { data: 'task' },
        { data: 'created_at' }
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
            return `<span>${full.sequence} </span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return `<b><span class="${full.type === 'debit' ? 'text-danger' : 'text-success'}">${full.amount} SAR</span><b>`;
          }
        },

        {
          targets: 3,
          render: function (data, type, full, meta) {
            let imageBtn = '';
            if (full.image) {
              imageBtn = `
                <button class="btn btn-sm btn-icon show-image" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="${baseUrl + full.image}" title="عرض الصورة">
                  <i class="ti ti-photo"></i>
                </button>
              `;
            }

            return `
              <span>${full.description}</span>
              ${imageBtn}
            `;
          }
        },

        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span>${full.maturity}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return `
            <span>${full.task ? 'Task #' + full.task : ''}</span>
            <span>${full.clearance ? 'Clearance #' + full.clearance : ''}</span>
            `;
          }
        },

        {
          targets: 6,
          render: function (data, type, full, meta) {
            return `<span>${full.created_at}</span>`;
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
            <input type="text" id="dateRange" class="form-control ms-2 mt-5" placeholder="Select Date Range">

        </label>`,
        `<label class='me-2'>
        <select id='statusFilter' class='form-select d-inline-block w-auto ms-2 mt-5'>
          <option value="">All</option>
          <option value="credit">Credit</option>
          <option value="debit">Debit</option>
        </select>
      </label>`,
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="Search..." />
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

  $('#dateRange').daterangepicker(
    {
      opens: 'left',
      locale: {
        format: 'DD MMM YYYY',
        cancelLabel: 'Cancel',
        applyLabel: 'Apply'
      },
      startDate: moment().startOf('month'),
      endDate: moment().endOf('month')
    },
    function (start, end, label) {
      const startDate = start.format('YYYY-MM-DD');
      const endDate = end.format('YYYY-MM-DD');
      start_from = startDate;
      end_to = endDate;
      dt_data.draw();
    }
  );

  $(document).on('click', '.show-image', function () {
    const imageUrl = $(this).data('image');
    $('#modalImage').attr('src', imageUrl);
  });
});
