/**
 * Payout Requests Management JS (Maker-Checker Workflow)
 */

'use strict';

$(function () {
  const dt_payouts_table = $('.datatables-payout-requests');

  // Handle Filters Change
  $('#filter_status, #filter_type, #filter_from_date, #filter_to_date').on('change', function () {
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
          d.payout_type = $('#filter_type').val();
          d.from_date = $('#filter_from_date').val();
          d.to_date = $('#filter_to_date').val();
        }
      },
      columns: [
        { data: 'reference_id' },
        { data: 'created_at' },
        { data: 'driver' },
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

          $('#modal-driver-name').text(d.driver.name);
          $('#modal-driver-mobile').text(d.driver.mobile);

          $('#modal-beneficiary-name').text(d.bank_details.beneficiary_name);
          $('#modal-bank-name').text(d.bank_details.bank_name);
          $('#modal-iban').text(d.bank_details.iban);
          $('#modal-bic').text(d.bank_details.bic);
          $('#modal-address').text(d.bank_details.address + ' (' + d.bank_details.city + ' - ' + d.bank_details.country + ')');

          $('#modal-created-at').text(d.created_at);
          $('#modal-created-by').text(d.created_by);

          $('#modal-payout-id').text(d.payout_id || '—');
          $('#modal-bulk-id').text(d.bulk_id || '—');

          // Approved info
          if (d.approved_at && d.approved_at !== '—') {
            $('#modal-approved-wrapper').show();
            $('#modal-approved-by').text(d.approved_by);
            $('#modal-approved-at').text(d.approved_at);
          } else {
            $('#modal-approved-wrapper').hide();
          }

          // Rejected info
          if (d.rejected_at && d.rejected_at !== '—') {
            $('#modal-rejected-wrapper').show();
            $('#modal-rejected-by').text(d.rejected_by);
            $('#modal-rejected-at').text(d.rejected_at);
            $('#modal-rejection-reason').text(d.rejection_reason);
          } else {
            $('#modal-rejected-wrapper').hide();
          }

          // Failure info
          if (d.failure_reason && d.failure_reason !== '—') {
            $('#modal-failure-wrapper').show();
            $('#modal-failure-reason').text(d.failure_reason);
          } else {
            $('#modal-failure-wrapper').hide();
          }

          // Notes
          $('#modal-notes').text(d.notes || '—');

          // Image
          if (d.image_url) {
            $('#modal-image-preview').attr('src', d.image_url);
            $('#modal-image-link').attr('href', d.image_url);
            $('#modal-image-container').show();
          } else {
            $('#modal-image-container').hide();
          }

          $('#payout-details-loading').hide();
          $('#payout-details-content').show();
        }
      },
      error: function (xhr) {
        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: 'تعذر جلب تفاصيل الطلب.'
        });
        $('#viewPayoutModal').modal('hide');
      }
    });
  });

  // -------------------------------------------------------------
  // Approve Payout Modal & Action
  // -------------------------------------------------------------
  $(document).on('click', '.btn-approve-payout', function () {
    const id = $(this).data('id');
    const ref = $(this).data('ref');
    const amount = $(this).data('amount');
    const beneficiary = $(this).data('beneficiary');

    $('#approve_payout_id').val(id);
    $('#approve_ref_display').text(ref);
    $('#approve_beneficiary_display').text(beneficiary);
    $('#approve_amount_display').text(amount);
    $('#approve_password').val('');

    $('#approvePayoutModal').modal('show');
  });

  $('#approvePayoutForm').on('submit', function (e) {
    e.preventDefault();

    const id = $('#approve_payout_id').val();
    const password = $('#approve_password').val();
    const btn = $('#btn-submit-approval');

    if (!password) {
      Swal.fire({
        icon: 'warning',
        title: 'تنبيه',
        text: 'يرجى إدخال كلمة المرور الخاصة بك للمصادقة.'
      });
      return;
    }

    const url = payoutApproveUrl.replace(':id', id);

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> جاري الإرسال والمصادقة...');

    $.ajax({
      url: url,
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        password: password
      },
      dataType: 'json',
      success: function (res) {
        btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> تأكيد المصادقة والإرسال الفوري');
        $('#approvePayoutModal').modal('hide');

        if (res.success) {
          Swal.fire({
            icon: 'success',
            title: 'تمت المصادقة بنجاح!',
            text: res.message,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
          dt_payouts.ajax.reload();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'فشل المصادقة',
            text: res.message
          });
        }
      },
      error: function (xhr) {
        btn.prop('disabled', false).html('<i class="ti ti-send me-1"></i> تأكيد المصادقة والإرسال الفوري');

        let msg = 'حدث خطأ أثناء تنفيذ المصادقة.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          msg = xhr.responseJSON.message;
        }

        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: msg
        });
      }
    });
  });

  // -------------------------------------------------------------
  // Reject Payout Modal & Action
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
    const btn = $('#btn-submit-rejection');

    if (!reason.trim()) {
      Swal.fire({
        icon: 'warning',
        title: 'تنبيه',
        text: 'يرجى كتابة سبب الرفض.'
      });
      return;
    }

    const url = payoutRejectUrl.replace(':id', id);

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> جاري الرفض...');

    $.ajax({
      url: url,
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        reason: reason
      },
      dataType: 'json',
      success: function (res) {
        btn.prop('disabled', false).html('<i class="ti ti-x me-1"></i> تأكيد الرفض');
        $('#rejectPayoutModal').modal('hide');

        if (res.success) {
          Swal.fire({
            icon: 'success',
            title: 'تم الرفض',
            text: res.message,
            customClass: {
              confirmButton: 'btn btn-primary'
            }
          });
          dt_payouts.ajax.reload();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: res.message
          });
        }
      },
      error: function (xhr) {
        btn.prop('disabled', false).html('<i class="ti ti-x me-1"></i> تأكيد الرفض');

        let msg = 'حدث خطأ أثناء رفض الطلب.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          msg = xhr.responseJSON.message;
        }

        Swal.fire({
          icon: 'error',
          title: 'خطأ',
          text: msg
        });
      }
    });
  });
});
