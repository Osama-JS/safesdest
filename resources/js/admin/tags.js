/**
 * Page User List
 */

'use strict';
import { deleteRecord } from '../ajax';

$(function () {
  var dt_data_table = $('.datatables-users');

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/settings/tags/data',
        data: function (d) {
          d.search = $('#searchFilter').val();
        }
      },
      columns: [
        { data: '' },
        { data: 'fake_id' },
        { data: 'name' },
        { data: 'slug' },
        { data: 'description' },
        { data: 'drivers' },
        { data: 'customers' },
        { data: null }
      ],
      columnDefs: [
        {
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 1,
          targets: 0,
          render: function () {
            return '';
          }
        },
        {
          targets: 1,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `<span>${full.fake_id}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return `<span>${full.name}</span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span>${full.slug}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span>${full.description}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return `<span>${full.drivers}</span>`;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            return `<span>${full.customers}</span>`;
          }
        },

        {
          targets: 7,
          title: __('Actions'),
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `
              <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-icon edit-record " data-id="${full.id}" data-bs-toggle="modal" data-bs-target="#submitModal">
                  <i class="ti ti-edit"></i>
                </button>
                <button class="btn btn-sm btn-icon delete-record " data-id="${full.id}"  data-name="${full.name}">
                  <i class="ti ti-trash"></i>
                </button>
              </div>`;
          }
        }
      ],
      order: [[1, 'asc']],
      dom:
        '<"row"' +
        '<"col-md-2"l>' +
        '<"col-md-10 d-flex justify-content-end"fB>' +
        '>t' +
        '<"row mt-3"' +
        '<"col-md-6"i>' +
        '<"col-md-6"p>' +
        '>',
      lengthMenu: [10, 25, 50, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: __('Search...'),
        info: __('Showing _START_ to _END_ of _TOTAL_ entries'),
        paginate: {
          next: '<i class="ti ti-chevron-right"></i>',
          previous: '<i class="ti ti-chevron-left"></i>'
        }
      },
      buttons: [
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="${__('Search...')}" />
          </label>`
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return __('Details of') + ' ' + data.name;
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col) {
              return col.title
                ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                      <td>${col.title}:</td>
                      <td>${col.data}</td>
                   </tr>`
                : '';
            }).join('');
            return $('<table class="table"/><tbody />').append(data);
          }
        }
      }
    });

    $('#searchFilter').on('input', function () {
      dt_data.draw();
    });

    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_data }));
  }

  $('.dataTables_filter').hide();

  document.addEventListener('formSubmitted', function (event) {
    let id = $('#tag_id').val();
    $('.form_submit').trigger('reset');
    if (id) {
      setTimeout(() => {
        $('#submitModal').modal('hide');
      }, 2000);
    }
    if (dt_data) {
      dt_data.draw();
    }
  });

  document.addEventListener('deletedSuccess', function (event) {
    if (dt_data) {
      dt_data.draw();
    }
  });

  $(document).on('click', '.edit-record', function () {
    console.log('edit');
    var data_id = $(this).data('id'),
      dtrModal = $('.dtr-bs-modal.show');
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }
    $.get(`${baseUrl}admin/settings/tags/edit/${data_id}`, function (data) {
      console.log(data.teamsIds);
      $('.text-error').html('');
      $('#tag_id').val(data.id);
      $('#tag-name').val(data.name);
      $('#tag-slug').val(data.slug);
      $('#tag-description').val(data.description);
      $('#modelTitle').html(`Edit Tag: <span class="bg-info text-white px-2 rounded">${data.name}</span>`);
    });
  });

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/settings/tags/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $(this).find('form')[0].reset();
    $('.text-error').html('');
    $('#tag_id').val('');
    $('#modelTitle').html(__('Add New Tag'));
  });
});
