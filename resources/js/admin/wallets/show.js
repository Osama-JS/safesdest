/**
 * Page User List
 */

'use strict';
import { deleteRecord, showFormModal, showAlert } from '../../ajax';
import writtenNumber from 'written-number';

$(function () {
  var dt_data_table = $('.datatables-users');

  function toggleMaturityTime() {
    if ($('#debit').is(':checked')) {
      $('.btn-credit').addClass('btn-outline-success').removeClass('btn-success');
      $('.btn-debit').addClass('btn-danger').removeClass('btn-outline-danger');

      $('#maturity-time-group').show();
      $('#payment-method-group').show();
    } else {
      $('.btn-credit').addClass('btn-success').removeClass('btn-outline-success');
      $('.btn-debit').addClass('btn-outline-danger').removeClass('btn-danger');

      $('#maturity-time-group').hide();
      $('#payment-method-group').hide();
    }
  }

  $('#credit, #debit').on('change', toggleMaturityTime);

  $(document).on('change', '#trans_payment_method', function () {
    if ($(this).val() === 'hyperpay') {
      $('#manual-hyperpay-bank-details').slideDown();
    } else {
      $('#manual-hyperpay-bank-details').slideUp();
    }
  });

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
        url: baseUrl + 'admin/wallets/transaction/data',
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
        { data: 'created_at' },
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
          orderable: true,
          render: function (data, type, full, meta) {
            return `<span>${full.sequence}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return `<b><span class="${full.type === 'debit' ? 'text-danger' : 'text-success'}">${full.amount}</span><b>`;
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

            let invoiceBadge = '';
            if (full.invoice_number) {
              invoiceBadge = ` <span class="badge bg-label-info fs-tiny" title="فاتورة محاسبية"><i class="ti ti-file-invoice me-1"></i>${full.invoice_number}</span>`;
            }

            return `
              <span>${full.description}</span>
              ${invoiceBadge}
              ${imageBtn}
            `;
          }
        },

        {
          targets: 4,
          render: function (data, type, full, meta) {
            let maturityHtml = `<span>${full.maturity}</span>`;
            if (full.invoice_number) {
              maturityHtml += `<br><small class="text-primary fw-semibold"><i class="ti ti-file-invoice me-1"></i>${full.invoice_number}</small>`;
            }
            return maturityHtml;
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
            return `<span>${full.user}</span>`;
          }
        },

        {
          targets: 7,
          render: function (data, type, full, meta) {
            return `<span>${full.created_at}</span>`;
          }
        },

        {
          targets: 8,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            // Print Receipt button for all transactions (credit/debit)
            const printReceiptBtn =
                `<a href="${baseUrl}admin/wallets/transactions/${full.id}/receipt" target="_blank" class="btn btn-sm btn-icon btn-success" title="Print Receipt">
                  <i class="ti ti-printer"></i>
                </a>`;

            if (full.is_payout) {
              return `<div class="text-end">${printReceiptBtn}</div>`;
            }

            return `
              <div class="text-end">
                ${printReceiptBtn}
                ${
                  (full.task || full.clearance) !== ''
                    ? `
                    <button class="btn btn-sm btn-icon edit-record " data-id="${full.id}"  >
                  <i class="ti ti-edit"></i>
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
      createdRow: function (row, data, dataIndex) {
        if (data.task !== '' || data.clearance !== '') {
          $(row).addClass('table-success');
        }
      },
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
          </label>`,
        `<label class="me-2">
            <button class="add-new btn btn-primary waves-effect waves-light ms-2 mt-5" data-bs-toggle="modal"
                  data-bs-target="#submitModal">
                  <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                  <span class="d-none d-sm-inline-block"> </span>
              </button>
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

  $(document).on('click', '.edit-record', function () {
    var id = $(this).data('id');

    $.get(`${baseUrl}admin/wallets/transaction/edit/${id}`, function (data) {
      $('.form_submit').trigger('reset');
      $('#submitModal').modal('show');

      $('.text-error').html('');
      $('#trans_id').val(data.data.id);
      $('#image').attr('src', baseUrl + (data.data.image || 'assets/img/placeholder.jpg'));
      $('#trans_amount').val(data.data.amount);
      $('#trans_description').val(data.data.description);
      if (data.data.transaction_type === 'credit') {
        $('#credit').prop('checked', true);
        $('.btn-credit').addClass('btn-success').removeClass('btn-outline-success');
        $('.btn-debit').addClass('btn-outline-danger').removeClass('btn-danger');
        $('#maturity-time-group').hide();
      } else {
        $('#trans_maturity').val(data.data.maturity_time);

        $('#debit').prop('checked', true);
        $('.btn-credit').addClass('btn-outline-success').removeClass('btn-success');
        $('.btn-debit').addClass('btn-danger').removeClass('btn-outline-danger');
        $('#maturity-time-group').show();
      }

      $('#modelTitle').html(`Edit Transaction: `);
    });
  });

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/wallets/transaction/delete/' + $(this).data('id');
    deleteRecord('Transaction : #' + $(this).data('name'), url);
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $(this).find('form')[0].reset();
    $('.text-error').html('');
    $('#image').attr('src', baseUrl + 'assets/img/placeholder.jpg');
    $('#trans_payment_method').val('manual');
    $('#payment-method-group').hide();
    $('#manual-hyperpay-bank-details').hide();

    $('#trans_id').val('');
    $('#modelTitle').html('Add New Transaction');
  });

  // Payment Request Handler
  $(document).on('click', '#payment-request', function () {
    // $('#paymentRequestModal').modal('show');

    console.log(`walletId: ${walletId}`);
    // Get task details for payment request
    $.get(`${baseUrl}admin/wallets/payment/request/${walletId}`, function (data) {
      if (data.status === 0) {
        showAlert('error', data.error);
        return;
      }

      const wallet = data.wallet;
      const balance = wallet.balance;

      // Fill wallet information
      $('#paymentRequestWalletId').text(`#${wallet.id}`);
      $('#walletInfoId').text(`#${wallet.id}`);
      $('#walletInfoAmount').text(`${balance.toFixed(2)} SAR`);
      $('#walletInfoOwner').text(wallet.driver_name || 'N/A');
      $('#walletInfoOwnerPhone').text(wallet.driver_phone || 'N/A');
      $('#walletInfoOwnerEmail').text(wallet.driver_email || 'N/A');

      // Set maximum amount (for display only, not validation)
      $('#maxAmount').text(`${balance.toFixed(2)} SAR`);
      $('#requestedAmount').removeAttr('max').data('balance', balance);

      // Set hidden wallet ID
      $('#paymentRequestWalletIdInput').val(wallet.id);

      // Store wallet data for later use
      $('#paymentRequestModal').data('walletData', {
        id: wallet.id,
        driver_amount: balance,
        driver_name: wallet.driver_name,
        driver_phone: wallet.driver_phone,
        driver_email: wallet.driver_email,
        driver_bank_name: wallet.driver_bank_name,
        driver_account_number: wallet.driver_account_number,
        driver_iban_number: wallet.driver_iban_number,
        user_id: wallet.user_id,
        user_name: wallet.user_name
      });
      // Show modal
      $('#paymentRequestModal').modal('show');
      // Reset form
      $('#paymentRequestForm')[0].reset();
      $('.text-error').text('');

      $('#bankName').val(wallet.driver_bank_name);
      $('#accountNumber').val(wallet.driver_account_number);
      const formattedIban = (wallet.driver_iban_number || '').replace(/(.{4})/g, '$1 ').trim();
      $('#ibanNumber').val(formattedIban);

      console.log(wallet);
      // Initialize Select2 for tasks
      initializeTasksSelect2(wallet.driver_id);
    }).fail(function () {
      showAlert('error', 'Error loading wallet details');
    });
  });

  // Format IBAN input
  $(document).on('input', '#ibanNumber', function () {
    let value = $(this).val().replace(/\s/g, '').toUpperCase();
    // Ensure it starts with SA
    if (value && !value.startsWith('SA')) {
      value = 'SA' + value.replace(/^SA/i, '');
    }
    let formatted = value.replace(/(.{4})/g, '$1 ').trim();
    $(this).val(formatted);
  });

  // Format Account Number input
  $(document).on('input', '#accountNumber', function () {
    let value = $(this).val().replace(/\D/g, '');
    $(this).val(value);
  });

  // Validate requested amount in real-time
  $(document).on('input', '#requestedAmount', function () {
    const value = parseFloat($(this).val());
    const balance = parseFloat($(this).data('balance'));

    if (value > balance) {
      $('.requested_amount-error')
        .text(`تنبيه: المبلغ أكبر من المبلغ المستحق (${balance.toFixed(2)} ريال). سيظهر الرصيد المتبقي بالسالب.`)
        .removeClass('text-danger')
        .addClass('text-warning');
    } else {
      $('.requested_amount-error').text('').removeClass('text-warning').addClass('text-danger');
    }
  });

  // Handle payment method selection
  $(document).on('change', '#paymentMethod', function () {
    const selectedValue = $(this).val();
    const bankTransferFields = $('#bankTransferFields');
    const otherPaymentField = $('#otherPaymentField');

    if (selectedValue === 'bank_transfer') {
      bankTransferFields.show();
      otherPaymentField.hide();
      $('#otherPaymentMethod').removeAttr('required').val('');
    } else if (selectedValue === 'other') {
      bankTransferFields.hide();
      otherPaymentField.show();
      $('#otherPaymentMethod').attr('required', true);
      // Clear bank fields
      $('#bankName').val('');
      $('#customBankName').val('').hide();
      $('#accountNumber').val('');
      $('#ibanNumber').val('');
    } else {
      bankTransferFields.hide();
      otherPaymentField.hide();
      $('#otherPaymentMethod').removeAttr('required').val('');
    }
  });

  // Handle bank selection
  $(document).on('change', '#bankName', function () {
    const selectedValue = $(this).val();
    if (selectedValue === 'other') {
      $('#customBankName').show().attr('required', true);
    } else {
      $('#customBankName').hide().attr('required', false).val('');
    }
  });

  // Generate Payment Request Handler
  $(document).on('click', '#generatePaymentRequest', function () {
    const form = $('#paymentRequestForm');
    const walletData = $('#paymentRequestModal').data('walletData');

    // Validate form
    const requestedAmount = parseFloat($('#requestedAmount').val());
    const paymentMethod = $('#paymentMethod').val();
    let bankName = $('#bankName').val().trim();
    const customBankName = $('#customBankName').val().trim();
    const accountNumber = $('#accountNumber').val().trim();
    const ibanNumber = $('#ibanNumber').val().trim();
    const otherPaymentMethod = $('#otherPaymentMethod').val().trim();
    const paymentRecipient = $('#paymentRecipient').val();
    const notes = $('#notes').val();
    const selectedTasks = $('#selectedTasks').select2('data');

    // Use custom bank name if "other" is selected
    if (bankName === 'other') {
      bankName = customBankName;
    }

    // Clear previous errors
    $('.text-error').text('').removeClass('text-warning').addClass('text-danger');

    let hasErrors = false;

    if (!requestedAmount || requestedAmount <= 0) {
      $('.requested_amount-error').text('المبلغ المطلوب مطلوب ويجب أن يكون أكبر من صفر');
      hasErrors = true;
    }

    if (!paymentMethod) {
      $('.payment_method-error').text('يرجى اختيار طريقة الدفع');
      hasErrors = true;
    }

    if (requestedAmount > walletData.driver_amount) {
      $('.requested_amount-error')
        .text(
          `تنبيه: المبلغ المطلوب أكبر من المبلغ المستحق للسائق (${walletData.driver_amount.toFixed(2)} ريال). سيظهر الرصيد المتبقي بالسالب في طلب السداد.`
        )
        .removeClass('text-danger')
        .addClass('text-warning');
    } else {
      $('.requested_amount-error').removeClass('text-warning').addClass('text-danger');
    }

    if (paymentMethod === 'other') {
      if (!otherPaymentMethod) {
        $('.other_payment_method-error').text('يرجى إدخال تفاصيل طريقة الدفع');
        hasErrors = true;
      }
    } else if (paymentMethod === 'bank_transfer') {
      // Bank transfer validation is optional now
      if (ibanNumber && !ibanNumber.replace(/\s/g, '').match(/^SA\d{22}$/)) {
        $('.iban_number-error').text('تنسيق رقم الآيبان غير صحيح (يجب أن يبدأ بـ SA ويتبعه 22 رقم)');
        hasErrors = true;
      }

      if (accountNumber && accountNumber.length < 8) {
        $('.account_number-error').text('رقم الحساب يجب أن يكون على الأقل 8 أرقام');
        hasErrors = true;
      }
    }

    if (hasErrors) {
      return;
    }

    // Generate payment request document
    generatePaymentRequestDocument({
      taskId: walletData.id,
      requestedAmount: requestedAmount,
      paymentMethod: paymentMethod,
      bankName: bankName,
      accountNumber: accountNumber,
      ibanNumber: ibanNumber,
      otherPaymentMethod: otherPaymentMethod,
      paymentRecipient: paymentRecipient,
      notes: notes,
      selectedTasks: selectedTasks,
      walletData: walletData
    });
  });

  // Function to generate payment request document
  function generatePaymentRequestDocument(data) {
    const today = new Date();
    const formattedDate = today.toLocaleDateString('ar-SA');
    const remainingAmount = data.walletData.driver_amount - data.requestedAmount;
    const recipientName = data.walletData.driver_name;
    const recipientPhone = data.walletData.driver_phone;

    // Generate reference number: TaskID + Date (YYYYMMDD) + Random 3 digits
    const dateString =
      today.getFullYear().toString() +
      (today.getMonth() + 1).toString().padStart(2, '0') +
      today.getDate().toString().padStart(2, '0');
    const randomNumber = Math.floor(Math.random() * 900) + 100; // 3-digit random number
    const referenceNumber = `${data.taskId}${dateString}${randomNumber}`;

    // Convert number to Arabic words
    // const requestedAmountInWords = numberToArabicWords(data.requestedAmount);
    let amount = data.requestedAmount; // المبلغ من قاعدة البيانات أو الـ API
    let requestedAmountInWords = writtenNumber(amount, { lang: 'ar' }) + ' ريال سعودي';

    // استخراج أسماء العملاء الفريدة من المهام المحددة
    let customerNames = [];
    if (data.selectedTasks && data.selectedTasks.length > 0) {
      customerNames = [...new Set(data.selectedTasks.map(t => t.customer_name).filter(Boolean))];
    }
    let customerNamesHtml = customerNames.length > 0 ? customerNames.join('، ') : '';
    let customerLabel = customerNames.length > 1 ? 'العملاء' : 'العميل';

    let tasksHtml = data.selectedTasks
      .map(task => {
        let cust = task.customer_name ? ` (العميل: ${task.customer_name})` : '';
        return `مهمة #${task.id}${cust}`;
      })
      .join(' ، ');
    console.log(data);
    const printContent = `
  <!DOCTYPE html>
  <html dir="rtl" lang="ar">
  <head>
    <meta charset="UTF-8">
    <title>طلب سداد - ${referenceNumber}</title>
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

      .emp-name{
        font-size: 16px;
      }

      table {
        width: 100%;
        margin-bottom: 15px;
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

        padding: 15px;
        margin: 20px 0;
        font-weight: bold;
        font-size: 16px;
      }

      .signatures td {
        height: 80px;
        text-align: center;
      }

      .amount-details{
        font-size: 16px;
      }
        .amount-details span{
          border:1px solid #000;
          padding: 5px 10px;
          margin: 20px 5px;
          border-radius: 5px;
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
        <h2>طلب سداد مالي</h2>
        <p>رقم الطلب: ${referenceNumber}</p>
        <p>التاريخ: ${formattedDate}</p>
        <p style="color: #007bff; font-weight: bold;">
          طريقة السداد: ${data.paymentMethod === 'bank_transfer' ? 'تحويل بنكي' : data.paymentMethod === 'other' ? 'طريقة أخرى' : 'غير محدد'}
        </p>
      </div>

      <!-- Employee -->

      <p class="emp-name">
          اسم الموظف طالب السداد : <strong> ${$('meta[name="user-name"]').attr('content') || 'المستخدم الحالي'}</strong>
      </p>
      ${
        customerNamesHtml
          ? `
      <p class="emp-name">
          ${customerLabel} : <strong>${customerNamesHtml}</strong>
      </p>`
          : ''
      }

      <h3>بيانات السداد</h3>
      <!-- Amount -->
      <div class="amount-box">
        مبلغ السداد:

        (${requestedAmountInWords})
      </div>
      <div>
        <p class="amount-details">
        السداد:
        دفعة <span>${data.requestedAmount.toFixed(2)} ريال </span>
        باقي حساب <span> ${remainingAmount.toFixed(2)} ريال </span>
        إجمالي الحساب <span>${data.walletData.driver_amount.toFixed(2)} ريال </span>
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
        <tr><td class="label">رقم الآيبان</td><td>${(data.ibanNumber || '').replace(/\s+/g, '') || 'غير محدد'}</td></tr>
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

      <!-- Trip Info -->
      <h3>بيانات المورد</h3>
      <table>
        <tr><td class="label">الإسم</td><td>${recipientName}</td></tr>
        <tr><td class="label">رقم الهاتف</td><td>${recipientPhone}</td></tr>
        <tr><td class="label">رقم المحفظة</td><td>${data.walletData.id}</td></tr>
        <tr><td class="label">الرصيد المتبقي</td><td> ${remainingAmount.toFixed(2)} ريال</td></tr>
      </table>
      ${
        data.notes
          ? ` <h3>ملاحظات إضافية</h3>
      <div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background-color: #f9f9f9; white-space: pre-line;">
        <strong>${data.notes}</strong>
      </div>`
          : ''
      }
      ${
        tasksHtml
          ? `<h3>المهام المرتبطة</h3>
      <div style="border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background-color: #f0f8ff; white-space: pre-line;">
        <strong>${tasksHtml}</strong>
      </div>`
          : ''
      }


      <!-- Signatures -->
      <h3>التوقيع</h3>
      <br>
      <br>
      <br>
      <!-- Footer -->
      <div class="footer">
        <p>تم إنشاء المستند إلكترونياً بتاريخ ${new Date().toLocaleDateString('ar-SA')}</p>

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

    // Add event listener for print dialog
    printWindow.addEventListener('beforeprint', function () {
      console.log('Print dialog opened');
    });

    printWindow.addEventListener('afterprint', function () {
      console.log('Print dialog closed - logging payment request');

      // Log the payment request after actual printing
      logPaymentRequest({
        walletId: data.walletData.id,
        amount: data.requestedAmount,
        paymentRequestNumber: referenceNumber,
        paymentMethod: data.paymentMethod,
        bankName: data.bankName,
        accountNumber: data.accountNumber,
        ibanNumber: data.ibanNumber,
        otherPaymentMethod: data.otherPaymentMethod,
        notes: data.notes || null,
        selectedTasks: data.selectedTasks || []
      });

      printWindow.close();
    });

    // Handle print cancellation
    printWindow.onbeforeunload = function () {
      return null;
    };

    // Trigger print
    printWindow.print();

    // Fallback: close window if user cancels print (for some browsers)
    setTimeout(function () {
      if (!printWindow.closed) {
        printWindow.addEventListener('focus', function () {
          setTimeout(function () {
            if (!printWindow.closed) {
              printWindow.close();
            }
          }, 100);
        });
      }
    }, 1000);

    // Close modal after printing
    setTimeout(() => {
      $('#paymentRequestModal').modal('hide');
      $('#paymentRequestForm')[0].reset();
    }, 1000);
  }

  // Function to initialize Select2 for tasks
  function initializeTasksSelect2(driverId) {
    if (!driverId) {
      console.error('❌ driverId is required to load tasks.');
      return;
    }

    $('#selectedTasks').select2({
      placeholder: 'اختر المهام المرتبطة بطلب السداد',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#paymentRequestModal'),
      ajax: {
        url: `${baseUrl}admin/wallets/driver-tasks/${driverId}`,
        dataType: 'json',
        delay: 250,
        cache: true,
        processResults: data => {
          if (data.status === 1 && Array.isArray(data.tasks)) {
            return {
              results: data.tasks.map(task => ({
                id: task.id,
                text: `مهمة #${task.id}` + (task.customer_name ? ` - ${task.customer_name}` : ''),
                ...task
              }))
            };
          }
          return { results: [] };
        }
      },
      templateResult: task => {
        if (task.loading) return task.text;

        return $(`
        <div class="task-option py-1">
          <div class="fw-bold d-flex justify-content-between align-items-center">
            <span>مهمة #${task.id}</span>
            ${task.customer_name ? `<span class="badge bg-label-primary font-small">${task.customer_name}</span>` : ''}
          </div>
          <div class="text-muted small">${task.pickup_address ?? ''}</div>
          <div class="text-primary small">${task.total_price} ريال - ${task.status}</div>
        </div>
      `);
      },
      templateSelection: task => {
        if (!task.id) return task.text;
        return `مهمة #${task.id}` + (task.customer_name ? ` - ${task.customer_name}` : '');
      }
    });
  }

  // Function to log payment request after printing
  function logPaymentRequest(data) {
    $.ajax({
      url: `${baseUrl}admin/wallets/${data.walletId}/log-payment-request`,
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
        selected_tasks: data.selectedTasks || [],
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.status === 1) {
          console.log('Payment request logged successfully:', response.log_id);
          // Refresh payment logs if visible
          if ($('#payment-logs-section').is(':visible')) {
            loadPaymentRequestLogs();
          }
        } else {
          console.error('Failed to log payment request:', response.error);
        }
      },
      error: function (xhr, status, error) {
        console.error('Error logging payment request:', error);
      }
    });
  }

  // Function to load payment request logs
  function loadPaymentRequestLogs() {
    $.ajax({
      url: `${baseUrl}admin/wallets/${walletId}/payment-request-logs`,
      method: 'GET',
      success: function (response) {
        if (response.status === 1) {
          displayPaymentRequestLogs(response.logs);
        } else {
          console.error('Failed to load payment logs:', response.error);
        }
      },
      error: function (xhr, status, error) {
        console.error('Error loading payment logs:', error);
      }
    });
  }

  // Function to display payment request logs
  function displayPaymentRequestLogs(logs) {
    const logsContainer = $('#payment-logs-container');

    if (logs.data.length === 0) {
      logsContainer.html(`
        <div class="text-center py-4">
          <i class="ti ti-file-x fs-1 text-muted"></i>
          <p class="text-muted mt-2">لا توجد سجلات طلبات سداد</p>
        </div>
      `);
      return;
    }

    let logsHtml = '';
    logs.data.forEach(log => {
      logsHtml += `
        <div class="card mb-3">
          <div class="card-body">
            <div class="row">
              <div class="col-md-2">
                <small class="text-muted">${__('Printing Date')}</small>
                <div class="fw-semibold">${moment(log.printed_at).format('DD-MM-YYYY HH:mm')}</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted">رقم الطلب</small>
                <div class="fw-semibold text-primary">${log.payment_request_number || 'غير محدد'}</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted">${__('Amount')}</small>
                <div class="fw-semibold text-success">${log.amount}</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted">${__('User')}</small>
                <div class="fw-semibold">${log.user.name}</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted">IP</small>
                <div class="fw-semibold">${log.ip_address}</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted">${__('Notes')}</small>
                <div class="fw-semibold">${log.notes || ''}</div>
              </div>
            </div>
          </div>
        </div>
      `;
    });

    logsContainer.html(logsHtml);

    // Add pagination if needed
    if (logs.last_page > 1) {
      // Add pagination controls here if needed
    }
  }

  $(document).on('click', '#loadRefresh', function () {
    loadPaymentRequestLogs();
  });

  // Load payment logs on page load if section is visible
  if ($('#payment-logs-section').length) {
    loadPaymentRequestLogs();
  }
  
  // --- Investment Settlement Settings Logic ---
  let unsettledTasks = [];

  $('#toggleSettlementPanelBtn').on('click', function() {
    const panel = $('#settlement-panel');
    if (panel.is(':visible')) {
      panel.slideUp();
    } else {
      panel.slideDown();
      fetchUnsettledTasks();
    }
  });

  function fetchUnsettledTasks() {
    $('#settlement-tasks-tbody').html('<tr><td colspan="5" class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> جاري التحميل...</td></tr>');
    
    $.get(baseUrl + 'admin/wallets/' + walletId + '/fetch-unsettled-tasks', function(response) {
      if (response.status === 1) {
        unsettledTasks = response.data;
        renderSettlementTasks();
        autoSelectSettlementTasks();
      } else {
        $('#settlement-tasks-tbody').html('<tr><td colspan="5" class="text-center text-danger">حدث خطأ أثناء جلب المهام</td></tr>');
      }
    });
  }

  function renderSettlementTasks() {
    const tbody = $('#settlement-tasks-tbody');
    tbody.empty();
    
    if (unsettledTasks.length === 0) {
      tbody.html('<tr><td colspan="5" class="text-center text-muted">لا توجد مهام ديون غير مسددة مرتبطة بمستثمرين</td></tr>');
      return;
    }
    
    unsettledTasks.forEach(function(task, index) {
      const isSettledByAdmin = task.is_settled_by_admin;
      const statusBadge = isSettledByAdmin 
        ? `<span class="badge bg-label-success fw-bold" title="تمت تسوية رأس مال هذه المهمة للمستثمر مسبقاً من قبل الإدارة، ولن يتم تكرار الصرف للمستثمر">
             <i class="ti ti-shield-check me-1"></i>تمت التسوية من الإدارة
           </span>
           <small class="d-block text-success mt-1" style="font-size: 0.72rem;">
             (لن يتم تكرار الصرف في محفظة الاستثمار)
           </small>`
        : `<span class="badge bg-label-secondary" title="سيتم إرجاع رأس المال للمستثمر تلقائياً عند إتمام هذه التسوية">
             <i class="ti ti-clock me-1"></i>بانتظار التسوية
           </span>
           <small class="d-block text-muted mt-1" style="font-size: 0.72rem;">
             (سيُعاد للمستثمر تلقائياً)
           </small>`;

      const tr = `
        <tr class="${isSettledByAdmin ? 'table-success bg-opacity-10' : ''}">
          <td>
            <input type="checkbox" class="form-check-input settlement-task-checkbox" data-id="${task.transaction_id}" data-amount="${task.unpaid_amount}" value="${task.transaction_id}">
          </td>
          <td>
            <span class="fw-bold">#${task.task_id}</span>
          </td>
          <td class="fw-semibold">${parseFloat(task.unpaid_amount).toFixed(2)}</td>
          <td>${task.investor_name}</td>
          <td>${statusBadge}</td>
        </tr>
      `;
      tbody.append(tr);
    });
  }

  function autoSelectSettlementTasks() {
    const creditAmount = parseFloat($('#trans_amount').val()) || 0;
    $('#settlement-credit-amount').text(creditAmount.toFixed(2));
    
    let currentTotal = 0;
    $('.settlement-task-checkbox').prop('checked', false);
    
    $('.settlement-task-checkbox').each(function() {
      const taskAmount = parseFloat($(this).data('amount'));
      // If adding this task doesn't exceed the credit amount (or we just allow partial up to the task amount, but since it's full/partial settlement we can just check if we have enough credit)
      // The backend will handle partial payment if needed. We just select tasks that *could* be covered.
      if (currentTotal < creditAmount) {
        $(this).prop('checked', true);
        currentTotal += taskAmount;
      }
    });
    
    updateSettlementTotal();
  }

  $('#trans_amount').on('input', function() {
    if ($('#settlement-panel').is(':visible')) {
      autoSelectSettlementTasks();
    }
  });

  $(document).on('change', '.settlement-task-checkbox', function() {
    updateSettlementTotal();
  });

  $('#selectAllSettlementTasks').on('change', function() {
    $('.settlement-task-checkbox').prop('checked', $(this).prop('checked'));
    updateSettlementTotal();
  });

  function updateSettlementTotal() {
    let total = 0;
    $('.settlement-task-checkbox:checked').each(function() {
      total += parseFloat($(this).data('amount'));
    });
    $('#settlement-selected-total').text(total.toFixed(2));
    
    const creditAmount = parseFloat($('#trans_amount').val()) || 0;
    const remaining = creditAmount - total;
    $('#settlement-remaining-amount').text(remaining.toFixed(2));
    
    if (remaining < 0) {
      $('#settlement-remaining-amount').removeClass('text-success text-warning').addClass('text-danger');
    } else {
      $('#settlement-remaining-amount').removeClass('text-danger text-warning').addClass('text-success');
    }
  }

  // Before form submit, append selected tasks as hidden inputs
  $('.add-new-transaction').on('submit', function(e) {
    // Remove old hidden inputs
    $('.hidden-settlement-tasks').remove();
    
    // Add selected ones
    if ($('#credit').is(':checked')) {
      let totalSelected = 0;
      $('.settlement-task-checkbox:checked').each(function() {
        totalSelected += parseFloat($(this).data('amount')) || 0;
      });

      const creditAmount = parseFloat($('#trans_amount').val()) || 0;

      if ($('#settlement-panel').is(':visible') && totalSelected > creditAmount) {
        e.preventDefault();
        e.stopPropagation();
        Swal.fire({
          icon: 'error',
          title: 'خطأ في التسوية',
          text: 'المبلغ المدخل أقل من إجمالي المبالغ للمهام المحددة. يرجى تعديل الاختيارات أو زيادة مبلغ الإيداع.',
          customClass: { confirmButton: 'btn btn-primary' }
        });
        return false;
      }

      $('.settlement-task-checkbox:checked').each(function() {
        $('<input>').attr({
          type: 'hidden',
          name: 'settlement_tasks[]',
          class: 'hidden-settlement-tasks',
          value: $(this).val()
        }).appendTo('.add-new-transaction');
      });
    }
  });

  // ==========================================
  // CUSTOMER ACCOUNTING INVOICES MODULE
  // ==========================================
  if (typeof walletUserType !== 'undefined' && walletUserType === 'customer') {
    let dt_invoices_table = $('#invoicesTable');
    let dt_invoices = null;

    if (dt_invoices_table.length) {
      dt_invoices = dt_invoices_table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: baseUrl + 'admin/customer-invoices/wallet/' + walletId + '/data',
          data: function (d) {
            d.status = $('#filter-invoice-status').val();
          }
        },
        columns: [
          { data: 'id' },
          { data: 'invoice_number' },
          { data: 'accounting_reference_no' },
          { data: 'total_amount' },
          { data: 'paid_amount' },
          { data: 'remaining_amount' },
          { data: 'due_date' },
          { data: 'status' },
          { data: 'attachment_url' },
          { data: 'created_by_name' },
          { data: null }
        ],
        columnDefs: [
          {
            targets: 0,
            searchable: false,
            orderable: false,
            render: function (data, type, full, meta) {
              return meta.row + 1 + meta.settings._iDisplayStart;
            }
          },
          {
            targets: 1,
            render: function (data, type, full) {
              return `<a href="javascript:void(0);" class="fw-bold text-primary btn-view-invoice" data-id="${full.id}"><i class="ti ti-file-invoice me-1"></i>${full.invoice_number}</a>`;
            }
          },
          {
            targets: 2,
            render: function (data, type, full) {
              return full.accounting_reference_no && full.accounting_reference_no !== '-' ? `<span class="badge bg-label-secondary">${full.accounting_reference_no}</span>` : '-';
            }
          },
          {
            targets: 3,
            render: function (data, type, full) {
              return `<span class="fw-bold">${parseFloat(full.total_amount).toFixed(2)}</span> <small class="text-muted">ر.س</small>`;
            }
          },
          {
            targets: 4,
            render: function (data, type, full) {
              return `<span class="fw-semibold text-success">${parseFloat(full.paid_amount).toFixed(2)}</span> <small class="text-muted">ر.س</small>`;
            }
          },
          {
            targets: 5,
            render: function (data, type, full) {
              const rem = parseFloat(full.remaining_amount);
              const color = rem > 0 ? 'text-danger' : 'text-muted';
              return `<span class="fw-bold ${color}">${rem.toFixed(2)}</span> <small class="text-muted">ر.س</small>`;
            }
          },
          {
            targets: 6,
            render: function (data, type, full) {
              let html = `<span>${full.due_date}</span>`;
              if (full.is_overdue) {
                html += ` <span class="badge bg-danger fs-tiny">متأخرة</span>`;
              }
              return html;
            }
          },
          {
            targets: 7,
            render: function (data, type, full) {
              const badges = {
                unpaid: '<span class="badge bg-label-warning">مستحقة للدفع</span>',
                paid: '<span class="badge bg-label-success">مدفوعة</span>',
                approved: '<span class="badge bg-label-primary">معتمدة نهائياً</span>',
                cancelled: '<span class="badge bg-label-secondary">ملغاة</span>'
              };
              return badges[full.status] || full.status;
            }
          },
          {
            targets: 8,
            render: function (data, type, full) {
              if (full.attachment_url) {
                return `<a href="${full.attachment_url}" target="_blank" class="btn btn-sm btn-icon btn-label-primary" title="عرض المرفق"><i class="ti ti-paperclip"></i></a>`;
              }
              return '<span class="text-muted">-</span>';
            }
          },
          {
            targets: 9,
            render: function (data, type, full) {
              return `<small class="text-muted">${full.created_by_name}</small>`;
            }
          },
          {
            targets: 10,
            orderable: false,
            searchable: false,
            render: function (data, type, full) {
              let actions = `
                <div class="d-inline-block text-nowrap">
                  <a href="${baseUrl}admin/customer-invoices/${full.id}/print" target="_blank" class="btn btn-sm btn-icon" title="طباعة الفاتورة">
                    <i class="ti ti-printer text-primary"></i>
                  </a>
                  <button class="btn btn-sm btn-icon btn-view-invoice" data-id="${full.id}" title="عرض التفاصيل">
                    <i class="ti ti-eye text-info"></i>
                  </button>
                  <button class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end m-0">
                    <a href="${baseUrl}admin/customer-invoices/${full.id}/print" target="_blank" class="dropdown-item">
                      <i class="ti ti-printer me-2 text-primary"></i>طباعة الفاتورة
                    </a>
                    <a href="javascript:void(0);" class="dropdown-item btn-view-invoice" data-id="${full.id}">
                      <i class="ti ti-eye me-2 text-info"></i>عرض التفاصيل
                    </a>
              `;

              if (full.status === 'unpaid') {
                actions += `
                    <a href="javascript:void(0);" class="dropdown-item btn-pay-invoice" data-id="${full.id}" data-number="${full.invoice_number}" data-total="${full.total_amount}" data-remaining="${full.remaining_amount}">
                      <i class="ti ti-cash me-2 text-success"></i>تسجيل سداد
                    </a>
                `;
              }

              if (full.status !== 'approved' && full.status !== 'cancelled') {
                actions += `
                    <a href="javascript:void(0);" class="dropdown-item btn-edit-invoice" data-id="${full.id}">
                      <i class="ti ti-edit me-2 text-warning"></i>تعديل الفاتورة
                    </a>
                    <a href="javascript:void(0);" class="dropdown-item btn-approve-invoice" data-id="${full.id}" data-number="${full.invoice_number}">
                      <i class="ti ti-check me-2 text-primary"></i>اعتماد نهائي
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="javascript:void(0);" class="dropdown-item text-danger btn-cancel-invoice" data-id="${full.id}" data-number="${full.invoice_number}">
                      <i class="ti ti-circle-x me-2"></i>إلغاء الفاتورة وفك الحركات
                    </a>
                `;
              }

              actions += `
                  </div>
                </div>
              `;
              return actions;
            }
          }
        ],
        order: [[1, 'desc']],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        language: {
          sLengthMenu: '_MENU_',
          search: '',
          searchPlaceholder: 'بحث في الفواتير...'
        }
      });
    }

    // Filter change
    $('#filter-invoice-status').on('change', function () {
      if (dt_invoices) dt_invoices.ajax.reload();
    });

    $('#btnRefreshInvoices').on('click', function () {
      if (dt_invoices) dt_invoices.ajax.reload();
    });

    // Load Uninvoiced Transactions
    function loadUninvoicedTransactions() {
      const tbody = $('#uninvoiced-transactions-table-body');
      tbody.html(`
        <tr>
          <td colspan="6" class="text-center py-4 text-muted">
            <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>
            جاري جلب الحركات المالية غير المفوترة...
          </td>
        </tr>
      `);
      $('#checkAllUninvoiced').prop('checked', false);
      $('#create-invoice-selected-count').text('0');
      $('#create-invoice-selected-total').text('0.00');
      $('#uninvoiced-task-search').val('');
      $('#uninvoiced-search').val('');
      $('#matching-tasks-count-badge').hide();

      $.get(baseUrl + 'admin/customer-invoices/wallet/' + walletId + '/uninvoiced-transactions', function (res) {
        if (res.status === 1 && res.data && res.data.length > 0) {
          let rowsHtml = '';
          res.data.forEach(function (tx) {
            const taskId = tx.task_id ? tx.task_id.toString().trim() : '';
            const taskNo = tx.task_number ? tx.task_number.toString().trim() : '';
            const customNo = tx.custom_task_number ? tx.custom_task_number.toString().trim() : '';
            const searchCorpus = (
              (tx.sequence || '') + ' ' +
              taskId + ' ' +
              taskNo + ' ' +
              customNo + ' ' +
              (tx.description || '') + ' ' +
              (tx.amount || '')
            ).toLowerCase();

            rowsHtml += `
              <tr class="uninvoiced-row"
                  data-task-id="${taskId}"
                  data-task-number="${taskNo.toLowerCase()}"
                  data-custom-number="${customNo.toLowerCase()}"
                  data-search="${searchCorpus}">
                <td class="text-center">
                  <input type="checkbox" class="form-check-input tx-checkbox" name="transaction_ids[]" value="${tx.id}" data-amount="${tx.amount}">
                </td>
                <td><span class="fw-semibold">${tx.sequence || tx.id}</span></td>
                <td>
                  ${taskNo ? `<span class="badge bg-label-primary fs-tiny fw-bold"><i class="ti ti-hash me-1"></i>${taskNo}</span>` : '<span class="text-muted">-</span>'}
                </td>
                <td><span class="text-truncate d-inline-block" style="max-width: 270px;" title="${tx.description}">${tx.description}</span></td>
                <td><small class="text-muted">${tx.current_maturity}</small></td>
                <td class="text-end fw-bold text-danger">${parseFloat(tx.amount).toFixed(2)}</td>
              </tr>
            `;
          });
          tbody.html(rowsHtml);
        } else {
          tbody.html(`
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="ti ti-circle-check fs-2 text-success d-block mb-1"></i>
                لا توجد حركات مدينة غير مفوترة في هذه المحفظة حالياً.
              </td>
            </tr>
          `);
        }
      }).fail(function () {
        tbody.html(`
          <tr>
            <td colspan="6" class="text-center py-4 text-danger">
              حدث خطأ أثناء جلب الحركات. يرجى إعادة المحاولة.
            </td>
          </tr>
        `);
      });
    }

    // Open Create Modal
    $(document).on('click', '#btnOpenCreateInvoiceModal, #btnToolbarCreateInvoice', function () {
      $('#formCreateCustomerInvoice')[0].reset();
      $('#issue_date').val(new Date().toISOString().split('T')[0]);
      loadUninvoicedTransactions();
      $('#createCustomerInvoiceModal').modal('show');
    });

    $('#btnReloadUninvoiced').on('click', function () {
      loadUninvoicedTransactions();
    });

    // Filter Uninvoiced Transactions by Task Number or General Search
    function filterUninvoicedRows() {
      const rawTaskQuery = ($('#uninvoiced-task-search').val() || '').trim().toLowerCase();
      const rawGeneralQuery = ($('#uninvoiced-search').val() || '').trim().toLowerCase();

      // Split task query by comma, semicolon, space, newline or tab
      const taskTokens = rawTaskQuery ? rawTaskQuery.split(/[\s,;\n]+/).filter(t => t.length > 0) : [];

      let visibleCount = 0;
      let matchingTaskCount = 0;

      $('.uninvoiced-row').each(function () {
        const row = $(this);
        const rowTaskId = (row.data('task-id') || '').toString().toLowerCase();
        const rowTaskNo = (row.data('task-number') || '').toString().toLowerCase();
        const rowCustomNo = (row.data('custom-number') || '').toString().toLowerCase();
        const rowSearch = (row.data('search') || '').toString().toLowerCase();

        // Check task tokens
        let matchesTask = true;
        if (taskTokens.length > 0) {
          matchesTask = taskTokens.some(token => {
            const cleanToken = token.replace(/^[#]/, '').trim();
            return (
              rowTaskId === cleanToken ||
              (cleanToken.length >= 2 && rowTaskId.indexOf(cleanToken) !== -1) ||
              rowTaskNo.indexOf(token) !== -1 ||
              rowTaskNo.indexOf(cleanToken) !== -1 ||
              rowCustomNo.indexOf(token) !== -1 ||
              rowCustomNo.indexOf(cleanToken) !== -1
            );
          });
        }

        // Check general search
        let matchesGeneral = true;
        if (rawGeneralQuery) {
          matchesGeneral = rowSearch.indexOf(rawGeneralQuery) !== -1;
        }

        if (matchesTask && matchesGeneral) {
          row.show();
          visibleCount++;
          if (taskTokens.length > 0) {
            matchingTaskCount++;
          }
        } else {
          row.hide();
        }
      });

      // Update matching badge
      if (taskTokens.length > 0) {
        $('#matching-tasks-count-text').text(`تم العثور على ${matchingTaskCount} حركة مطابقة للمهام`);
        $('#matching-tasks-count-badge').show();
      } else {
        $('#matching-tasks-count-badge').hide();
      }
    }

    $(document).on('keyup input', '#uninvoiced-task-search, #uninvoiced-search', filterUninvoicedRows);

    // Clear Task Search
    $(document).on('click', '#btnClearTaskSearch', function () {
      $('#uninvoiced-task-search').val('');
      filterUninvoicedRows();
    });

    // Select All Matching Filtered Tasks
    $(document).on('click', '#btnSelectFilteredTasks', function () {
      const visibleCheckboxes = $('.uninvoiced-row:visible .tx-checkbox');
      if (visibleCheckboxes.length === 0) {
        Swal.fire({
          icon: 'info',
          title: 'تنبيه',
          text: 'لا توجد حركات مطابقة للبحث الحالي لتحديدها.',
          customClass: { confirmButton: 'btn btn-primary' }
        });
        return;
      }
      visibleCheckboxes.prop('checked', true);
      updateCreateInvoiceTotals();

      Swal.fire({
        icon: 'success',
        title: 'تم التحديد',
        text: `تم تحديد ${visibleCheckboxes.length} حركة مطابقة بنجاح.`,
        timer: 1500,
        showConfirmButton: false
      });
    });

    // Recalculate selected sum & count
    function updateCreateInvoiceTotals() {
      let count = 0;
      let total = 0.0;
      $('.tx-checkbox:checked').each(function () {
        count++;
        total += parseFloat($(this).data('amount')) || 0;
      });
      $('#create-invoice-selected-count').text(count);
      $('#create-invoice-selected-total').text(total.toFixed(2));
    }

    $(document).on('change', '.tx-checkbox', function () {
      updateCreateInvoiceTotals();
    });

    // Check All Visible
    $(document).on('change', '#checkAllUninvoiced', function () {
      const isChecked = $(this).is(':checked');
      $('.uninvoiced-row:visible .tx-checkbox').prop('checked', isChecked);
      updateCreateInvoiceTotals();
    });

    // Submit Create Invoice
    $('#formCreateCustomerInvoice').on('submit', function (e) {
      e.preventDefault();

      const selectedCount = $('.tx-checkbox:checked').length;
      if (selectedCount === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'تنبيه',
          text: 'يرجى تحديد حركة مدينة واحدة على الأقل لربطها بالفاتورة.',
          customClass: { confirmButton: 'btn btn-primary' }
        });
        return;
      }

      if (!$('#due_date').val()) {
        Swal.fire({
          icon: 'warning',
          title: 'تنبيه',
          text: 'يرجى تحديد تاريخ استحقاق الفاتورة.',
          customClass: { confirmButton: 'btn btn-primary' }
        });
        return;
      }

      const submitBtn = $('#btnSubmitCreateInvoice');
      submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> جاري الحفظ وتعميم الاستحقاق...');

      const formData = new FormData(this);

      $.ajax({
        url: baseUrl + 'admin/customer-invoices/store',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (res) {
          submitBtn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> حفظ وإنشاء الفاتورة');
          if (res.status === 1) {
            $('#createCustomerInvoiceModal').modal('hide');
            Swal.fire({
              icon: 'success',
              title: 'تم بنجاح',
              text: res.success || 'تم إنشاء الفاتورة المحاسبية بنجاح وتعميم تاريخ الاستحقاق على الحركات.',
              customClass: { confirmButton: 'btn btn-primary' }
            });
            if (dt_invoices) dt_invoices.ajax.reload();
            if (dt_data) dt_data.ajax.reload();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'خطأ',
              text: res.error || 'فشل إنشاء الفاتورة',
              customClass: { confirmButton: 'btn btn-primary' }
            });
          }
        },
        error: function (xhr) {
          submitBtn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> حفظ وإنشاء الفاتورة');
          let msg = 'حدث خطأ غير متوقع';
          if (xhr.responseJSON && xhr.responseJSON.error) {
            msg = xhr.responseJSON.error;
          } else if (xhr.responseJSON && xhr.responseJSON.message) {
            msg = xhr.responseJSON.message;
          }
          Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: msg,
            customClass: { confirmButton: 'btn btn-primary' }
          });
        }
      });
    });

    // View Invoice Details
    let currentViewingInvoiceId = null;
    $(document).on('click', '.btn-view-invoice', function () {
      const id = $(this).data('id');
      currentViewingInvoiceId = id;

      $.get(baseUrl + 'admin/customer-invoices/' + id, function (res) {
        if (res.status === 1) {
          const inv = res.invoice;
          $('#viewInvoiceNumber').text(inv.invoice_number);
          $('#viewInvoiceRef').text(inv.accounting_reference_no || '-');
          $('#viewInvoiceIssueDate').text(inv.issue_date ? inv.issue_date.split('T')[0] : '-');
          $('#viewInvoiceDueDate').text(inv.due_date ? inv.due_date.split('T')[0] : '-');
          $('#viewInvoiceTotal').text(parseFloat(inv.total_amount).toFixed(2));
          $('#viewInvoicePaid').text(parseFloat(inv.paid_amount).toFixed(2));
          $('#viewInvoiceRemaining').text(parseFloat(inv.remaining_amount).toFixed(2));

          const statusBadges = {
            unpaid: '<span class="badge bg-label-warning">مستحقة للدفع</span>',
            paid: '<span class="badge bg-label-success">مدفوعة</span>',
            approved: '<span class="badge bg-label-primary">معتمدة نهائياً</span>',
            cancelled: '<span class="badge bg-label-secondary">ملغاة</span>'
          };
          $('#viewInvoiceStatusBadge').html(statusBadges[inv.status] || inv.status);

          if (inv.notes) {
            $('#viewInvoiceNotes').text(inv.notes);
            $('#viewInvoiceNotesBox').show();
          } else {
            $('#viewInvoiceNotesBox').hide();
          }

          if (res.attachment_url) {
            $('#viewInvoiceAttachmentLink').attr('href', res.attachment_url);
            $('#viewInvoiceAttachmentBox').show();
          } else {
            $('#viewInvoiceAttachmentBox').hide();
          }

          $('#viewInvoiceCreator').text(inv.creator ? inv.creator.name : '-');
          $('#viewInvoiceCreatedAt').text(inv.created_at ? inv.created_at.split('T')[0] : '-');

          if (inv.approver) {
            $('#viewInvoiceApprover').text(inv.approver.name + ' (' + (inv.approved_at ? inv.approved_at.split('T')[0] : '') + ')');
            $('#viewInvoiceApproverBox').show();
          } else {
            $('#viewInvoiceApproverBox').hide();
          }

          let itemsHtml = '';
          if (inv.items && inv.items.length > 0) {
            inv.items.forEach(function (item, idx) {
              const seq = item.wallet_transaction ? item.wallet_transaction.sequence : item.wallet_transaction_id;
              const taskNo = item.task ? (item.task.custom_task_number || item.task_id) : '-';
              const desc = item.wallet_transaction ? item.wallet_transaction.description : '-';
              itemsHtml += `
                <tr>
                  <td>${idx + 1}</td>
                  <td><span class="fw-semibold">${seq}</span></td>
                  <td>${taskNo !== '-' ? '<span class="badge bg-label-info">' + taskNo + '</span>' : '-'}</td>
                  <td>${desc}</td>
                  <td class="text-end fw-bold">${parseFloat(item.amount).toFixed(2)}</td>
                </tr>
              `;
            });
          } else {
            itemsHtml = '<tr><td colspan="5" class="text-center text-muted py-2">لا توجد بنود مرتبطة</td></tr>';
          }
          $('#viewInvoiceItemsBody').html(itemsHtml);

          $('#viewCustomerInvoiceModal').modal('show');
        }
      }).fail(function () {
        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: 'تعذر جلب تفاصيل الفاتورة.',
          customClass: { confirmButton: 'btn btn-primary' }
        });
      });
    });

    // Print from Details Modal
    $('#btnModalPrintInvoice').on('click', function () {
      if (currentViewingInvoiceId) {
        window.open(baseUrl + 'admin/customer-invoices/' + currentViewingInvoiceId + '/print', '_blank');
      }
    });

    // Pay Invoice Modal
    $(document).on('click', '.btn-pay-invoice', function () {
      const id = $(this).data('id');
      const number = $(this).data('number');
      const total = parseFloat($(this).data('total')).toFixed(2);
      const remaining = parseFloat($(this).data('remaining')).toFixed(2);

      $('#pay_invoice_id').val(id);
      $('#payInvoiceNumber').text(number);
      $('#payInvoiceTotal').text(total + ' ر.س');
      $('#payInvoiceRemaining').text(remaining + ' ر.س');
      $('#pay_amount').val(remaining).attr('max', remaining);
      $('#pay_date').val(new Date().toISOString().split('T')[0]);
      $('#pay_notes').val('');

      $('#payCustomerInvoiceModal').modal('show');
    });

    // Submit Payment
    $('#formPayCustomerInvoice').on('submit', function (e) {
      e.preventDefault();
      const id = $('#pay_invoice_id').val();
      const submitBtn = $('#btnSubmitPayment');
      submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> جاري التسجيل...');

      $.post(baseUrl + 'admin/customer-invoices/' + id + '/pay', $(this).serialize(), function (res) {
        submitBtn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> تأكيد السداد');
        if (res.status === 1) {
          $('#payCustomerInvoiceModal').modal('hide');
          Swal.fire({
            icon: 'success',
            title: 'تم بنجاح',
            text: res.success || 'تم تسجيل سداد الفاتورة بنجاح.',
            customClass: { confirmButton: 'btn btn-primary' }
          });
          if (dt_invoices) dt_invoices.ajax.reload();
          if (dt_data) dt_data.ajax.reload();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: res.error || 'فشل تسجيل السداد',
            customClass: { confirmButton: 'btn btn-primary' }
          });
        }
      }).fail(function (xhr) {
        submitBtn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> تأكيد السداد');
        let msg = 'حدث خطأ أثناء السداد';
        if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
        Swal.fire({ icon: 'error', title: 'خطأ', text: msg, customClass: { confirmButton: 'btn btn-primary' } });
      });
    });

    // Approve Invoice
    $(document).on('click', '.btn-approve-invoice', function () {
      const id = $(this).data('id');
      const number = $(this).data('number');

      Swal.fire({
        title: 'الاعتماد النهائي للفاتورة',
        text: `هل أنت متأكد من اعتماد الفاتورة (${number}) بشكل نهائي؟ لن يمكن تعديلها بعد ذلك.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، اعتماد الفاتورة',
        cancelButtonText: 'إلغاء',
        customClass: {
          confirmButton: 'btn btn-primary me-2',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
      }).then(function (result) {
        if (result.isConfirmed) {
          $.post(baseUrl + 'admin/customer-invoices/' + id + '/approve', function (res) {
            if (res.status === 1) {
              Swal.fire({
                icon: 'success',
                title: 'تم الاعتماد',
                text: res.success || 'تم الاعتماد النهائي للفاتورة بنجاح.',
                customClass: { confirmButton: 'btn btn-primary' }
              });
              if (dt_invoices) dt_invoices.ajax.reload();
              if (dt_data) dt_data.ajax.reload();
            } else {
              Swal.fire({ icon: 'error', title: 'خطأ', text: res.error || 'فشل الاعتماد', customClass: { confirmButton: 'btn btn-primary' } });
            }
          }).fail(function (xhr) {
            let msg = 'تعذر اعتماد الفاتورة';
            if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
            Swal.fire({ icon: 'error', title: 'خطأ', text: msg, customClass: { confirmButton: 'btn btn-primary' } });
          });
        }
      });
    });

    // Cancel Invoice
    $(document).on('click', '.btn-cancel-invoice', function () {
      const id = $(this).data('id');
      const number = $(this).data('number');

      Swal.fire({
        title: 'إلغاء الفاتورة وفك الحركات',
        text: `هل أنت متأكد من إلغاء الفاتورة (${number})؟ سيتم فك ارتباط الحركات وإعادة تاريخ الاستحقاق السابق لكل حركة.`,
        input: 'text',
        inputPlaceholder: 'سبب الإلغاء (اختياري)...',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، إلغاء الفاتورة',
        cancelButtonText: 'تراجع',
        customClass: {
          confirmButton: 'btn btn-danger me-2',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
      }).then(function (result) {
        if (result.isConfirmed) {
          $.post(baseUrl + 'admin/customer-invoices/' + id + '/cancel', { reason: result.value }, function (res) {
            if (res.status === 1) {
              Swal.fire({
                icon: 'success',
                title: 'تم الإلغاء',
                text: res.success || 'تم إلغاء الفاتورة وفك ارتباط الحركات بنجاح.',
                customClass: { confirmButton: 'btn btn-primary' }
              });
              if (dt_invoices) dt_invoices.ajax.reload();
              if (dt_data) dt_data.ajax.reload();
            } else {
              Swal.fire({ icon: 'error', title: 'خطأ', text: res.error || 'فشل الإلغاء', customClass: { confirmButton: 'btn btn-primary' } });
            }
          }).fail(function (xhr) {
            let msg = 'تعذر إلغاء الفاتورة';
            if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
            Swal.fire({ icon: 'error', title: 'خطأ', text: msg, customClass: { confirmButton: 'btn btn-primary' } });
          });
        }
      });
    });

    // Edit Invoice
    $(document).on('click', '.btn-edit-invoice', function () {
      const id = $(this).data('id');

      $.get(baseUrl + 'admin/customer-invoices/' + id, function (res) {
        if (res.status === 1) {
          const inv = res.invoice;
          $('#edit_invoice_id').val(inv.id);
          $('#editInvoiceNumberTitle').text(inv.invoice_number);
          $('#edit_accounting_reference_no').val(inv.accounting_reference_no || '');
          $('#edit_issue_date').val(inv.issue_date ? inv.issue_date.split('T')[0] : '');
          $('#edit_due_date').val(inv.due_date ? inv.due_date.split('T')[0] : '');
          $('#edit_notes').val(inv.notes || '');
          $('#edit_attachment').val('');

          if (res.attachment_url) {
            $('#editCurrentAttachmentPreview').html(`
              <a href="${res.attachment_url}" target="_blank" class="text-primary">
                <i class="ti ti-file me-1"></i> المرفق الحالي
              </a>
            `);
          } else {
            $('#editCurrentAttachmentPreview').html('<span class="text-muted">لا يوجد مرفق حالي</span>');
          }

          $('#editCustomerInvoiceModal').modal('show');
        }
      });
    });

    // Submit Edit Invoice
    $('#formEditCustomerInvoice').on('submit', function (e) {
      e.preventDefault();
      const id = $('#edit_invoice_id').val();
      const submitBtn = $('#btnSubmitEditInvoice');
      submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> جاري الحفظ وتحديث الاستحقاق...');

      const formData = new FormData(this);

      $.ajax({
        url: baseUrl + 'admin/customer-invoices/' + id + '/update',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function (res) {
          submitBtn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> حفظ التعديلات');
          if (res.status === 1) {
            $('#editCustomerInvoiceModal').modal('hide');
            Swal.fire({
              icon: 'success',
              title: 'تم التعديل',
              text: res.success || 'تم تحديث الفاتورة وتعميم تاريخ الاستحقاق بنجاح.',
              customClass: { confirmButton: 'btn btn-primary' }
            });
            if (dt_invoices) dt_invoices.ajax.reload();
            if (dt_data) dt_data.ajax.reload();
          } else {
            Swal.fire({ icon: 'error', title: 'خطأ', text: res.error || 'فشل التعديل', customClass: { confirmButton: 'btn btn-primary' } });
          }
        },
        error: function (xhr) {
          submitBtn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> حفظ التعديلات');
          let msg = 'حدث خطأ أثناء التعديل';
          if (xhr.responseJSON && xhr.responseJSON.error) msg = xhr.responseJSON.error;
          Swal.fire({ icon: 'error', title: 'خطأ', text: msg, customClass: { confirmButton: 'btn btn-primary' } });
        }
      });
    });
  }

});
