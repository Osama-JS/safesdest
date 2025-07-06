/**
 * Team Wallet Management Page
 */

'use strict';
import {
  initDashboard,
  showAlert,
  setButtonLoading,
  handleAjaxError,
  displayFormErrors,
  showConfirmation
} from './common.js';

$(function () {
  // Initialize common dashboard functionality
  initDashboard();
  var dt_transaction_table = $('.datatables-transactions');
  var selectedTransactions = [];

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize date range picker
  if ($('#date-range').length) {
    $('#date-range').flatpickr({
      mode: 'range',
      dateFormat: 'Y-m-d',
      placeholder: 'Select date range'
    });
  }

  // // Transactions datatable
  // if (dt_transaction_table.length) {
  //   var dt_trans = dt_transaction_table.DataTable({
  //     processing: true,
  //     serverSide: true,
  //     ajax: {
  //       url: baseUrl + 'admin/teams/wallet/transactions/data',
  //       data: function (d) {
  //         d.wallet = walletId;
  //         d.status = $('#type-filter').val();
  //         d.from_date = $('#date-range').val() ? $('#date-range').val().split(' to ')[0] : '';
  //         d.to_date = $('#date-range').val() ? $('#date-range').val().split(' to ')[1] : '';
  //         d.min_amount = $('#min-amount').val();
  //         d.max_amount = $('#max-amount').val();
  //       }
  //     },
  //     columns: [
  //       { data: 'checkbox' },
  //       { data: '' },
  //       { data: 'sequence' },
  //       { data: 'amount' },
  //       { data: 'transaction_type' },
  //       { data: 'description' },
  //       { data: 'maturity_time' },
  //       { data: 'task' },
  //       { data: 'user' },
  //       { data: 'created_at' },
  //       { data: null }
  //     ],
  //     columnDefs: [
  //       {
  //         targets: 0,
  //         searchable: false,
  //         orderable: false,
  //         render: function (data, type, full, meta) {
  //           if (full.transaction_type === 'debit' && !full.task_id) {
  //             return `<input type="checkbox" class="form-check-input transaction-checkbox" value="${full.id}" data-amount="${full.amount}">`;
  //           }
  //           return '';
  //         }
  //       },
  //       {
  //         targets: 1,
  //         className: 'control',
  //         searchable: false,
  //         orderable: false,
  //         responsivePriority: 1,
  //         render: function () {
  //           return '';
  //         }
  //       },
  //       {
  //         targets: 2,
  //         render: function (data, type, full, meta) {
  //           return `<span class="fw-bold">#${full.sequence}</span>`;
  //         }
  //       },
  //       {
  //         targets: 3,
  //         render: function (data, type, full, meta) {
  //           const isCredit = full.transaction_type === 'credit';
  //           const colorClass = isCredit ? 'success' : 'danger';
  //           const icon = isCredit ? 'ti-arrow-up-right' : 'ti-arrow-down-left';

  //           return `<span class="badge bg-label-${colorClass} fs-6">
  //                     <i class="ti ${icon} me-1"></i>${full.amount} SAR
  //                   </span>`;
  //         }
  //       },
  //       {
  //         targets: 4,
  //         className: 'text-center',
  //         render: function (data, type, full, meta) {
  //           const isCredit = full.transaction_type === 'credit';
  //           const colorClass = isCredit ? 'success' : 'danger';
  //           const icon = isCredit ? 'ti-plus' : 'ti-minus';

  //           return `<span class="badge bg-label-${colorClass}">
  //                     <i class="ti ${icon} me-1"></i>${full.transaction_type}
  //                   </span>`;
  //         }
  //       },
  //       {
  //         targets: 5,
  //         render: function (data, type, full, meta) {
  //           let imageBtn = '';
  //           if (full.image) {
  //             imageBtn = `
  //               <button class="btn btn-sm btn-icon show-image ms-2" data-bs-toggle="modal"
  //                       data-bs-target="#imageModal" data-image="${baseUrl + full.image}" title="View Attachment">
  //                 <i class="ti ti-photo"></i>
  //               </button>
  //             `;
  //           }

  //           return `<span class="text-truncate" style="max-width: 200px;" title="${full.description}">
  //                     ${full.description}
  //                   </span>${imageBtn}`;
  //         }
  //       },
  //       {
  //         targets: 6,
  //         render: function (data, type, full, meta) {
  //           return full.maturity_time ? `<span class="text-muted">${full.maturity_time}</span>` : '-';
  //         }
  //       },
  //       {
  //         targets: 7,
  //         render: function (data, type, full, meta) {
  //           if (full.task) {
  //             return `<a href="${baseUrl}admin/tasks/show/${full.task.id}" class="text-primary">
  //                       #${full.task.id}
  //                     </a>`;
  //           }
  //           return '-';
  //         }
  //       },
  //       {
  //         targets: 8,
  //         render: function (data, type, full, meta) {
  //           return full.user ? full.user.name : '-';
  //         }
  //       },
  //       {
  //         targets: 9,
  //         render: function (data, type, full, meta) {
  //           return full.created_at;
  //         }
  //       },
  //       {
  //         targets: 10,
  //         searchable: false,
  //         orderable: false,
  //         render: function (data, type, full, meta) {
  //           let actions = `
  //             <div class="d-flex align-items-center gap-2">
  //               <button class="btn btn-sm btn-icon view-transaction" data-id="${full.id}" title="View Details">
  //                 <i class="ti ti-eye"></i>
  //               </button>
  //           `;

  //           // Only allow editing if not linked to a task
  //           if (!full.task_id) {
  //             actions += `
  //               <button class="btn btn-sm btn-icon edit-transaction" data-id="${full.id}"
  //                       data-bs-toggle="modal" data-bs-target="#addTransactionModal" title="Edit">
  //                 <i class="ti ti-edit"></i>
  //               </button>
  //               <button class="btn btn-sm btn-icon delete-transaction" data-id="${full.id}"
  //                       data-description="${full.description}" title="Delete">
  //                 <i class="ti ti-trash"></i>
  //               </button>
  //             `;
  //           }

  //           actions += '</div>';
  //           return actions;
  //         }
  //       }
  //     ],
  //     dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
  //     displayLength: 10,
  //     lengthMenu: [10, 25, 50, 75, 100],
  //     responsive: {
  //       details: {
  //         display: $.fn.dataTable.Responsive.display.modal({
  //           header: function (row) {
  //             var data = row.data();
  //             return 'Transaction #' + data.sequence;
  //           }
  //         }),
  //         type: 'column'
  //       }
  //     }
  //   });

  //   // Filter event handlers
  //   $('#type-filter, #date-range, #min-amount, #max-amount').on('change input', function () {
  //     dt_trans.draw();
  //   });

  //   // Reset filters
  //   $('#reset-filters').on('click', function () {
  //     $('#type-filter').val('');
  //     $('#date-range, #min-amount, #max-amount').val('');
  //     dt_trans.draw();
  //   });

  //   // Refresh table
  //   $('#refresh-table').on('click', function () {
  //     dt_trans.draw();
  //   });

  //   document.dispatchEvent(new CustomEvent('dtTransactionReady', { detail: dt_trans }));
  // }

  var start_from = moment().startOf('month').format('YYYY-MM-DD');
  var end_to = moment().endOf('month').format('YYYY-MM-DD');

  if (dt_transaction_table.length) {
    var dt_trans = dt_transaction_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/teams/wallet/transactions/data',
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
        { data: 'user' },
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
            return `<span>${full.sequence}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            const colorClass = full.type === 'credit' ? 'text-success' : 'text-danger';
            const icon = full.type === 'credit' ? 'ti-arrow-up-right' : 'ti-arrow-down-left';
            return `<span class="${colorClass}"><i class="ti ${icon} me-1"></i>${parseFloat(data).toFixed(2)} SAR</span>`;
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
            return `<span>${full.maturity || '-'}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return `<span>${full.task || '-'}</span>`;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            return `<span>${full.user}</span>`;
          }
        },

        {
          targets: 7,
          render: function (data, type, full, meta) {
            return `<span>${full.created_at}</span>`;
          }
        }
      ],
      createdRow: function (row, data, dataIndex) {
        if (data.task !== '') {
          $(row).addClass('table-success');
        }
      },
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
      dt_trans.draw();
    });

    $('#searchFilter').on('input', function () {
      dt_trans.draw();
    });

    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_trans }));
  }

  $('.dataTables_filter').hide();

  // Transaction type radio button handlers
  $('input[name="type"]').on('change', function () {
    if ($(this).val() === 'debit') {
      $('#maturity-time-group').show();
    } else {
      $('#maturity-time-group').hide();
      $('#trans_maturity').val('');
    }
  });

  // Select all transactions checkbox
  $('#select-all-transactions').on('change', function () {
    const isChecked = $(this).is(':checked');
    $('.transaction-checkbox').prop('checked', isChecked);
    updateSelectedTransactions();
  });

  // Individual transaction checkbox
  $(document).on('change', '.transaction-checkbox', function () {
    updateSelectedTransactions();
  });

  // Clear selection
  $('#clear-selection').on('click', function () {
    $('.transaction-checkbox').prop('checked', false);
    $('#select-all-transactions').prop('checked', false);
    updateSelectedTransactions();
  });

  // Show image modal
  $(document).on('click', '.show-image', function () {
    const imageUrl = $(this).data('image');
    $('#modalImage').attr('src', imageUrl);
  });

  // Edit transaction
  $(document).on('click', '.edit-transaction', function () {
    const transactionId = $(this).data('id');
    loadTransactionData(transactionId);
  });

  // Delete transaction
  $(document).on('click', '.delete-transaction', function () {
    const transactionId = $(this).data('id');
    const description = $(this).data('description');

    showConfirmation('Are you sure?', `Delete transaction: ${description}`, 'Yes, delete it!', 'Cancel').then(
      confirmed => {
        if (confirmed) {
          deleteTransaction(transactionId);
        }
      }
    );
  });

  // Form submission
  $('.form_submit').on('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = $('.data-submit');

    setButtonLoading(submitBtn, true);

    $.ajax({
      url: $(this).attr('action'),
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 1) {
          showAlert('success', response.success || 'Transaction saved successfully');
          $('#addTransactionModal').modal('hide');
          dt_trans.draw();
          resetForm();
        } else {
          if (response.error && typeof response.error === 'object') {
            displayFormErrors(response.error);
          } else {
            showAlert('error', response.error || 'An error occurred');
          }
        }
      },
      error: function (xhr, textStatus, errorThrown) {
        handleAjaxError(xhr, textStatus, errorThrown);
      },
      complete: function () {
        setButtonLoading(submitBtn, false);
      }
    });
  });

  // Export transactions
  $('#export-transactions').on('click', function () {
    showAlert('info', 'Export functionality will be implemented soon');
  });

  // Process payment
  $('#process-payment').on('click', function () {
    if (selectedTransactions.length === 0) {
      showAlert('warning', 'Please select transactions to process payment');
      return;
    }

    loadPaymentForm();
  });

  // Image input handler
  $('.image-input').on('click', function () {
    $('.file-pickup-image').click();
  });

  $('.file-pickup-image').on('change', function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        $('.preview-pickup-image').attr('src', e.target.result);
      };
      reader.readAsDataURL(file);
    }
  });
});

