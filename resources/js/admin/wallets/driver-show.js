import { deleteRecord, showAlert } from '../../ajax.js';

$(document).ready(function () {
  'use strict';

  let dt_trans;
  let selectedTransactions = [];
  let originalTotal = 0;

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize DataTable
  if ($('.datatables-transactions').length) {
    dt_trans = $('.datatables-transactions').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: `${baseUrl}admin/wallets/transactions/${walletId}`,
        type: 'GET',
        error: function (xhr, error, thrown) {
          console.error('DataTable AJAX Error:', error, thrown);
          console.error('Response:', xhr.responseText);
        },
        dataSrc: function (json) {
          console.log('DataTable Response:', json);
          if (json.data) {
            console.log('First 3 rows:', json.data.slice(0, 3));
          }
          return json.data || [];
        }
      },
      columns: [
        {
          data: null,
          orderable: false,
          searchable: false,
          render: function (data, type, row) {
            console.log('Row data:', row); // Debug log
            // Show checkbox for credit transactions with status 0 (unpaid to driver)
            if (row.type === 'credit' && row.status == 0) {
              return `<input type="checkbox" class="form-check-input transaction-checkbox" value="${row.id}" data-amount="${row.amount}" data-description="${row.description}">`;
            }
            return '';
          }
        },

        {
          data: 'sequence',
          render: function (data, type, row) {
            return data || '';
          }
        },
        {
          data: 'amount',
          render: function (data, type, row) {
            const colorClass = row.type === 'credit' ? 'text-success' : 'text-danger';
            const icon = row.type === 'credit' ? 'ti-arrow-up-right' : 'ti-arrow-down-left';
            return `<span class="${colorClass}"><i class="ti ${icon} me-1"></i>${parseFloat(data).toFixed(2)} SAR</span>`;
          }
        },
        {
          data: 'description',
          render: function (data, type, row) {
            return data || '';
          }
        },
        {
          data: 'maturity',
          render: function (data, type, row) {
            return data || '';
          }
        },
        {
          data: 'task',
          render: function (data, type, row) {
            return data || '';
          }
        },
        {
          data: null,
          render: function (data, type, row) {
            console.log('Status render - type:', row.type, 'status:', row.status); // Debug log
            if (row.type === 'credit') {
              if (row.status == 1) {
                return '<span class="badge bg-success">paid</span>';
              } else {
                return '<span class="badge bg-danger">not paid</span>';
              }
            } else if (row.type === 'debit') {
              return '<span class="badge bg-info">pay</span>';
            }
            return '<span class="badge bg-secondary">undefined</span>';
          }
        },
        {
          data: 'user',
          render: function (data, type, row) {
            return data || 'automatic';
          }
        },
        {
          data: 'created_at',
          render: function (data, type, row) {
            return data || '';
          }
        },
        {
          data: 'created_at',
          render: function (data, type, row) {
            return `
              <div class="text-end">
                ${
                  row.task !==
                  //''     ? `
                  //     <button class="btn btn-sm btn-icon edit-record " data-id="${row.id}"  >
                  //   <i class="ti ti-edit"></i>
                  // </button>
                  // `
                  // :
                  `<button class="btn btn-sm btn-icon edit-record " data-id="${row.id}"  >
                  <i class="ti ti-edit"></i>
                </button>
                <button class="btn btn-sm btn-icon delete-record " data-id="${row.id}"  data-name="${row.sequence}">
                  <i class="ti ti-trash"></i>
                </button>`
                }

              </div>`;
          }
        }
      ],
      order: [[1, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: {
        paginate: {
          previous: '&nbsp;',
          next: '&nbsp;'
        }
      },
      drawCallback: function (settings) {
        console.log('DataTable draw completed');
        console.log('Checkboxes found:', $('.transaction-checkbox').length);

        // Update select all checkbox state after each draw
        updateSelectAllCheckbox();
      }
    });
  }

  let dt_payouts;
  if ($('.datatables-payouts').length) {
    dt_payouts = $('.datatables-payouts').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: `${baseUrl}admin/wallets/payouts/${walletId}`,
        type: 'GET'
      },
      columns: [
        { data: 'fake_id' },
        { data: 'reference_id' },
        { 
          data: 'amount',
          render: function(data) { return parseFloat(data).toFixed(2) + ' SAR'; }
        },
        { 
          data: 'status',
          render: function(data) {
            let badgeClass = 'bg-secondary';
            if(data === 'pending') badgeClass = 'bg-warning';
            else if(data === 'completed') badgeClass = 'bg-success';
            else if(data === 'failed') badgeClass = 'bg-danger';
            return `<span class="badge ${badgeClass}">${data}</span>`;
          }
        },
        { data: 'type' },
        { data: 'payout_id' },
        { data: 'created_at' }
      ],
      order: [[6, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: {
        paginate: {
          previous: '&nbsp;',
          next: '&nbsp;'
        }
      }
    });
  }

  // Handle individual checkbox change
  $(document).on('change', '.transaction-checkbox', function () {
    const transactionId = parseInt($(this).val());
    const amount = parseFloat($(this).data('amount'));
    const description = $(this).data('description');

    if ($(this).is(':checked')) {
      // Add to selected transactions
      selectedTransactions.push({
        id: transactionId,
        amount: amount,
        description: description,
        driver: walletOwnerName || 'Driver' // We'll get this from the page
      });
    } else {
      // Remove from selected transactions
      selectedTransactions = selectedTransactions.filter(trans => trans.id !== transactionId);
    }

    updateSelectedDisplay();
    updateSelectAllCheckbox();
  });

  // Handle select all checkbox
  $('#select-all-transactions').on('change', function () {
    const isChecked = $(this).is(':checked');

    $('.transaction-checkbox').each(function () {
      const $checkbox = $(this);
      const transactionId = parseInt($checkbox.val());
      const amount = parseFloat($checkbox.data('amount'));
      const description = $checkbox.data('description');

      if (isChecked && !$checkbox.is(':checked')) {
        $checkbox.prop('checked', true);
        selectedTransactions.push({
          id: transactionId,
          amount: amount,
          description: description,
          driver: walletOwnerName || 'Driver'
        });
      } else if (!isChecked && $checkbox.is(':checked')) {
        $checkbox.prop('checked', false);
        selectedTransactions = selectedTransactions.filter(trans => trans.id !== transactionId);
      }
    });

    updateSelectedDisplay();
  });

  // Update selected display
  function updateSelectedDisplay() {
    const count = selectedTransactions.length;
    const total = selectedTransactions.reduce((sum, trans) => sum + trans.amount, 0);

    $('#selected-count').text(count);
    $('#selected-total').text(total.toFixed(2) + ' SAR');

    // Show/hide payment controls
    if (count > 0) {
      $('#payment-controls').slideDown();
      originalTotal = total;
    } else {
      $('#payment-controls').slideUp();
      originalTotal = 0;
    }
  }

  // Update select all checkbox state
  function updateSelectAllCheckbox() {
    const totalCheckboxes = $('.transaction-checkbox').length;
    const checkedCheckboxes = $('.transaction-checkbox:checked').length;

    if (checkedCheckboxes === 0) {
      $('#select-all-transactions').prop('checked', false).prop('indeterminate', false);
    } else if (checkedCheckboxes === totalCheckboxes) {
      $('#select-all-transactions').prop('checked', true).prop('indeterminate', false);
    } else {
      $('#select-all-transactions').prop('checked', false).prop('indeterminate', true);
    }
  }

  // Clear selection
  $('#clear-selection').on('click', function () {
    $('.transaction-checkbox').prop('checked', false);
    $('#select-all-transactions').prop('checked', false).prop('indeterminate', false);
    selectedTransactions = [];
    updateSelectedDisplay();
  });

  // Handle payment modal opening
  $('#process-payment').on('click', function () {
    if (selectedTransactions.length === 0) {
      showAlert('warning', 'يرجى تحديد معاملات للدفع', 5000);
      return;
    }

    // Update modal summary
    $('#modal-selected-count').text(selectedTransactions.length);
    $('#modal-original-total').text(originalTotal.toFixed(2) + ' SAR');
    $('#maxAmountDisplay').text(originalTotal.toFixed(2) + ' SAR');
    $('#totalPaymentAmount').val(originalTotal.toFixed(2)).attr('max', originalTotal.toFixed(2));
    $('#modal-payment-total').text(originalTotal.toFixed(2) + ' SAR');

    // Populate selected transactions table
    updateSelectedTransactionsTable();
  });

  // Update selected transactions table in modal
  function updateSelectedTransactionsTable() {
    const tbody = $('#selectedTransactionsBody');
    tbody.empty();

    selectedTransactions.forEach(trans => {
      tbody.append(`
        <tr>
          <td>${trans.id}</td>
          <td>${trans.driver}</td>
          <td>${trans.description}</td>
          <td>${trans.amount.toFixed(2)} SAR</td>
          <td class="payment-amount">${trans.amount.toFixed(2)} SAR</td>
          <td>
            <button type="button" class="btn btn-sm btn-outline-danger remove-transaction"
                    data-transaction-id="${trans.id}">
              <i class="ti ti-x"></i>
            </button>
          </td>
        </tr>
      `);
    });
  }

  // Handle payment amount change
  $('#totalPaymentAmount').on('input', function () {
    const newTotal = parseFloat($(this).val()) || 0;
    const maxAmount = originalTotal;

    // Validate amount doesn't exceed maximum
    if (newTotal > maxAmount) {
      $(this).val(maxAmount.toFixed(2));
      showAlert('error', `المبلغ لا يمكن أن يتجاوز ${maxAmount.toFixed(2)} SAR`, 5000);
      return;
    }

    // Update payment total display
    $('#modal-payment-total').text(newTotal.toFixed(2) + ' SAR');

    // Distribute amount sequentially
    distributePaymentAmountSequential(newTotal);
  });

  // Handle "Use Maximum" button
  $('#useMaxAmount').on('click', function () {
    const maxAmount = originalTotal;
    $('#totalPaymentAmount').val(maxAmount.toFixed(2));
    $('#modal-payment-total').text(maxAmount.toFixed(2) + ' SAR');
    distributePaymentAmountSequential(maxAmount);
  });

  // Sequential distribution function
  function distributePaymentAmountSequential(totalAmount) {
    let remainingAmount = totalAmount;

    selectedTransactions.forEach((trans, index) => {
      const row = $(`#selectedTransactionsBody tr:eq(${index})`);
      const paymentCell = row.find('.payment-amount');

      if (remainingAmount >= trans.amount) {
        // Full payment
        paymentCell
          .text(trans.amount.toFixed(2) + ' SAR')
          .removeClass('text-warning')
          .addClass('text-success');
        remainingAmount -= trans.amount;
      } else if (remainingAmount > 0) {
        // Partial payment
        paymentCell
          .text(remainingAmount.toFixed(2) + ' SAR')
          .removeClass('text-success')
          .addClass('text-warning');
        remainingAmount = 0;
      } else {
        // No payment
        paymentCell.text('0.00 SAR').removeClass('text-success text-warning');
      }
    });
  }

  // Remove transaction from modal
  $(document).on('click', '.remove-transaction', function () {
    const transactionId = $(this).data('transaction-id');

    console.log('Removing transaction:', transactionId);

    // Remove from selectedTransactions array
    const originalLength = selectedTransactions.length;
    selectedTransactions = selectedTransactions.filter(trans => trans.id != transactionId);

    console.log('Transactions before removal:', originalLength, 'after removal:', selectedTransactions.length);

    // Uncheck the checkbox in main table
    $(`.transaction-checkbox[value="${transactionId}"]`).prop('checked', false);

    // Update displays
    updateSelectedDisplay();
    updateSelectAllCheckbox();

    // Recalculate total and update modal
    const newTotal = selectedTransactions.reduce((sum, trans) => sum + trans.amount, 0);
    originalTotal = newTotal;

    $('#totalPaymentAmount').val(newTotal.toFixed(2)).attr('max', newTotal.toFixed(2));
    $('#modal-original-total').text(newTotal.toFixed(2) + ' SAR');
    $('#maxAmountDisplay').text(newTotal.toFixed(2) + ' SAR');
    $('#modal-payment-total').text(newTotal.toFixed(2) + ' SAR');
    $('#modal-selected-count').text(selectedTransactions.length);

    // Update selected transactions table
    updateSelectedTransactionsTable();

    // Recalculate distribution with new total
    distributePaymentAmountSequential(newTotal);

    // If no transactions left, close modal
    if (selectedTransactions.length === 0) {
      showAlert('info', 'تم إزالة جميع المعاملات. سيتم إغلاق نافذة الدفع.', 3000);
      setTimeout(() => {
        $('#paymentModal').modal('hide');
      }, 1500);
    } else {
      showAlert('success', `تم إزالة المعاملة بنجاح. المتبقي: ${selectedTransactions.length} معاملة`, 3000);
    }
  });

  // Handle payment confirmation
  $('#confirmPayment').on('click', function () {
    const totalAmount = parseFloat($('#totalPaymentAmount').val());
    const notes = $('#paymentNotes').val();

    if (!totalAmount || totalAmount <= 0) {
      showAlert('error', 'يرجى إدخال مبلغ دفع صحيح', 5000);
      return;
    }

    if (selectedTransactions.length === 0) {
      showAlert('error', 'لا توجد معاملات محددة للدفع', 5000);
      return;
    }

    // Prepare transaction data with sequential distribution
    const transactionData = [];
    let remainingAmount = totalAmount;

    selectedTransactions.forEach(trans => {
      if (remainingAmount > 0) {
        const paymentAmount = Math.min(remainingAmount, trans.amount);
        transactionData.push({
          id: trans.id,
          payment_amount: paymentAmount
        });
        remainingAmount -= paymentAmount;
      }
    });

    // Show loading state
    $('#confirmPayment').prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>معالجة...');

    // Send payment request
    $.ajax({
      url: `${baseUrl}admin/wallets/driver/payment`,
      type: 'POST',
      data: {
        wallet_id: walletId,
        total_amount: totalAmount,
        transactions: transactionData,
        notes: notes,
        payment_method: $('#paymentMethod').val(),
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          // Show success message
          showAlert('success', 'تم معالجة الدفع بنجاح!', 5000);

          // Close modal
          $('#paymentModal').modal('hide');

          // Clear selections
          selectedTransactions = [];
          $('.transaction-checkbox').prop('checked', false);
          $('#select-all-transactions').prop('checked', false).prop('indeterminate', false);
          updateSelectedDisplay();

          // Reload transactions table
          if (dt_trans) {
            dt_trans.ajax.reload();
          }

          // Reset form
          $('#paymentForm')[0].reset();
        } else {
          showAlert('error', 'خطأ: ' + (response.message || 'فشل في معالجة الدفع'), 7000);
        }
      },
      error: function (xhr) {
        let errorMessage = 'فشل في معالجة الدفع';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
          errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
        }
        showAlert('error', 'خطأ: ' + errorMessage, 7000);
      },
      complete: function () {
        // Reset button state
        $('#confirmPayment').prop('disabled', false).html('<i class="ti ti-check me-1"></i>تأكيد الدفع');
      }
    });
  });

  // Toggle bank details display
  $(document).on('change', '#paymentMethod', function () {
    if ($(this).val() === 'hyperpay') {
      $('#hyperpay-bank-details').slideDown();
    } else {
      $('#hyperpay-bank-details').slideUp();
    }
  });

  // Reset on modal hide
  $('#paymentModal').on('hidden.bs.modal', function () {
    $('#hyperpay-bank-details').hide();
  });

  // Get wallet owner name from page
  const walletOwnerName = $('h5.card-title .text-dark').text().trim();

  document.addEventListener('deletedSuccess', function (event) {
    if (dt_trans) {
      dt_trans.draw();
    }
  });
  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/wallets/transaction/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });
});
