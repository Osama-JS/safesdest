/**
 * User Wallets Management
 */

'use strict';
import { deleteRecord, showAlert } from '../ajax';
import writtenNumber from 'written-number';

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
                <button class="btn btn-sm btn-icon show-image" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="${baseUrl + full.image}" title="Ø¹Ø±Ø¶ Ø§Ù„ØµÙˆØ±Ø©">
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
                    <button class="btn btn-sm btn-icon delete-record " data-id="${full.id}" data-name="${full.sequence}" data-task-id="${full.task_id}" >
                  <i class="ti ti-trash"></i>
                </button>
                    `
                    : `<button class="btn btn-sm btn-icon edit-record " data-id="${full.id}"  >
                  <i class="ti ti-edit"></i>
                </button>
                <button class="btn btn-sm btn-icon delete-record " data-id="${full.id}"  data-name="${full.sequence}" data-task-id="${full.task_id}">
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
    let id = $(this).data('id');
    let sequence = $(this).data('name');
    let taskId = $(this).data('task-id');
    let url = baseUrl + 'admin/user-wallets/transaction/delete/' + id;

    if (taskId && taskId !== '' && taskId !== 'null' && taskId !== null) {
      Swal.fire({
        title: 'ØµÙ„Ø§Ø­ÙŠØ© Ù…Ø·Ù„ÙˆØ¨Ø©',
        text: 'Ù‡Ø°Ù‡ Ø§Ù„Ø¹Ù…ÙˆÙ„Ø© Ù…Ø±ØªØ¨Ø·Ø© Ø¨Ù…Ù‡Ù…Ø©. ÙŠØ±Ø¬Ù‰ Ø¥Ø¯Ø®Ø§Ù„ ÙƒÙ„Ù…Ø© Ø§Ù„Ù…Ø±ÙˆØ± Ù„Ø­Ø°ÙÙ‡Ø§:',
        input: 'password',
        inputAttributes: {
          autocapitalize: 'off',
          autocorrect: 'off'
        },
        showCancelButton: true,
        confirmButtonText: 'Ø­Ø°Ù Ø§Ù„Ø¹Ù…ÙˆÙ„Ø©',
        cancelButtonText: 'Ø¥Ù„ØºØ§Ø¡',
        customClass: { confirmButton: 'btn btn-danger me-3', cancelButton: 'btn btn-label-secondary' }
      }).then((result) => {
        if (result.isConfirmed) {
          if (!result.value) {
            Swal.fire('Ø®Ø·Ø£', 'ÙƒÙ„Ù…Ø© Ø§Ù„Ù…Ø±ÙˆØ± Ù…Ø·Ù„ÙˆØ¨Ø©', 'error');
            return;
          }
          $.ajax({
            url: url,
            type: 'DELETE',
            data: {
              password: result.value,
              _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
              if (res.status == 1) {
                Swal.fire('ØªÙ… ×”×—Ø°Ù!', res.success, 'success').then(() => dt_transactions.draw());
              } else {
                Swal.fire('Ø®Ø·Ø£!', res.error || 'Ø­Ø¯Ø« Ø®Ø·Ø£.', 'error');
              }
            },
            error: function () {
              Swal.fire('Ø®Ø·Ø£!', 'Ø­Ø¯Ø« Ø®Ø·Ø£ Ø£Ø«Ù†Ø§Ø¡ Ø§Ù„Ø§ØªØµØ§Ù„ Ø¨Ø§Ù„Ø®Ø§Ø¯Ù….', 'error');
            }
          });
        }
      });
    } else {
      deleteRecord('Transaction : #' + sequence, url);
    }
  });

  $(document).on('click', '.show-image', function () {
    const fileUrl = $(this).data('image'); // Ø§Ù„Ø±Ø§Ø¨Ø· Ø§Ù„ÙƒØ§Ù…Ù„ Ù„Ù„Ù…Ù„Ù

    // Ø§Ø³ØªØ®Ø±Ø¬ Ø§Ø³Ù… Ø§Ù„Ù…Ù„Ù Ù…Ù† Ø§Ù„Ø±Ø§Ø¨Ø·
    const fileName = fileUrl.split('/').pop();

    // Ø§Ø³ØªØ®Ø±Ø¬ Ø§Ù„Ø§Ù…ØªØ¯Ø§Ø¯
    const extension = fileName.split('.').pop().toLowerCase();

    // Ø§Ù„Ø§Ù…ØªØ¯Ø§Ø¯Ø§Øª Ø§Ù„Ù…Ø³Ù…ÙˆØ­ Ø¨Ù‡Ø§ Ù„Ù„ØµÙˆØ±
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (imageExtensions.includes(extension)) {
      // Ø¥Ø°Ø§ ÙƒØ§Ù† ØµÙˆØ±Ø© -> Ø§Ø¹Ø±Ø¶Ù‡Ø§ Ø¯Ø§Ø®Ù„ <img>
      $('#modalContent').html(`
            <img id="modalImage" src="${fileUrl}" class="img-fluid rounded" alt="${fileName}">
        `);
    } else if (extension === 'pdf') {
      // Ø§Ø³ØªØ®Ø¯Ø§Ù… Google Docs Viewer
      $('#modalContent').html(`
        <iframe src="https://docs.google.com/gview?url=${encodeURIComponent(fileUrl)}&embedded=true"
                width="100%" height="600px" style="border:none;"></iframe>
    `);
    } else {
      // Ø£ÙŠ Ù…Ù„Ù Ø¢Ø®Ø± (Word, Excel, ...) -> Ø§Ø¹Ø±Ø¶ Ø§Ø³Ù…Ù‡ Ù…Ø¹ Ø²Ø± ÙØªØ­
      $('#modalContent').html(`
            <div class="p-3 text-center">
                <p><strong>Ø§Ù„Ù…Ù„Ù:</strong> ${fileName}</p>
                <a href="${fileUrl}" target="_blank" class="btn btn-primary">ÙØªØ­ Ø§Ù„Ù…Ù„Ù</a>
            </div>
        `);
    }

    // Ø§ÙØªØ­ Ø§Ù„Ù…ÙˆØ¯Ø§Ù„
    $('#fileModal').modal('show');
  });

  $('#transactionModal').on('hidden.bs.modal', function () {
    $(this).find('form')[0].reset();
    $('.text-error').html('');
    $('#image').attr('src', baseUrl + 'assets/img/placeholder.jpg');

    $('#trans_id').val('');
    $('#modelTitle').html('Add New Transaction');
  });
  $(document).on('click', '#clearWalletBtn', function () {
    Swal.fire({
      title: 'Ø·Ù„Ø¨ ÙƒÙ„Ù…Ø© Ø§Ù„Ù…Ø±ÙˆØ± Ø§Ù„Ø³Ø±ÙŠØ©',
      text: 'Ø§Ù„Ø±Ø¬Ø§Ø¡ Ø¥Ø¯Ø®Ø§Ù„ Ø§Ù„ÙƒÙ„Ù…Ø© Ø§Ù„Ø³Ø±ÙŠØ© Ù„ØªØ£ÙƒÙŠØ¯ Ø¹Ù…Ù„ÙŠØ© ØªØµÙÙŠØ© Ø§Ù„Ù…Ø­ÙØ¸Ø© Ø¨Ø§Ù„ÙƒØ§Ù…Ù„:',
      input: 'password',
      inputPlaceholder: 'Ø£Ø¯Ø®Ù„ Ø§Ù„ÙƒÙ„Ù…Ø© Ø§Ù„Ø³Ø±ÙŠØ©...',
      inputAttributes: {
        autocapitalize: 'off',
        autocorrect: 'off'
      },
      showCancelButton: true,
      confirmButtonText: 'ØªØ­Ù‚Ù‚ ÙˆØªØµÙÙŠØ© Ø§Ù„Ù…Ø­ÙØ¸Ø©',
      cancelButtonText: 'Ø¥Ù„ØºØ§Ø¡',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.isConfirmed) {
        if (result.value !== 'OsamaAlsamomy@1998') {
          Swal.fire({
            title: 'Ø®Ø·Ø£ ÙÙŠ Ø§Ù„ØªØ­Ù‚Ù‚!',
            text: 'Ø§Ù„ÙƒÙ„Ù…Ø© Ø§Ù„Ø³Ø±ÙŠØ© Ø§Ù„Ù…Ø¯Ø®Ù„Ø© ØºÙŠØ± ØµØ­ÙŠØ­Ø©ØŒ Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø¥ØªÙ…Ø§Ù… Ø¹Ù…Ù„ÙŠØ© Ø§Ù„ØªØµÙÙŠØ©.',
            icon: 'error',
            customClass: {
              confirmButton: 'btn btn-primary'
            }
          });
          return;
        }

        // ÙƒÙ„Ù…Ø© Ø§Ù„Ù…Ø±ÙˆØ± ØµØ­ÙŠØ­Ø©ØŒ Ù†Ù‚ÙˆÙ… Ø¨Ø·Ù„Ø¨ Ø§Ù„ØªØ£ÙƒÙŠØ¯ Ø§Ù„Ù†Ù‡Ø§Ø¦ÙŠ Ø£Ùˆ Ø§Ù„Ø¨Ø¯Ø¡ Ø§Ù„ÙÙˆØ±ÙŠ
        Swal.fire({
          title: 'Ù‡Ù„ Ø£Ù†Øª Ù…ØªØ£ÙƒØ¯ Ù†Ù‡Ø§Ø¦ÙŠØ§Ù‹ØŸ',
          text: 'Ø³ÙŠØªÙ… Ù…Ø³Ø­ ÙƒØ§ÙØ© Ø§Ù„Ø­Ø±ÙƒØ§Øª Ø§Ù„Ù…Ø§Ù„ÙŠØ© Ù…Ù† Ø§Ù„Ù…Ø­ÙØ¸Ø© Ù†Ù‡Ø§Ø¦ÙŠØ§Ù‹ ÙˆØªØµÙÙŠØ± Ø§Ù„Ø±ØµÙŠØ¯!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ù†Ø¹Ù…ØŒ Ù…Ø³Ø­ ÙˆØªØµÙÙŠØ©!',
          cancelButtonText: 'ØªØ±Ø§Ø¬Ø¹',
          customClass: {
            confirmButton: 'btn btn-danger me-3',
            cancelButton: 'btn btn-label-secondary'
          },
          buttonsStyling: false
        }).then(function (confirmResult) {
          if (confirmResult.value) {
            $.ajax({
              url: clearWalletUrl,
              type: 'POST',
              data: {
                _token: $('meta[name="csrf-token"]').attr('content')
              },
              success: function (response) {
                if (response.status === 1) {
                  Swal.fire({
                    icon: 'success',
                    title: 'ØªÙ…Øª Ø§Ù„ØªØµÙÙŠØ©!',
                    text: response.success,
                    customClass: {
                      confirmButton: 'btn btn-success'
                    }
                  }).then(() => {
                    location.reload();
                  });
                } else {
                  Swal.fire({
                    title: 'Ø®Ø·Ø£!',
                    text: response.error,
                    icon: 'error',
                    customClass: {
                      confirmButton: 'btn btn-primary'
                    }
                  });
                }
              },
              error: function () {
                Swal.fire({
                  title: 'Ø®Ø·Ø£!',
                  text: 'Ø­Ø¯Ø« Ø®Ø·Ø£ Ø£Ø«Ù†Ø§Ø¡ Ù…Ø­Ø§ÙˆÙ„Ø© ØªØµÙÙŠØ© Ø§Ù„Ù…Ø­ÙØ¸Ø©.',
                  icon: 'error',
                  customClass: {
                    confirmButton: 'btn btn-primary'
                  }
                });
              }
            });
          }
        });
      }
    });
  });

  // Manual Commission Logic
  $(document).on('click', '#btnSearchTask', function () {
    const taskId = $('#search_task_id').val();
    if (!taskId) return;

    const btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

    $.ajax({
      url: searchTaskUrl,
      type: 'GET',
      data: { task_id: taskId },
      success: function (response) {
        btn.prop('disabled', false).html('<i class="ti ti-search"></i>');
        if (response.status === 1) {
          $('#taskSearchResult').removeClass('d-none');
          $('#resCustomerName').text(response.task.customer_name);
          $('#resTotalPrice').text(response.task.total_price);
          $('#resPlatformCut').text(response.task.platform_cut);
          
          let statusText = response.task.status;
          let badgeClass = 'bg-secondary';
          if (statusText === 'completed') badgeClass = 'bg-success';
          if (statusText === 'canceled') badgeClass = 'bg-danger';
          
          $('#taskStatusBadge').text(statusText).attr('class', 'badge ' + badgeClass);

          let eligibilityMsg = '';
          let alertClass = 'alert-info';
          let allowCalc = true;

          if (response.already_calculated) {
            eligibilityMsg = '<i class="ti ti-alert-triangle me-1"></i> ØªÙ†Ø¨ÙŠÙ‡: ØªÙ… Ø§Ø­ØªØ³Ø§Ø¨ Ø¹Ù…ÙˆÙ„Ø© Ù‡Ø°Ù‡ Ø§Ù„Ù…Ù‡Ù…Ø© Ù…Ø³Ø¨Ù‚Ø§Ù‹ Ù„Ù‡Ø°Ø§ Ø§Ù„Ù…Ø³ØªØ«Ù…Ø±.';
            alertClass = 'alert-warning';
            allowCalc = false;
          } else if (response.funded_by_other) {
            eligibilityMsg = '<i class="ti ti-circle-x me-1"></i> Ø®Ø·Ø£: Ù‡Ø°Ù‡ Ø§Ù„Ù…Ù‡Ù…Ø© Ù…Ù…ÙˆÙ„Ø© Ù…Ù† Ù‚Ø¨Ù„ Ù…Ø³ØªØ«Ù…Ø± Ø¢Ø®Ø±.';
            alertClass = 'alert-danger';
            allowCalc = false;
          } else {
            eligibilityMsg = '<i class="ti ti-check me-1"></i> Ø§Ù„Ù…Ù‡Ù…Ø© Ø¬Ø§Ù‡Ø²Ø© Ù„Ù„Ø§Ø­ØªØ³Ø§Ø¨ Ø§Ù„ÙŠØ¯ÙˆÙŠ.';
            alertClass = 'alert-success';
            if (response.is_cancelled) {
              eligibilityMsg += ' (Ù…Ù„Ø§Ø­Ø¸Ø©: Ø§Ù„Ù…Ù‡Ù…Ø© Ù…Ù„ØºØ§Ø©)';
            }
          }

          $('#taskEligibilityMsg').html(eligibilityMsg).attr('class', 'alert ' + alertClass + ' mb-0');
          if (allowCalc) {
            $('#btnConfirmManualCalc').removeClass('d-none');
          } else {
            $('#btnConfirmManualCalc').addClass('d-none');
          }
        } else {
          Swal.fire({ title: 'Ø®Ø·Ø£!', text: response.error, icon: 'error' });
          $('#taskSearchResult').addClass('d-none');
          $('#btnConfirmManualCalc').addClass('d-none');
        }
      },
      error: function () {
        btn.prop('disabled', false).html('<i class="ti ti-search"></i>');
        Swal.fire({ title: 'Ø®Ø·Ø£!', text: 'Ø­Ø¯Ø« Ø®Ø·Ø£ Ø£Ø«Ù†Ø§Ø¡ Ø§Ù„Ø¨Ø­Ø« Ø¹Ù† Ø§Ù„Ù…Ù‡Ù…Ø©.', icon: 'error' });
      }
    });
  });

  $(document).on('click', '#btnConfirmManualCalc', function () {
    const taskId = $('#search_task_id').val();
    const btn = $(this);

    Swal.fire({
      title: 'Ù‡Ù„ Ø£Ù†Øª Ù…ØªØ£ÙƒØ¯ØŸ',
      text: "Ø³ÙŠØªÙ… Ø§Ø­ØªØ³Ø§Ø¨ Ø¹Ù…ÙˆÙ„Ø© Ø§Ù„Ù…Ù‡Ù…Ø© Ø±Ù‚Ù… #" + taskId + " ÙŠØ¯ÙˆÙŠØ§Ù‹.",
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ù†Ø¹Ù…ØŒ Ø§Ø­ØªØ³Ø¨Ù‡Ø§',
      cancelButtonText: 'Ø¥Ù„ØºØ§Ø¡',
      customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
        $.ajax({
          url: calculateManualUrl,
          type: 'POST',
          data: {
            task_id: taskId,
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            btn.prop('disabled', false).text('Calculate Commission');
            if (response.status === 1) {
              Swal.fire({ icon: 'success', title: 'Ù†Ø¬Ø§Ø­!', text: response.success, customClass: { confirmButton: 'btn btn-success' } })
                .then(() => location.reload());
            } else {
              Swal.fire({ title: 'Ø®Ø·Ø£!', text: response.error, icon: 'error' });
            }
          },
          error: function () {
            btn.prop('disabled', false).text('Calculate Commission');
            Swal.fire({ title: 'Ø®Ø·Ø£!', text: 'Ø­Ø¯Ø« Ø®Ø·Ø£ Ø£Ø«Ù†Ø§Ø¡ Ø¹Ù…Ù„ÙŠØ© Ø§Ù„Ø§Ø­ØªØ³Ø§Ø¨.', icon: 'error' });
          }
        });
      }
    });
  });

  $(document).on('click', '#calculateGeneralBtn', function () {
    const btn = $(this);
    Swal.fire({
      title: 'ØªØ£ÙƒÙŠØ¯',
      text: 'Ù‡Ù„ ØªØ±ÙŠØ¯ Ø§Ø­ØªØ³Ø§Ø¨ Ø¬Ù…ÙŠØ¹ Ø§Ù„Ø¹Ù…ÙˆÙ„Ø§Øª Ø§Ù„Ø¹Ø§Ù…Ø© Ø§Ù„Ù…Ø³ØªØ­Ù‚Ø© Ù„Ù‡Ø°Ø§ Ø§Ù„Ù…Ø³ØªØ«Ù…Ø±ØŸ',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ù†Ø¹Ù…ØŒ Ø§Ø¨Ø¯Ø£ Ø§Ù„Ø§Ø­ØªØ³Ø§Ø¨',
      cancelButtonText: 'Ø¥Ù„ØºØ§Ø¡',
      customClass: { confirmButton: 'btn btn-success me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
        $.ajax({
          url: calculateGeneralUrl,
          type: 'POST',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            btn.prop('disabled', false).html('<i class="ti ti-calculator me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block"> Calculate General Commissions</span>');
            if (response.status === 1) {
              Swal.fire({ icon: response.info ? 'info' : 'success', title: response.info ? 'ØªÙ†Ø¨ÙŠÙ‡' : 'Ù†Ø¬Ø§Ø­!', text: response.info || response.success, customClass: { confirmButton: 'btn btn-primary' } })
                .then(() => location.reload());
            } else {
              Swal.fire({ title: 'Ø®Ø·Ø£!', text: response.error, icon: 'error' });
            }
          },
          error: function () {
            btn.prop('disabled', false).html('<i class="ti ti-calculator me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block"> Calculate General Commissions</span>');
            Swal.fire({ title: 'Ø®Ø·Ø£!', text: 'Ø­Ø¯Ø« Ø®Ø·Ø£ Ø£Ø«Ù†Ø§Ø¡ Ø¹Ù…Ù„ÙŠØ© Ø§Ù„Ø§Ø­ØªØ³Ø§Ø¨.', icon: 'error' });
          }
        });
      }
    });
  });

  $(document).on('click', '#calculateTasksBtn', function () {
    const btn = $(this);
    Swal.fire({
      title: 'تأكيد',
      text: 'هل تريد احتساب عمولات جميع المهام الممولة التي لم يتم احتسابها بعد؟',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'نعم، ابدأ الاحتساب',
      cancelButtonText: 'إلغاء',
      customClass: { confirmButton: 'btn btn-success me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
        $.ajax({
          url: calculateTasksUrl,
          type: 'POST',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            btn.prop('disabled', false).html('<i class="ti ti-calculator me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block"> احتساب عمولات المهام الممولة</span>');
            if (response.status === 1) {
              Swal.fire({ icon: response.info ? 'info' : 'success', title: response.info ? 'تنبيه' : 'نجاح!', text: response.info || response.success, customClass: { confirmButton: 'btn btn-primary' } })
                .then(() => location.reload());
            } else {
              Swal.fire({ title: 'خطأ!', text: response.error, icon: 'error' });
            }
          },
          error: function () {
            btn.prop('disabled', false).html('<i class="ti ti-calculator me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block"> احتساب عمولات المهام الممولة</span>');
            Swal.fire({ title: 'خطأ!', text: 'حدث خطأ أثناء عملية الاحتساب.', icon: 'error' });
          }
        });
      }
    });
  });

  $(document).on('click', '#calculateBrokerBtn', function () {
    const btn = $(this);
    Swal.fire({
      title: 'ØªØ£ÙƒÙŠØ¯ Ø§Ø­ØªØ³Ø§Ø¨ Ø¹Ù…ÙˆÙ„Ø§Øª Ø§Ù„ÙˆØ³ÙŠØ·',
      text: 'Ù‡Ù„ ØªØ±ÙŠØ¯ ÙØ­Øµ ÙˆØ§Ø­ØªØ³Ø§Ø¨ ÙƒØ§ÙØ© Ø¹Ù…ÙˆÙ„Ø§Øª Ø§Ù„ÙˆØ³Ø§Ø·Ø© Ø§Ù„Ù…Ø³ØªØ­Ù‚Ø© Ù„Ù‡Ø°Ø§ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… Ø¹Ù† Ø§Ù„Ù…Ù‡Ø§Ù… Ø§Ù„ØªÙŠ Ù…ÙˆÙ„Ù‡Ø§ Ø§Ù„Ù…Ø³ØªØ«Ù…Ø±ÙˆÙ† Ø§Ù„Ù…Ø±ØªØ¨Ø·ÙˆÙ† Ø¨Ù‡ØŸ',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ù†Ø¹Ù…ØŒ Ø§Ø¨Ø¯Ø£ Ø§Ù„Ø§Ø­ØªØ³Ø§Ø¨',
      cancelButtonText: 'Ø¥Ù„ØºØ§Ø¡',
      customClass: { confirmButton: 'btn btn-warning me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Ø¬Ø§Ø±ÙŠ Ø§Ù„Ø§Ø­ØªØ³Ø§Ø¨...');
        $.ajax({
          url: calculateBrokerUrl,
          type: 'POST',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            btn.prop('disabled', false).html('<i class="ti ti-user-check me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block"> Ø§Ø­ØªØ³Ø§Ø¨ Ø¹Ù…ÙˆÙ„Ø§Øª Ø§Ù„ÙˆØ³ÙŠØ·</span>');
            if (response.status === 1) {
              Swal.fire({ icon: response.info ? 'info' : 'success', title: response.info ? 'ØªÙ†Ø¨ÙŠÙ‡' : 'Ù†Ø¬Ø§Ø­!', text: response.info || response.success, customClass: { confirmButton: 'btn btn-primary' } })
                .then(() => location.reload());
            } else {
              Swal.fire({ title: 'Ø®Ø·Ø£!', text: response.error, icon: 'error' });
            }
          },
          error: function () {
            btn.prop('disabled', false).html('<i class="ti ti-user-check me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block"> Ø§Ø­ØªØ³Ø§Ø¨ Ø¹Ù…ÙˆÙ„Ø§Øª Ø§Ù„ÙˆØ³ÙŠØ·</span>');
            Swal.fire({ title: 'Ø®Ø·Ø£!', text: 'Ø­Ø¯Ø« Ø®Ø·Ø£ Ø£Ø«Ù†Ø§Ø¡ Ø¹Ù…Ù„ÙŠØ© Ø§Ù„Ø§Ø­ØªØ³Ø§Ø¨.', icon: 'error' });
          }
        });
      }
    });
  });

  $(document).on('click', '#calculateTruckBrokerBtn', function () {
    const btn = $(this);
    Swal.fire({
      title: 'تأكيد احتساب عمولات وسيط الشاحنات',
      text: 'هل تريد فحص واحتساب كافة عمولات الوساطة المستحقة لهذا الوسيط عن المهام المرتبطة به أو بسائقيه؟',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'نعم، ابدأ الاحتساب',
      cancelButtonText: 'إلغاء',
      customClass: { confirmButton: 'btn btn-dark me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الاحتساب...');
        $.ajax({
          url: calculateTruckBrokerUrl,
          type: 'POST',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            btn.prop('disabled', false).html('<i class="ti ti-truck me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block"> احتساب عمولات وسيط الشاحنات</span>');
            if (response.status === 1) {
              Swal.fire({ icon: response.info ? 'info' : 'success', title: response.info ? 'تنبيه' : 'نجاح!', text: response.info || response.success, customClass: { confirmButton: 'btn btn-primary' } })
                .then(() => location.reload());
            } else {
              Swal.fire({ title: 'خطأ!', text: response.error, icon: 'error' });
            }
          },
          error: function () {
            btn.prop('disabled', false).html('<i class="ti ti-truck me-0 me-sm-1 ti-xs"></i><span class="d-none d-sm-inline-block"> احتساب عمولات وسيط الشاحنات</span>');
            Swal.fire({ title: 'خطأ!', text: 'حدث خطأ أثناء عملية الاحتساب.', icon: 'error' });
          }
        });
      }
    });
  });

  $(document).on('click', '#calculateOldTruckBrokerBtn', function () {
    $('#oldBrokerCommissionModal').modal('show');
    $('#oldBrokerLoading').removeClass('d-none');
    $('#oldBrokerContent').addClass('d-none');
    $('#oldBrokerNoData').addClass('d-none');
    $('#oldBrokerExportBtn').addClass('d-none');
    $('#oldBrokerConfirmBtn').addClass('d-none');
    $('#oldBrokerPassword').val('');
    
    $.ajax({
      url: previewOldTruckBrokerUrl,
      type: 'GET',
      success: function (response) {
        $('#oldBrokerLoading').addClass('d-none');
        
        if (response.status === 1) {
          if (response.tasks.length > 0) {
            $('#oldBrokerContent').removeClass('d-none');
            $('#oldBrokerExportBtn').removeClass('d-none');
            $('#oldBrokerConfirmBtn').removeClass('d-none');
            $('#oldBrokerTotalCommission').text(response.total_commission);
            
            let html = '';
            response.tasks.forEach(function(item) {
              html += `
                <tr>
                  <td>${item.task_id}</td>
                  <td>${item.type_name}</td>
                  <td>${item.total_price}</td>
                  <td>${item.commission}</td>
                  <td>${item.date}</td>
                </tr>
              `;
            });
            $('#oldBrokerTasksTable tbody').html(html);
          } else {
            $('#oldBrokerNoData').removeClass('d-none');
          }
        } else {
          $('#oldBrokerCommissionModal').modal('hide');
          Swal.fire({ title: 'خطأ!', text: response.error, icon: 'error' });
        }
      },
      error: function () {
        $('#oldBrokerCommissionModal').modal('hide');
        Swal.fire({ title: 'خطأ!', text: 'حدث خطأ أثناء جلب المعاينة.', icon: 'error' });
      }
    });
  });

  $(document).on('click', '#previewAllOldTruckBrokerBtn', function () {
    $('#allOldBrokerCommissionModal').modal('show');
    $('#allOldBrokerLoading').removeClass('d-none');
    $('#allOldBrokerContent').addClass('d-none');
    $('#allOldBrokerNoData').addClass('d-none');
    $('#allOldBrokerExportBtn').addClass('d-none');
    
    $.ajax({
      url: previewAllOldTruckBrokerUrl,
      type: 'GET',
      success: function (response) {
        $('#allOldBrokerLoading').addClass('d-none');
        
        if (response.status === 1) {
          if (response.tasks.length > 0) {
            $('#allOldBrokerContent').removeClass('d-none');
            $('#allOldBrokerExportBtn').removeClass('d-none');
            $('#allOldBrokerTotalCommission').text(response.total_commission);
            
            let html = '';
            response.tasks.forEach(function(item) {
              html += `
                <tr>
                  <td>${item.task_id}</td>
                  <td>${item.broker_name}</td>
                  <td>${item.total_price}</td>
                  <td>${item.commission}</td>
                  <td>${item.date}</td>
                </tr>
              `;
            });
            $('#allOldBrokerTasksTable tbody').html(html);
          } else {
            $('#allOldBrokerNoData').removeClass('d-none');
          }
        } else {
          $('#allOldBrokerCommissionModal').modal('hide');
          Swal.fire({ title: 'خطأ!', text: response.error, icon: 'error' });
        }
      },
      error: function () {
        $('#allOldBrokerCommissionModal').modal('hide');
        Swal.fire({ title: 'خطأ!', text: 'حدث خطأ أثناء جلب المعاينة.', icon: 'error' });
      }
    });
  });

  $(document).on('click', '#oldBrokerConfirmBtn', function () {
    const password = $('#oldBrokerPassword').val();
    if (!password) {
      Swal.fire({ title: 'تنبيه', text: 'يجب إدخال كلمة المرور لتأكيد العملية', icon: 'warning' });
      return;
    }

    const btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جاري الاحتساب...');

    $.ajax({
      url: calculateOldTruckBrokerUrl,
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        password: password
      },
      success: function (response) {
        btn.prop('disabled', false).html('<i class="ti ti-calculator me-1"></i> تأكيد واحتساب');
        if (response.status === 1) {
          $('#oldBrokerCommissionModal').modal('hide');
          Swal.fire({ 
            icon: response.info ? 'info' : 'success', 
            title: response.info ? 'تنبيه' : 'نجاح!', 
            text: response.info || response.success, 
            customClass: { confirmButton: 'btn btn-primary' } 
          }).then(() => location.reload());
        } else {
          Swal.fire({ title: 'خطأ!', text: response.error, icon: 'error' });
        }
      },
      error: function () {
        btn.prop('disabled', false).html('<i class="ti ti-calculator me-1"></i> تأكيد واحتساب');
        Swal.fire({ title: 'خطأ!', text: 'حدث خطأ أثناء عملية الاحتساب.', icon: 'error' });
      }
    });
  });

  // Ø¥Ø¹Ø§Ø¯Ø© Ø§Ø³ØªØ«Ù…Ø§Ø± Ø§Ù„Ø£Ø±Ø¨Ø§Ø­ (Ù„Ù„Ù…Ø¶Ø§Ø±Ø¨ÙŠÙ†)
  $(document).on('submit', '#reinvestProfitsForm', function (e) {
    e.preventDefault();

    const form = $(this);
    const submitBtn = $('#reinvestSubmitBtn');
    const amountInput = $('#reinvest_amount');
    const amount = parseFloat(amountInput.val());
    const maxAmount = parseFloat(amountInput.attr('max')) || withdrawableBalance;
    const amountError = $('#reinvest_amount_error');

    amountError.addClass('d-none').text('');

    if (isNaN(amount) || amount <= 0) {
      amountError.removeClass('d-none').text(__('Enter valid amount'));
      return;
    }

    if (amount > maxAmount) {
      amountError.removeClass('d-none').text(__('Amount exceeds withdrawable'));
      return;
    }

    Swal.fire({
      title: __('Confirm reinvestment title'),
      html: __('Confirm reinvestment html', { amount: amount.toFixed(2) }),
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: __('Yes confirm'),
      cancelButtonText: __('Cancel'),
      customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(function (result) {
      if (!result.value) return;

      submitBtn.prop('disabled', true);
      submitBtn.find('.spinner-border').removeClass('d-none');

      $.ajax({
        url: typeof reinvestProfitsUrl !== 'undefined' ? reinvestProfitsUrl : '',
        type: 'POST',
        data: {
          amount: amount,
          notes: $('#reinvest_notes').val(),
          _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
          submitBtn.prop('disabled', false);
          submitBtn.find('.spinner-border').addClass('d-none');

          if (response.status === 1) {
            Swal.fire({
              icon: 'success',
              title: __('Success'),
              text: response.success,
              customClass: { confirmButton: 'btn btn-success' }
            }).then(function () {
              location.reload();
            });
          } else if (response.errors) {
            const firstError = Object.values(response.errors)[0][0];
            Swal.fire({ title: __('Error'), text: firstError, icon: 'error' });
          } else {
            Swal.fire({ title: __('Error'), text: response.error || __('unexpectedErrorOccurred'), icon: 'error' });
          }
        },
        error: function () {
          submitBtn.prop('disabled', false);
          submitBtn.find('.spinner-border').addClass('d-none');
          Swal.fire({ title: __('Error'), text: __('Reinvestment error'), icon: 'error' });
        }
      });
    });
  });

  $(document).on('click', '#reinvestMaxBtn', function () {
    const amountInput = $('#reinvest_amount');
    if (amountInput.length) {
      amountInput.val(amountInput.attr('max'));
    }
  });

  // ==========================================
  // User Wallet Payment Request Handler
  // ==========================================

  $(document).on('click', '#user-payment-request', function () {
    const targetUserId = typeof currentUserId !== 'undefined' && currentUserId ? currentUserId : (typeof userId !== 'undefined' ? userId : null);
    if (!targetUserId) {
      showAlert('error', 'User ID is missing');
      return;
    }

    // Fetch user and wallet details for payment request
    $.get(`${baseUrl}admin/users/${targetUserId}/wallet/payment-request-data`, function (data) {
      if (data.status === 0) {
        showAlert('error', data.error || 'Error loading wallet data');
        return;
      }

      const wallet = data.wallet;
      const balance = parseFloat(wallet.balance) || 0;
      const withdrawable = parseFloat(wallet.withdrawable_balance) || balance;

      // Fill wallet & user information in modal
      $('#userPaymentRequestWalletId').text(`#${wallet.id}`);
      $('#userWalletInfoId').text(`#${wallet.id}`);
      $('#userWalletInfoAmount').text(`${balance.toFixed(2)} SAR`);
      if ($('#userWalletInfoWithdrawable').length) {
        $('#userWalletInfoWithdrawable').text(`${withdrawable.toFixed(2)} SAR`);
      }
      $('#userWalletInfoOwner').text(wallet.user_name || 'N/A');
      $('#userWalletInfoOwnerPhone').text(wallet.user_phone || 'N/A');
      $('#userWalletInfoOwnerEmail').text(wallet.user_email || 'N/A');

      // Set max amount label
      $('#userMaxAmount').text(`${balance.toFixed(2)} SAR`);
      $('#userRequestedAmount').removeAttr('max').data('balance', balance);

      // Set hidden wallet ID
      $('#userPaymentRequestWalletIdInput').val(wallet.id);

      // Store wallet data on modal
      $('#userPaymentRequestModal').data('walletData', {
        id: wallet.id,
        user_id: wallet.user_id,
        user_amount: balance,
        withdrawable_amount: withdrawable,
        user_name: wallet.user_name,
        user_phone: wallet.user_phone,
        user_email: wallet.user_email,
        user_bank_name: wallet.user_bank_name,
        user_account_number: wallet.user_account_number,
        user_iban_number: wallet.user_iban_number,
        admin_user_id: wallet.admin_user_id,
        admin_user_name: wallet.admin_user_name
      });

      // Reset form & errors
      $('#userPaymentRequestForm')[0].reset();
      $('.text-error').text('');

      // Pre-fill bank details if available
      if (wallet.user_bank_name) {
        if ($('#userBankName option[value="' + wallet.user_bank_name + '"]').length > 0) {
          $('#userBankName').val(wallet.user_bank_name);
          $('#userCustomBankName').hide();
        } else {
          $('#userBankName').val('other');
          $('#userCustomBankName').val(wallet.user_bank_name).show();
        }
      } else {
        $('#userBankName').val('');
        $('#userCustomBankName').val('').hide();
      }

      $('#userAccountNumber').val(wallet.user_account_number || '');
      const formattedIban = (wallet.user_iban_number || '').replace(/(.{4})/g, '$1 ').trim();
      $('#userIbanNumber').val(formattedIban);

      // Show modal
      $('#userPaymentRequestModal').modal('show');
    }).fail(function () {
      showAlert('error', 'Error loading user wallet details');
    });
  });

  // Format IBAN input
  $(document).on('input', '#userIbanNumber', function () {
    let value = $(this).val().replace(/\s/g, '').toUpperCase();
    if (value && !value.startsWith('SA')) {
      value = 'SA' + value.replace(/^SA/i, '');
    }
    let formatted = value.replace(/(.{4})/g, '$1 ').trim();
    $(this).val(formatted);
  });

  // Format Account Number input
  $(document).on('input', '#userAccountNumber', function () {
    let value = $(this).val().replace(/\D/g, '');
    $(this).val(value);
  });

  // Validate requested amount in real-time
  $(document).on('input', '#userRequestedAmount', function () {
    const value = parseFloat($(this).val());
    const balance = parseFloat($(this).data('balance')) || 0;

    if (value > balance) {
      $('.user_requested_amount-error')
        .text(`تنبيه: المبلغ أكبر من الرصيد المتاح (${balance.toFixed(2)} ريال). سيظهر الرصيد المتبقي بالسالب في طلب السداد.`)
        .removeClass('text-danger')
        .addClass('text-warning');
    } else {
      $('.user_requested_amount-error').text('').removeClass('text-warning').addClass('text-danger');
    }
  });

  // Handle payment method selection
  $(document).on('change', '#userPaymentMethod', function () {
    const selectedValue = $(this).val();
    const bankTransferFields = $('#userBankTransferFields');
    const otherPaymentField = $('#userOtherPaymentField');

    if (selectedValue === 'bank_transfer') {
      bankTransferFields.slideDown();
      otherPaymentField.slideUp();
      $('#userOtherPaymentMethod').removeAttr('required').val('');
    } else if (selectedValue === 'other') {
      bankTransferFields.slideUp();
      otherPaymentField.slideDown();
      $('#userOtherPaymentMethod').attr('required', true);
      $('#userBankName').val('');
      $('#userCustomBankName').val('').hide();
      $('#userAccountNumber').val('');
      $('#userIbanNumber').val('');
    } else {
      bankTransferFields.slideUp();
      otherPaymentField.slideUp();
      $('#userOtherPaymentMethod').removeAttr('required').val('');
    }
  });

  // Handle bank selection
  $(document).on('change', '#userBankName', function () {
    const selectedValue = $(this).val();
    if (selectedValue === 'other') {
      $('#userCustomBankName').slideDown().attr('required', true);
    } else {
      $('#userCustomBankName').slideUp().attr('required', false).val('');
    }
  });

  // Generate Payment Request Handler
  $(document).on('click', '#generateUserPaymentRequest', function () {
    const walletData = $('#userPaymentRequestModal').data('walletData');
    if (!walletData) {
      showAlert('error', 'Missing wallet data');
      return;
    }

    const requestedAmount = parseFloat($('#userRequestedAmount').val());
    const paymentMethod = $('#userPaymentMethod').val();
    let bankName = ($('#userBankName').val() || '').trim();
    const customBankName = ($('#userCustomBankName').val() || '').trim();
    const accountNumber = ($('#userAccountNumber').val() || '').trim();
    const ibanNumber = ($('#userIbanNumber').val() || '').trim();
    const otherPaymentMethod = ($('#userOtherPaymentMethod').val() || '').trim();
    const notes = ($('#userPaymentNotes').val() || '').trim();

    if (bankName === 'other') {
      bankName = customBankName;
    }

    // Clear previous errors
    $('.text-error').text('').removeClass('text-warning').addClass('text-danger');

    let hasErrors = false;

    if (!requestedAmount || requestedAmount <= 0) {
      $('.user_requested_amount-error').text('المبلغ المطلوب إلزامي ويجب أن يكون أكبر من صفر');
      hasErrors = true;
    }

    if (!paymentMethod) {
      $('.user_payment_method-error').text('يرجى اختيار طريقة الدفع');
      hasErrors = true;
    }

    if (paymentMethod === 'other') {
      if (!otherPaymentMethod) {
        $('.user_other_payment_method-error').text('يرجى إدخال تفاصيل طريقة الدفع');
        hasErrors = true;
      }
    } else if (paymentMethod === 'bank_transfer') {
      if (ibanNumber && !ibanNumber.replace(/\s/g, '').match(/^SA\d{22}$/)) {
        $('.user_iban_number-error').text('تنسيق رقم الآيبان غير صحيح (يجب أن يبدأ بـ SA ويتبعه 22 رقم)');
        hasErrors = true;
      }

      if (accountNumber && accountNumber.length < 8) {
        $('.user_account_number-error').text('رقم الحساب يجب أن يكون 8 أرقام على الأقل');
        hasErrors = true;
      }
    }

    if (hasErrors) {
      return;
    }

    // Generate printable payment request document
    generateUserPaymentRequestDocument({
      walletId: walletData.id,
      requestedAmount: requestedAmount,
      paymentMethod: paymentMethod,
      bankName: bankName,
      accountNumber: accountNumber,
      ibanNumber: ibanNumber,
      otherPaymentMethod: otherPaymentMethod,
      notes: notes,
      walletData: walletData
    });
  });

  // Function to generate and print payment request document for User Wallet
  function generateUserPaymentRequestDocument(data) {
    const today = new Date();
    const formattedDate = today.toLocaleDateString('ar-SA');
    const remainingAmount = data.walletData.user_amount - data.requestedAmount;
    const recipientName = data.walletData.user_name || 'غير محدد';
    const recipientPhone = data.walletData.user_phone || 'غير محدد';

    // Generate Reference Number: UW + WalletID + Date (YYYYMMDD) + Random 3 digits
    const dateString =
      today.getFullYear().toString() +
      (today.getMonth() + 1).toString().padStart(2, '0') +
      today.getDate().toString().padStart(2, '0');
    const randomNumber = Math.floor(Math.random() * 900) + 100;
    const referenceNumber = `UW${data.walletId}${dateString}${randomNumber}`;

    let amount = data.requestedAmount;
    let requestedAmountInWords = writtenNumber(amount, { lang: 'ar' }) + ' ريال سعودي';

    const printContent = `
  <!DOCTYPE html>
  <html dir="rtl" lang="ar">
  <head>
    <meta charset="UTF-8">
    <title>طلب سداد مالي - ${referenceNumber}</title>
    <style>
      body {
        font-family: 'Tajawal', Arial, sans-serif;
        margin: 0;
        padding: 20mm;
        font-size: 14px;
        color: #000;
        background: #fff;
      }

      .container {
        max-width: 210mm;
        margin: auto;
      }

      h1, h2, h3 {
        margin: 0 0 10px 0;
        font-weight: bold;
      }

      .title {
        text-align: center;
        margin-bottom: 20px;
      }

      .emp-name {
        font-size: 15px;
        margin-bottom: 15px;
      }

      table {
        width: 100%;
        margin-bottom: 15px;
        border-collapse: collapse;
      }

      td {
        border: 1px solid #000;
        padding: 8px;
        vertical-align: top;
      }

      .label {
        width: 30%;
        font-weight: bold;
        background: #f7f7f7;
      }

      .amount-box {
        padding: 12px;
        margin: 15px 0;
        font-weight: bold;
        font-size: 16px;
        background: #fdfdfd;
        border: 1px solid #ddd;
        border-radius: 4px;
      }

      .amount-details {
        font-size: 15px;
        margin: 15px 0;
      }

      .amount-details span {
        border: 1px solid #000;
        padding: 4px 8px;
        margin: 0 4px;
        border-radius: 4px;
        font-weight: bold;
      }

      .signatures {
        margin-top: 40px;
      }

      .signatures td {
        height: 70px;
        text-align: center;
      }

      .footer {
        margin-top: 25px;
        text-align: center;
        font-size: 12px;
        color: #555;
      }

      @media print {
        body { margin: 0; padding: 15mm; font-size: 12px; }
        .container { width: auto; }
      }
    </style>
  </head>
  <body>
    <div class="container">

      <!-- Header -->
      <div class="title">
        <h1>Safedests</h1>
        <h2>طلب سداد مالي (محفظة مستخدم)</h2>
        <p><strong>رقم الطلب:</strong> ${referenceNumber}</p>
        <p><strong>التاريخ:</strong> ${formattedDate}</p>
        <p style="color: #007bff; font-weight: bold;">
          طريقة السداد: ${data.paymentMethod === 'bank_transfer' ? 'تحويل بنكي' : data.paymentMethod === 'other' ? 'طريقة أخرى' : 'غير محدد'}
        </p>
      </div>

      <!-- Employee -->
      <p class="emp-name">
        اسم الموظف طالب السداد: <strong>${$('meta[name="user-name"]').attr('content') || data.walletData.admin_user_name || 'الإدارة'}</strong>
      </p>

      <h3>بيانات السداد</h3>
      <!-- Amount -->
      <div class="amount-box">
        مبلغ السداد: <strong>${data.requestedAmount.toFixed(2)} ريال</strong> (${requestedAmountInWords})
      </div>

      <div>
        <p class="amount-details">
          السداد:
          دفعة <span>${data.requestedAmount.toFixed(2)} ريال</span>
          باقي حساب <span>${remainingAmount.toFixed(2)} ريال</span>
          إجمالي الحساب <span>${data.walletData.user_amount.toFixed(2)} ريال</span>
        </p>
      </div>

      <!-- Payment Method Info -->
      ${
        data.paymentMethod === 'bank_transfer'
          ? `
      <h3>بيانات التحويل البنكي</h3>
      <table>
        <tr><td class="label">اسم البنك</td><td>${data.bankName || 'غير محدد'}</td></tr>
        <tr><td class="label">رقم الحساب</td><td>${data.accountNumber || 'غير محدد'}</td></tr>
        <tr><td class="label">رقم الآيبان (IBAN)</td><td>${(data.ibanNumber || '').replace(/\s+/g, '') || 'غير محدد'}</td></tr>
      </table>
      `
          : data.paymentMethod === 'other'
            ? `
      <h3>طريقة الدفع</h3>
      <div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background-color: #f9f9f9;">
        <p><strong>${data.otherPaymentMethod || 'غير محدد'}</strong></p>
      </div>
      `
            : `
      <h3>معلومات الدفع</h3>
      <p>لم يتم تحديد طريقة الدفع</p>
      `
      }

      <!-- User / Beneficiary Info -->
      <h3>بيانات المستفيد</h3>
      <table>
        <tr><td class="label">اسم المستفيد</td><td>${recipientName}</td></tr>
        <tr><td class="label">رقم الهاتف</td><td>${recipientPhone}</td></tr>
        <tr><td class="label">رقم المحفظة</td><td>#${data.walletData.id}</td></tr>
        <tr><td class="label">الرصيد المتبقي</td><td>${remainingAmount.toFixed(2)} ريال</td></tr>
      </table>

      ${
        data.notes
          ? `<h3>ملاحظات إضافية</h3>
      <div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background-color: #f9f9f9; white-space: pre-line;">
        <strong>${data.notes}</strong>
      </div>`
          : ''
      }

      <!-- Signatures -->
      <h3 style="margin-top: 30px;">الاعتمادات والتوقيع</h3>
      <table class="signatures">
        <tr>
          <td style="width: 33%;">توقيع طالب السداد<br><br>.........................</td>
          <td style="width: 33%;">المدير المالي<br><br>.........................</td>
          <td style="width: 33%;">اعتماد الإدارة<br><br>.........................</td>
        </tr>
      </table>

      <!-- Footer -->
      <div class="footer">
        <p>تم إنشاء هذا المستند إلكترونياً عبر منصة Safedests بتاريخ ${new Date().toLocaleDateString('ar-SA')}</p>
      </div>

    </div>
  </body>
  </html>
  `;

    // Open print window
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();

    printWindow.addEventListener('afterprint', function () {
      logUserPaymentRequest({
        walletId: data.walletData.id,
        amount: data.requestedAmount,
        paymentRequestNumber: referenceNumber,
        paymentMethod: data.paymentMethod,
        bankName: data.bankName,
        accountNumber: data.accountNumber,
        ibanNumber: data.ibanNumber,
        otherPaymentMethod: data.otherPaymentMethod,
        notes: data.notes || null
      });

      printWindow.close();
    });

    // Fallback if print is cancelled or blocked
    printWindow.onbeforeunload = function () {
      return null;
    };

    printWindow.print();

    setTimeout(() => {
      $('#userPaymentRequestModal').modal('hide');
      $('#userPaymentRequestForm')[0].reset();
    }, 1000);
  }

  // Function to log payment request in database
  function logUserPaymentRequest(data) {
    $.ajax({
      url: `${baseUrl}admin/user-wallets/${data.walletId}/log-payment-request`,
      method: 'POST',
      data: {
        amount: data.amount,
        payment_request_number: data.paymentRequestNumber,
        payment_method: data.paymentMethod,
        bank_name: data.bankName,
        account_number: data.accountNumber,
        iban_number: data.ibanNumber,
        other_payment_method: data.otherPaymentMethod,
        notes: data.notes,
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.status === 1) {
          if ($('#user-payment-logs-section').is(':visible')) {
            loadUserPaymentRequestLogs();
          }
        } else {
          console.error('Failed to log user payment request:', response.error);
        }
      },
      error: function (xhr, status, error) {
        console.error('Error logging user payment request:', error);
      }
    });
  }

  // Function to load payment request logs for User Wallet
  function loadUserPaymentRequestLogs() {
    if (typeof walletId === 'undefined' || !walletId) {
      return;
    }

    $.ajax({
      url: `${baseUrl}admin/user-wallets/${walletId}/payment-request-logs`,
      method: 'GET',
      success: function (response) {
        if (response.status === 1) {
          displayUserPaymentRequestLogs(response.logs);
        } else {
          console.error('Failed to load user payment logs:', response.error);
        }
      },
      error: function (xhr, status, error) {
        console.error('Error loading user payment logs:', error);
      }
    });
  }

  // Function to display payment request logs in UI
  function displayUserPaymentRequestLogs(logs) {
    const logsContainer = $('#user-payment-logs-container');

    if (!logs || !logs.data || logs.data.length === 0) {
      logsContainer.html(`
        <div class="text-center py-4">
          <i class="ti ti-file-x fs-1 text-muted"></i>
          <p class="text-muted mt-2">لا توجد سجلات طلبات سداد سابقة لهذه المحفظة</p>
        </div>
      `);
      return;
    }

    let logsHtml = '';
    logs.data.forEach(log => {
      logsHtml += `
        <div class="card mb-3 border shadow-none">
          <div class="card-body py-3">
            <div class="row align-items-center">
              <div class="col-md-2">
                <small class="text-muted d-block">تاريخ الطباعة</small>
                <div class="fw-semibold">${moment(log.printed_at).format('DD-MM-YYYY HH:mm')}</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted d-block">رقم الطلب</small>
                <div class="fw-semibold text-primary font-monospace">${log.payment_request_number || 'غير محدد'}</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted d-block">المبلغ</small>
                <div class="fw-bold text-success">${parseFloat(log.amount).toFixed(2)} SAR</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted d-block">الموظف المنفذ</small>
                <div class="fw-semibold">${log.printed_by ? log.printed_by.name : (log.user ? log.user.name : 'الإدارة')}</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted d-block">عنوان IP</small>
                <div class="fw-semibold font-monospace small">${log.ip_address || '-'}</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted d-block">الملاحظات</small>
                <div class="fw-normal small text-truncate" title="${log.notes || ''}">${log.notes || '-'}</div>
              </div>
            </div>
          </div>
        </div>
      `;
    });

    logsContainer.html(logsHtml);
  }

  // Load logs on page load if section is present
  if ($('#user-payment-logs-section').length) {
    loadUserPaymentRequestLogs();
  }

  // Refresh logs button handler
  $(document).on('click', '#loadUserPaymentRefresh', function () {
    loadUserPaymentRequestLogs();
  });
});


