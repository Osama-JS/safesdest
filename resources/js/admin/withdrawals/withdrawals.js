/**
 * Withdrawal Requests JS
 */

'use strict';

$(function () {
  var dt_withdrawals_table = $('.datatables-withdrawals');

  // Select2
  // Select2 for filters
  $('#filter_status, #filter_driver').select2();

  // Select2 for modal with dropdownParent fix
  $('#payment_method').select2({
    dropdownParent: $('#processWithdrawalModal')
  });

  // Handle Filter Change
  $('#filter_status, #filter_driver').on('change', function () {
    dt_withdrawals.ajax.reload();
  });

  // DataTable
  if (dt_withdrawals_table.length) {
    var dt_withdrawals = dt_withdrawals_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: withdrawalDataUrl,
        data: function (d) {
          d.status = $('#filter_status').val();
          d.driver_id = $('#filter_driver').val();
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
          data: 'approved_amount',
          render: function (data) {
            return '<span class="fw-bold text-success">' + data + ' SAR</span>';
          }
        },
        {
          data: 'status',
          render: function (data) {
            var statusObj = {
              pending: { title: 'Pending', class: 'bg-label-warning' },
              processing: { title: 'Processing', class: 'bg-label-info' },
              completed: { title: 'Approved', class: 'bg-label-success' },
              rejected: { title: 'Rejected', class: 'bg-label-danger' },
              cancelled: { title: 'Cancelled', class: 'bg-label-secondary' }
            };
            return '<span class="badge ' + statusObj[data].class + '">' + statusObj[data].title + '</span>';
          }
        },
        { data: 'payment_method' },
        { data: 'created_at' },
        { data: 'processed_by_name', defaultContent: 'N/A' },
        { data: 'actions' }
      ],
      columnDefs: [
        {
          // Actions
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            var actions = '';

            // View Details Button
            actions +=
              '<button class="btn btn-sm btn-icon btn-label-info me-1 view-withdrawal" data-id="' +
              full.id +
              '" title="View Details"><i class="ti ti-eye"></i></button>';

            if (full.status === 'pending') {
              actions +=
                '<button class="btn btn-sm btn-primary process-btn" data-id="' +
                full.id +
                '" data-amount="' +
                full.amount_requested +
                '">' +
                '<i class="ti ti-edit me-1"></i> Process' +
                '</button>';
            }
            return actions || '<span class="text-muted">No Actions</span>';
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
      // Trigger payment method change to handle receipt visibility
      $('#payment_method').trigger('change');
    }
  });

  // Handle Payment Method Change
  $('#payment_method').on('change', function () {
    if ($(this).val() === 'hyperpay') {
      $('#receipt_field_container').hide();
      $('#driver_bank_info').slideDown();
    } else {
      $('#receipt_field_container').show();
      $('#driver_bank_info').slideUp();
    }
  });

  // Open Modal
  $(document).on('click', '.process-btn', function () {
    var tr = $(this).closest('tr');
    var row = dt_withdrawals_table.DataTable().row(tr);
    var data = row.data();

    if (!data) return;

    $('#withdrawal_id').val(data.id);
    $('#amount_paid').val(data.amount_requested);
    $('#requested_amount_display').text(data.amount_requested);

    // Populate Driver Info
    $('#driver_wallet_balance').text(data.wallet_balance);
    $('#info_beneficiary').text(data.bank_details.beneficiary);
    $('#info_bic').text(data.bank_details.bic);
    $('#info_iban').text(data.bank_details.iban);
    $('#info_address').text(data.bank_details.address1);
    $('#info_city').text(data.bank_details.city);
    $('#info_country').text(data.bank_details.country);

    // Reset to Approve view
    $('#withdrawal_action').val('approve').trigger('change');
    $('#payment_method').val('bank_transfer').trigger('change');

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
        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        } else if (xhr.status === 422 && xhr.responseJSON.errors) {
          // Handle Laravel validation errors specifically if needed
          message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
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

  // View Withdrawal Details
  $(document).on('click', '.view-withdrawal', function () {
    var tr = $(this).closest('tr');
    var row = dt_withdrawals_table.DataTable().row(tr);
    var data = row.data();

    if (!data) return;

    // Basic Info
    $('#view_request_id').text(data.id);
    $('#view_driver_name').text(data.driver_name);
    $('#view_wallet_id').text(data.wallet_id);
    $('#view_amount_requested').text(data.amount_requested + ' SAR');
    $('#view_created_at').text(data.created_at);

    // Status Badge
    var statusClass = 'bg-label-secondary';
    if (data.status === 'pending') statusClass = 'bg-label-warning';
    else if (data.status === 'completed') statusClass = 'bg-label-success';
    else if (data.status === 'rejected') statusClass = 'bg-label-danger';

    $('#view_status').html('<span class="badge ' + statusClass + '">' + data.status + '</span>');

    // Processing Details
    if (data.status === 'pending') {
      $('#processing_details_section').hide();
    } else {
      $('#processing_details_section').show();
      $('#view_processed_by').text(data.processed_by_name);
      $('#view_processed_at').text(data.processed_at);
      $('#view_amount_paid').text(data.amount_paid + ' SAR');
      $('#view_payment_method').text(data.payment_method || '-');
      $('#view_admin_notes').text(data.admin_notes || 'No notes');

      if (data.receipt_image) {
        $('#view_receipt_container').show();
        $('#view_receipt_img').attr('src', data.receipt_image);
        $('#view_receipt_link').attr('href', data.receipt_image);
      } else {
        $('#view_receipt_container').hide();
      }
    }

    $('#viewWithdrawalModal').modal('show');
  });
});
