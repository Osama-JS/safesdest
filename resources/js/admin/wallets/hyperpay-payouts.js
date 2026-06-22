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
            let actions = `<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect check-status-btn" data-id="${full['id']}" data-bs-toggle="tooltip" title="Check Status">
                <i class="ti ti-refresh"></i>
              </button>`;
              
            if (full['status'] === 'failed' || full['status'] === 'completed') {
              let payloadStr = full['webhook_payload'] ? full['webhook_payload'].replace(/"/g, '&quot;') : '';
              let reasonStr = full['failure_reason'] ? full['failure_reason'].replace(/"/g, '&quot;') : '';
              actions += `<button class="btn btn-sm btn-icon btn-text-info rounded-pill waves-effect show-error-btn" data-reason="${reasonStr}" data-payload="${payloadStr}" data-status="${full['status']}" data-bs-toggle="tooltip" title="View Details">
                <i class="ti ti-info-circle"></i>
              </button>`;
            }
            
            return actions;
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

  // Handle Show Details Button Click
  $(document).on('click', '.show-error-btn', function () {
    var reason = $(this).data('reason');
    var payloadStr = $(this).data('payload');
    var status = $(this).data('status');
    
    var title = status === 'completed' ? 'Payout Details' : 'Failure Details';
    var icon = status === 'completed' ? 'success' : 'error';
    
    var htmlContent = '';
    
    if (reason && status === 'failed') {
      htmlContent += `<div class="alert alert-danger mb-3 text-start"><strong>Reason:</strong> ${reason}</div>`;
    }
    
    if (payloadStr) {
      try {
        var payloadObj = JSON.parse(payloadStr);
        htmlContent += `<div class="text-start mb-2 fw-bold">Webhook Response:</div>`;
        htmlContent += `<pre class="text-start bg-light p-3 rounded border" style="font-size: 13px; max-height: 300px; overflow-y: auto;"><code>${JSON.stringify(payloadObj, null, 2)}</code></pre>`;
      } catch (e) {
        htmlContent += `<pre class="text-start bg-light p-3 rounded border" style="font-size: 13px; max-height: 300px; overflow-y: auto;"><code>${payloadStr}</code></pre>`;
      }
    } else {
      htmlContent += `<p class="text-muted text-start">No additional payload data recorded.</p>`;
    }

    Swal.fire({
      icon: icon,
      title: title,
      html: htmlContent,
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      width: '600px'
    });
  });
});
