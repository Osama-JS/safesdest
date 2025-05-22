/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, showFormModal } from '../ajax';

// Datatable (jquery)
$(function () {
  // Variable declaration for table
  var dt_data_table = $('.datatables-users'),
    offCanvasForm = $('#largeModal');

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Users datatable
  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/settings/pricing/data',
        data: function (d) {
          d.guard = $('#roleFilter').val();
        }
      },
      columns: [
        // columns according to JSON
        { data: '' },
        { data: 'id' },
        { data: 'name' },
        { data: 'description' },
        { data: 'status' },
        { data: null }
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
          // User full name
          targets: 2,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            return full['name'];
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return full['description'];
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            var html = `<label class="switch switch-success">
              <input type="checkbox" class="switch-input edit_status" data-id=${full['id']} ${full['status'] == 1 ? 'checked' : ''} />
              <span class="switch-toggle-slider">
                <span class="switch-on">
                  <i class="ti ti-check"></i>
                </span>
                <span class="switch-off">
                  <i class="ti ti-x"></i>
                </span>
              </span>
            </label>`;
            return html;
          }
        },

        {
          // Actions
          targets: -1,
          title: __('Actions'),
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center gap-50">' +
              `<button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}" data-name="${full['name']}"  data-guard="${full['guard']}" data-bs-toggle="modal" data-bs-target="#largeModal"><i class="ti ti-edit"></i></button>`
            );
          }
        }
      ],
      order: [[2, 'desc']],
      dom:
        '<"row"' +
        '<"col-md-2"<"ms-n2"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0"fB>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      lengthMenu: [7, 10, 20, 50, 70, 100], //for length of menu
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: __('Search...'),
        info: __('Displaying _START_ to _END_ of _TOTAL_ entries'),
        paginate: {
          next: '<i class="ti ti-chevron-right ti-sm"></i>',
          previous: '<i class="ti ti-chevron-left ti-sm"></i>'
        }
      },
      // Buttons
      buttons: [],
      // For responsive popup
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return __('Details of') + ' ' + data['name'];
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
      },
      initComplete: function () {
        // استهدف input الخاص بالبحث واحذف الكلاسات
        $('.dataTables_filter input').removeClass(' form-control-sm'); // عدّل حسب الكلاسات اللي تبغى تشيلها
      }
    });
    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_data }));
  }

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');
    dt_data.draw();

    setTimeout(() => {
      $('#submitModal').modal('hide');
    }, 2000);
  });

  $(document).on('change', '.edit_status', function () {
    var Id = $(this).data('id');
    $.ajax({
      url: `${baseUrl}admin/settings/pricing/status/${Id}`,
      type: 'post',

      success: function (response) {
        if (response.status != 1) {
          showAlert('error', data.error, 10000, true);
        }
      },
      error: function () {
        showAlert('Error!', 'Failed Request', 'error');
      }
    });
  });

  $(document).on('click', '.edit-record', function () {
    var Id = $(this).data('id');
    var name = $(this).data('name');

    $('#modelTitle').html(__('Edit Method') + `: <span class="bg-info text-white px-2 rounded">${name}</span>`);
    // get data
    $.get(`${baseUrl}admin/settings/pricing/edit/${Id}`, function (data) {
      $('#submitModal').modal('show');

      $('#pricing_id').val(data.id);
      $('#pricing-name').val(data.name);
      $('#pricing-description').val(data.description);
    });
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    $('.text-error').html('');
    $('#modelTitle').html(__('Edit Method'));
  });
});
