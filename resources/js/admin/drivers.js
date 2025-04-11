/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, showFormModal } from '../ajax';

// Datatable (jquery)
$(function () {
  var dt_data_table = $('.datatables-users'),
    userView = baseUrl + 'app/user/view/account';

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
        url: baseUrl + 'admin/drivers/data',
        data: function (d) {
          d.status = $('#statusFilter').val();
          d.search = $('#searchFilter').val();
        },
        dataSrc: function (json) {
          $('#total').text(json.summary.total);
          $('#total-active').text(json.summary.total_active);
          $('#total-active + p').text(`(${((json.summary.total_active / json.summary.total) * 100).toFixed(1)})%`);
          $('#total-verified').text(json.summary.total_verified);
          $('#total-verified + p').text(`(${((json.summary.total_verified / json.summary.total) * 100).toFixed(1)})%`);
          $('#total-blocked').text(json.summary.total_blocked);
          $('#total-blocked + p').text(`(${((json.summary.total_blocked / json.summary.total) * 100).toFixed(1)})%`);
          return json.data;
        }
      },
      columns: [
        // columns according to JSON
        { data: '' },
        { data: 'fake_id' },
        { data: 'name' },
        { data: 'username' },
        { data: 'email' },
        { data: 'phone' },
        { data: 'role' },
        { data: 'tags' },
        { data: 'status' },
        { data: 'created_at' },
        { data: null }
      ],
      columnDefs: [
        {
          targets: 0,
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 1,
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
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            var $name = full.name;
            if (full.image === null) {
              var initials = $name.match(/\b\w/g) || [];
              initials = (initials.shift() || '') + (initials.pop() || '');
              var colors = ['success', 'danger', 'warning', 'info', 'dark', 'primary'];
              var color = colors[Math.floor(Math.random() * colors.length)];
              var img = `<div class="avatar  bg-label-${color} rounded-circle">
                      <span class="avatar-initial">${initials.toUpperCase()}</span>
                    </div>`;
            } else {
              var img = `<div class="avatar  bg-label-${color} rounded-circle">
                <img src="${full.image}"  class="rounded-circle  object-cover"/>
            </div>`;
            }

            return `
              <div class="d-flex align-items-center">
                <div class="avatar-wrapper me-3">
                  ${img}
                </div>
                <div class="d-flex flex-column">
                  <span class="fw-medium">${$name}</span>
                </div>
              </div>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span>${full.username}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span>${full.email}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return `<span>${full.phone}</span>`;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            return `<span>${full.role}</span>`;
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            return `<span>${full.tags}</span>`;
          }
        },
        {
          targets: 8,
          className: 'text-center',
          render: function (data, type, full, meta) {
            let icon = '';
            let status = full.status;

            switch (status) {
              case 'active':
                icon = '<i class="ti ti-shield-check text-success fs-5 ms-2"></i>';
                break;
              case 'blocked':
                icon = '<i class="ti ti-shield-x text-danger fs-5 ms-2"></i>';
                break;
              case 'verified':
                icon = '<i class="ti ti-hourglass text-warning fs-5 ms-2"></i>';
                break;
            }

            return `<span class="bg-label-${status}">${status}</span> ${icon}`;
          }
        },
        {
          targets: 9,
          render: function (data, type, full, meta) {
            return full.created_at;
          }
        },
        {
          targets: 10,
          title: 'Actions',
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
                <div class="dropdown">
                  <button class="btn btn-sm btn-icon  dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a href="${userView}" class="dropdown-item">View</a></li>
                    <li><a href="javascript:;" class="dropdown-item status-record" data-id="${full.id}" data-name="${full.name}" data-status="${full.status}">Change Status</a></li>
                  </ul>
                </div>
              </div>`;
          }
        }
      ],
      order: [[1, 'desc']],
      dom:
        '<"row"' +
        '<"col-md-2"l>' +
        '<"col-md-10 d-flex justify-content-end"fB>' +
        '>t' +
        '<"row mt-3"' +
        '<"col-md-6"i>' +
        '<"col-md-6"p>' +
        '>',
      lengthMenu: [10, 25, 50, 100], //for length of menu
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search...',
        info: 'Displaying _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="ti ti-chevron-right"></i>',
          previous: '<i class="ti ti-chevron-left"></i>'
        }
      },
      buttons: [
        `<label class='me-2'>
        <select id='statusFilter' class='form-select d-inline-block w-auto ms-2 mt-5'>
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="verified">Unverified</option>
          <option value="blocked">Blocked</option>
        </select>
      </label>`,
        ` <label class="me-2">
            <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="Search driver" />
        </label>`
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data.name;
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

    $('#statusFilter').on('change', function () {
      dt_data.draw();
    });

    $('#searchFilter').on('input', function () {
      dt_data.draw();
    });

    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_data }));
  }

  $('.dataTables_filter').hide();

  document.addEventListener('formSubmitted', function (event) {
    let id = $('#customer_id').val();
    $('.form_submit').trigger('reset');
    $('.preview-image').attr('src', baseUrl + 'assets/img/person.png');
    $('#additional-form').html('');
    $('#select-template').val('');
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
    var data_id = $(this).data('id'),
      dtrModal = $('.dtr-bs-modal.show');
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }
    $.get(`${baseUrl}admin/drivers/edit/${data_id}`, function (data) {
      console.log(data.teamsIds);
      $('.text-error').html('');
      $('#driver_id').val(data.id);
      $('#driver-fullname').val(data.name);
      $('#driver-username').val(data.username);
      $('#driver-email').val(data.email);
      $('#driver-phone').val(data.phone);
      $('#phone-code').val(data.phone_code);
      $('#driver-role').val(data.role_id);
      $('#driver-team').val(data.time_id);
      $('#driver-address').val(data.address);
      $('#driver-commission-type').val(data.commission_type);
      $('#driver-commission').val(data.commission);
      if (data.img !== null) {
        $('.preview-image').attr('src', data.img);
      }
      $('#modelTitle').html(`Edit User: <span class="bg-info text-white px-2 rounded">${data.name}</span>`);
    });
  });

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/customers/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  $(document).on('click', '.status-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const status = $(this).data('status');

    const fields = `
      <input type="hidden" name="id" value="${id}">
      <select class="form-select" name="status">
        <option value="active" ${status === 'active' ? 'selected' : ''}>Active</option>
        <option value="verified" ${status === 'verified' ? 'selected' : ''}>Unverified</option>
        <option value="blocked" ${status === 'blocked' ? 'selected' : ''}>Blocked</option>
      </select>
    `;

    showFormModal({
      title: `Change Driver: ${name} Status`,
      icon: 'info',
      fields: fields,
      url: `${baseUrl}admin/drivers/status`,
      method: 'POST',
      dataTable: dt_data
    });
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $(this).find('form')[0].reset();
    $('.preview-image').attr('src', baseUrl + 'assets/img/person.png');

    $('.text-error').html('');
    $('#driver_id').val('');
    $('#modelTitle').html('Add New Driver');
    $('#additional-form').html('');
    $('#select-template').val('');
  });

  function loadData(vehicle = '', type = '', lode = true, lodeType = false, loadSize = false) {
    $.ajax({
      url: baseUrl + 'admin/settings/vehicles/data',
      type: 'GET',
      data: { vehicle: vehicle, type: type },
      success: function (response) {
        var vehicle_options = ` <option value="">-- Select vehicle </option>`;
        vehicle_options += response.data.vehicles
          .map(
            option => `
          <option value="${option.id}">${option.name} - ${option.en_name}</option>
        `
          )
          .join('');
        if (lode) {
          $('#vehicle-vehicle').html(vehicle_options);
        }

        var vehicle_type_options = ` <option value="">-- select vehicle types </option>`;
        vehicle_type_options += response.data.types
          .map(
            option => `
          <option value="${option.id}"> ${option.name} - ${option.en_name}</option>
        `
          )
          .join('');

        if (lodeType) {
          $('#vehicle-type').html(vehicle_type_options);
        }

        var vehicle_sizes_options = ` <option value="">-- select vehicle Size </option>`;
        vehicle_sizes_options += response.data.sizes
          .map(
            size => `
          <option value="${size.id}"> ${size.name}</option>
        `
          )
          .join('');

        if (loadSize) {
          console.log('seize');
          console.log(response.data.sizes);
          $('#vehicle-size').html(vehicle_sizes_options);
        }
      }
    });
  }
  loadData();

  $(document).on('change', '#vehicle-vehicle', function () {
    var vehicle = $(this).val();
    $('#vehicle-size').html('<option value="">-- select vehicle Size </option>');
    $('#vehicle-type').html('<option value="">-- select vehicle type </option>');
    loadData(vehicle, '', false, true, false);
  });
  $(document).on('change', '#vehicle-type', function () {
    var type = $(this).val();
    $('#vehicle-size').html('<option value="">-- select vehicle Size </option>');
    loadData('', type, false, false, true);
  });
});
