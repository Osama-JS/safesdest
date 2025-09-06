$(function () {
  'use strict';

  var start_from = null;
  var end_to = null;

  var dt_tasks_table = $('.datatables-tasks'),
    userView = baseUrl + 'customer/tasks/show/';

  var select2 = $('.flitter-status');
  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: __('Select status'),
      dropdownParent: $this.parent(),
      closeOnSelect: false
    });
  }
  var select2 = $('.flitter-payment');
  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: __('Select pyment status'),
      dropdownParent: $this.parent(),
      closeOnSelect: false
    });
  }

  var select2 = $('.flitter-payment-type');
  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: __('Select pyment type'),
      dropdownParent: $this.parent(),
      closeOnSelect: false
    });
  }

  // Tasks DataTable
  if (dt_tasks_table.length) {
    var dt_tasks = dt_tasks_table.DataTable({
      ajax: {
        url: baseUrl + 'customer/tasks/data',
        data: function (d) {
          d.from_date = start_from;
          d.to_date = end_to;
          d.status = $('#statusFilter').val();
          d.search_term = $('#searchFilter').val();
        }
      },
      columns: [
        { data: '' },
        { data: 'fake_id' },
        { data: 'id' },
        { data: 'total_price' },
        { data: 'pickup_address' },
        { data: 'delivery_address' },
        { data: 'driver_name' },
        { data: 'vehicle_info' },
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
          className: 'text-nowrap w-auto',
          render: function (data, type, full, meta) {
            return `<span class="text-primary fw-bold rounded border px-2">${full.total_price}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span class="text-truncate" title="${full.pickup_address}">${full.pickup_address}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return `<span class="text-truncate" title="${full.delivery_address}">${full.delivery_address}</span>`;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            let whatsapp = '';
            if (full.driver) {
              console.log(full.driver);
              const cleanNumber = full.whatsapp.replace(/[+\s-]/g, '');
              whatsapp = `
                <a href="https://wa.me/${cleanNumber}" target="_blank" class="text-success text-decoration-none">
                  <i class="ti ti-brand-whatsapp me-1"></i>${cleanNumber}
                  <i class="ti ti-external-link ms-1" style="font-size: 0.8rem;"></i>
                </a>
              `;
            } else {
              whatsapp = `<span class="text-muted"><i class="ti ti-minus me-1"></i>Not provided</span>`;
            }
            return full.driver
              ? `
            <p class="p-0 m-0">Name: ${full.driver.name}</p>
            <p class="p-0 m-0">Email: ${full.driver.email}</p>
            ${whatsapp}
            `
              : '<span class="text-muted">Not assigned</span>';
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            return `<small class="text-muted">${full.vehicle_info}</small>`;
          }
        },

        {
          targets: 8,
          render: function (data, type, full, meta) {
            var statusObj = {
              in_progress: { title: 'In Progress', class: 'bg-label-warning' },
              advertised: { title: 'Advertised', class: 'bg-label-info' },
              assign: { title: 'Assigned', class: 'bg-label-primary' },
              started: { title: 'Started', class: 'bg-label-info' },
              completed: { title: 'Completed', class: 'bg-label-success' },
              canceled: { title: 'Canceled', class: 'bg-label-danger' },
              refund: { title: 'Refund', class: 'bg-label-danger' }
            };

            // جلب الحالة أو جعلها Secondary كافتراضي
            var status = statusObj[full.status] || { title: full.status, class: 'bg-label-secondary' };

            return '<span class="badge ' + status.class + '">' + status.title + '</span>';
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
          // render: function (data, type, full, meta) {
          //   var actions = `
          //     <div class="d-flex align-items-center">
          //       <a href="${userView}${full.id}" class="text-body">
          //         <i class="ti ti-eye ti-sm me-2"></i>
          //       </a>
          //   `;

          //   if (full.can_track) {
          //     actions += `
          //       <a href="${baseUrl}customer/tasks/track/${full.id}" class="text-warning">
          //         <i class="ti ti-map-pin ti-sm me-2"></i>
          //       </a>
          //     `;
          //   }

          //   actions += `</div>`;
          //   return actions;
          // }

          render: function (data, type, full, meta) {
            // تحديد إمكانية الحذف بناءً على الحالة
            const canDelete =
              ['in_progress', 'advertised'].includes(full.status) &&
              full.payment !== 'completed' &&
              full.payment !== 'pending' &&
              !full.closed;

            return `
              <div class="d-flex align-items-center gap-2">

                <div class="dropdown">
                  <button class="btn btn-sm btn-icon  dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a href="${baseUrl}customer/tasks/track/${full.id}" class="dropdown-item status-record" data-id="${full.id}" data-name="${full.name}" data-status="${full.status}">View Details</a></li>
                    ${canDelete ? `<li><hr class="dropdown-divider"></li><li><a href="javascript:;" class="dropdown-item text-danger delete-task" data-id="${full.id}" data-status="${full.status}" data-payment="${full.payment}"><i class="ti ti-trash me-1"></i>Delete Task</a></li>` : ''}
                  </ul>
                </div>
              </div>`;
          }
        }
      ],
      order: [[0, 'desc']],
      dom: '<"row mx-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 10,
      lengthMenu: [10, 25, 50, 75, 100],

      buttons: [
        ` <div class="mt-5 mx-2">
            <input type="text" id="dateRange" class="form-control" placeholder="Select Date Range">
        </div>`,
        `<label class='me-2'>
          <select id="statusFilter" class="form-select d-inline-block w-auto ms-2 mt-5">
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

    $('#statusFilter').on('change', function () {
      console.log($('#statusFilter').val());
      dt_tasks.draw();
    });

    $('#searchFilter').on('input', function () {
      console.log($('#searchFilter').val());

      dt_tasks.draw();
    });
    $('.dataTables_filter').hide();
  }

  $('#dateRange').daterangepicker(
    {
      opens: 'left',
      locale: {
        format: 'DD MMM YYYY',
        cancelLabel: 'Cancel',
        applyLabel: 'Apply'
      },
      startDate: moment().startOf('month'),
      endDate: moment().endOf('month')
    },
    function (start, end, label) {
      const startDate = start.format('YYYY-MM-DD');
      const endDate = end.format('YYYY-MM-DD');
      console.log('oo');
      start_from = startDate;
      end_to = endDate;
      dt_tasks.draw();
    }
  );

  // Initialize report date range picker (only if element exists)
  if ($('#reportDateRange').length) {
    $('#reportDateRange').daterangepicker({
      opens: 'left',
      locale: {
        format: 'DD MMM YYYY',
        cancelLabel: 'إلغاء',
        applyLabel: 'تطبيق'
      },
      startDate: moment().startOf('month'),
      endDate: moment().endOf('month')
    });
  }

  // Handle report generation
  $('#generateReportBtn').on('click', function () {
    const dateRange = $('#reportDateRange').data('daterangepicker');
    const startDate = dateRange.startDate.format('YYYY-MM-DD');
    const endDate = dateRange.endDate.format('YYYY-MM-DD');

    // Get multiple selected values
    const statuses = $('#reportStatus').val() || [];
    const paymentStatuses = $('#reportPaymentStatus').val() || [];
    const paymentMethods = $('#reportPaymentMethod').val() || [];

    // Show loading state
    $(this).prop('disabled', true).html('<i class="ti ti-loader me-2"></i>Generating...');

    // Build URL with parameters
    const params = new URLSearchParams();
    params.append('from_date', startDate);
    params.append('to_date', endDate);

    // Add multiple values for each filter
    statuses.forEach(status => {
      if (status) params.append('status[]', status);
    });

    paymentStatuses.forEach(paymentStatus => {
      if (paymentStatus) params.append('payment_status[]', paymentStatus);
    });

    paymentMethods.forEach(paymentMethod => {
      if (paymentMethod) params.append('payment_method[]', paymentMethod);
    });

    // Open PDF in new window
    const reportUrl = `${baseUrl}customer/tasks/report?${params.toString()}`;
    window.open(reportUrl, '_blank');

    // Reset button state
    setTimeout(() => {
      $(this).prop('disabled', false).html('<i class="ti ti-file-type-pdf me-2"></i>Generate PDF Report');
      $('#reportModal').modal('hide');
    }, 1000);
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
        if (
          [
            'in_progress',
            'assign',
            'started',
            'in pickup point',
            'loading',
            'in the way',
            'in delivery point',
            'unloading'
          ].includes(data.status)
        ) {
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
