/**
 * Team Tasks Management Page
 */

'use strict';
import { initDashboard, showAlert } from './common.js';

$(function () {
  // Initialize common dashboard functionality
  initDashboard();
  var dt_task_table = $('.datatables-tasks');

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize date range picker
  if ($('#date-range').length) {
    $('#date-range').flatpickr({
      mode: 'range',
      dateFormat: 'Y-m-d',
      placeholder: 'Select date range'
    });
  }

  // Tasks datatable
  // if (dt_task_table.length) {
  //   var dt_task = dt_task_table.DataTable({
  //     processing: true,
  //     serverSide: true,
  //     ajax: {
  //       url: baseUrl + 'admin/teams/tasks',
  //       data: function (d) {
  //         d.status = $('#status-filter').val();
  //         d.driver = $('#driver-filter').val();
  //         d.payment_status = $('#payment-filter').val();
  //         d.date_range = $('#date-range').val();
  //         d.min_price = $('#min-price').val();
  //         d.max_price = $('#max-price').val();
  //         d.team = teamID;
  //       }
  //     },
  //     columns: [
  //       { data: '' }, // للـ control (responsive)
  //       { data: 'id' },
  //       { data: 'price' },
  //       { data: 'driver' },
  //       { data: 'address' },
  //       { data: 'start' },
  //       { data: 'complete' },
  //       { data: 'status' },
  //       { data: 'payment_status' },
  //       { data: 'created_at' },
  //       { data: null } // actions
  //     ],
  //     columnDefs: [
  //       {
  //         targets: 0,
  //         className: 'control',
  //         searchable: false,
  //         orderable: false,
  //         responsivePriority: 1,
  //         render: function () {
  //           return '';
  //         }
  //       },
  //       {
  //         targets: 1,
  //         searchable: false,
  //         orderable: false,
  //         render: function (data, type, full, meta) {
  //           return `<span class="fw-bold text-primary">#${full.id}</span>`;
  //         }
  //       },
  //       {
  //         targets: 2,
  //         searchable: false,
  //         orderable: false,
  //         render: function (data, type, full, meta) {
  //           return `<span class="badge bg-label-success fs-6">${full.price} SAR</span>`;
  //         }
  //       },
  //       {
  //         targets: 3,
  //         render: function (data, type, full, meta) {
  //           if (full.driver) {
  //             return `<div class="d-flex align-items-center">
  //                       <div class="avatar avatar-xs me-2">
  //                         <div class="avatar-initial bg-label-primary rounded-circle">
  //                           ${full.driver.charAt(0).toUpperCase()}
  //                         </div>
  //                       </div>
  //                       <span>${full.driver}</span>
  //                     </div>`;
  //           }
  //           return '<span class="text-muted">{{ __("Unassigned") }}</span>';
  //         }
  //       },
  //       {
  //         targets: 4,
  //         render: function (data, type, full, meta) {
  //           return `<span class="text-truncate" style="max-width: 200px;" title="${full.address}">${full.address}</span>`;
  //         }
  //       },
  //       {
  //         targets: 5,
  //         render: function (data, type, full, meta) {
  //           return `<span class="text-muted">${full.start || 'N/A'}</span>`;
  //         }
  //       },
  //       {
  //         targets: 6,
  //         render: function (data, type, full, meta) {
  //           return `<span class="text-muted">${full.complete || 'N/A'}</span>`;
  //         }
  //       },
  //       {
  //         targets: 7,
  //         className: 'text-center',
  //         render: function (data, type, full, meta) {
  //           let badgeClass = 'secondary';
  //           let icon = 'ti-clock';

  //           switch (full.status) {
  //             case 'pending':
  //               badgeClass = 'warning';
  //               icon = 'ti-clock';
  //               break;
  //             case 'advertised':
  //               badgeClass = 'info';
  //               icon = 'ti-speakerphone';
  //               break;
  //             case 'in_progress':
  //             case 'assign':
  //             case 'started':
  //               badgeClass = 'primary';
  //               icon = 'ti-progress';
  //               break;
  //             case 'loading':
  //             case 'in pickup point':
  //               badgeClass = 'warning';
  //               icon = 'ti-truck-loading';
  //               break;
  //             case 'in the way':
  //               badgeClass = 'info';
  //               icon = 'ti-truck';
  //               break;
  //             case 'in delivery point':
  //             case 'unloading':
  //               badgeClass = 'warning';
  //               icon = 'ti-truck-delivery';
  //               break;
  //             case 'completed':
  //               badgeClass = 'success';
  //               icon = 'ti-check';
  //               break;
  //             case 'canceled':
  //               badgeClass = 'danger';
  //               icon = 'ti-x';
  //               break;
  //           }

  //           return `<span class="badge bg-label-${badgeClass}">
  //                     <i class="ti ${icon} me-1"></i>${full.status}
  //                   </span>`;
  //         }
  //       },
  //       {
  //         targets: 8,
  //         className: 'text-center',
  //         render: function (data, type, full, meta) {
  //           let badgeClass = 'secondary';
  //           let icon = 'ti-clock';

  //           switch (full.payment_status) {
  //             case 'paid':
  //               badgeClass = 'success';
  //               icon = 'ti-check';
  //               break;
  //             case 'pending':
  //               badgeClass = 'warning';
  //               icon = 'ti-clock';
  //               break;
  //             case 'partial':
  //               badgeClass = 'info';
  //               icon = 'ti-progress';
  //               break;
  //           }

  //           return `<span class="badge bg-label-${badgeClass}">
  //                     <i class="ti ${icon} me-1"></i>${full.payment_status || 'pending'}
  //                   </span>`;
  //         }
  //       },
  //       {
  //         targets: 9,
  //         render: function (data, type, full, meta) {
  //           return full.created_at;
  //         }
  //       },
  //       {
  //         targets: 10,
  //         title: 'Actions',
  //         searchable: false,
  //         orderable: false,
  //         render: function (data, type, full, meta) {
  //           return `
  //             <div class="d-flex align-items-center gap-2">
  //               <button class="btn btn-sm btn-icon view-task" data-id="${full.id}" data-bs-toggle="modal" data-bs-target="#taskDetailsModal">
  //                 <i class="ti ti-eye"></i>
  //               </button>
  //               <button class="btn btn-sm btn-icon edit-task" data-id="${full.id}">
  //                 <i class="ti ti-edit"></i>
  //               </button>
  //               <div class="dropdown">
  //                 <button class="btn btn-sm btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
  //                   <i class="ti ti-dots-vertical"></i>
  //                 </button>
  //                 <ul class="dropdown-menu dropdown-menu-end">
  //                   <li><a href="${baseUrl}admin/tasks/show/${full.id}" class="dropdown-item">
  //                     <i class="ti ti-external-link me-2"></i>View Full Details
  //                   </a></li>
  //                   <li><a href="#" class="dropdown-item assign-driver" data-id="${full.id}">
  //                     <i class="ti ti-user-plus me-2"></i>Assign Driver
  //                   </a></li>
  //                   <li><a href="#" class="dropdown-item change-status" data-id="${full.id}">
  //                     <i class="ti ti-edit me-2"></i>Change Status
  //                   </a></li>
  //                   <li><hr class="dropdown-divider"></li>
  //                   <li><a href="#" class="dropdown-item text-danger cancel-task" data-id="${full.id}">
  //                     <i class="ti ti-x me-2"></i>Cancel Task
  //                   </a></li>
  //                 </ul>
  //               </div>
  //             </div>
  //           `;
  //         }
  //       }
  //     ],
  //     dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
  //     displayLength: 10,
  //     lengthMenu: [10, 25, 50, 75, 100],
  //     responsive: {
  //       details: {
  //         display: $.fn.dataTable.Responsive.display.modal({
  //           header: function (row) {
  //             var data = row.data();
  //             return 'Task Details #' + data.id;
  //           }
  //         }),
  //         type: 'column',
  //         renderer: function (api, rowIdx, columns) {
  //           var data = $.map(columns, function (col) {
  //             return col.title
  //               ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
  //                     <td>${col.title}:</td>
  //                     <td>${col.data}</td>
  //                  </tr>`
  //               : '';
  //           }).join('');
  //           return $('<table class="table"/><tbody />').append(data);
  //         }
  //       }
  //     }
  //   });

  //   // Filter event handlers
  //   $('#status-filter, #driver-filter, #payment-filter, #date-range, #min-price, #max-price').on(
  //     'change input',
  //     function () {
  //       dt_task.draw();
  //     }
  //   );

  //   // Reset filters
  //   $('#reset-filters').on('click', function () {
  //     $('#status-filter, #driver-filter, #payment-filter').val('');
  //     $('#date-range, #min-price, #max-price').val('');
  //     dt_task.draw();
  //   });

  //   // Refresh tasks
  //   $('#refresh-tasks').on('click', function () {
  //     dt_task.draw();
  //     updateQuickStats();
  //   });

  //   // Export tasks
  //   $('#export-tasks').on('click', function () {
  //     // Implementation for export functionality
  //     showAlert('info', 'Export functionality will be implemented soon');
  //   });

  //   document.dispatchEvent(new CustomEvent('dtTaskReady', { detail: dt_task }));
  // }

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
        { data: 'created' }, // actions
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
          render: function (data, type, full, meta) {
            return `<span>${full.created_at}</span>`;
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

    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_task }));
  }

  $('.dataTables_filter').hide();

  // View task details
  $(document).on('click', '.view-task', function () {
    var taskId = $(this).data('id');
    loadTaskDetails(taskId);
  });

  // Edit task
  $(document).on('click', '.edit-task', function () {
    var taskId = $(this).data('id');
    window.location.href = baseUrl + 'admin/tasks/show/' + taskId;
  });

  // Assign driver
  $(document).on('click', '.assign-driver', function (e) {
    e.preventDefault();
    var taskId = $(this).data('id');
    // Implementation for driver assignment
    showAlert('info', 'Driver assignment functionality will be implemented');
  });

  // Change status
  $(document).on('click', '.change-status', function (e) {
    e.preventDefault();
    var taskId = $(this).data('id');
    // Implementation for status change
    showAlert('info', 'Status change functionality will be implemented');
  });

  // Cancel task
  $(document).on('click', '.cancel-task', function (e) {
    e.preventDefault();
    var taskId = $(this).data('id');
    // Implementation for task cancellation
    showAlert('info', 'Task cancellation functionality will be implemented');
  });
});

/**
 * Load task details
 */
function loadTaskDetails(taskId) {
  $('#task-details-content').html('<div class="text-center"><i class="ti ti-loader ti-spin"></i> Loading...</div>');

  // This would typically load task details via AJAX
  setTimeout(() => {
    $('#task-details-content').html(`
      <div class="row">
        <div class="col-md-6">
          <h6>Task Information</h6>
          <p><strong>Task ID:</strong> #${taskId}</p>
          <p><strong>Status:</strong> <span class="badge bg-label-info">In Progress</span></p>
          <p><strong>Price:</strong> 150.00 SAR</p>
        </div>
        <div class="col-md-6">
          <h6>Driver Information</h6>
          <p><strong>Driver:</strong> John Doe</p>
          <p><strong>Phone:</strong> +966 50 123 4567</p>
          <p><strong>Status:</strong> <span class="badge bg-label-success">Online</span></p>
        </div>
      </div>
    `);
  }, 1000);
}

/**
 * Update quick stats
 */
function updateQuickStats() {
  // This would typically update stats via AJAX
  // For now, just add a visual indicator
  $('.avatar-initial').css('opacity', '0.6');
  setTimeout(() => {
    $('.avatar-initial').css('opacity', '1');
  }, 1000);
}
