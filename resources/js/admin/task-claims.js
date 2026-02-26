'use strict';

/**
 * Task Claims List - Enhanced
 */

$(function () {
  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Variable declaration for table
  var dt_claims_table = $('.datatables-task-claims');
  var statusFilter = '';

  // Claims datatable
  if (dt_claims_table.length) {
    var dt_claims = dt_claims_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/task-claims/data',
        type: 'GET',
        data: function (d) {
          d.status_filter = statusFilter;
        }
      },
      columns: [
        { data: '' },
        { data: 'task_number' },
        { data: 'task_price' },
        { data: 'driver_name' },
        { data: 'location' },
        { data: 'vehicle_size' },
        { data: 'customer_name' },
        { data: 'note' },
        { data: 'status' },
        { data: 'created_at' },
        { data: 'actions' }
      ],
      columnDefs: [
        {
          // For Responsive
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
          // Task Number
          targets: 1,
          orderable: false,
          render: function (data, type, full, meta) {
            return data;
          }
        },
        {
          // Task Price / Driver Earnings
          targets: 2,
          orderable: false,
          render: function (data, type, full, meta) {
            return data;
          }
        },
        {
          // Driver Name + Phone
          targets: 3,
          orderable: false,
          render: function (data, type, full, meta) {
            return data;
          }
        },
        {
          // Route: Pickup → Delivery
          targets: 4,
          orderable: false,
          responsivePriority: 3,
          render: function (data, type, full, meta) {
            return data;
          }
        },
        {
          // Vehicle Size
          targets: 5,
          orderable: false,
          render: function (data, type, full, meta) {
            return data;
          }
        },
        {
          // Customer Name
          targets: 6,
          orderable: false,
          render: function (data, type, full, meta) {
            return '<span class="fw-medium">' + data + '</span>';
          }
        },
        {
          // Note
          targets: 7,
          orderable: false,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            return data;
          }
        },
        {
          // Status
          targets: 8,
          orderable: false,
          render: function (data, type, full, meta) {
            return data;
          }
        },
        {
          // Created At
          targets: 9,
          render: function (data, type, full, meta) {
            if (!data) return '-';
            var date = new Date(data);
            var now = new Date();
            var diffMs = now - date;
            var diffMins = Math.floor(diffMs / 60000);
            var diffHours = Math.floor(diffMs / 3600000);
            var diffDays = Math.floor(diffMs / 86400000);

            var timeAgo = '';
            if (diffMins < 1) {
              timeAgo = 'Just now';
            } else if (diffMins < 60) {
              timeAgo = diffMins + 'm ago';
            } else if (diffHours < 24) {
              timeAgo = diffHours + 'h ago';
            } else if (diffDays < 7) {
              timeAgo = diffDays + 'd ago';
            } else {
              timeAgo = date.toLocaleDateString();
            }

            return (
              '<div>' +
              '<span class="fw-medium">' +
              timeAgo +
              '</span><br>' +
              '<small class="text-muted">' +
              date.toLocaleDateString() +
              ' ' +
              date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) +
              '</small>' +
              '</div>'
            );
          }
        },
        {
          // Actions
          targets: 10,
          orderable: false,
          searchable: false,
          render: function (data, type, full, meta) {
            return data;
          }
        }
      ],
      order: [[9, 'desc']],
      dom:
        '<"row mx-2"' +
        '<"col-md-2"<"me-3"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>' +
        '>t' +
        '<"row mx-2"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Claims...',
        processing:
          '<div class="d-flex justify-content-center align-items-center">' +
          '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>' +
          '<span>Loading...</span></div>',
        emptyTable:
          '<div class="text-center py-4">' +
          '<i class="ti ti-clipboard-off ti-lg text-muted mb-2 d-block"></i>' +
          '<span class="text-muted">No claim requests found</span>' +
          '</div>',
        zeroRecords:
          '<div class="text-center py-4">' +
          '<i class="ti ti-search-off ti-lg text-muted mb-2 d-block"></i>' +
          '<span class="text-muted">No matching records found</span>' +
          '</div>'
      },
      // Buttons with Export options
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-secondary dropdown-toggle mx-3',
          text: '<i class="ti ti-screen-share me-1 ti-xs"></i>Export',
          buttons: [
            {
              extend: 'print',
              text: '<i class="ti ti-printer me-2"></i>Print',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9] }
            },
            {
              extend: 'csv',
              text: '<i class="ti ti-file-text me-2"></i>CSV',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9] }
            },
            {
              extend: 'excel',
              text: '<i class="ti ti-file-spreadsheet me-2"></i>Excel',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9] }
            },
            {
              extend: 'pdf',
              text: '<i class="ti ti-file-description me-2"></i>PDF',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9] }
            },
            {
              extend: 'copy',
              text: '<i class="ti ti-copy me-2"></i>Copy',
              className: 'dropdown-item',
              exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8, 9] }
            }
          ]
        }
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.childRow,
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== ''
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td class="fw-medium">' +
                    col.title +
                    ':</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody/>').append(data) : false;
          }
        }
      },
      drawCallback: function () {
        // Re-initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
      }
    });

    // Status filter button group
    $('#statusFilterBtns .btn').on('click', function () {
      $('#statusFilterBtns .btn').removeClass('active');
      $(this).addClass('active');
      statusFilter = $(this).data('status');
      dt_claims.ajax.reload();
    });

    // Stat card click to filter
    $('.stat-filter-card').on('click', function () {
      var status = $(this).data('status');
      statusFilter = status;
      // Update button group to match
      $('#statusFilterBtns .btn').removeClass('active');
      $('#statusFilterBtns .btn[data-status="' + status + '"]').addClass('active');
      dt_claims.ajax.reload();

      // Smooth scroll to table
      $('html, body').animate(
        {
          scrollTop: $('.datatables-task-claims').offset().top - 100
        },
        500
      );
    });
  }

  // Handle Approve/Reject buttons
  $(document).on('click', '.approve-claim, .reject-claim', function () {
    const id = $(this).data('id');
    const action = $(this).hasClass('approve-claim') ? 'approve' : 'reject';

    const isApprove = action === 'approve';
    const title = isApprove ? 'Approve Claim Request' : 'Reject Claim Request';
    const subtitle = isApprove
      ? 'Approving this request will assign the driver to the task.'
      : 'Please provide a reason for rejection.';

    $('#claimId').val(id);
    $('#claimAction').val(action);
    $('#modalTitle').html(
      '<i class="ti ' + (isApprove ? 'ti-circle-check text-success' : 'ti-circle-x text-danger') + ' me-2"></i>' + title
    );
    $('#modalSubtitle').text(subtitle);

    // Update modal icon
    $('#modalIcon .avatar-initial')
      .removeClass('bg-label-primary bg-label-success bg-label-danger')
      .addClass(isApprove ? 'bg-label-success' : 'bg-label-danger')
      .html('<i class="ti ' + (isApprove ? 'ti-check' : 'ti-x') + ' ti-lg"></i>');

    $('#submitBtn')
      .html('<i class="ti ' + (isApprove ? 'ti-check' : 'ti-x') + ' me-1"></i>' + (isApprove ? 'Approve' : 'Reject'))
      .removeClass('btn-primary btn-success btn-danger')
      .addClass(isApprove ? 'btn-success' : 'btn-danger');

    $('#reviewClaimModal').modal('show');
  });

  // Handle Form Submission
  $('#reviewClaimForm').on('submit', function (e) {
    e.preventDefault();
    const id = $('#claimId').val();
    const action = $('#claimAction').val();
    const note = $('#adminNote').val();

    // Disable submit button
    const $submitBtn = $('#submitBtn');
    $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Processing...');

    $.ajax({
      url: baseUrl + 'admin/task-claims/' + id + '/' + action,
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        note: note
      },
      success: function (response) {
        $('#reviewClaimModal').modal('hide');
        $('#adminNote').val('');
        $submitBtn.prop('disabled', false);

        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: response.message,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
          dt_claims.ajax.reload(null, false);

          // Update stat cards - reload page after delay to refresh counts
          setTimeout(function () {
            location.reload();
          }, 1500);
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.message,
            customClass: {
              confirmButton: 'btn btn-primary'
            }
          });
        }
      },
      error: function (xhr) {
        $('#reviewClaimModal').modal('hide');
        $submitBtn.prop('disabled', false).html('<i class="ti ti-check me-1"></i>Submit');

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: xhr.responseJSON?.message || 'Something went wrong!',
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      }
    });
  });

  // Reset modal on close
  $('#reviewClaimModal').on('hidden.bs.modal', function () {
    $('#adminNote').val('');
    const $submitBtn = $('#submitBtn');
    $submitBtn.prop('disabled', false);
  });
});
