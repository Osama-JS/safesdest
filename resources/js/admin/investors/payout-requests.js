/**
 * Investor Payout Requests Management JS (Dual Control Maker-Checker Workflow)
 */

'use strict';

$(function () {
  const dt_payouts_table = $('.datatables-payout-requests');

  // Handle Filters Change
  $('#filter_status, #filter_investor, #filter_from_date, #filter_to_date').on('change', function () {
    if (dt_payouts) {
      dt_payouts.ajax.reload();
    }
  });

  $('#btn-refresh-table').on('click', function () {
    if (dt_payouts) {
      dt_payouts.ajax.reload();
    }
  });

  // Select2 Init if available
  if ($('.select2').length) {
    $('.select2').each(function () {
      const $this = $(this);
      $this.wrap('<div class="position-relative"></div>').select2({
        placeholder: 'اختر...',
        dropdownParent: $this.parent(),
        allowClear: true
      });
    });
  }

  // DataTable Initialization
  if (dt_payouts_table.length) {
    var dt_payouts = dt_payouts_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: payoutDataUrl,
        data: function (d) {
          d.status = $('#filter_status').val();
          d.investor_id = $('#filter_investor').val();
          d.from_date = $('#filter_from_date').val();
          d.to_date = $('#filter_to_date').val();
        }
      },
      columns: [
        { data: 'id' },
        { data: 'reference_id' },
        { data: 'investor' },
        { data: 'bank_details' },
        { data: 'amount' },
        { data: 'status' },
        { data: 'created_at' },
        { data: 'audit' },
        { data: 'actions' }
      ],
      columnDefs: [
        {
          targets: [0, 1, 4, 5, 6, 7, 8],
          className: 'text-nowrap'
        },
        {
          targets: -1,
          searchable: false,
          orderable: false
        }
      ],
      order: [[0, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: {
        search: 'بحث:',
        lengthMenu: 'عرض _MENU_ مدخلات',
        info: 'عرض _START_ إلى _END_ من أصل _TOTAL_ مدخل',
        infoEmpty: 'يعرض 0 إلى 0 من أصل 0 مدخل',
        infoFiltered: '(تمت التصفية من أصل _MAX_ مدخل)',
        zeroRecords: 'لم يتم العثور على سجلات مطابقة',
        emptyTable: 'لا توجد بيانات متاحة في الجدول',
        paginate: {
          first: 'الأول',
          previous: 'السابق',
          next: 'التالي',
          last: 'الأخير'
        }
      }
    });
  }

  // -------------------------------------------------------------
  // View Details Modal
  // -------------------------------------------------------------
  $(document).on('click', '.view-details-btn', function () {
    const id = $(this).data('id');
    $('#modal-loading-spinner').show();
    $('#modal-content-area').hide();
    $('#viewDetailsModal').modal('show');

    const url = payoutShowUrl.replace(':id', id);

    $.ajax({
      url: url,
      type: 'GET',
      dataType: 'json',
      success: function (res) {
        if (res.success) {
          const d = res.data;

          $('#detail_reference_id').text(d.reference_id);
          $('#detail_status_badge').html(d.status_badge);
          $('#detail_amount').text(d.amount);
          $('#detail_payout_type').text(d.payout_type);

          // Investor Info
          $('#detail_investor_name').text(d.investor.name);
          $('#detail_investor_phone').text(d.investor.phone);
          $('#detail_investor_email').text(d.investor.email);
          $('#detail_investor_balance').text(d.investor.balance + ' ر.س');
          if (d.investor.wallet_url) {
            $('#detail_wallet_link').attr('href', d.investor.wallet_url).show();
          } else {
            $('#detail_wallet_link').hide();
          }

          // Bank details
          $('#detail_bank_beneficiary').text(d.bank_details.beneficiary_name);
          $('#detail_bank_name').text(d.bank_details.bank_name);
          $('#detail_bank_iban').text(d.bank_details.iban);
          $('#detail_bank_bic').text(d.bank_details.bic);
          $('#detail_bank_location').text(d.bank_details.city + ' - ' + d.bank_details.country);

          // Audit
          $('#detail_created_at').text(d.created_at);
          $('#detail_created_by').text(d.created_by);
          $('#detail_payout_id').text(d.payout_id);
          $('#detail_bulk_id').text(d.bulk_id);

          let approvalHtml = '—';
          if (d.approved_at) {
            approvalHtml = '<span class="text-success"><i class="ti ti-check me-1"></i>تم الاعتماد بواسطة: ' + (d.approved_by || 'المدير') + ' (' + d.approved_at + ')</span>';
          }
          $('#detail_approval_info').html(approvalHtml);

          if (d.rejection_reason && d.rejection_reason !== '—') {
            $('#detail_rejection_label').show();
            $('#detail_rejection_reason').text(d.rejection_reason).show();
          } else {
            $('#detail_rejection_label').hide();
            $('#detail_rejection_reason').hide();
          }

          if (d.failure_reason && d.failure_reason !== '—') {
            $('#detail_failure_label').show();
            $('#detail_failure_reason').text(d.failure_reason).show();
          } else {
            $('#detail_failure_label').hide();
            $('#detail_failure_reason').hide();
          }

          $('#detail_notes').text(d.notes);

          // Attachment
          if (d.attachment) {
            let attHtml = '';
            if (d.attachment.is_image) {
              attHtml = '<a href="' + d.attachment.file_url + '" target="_blank">' +
                        '<img src="' + d.attachment.file_url + '" class="img-thumbnail" style="max-height: 180px;" alt="Attachment">' +
                        '</a>';
            } else if (d.attachment.is_pdf) {
              attHtml = '<a href="' + d.attachment.file_url + '" target="_blank" class="btn btn-label-danger btn-sm">' +
                        '<i class="ti ti-file-type-pdf me-1"></i> عرض مستند PDF' +
                        '</a>';
            } else {
              attHtml = '<a href="' + d.attachment.file_url + '" target="_blank" class="btn btn-label-secondary btn-sm">' +
                        '<i class="ti ti-download me-1"></i> تحميل الملف المرفق' +
                        '</a>';
            }
            $('#detail_attachment_content').html(attHtml);
            $('#detail_attachment_box').show();
          } else {
            $('#detail_attachment_box').hide();
          }

          $('#modal-loading-spinner').hide();
          $('#modal-content-area').show();
        }
      },
      error: function (xhr) {
        $('#modal-loading-spinner').hide();
        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: xhr.responseJSON?.message || 'تعذر تحميل بيانات الطلب.'
        });
        $('#viewDetailsModal').modal('hide');
      }
    });
  });

  // -------------------------------------------------------------
  // Approve Modal Trigger
  // -------------------------------------------------------------
  $(document).on('click', '.approve-btn', function () {
    const id = $(this).data('id');
    const reference = $(this).data('reference');
    const amount = $(this).data('amount');
    const investor = $(this).data('investor');

    $('#approve_payout_id').val(id);
    $('#approve_modal_reference').text(reference);
    $('#approve_modal_amount').text(amount);
    $('#approve_modal_investor').text(investor);
    $('#manager_password').val('');

    $('#approveModal').modal('show');
  });

  // Submit Approval
  $('#approvePayoutForm').on('submit', function (e) {
    e.preventDefault();

    const id = $('#approve_payout_id').val();
    const password = $('#manager_password').val();
    const btn = $('#btn-submit-approve');

    if (!password) {
      Swal.fire({
        icon: 'warning',
        title: 'تنبيه',
        text: 'يرجى إدخال كلمة المرور لتأكيد العملية.'
      });
      return;
    }

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> جاري التحويل عبر البنك...');

    const url = payoutApproveUrl.replace(':id', id);

    $.ajax({
      url: url,
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        password: password
      },
      dataType: 'json',
      success: function (res) {
        btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> تأكيد وإرسال الحوالة البنكية');
        $('#approveModal').modal('hide');

        Swal.fire({
          icon: 'success',
          title: 'تمت المصادقة بنجاح',
          text: res.message || 'تم إرسال أمر التحويل بنجاح، وتتحول العملية إلى قيد المعالجة بالبنك.',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        }).then(() => {
          if (dt_payouts) {
            dt_payouts.ajax.reload();
          }
        });
      },
      error: function (xhr) {
        btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> تأكيد وإرسال الحوالة البنكية');

        Swal.fire({
          icon: 'error',
          title: 'فشل الاعتماد',
          text: xhr.responseJSON?.message || 'حدث خطأ أثناء معالجة الطلب.',
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      }
    });
  });

  // -------------------------------------------------------------
  // Reject Modal Trigger
  // -------------------------------------------------------------
  $(document).on('click', '.reject-btn', function () {
    const id = $(this).data('id');
    $('#reject_payout_id').val(id);
    $('#rejection_reason').val('');
    $('#rejectModal').modal('show');
  });

  // Submit Rejection
  $('#rejectPayoutForm').on('submit', function (e) {
    e.preventDefault();

    const id = $('#reject_payout_id').val();
    const reason = $('#rejection_reason').val().trim();
    const btn = $('#btn-submit-reject');

    if (!reason || reason.length < 3) {
      Swal.fire({
        icon: 'warning',
        title: 'تنبيه',
        text: 'يرجى كتابة سبب الرفض بوضوح (3 أحرف على الأقل).'
      });
      return;
    }

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> جاري الرفض...');

    const url = payoutRejectUrl.replace(':id', id);

    $.ajax({
      url: url,
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        reason: reason
      },
      dataType: 'json',
      success: function (res) {
        btn.prop('disabled', false).html('<i class="ti ti-trash me-1"></i> تأكيد الرفض');
        $('#rejectModal').modal('hide');

        Swal.fire({
          icon: 'success',
          title: 'تم الرفض',
          text: res.message || 'تم رفض طلب الدفع بنجاح.',
          customClass: {
            confirmButton: 'btn btn-success'
          }
        }).then(() => {
          if (dt_payouts) {
            dt_payouts.ajax.reload();
          }
        });
      },
      error: function (xhr) {
        btn.prop('disabled', false).html('<i class="ti ti-trash me-1"></i> تأكيد الرفض');

        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: xhr.responseJSON?.message || 'تعذر رفض الطلب.',
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      }
    });
  });
});
