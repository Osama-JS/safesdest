/**
 * HyperPay Payouts DataTables
 */

'use strict';

$(function () {
  var dt_payouts_table = $('.datatables-payouts');

  if (dt_payouts_table.length) {
    var dt_payouts = dt_payouts_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: fetchUrl,
        type: 'GET',
        data: function (d) {
          d.wallet = walletId;
        }
      },
      columns: [
        { data: '' },
        { data: 'fake_id' },
        { data: 'reference_id' },
        { data: 'payout_id' },
        { data: 'amount' },
        { data: 'type' },
        { data: 'status' },
        { data: 'created_at' },
        { data: 'action', orderable: false, searchable: false }
      ],
      columnDefs: [
        {
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          targets: 1,
          orderable: false,
          render: function (data, type, full, meta) {
            return `<span class="fw-medium">${full['fake_id']}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return `<span class="text-primary">${full['reference_id']}</span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span>${full['payout_id']}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span class="fw-bold">${full['amount']} SAR</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            let typeStr = full['type'] === 'WP' ? 'Wallet Payment' : 'Manual Transfer';
            return `<span>${typeStr}</span>`;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            var status = full['status'];
            var badgeClass = 'bg-label-warning';
            var statusText = 'Pending';
            
            if (status === 'completed') {
              badgeClass = 'bg-label-success';
              statusText = 'Completed';
            } else if (status === 'failed') {
              badgeClass = 'bg-label-danger';
              statusText = 'Failed';
            }
            
            return `<span class="badge ${badgeClass}">${statusText}</span>`;
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            return `<span>${full['created_at']}</span>`;
          }
        },
        {
          targets: 8,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              `<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect check-status-btn" data-id="${full['id']}" data-bs-toggle="tooltip" title="Check Status">
                <i class="ti ti-refresh"></i>
              </button>`
            );
          }
        }
      ],
      order: [[7, 'desc']],
      dom: '<"row mx-1"' +
        '<"col-12 col-md-6 d-flex align-items-center justify-content-center justify-content-md-start gap-2"l<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start mt-md-0 mt-3"B>>' +
        '<"col-12 col-md-6 d-flex align-items-center justify-content-end flex-column flex-md-row pe-3 gap-md-2"f<"invoice_status mb-3 mb-md-0">>' +
        '>t' +
        '<"row mx-2"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search...'
      },
      buttons: []
    });
  }

  // Handle Check Status Button Click
  $(document).on('click', '.check-status-btn', function () {
    var id = $(this).data('id');
    var url = checkStatusUrl.replace(':id', id);
    var $btn = $(this);
    
    // add loading spinner
    var originalIcon = $btn.html();
    $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
    $btn.prop('disabled', true);

    $.ajax({
      url: url,
      type: 'POST',
      data: {
        _token: csrfToken
      },
      success: function (response) {
        $btn.html(originalIcon);
        $btn.prop('disabled', false);

        if (response.status === 1) {
          Swal.fire({
            icon: 'success',
            title: 'Status Updated',
            text: response.success,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
          dt_payouts.ajax.reload(null, false);
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.error,
            customClass: {
              confirmButton: 'btn btn-primary'
            }
          });
        }
      },
      error: function (xhr) {
        $btn.html(originalIcon);
        $btn.prop('disabled', false);
        
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Something went wrong!',
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      }
    });
  });
});
