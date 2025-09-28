/**
 * User Wallets Management
 */

'use strict';
import { deleteRecord, showAlert } from '../ajax';

// Datatable (jquery)
$(function () {
  // Variable declaration for table
  var dt_transactions_table = $('.datatables-transactions');
  var start_from, end_to;

  // DataTable for transactions
  if (dt_transactions_table.length) {
    var dt_transactions = dt_transactions_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: transactionsDataUrl,
        type: 'GET',
        data: function (d) {
          d.from_date = start_from;
          d.to_date = end_to;
          d.search = $('#searchFilter').val();
          d.status = $('#statusFilter').val();
        }
      },
      columns: [
        // columns according to JSON
        { data: '' },
        { data: 'id' },
        { data: 'amount' },
        { data: 'description' },
        { data: 'task_id' },
        { data: 'user' },
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
          // Sequence
          targets: 1,
          render: function (data, type, full, meta) {
            return `<span>#${full.sequence}</span>`;
          }
        },
        {
          // Amount
          targets: 2,
          render: function (data, type, full, meta) {
            var colorClass = full.transaction_type === 'credit' ? 'text-success' : 'text-danger';
            var sign = full.transaction_type === 'credit' ? '+' : '-';
            return `<span class="fw-medium ${colorClass}">${sign}${full.amount} SAR</span>`;
          }
        },
        {
          // Description
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
          // Task ID
          targets: 4,
          render: function (data, type, full, meta) {
            return full.task_id ? `<span> Task #${full.task_id}</span>` : '-';
          }
        },
        {
          // User
          targets: 5,
          render: function (data, type, full, meta) {
            return full.task_id ? `<span> ${full.user}</span>` : '-';
          }
        },

        {
          // Created At
          targets: 6,
          render: function (data, type, full, meta) {
            return `<span class="text-nowrap">${full.created_at}</span>`;
          }
        },
        {
          // Task ID
          targets: 7,
          render: function (data, type, full, meta) {
            return `
              <div class="text-end">
                ${
                  full.task_id !== ''
                    ? `
                    <button class="btn btn-sm btn-icon delete-record " data-id="${full.id}" data-name="${full.sequence}" >
                  <i class="ti ti-trash"></i>
                </button>
                    `
                    : `<button class="btn btn-sm btn-icon edit-record " data-id="${full.id}"  >
                  <i class="ti ti-edit"></i>
                </button>
                <button class="btn btn-sm btn-icon delete-record " data-id="${full.id}"  data-name="${full.sequence}">
                  <i class="ti ti-trash"></i>
                </button>`
                }

              </div>`;
          }
        }
      ],
      order: [[1, 'desc']],
      dom:
        '<"row"' +
        '<"col-md-2"<"me-3"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: __('Search..'),
        paginate: {
          next: '<i class="ti ti-chevron-right ti-sm"></i>',
          previous: '<i class="ti ti-chevron-left ti-sm"></i>'
        }
      },
      // Buttons with Dropdown
      buttons: [
        `<label class='me-2'>
            <input type="text" id="dateRange" class="form-control ms-2 mt-5" placeholder="Select Date Range">

        </label>`,
        `<label class='me-2'>
        <select id='statusFilter' class='form-select d-inline-block w-auto ms-2 mt-5'>
          <option value="all">All</option>
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
              return 'Transaction Details #' + data['id'];
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

    $('#statusFilter').on('change', function () {
      dt_transactions.draw();
    });

    $('#searchFilter').on('input', function () {
      dt_transactions.draw();
    });
  }

  $('.dataTables_filter').hide();

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
      dt_transactions.draw();
    }
  );

  // Add Transaction
  $(document).on('click', '.add-transaction', function () {
    $('#form_submit')[0].reset();
    $('.text-error').html('');
  });

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');

    setTimeout(() => {
      $('#transactionModal').modal('hide');
    }, 2000);

    if (dt_transactions) {
      dt_transactions.draw();
    }
  });

  document.addEventListener('deletedSuccess', function (event) {
    if (dt_transactions) {
      dt_transactions.draw();
    }
  });

  $(document).on('click', '.edit-record', function () {
    var id = $(this).data('id');

    $.get(`${baseUrl}admin/user-wallets/transaction/edit/${id}`, function (data) {
      $('.form_submit').trigger('reset');
      $('#transactionModal').modal('show');

      $('.text-error').html('');
      $('#trans_id').val(data.data.id);
      $('#image').attr('src', baseUrl + (data.data.image || 'assets/img/placeholder.jpg'));
      $('#trans_amount').val(data.data.amount);
      $('#trans_description').val(data.data.description);

      $('#modelTitle').html(
        `Edit Transaction: <span class="bg-info rounded text-white px-2">${data.data.sequence}</span>`
      );
    });
  });
  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/user-wallets/transaction/delete/' + $(this).data('id');
    deleteRecord('Transaction : #' + $(this).data('name'), url);
  });

  $(document).on('click', '.show-image', function () {
    const fileUrl = $(this).data('image'); // الرابط الكامل للملف

    // استخرج اسم الملف من الرابط
    const fileName = fileUrl.split('/').pop();

    // استخرج الامتداد
    const extension = fileName.split('.').pop().toLowerCase();

    // الامتدادات المسموح بها للصور
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (imageExtensions.includes(extension)) {
      // إذا كان صورة -> اعرضها داخل <img>
      $('#modalContent').html(`
            <img id="modalImage" src="${fileUrl}" class="img-fluid rounded" alt="${fileName}">
        `);
    } else if (extension === 'pdf') {
      // استخدام Google Docs Viewer
      $('#modalContent').html(`
        <iframe src="https://docs.google.com/gview?url=${encodeURIComponent(fileUrl)}&embedded=true"
                width="100%" height="600px" style="border:none;"></iframe>
    `);
    } else {
      // أي ملف آخر (Word, Excel, ...) -> اعرض اسمه مع زر فتح
      $('#modalContent').html(`
            <div class="p-3 text-center">
                <p><strong>الملف:</strong> ${fileName}</p>
                <a href="${fileUrl}" target="_blank" class="btn btn-primary">فتح الملف</a>
            </div>
        `);
    }

    // افتح المودال
    $('#fileModal').modal('show');
  });

  $('#transactionModal').on('hidden.bs.modal', function () {
    $(this).find('form')[0].reset();
    $('.text-error').html('');
    $('#image').attr('src', baseUrl + 'assets/img/placeholder.jpg');

    $('#trans_id').val('');
    $('#modelTitle').html('Add New Transaction');
  });
});