/**
 * Update selected transactions
 */
function updateSelectedTransactions() {
  selectedTransactions = [];
  let totalAmount = 0;

  $('.transaction-checkbox:checked').each(function () {
    selectedTransactions.push($(this).val());
    totalAmount += parseFloat($(this).data('amount'));
  });

  $('#selected-count').text(selectedTransactions.length);
  $('#selected-total').text(totalAmount.toFixed(2) + ' SAR');

  if (selectedTransactions.length > 0) {
    $('#payment-controls').show();
  } else {
    $('#payment-controls').hide();
  }
}

/**
 * Load transaction data for editing
 */
function loadTransactionData(transactionId) {
  $.get(baseUrl + 'admin/teams/wallet/transaction/edit/' + transactionId, function (response) {
    if (response.status === 1) {
      const data = response.data;
      $('#trans_id').val(data.id);
      $('#trans_amount').val(data.amount);
      $('#trans_description').val(data.description);
      $(`input[name="type"][value="${data.transaction_type}"]`).prop('checked', true).trigger('change');

      if (data.maturity_time) {
        $('#trans_maturity').val(data.maturity_time);
      }

      if (data.image) {
        $('.preview-pickup-image').attr('src', baseUrl + data.image);
      }

      $('#transactionModalTitle').text('Edit Transaction');
    }
  });
}

