/**
 * Admin Customs Clearances Management
 */

'use strict';
import { deleteRecord, showAlert, showFormModal, generateFields, handleErrors, showBlockAlert } from '../../ajax';

$(function () {
  // Variable declaration for table
  var dt_data_table = $('.datatables-customs-clearances'),
    clearanceView = baseUrl + 'admin/customs-clearances/';

  var start_from;
  var end_to;
  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Customs Clearances datatable
  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'customer/customs-clearances/orders/data',
        data: function (d) {
          d.search = $('#searchFilter').val();
          d.status = $('#statusFilter').val();
          d.closed = $('#closedFilter').val();

          d.date_from = start_from;
          d.date_to = end_to;
        }
      },
      columns: [
        { data: '' }, // للـ control (responsive)
        { data: 'id' },
        { data: 'price_info' },
        { data: 'owner' },
        { data: 'status' },
        { data: 'closed' },
        { data: 'created_at' },
        { data: 'actions' }
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
          targets: 1,
          searchable: false,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            return `<span class="fw-medium">#${full.id}</span>`;
          }
        },
        {
          targets: 2,
          className: 'text-nowrap w-auto',

          render: function (data, type, full, meta) {
            return full.price_info;
          }
        },
        {
          targets: 3,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            if (full.owner && typeof full.owner === 'object') {
              return `
                <div class="d-flex flex-column">
                  <span class="fw-medium">${full.owner.name}</span>
                  <a href="tel:${full.owner.phone}" class="fw-medium">${full.owner.phone}</a>
                  <a href="mailto${full.owner.email}"class="fw-medium">${full.owner.email}</a>

                </div>
              `;
            }
            return '<span class="text-muted">غير محدد</span>';
          }
        },

        {
          targets: 4,
          render: function (data, type, full, meta) {
            return full.status;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return full.closed
              ? '<span class="badge bg-success text-white px-2 py-1 rounded">Closed</span>'
              : '<span class="badge bg-secondary text-white px-2 py-1 rounded">Open</span>';
          }
        },

        {
          targets: 6,
          render: function (data, type, full, meta) {
            return `<span class="text-nowrap">${full.created_at}</span>`;
          }
        },
        {
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return full.actions;
          }
        }
      ],
      order: [[1, 'desc']],
      dom: '<"row"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      lengthMenu: [10, 25, 50, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Customs Clearances...'
      },
      //['waiting', 'completed', 'pending']
      buttons: [
        ` <label class="me-2">
            <input type="text" id="dateRange" class="form-control  mt-5" placeholder="Select Date Range">
        </label>`,
        ` <label class="me-2">
            <select class="form-select mt-5" id="closedFilter">
                <option value="">${__('Closed Status ')}</option>
                <option value="true">${__('closed')}</option>
                <option value="false">${__('open')}</option>
            </select>
        </lable>`,
        ` <label class="me-2">
            <select class="form-select mt-5" id="statusFilter">
                <option value="">${__('All Status')}</option>
                <option value="start">${__('Started')}</option>
                <option value="completed">${__('Completed')}</option>
                <option value="canceled">${__('Canceled')}</option>
            </select>
        </lable>`,
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="Search Customs Clearances..." />
          </label>`
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of Customs Clearance #' + data['id'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== ''
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
  }

  $('.dataTables_filter').hide();

  // Filter event handlers
  $('#searchFilter').on('input', function () {
    dt_data.draw();
  });

  $('#statusFilter, #ownerTypeFilter, #templateFilter').on('change', function () {
    dt_data.draw();
  });

  $('#closedFilter, #ownerTypeFilter, #templateFilter').on('change', function () {
    dt_data.draw();
  });

  $('#dateFromFilter, #dateToFilter').on('change', function () {
    dt_data.draw();
  });

  $('#clearFilters').on('click', function () {
    $('#statusFilter, #ownerTypeFilter, #templateFilter').val('').trigger('change');
    $('#dateFromFilter, #dateToFilter').val('');
    start_from = null;
    end_to = null;
    dt_data.draw();
  });

  $('#refreshTable').on('click', function () {
    dt_data.draw();
  });

  // Delete record
  $(document).on('click', '.delete-record', function () {
    const id = $(this).data('id');
    deleteRecord(id, `${clearanceView}${id}`, dt_data);
  });

  // Initialize Select2 for customer dropdown
  $('#customer_id').select2({
    dropdownParent: $('#submitModal'),
    placeholder: 'Select Customer',
    allowClear: true
  });

  // Initialize flatpickr for date filters
  if (typeof flatpickr !== 'undefined') {
    $('.flatpickr-date').flatpickr({
      dateFormat: 'Y-m-d',
      allowInput: true
    });
  }

  $('#dateRange').daterangepicker(
    {
      opens: 'left',
      locale: {
        format: 'YYYY-MM-DD',
        separator: ' to ',
        applyLabel: 'Apply',
        cancelLabel: 'Cancel',
        fromLabel: 'From',
        toLabel: 'To',
        customRangeLabel: 'Custom',
        weekLabel: 'W',
        daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
        monthNames: [
          'January',
          'February',
          'March',
          'April',
          'May',
          'June',
          'July',
          'August',
          'September',
          'October',
          'November',
          'December'
        ],
        firstDay: 1
      },
      ranges: {
        Today: [moment(), moment()],
        Yesterday: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
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

  // Initialize tooltips
  $('[data-bs-toggle="tooltip"]').tooltip();
});
