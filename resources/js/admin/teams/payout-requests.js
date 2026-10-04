/**
 * Team Payout Requests Management JS (Maker-Checker Workflow)
 */

'use strict';

$(function () {
  const dt_payouts_table = $('.datatables-team-payout-requests');

  // Handle Filters Change
  $('#filter_status, #filter_team, #filter_from_date, #filter_to_date').on('change', function () {
    if (dt_payouts) {
      dt_payouts.ajax.reload();
    }
  });

  $('#btn-refresh-table').on('click', function () {
    if (dt_payouts) {
      dt_payouts.ajax.reload();
    }
  });

  // DataTable Initialization
  if (dt_payouts_table.length) {
    var dt_payouts = dt_payouts_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: payoutDataUrl,
        data: function (d) {
          d.status = $('#filter_status').val();
          d.team_id = $('#filter_team').val();
          d.from_date = $('#filter_from_date').val();
          d.to_date = $('#filter_to_date').val();
        }
      },
      columns: [
        { data: 'reference_id' },
        { data: 'created_at' },
        { data: 'team' },
        { data: 'payout_type' },
        { data: 'amount' },
        { data: 'beneficiary' },
        { data: 'created_by' },
        { data: 'status' },
        { data: 'approver_info' },
        { data: 'actions' }
      ],
      columnDefs: [
        {
          targets: [0, 1, 3, 4, 6, 7, 8, 9],
          className: 'text-nowrap'
        },
        {
          targets: -1,
          searchable: false,
          orderable: false
        }
      ],
      order: [[1, 'desc']],
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
  $(document).on('click', '.btn-view-payout', function () {
    const id = $(this).data('id');
    $('#payout-details-loading').show();
    $('#payout-details-content').hide();
    $('#viewPayoutModal').modal('show');

    const url = payoutShowUrl.replace(':id', id);

    $.ajax({
      url: url,
      type: 'GET',
      dataType: 'json',
      success: function (res) {
        if (res.success) {
          const d = res.data;

          $('#modal-reference-id').text(d.reference_id);
          $('#modal-status-badge').html(d.status_badge);
          $('#modal-payout-type').text(d.payout_type_name);
          $('#modal-amount').text(d.amount);

          // Team Info
          $('#modal-team-name').text(d.team ? d.team.name : '—');
          if (d.team && d.team.wallet_url) {
            $('#modal-team-wallet-btn').attr('href', d.team.wallet_url).show();
          } else {
            $('#modal-team-wallet-btn').hide();
          }

          // Bank details
          $('#modal-beneficiary-name').text(d.bank_details.beneficiary_name || '—');
          $('#modal-bank-name').text(d.bank_details.bank_name || '—');
          $('#modal-iban').text(d.bank_details.iban || '—');
          $('#modal-bic').text(d.bank_details.bic || '—');
          $('#modal-purpose').text(d.bank_details.purpose || 'BA');
          $('#modal-address').text((d.bank_details.address || '—') + ' / ' + (d.bank_details.city || '—'));

          // Audit
          $('#modal-created-at').text(d.created_at);
          $('#modal-created-by').text(d.created_by);
          $('#modal-payout-id').text(d.payout_id);
          $('#modal-bulk-id').text(d.bulk_id);

          // Approver
          if (d.approved_by && d.approved_by !== '—') {
            $('#modal-approved-by').text(d.approved_by);
            $('#modal-approved-at').text(d.approved_at);
            $('#modal-approved-wrapper').show();
          } else {
            $('#modal-approved-wrapper').hide();
          }

          // Rejector
          if (d.rejected_by && d.rejected_by !== '—') {
            $('#modal-rejected-by').text(d.rejected_by);
            $('#modal-rejected-at').text(d.rejected_at);
            $('#modal-rejection-reason').text(d.rejection_reason || '—');
            $('#modal-rejected-wrapper').show();
          } else {
            $('#modal-rejected-wrapper').hide();
          }

          // Failure
          if (d.failure_reason && d.failure_reason !== '—') {
            $('#modal-failure-reason').text(d.failure_reason);
            $('#modal-failure-wrapper').show();
          } else {
            $('#modal-failure-wrapper').hide();
          }

          // Notes
          $('#modal-notes').text(d.notes || '—');

          // Attachments
          $('#modal-image-wrapper').hide();
          $('#modal-doc-wrapper').hide();
          $('#modal-no-attachment').hide();

          if (d.attachment && d.attachment.has_file) {
            const att = d.attachment;
            if (att.is_image) {
              $('#modal-attachment-img').attr('src', att.file_url);
              $('#modal-attachment-img-download-btn').attr('href', att.file_url);
              $('#modal-attachment-img').data('lightbox-url', att.file_url);
              $('#modal-attachment-img').data('lightbox-name', att.file_name);
              $('#modal-image-wrapper').show();
            } else {
              $('#modal-attachment-filename').text(att.file_name);
              $('#modal-attachment-ext-badge').text(att.extension.toUpperCase());
              $('#modal-attachment-doc-view-btn').attr('href', att.file_url);
              $('#modal-attachment-doc-download-btn').attr('href', att.file_url);
              $('#modal-doc-wrapper').show();
            }
          } else {
            $('#modal-no-attachment').show();
          }

          $('#payout-details-loading').hide();
          $('#payout-details-content').fadeIn();
        }
      },
      error: function (xhr) {
        $('#payout-details-loading').hide();
        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: 'تعذر جلب تفاصيل طلب الدفع. الرجاء المحاولة مجدداً.'
        });
      }
    });
  });

  // -------------------------------------------------------------
  // Image Lightbox Preview
  // -------------------------------------------------------------
  $(document).on('click', '.btn-open-image-lightbox', function () {
    const imgEl = $('#modal-attachment-img');
    const fileUrl = imgEl.data('lightbox-url') || imgEl.attr('src');
    const fileName = imgEl.data('lightbox-name') || 'سند_التحويل';

    if (!fileUrl || fileUrl === '#') return;

    $('#lightbox-modal-image').attr('src', fileUrl);
    $('#lightbox-modal-filename').text(fileName);
    $('#lightbox-modal-download-btn').attr('href', fileUrl);

    // Reset zoom state
    $('#lightbox-modal-image').css({
      'max-height': '78vh',
      cursor: 'zoom-in'
    });
    $('#lightbox-zoom-toggle').data('zoomed', false);
    $('#lightbox-zoom-toggle i').removeClass('ti-arrows-minimize').addClass('ti-arrows-maximize');

    $('#payoutImagePreviewModal').modal('show');
  });

  // Toggle Zoom in Lightbox
  $('#lightbox-zoom-toggle, #lightbox-modal-image').on('click', function (e) {
    if (e.target.id === 'lightbox-zoom-toggle' || e.target.id === 'lightbox-modal-image') {
      const isZoomed = $('#lightbox-zoom-toggle').data('zoomed') === true;
      const img = $('#lightbox-modal-image');

      if (!isZoomed) {
        img.css({
          'max-height': 'none',
          cursor: 'zoom-out'
        });
        $('#lightbox-zoom-toggle').data('zoomed', true);
        $('#lightbox-zoom-toggle i').removeClass('ti-arrows-maximize').addClass('ti-arrows-minimize');
      } else {
        img.css({
          'max-height': '78vh',
          cursor: 'zoom-in'
        });
        $('#lightbox-zoom-toggle').data('zoomed', false);
        $('#lightbox-zoom-toggle i').removeClass('ti-arrows-minimize').addClass('ti-arrows-maximize');
      }
    }
  });

  // When Lightbox Closes, re-focus viewPayoutModal nicely
  $('#payoutImagePreviewModal').on('hidden.bs.modal', function () {
    if ($('#viewPayoutModal').hasClass('show')) {
      $('body').addClass('modal-open');
    }
  });

  // -------------------------------------------------------------
  // Approve Payout Modal (Requires Manager Password)
  // -------------------------------------------------------------
  $(document).on('click', '.btn-approve-payout', function () {
    const id = $(this).data('id');
    const ref = $(this).data('ref');
    const amount = $(this).data('amount');
    const beneficiary = $(this).data('beneficiary');

    $('#approve_payout_id').val(id);
    $('#approve_ref_display').text(ref);
    $('#approve_amount_display').text(amount);
    $('#approve_beneficiary_display').text(beneficiary);
    $('#approve_password').val('');

    $('#approvePayoutModal').modal('show');
  });

  $('#approvePayoutForm').on('submit', function (e) {
    e.preventDefault();

    const id = $('#approve_payout_id').val();
    const password = $('#approve_password').val();
    const submitBtn = $('#btn-submit-approval');

    if (!password) {
      Swal.fire({
        icon: 'warning',
        title: 'تنبيه',
        text: 'يرجى إدخال كلمة المرور للمصادقة.'
      });
      return;
    }

    const url = payoutApproveUrl.replace(':id', id);

    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> جاري المصادقة والإرسال...');

    $.ajax({
      url: url,
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        password: password
      },
      dataType: 'json',
      success: function (res) {
        submitBtn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> تأكيد المصادقة والإرسال الفوري');

        if (res.success) {
          $('#approvePayoutModal').modal('hide');
          Swal.fire({
            icon: 'success',
            title: 'تمت المصادقة بنجاح!',
            text: res.message,
            customClass: {
              confirmButton: 'btn btn-primary'
            }
          });

          if (dt_payouts) {
            dt_payouts.ajax.reload(null, false);
          }
        }
      },
      error: function (xhr) {
        submitBtn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> تأكيد المصادقة والإرسال الفوري');
        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'حدث خطأ أثناء تنفيذ المصادقة.';
        Swal.fire({
          icon: 'error',
          title: 'فشلت المصادقة',
          text: msg
        });
      }
    });
  });

  // -------------------------------------------------------------
  // Reject Payout Modal (Requires Rejection Reason)
  // -------------------------------------------------------------
  $(document).on('click', '.btn-reject-payout', function () {
    const id = $(this).data('id');
    const ref = $(this).data('ref');

    $('#reject_payout_id').val(id);
    $('#reject_ref_display').text(ref);
    $('#reject_reason').val('');

    $('#rejectPayoutModal').modal('show');
  });

  $('#rejectPayoutForm').on('submit', function (e) {
    e.preventDefault();

    const id = $('#reject_payout_id').val();
    const reason = $('#reject_reason').val();
    const submitBtn = $('#btn-submit-rejection');

    if (!reason.trim()) {
      Swal.fire({
        icon: 'warning',
        title: 'تنبيه',
        text: 'يرجى كتابة سبب رفض الطلب.'
      });
      return;
    }

    const url = payoutRejectUrl.replace(':id', id);

    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> جاري الرفض...');

    $.ajax({
      url: url,
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        reason: reason
      },
      dataType: 'json',
      success: function (res) {
        submitBtn.prop('disabled', false).html('<i class="ti ti-x me-1"></i> تأكيد الرفض');

        if (res.success) {
          $('#rejectPayoutModal').modal('hide');
          Swal.fire({
            icon: 'info',
            title: 'تم رفض الطلب',
            text: res.message,
            customClass: {
              confirmButton: 'btn btn-primary'
            }
          });

          if (dt_payouts) {
            dt_payouts.ajax.reload(null, false);
          }
        }
      },
      error: function (xhr) {
        submitBtn.prop('disabled', false).html('<i class="ti ti-x me-1"></i> تأكيد الرفض');
        const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'حدث خطأ أثناء رفض الطلب.';
        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: msg
        });
      }
    });
  });
});