/**
 * Delete transaction
 */
function deleteTransaction(transactionId) {
  $.ajax({
    url: baseUrl + 'admin/teams/wallet/transaction/delete/' + transactionId,
    type: 'DELETE',
    success: function (response) {
      if (response.status === 1) {
        showAlert('success', 'Transaction deleted successfully');
        dt_trans.draw();
      } else {
        showAlert('error', response.error || 'Failed to delete transaction');
      }
    },
    error: function () {
      showAlert('error', 'An error occurred while deleting');
    }
  });
}

/**
 * Reset form
 */
function resetForm() {
  $('.form_submit')[0].reset();
  $('.text-error').text('');
  $('#trans_id').val('');
  $('.preview-pickup-image').attr('src', baseUrl + 'assets/img/placeholder.jpg');
  $('#transactionModalTitle').text('Add New Transaction');
  $('#maturity-time-group').hide();
}

/**
 * Load payment form
 */
function loadPaymentForm() {
  $('#payment-form-content').html(`
    <div class="row mb-4">
      <div class="col-md-6">
        <label for="totalPaymentAmount" class="form-label fw-semibold">
          <i class="ti ti-calculator me-1"></i>Total Payment Amount
        </label>
        <div class="input-group">
          <input type="number" class="form-control" id="totalPaymentAmount"
                 name="total_amount" step="0.01" min="0" required>
          <span class="input-group-text">SAR</span>
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label fw-semibold">Payment Summary</label>
        <div class="card">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between">
              <span>Selected Transactions:</span>
              <span class="fw-bold">${selectedTransactions.length}</span>
            </div>
            <div class="d-flex justify-content-between">
              <span>Total Amount:</span>
              <span class="fw-bold">${$('#selected-total').text()}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="mb-3">
      <label for="paymentNotes" class="form-label">
        <i class="ti ti-note me-1"></i>Payment Notes
      </label>
      <textarea class="form-control" id="paymentNotes" name="notes" rows="3"
                placeholder="Add any notes about this payment..."></textarea>
    </div>
  `);
}

