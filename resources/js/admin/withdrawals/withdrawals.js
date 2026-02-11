/**
 * Withdrawal Requests JS
 */

'use strict';

$(function () {
  var dt_withdrawals_table = $('.datatables-withdrawals');

  // DataTable
  if (dt_withdrawals_table.length) {
    var dt_withdrawals = dt_withdrawals_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: withdrawalDataUrl,
        data: function (d) {
          d.status = $('#filter-status').val();
        }
      },
      columns: [
        { data: 'id' },
        { data: 'driver_name', name: 'driver.name' },
        {
          data: 'amount_requested',
          render: function (data) {
            return '<span class="fw-bold text-primary">' + data + ' SAR</span>';
          }
        },
        {
          data: 'status',
          render: function (data) {
            var statusObj = {
              pending: { title: 'Pending', class: 'bg-label-warning' },
              completed: { title: 'Completed', class: 'bg-label-success' },
              rejected: { title: 'Rejected', class: 'bg-label-danger' },
              cancelled: { title: 'Cancelled', class: 'bg-label-secondary' }
            };
            return '<span class="badge ' + statusObj[data].class + '">' + statusObj[data].title + '</span>';
          }
        },
        { data: 'payment_method' },
        { data: 'created_at' },
        { data: 'id' }
      ],
      columnDefs: [
        {
          // Actions
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            if (full.status === 'pending') {
              return (
                '<button class="btn btn-sm btn-primary process-btn" data-id="' +
                data +
                '" data-amount="' +
                full.amount_requested +
                '">' +
                '<i class="ti ti-edit me-1"></i> Process' +
                '</button>'
              );
            }
            return '<span class="text-muted">No Actions</span>';
          }
        }
      ],
      order: [[0, 'desc']],
      dom: '<"card-header flex-column flex-md-row"<"head-label text-center"><"dt-action-buttons text-end pt-3 pt-md-0"B>><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      buttons: []
    });
  }

  // Handle Action Change (Approve/Reject)
  $('#withdrawal_action').on('change', function () {
    if ($(this).val() === 'reject') {
      $('.approve-fields').hide();
      $('#amount_paid').prop('required', false);
    } else {
      $('.approve-fields').show();
      $('#amount_paid').prop('required', true);
    }
  });

  // Open Modal
  $(document).on('click', '.process-btn', function () {
    var id = $(this).data('id');
    var amount = $(this).data('amount');

    $('#withdrawal_id').val(id);
    $('#amount_paid').val(amount);
    $('#requested_amount_display').text(amount + ' SAR');

    $('#processWithdrawalModal').modal('show');
  });

  // Submit Form
  $('#processWithdrawalForm').on('submit', function (e) {
    e.preventDefault();

    var id = $('#withdrawal_id').val();
    var url = processWithdrawalUrl.replace(':id', id);
    var formData = new FormData(this);

    var btn = $('#submitProcessBtn');
    btn
      .prop('disabled', true)
      .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');

    $.ajax({
      url: url,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        $('#processWithdrawalModal').modal('hide');
        Swal.fire({
          icon: 'success',
          title: 'Success',
          text: response.message,
          customClass: {
            confirmButton: 'btn btn-success'
          }
        });
        dt_withdrawals.draw();
        btn.prop('disabled', false).text('Process Request');
      },
      error: function (xhr) {
        btn.prop('disabled', false).text('Process Request');
        var message = 'Something went wrong';
        if (xhr.status === 422) {
          message = xhr.responseJSON.message || 'Validation Error';
        }
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: message,
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      }
    });
  });
});
