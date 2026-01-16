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
          orderable: false,
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
            // Print Receipt button for credit transactions
            const printReceiptBtn =
              full.type === 'credit'
                ? `<a href="${baseUrl}admin/wallets/transactions/${full.id}/receipt" target="_blank" class="btn btn-sm btn-icon btn-success" title="Print Receipt">
                  <i class="ti ti-printer"></i>
                </a>`
                : '';

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

    let tasksHtml = data.selectedTasks
      .map(task => `مهمة #${task.id} - ${task.pickup_address} - ${task.total_price} ريال`)
      .join(' , ');
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
        <tr><td class="label">رقم الآيبان</td><td>${data.ibanNumber || 'غير محدد'}</td></tr>
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
                text: `مهمة #${task.id}`,
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
        <div class="task-option">
          <div class="fw-bold">مهمة #${task.id}</div>
          <div class="text-muted small">${task.pickup_address ?? ''}</div>
          <div class="text-primary small">${task.total_price} ريال - ${task.status}</div>
        </div>
      `);
      },
      templateSelection: task => task.text
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
});
