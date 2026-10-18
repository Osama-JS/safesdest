/**
 * Customer Accounting Invoices Management JS
 */

'use strict';

$(function () {
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  const canViewInvoices = typeof canViewCustomerInvoices !== 'undefined' ? canViewCustomerInvoices : true;
  const canEditInvoices = typeof canEditCustomerInvoices !== 'undefined' ? canEditCustomerInvoices : true;
  const canForceEditInvoices = typeof canForceEditCustomerInvoices !== 'undefined' ? canForceEditCustomerInvoices : true;
  const canPayInvoices = typeof canPayCustomerInvoices !== 'undefined' ? canPayCustomerInvoices : true;
  const canApproveInvoices = typeof canApproveCustomerInvoices !== 'undefined' ? canApproveCustomerInvoices : true;
  const canCancelInvoices = typeof canCancelCustomerInvoices !== 'undefined' ? canCancelCustomerInvoices : true;

  const tableElement = $('#allCustomerInvoicesTable');
  let dt_invoices = null;

  // Initialize Select2 on filters
  if ($('#filter_customer_id').length) {
    $('#filter_customer_id').select2({
      placeholder: 'اختر العميل...',
      allowClear: true,
      width: '100%'
    });
  }

  // Number Formatter helper
  function formatMoney(amount) {
    return parseFloat(amount || 0).toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  // 1. Fetch & update Top Statistics Cards dynamically
  function updateStatistics() {
    const customerId = $('#filter_customer_id').val();
    $.get(invoicesStatsUrl, { customer_id: customerId }, function (res) {
      if (res && res.status === 1) {
        const s = res.stats;

        // Cards
        $('#statTotalAmount').html(formatMoney(s.total_amount) + ' <small class="fs-tiny">ر.س</small>');
        $('#statTotalCount').text(s.total_count);

        $('#statOverdueAmount').html(formatMoney(s.overdue_amount) + ' <small class="fs-tiny">ر.س</small>');
        $('#statOverdueCount').text(s.overdue_count);

        $('#statExpiringSoonAmount').html(formatMoney(s.expiring_soon_amount) + ' <small class="fs-tiny">ر.س</small>');
        $('#statExpiringSoonCount').text(s.expiring_soon_count);

        const remainingAmt = s.remaining_amount !== undefined ? s.remaining_amount : (s.total_remaining || 0);
        const remainingCnt = s.remaining_count !== undefined ? s.remaining_count : 0;
        $('#statRemainingAmount').html(formatMoney(remainingAmt) + ' <small class="fs-tiny">ر.س</small>');
        $('#statRemainingCount').text(remainingCnt);

        $('#statUpcomingAmount').html(formatMoney(s.upcoming_amount) + ' <small class="fs-tiny">ر.س</small>');
        $('#statUpcomingCount').text(s.upcoming_count);

        $('#statTotalPaid').html(formatMoney(s.total_paid) + ' <small class="fs-tiny">ر.س</small>');
        $('#statPaidCount').text(s.paid_approved_count);

        let hasAnyAlert = false;

        // Alerts Banners
        if (s.overdue_count > 0) {
          $('#alertOverdueCount').text(s.overdue_count);
          $('#alertOverdueAmount').text(formatMoney(s.overdue_amount) + ' ر.س');
          $('#alertOverdueContainer').show();
          hasAnyAlert = true;
        } else {
          $('#alertOverdueContainer').hide();
        }

        if (s.expiring_soon_count > 0) {
          $('#alertExpiringCount').text(s.expiring_soon_count);
          $('#alertExpiringAmount').text(formatMoney(s.expiring_soon_amount) + ' ر.س');
          $('#alertExpiringSoonContainer').show();
          hasAnyAlert = true;
        } else {
          $('#alertExpiringSoonContainer').hide();
        }

        if (s.due_today_count > 0) {
          $('#alertDueTodayCount').text(s.due_today_count);
          $('#alertDueTodayAmount').text(formatMoney(s.due_today_amount) + ' ر.س');
          $('#alertDueTodayContainer').show();
          hasAnyAlert = true;
        } else {
          $('#alertDueTodayContainer').hide();
        }

        if (hasAnyAlert) {
          $('#invoicesAlertsContainer').show();
        } else {
          $('#invoicesAlertsContainer').hide();
        }

        // Quick Pills Counts
        $('#pillTotalCount').text(s.total_count);
        $('#pillOverdueCount').text(s.overdue_count);
        $('#pillExpiringSoonCount').text(s.expiring_soon_count);
        $('#pillDueTodayCount').text(s.due_today_count);
        $('#pillUnpaidCount').text(s.unpaid_count);
        $('#pillApprovedCount').text(s.approved_count);
        $('#pillPaidCount').text(s.paid_approved_count);
      }
    });
  }

  // 2. Initialize DataTable
  if (tableElement.length) {
    dt_invoices = tableElement.DataTable({
      processing: true,
      serverSide: true,
      order: [[0, 'desc']],
      dom: '<"row p-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row p-3"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      ajax: {
        url: allInvoicesDataUrl,
        data: function (d) {
          d.customer_id     = $('#filter_customer_id').val();
          d.due_status      = $('#filter_due_status').val();
          d.status          = $('#filter_status').val();
          d.due_date_from   = $('#filter_due_date_from').val();
          d.due_date_to     = $('#filter_due_date_to').val();
          d.issue_date_from = $('#filter_issue_date_from').val();
          d.issue_date_to   = $('#filter_issue_date_to').val();
        }
      },
      columns: [
        { data: 'id', name: 'id' },
        {
          data: 'invoice_number',
          name: 'invoice_number',
          render: function (data, type, row) {
            let refHtml = row.accounting_reference_no && row.accounting_reference_no !== '-'
              ? `<small class="text-muted d-block"><i class="ti ti-hash ti-xs"></i> مرجع: ${row.accounting_reference_no}</small>`
              : '';
            return `
              <div>
                <a href="javascript:void(0);" class="fw-bold text-primary btn-view-invoice" data-id="${row.id}">
                  ${data}
                </a>
                ${refHtml}
              </div>
            `;
          }
        },
        {
          data: 'customer',
          name: 'customer.name',
          render: function (data, type, row) {
            const name = data && data.name ? data.name : 'غير محدد';
            const phone = data && data.phone ? `<small class="text-muted d-block">${data.phone}</small>` : '';
            return `
              <div>
                <a href="${row.wallet_url}" class="fw-bold text-dark text-truncate d-block" target="_blank" title="الانتقال إلى محفظة العميل">
                  <i class="ti ti-user me-1 text-secondary"></i> ${name}
                </a>
                <div class="d-flex align-items-center gap-1 mt-1">
                  <span class="badge bg-label-secondary font-monospace">محفظة #${row.wallet_id}</span>
                  ${phone}
                </div>
              </div>
            `;
          }
        },
        {
          data: 'issue_date',
          name: 'issue_date',
          render: function (data) {
            return `<span class="text-nowrap">${data || '-'}</span>`;
          }
        },
        {
          data: 'due_date',
          name: 'due_date',
          render: function (data, type, row) {
            let badgeClass = 'badge-upcoming';
            let iconClass = 'ti-clock';

            if (row.due_status_type === 'overdue') {
              badgeClass = 'badge-overdue';
              iconClass = 'ti-alert-triangle';
            } else if (row.due_status_type === 'expiring_soon') {
              badgeClass = 'badge-expiring-soon';
              iconClass = 'ti-hourglass-empty';
            } else if (row.due_status_type === 'today') {
              badgeClass = 'badge-today';
              iconClass = 'ti-bell';
            } else if (row.due_status_type === 'settled') {
              badgeClass = 'badge-settled';
              iconClass = 'ti-circle-check';
            } else if (row.due_status_type === 'cancelled') {
              badgeClass = 'bg-label-secondary';
              iconClass = 'ti-x';
            }

            return `
              <div>
                <span class="fw-bold text-nowrap d-block">${data || '-'}</span>
                <span class="badge ${badgeClass} d-inline-flex align-items-center gap-1 mt-1 text-nowrap">
                  <i class="ti ${iconClass} ti-xs"></i> ${row.due_status_label}
                </span>
              </div>
            `;
          }
        },
        {
          data: 'total_amount',
          name: 'total_amount',
          render: function (data) {
            return `
              <div class="fw-bold text-dark fs-6 text-nowrap">
                ${formatMoney(data)} <small class="text-muted fs-tiny">ر.س</small>
              </div>
            `;
          }
        },
        {
          data: 'remaining_amount',
          render: function (data, type, row) {
            const percentage = row.payment_percentage || 0;
            const progressColor = percentage >= 100 ? 'bg-success' : (percentage > 0 ? 'bg-info' : 'bg-secondary');

            return `
              <div style="min-width: 130px;">
                <div class="d-flex justify-content-between text-nowrap small mb-1">
                  <span class="text-success fw-bold">${formatMoney(row.paid_amount)}</span>
                  <span class="text-danger fw-bold">${formatMoney(row.remaining_amount)}</span>
                </div>
                <div class="progress progress-sm" title="نسبة السداد: ${percentage}%">
                  <div class="progress-bar ${progressColor}" role="progressbar" style="width: ${percentage}%"></div>
                </div>
                <div class="d-flex justify-content-between text-muted fs-tiny mt-1">
                  <span>مدفوع</span>
                  <span>متبقي</span>
                </div>
              </div>
            `;
          }
        },
        {
          data: 'status',
          name: 'status',
          render: function (data) {
            const statusMap = {
              unpaid: { title: 'بانتظار الاعتماد', class: 'bg-label-warning' },
              approved: { title: 'معتمدة (بانتظار السداد)', class: 'bg-label-info' },
              paid: { title: 'مسددة ومقفلة نهائياً', class: 'bg-label-success' },
              cancelled: { title: 'ملغاة', class: 'bg-label-secondary' }
            };
            const s = statusMap[data] || { title: data, class: 'bg-label-dark' };
            return `<span class="badge ${s.class}">${s.title}</span>`;
          }
        },
        {
          data: 'attachment_url',
          orderable: false,
          searchable: false,
          render: function (data, type, row) {
            let html = '';
            if (row.attachment_url) {
              html += `
                <button type="button" class="btn btn-icon btn-sm btn-label-primary btn-preview-attachment me-1"
                        data-url="${row.attachment_url}" data-title="${row.invoice_number}" title="ملف الفاتورة">
                  <i class="ti ti-file-text ti-sm"></i>
                </button>
              `;
            }
            if (row.payment_receipt_url) {
              html += `
                <button type="button" class="btn btn-icon btn-sm btn-label-success btn-preview-attachment"
                        data-url="${row.payment_receipt_url}" data-title="إيصال سداد ${row.invoice_number}" title="إيصال السداد">
                  <i class="ti ti-receipt ti-sm"></i>
                </button>
              `;
            }
            return html || '<span class="text-muted">-</span>';
          }
        },
        {
          data: null,
          orderable: false,
          searchable: false,
          className: 'text-end',
          render: function (data, type, row) {
            const p = row.permissions || {};
            let actionsHtml = `
              <div class="d-inline-block">
                <button class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                  <i class="ti ti-dots-vertical"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                  <a href="javascript:void(0);" class="dropdown-item btn-view-invoice" data-id="${row.id}">
                    <i class="ti ti-eye me-2 text-primary"></i> عرض التفاصيل
                  </a>
                  <a href="${row.wallet_url}" target="_blank" class="dropdown-item">
                    <i class="ti ti-wallet me-2 text-secondary"></i> محفظة العميل
                  </a>
            `;

            if (p.can_pay) {
              actionsHtml += `
                <a href="javascript:void(0);" class="dropdown-item btn-pay-invoice text-success"
                   data-id="${row.id}" data-number="${row.invoice_number}"
                   data-customer="${row.customer ? row.customer.name : ''}"
                   data-total="${row.total_amount}" data-remaining="${row.remaining_amount}">
                  <i class="ti ti-cash me-2"></i> تسجيل سداد
                </a>
              `;
            }

            if (p.can_approve) {
              actionsHtml += `
                <a href="javascript:void(0);" class="dropdown-item btn-approve-invoice text-primary"
                   data-id="${row.id}" data-number="${row.invoice_number}">
                  <i class="ti ti-check me-2"></i> الاعتماد النهائي
                </a>
              `;
            }

            if (p.can_edit) {
              actionsHtml += `
                <a href="javascript:void(0);" class="dropdown-item btn-edit-invoice text-warning"
                   data-id="${row.id}">
                  <i class="ti ti-edit me-2"></i> تعديل الفاتورة
                </a>
              `;
            }

            if (p.can_force_edit) {
              actionsHtml += `
                <a href="javascript:void(0);" class="dropdown-item btn-force-edit-invoice text-danger fw-semibold"
                   data-id="${row.id}" data-number="${row.invoice_number}">
                  <i class="ti ti-shield-bolt me-2 text-danger"></i> تعديل بالقوة على الفاتورة
                </a>
              `;
            }

            if (p.can_cancel) {
              actionsHtml += `
                <div class="dropdown-divider"></div>
                <a href="javascript:void(0);" class="dropdown-item btn-cancel-invoice text-danger"
                   data-id="${row.id}" data-number="${row.invoice_number}">
                  <i class="ti ti-trash me-2"></i> إلغاء الفاتورة
                </a>
              `;
            }

            actionsHtml += `
                </div>
              </div>
            `;
            return actionsHtml;
          }
        }
      ],
      language: {
        search: "بحث:",
        lengthMenu: "عرض _MENU_ مدخلات",
        info: "عرض _START_ إلى _END_ من أصل _TOTAL_ مدخل",
        infoEmpty: "لا توجد سجلات متاحة",
        infoFiltered: "(تصفية من _MAX_ إجمالي المدخلات)",
        zeroRecords: "لم يتم العثور على أي فواتير مطابقة",
        emptyTable: "لا توجد فواتير محاسبية مسجلة",
        paginate: {
          first: "الأول",
          previous: "السابق",
          next: "التالي",
          last: "الأخير"
        }
      }
    });
  }

  // 3. Filter Event Listeners
  $('#filter_customer_id, #filter_due_status, #filter_status, #filter_due_date_from, #filter_due_date_to, #filter_issue_date_from, #filter_issue_date_to')
    .on('change', function () {
      if (dt_invoices) {
        dt_invoices.ajax.reload();
      }
      updateStatistics();
    });

  // Reset Filters button
  $('#btnResetFilters').on('click', function () {
    $('#filter_customer_id').val('all').trigger('change');
    $('#filter_due_status').val('all');
    $('#filter_status').val('all');
    $('#filter_due_date_from').val('');
    $('#filter_due_date_to').val('');
    if ($('#filter_issue_date_from').length) $('#filter_issue_date_from').val('');
    if ($('#filter_issue_date_to').length) $('#filter_issue_date_to').val('');

    if (dt_invoices) {
      dt_invoices.ajax.reload();
    }
    updateStatistics();
  });

  // Refresh Button
  $('#btnRefreshStatsAndTable').on('click', function () {
    if (dt_invoices) {
      dt_invoices.ajax.reload();
    }
    updateStatistics();
  });

  // Quick Filters Click Handler (Alerts, Statistics Cards, Quick Summary Pills)
  $(document).on('click', '.btn-filter-quick, .card-filter-quick', function () {
    const dueStatus = $(this).data('due-status');
    const status = $(this).data('status');

    if (dueStatus !== undefined) {
      $('#filter_due_status').val(dueStatus);
    }
    if (status !== undefined) {
      $('#filter_status').val(status);
    }

    if (dt_invoices) {
      dt_invoices.ajax.reload();
    }

    // Smooth scroll down to table
    if (tableElement.length) {
      $('html, body').animate({
        scrollTop: tableElement.offset().top - 120
      }, 300);
    }
  });

  // ─────────────────────────────────────────────────────────────────────────
  // 4. Action: View Invoice Details Modal
  // ─────────────────────────────────────────────────────────────────────────
  let activeInvoiceData = null;

  $(document).on('click', '.btn-view-invoice', function () {
    const id = $(this).data('id');
    $.get(invoiceBaseUrl + '/' + id, function (res) {
      if (res && res.status === 1) {
        const inv = res.invoice;
        activeInvoiceData = inv;

        $('#viewInvoiceNumber').text(inv.invoice_number);
        $('#viewInvoiceCustomer').text(inv.customer ? inv.customer.name : 'N/A');
        $('#viewInvoiceRef').text(inv.accounting_reference_no || '-');
        $('#viewInvoiceIssueDate').text(inv.issue_date ? inv.issue_date.split('T')[0] : '-');
        $('#viewInvoiceDueDate').text(inv.due_date ? inv.due_date.split('T')[0] : '-');
        $('#viewInvoiceTotal').text(formatMoney(inv.total_amount));
        $('#viewInvoicePaid').text(formatMoney(inv.paid_amount));
        $('#viewInvoiceRemaining').text(formatMoney(inv.remaining_amount));

        const walletLink = inv.wallet_id ? `${baseUrl}admin/wallets/transaction/show/${inv.wallet_id}` : '#';
        $('#viewInvoiceWalletLink').attr('href', walletLink);
        $('#viewInvoiceWalletText').text(`محفظة #${inv.wallet_id}`);

        const percentage = parseFloat(inv.total_amount) > 0
          ? Math.round((parseFloat(inv.paid_amount) / parseFloat(inv.total_amount)) * 100)
          : 0;
        $('#viewInvoicePercentage').text(percentage + '%');
        $('#viewInvoiceProgressBar').css('width', percentage + '%');

        // Status badge
        const statusMap = {
          unpaid: '<span class="badge bg-label-warning">بانتظار الاعتماد</span>',
          approved: '<span class="badge bg-label-info">معتمدة (بانتظار السداد)</span>',
          paid: '<span class="badge bg-label-success">مسددة ومقفلة نهائياً</span>',
          cancelled: '<span class="badge bg-label-secondary">ملغاة</span>'
        };
        $('#viewInvoiceStatusBadge').html(statusMap[inv.status] || inv.status);

        // Print Link
        if ($('#viewInvoicePrintBtn').length) {
          $('#viewInvoicePrintBtn').attr('href', `${invoiceBaseUrl}/${inv.id}/print`);
        }

        // Notes
        if (inv.notes) {
          $('#viewInvoiceNotes').text(inv.notes);
          $('#viewInvoiceNotesBox').show();
        } else {
          $('#viewInvoiceNotesBox').hide();
        }

        // Attachment
        if (res.attachment_url) {
          $('#viewInvoiceAttachmentBtn')
            .attr('data-url', res.attachment_url)
            .attr('data-title', inv.invoice_number)
            .removeClass('btn-preview-attachment')
            .addClass('btn-preview-attachment');
          $('#viewInvoiceAttachmentBox').show();
        } else {
          $('#viewInvoiceAttachmentBox').hide();
        }

        // Payment Receipt
        if (res.payment_receipt_url) {
          $('#viewInvoicePaymentReceiptBtn')
            .attr('data-url', res.payment_receipt_url)
            .attr('data-title', 'إيصال سداد ' + inv.invoice_number)
            .removeClass('btn-preview-attachment')
            .addClass('btn-preview-attachment');
          $('#viewInvoicePaymentReceiptBox').show();
        } else {
          $('#viewInvoicePaymentReceiptBox').hide();
        }

        // Audit
        $('#viewInvoiceCreator').text(inv.creator ? inv.creator.name : '-');
        $('#viewInvoiceCreatedAt').text(inv.created_at ? inv.created_at.replace('T', ' ').substring(0, 16) : '-');
        if (inv.approver) {
          $('#viewInvoiceApprover').text(`${inv.approver.name} (${inv.approved_at ? inv.approved_at.substring(0, 16) : ''})`);
          $('#viewInvoiceApproverBox').show();
        } else {
          $('#viewInvoiceApproverBox').hide();
        }

        // Items body
        let itemsHtml = '';
        if (inv.items && inv.items.length) {
          inv.items.forEach((item, index) => {
            const task = item.task || {};
            const tx = item.wallet_transaction || {};
            const taskCustom = task.custom_task_number || (task.id ? '#' + task.id : '-');
            const deliveryNumber = task.delivery_number || '-';
            const seq = tx.sequence || item.wallet_transaction_id;

            itemsHtml += `
              <tr>
                <td>${index + 1}</td>
                <td class="font-monospace fw-semibold">${seq}</td>
                <td><span class="badge bg-label-primary">${taskCustom}</span></td>
                <td><span class="badge bg-label-info">${deliveryNumber}</span></td>
                <td class="text-truncate" style="max-width: 250px;">${tx.description || '-'}</td>
                <td class="text-end fw-bold">${formatMoney(item.amount)}</td>
              </tr>
            `;
          });
        } else {
          itemsHtml = '<tr><td colspan="6" class="text-center text-muted py-3">لا توجد حركات مرتبطة</td></tr>';
        }
        $('#viewInvoiceItemsBody').html(itemsHtml);

        // Quick action buttons in modal:
        // 1. Pay button: ONLY if approved and remaining > 0
        if (canPayInvoices && inv.status === 'approved' && parseFloat(inv.remaining_amount) > 0) {
          $('#viewInvoiceQuickPayBtn').show();
        } else {
          $('#viewInvoiceQuickPayBtn').hide();
        }

        // 2. Approve button: ONLY if unpaid (awaiting approval)
        if (canApproveInvoices && inv.status === 'unpaid') {
          $('#viewInvoiceQuickApproveBtn').show();
        } else {
          $('#viewInvoiceQuickApproveBtn').hide();
        }

        // 3. Force Edit button: ONLY if approved and user has force edit permission
        if (canForceEditInvoices && inv.status === 'approved') {
          $('#viewInvoiceQuickForceEditBtn').show();
        } else {
          $('#viewInvoiceQuickForceEditBtn').hide();
        }

        $('#viewCustomerInvoiceModal').modal('show');
      } else {
        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: (res && res.error) || 'تعذر تحميل بيانات الفاتورة',
          customClass: { confirmButton: 'btn btn-primary' }
        });
      }
    }).fail(function () {
      Swal.fire({
        icon: 'error',
        title: 'خطأ',
        text: 'تعذر الاتصال بالخادم لجلب تفاصيل الفاتورة',
        customClass: { confirmButton: 'btn btn-primary' }
      });
    });
  });

  // ─────────────────────────────────────────────────────────────────────────
  // 5. Action: Pay Customer Invoice
  // ─────────────────────────────────────────────────────────────────────────
  $(document).on('click', '.btn-pay-invoice', function () {
    const id = $(this).data('id');
    const number = $(this).data('number');
    const customer = $(this).data('customer');
    const total = $(this).data('total');
    const remaining = $(this).data('remaining');

    openPayModal(id, number, customer, total, remaining);
  });

  $('#viewInvoiceQuickPayBtn').on('click', function () {
    if (!activeInvoiceData) return;
    $('#viewCustomerInvoiceModal').modal('hide');
    openPayModal(
      activeInvoiceData.id,
      activeInvoiceData.invoice_number,
      activeInvoiceData.customer ? activeInvoiceData.customer.name : '',
      activeInvoiceData.total_amount,
      activeInvoiceData.remaining_amount
    );
  });

  $('#viewInvoiceQuickForceEditBtn').on('click', function () {
    if (!activeInvoiceData) return;
    $('#viewCustomerInvoiceModal').modal('hide');
    openInvoiceEditModal(activeInvoiceData.id, true);
  });

  function openPayModal(id, number, customer, total, remaining) {
    $('#pay_invoice_id').val(id);
    $('#payInvoiceNumber').text(number);
    $('#payInvoiceCustomer').text(customer || 'N/A');
    $('#payInvoiceTotal').text(formatMoney(total) + ' ر.س');
    $('#payInvoiceRemaining').text(formatMoney(remaining) + ' ر.س');
    $('#pay_amount').val(parseFloat(remaining).toFixed(2));
    $('#pay_amount').attr('max', parseFloat(remaining).toFixed(2));
    $('#pay_receipt').val('');
    $('#pay_notes').val('');
    $('#payCustomerInvoiceModal').modal('show');
  }

  $('#formPayCustomerInvoice').on('submit', function (e) {
    e.preventDefault();
    const id = $('#pay_invoice_id').val();
    const submitBtn = $('#btnSubmitPayment');
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> جارٍ المعالجة...');

    const formData = new FormData(this);

    $.ajax({
      url: `${invoiceBaseUrl}/${id}/pay`,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        submitBtn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> تأكيد السداد');
        if (res.status === 1) {
          $('#payCustomerInvoiceModal').modal('hide');
          Swal.fire({
            icon: 'success',
            title: 'تم السداد بنجاح',
            text: res.success || 'تم تسجيل الدفعة وتحديث رصيد الفاتورة بنجاح.',
            customClass: { confirmButton: 'btn btn-primary' }
          });
          if (dt_invoices) dt_invoices.ajax.reload();
          updateStatistics();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: res.error || 'تعذر تسجيل السداد',
            customClass: { confirmButton: 'btn btn-primary' }
          });
        }
      },
      error: function (xhr) {
        submitBtn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> تأكيد السداد');
        const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'حدث خطأ أثناء تسجيل السداد';
        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: msg,
          customClass: { confirmButton: 'btn btn-primary' }
        });
      }
    });
  });

  // ─────────────────────────────────────────────────────────────────────────
  // 6. Action: Approve Invoice
  // ─────────────────────────────────────────────────────────────────────────
  $(document).on('click', '.btn-approve-invoice', function () {
    const id = $(this).data('id');
    const number = $(this).data('number');
    confirmApproveInvoice(id, number);
  });

  $('#viewInvoiceQuickApproveBtn').on('click', function () {
    if (!activeInvoiceData) return;
    $('#viewCustomerInvoiceModal').modal('hide');
    confirmApproveInvoice(activeInvoiceData.id, activeInvoiceData.invoice_number);
  });

  function confirmApproveInvoice(id, number) {
    Swal.fire({
      title: 'اعتماد الفاتورة',
      text: `هل أنت متأكد من اعتماد الفاتورة رقم (${number}) في النظام؟ بعد الاعتماد تصبح الفاتورة معتمدة وجاهزة لتحصيل وسداد العميل.`,
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
        $.post(`${invoiceBaseUrl}/${id}/approve`, function (res) {
          if (res.status === 1) {
            Swal.fire({
              icon: 'success',
              title: 'تم الاعتماد',
              text: res.success || 'تم اعتماد الفاتورة نهائياً بنجاح.',
              customClass: { confirmButton: 'btn btn-primary' }
            });
            if (dt_invoices) dt_invoices.ajax.reload();
            updateStatistics();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'خطأ',
              text: res.error || 'تعذر اعتماد الفاتورة',
              customClass: { confirmButton: 'btn btn-primary' }
            });
          }
        }).fail(function (xhr) {
          const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'فشل الاعتماد';
          Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: msg,
            customClass: { confirmButton: 'btn btn-primary' }
          });
        });
      }
    });
  }

  // ─────────────────────────────────────────────────────────────────────────
  // 7. Action: Cancel Invoice
  // ─────────────────────────────────────────────────────────────────────────
  $(document).on('click', '.btn-cancel-invoice', function () {
    const id = $(this).data('id');
    const number = $(this).data('number');

    Swal.fire({
      title: 'إلغاء الفاتورة وفك الارتباط',
      text: `هل أنت متأكد من إلغاء الفاتورة رقم (${number})؟ سيتم فك ارتباط كافة الحركات المالية وإعادتها لقائمة غير المفوترة.`,
      icon: 'warning',
      input: 'textarea',
      inputPlaceholder: 'اكتب سبب إلغاء الفاتورة هنا (اختياري)...',
      showCancelButton: true,
      confirmButtonText: 'نعم، قم بالإلغاء',
      cancelButtonText: 'تراجع',
      customClass: {
        confirmButton: 'btn btn-danger me-2',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.isConfirmed) {
        $.post(`${invoiceBaseUrl}/${id}/cancel`, { reason: result.value }, function (res) {
          if (res.status === 1) {
            Swal.fire({
              icon: 'success',
              title: 'تم الإلغاء',
              text: res.success || 'تم إلغاء الفاتورة وفك ارتباط الحركات بنجاح.',
              customClass: { confirmButton: 'btn btn-primary' }
            });
            if (dt_invoices) dt_invoices.ajax.reload();
            updateStatistics();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'خطأ',
              text: res.error || 'تعذر إلغاء الفاتورة',
              customClass: { confirmButton: 'btn btn-primary' }
            });
          }
        }).fail(function (xhr) {
          const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'فشل إلغاء الفاتورة';
          Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: msg,
            customClass: { confirmButton: 'btn btn-primary' }
          });
        });
      }
    });
  });

  // ─────────────────────────────────────────────────────────────────────────
  // 8. Action: Edit Invoice (Normal & Force Edit)
  // ─────────────────────────────────────────────────────────────────────────
  let editSelectedTransactions = [];

  function openInvoiceEditModal(id, isForceEdit = false) {
    $('#edit_invoice_id').val(id);
    $('#edit_is_force_edit').val(isForceEdit ? '1' : '0');
    $('#edit_force_edit_reason').val('');

    if (isForceEdit) {
      $('#editForceEditBanner').show();
      $('#editForceEditReasonContainer').show();
      $('#edit_force_edit_reason').prop('required', true).val('');
      $('#edit_force_edit_password').prop('required', true).val('');
      $('#editNormalInfoAlert').hide();
      $('#editCustomerInvoiceModal .modal-title')
        .removeClass('text-primary')
        .addClass('text-danger')
        .html(`<i class="ti ti-shield-bolt me-1 text-danger"></i> تعديل بالقوة على الفاتورة - <span id="editInvoiceNumberTitle"></span>`);
      $('#btnSubmitEditInvoice')
        .removeClass('btn-primary')
        .addClass('btn-danger')
        .html('<i class="ti ti-shield-bolt me-1"></i> حفظ التعديل بالقوة');
    } else {
      $('#editForceEditBanner').hide();
      $('#editForceEditReasonContainer').hide();
      $('#edit_force_edit_reason').prop('required', false).val('');
      $('#edit_force_edit_password').prop('required', false).val('');
      $('#editNormalInfoAlert').show();
      $('#editCustomerInvoiceModal .modal-title')
        .removeClass('text-danger')
        .addClass('text-primary')
        .html(`<i class="ti ti-edit me-1 text-primary"></i> تعديل بيانات الفاتورة - <span id="editInvoiceNumberTitle"></span>`);
      $('#btnSubmitEditInvoice')
        .removeClass('btn-danger')
        .addClass('btn-primary')
        .html('<i class="ti ti-check me-1"></i> حفظ التعديلات');
    }

    $.get(`${invoiceBaseUrl}/${id}`, function (res) {
      if (res.status === 1) {
        const inv = res.invoice;
        $('#editInvoiceNumberTitle').text(inv.invoice_number);
        $('#edit_accounting_reference_no').val(inv.accounting_reference_no || '');
        $('#edit_issue_date').val(inv.issue_date ? inv.issue_date.split('T')[0] : '');
        $('#edit_due_date').val(inv.due_date ? inv.due_date.split('T')[0] : '');
        if (inv.issue_date) {
          const d = new Date(inv.issue_date.split('T')[0]);
          d.setDate(d.getDate() + 1);
          $('#edit_due_date').attr('min', d.toISOString().split('T')[0]);
        } else {
          $('#edit_due_date').removeAttr('min');
        }
        $('#edit_notes').val(inv.notes || '');

        if (res.attachment_url) {
          $('#editCurrentAttachmentPreview').html(`
            <span class="text-muted">المرفق الحالي:</span>
            <a href="${res.attachment_url}" target="_blank" class="fw-bold ms-1">معاينة الملف</a>
          `);
        } else {
          $('#editCurrentAttachmentPreview').html('');
        }

        editSelectedTransactions = [];
        if (inv.items && inv.items.length) {
          inv.items.forEach(item => {
            const tx = item.wallet_transaction || {};
            const task = item.task || {};
            editSelectedTransactions.push({
              id: item.wallet_transaction_id,
              sequence: tx.sequence || item.wallet_transaction_id,
              amount: parseFloat(item.amount),
              task_custom: task.custom_task_number || (task.id ? '#' + task.id : '-'),
              delivery_number: task.delivery_number || '-',
              description: tx.description || '-'
            });
          });
        }
        renderEditLinkedTransactions();

        // Render available uninvoiced transactions
        let availableHtml = '';
        if (res.uninvoiced_transactions && res.uninvoiced_transactions.length) {
          res.uninvoiced_transactions.forEach(tx => {
            availableHtml += `
              <tr>
                <td class="text-center">
                  <input type="checkbox" class="form-check-input check-available-edit"
                         value="${tx.id}"
                         data-sequence="${tx.sequence}"
                         data-amount="${tx.amount}"
                         data-task="${tx.task_number || '-'}"
                         data-delivery="${tx.delivery_number || '-'}"
                         data-description="${tx.description || '-'}">
                </td>
                <td class="font-monospace">${tx.sequence}</td>
                <td><span class="badge bg-label-primary">${tx.task_number || '-'}</span></td>
                <td><span class="badge bg-label-info">${tx.delivery_number || '-'}</span></td>
                <td class="text-truncate" style="max-width: 200px;">${tx.description || '-'}</td>
                <td class="text-end fw-bold">${formatMoney(tx.amount)}</td>
              </tr>
            `;
          });
        } else {
          availableHtml = '<tr><td colspan="6" class="text-center text-muted py-2">لا توجد حركات إضافية غير مفوترة</td></tr>';
        }
        $('#editAvailableUninvoicedBody').html(availableHtml);
        $('#editAvailableUninvoicedSection').hide();

        $('#editCustomerInvoiceModal').modal('show');
      }
    });
  }

  $(document).on('click', '.btn-edit-invoice', function () {
    const id = $(this).data('id');
    openInvoiceEditModal(id, false);
  });

  $(document).on('click', '.btn-force-edit-invoice', function () {
    const id = $(this).data('id');
    openInvoiceEditModal(id, true);
  });

  function renderEditLinkedTransactions() {
    let html = '';
    if (editSelectedTransactions.length) {
      editSelectedTransactions.forEach((tx, idx) => {
        html += `
          <tr data-id="${tx.id}">
            <td class="text-center">${idx + 1}</td>
            <td class="font-monospace fw-semibold">${tx.sequence}</td>
            <td><span class="badge bg-label-primary">${tx.task_custom}</span></td>
            <td><span class="badge bg-label-info">${tx.delivery_number}</span></td>
            <td class="text-truncate" style="max-width: 200px;">${tx.description}</td>
            <td class="text-end fw-bold">${formatMoney(tx.amount)}</td>
            <td class="text-center">
              <button type="button" class="btn btn-xs btn-label-danger btn-remove-edit-tx" data-id="${tx.id}">
                <i class="ti ti-trash"></i>
              </button>
            </td>
          </tr>
        `;
      });
    } else {
      html = '<tr><td colspan="7" class="text-center text-danger py-2">يجب ربط حركة مالية واحدة على الأقل بالفاتورة!</td></tr>';
    }
    $('#editInvoiceTransactionsBody').html(html);
  }

  $(document).on('click', '.btn-remove-edit-tx', function () {
    const id = $(this).data('id');
    if (editSelectedTransactions.length <= 1) {
      Swal.fire({
        icon: 'warning',
        title: 'تنبيه',
        text: 'لا يمكن حذف كافة الحركات! الفاتورة يجب أن تحتوي على حركة مالية واحدة على الأقل.',
        customClass: { confirmButton: 'btn btn-primary' }
      });
      return;
    }
    editSelectedTransactions = editSelectedTransactions.filter(t => t.id !== id);
    renderEditLinkedTransactions();
  });

  $('#btnToggleAddMoreTransactions').on('click', function () {
    $('#editAvailableUninvoicedSection').slideToggle();
  });

  $(document).on('change', '.check-available-edit', function () {
    const checked = $(this).is(':checked');
    const id = parseInt($(this).val());
    if (checked) {
      if (!editSelectedTransactions.find(t => t.id === id)) {
        editSelectedTransactions.push({
          id: id,
          sequence: $(this).data('sequence'),
          amount: parseFloat($(this).data('amount')),
          task_custom: $(this).data('task'),
          delivery_number: $(this).data('delivery'),
          description: $(this).data('description')
        });
      }
    } else {
      editSelectedTransactions = editSelectedTransactions.filter(t => t.id !== id);
    }
    renderEditLinkedTransactions();
  });

  $('#checkAllAvailableForEdit').on('change', function () {
    const checked = $(this).is(':checked');
    $('.check-available-edit').prop('checked', checked).trigger('change');
  });

  $('#edit_issue_date').on('change', function () {
    const issueVal = $(this).val();
    if (issueVal) {
      const d = new Date(issueVal);
      d.setDate(d.getDate() + 1);
      $('#edit_due_date').attr('min', d.toISOString().split('T')[0]);
    } else {
      $('#edit_due_date').removeAttr('min');
    }
  });

  $('#formEditCustomerInvoice').on('submit', function (e) {
    e.preventDefault();

    const editIssueVal = $('#edit_issue_date').val();
    const editDueVal = $('#edit_due_date').val();
    if (editDueVal && editIssueVal && editDueVal <= editIssueVal) {
      Swal.fire({
        icon: 'warning',
        title: 'تاريخ الاستحقاق غير صالح',
        text: `تاريخ استحقاق الفاتورة يجب أن يكون أكبر من تاريخ إصدار الفاتورة (${editIssueVal}).`,
        customClass: { confirmButton: 'btn btn-primary' }
      });
      $('#edit_due_date').focus();
      return;
    }

    if (!editSelectedTransactions.length) {
      Swal.fire({
        icon: 'error',
        title: 'خطأ',
        text: 'يجب اختيار حركة مالية واحدة على الأقل لحفظ الفاتورة!',
        customClass: { confirmButton: 'btn btn-primary' }
      });
      return;
    }

    const isForceEdit = $('#edit_is_force_edit').val() === '1';
    if (isForceEdit) {
      const reason = $('#edit_force_edit_reason').val().trim();
      if (!reason) {
        Swal.fire({
          icon: 'warning',
          title: 'سبب التعديل بالقوة مطلوب',
          text: 'يجب كتابة مبرر وسبب التعديل بالقوة لتوثيق العملية في سجل التدقيق.',
          customClass: { confirmButton: 'btn btn-primary' }
        });
        $('#edit_force_edit_reason').focus();
        return;
      }

      const pwd = $('#edit_force_edit_password').val();
      if (!pwd) {
        Swal.fire({
          icon: 'warning',
          title: 'كلمة المرور مطلوبة',
          text: 'يرجى إدخال كلمة المرور الخاصة بك لتأكيد واعتماد التعديل بالقوة.',
          customClass: { confirmButton: 'btn btn-primary' }
        });
        $('#edit_force_edit_password').focus();
        return;
      }
    }

    const id = $('#edit_invoice_id').val();
    const formData = new FormData(this);
    editSelectedTransactions.forEach(t => {
      formData.append('transaction_ids[]', t.id);
    });

    const submitBtn = $('#btnSubmitEditInvoice');
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> جارٍ الحفظ...');

    $.ajax({
      url: `${invoiceBaseUrl}/${id}/update`,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        submitBtn.prop('disabled', false).html(isForceEdit ? '<i class="ti ti-shield-bolt me-1"></i> حفظ التعديل بالقوة' : '<i class="ti ti-check me-1"></i> حفظ التعديلات');
        if (res.status === 1) {
          $('#editCustomerInvoiceModal').modal('hide');
          Swal.fire({
            icon: 'success',
            title: isForceEdit ? 'تم التعديل بالقوة بنجاح' : 'تم التعديل بنجاح',
            text: res.success || (isForceEdit ? 'تم التعديل بالقوة وتوثيق العملية في سجل النشاطات بنجاح.' : 'تم تحديث بيانات الفاتورة بنجاح.'),
            customClass: { confirmButton: 'btn btn-primary' }
          });
          if (dt_invoices) dt_invoices.ajax.reload();
          updateStatistics();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: res.error || 'تعذر تعديل الفاتورة',
            customClass: { confirmButton: 'btn btn-primary' }
          });
        }
      },
      error: function (xhr) {
        submitBtn.prop('disabled', false).html(isForceEdit ? '<i class="ti ti-shield-bolt me-1"></i> حفظ التعديل بالقوة' : '<i class="ti ti-check me-1"></i> حفظ التعديلات');
        const msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'فشل تعديل الفاتورة';
        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: msg,
          customClass: { confirmButton: 'btn btn-primary' }
        });
      }
    });
  });

  // ─────────────────────────────────────────────────────────────────────────
  // 9. Action: Attachment Preview Modal
  // ─────────────────────────────────────────────────────────────────────────
  $(document).on('click', '.btn-preview-attachment', function () {
    const url = $(this).data('url') || $(this).attr('data-url');
    const title = $(this).data('title') || $(this).attr('data-title') || 'معاينة المرفق';
    if (!url) return;

    $('#previewAttachmentTitle').html(`<i class="ti ti-file-text me-1"></i> مرفق الفاتورة: ${title}`);
    $('#btnDownloadAttachment').attr('href', url);

    const isPdf = url.toLowerCase().endsWith('.pdf');
    let previewContent = '';

    if (isPdf) {
      previewContent = `<iframe src="${url}" class="w-100 h-100 border-0" style="min-height: 550px;" frameborder="0"></iframe>`;
    } else {
      previewContent = `<img src="${url}" alt="Attachment" class="img-fluid rounded p-2" style="max-height: 80vh; object-fit: contain;">`;
    }

    $('#attachmentPreviewContainer').html(previewContent);
    $('#previewAttachmentModal').modal('show');
  });
});
