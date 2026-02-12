'use strict';

/**
 * Task Claims List
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

  // Claims datatable
  if (dt_claims_table.length) {
    var dt_claims = dt_claims_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/task-claims/data',
        type: 'GET'
      },
      columns: [
        // columns according to JSON
        { data: '' },
        { data: 'task_number' },
        { data: 'driver_name' },
        { data: 'customer_name' },
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
          // Created At
          targets: 5,
          render: function (data, type, full, meta) {
            var date = new Date(data);
            return date.toLocaleString();
          }
        }
      ],
      order: [[5, 'desc']],
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
        searchPlaceholder: 'Search Claims'
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
              text: '<i class="ti ti-printer me-2" ></i>Print',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5]
              }
            },
            {
              extend: 'csv',
              text: '<i class="ti ti-file-text me-2" ></i>Csv',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5]
              }
            },
            {
              extend: 'excel',
              text: '<i class="ti ti-file-spreadsheet me-2" ></i>Excel',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5]
              }
            },
            {
              extend: 'pdf',
              text: '<i class="ti ti-file-description me-2" ></i>Pdf',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5]
              }
            },
            {
              extend: 'copy',
              text: '<i class="ti ti-copy me-2" ></i>Copy',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5]
              }
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
              return col.title !== '' // ? Do not show row in modal popup if title is empty (for check box)
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody/>').append(data) : false;
          }
        }
      }
    });
  }

  // Handle Approve/Reject buttons
  $(document).on('click', '.approve-claim, .reject-claim', function () {
    const id = $(this).data('id');
    const action = $(this).hasClass('approve-claim') ? 'approve' : 'reject';
    const title = action === 'approve' ? 'Approve Claim Request' : 'Reject Claim Request';
    const subtitle =
      action === 'approve'
        ? 'Approving this request will assign the driver to the task.'
        : 'Please provide a reason for rejection.';

    $('#claimId').val(id);
    $('#claimAction').val(action);
    $('#modalTitle').text(title);
    $('#modalSubtitle').text(subtitle);
    $('#submitBtn')
      .text(action === 'approve' ? 'Approve' : 'Reject')
      .removeClass('btn-primary btn-danger')
      .addClass(action === 'approve' ? 'btn-success' : 'btn-danger');

    $('#reviewClaimModal').modal('show');
  });

  // Handle Form Submission
  $('#reviewClaimForm').on('submit', function (e) {
    e.preventDefault();
    const id = $('#claimId').val();
    const action = $('#claimAction').val();
    const note = $('#adminNote').val();

    $.ajax({
      url: baseUrl + 'admin/task-claims/' + id + '/' + action,
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        note: note
      },
      success: function (response) {
        $('#reviewClaimModal').modal('hide');
        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: 'Success',
            text: response.message,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
          dt_claims.ajax.reload();
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
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: xhr.responseJSON.message || 'Something went wrong!',
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      }
    });
  });
});
