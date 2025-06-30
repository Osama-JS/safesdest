/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, generateFields, showFormModal } from '../../ajax';

// Datatable (jquery)
$(function () {
  var dt_data_table = $('.datatables-users'),
    dt_task_table = $('.datatables-tasks'),
    dt_transaction_table = $('.datatables-transactions'),
    dt_trans, // Define dt_trans in global scope
    userView = baseUrl + 'admin/drivers/account/';
  console.log(templateId);

  if (templateId != null) {
    $('#select-template').val(templateId).trigger('change');
  }

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  if (dt_task_table.length) {
    var dt_task = dt_task_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/teams/tasks',
        data: function (d) {
          d.owner = $('#owner-fillter').val();
          d.driver = $('#driver-fillter').val();

          d.team = teamID;
        }
      },

      columns: [
        { data: '' }, // للـ control (responsive)
        { data: 'id' }, // الترقيم التسلسلي
        { data: 'price' }, // الحالة
        { data: 'driver' }, // الحالة
        { data: 'address' }, // الحالة
        { data: 'start' }, // الحالة
        { data: 'complete' }, // الحالة
        { data: 'status' }, // الحالة
        { data: 'closed' }, // تاريخ الإنشاء
        { data: null } // actions
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
            return `<span>${full.id}</span>`;
          }
        },
        {
          targets: 2,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `<span class="border px-3 rounded text-primary"><b>${full.price} ${__('SAR')}</b></span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span>${full.driver}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span>${full.address}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return `<span>${full.start}</span>`;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            return `<span>${full.complete}</span>`;
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            let colorClass = '';

            switch (full.status) {
              case 'advertised':
                colorClass = 'badge bg-secondary'; // رمادي
                break;
              case 'in_progress':
                colorClass = 'badge bg-primary'; // أزرق
                break;
              case 'assign':
                colorClass = 'badge bg-info'; // سماوي
                break;
              case 'accepted':
                colorClass = 'badge bg-warning text-dark'; // أصفر
                break;
              case 'start':
                colorClass = 'badge bg-dark'; // أسود
                break;
              case 'completed':
                colorClass = 'badge bg-success'; // أخضر
                break;
              case 'canceled':
                colorClass = 'badge bg-danger'; // أحمر
                break;
              default:
                colorClass = 'badge bg-light text-dark'; // افتراضي
            }

            return `<span class="w-100 text-center ${colorClass}">${full.status.replace('_', ' ')}</span>`;
          }
        },
        {
          targets: 8,
          render: function (data, type, full, meta) {
            let colorClass = '';
            switch (full.closed) {
              case 'closed':
                colorClass = 'badge bg-secondary';
                break;
              case 'open':
                colorClass = 'badge bg-primary';
                break;
              default:
                colorClass = 'badge bg-light text-dark';
            }

            return `<span class="w-100 text-center ${colorClass}">${full.closed}</span>`;
          }
        },
        {
          targets: 9,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `
              <div class="d-flex align-items-center gap-2">
                  <a href="${baseUrl}admin/teams/tasks/show/${full.id}" class="btn btn-sm btn-icon  " >
                    <i class="ti ti-help"></i>
                  </a>
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
      lengthMenu: [10, 25, 50, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search...',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="ti ti-chevron-right"></i>',
          previous: '<i class="ti ti-chevron-left"></i>'
        }
      },

      buttons: [
        ` <div class="mt-5 mx-2">
            <input type="text" id="dateRange" class="form-control" placeholder="Select Date Range">
        </div>`,
        `<label class='me-2'>
          <select id='statusFilter' class='form-select d-inline-block w-auto ms-2 mt-5'>
            <option value="">All Status</option>
            <option value="advertised">Advertised</option>
            <option value="in_progress">In Progress</option>
            <option value="assign">Assign</option>
            <option value="started">Started</option>
            <option value="in pickup point">In Pickup Point</option>
            <option value="loading">Loading</option>
            <option value="in the way">In The Way</option>
            <option value="in delivery point">In Delivery Point</option>
            <option value="unloading">Unloading</option>
            <option value="completed">Completed</option>
            <option value="canceleds">canceled</option>
          </select>
        </label>`,
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="Search Tasks" />
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
      dt_task.draw();
    });

    $('#searchFilter').on('input', function () {
      dt_task.draw();
    });

    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_data }));
  }

  // Users datatable
  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/teams/drivers',
        data: function (d) {
          d.status = $('#statusFilter').val();
          d.search = $('#searchFilter').val();
          d.team = teamID;
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
          responsivePriority: 2,
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
            return `<span>${full.tags || ''}</span>`;
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
                icon = '<i class="ti ti-hourglass text-secondary fs-5 ms-2"></i>';
              case 'pending':
                icon = '<i class="ti ti-user-search text-warning fs-5 ms-2"></i>';
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
                    <li><a href=${userView}${full.id}/${full.name}" class="dropdown-item">View</a></li>
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
          <option value="pending">Pending</option>
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

  if (dt_transaction_table.length) {
    console.log('Initializing transactions DataTable'); // Debug log
    dt_trans = dt_transaction_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/teams/transactions',
        data: function (d) {
          d.search = $('#tranSearchFilter').val();
          d.status = $('#tranStatusFilter').val();
          d.team = teamID;
        }
      },
      columns: [
        { data: 'checkbox' },
        { data: '' },
        { data: 'fake_id' },
        { data: 'amount' },
        { data: 'driver' },
        { data: 'description' },
        { data: 'maturity' },
        { data: 'task' },
        { data: 'status' },
        { data: 'created_at' }
      ],
      columnDefs: [
        {
          targets: 0,
          searchable: false,
          orderable: false,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            // Only show checkbox for unpaid transactions (status = 0)
            console.log('Rendering checkbox for transaction:', full.id, 'status:', full.status); // Debug log
            if (full.status == 0 || full.status === false || full.status === '0') {
              console.log('Creating checkbox for transaction:', full.id); // Debug log
              return `<input type="checkbox" class="form-check-input transaction-checkbox"
                             value="${full.id}"
                             data-amount="${full.amount}"
                             data-driver="${full.driver}"
                             data-description="${full.description}"
                             data-sequence="${full.sequence}">`;
            }
            console.log('No checkbox for transaction:', full.id, 'because status is:', full.status); // Debug log
            return '';
          }
        },
        {
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 1,
          targets: 1,
          render: function () {
            return '';
          }
        },
        {
          targets: 2,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `<span>${full.sequence} </span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<b><span class="${full.type === 'debit' ? 'text-danger' : 'text-success'} border px-3 rounded ">${full.amount} SAR</span><b>`;
          }
        },
        {
          targets: 4,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `<span>${full.driver} </span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            let imageBtn = '';
            if (full.image) {
              imageBtn = `
                <button class="btn btn-sm btn-icon show-image" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="${baseUrl + full.image}" title="عرض الصورة">
                  <i class="ti ti-photo"></i>
                </button>
              `;
            }

            return `
              <span>${full.description}</span>
              ${imageBtn}
            `;
          }
        },

        {
          targets: 6,
          render: function (data, type, full, meta) {
            return `<span>${full.maturity}</span>`;
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            return `<span>${full.task}</span>`;
          }
        },

        {
          targets: 8,
          render: function (data, type, full, meta) {
            if (full.type === 'credit') {
              if (full.status == 1) {
                return '<span class="badge bg-success">paid</span>';
              } else {
                return '<span class="badge bg-danger">not paid</span>';
              }
            } else if (full.type === 'debit') {
              return '<span class="badge bg-info">pay</span>';
            }
            return '<span class="badge bg-secondary">undefined</span>';
          }
        },

        {
          targets: 9,
          render: function (data, type, full, meta) {
            return `<span>${full.created_at}</span>`;
          }
        }
      ],
      createdRow: function (row, data, dataIndex) {
        if (data.task !== '') {
          $(row).addClass('table-');
        }
      },
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
        searchPlaceholder: 'Search...',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="ti ti-chevron-right"></i>',
          previous: '<i class="ti ti-chevron-left"></i>'
        }
      },
      buttons: [
        `<label class='me-2'>
        <select id='statusFilter' class='form-select d-inline-block w-auto ms-2 mt-5'>
          <option value="all">All</option>
          <option value="credit">Credit</option>
          <option value="debit">Debit</option>
        </select>
      </label>`,
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="Search..." />
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
      dt_trans.draw();
    });

    $('#tranStatusFilter').on('input', function () {
      dt_trans.draw();
    });

    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_trans }));

    // Check for checkboxes after DataTable is loaded
    dt_trans.on('draw', function () {
      console.log('DataTable drawn, checking for checkboxes...'); // Debug log
      const checkboxes = $('.transaction-checkbox');
      console.log('Found', checkboxes.length, 'checkboxes'); // Debug log

      // Also check all input elements
      const allInputs = $('input[type="checkbox"]');
      console.log('Found', allInputs.length, 'total checkboxes'); // Debug log

      // Check if payment controls exist
      const paymentControls = $('#payment-controls');
      console.log('Payment controls element exists:', paymentControls.length > 0); // Debug log

      // Add a test checkbox to verify event delegation
      // if (checkboxes.length === 0) {
      //   console.log('No checkboxes found, adding test checkbox'); // Debug log
      //   const testCheckbox =
      //     '<input type="checkbox" class="form-check-input transaction-checkbox test-checkbox" value="test" data-amount="100" data-driver="Test Driver" data-description="Test Description" data-sequence="1">';
      //   $('.datatables-transactions tbody').append(
      //     '<tr><td>' + testCheckbox + '</td><td colspan="9">Test Row</td></tr>'
      //   );
      // }
    });
  }

  $('.dataTables_filter').hide();

  document.addEventListener('formSubmitted', function (event) {
    let id = $('#driver_id').val();
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

  $(document).on('click', '.edit-record', async function () {
    var data_id = $(this).data('id'),
      dtrModal = $('.dtr-bs-modal.show');
    console.log(data_id);

    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    $.get(`${baseUrl}admin/drivers/edit/${data_id}`, async function (data) {
      $('.form_submit').trigger('reset');
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

      $('.vehicle-select').val(data.vehicle).trigger('change');

      await delay(1000);
      $('.vehicle-type-select').val(data.vehicle_type).trigger('change');

      await delay(1000);
      $('.vehicle-size-select').val(data.vehicle_size_id).trigger('change');

      if (data.img !== null) {
        $('.preview-image').attr('src', data.img);
      }

      $('#additional-form').html('');
      $('#select-template').val(data.form_template_id);

      if (data.form_template_id === null) {
        $('#select-template').val(templateId).trigger('change');
      }

      generateFields(data.fields, data.additional_data);

      $('#modelTitle').html(`Edit User: <span class="bg-info text-white px-2 rounded">${data.name}</span>`);
    });
  });

  // وظيفة تأخير باستخدام Promise
  function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/drivers/delete/' + $(this).data('id');
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
        <option value="pending" ${status === 'pending' ? 'selected' : ''}>Pending</option>
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
    $('.form_submit').trigger('reset');
    $('.preview-image').attr('src', baseUrl + 'assets/img/person.png');

    $('.text-error').html('');
    $('#driver_id').val('');
    $('#modelTitle').html('Add New Driver');
    $('#additional-form').html('');
    $('#select-template').val(templateId).trigger('change');
  });

  const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');

  tabButtons.forEach(function (button) {
    button.addEventListener('shown.bs.tab', function (event) {
      const targetId = event.target.getAttribute('data-bs-target');

      if (targetId === '#navs-drivers') {
        dt_data.draw();
      }

      if (targetId === '#navs-tasks') {
        dt_task.draw();
      }

      if (targetId === '#navs-wallet') {
        dt_trans.draw();
        // كود خاص بالمحفظة
      }
    });
  });
});

// $('#dateRange').daterangepicker(
//   {
//     opens: 'left',
//     locale: {
//       format: 'DD MMM YYYY',
//       cancelLabel: 'Cancel',
//       applyLabel: 'Apply'
//     },
//     startDate: moment().startOf('month'),
//     endDate: moment().endOf('month')
//   },
//   function (start, end, label) {
//     const startDate = start.format('YYYY-MM-DD');
//     const endDate = end.format('YYYY-MM-DD');
//     start_from = startDate;
//     end_to = endDate;
//     dt_task.draw();
//   }
// );
/* ================  Select Vehicles Code   =============== */
let vehicleIndex = 0;
const selectedTypes = new Set();

function createVehicleRow(index) {
  return $('#vehicle-row-template').html().replaceAll('{index}', index);
}

function updateVehicleRowEvents($row) {
  const $vehicleSelect = $row.find('.vehicle-select');
  const $typeSelect = $row.find('.vehicle-type-select');
  const $sizeSelect = $row.find('.vehicle-size-select');

  $vehicleSelect.on('change', function () {
    const vehicleId = $(this).val();
    $typeSelect.prop('disabled', true).empty().append('<option>Loading...</option>');
    $sizeSelect.prop('disabled', true).empty().append('<option>Select a vehicle size</option>');

    if (vehicleId) {
      $.get(`${baseUrl}chosen/vehicles/types/${vehicleId}`, function (types) {
        $typeSelect.empty().append('<option value="">Select a vehicle type</option>');
        types.forEach(type => {
          $typeSelect.append(`<option value="${type.id}">${type.name}</option>`);
        });
        $typeSelect.prop('disabled', false);
      });
    }
  });

  $typeSelect.on('change', function () {
    const typeId = $(this).val();
    $sizeSelect.prop('disabled', true).empty().append('<option>Loading...</option>');

    if (typeId) {
      selectedTypes.add(typeId);
      $.get(`${baseUrl}chosen/vehicles/sizes/${typeId}`, function (sizes) {
        $sizeSelect.empty().append('<option value="">Select a vehicle size</option>');
        sizes.forEach(size => {
          $sizeSelect.append(`<option value="${size.id}">${size.name}</option>`);
        });
        $sizeSelect.prop('disabled', false);
      });
    }
  });
}

const $newRow = $(createVehicleRow(vehicleIndex++));
$('#vehicle-selection-container').append($newRow);
updateVehicleRowEvents($newRow);

// Payment System Variables
let selectedTransactions = [];
let originalTotal = 0;

console.log('Payment system initialized'); // Debug log

// Update selected transactions display
function updateSelectedDisplay() {
  console.log('updateSelectedDisplay called, selectedTransactions:', selectedTransactions); // Debug log
  const count = selectedTransactions.length;
  const total = selectedTransactions.reduce((sum, trans) => sum + parseFloat(trans.amount), 0);

  console.log('Count:', count, 'Total:', total); // Debug log

  $('#selected-count').text(count);
  $('#selected-total').text(total.toFixed(2) + ' SAR');

  // Show/hide payment controls
  if (count > 0) {
    $('#payment-controls').show();
    console.log('Showing payment controls'); // Debug log
  } else {
    $('#payment-controls').hide();
    console.log('Hiding payment controls'); // Debug log
  }

  originalTotal = total;
}

// Handle individual checkbox change
console.log('Setting up checkbox event listener'); // Debug log
$(document).on('change', '.transaction-checkbox', function () {
  console.log('Checkbox changed!'); // Debug log
  const checkbox = $(this);
  const transactionId = checkbox.val();
  const amount = parseFloat(checkbox.data('amount'));
  const driver = checkbox.data('driver');
  const description = checkbox.data('description');
  const sequence = checkbox.data('sequence');

  console.log('Transaction data:', { transactionId, amount, driver, description, sequence }); // Debug log

  if (checkbox.is(':checked')) {
    // Add to selected transactions
    selectedTransactions.push({
      id: transactionId,
      amount: amount,
      driver: driver,
      description: description,
      sequence: sequence
    });
  } else {
    // Remove from selected transactions
    selectedTransactions = selectedTransactions.filter(trans => trans.id !== transactionId);
  }

  updateSelectedDisplay();
  updateSelectAllCheckbox();
});

// Handle select all checkbox
$('#select-all-transactions').on('change', function () {
  console.log('chec all');
  const isChecked = $(this).is(':checked');

  $('.transaction-checkbox').each(function () {
    const checkbox = $(this);
    const transactionId = checkbox.val();
    const amount = parseFloat(checkbox.data('amount'));
    const driver = checkbox.data('driver');
    const description = checkbox.data('description');
    const sequence = checkbox.data('sequence');

    if (isChecked && !checkbox.is(':checked')) {
      checkbox.prop('checked', true);
      selectedTransactions.push({
        id: transactionId,
        amount: amount,
        driver: driver,
        description: description,
        sequence: sequence
      });
    } else if (!isChecked && checkbox.is(':checked')) {
      checkbox.prop('checked', false);
      selectedTransactions = selectedTransactions.filter(trans => trans.id !== transactionId);
    }
  });

  updateSelectedDisplay();
});

// Update select all checkbox state
function updateSelectAllCheckbox() {
  const totalCheckboxes = $('.transaction-checkbox').length;
  const checkedCheckboxes = $('.transaction-checkbox:checked').length;

  if (checkedCheckboxes === 0) {
    $('#select-all-transactions').prop('indeterminate', false).prop('checked', false);
  } else if (checkedCheckboxes === totalCheckboxes) {
    $('#select-all-transactions').prop('indeterminate', false).prop('checked', true);
  } else {
    $('#select-all-transactions').prop('indeterminate', true);
  }
}

// Clear selection
$('#clear-selection').on('click', function () {
  $('.transaction-checkbox').prop('checked', false);
  $('#select-all-transactions').prop('checked', false).prop('indeterminate', false);
  selectedTransactions = [];
  updateSelectedDisplay();
});

// Handle payment modal opening
$('#process-payment').on('click', function () {
  if (selectedTransactions.length === 0) {
    showAlert('warning', 'يرجى تحديد معاملات للدفع', 5000);
    return;
  }

  // Update modal summary
  $('#modal-selected-count').text(selectedTransactions.length);
  $('#modal-original-total').text(originalTotal.toFixed(2) + ' SAR');
  $('#maxAmountDisplay').text(originalTotal.toFixed(2) + ' SAR');
  $('#totalPaymentAmount').val(originalTotal.toFixed(2)).attr('max', originalTotal.toFixed(2));
  $('#modal-payment-total').text(originalTotal.toFixed(2) + ' SAR');

  // Populate selected transactions table
  updateSelectedTransactionsTable();
});

// Update selected transactions table in modal
function updateSelectedTransactionsTable() {
  const tableBody = $('#selectedTransactionsTable');
  tableBody.empty();

  selectedTransactions.forEach((trans, index) => {
    const row = `
      <tr data-transaction-id="${trans.id}">
        <td>${trans.sequence}</td>
        <td>${trans.driver}</td>
        <td>${trans.description}</td>
        <td>${trans.amount.toFixed(2)} SAR</td>
        <td class="payment-amount">${trans.amount.toFixed(2)} SAR</td>
        <td>
          <button type="button" class="btn btn-sm btn-outline-danger remove-transaction"
                  data-transaction-id="${trans.id}">
            <i class="ti ti-x"></i>
          </button>
        </td>
      </tr>
    `;
    tableBody.append(row);
  });
}

// Handle total amount change
$('#totalPaymentAmount').on('input', function () {
  const newTotal = parseFloat($(this).val()) || 0;
  const maxAmount = originalTotal;

  // Validate amount doesn't exceed maximum
  if (newTotal > maxAmount) {
    $(this).val(maxAmount.toFixed(2));
    showAlert('error', `المبلغ لا يمكن أن يتجاوز ${maxAmount.toFixed(2)} SAR`, 5000);
    return;
  }

  $('#modal-payment-total').text(newTotal.toFixed(2) + ' SAR');

  // Distribute the new amount using sequential allocation
  distributePaymentAmountSequential(newTotal);
});

// Sequential distribution: fill transactions in order, mark partial if needed
function distributePaymentAmountSequential(totalAmount) {
  if (selectedTransactions.length === 0) return;

  let remainingAmount = totalAmount;
  let transactionsToRemove = [];

  selectedTransactions.forEach((trans, index) => {
    let distributedAmount = 0;
    let status = 'unpaid'; // unpaid, partial, full

    if (remainingAmount > 0) {
      if (remainingAmount >= trans.amount) {
        // Full payment for this transaction
        distributedAmount = trans.amount;
        remainingAmount -= trans.amount;
        status = 'full';
      } else {
        // Partial payment for this transaction
        distributedAmount = remainingAmount;
        remainingAmount = 0;
        status = 'partial';
      }
    } else {
      // No amount left for this transaction
      status = 'unpaid';
      transactionsToRemove.push(trans.id);
    }

    // Update the table display with status indication
    const statusBadge =
      status === 'full'
        ? '<span class="badge bg-success">مدفوع كاملاً</span>'
        : status === 'partial'
          ? '<span class="badge bg-warning">مدفوع جزئياً</span>'
          : '<span class="badge bg-danger">غير مدفوع</span>';

    $(`tr[data-transaction-id="${trans.id}"] .payment-amount`).html(
      `${distributedAmount.toFixed(2)} SAR ${statusBadge}`
    );
  });

  // Show warning for unpaid transactions
  if (transactionsToRemove.length > 0) {
    const message = `تحذير: ${transactionsToRemove.length} معاملة لم يتم تخصيص أي مبلغ لها. يرجى إزالتها من التحديد أو زيادة المبلغ الإجمالي.`;
    $('#paymentWarning').remove(); // Remove existing warning
    $('#selectedTransactionsTable').after(`
      <div id="paymentWarning" class="alert alert-warning mt-3">
        <i class="ti ti-alert-triangle me-2"></i>${message}
        <button type="button" class="btn btn-sm btn-outline-warning ms-2" id="removeUnpaidTransactions">
          إزالة المعاملات غير المدفوعة
        </button>
      </div>
    `);
  } else {
    $('#paymentWarning').remove();
  }
}

// Handle removing unpaid transactions
$(document).on('click', '#removeUnpaidTransactions', function () {
  const totalAmount = parseFloat($('#totalPaymentAmount').val()) || 0;
  let remainingAmount = totalAmount;
  let transactionsToKeep = [];

  // Keep only transactions that can be paid (fully or partially)
  selectedTransactions.forEach(trans => {
    if (remainingAmount > 0) {
      transactionsToKeep.push(trans);
      if (remainingAmount >= trans.amount) {
        remainingAmount -= trans.amount;
      } else {
        remainingAmount = 0;
      }
    } else {
      // Remove checkbox from main table
      $(`.transaction-checkbox[value="${trans.id}"]`).prop('checked', false);
    }
  });

  // Update selected transactions
  selectedTransactions = transactionsToKeep;

  // Update displays
  updateSelectedDisplay();
  updateSelectAllCheckbox();
  updateSelectedTransactionsTable();

  // Recalculate distribution
  distributePaymentAmountSequential(totalAmount);

  // Update modal summary
  const newTotal = selectedTransactions.reduce((sum, trans) => sum + trans.amount, 0);
  $('#modal-original-total').text(newTotal.toFixed(2) + ' SAR');
  $('#maxAmountDisplay').text(newTotal.toFixed(2) + ' SAR');
  $('#modal-selected-count').text(selectedTransactions.length);
});

// Remove transaction from modal
$(document).on('click', '.remove-transaction', function () {
  const transactionId = $(this).data('transaction-id');

  console.log('Removing transaction:', transactionId); // Debug log

  // Remove from selectedTransactions array
  const originalLength = selectedTransactions.length;
  selectedTransactions = selectedTransactions.filter(trans => trans.id != transactionId);

  console.log('Transactions before removal:', originalLength, 'after removal:', selectedTransactions.length); // Debug log

  // Uncheck the checkbox in main table
  $(`.transaction-checkbox[value="${transactionId}"]`).prop('checked', false);

  // Update displays
  updateSelectedDisplay();
  updateSelectAllCheckbox();

  // Recalculate total and update modal
  const newTotal = selectedTransactions.reduce((sum, trans) => sum + trans.amount, 0);
  originalTotal = newTotal; // Update original total for validation

  $('#totalPaymentAmount').val(newTotal.toFixed(2)).attr('max', newTotal.toFixed(2));
  $('#modal-original-total').text(newTotal.toFixed(2) + ' SAR');
  $('#maxAmountDisplay').text(newTotal.toFixed(2) + ' SAR');
  $('#modal-payment-total').text(newTotal.toFixed(2) + ' SAR');
  $('#modal-selected-count').text(selectedTransactions.length);

  // Update selected transactions table
  updateSelectedTransactionsTable();

  // Recalculate distribution with new total
  distributePaymentAmountSequential(newTotal);

  // If no transactions left, close modal
  if (selectedTransactions.length === 0) {
    showAlert('info', 'تم إزالة جميع المعاملات. سيتم إغلاق نافذة الدفع.', 3000);
    setTimeout(() => {
      $('#paymentModal').modal('hide');
    }, 1500);
  } else {
    showAlert('success', `تم إزالة المعاملة بنجاح. المتبقي: ${selectedTransactions.length} معاملة`, 3000);
  }
});

// Handle payment confirmation
$('#confirmPayment').on('click', function () {
  const totalAmount = parseFloat($('#totalPaymentAmount').val());
  const notes = $('#paymentNotes').val();

  if (!totalAmount || totalAmount <= 0) {
    showAlert('error', 'يرجى إدخال مبلغ دفع صحيح', 5000);
    return;
  }

  if (selectedTransactions.length === 0) {
    showAlert('error', 'لا توجد معاملات محددة للدفع', 5000);
    return;
  }

  // Prepare payment data
  const paymentData = {
    team_id: teamID,
    total_amount: totalAmount,
    notes: notes,
    transactions: []
  };

  // Calculate distributed amounts for each transaction
  const originalSum = selectedTransactions.reduce((sum, trans) => sum + trans.amount, 0);
  const ratio = totalAmount / originalSum;
  let distributedTotal = 0;

  selectedTransactions.forEach((trans, index) => {
    let distributedAmount;

    if (index === selectedTransactions.length - 1) {
      // Last transaction gets the remainder
      distributedAmount = totalAmount - distributedTotal;
    } else {
      distributedAmount = Math.round(trans.amount * ratio * 100) / 100;
      distributedTotal += distributedAmount;
    }

    paymentData.transactions.push({
      id: trans.id,
      original_amount: trans.amount,
      payment_amount: distributedAmount
    });
  });

  // Show loading state
  $('#confirmPayment').prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>Processing...');

  // Send payment request
  $.ajax({
    url: `${baseUrl}admin/teams/${teamID}/pay-transactions`,
    method: 'POST',
    data: paymentData,
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    success: function (response) {
      if (response.success) {
        // Show success message
        showAlert('success', 'تم معالجة الدفع بنجاح!', 5000);

        // Close modal
        $('#paymentModal').modal('hide');

        // Clear selections
        selectedTransactions = [];
        $('.transaction-checkbox').prop('checked', false);
        $('#select-all-transactions').prop('checked', false).prop('indeterminate', false);
        updateSelectedDisplay();

        // Reload transactions table
        if (dt_trans) {
          dt_trans.ajax.reload();
        }

        // Reset form
        $('#paymentForm')[0].reset();
      } else {
        showAlert('error', 'خطأ: ' + (response.message || 'فشل في معالجة الدفع'), 7000);
      }
    },
    error: function (xhr) {
      let errorMessage = 'فشل في معالجة الدفع';
      if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMessage = xhr.responseJSON.message;
      } else if (xhr.responseJSON && xhr.responseJSON.errors) {
        errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
      }
      showAlert('error', 'خطأ: ' + errorMessage, 7000);
    },
    complete: function () {
      // Reset button state
      $('#confirmPayment').prop('disabled', false).html('<i class="ti ti-check me-1"></i>Confirm Payment');
    }
  });
});
