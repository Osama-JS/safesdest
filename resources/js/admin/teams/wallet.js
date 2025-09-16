/**
 * Page User List
 */

'use strict';
import { deleteRecord, showFormModal } from '../../ajax';

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
        },

        {
          targets: 8,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `
              <div class="text-end">
                ${
                  full.task !== ''
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

    $.get(`${baseUrl}admin/teams/wallet/transaction/edit/${id}`, function (data) {
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
    let url = baseUrl + 'admin/teams/wallet/transaction/delete/' + $(this).data('id');
    deleteRecord('Transaction : #' + $(this).data('name'), url);
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $(this).find('form')[0].reset();
    $('.text-error').html('');
    $('#image').attr('src', baseUrl + 'assets/img/placeholder.jpg');

    $('#trans_id').val('');
    $('#modelTitle').html('Add New Transaction');
  });

  // Initialize wallet stats cards
  initWalletStatsCards();
});

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

// ==================== TEAM PAYMENT REQUEST FUNCTIONS ====================

/**
 * Show team payment request modal (similar to driver wallet)
 */
window.showTeamPaymentRequestOptions = function () {
  // Get team wallet data first
  $.ajax({
    url: baseUrl + 'admin/teams/wallet/check-team-leader',
    method: 'GET',
    data: { team_wallet_id: walletId },
    success: function (response) {
      if (response.status === 1) {
        const teamLeader = response.teamLeader;
        const teamWallet = response.teamWallet;
        const team = response.team;

        // Populate team wallet information (left side)
        $('#teamPaymentRequestWalletId').text('#' + walletId);
        $('#teamWalletInfoId').text('#' + walletId);
        $('#teamWalletInfoAmount').text(formatWalletNumber(teamWallet.balance) + ' ريال');
        $('#teamWalletInfoName').text(team.name);
        $('#teamWalletInfoLeader').text(teamLeader.name);
        $('#teamWalletInfoLeaderEmail').text(teamLeader.email || '-');

        // Set hidden input
        $('#teamPaymentRequestWalletIdInput').val(walletId);

        // Set max amount
        $('#teamMaxAmount').text(formatWalletNumber(teamWallet.balance) + ' ريال');

        // Pre-fill bank details if available
        if (teamLeader.bank_name) {
          $('#teamBankName').val(teamLeader.bank_name);
        }
        if (teamLeader.account_number) {
          $('#teamAccountNumber').val(teamLeader.account_number);
        }
        if (teamLeader.iban_number) {
          $('#teamIbanNumber').val(teamLeader.iban_number);
        }

        // Store team data for later use (exactly like driver wallet structure)
        $('#teamPaymentRequestModal').data('teamWalletData', {
          teamWallet: {
            id: teamWallet.id,
            balance: parseFloat(teamWallet.balance)
          },
          teamLeader: teamLeader,
          team: team
        });

        // Show the payment request modal
        $('#teamPaymentRequestModal').modal('show');
      } else {
        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: response.error || 'لا يمكن إنشاء طلب سحب نقدي',
          confirmButtonText: 'موافق'
        });
      }
    },
    error: function () {
      Swal.fire({
        icon: 'error',
        title: 'خطأ',
        text: 'حدث خطأ في التحقق من بيانات الفريق',
        confirmButtonText: 'موافق'
      });
    }
  });
};

/**
 * Handle team payment request form submission (exactly like driver wallet)
 */
$(document).on('click', '#generateTeamPaymentRequest', function () {
  const form = $('#teamPaymentRequestForm');
  const teamWalletData = $('#teamPaymentRequestModal').data('teamWalletData');

  // Validate form
  const requestedAmount = parseFloat($('#teamRequestedAmount').val());
  let bankName = $('#teamBankName').val().trim();
  const customBankName = $('#teamCustomBankName').val().trim();
  const accountNumber = $('#teamAccountNumber').val().trim();
  const ibanNumber = $('#teamIbanNumber').val().trim();
  const notes = $('#teamNotes').val();

  // Use custom bank name if "other" is selected
  if (bankName === 'other') {
    bankName = customBankName;
  }

  // Clear previous errors
  $('.text-error').text('');

  let hasErrors = false;

  if (!requestedAmount || requestedAmount <= 0) {
    $('.team_requested_amount-error').text('المبلغ المطلوب مطلوب ويجب أن يكون أكبر من صفر');
    hasErrors = true;
  }

  if (requestedAmount > teamWalletData.teamWallet.balance) {
    $('.team_requested_amount-error').text(
      `المبلغ المطلوب لا يمكن أن يكون أكبر من رصيد المحفظة (${teamWalletData.teamWallet.balance.toFixed(2)} ريال)`
    );
    hasErrors = true;
  }

  if (!bankName || bankName.length < 2) {
    if ($('#teamBankName').val() === 'other') {
      $('.team_bank_name-error').text('يرجى إدخال اسم البنك في الحقل المخصص');
    } else {
      $('.team_bank_name-error').text('يرجى اختيار البنك');
    }
    hasErrors = true;
  }

  if (!accountNumber || accountNumber.length < 8) {
    $('.team_account_number-error').text('رقم الحساب مطلوب ويجب أن يكون على الأقل 8 أرقام');
    hasErrors = true;
  }

  if (!ibanNumber || ibanNumber.replace(/\s/g, '').length < 15) {
    $('.team_iban_number-error').text('رقم الآيبان مطلوب ويجب أن يكون صحيحاً (على الأقل 15 رقم)');
    hasErrors = true;
  }

  // Validate IBAN format (basic validation)
  if (ibanNumber && !ibanNumber.replace(/\s/g, '').match(/^SA\d{22}$/)) {
    $('.team_iban_number-error').text('تنسيق رقم الآيبان غير صحيح (يجب أن يبدأ بـ SA ويتبعه 22 رقم)');
    hasErrors = true;
  }

  if (hasErrors) {
    return;
  }
  console.log(teamWalletData);

  // Generate payment request document directly (like driver wallet)
  generateTeamPaymentRequestDocument({
    teamWalletId: teamWalletData.teamWallet.id,
    requestedAmount: requestedAmount,
    bankName: bankName,
    accountNumber: accountNumber,
    ibanNumber: ibanNumber,
    notes: notes,
    teamWalletData: teamWalletData
  });
});

// Validate requested amount in real-time
$(document).on('input', '#teamRequestedAmount', function () {
  const teamWalletData = $('#teamPaymentRequestModal').data('teamWalletData');

  const value = parseFloat($(this).val());
  const maxAmount = teamWalletData.teamWallet.balance;
  console.log(maxAmount);

  if (value > maxAmount) {
    $('.team_requested_amount-error').text(`المبلغ لا يمكن أن يكون أكبر من ${maxAmount.toFixed(2)} ريال`);
  } else {
    $('.team_requested_amount-error').text('');
  }
});

/**
 * Function to generate team payment request document (exactly like driver wallet)
 */
function generateTeamPaymentRequestDocument(data) {
  console.log(data);
  const today = new Date();
  const formattedDate = today.toLocaleDateString('ar-SA');
  const remainingAmount = data.teamWalletData.teamWallet.balance - data.requestedAmount;
  const recipientName = data.teamWalletData.teamLeader.name;
  const teamName = data.teamWalletData.team.name;
  const user = data.teamWalletData.team.user;

  // Generate reference number: TeamWalletID + Date (YYYYMMDD) + Random 3 digits
  const dateString =
    today.getFullYear().toString() +
    (today.getMonth() + 1).toString().padStart(2, '0') +
    today.getDate().toString().padStart(2, '0');
  const randomNumber = Math.floor(Math.random() * 900) + 100; // 3-digit random number
  const referenceNumber = `TW${data.teamWalletId}${dateString}${randomNumber}`;

  // Create the HTML document (matching driver wallet structure)
  const documentHTML = `
    <!DOCTYPE html>
    <html dir="rtl" lang="ar">
    <head>
        <meta charset="UTF-8">
        <title>طلب سداد - محفظة الفريق - ${referenceNumber}</title>
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
                <h2>طلب سداد مالي - محفظة الفريق</h2>
                <p>رقم الطلب: TR-${data.teamWalletId}-${Date.now()}</p>
                <p>التاريخ: ${formattedDate}</p>
            </div>

            <!-- Employee -->
            <p class="emp-name">
                اسم الموظف طالب السداد : <strong> ${user}</strong>
            </p>

            <h3>بيانات السداد</h3>
            <!-- Amount -->
            <div class="amount-box">
                مبلغ السداد: ${data.requestedAmount.toFixed(2)} ريال سعودي
            </div>
            <div>
                <p class="amount-details">
                السداد:
                دفعة <span>${data.requestedAmount.toFixed(2)} ريال </span>
                باقي حساب <span> ${remainingAmount.toFixed(2)} ريال </span>
                إجمالي الحساب <span>${data.teamWalletData.teamWallet.balance.toFixed(2)} ريال </span>
                </p>
            </div>

            <!-- Bank Info -->
            <h3>بيانات البنك</h3>
            <table>
                <tr><td class="label">اسم البنك</td><td>${data.bankName}</td></tr>
                <tr><td class="label">رقم الحساب</td><td>${data.accountNumber}</td></tr>
                <tr><td class="label">رقم الآيبان</td><td>${data.ibanNumber}</td></tr>
            </table>

            <!-- Team Info -->
            <h3>بيانات الفريق</h3>
            <table>
                <tr><td class="label">اسم الفريق</td><td>${teamName}</td></tr>
                <tr><td class="label">رئيس الفريق</td><td>${recipientName}</td></tr>
                <tr><td class="label">رقم المحفظة</td><td>#${data.teamWalletId}</td></tr>
                <tr><td class="label">الرصيد المتبقي</td><td> ${remainingAmount.toFixed(2)} ريال</td></tr>
            </table>
            <h3>ملاحظات</h3>
            <p> <strong>${data.notes || 'لا توجد ملاحظات'}</strong> </p>

            <!-- Signatures -->
            <h3>التوقيع</h3>
            <br><br><br>
            <!-- Footer -->
            <div class="footer">
                <p>تم إنشاء المستند إلكترونياً بتاريخ ${formattedDate}</p>
                <p>أنشأ من قبل: ${user}</p>
            </div>
        </div>
    </body>
    </html>
  `;

  // Open print window and print (exactly like driver wallet)
  const printWindow = window.open('', '_blank', 'width=800,height=600');
  printWindow.document.write(documentHTML);
  printWindow.document.close();

  // Handle print events (exactly like driver wallet)
  printWindow.addEventListener('beforeprint', function () {
    console.log('Print dialog opened');
  });

  printWindow.addEventListener('afterprint', function () {
    console.log('Print dialog closed - logging team payment request');

    // Log the payment request after actual printing
    logTeamPaymentRequest({
      teamWalletId: data.teamWalletId,
      amount: data.requestedAmount,
      paymentRequestNumber: referenceNumber,
      notes: data.notes || null
    });

    printWindow.close();
  });

  // Auto-focus and print
  setTimeout(() => {
    printWindow.focus();
    printWindow.print();
  }, 500);

  // Fallback: if user cancels print, still close the window
  setTimeout(() => {
    if (!printWindow.closed) {
      printWindow.close();
    }
  }, 1000);

  // Close modal after printing (exactly like driver wallet)
  setTimeout(() => {
    $('#teamPaymentRequestModal').modal('hide');
    $('#teamPaymentRequestForm')[0].reset();
  }, 1000);
}

// Function to log team payment request after printing (exactly like driver wallet)
function logTeamPaymentRequest(data) {
  $.ajax({
    url: `${baseUrl}admin/teams/wallet/log-payment-request`,
    method: 'POST',
    data: {
      amount: data.amount,
      payment_request_number: data.paymentRequestNumber,
      notes: data.notes,
      team_wallet_id: data.teamWalletId,
      _token: $('meta[name="csrf-token"]').attr('content')
    },
    success: function (response) {
      if (response.status === 1) {
        console.log('Team payment request logged successfully:', response.log_id);
        // Refresh payment logs if visible
        if ($('#team-payment-logs-section').is(':visible')) {
          loadTeamPaymentRequestLogs();
        }
      } else {
        console.error('Failed to log team payment request:', response.error);
      }
    },
    error: function (xhr, status, error) {
      console.error('Error logging team payment request:', error);
    }
  });
}

/**
 * Load team payment request logs
 */
window.loadTeamPaymentRequestLogs = function () {
  const container = $('#teamPaymentLogsContainer');

  // Show loading
  container.html(`
    <div class="text-center py-4">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">${__('Loading')}...</span>
      </div>
      <p class="mt-2 text-muted">${__('Loading payment request logs')}...</p>
    </div>
  `);

  $.ajax({
    url: baseUrl + 'admin/teams/wallet/payment-request-logs',
    method: 'GET',
    data: { team_wallet_id: walletId },
    success: function (response) {
      if (response.status === 1) {
        if (response.data && response.data.length > 0) {
          let logsHtml = '';
          response.data.forEach((log, index) => {
            logsHtml += `
                    <div class="card mb-3">
                      <div class="card-body">
                        <div class="row">
                          <div class="col-md-2">
                            <small class="text-muted">${__('Printing Date')}</small>
                            <div class="fw-semibold">${log.printed_at}</div>
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
                            <small class="text-muted">${__('Team manager')}</small>
                            <div class="fw-semibold">${log.team_leader_name}</div>
                          </div>
                          <div class="col-md-2">
                            <small class="text-muted">${__('User')}</small>
                            <div class="fw-semibold">${log.user_name}</div>
                          </div>
                          <div class="col-md-2">
                            <small class="text-muted">IP</small>
                            <div class="fw-semibold">${log.ip_address}</div>
                          </div>
                        </div>
                        <div class="row mt-2">
                          <div class="col-md-12">
                            <small class="text-muted">${__('Notes')}</small>
                            <div class="fw-semibold">${log.notes || 'لا توجد ملاحظات'}</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  `;
          });

          // logsHtml += '</tbody></table></div>';
          container.html(logsHtml);
        } else {
          container.html(`
            <div class="text-center py-4">
              <i class="ti ti-file-x text-muted" style="font-size: 3rem;"></i>
              <p class="mt-2 text-muted">لا توجد سجلات طلبات سحب نقدي</p>
            </div>
          `);
        }
      } else {
        console.log(response.error);

        container.html(`
          <div class="text-center py-4">
            <i class="ti ti-alert-circle text-danger" style="font-size: 3rem;"></i>
            <p class="mt-2 text-danger">خطأ في تحميل السجلات</p>
            <button class="btn btn-sm btn-outline-primary" onclick="loadTeamPaymentRequestLogs()">
              <i class="ti ti-refresh me-1"></i>إعادة المحاولة
            </button>
          </div>
        `);
      }
    },
    error: function () {
      console.error('Error loading team payment request logs: ' + response.data);
      container.html(`
        <div class="text-center py-4">
          <i class="ti ti-wifi-off text-danger" style="font-size: 3rem;"></i>
          <p class="mt-2 text-danger">خطأ في الاتصال بالخادم</p>
          <button class="btn btn-sm btn-outline-primary" onclick="loadTeamPaymentRequestLogs()">
            <i class="ti ti-refresh me-1"></i>إعادة المحاولة
          </button>
        </div>
      `);
    }
  });
};

// Load logs when page loads
$(document).ready(function () {
  if (typeof walletId !== 'undefined') {
    loadTeamPaymentRequestLogs();
  }
});
