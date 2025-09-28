/**
 * User Commissions Management
 */

'use strict';
import { deleteRecord, showAlert, showFormModal } from '../ajax';

// Datatable (jquery)
$(function () {
  // Variable declaration for table
  var dt_data_table = $('.datatables-commissions'),
    offCanvasForm = $('#submitModal');

  var select2 = $('.select2');
  if (select2.length) {
    select2.each(function () {
      var $this = $(this);
      $this.wrap('<div class="position-relative"></div>').select2({
        allowClear: true,
        placeholder: $this.data('placeholder') || 'Select option',
        dropdownParent: $this.parent()
      });
    });
  }

  var permissions = {
    edit: false,
    delete: false
  };

  // DataTable with buttons
  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: commissionDataUrl,
        type: 'GET',
        dataSrc: function (json) {
          permissions['edit'] = json.summary.edit_permission;
          permissions['delete'] = json.summary.delete_permission;
          return json.data;
        }
      },
      columns: [
        // columns according to JSON
        { data: '' },
        { data: 'id' },
        { data: 'user' },
        { data: 'customer' },
        { data: 'commission_type' },
        { data: 'commission_value' },
        { data: 'status' },
        { data: 'created_at' },
        { data: 'action' }
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
          searchable: false,
          orderable: false,
          targets: 1,
          render: function (data, type, full, meta) {
            return `<span>${full.fake_id}</span>`;
          }
        },
        {
          // User
          targets: 2,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            return `<span class="fw-medium">${full.user}</span>`;
          }
        },
        {
          // Customer
          targets: 3,
          responsivePriority: 3,
          render: function (data, type, full, meta) {
            return `<span class="fw-medium">${full.customer}</span>`;
          }
        },
        {
          // Commission Type
          targets: 4,
          render: function (data, type, full, meta) {
            var badgeClass = full.commission_type === 'نسبة مئوية' ? 'bg-label-info' : 'bg-label-warning';
            return `<span class="badge ${badgeClass}">${full.commission_type}</span>`;
          }
        },
        {
          // Commission Value
          targets: 5,
          render: function (data, type, full, meta) {
            return `<span class="fw-medium text-primary">${full.commission_value}</span>`;
          }
        },
        {
          // Status
          targets: 6,
          render: function (data, type, full, meta) {
            var statusClass = full.status ? 'bg-label-success' : 'bg-label-secondary';
            var statusText = full.status ? __('Active') : __('Inactive');
            return `<span class="badge ${statusClass}">${statusText}</span>`;
          }
        },
        {
          // Created At
          targets: 7,
          render: function (data, type, full, meta) {
            return `<span class="text-nowrap">${full.created_at}</span>`;
          }
        },
        {
          // Actions
          targets: -1,
          title: __('Actions'),
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `<div class="d-flex align-items-center gap-50">
                      ${permissions['edit'] ? `<button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}" data-bs-toggle="modal" data-bs-target="#submitModal"><i class="ti ti-edit"></i></button>` : ''}
                      ${permissions['delete'] ? `<button class="btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}" data-name="${full['user']} - ${full['customer']}"><i class="ti ti-trash"></i></button>` : ''}
                      <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                      <div class="dropdown-menu dropdown-menu-end m-0">
                        <a href="javascript:;" class="dropdown-item status-record" data-id="${full['id']}" data-status="${full['status']}">${full['status'] ? __('Deactivate') : __('Activate')}</a>
                      </div>
                    </div>`;
          }
        }
      ],
      order: [[1, 'desc']],
      dom:
        '<"row"' +
        '<"col-md-2"<"me-3"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: __('Search..'),
        paginate: {
          next: '<i class="ti ti-chevron-right ti-sm"></i>',
          previous: '<i class="ti ti-chevron-left ti-sm"></i>'
        }
      },
      // Buttons with Dropdown
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-outline-secondary dropdown-toggle mx-3 waves-effect waves-light',
          text: '<i class="ti ti-download me-1 ti-xs"></i>Export',
          buttons: [
            {
              extend: 'print',
              text: '<i class="ti ti-printer me-2" ></i>Print',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6, 7],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              },
              customize: function (win) {
                $(win.document.body)
                  .css('color', config.colors.headingColor)
                  .css('border-color', config.colors.borderColor)
                  .css('background-color', config.colors.bodyBg);
                $(win.document.body)
                  .find('table')
                  .addClass('compact')
                  .css('color', 'inherit')
                  .css('border-color', 'inherit')
                  .css('background-color', 'inherit');
              }
            },
            {
              extend: 'csv',
              text: '<i class="ti ti-file-text me-2" ></i>Csv',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6, 7],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'excel',
              text: '<i class="ti ti-file-spreadsheet me-2"></i>Excel',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6, 7],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'pdf',
              text: '<i class="ti ti-file-code-2 me-2"></i>Pdf',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6, 7],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'copy',
              text: '<i class="ti ti-copy me-2" ></i>Copy',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6, 7],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            }
          ]
        }
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['user'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' // ? Do not show row in modal popup if title is blank (for check box)
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

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });
  }

  // Delete Record
  $(document).on('click', '.delete-record', function () {
    var commission_id = $(this).data('id'),
      dtrModal = $('.dtr-bs-modal.show');

    // hide responsive modal in small screen
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    // sweetalert for confirmation of delete
    deleteRecord(commissionDeleteUrl, commission_id, dt_data);
  });

  // Edit record
  $(document).on('click', '.edit-record', function () {
    var commission_id = $(this).data('id');

    // get data
    $.get(`${commissionEditUrl}/${commission_id}`, function (data) {
      $('.text-error').html('');
      $('#commission_id').val(data.id);
      $('#user_id').val(data.user_id).trigger('change');
      $('#customer_id').val(data.customer_id).trigger('change');
      $('#commission_type').val(data.commission_type);
      $('#commission_value').val(data.commission_value);
      $('#status').prop('checked', data.status);
      $('#modalTitle').text(__('Edit Commission'));
    });
  });

  // Status change
  $(document).on('click', '.status-record', function () {
    var commission_id = $(this).data('id');
    var current_status = $(this).data('status');
    var new_status = !current_status;

    $.post(commissionStatusUrl, {
      id: commission_id,
      status: new_status,
      _token: $('meta[name="csrf-token"]').attr('content')
    }, function (response) {
      if (response.status === 1) {
        showAlert('success', response.success);
        dt_data.ajax.reload();
      } else {
        showAlert('error', response.error);
      }
    });
  });

  // Add New record
  $(document).on('click', '.add-new', function () {
    $('#commission_id').val('');
    $('#submitForm')[0].reset();
    $('.text-error').html('');
    $('#modalTitle').text(__('Add New Commission'));
    $('.select2').val(null).trigger('change');
  });

  // Form Submission
  $(document).on('submit', '#submitForm', function (e) {
    e.preventDefault();
    var formData = new FormData(this);

    $.ajax({
      url: commissionStoreUrl,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.status === 1) {
          $('#submitModal').modal('hide');
          showAlert('success', response.success);
          dt_data.ajax.reload();
        } else {
          if (response.errors) {
            $.each(response.errors, function (key, value) {
              $('#' + key + '-error').html(value[0]);
            });
          } else {
            showAlert('error', response.error);
          }
        }
      },
      error: function (xhr) {
        showAlert('error', 'An error occurred while processing your request.');
      }
    });
  });
});
