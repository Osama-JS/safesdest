$(function () {
  'use strict';

  var dt_tasks_table = $('.datatables-tasks'),
    userView = baseUrl + 'customer/tasks/show/';

  // Tasks DataTable
  if (dt_tasks_table.length) {
    var dt_tasks = dt_tasks_table.DataTable({
      ajax: {
        url: baseUrl + 'customer/tasks/data',
        data: function (d) {
          d.from_date = $('#from_date').val();
          d.to_date = $('#to_date').val();
          d.status = $('#status_filter').val();
        }
      },
      columns: [
        { data: '' },
        { data: 'fake_id' },
        { data: 'id' },
        { data: 'pickup_address' },
        { data: 'delivery_address' },
        { data: 'driver_name' },
        { data: 'vehicle_info' },
        { data: 'total_price' },
        { data: 'status' },
        { data: 'created_at' },
        { data: null }
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
          searchable: false,
          visible: false
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return `<span class="fw-bold">#${full.id}</span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span class="text-truncate" title="${full.pickup_address}">${full.pickup_address}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span class="text-truncate" title="${full.delivery_address}">${full.delivery_address}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return full.driver_name || '<span class="text-muted">Not assigned</span>';
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            return `<small class="text-muted">${full.vehicle_info}</small>`;
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            return `<span class="text-success fw-bold">${full.total_price}</span>`;
          }
        },
        {
          targets: 8,
          render: function (data, type, full, meta) {
            var statusObj = {
              'in_progress': { title: 'In Progress', class: 'bg-label-warning' },
              'advertised': { title: 'Advertised', class: 'bg-label-info' },
              'assign': { title: 'Assigned', class: 'bg-label-primary' },
              'started': { title: 'Started', class: 'bg-label-warning' },
              'completed': { title: 'Completed', class: 'bg-label-success' },
              'cancelled': { title: 'Cancelled', class: 'bg-label-danger' }
            };
            return (
              '<span class="badge ' +
              statusObj[full.status]?.class +
              '">' +
              (statusObj[full.status]?.title || full.status) +
              '</span>'
            );
          }
        },
        {
          targets: 9,
          render: function (data, type, full, meta) {
            return full.created_at;
          }
        },
        {
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            var actions = `
              <div class="d-flex align-items-center">
                <a href="${userView}${full.id}" class="text-body">
                  <i class="ti ti-eye ti-sm me-2"></i>
                </a>
            `;
            
            if (full.can_track) {
              actions += `
                <a href="${baseUrl}customer/tasks/track/${full.id}" class="text-warning">
                  <i class="ti ti-map-pin ti-sm me-2"></i>
                </a>
              `;
            }
            
            actions += `</div>`;
            return actions;
          }
        }
      ],
      order: [[1, 'desc']],
      dom: '<"row mx-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 10,
      lengthMenu: [10, 25, 50, 75, 100],
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
                columns: [2, 3, 4, 5, 6, 7, 8, 9],
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
                  .css('color', headingColor)
                  .css('border-color', borderColor)
                  .css('background-color', bodyBg);
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
                columns: [2, 3, 4, 5, 6, 7, 8, 9],
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
                columns: [2, 3, 4, 5, 6, 7, 8, 9],
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
                columns: [2, 3, 4, 5, 6, 7, 8, 9],
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
                columns: [2, 3, 4, 5, 6, 7, 8, 9],
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
              return 'Details of Task #' + data['id'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== ''
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

  // Date range picker
  $('#from_date, #to_date').datepicker({
    format: 'yyyy-mm-dd',
    autoclose: true,
    todayHighlight: true
  });

  // Filter handlers
  $('#from_date, #to_date, #status_filter').on('change', function () {
    dt_tasks.draw();
  });

  // Load statistics
  loadTaskStatistics();

  function loadTaskStatistics() {
    // This would typically come from an API endpoint
    // For now, we'll update after the table loads
    dt_tasks.on('draw', function () {
      var info = dt_tasks.page.info();
      $('#total-tasks').text(info.recordsTotal);
      
      // Count active tasks (you might want to get this from server)
      var activeCount = 0;
      var completedCount = 0;
      var totalSpent = 0;
      
      dt_tasks.rows().every(function () {
        var data = this.data();
        if (['assign', 'started', 'in pickup point', 'loading', 'in the way', 'in delivery point', 'unloading'].includes(data.status)) {
          activeCount++;
        }
        if (data.status === 'completed') {
          completedCount++;
        }
        if (data.total_price && data.status === 'completed') {
          totalSpent += parseFloat(data.total_price.replace(/[^\d.-]/g, ''));
        }
      });
      
      $('#active-tasks').text(activeCount);
      $('#completed-tasks').text(completedCount);
      $('#total-spent').text(totalSpent.toFixed(2) + ' SAR');
    });
  }
});
