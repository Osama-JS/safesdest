/**
 * Investor Wallet Management
 */

'use strict';

$(function () {
  var dt_transaction_table = $('#investorTransactionsTable'),
    transactionForm = $('#transactionForm');

  // Transactions datatable
  if (dt_transaction_table.length) {
    var dt_transaction = dt_transaction_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: transactionsDataUrl
      },
      columns: [
        { data: 'id' },
        { data: 'amount' },
        { data: 'transaction_type' },
        { data: 'source_type' },
        { data: 'description' },
        { data: 'task_id' },
        { data: 'created_at' },
        { data: 'id' }
      ],
      columnDefs: [
        {
          // For Responsive
          targets: 0,
          render: function (data, type, full, meta) {
            return meta.row + meta.settings._iDisplayStart + 1;
          }
        },
        {
          // Amount
          targets: 1,
          render: function (data, type, full, meta) {
            var $amount = data;
            var $type = full['transaction_type'];
            var $color = $type === 'credit' ? 'text-success' : 'text-danger';
            var $prefix = $type === 'credit' ? '+' : '-';
            return '<span class="fw-bold ' + $color + '">' + $prefix + $amount + ' ' + __('SAR') + '</span>';
          }
        },
        {
          // Type
          targets: 2,
          render: function (data, type, full, meta) {
            var $type = data;
            var $sourceType = full['source_type'];
            if ($type === 'credit') {
              if ($sourceType === 'refund') {
                return '<span class="badge bg-label-info">' + __('Capital return') + '</span>';
              } else {
                return '<span class="badge bg-label-success">' + __('Capital deposit') + '</span>';
              }
            } else {
              return '<span class="badge bg-label-danger">' + __('Task funding') + '</span>';
            }
          }
        },
        {
          // Source Type
          targets: 3,
          render: function (data, type, full, meta) {
            var val = data || '-';
            return '<span class="text-muted">' + val + '</span>';
          }
        },
        {
          // Description
          targets: 4,
          render: function (data, type, full, meta) {
            var attachmentLink = '';
            if (full['attachment']) {
              var attachmentUrl = baseUrl + 'storage/' + full['attachment'];
              attachmentLink =
                '<div class="mt-1"><a href="javascript:;" class="text-primary fw-bold show-attachment" data-file="' +
                attachmentUrl +
                '"><i class="ti ti-link ti-xs me-1"></i>' +
                __('View attachment') +
                '</a></div>';
            }
            return (
              '<div class="text-wrap" style="min-width: 200px;">' +
              '<div>' +
              data +
              '</div>' +
              attachmentLink +
              '</div>'
            );
          }
        },
        {
          // Task ID
          targets: 5,
          render: function (data, type, full, meta) {
            if (data && data !== '-') {
              return (
                '<a href="' + baseUrl + 'admin/tasks/show/' + data + '" class="badge bg-label-info">#' + data + '</a>'
              );
            }
            return '<span class="text-muted">-</span>';
          }
        },
        {
          // Actions
          targets: -1,
          title: __('Actions'),
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            var actions = '<div class="d-flex align-items-center">';

            // Print Button
            actions +=
              '<a href="javascript:;" class="text-body print-record me-2" data-id="' +
              data +
              '" title="' +
              __('Print') +
              '"><i class="ti ti-printer ti-sm"></i></a>';

            // 1. Refunds (Investment Recovery) get the secure delete button exclusively
            if (full['source_type'] === 'capital_return') {
              var lockTitle = __('Investment recovery locked');
              actions += '<i class="ti ti-lock text-muted me-2" title="' + lockTitle + '"></i>';
              actions +=
                '<a href="javascript:;" class="text-danger delete-settlement me-2" data-id="' +
                data +
                '" title="حذف استعادة الاستثمار (تسوية)"><i class="ti ti-trash-off ti-sm"></i></a>';
            }
            // 2. Normal Capital Deposits (credit without task_id and not refund) get normal edit/delete
            else if ((!full['task_id'] || full['task_id'] === '-') && full['transaction_type'] !== 'debit') {
              actions +=
                '<a href="javascript:;" class="text-body edit-record me-2" data-id="' +
                data +
                '" title="' +
                __('Edit') +
                '"><i class="ti ti-edit ti-sm"></i></a>';
              actions +=
                '<a href="javascript:;" class="text-body delete-record me-2" data-id="' +
                data +
                '" title="' +
                __('Delete') +
                '"><i class="ti ti-trash ti-sm"></i></a>';
            }
            // 3. Task Funding (debit or linked to task) gets locked, no buttons
            else {
              var lockTitle =
                full['transaction_type'] === 'debit'
                  ? __('Funding transaction locked')
                  : __('Task linked transaction locked');
              actions += '<i class="ti ti-lock text-muted" title="' + lockTitle + '"></i>';
            }

            // Convert capital deposit to investment recovery
            if (
              full['transaction_type'] === 'credit' &&
              full['source_type'] !== 'refund' &&
              (!full['task_id'] || full['task_id'] === '-')
            ) {
              actions +=
                '<a href="javascript:;" class="text-body convert-investment me-2" data-id="' +
                data +
                '" data-amount="' +
                full['amount'] +
                '" title="' +
                __('Convert to Capital Return') +
                '"><i class="ti ti-arrow-forward ti-sm"></i></a>';
            }

            actions += '</div>';
            return actions;
          }
        }
      ],
      order: [[0, 'desc']],
      dom:
        '<"row me-2"' +
        '<"col-md-2"<"me-3"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>' +
        '>t' +
        '<"row mx-2"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: __('Search in operations...'),
        paginate: {
          next: '<i class="ti ti-chevron-left ti-sm"></i>',
          previous: '<i class="ti ti-chevron-right ti-sm"></i>'
        }
      },
      buttons: []
    });

    // Fix: Prevent browser from autofilling the DataTable search input with user email
    setTimeout(function () {
      var searchInput = dt_transaction_table.closest('.dataTables_wrapper').find('input[type="search"]');
      searchInput.attr('autocomplete', 'off');
      searchInput.attr('name', 'dt-search-' + Math.random().toString(36).substr(2, 9));
      // Clear only if value looks like an email (browser autofill artifact)
      var val = searchInput.val();
      if (val && val.indexOf('@') !== -1) {
        searchInput.val('');
        dt_transaction.search('').draw();
      }
    }, 100);
  }

  // Handle Form Submit
  transactionForm.on('submit', function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    var submitBtn = $(this).find('.btn-submit');

    submitBtn.prop('disabled', true).find('.spinner-border').removeClass('d-none');

    $.ajax({
      url: addTransactionUrl,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (data) {
        submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');
        if (data.status == 1) {
          $('#transactionModal').modal('hide');
          transactionForm[0].reset();
          dt_transaction.draw();
          Swal.fire({
            icon: 'success',
            title: __('Done!'),
            text: data.success,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          }).then(() => {
            location.reload(); // Refresh to update statistics
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: __('Error!'),
            text: data.error,
            customClass: {
              confirmButton: 'btn btn-danger'
            }
          });
        }
      },
      error: function () {
        submitBtn.prop('disabled', false).find('.spinner-border').addClass('d-none');
        Swal.fire({
          icon: 'error',
          title: __('Error!'),
          text: __('Failed to process request'),
          customClass: {
            confirmButton: 'btn btn-danger'
          }
        });
      }
    });
  });

  // Edit Record
  $('.datatables-transactions tbody').on('click', '.edit-record', function () {
    var id = $(this).data('id');
    var row = dt_transaction.row($(this).parents('tr')).data();

    // Clear form and set values
    transactionForm[0].reset();
    $('#transaction_id').val(id);
    transactionForm.find('input[name="amount"]').val(row.amount.replace(/,/g, ''));
    transactionForm.find('textarea[name="description"]').val(row.description);

    // Change modal title
    $('#modalTitle').text(__('Edit financial transaction'));
    $('#transactionModal').modal('show');
  });

  // Reset modal on hide
  $('#transactionModal').on('hidden.bs.modal', function () {
    transactionForm[0].reset();
    $('#transaction_id').val('');
    $('#modalTitle').text(__('Add new financial transaction'));
  });

  $('#convertTransactionModal').on('hidden.bs.modal', function () {
    $('#convert_transaction_id').val('');
    $('#convertTransactionReference').text('-');
    $('#convertTransactionAmount').text('-');
    $('#convertTransactionPassword').val('');
  });

  // زر الطباعة - تحميل إيصال PDF احترافي
  $('.datatables-transactions tbody').on('click', '.print-record', function () {
    var id = $(this).data('id');
    // فتح الرابط في نافذة جديدة لتحميل الـ PDF
    window.open(baseUrl + 'admin/investors/invest-wallet/transaction/receipt/' + id, '_blank');
  });

  // Show Attachment in Modal
  $(document).on('click', '.show-attachment', function () {
    const fileUrl = $(this).data('file');
    const fileName = fileUrl.split('/').pop();
    const extension = fileName.split('.').pop().toLowerCase();
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    $('#modalFileContent').html('<div class="spinner-border text-primary" role="status"></div>');
    $('#fileModal').modal('show');

    if (imageExtensions.includes(extension)) {
      $('#modalFileContent').html(
        '<img src="' +
          fileUrl +
          '" class="img-fluid rounded shadow-sm" alt="' +
          fileName +
          '" style="max-height: 70vh; width: auto; object-fit: contain;">'
      );
    } else if (extension === 'pdf') {
      // Use native browser PDF viewer
      $('#modalFileContent').html(
        '<iframe src="' + fileUrl + '" width="100%" height="600px" style="border:none;"></iframe>'
      );
    } else {
      $('#modalFileContent').html(
        '<div class="p-4 text-center">' +
          '<i class="ti ti-file-description ti-lg mb-3 d-block text-secondary"></i>' +
          '<p class="mb-3"><strong>' +
          __('File') +
          ':</strong> ' +
          fileName +
          '</p>' +
          '<a href="' +
          fileUrl +
          '" target="_blank" class="btn btn-primary"><i class="ti ti-download me-1"></i> ' +
          __('Download or open file') +
          '</a>' +
          '</div>'
      );
    }
  });

  // Convert capital deposit to refund transaction
  $('.datatables-transactions tbody').on('click', '.convert-investment', function (e) {
    e.preventDefault();
    e.stopPropagation();

    var id = $(this).data('id');
    var amount = $(this).data('amount');

    $('#convert_transaction_id').val(id);
    $('#convertTransactionReference').text('#' + id);
    $('#convertTransactionAmount').text(amount + ' ' + __('SAR'));
    $('#convertTransactionModal').modal('show');
  });

  $('#confirmConvertTransaction').on('click', function () {
    var id = $('#convert_transaction_id').val();
    var password = $('#convertTransactionPassword').val().trim();
    var button = $(this);

    if (!password) {
      Swal.fire({
        icon: 'warning',
        title: __('Password required'),
        text: __('Please verify password before confirm'),
        customClass: {
          confirmButton: 'btn btn-warning'
        }
      });
      return;
    }

    button.prop('disabled', true);

    $.ajax({
      type: 'POST',
      url: convertTransactionUrl + '/' + id,
      data: {
        password: password
      },
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (data) {
        button.prop('disabled', false);
        $('#convertTransactionModal').modal('hide');
        if (data.status == 1) {
          dt_transaction.draw();
          Swal.fire({
            icon: 'success',
            title: __('Done!'),
            text: data.success,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          }).then(() => {
            location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: __('Error!'),
            text: data.error,
            customClass: {
              confirmButton: 'btn btn-danger'
            }
          });
        }
      },
      error: function () {
        button.prop('disabled', false);
        $('#convertTransactionModal').modal('hide');
        Swal.fire({
          icon: 'error',
          title: __('Error!'),
          text: __('Conversion processing error'),
          customClass: {
            confirmButton: 'btn btn-danger'
          }
        });
      }
    });
  });

  // Delete Record
  $('.datatables-transactions tbody').on('click', '.delete-record', function () {
    var id = $(this).data('id');
    Swal.fire({
      title: __('Are you sure?'),
      text: __('Delete transaction warning'),
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: __('Yes, delete it!'),
      cancelButtonText: __('Cancel'),
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        $.ajax({
          type: 'DELETE',
          url: baseUrl + 'admin/investors/invest-wallet/transaction/delete/' + id,
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (data) {
            dt_transaction.draw();
            if (data.status == 1) {
              Swal.fire({
                icon: 'success',
                title: __('Deleted!'),
                text: __('Transaction deleted success'),
                customClass: {
                  confirmButton: 'btn btn-success'
                }
              }).then(() => {
                location.reload();
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'خطأ!',
                text: data.error,
                customClass: {
                  confirmButton: 'btn btn-danger'
                }
              });
            }
          }
        });
      }
    });
  });

  // Delete Settlement Transaction (password protected)
  $('.datatables-transactions tbody').on('click', '.delete-settlement', function () {
    var id = $(this).data('id');
    $('#deleteSettlementId').val(id);
    $('#deleteSettlementPassword').val('');
    $('#deleteSettlementModal').modal('show');
  });

  $('#confirmDeleteSettlement').on('click', function () {
    var id = $('#deleteSettlementId').val();
    var password = $('#deleteSettlementPassword').val().trim();
    var button = $(this);

    if (!password) {
      Swal.fire({
        icon: 'warning',
        title: 'كلمة المرور مطلوبة',
        text: 'يرجى إدخال كلمة المرور للمتابعة.',
        customClass: { confirmButton: 'btn btn-warning' }
      });
      return;
    }

    button.prop('disabled', true);

    $.ajax({
      type: 'DELETE',
      url: baseUrl + 'admin/investors/invest-wallet/transaction/delete-settlement/' + id,
      data: { password: password },
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function (data) {
        button.prop('disabled', false);
        $('#deleteSettlementModal').modal('hide');
        if (data.status == 1) {
          dt_transaction.draw();
          Swal.fire({
            icon: 'success',
            title: 'تم الحذف!',
            text: data.success,
            customClass: { confirmButton: 'btn btn-success' }
          }).then(() => {
            location.reload();
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'خطأ!',
            text: data.error,
            customClass: { confirmButton: 'btn btn-danger' }
          });
        }
      },
      error: function () {
        button.prop('disabled', false);
        Swal.fire({
          icon: 'error',
          title: 'خطأ!',
          text: 'حدث خطأ في الاتصال بالخادم.',
          customClass: { confirmButton: 'btn btn-danger' }
        });
      }
    });
  });
});