/**
 * Initialize wallet statistics cards with animations
 */
function initWalletStatsCards() {
  // Add hover effects to wallet stats cards
  $('.wallet-stats-card')
    .on('mouseenter', function () {
      $(this).find('.avatar-initial').addClass('animate__animated animate__pulse');
    })
    .on('mouseleave', function () {
      $(this).find('.avatar-initial').removeClass('animate__animated animate__pulse');
    });

  // Initialize tooltips for wallet stats
  $('[data-bs-toggle="tooltip"]').tooltip();

  // Add counter animation for numbers
  animateWalletCounters();
}

/**
 * Animate counter numbers for wallet stats
 */
function animateWalletCounters() {
  $('.wallet-stats-card h3').each(function () {
    const $this = $(this);
    const countTo = parseFloat($this.text().replace(/[^0-9.-]/g, ''));

    if (!isNaN(countTo)) {
      $({ countNum: 0 }).animate(
        {
          countNum: countTo
        },
        {
          duration: 2000,
          easing: 'swing',
          step: function () {
            $this.text(formatWalletNumber(Math.floor(this.countNum)));
          },
          complete: function () {
            $this.text(formatWalletNumber(countTo));
          }
        }
      );
    }
  });
}

/**
 * Format number with commas for wallet display
 */
function formatWalletNumber(num) {
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Initialize wallet stats when document is ready
$(document).ready(function () {
  initWalletStatsCards();
});
