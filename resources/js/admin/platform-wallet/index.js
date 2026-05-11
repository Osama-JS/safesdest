/**
 * ===================================================================
 * PLATFORM WALLET MANAGEMENT
 * ===================================================================
 * This file manages the platform wallet functionality including
 * commission tracking, statistics, and data export features.
 */

'use strict';
import { showAlert } from '../../ajax';

// ===================================================================
// INITIALIZATION AND SETUP
// ===================================================================

$(function () {
  /**
   * متغيرات عامة للجدول والمسارات
   */
  var dt_platform_wallet = $('.datatables-platform-wallet'),
    walletView = baseUrl + 'admin/platform-wallet/';

  /**
   * إعداد CSRF token لطلبات Ajax
   */
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // ===================================================================
  // DATATABLE INITIALIZATION
  // ===================================================================

  /**
   * تهيئة جدول بيانات محفظة المنصة
   * يعرض العمولات مع إمكانيات الفلترة والبحث
   */
  if (dt_platform_wallet.length) {
    var dt_platform = dt_platform_wallet.DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: walletView + 'data',
        type: 'GET',
        data: function (d) {
          // إضافة بيانات الفلاتر إلى الطلب
          d.date_from = $('#dateFrom').val();
          d.date_to = $('#dateTo').val();
          d.payment_status = $('#paymentStatus').val();
          d.commission_type = $('#commissionType').val();
          d.task_status = $('#taskStatus').val();
          d.is_closed = $('#isClosed').val();
        }
      },
      columns: [
        { data: 'id' }, // رقم المهمة
        { data: 'customer' }, // العميل
        { data: 'driver' }, // السائق
        { data: 'team' }, // الفريق
        { data: null }, // المسار (مخصص)
        { data: 'total_price' }, // السعر الإجمالي
        { data: 'commission' }, // العمولة
        { data: 'commission_type' }, // نوع العمولة
        { data: 'payment_status' }, // حالة الدفع
        { data: 'task_status' }, // حالة المهمة
        { data: 'completed_at' }, // تاريخ الإكمال
        { data: null } // الإجراءات (مخصص)
      ],
      columnDefs: [
        {
          targets: 0,
          render: function (data, type, full, meta) {
            return `<span class="fw-bold text-primary">#${data}</span>`;
          }
        },
        {
          targets: 1,
          render: function (data, type, full, meta) {
            return `<span class="text-truncate" title="${data}">${data}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return data !== 'N/A' ? `<span class="text-success">${data}</span>` : '<span class="text-muted">N/A</span>';
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return data !== 'N/A' ? `<span class="text-info">${data}</span>` : '<span class="text-muted">N/A</span>';
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            const pickup = full.pickup_address !== 'N/A' ? full.pickup_address.substring(0, 30) + '...' : 'N/A';
            const delivery = full.delivery_address !== 'N/A' ? full.delivery_address.substring(0, 30) + '...' : 'N/A';
            return `
              <div class="small">
                <div class="text-muted">From: ${pickup}</div>
                <div class="text-muted">To: ${delivery}</div>
              </div>
            `;
          }
        },
        {
          targets: 5,
          className: 'text-nowrap w-auto',
          render: function (data, type, full, meta) {
            return `<span class="fw-bold text-dark">${data} SAR</span>`;
          }
        },
        {
          targets: 6,
          className: 'text-nowrap w-auto',
          render: function (data, type, full, meta) {
            return `<span class="commission-badge bg-success text-white">${data} SAR</span>`;
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            const badgeClass = data === 'Dynamic' ? 'bg-primary' : 'bg-secondary';
            return `<span class="badge ${badgeClass}">${data}</span>`;
          }
        },
        {
          targets: 8,
          render: function (data, type, full, meta) {
            let badgeClass = '';
            let displayText = '';

            switch (data) {
              case 'pending':
                badgeClass = 'bg-warning';
                displayText = 'Pending';
                break;
              case 'just_commission':
                badgeClass = 'bg-info';
                displayText = 'Commission Only';
                break;
              case 'all':
                badgeClass = 'bg-success';
                displayText = 'Fully Paid';
                break;
              default:
                badgeClass = 'bg-secondary';
                displayText = data;
            }

            return `<span class="payment-status-badge ${badgeClass} text-white">${displayText}</span>`;
          }
        },
        {
          targets: 9,
          render: function (data, type, full, meta) {
            let statusClasses = {
              pending_payment: 'bg-label-warning',
              payment_failed: 'bg-label-danger',
              advertised: 'bg-label-secondary',
              in_progress: 'bg-label-info',
              assign: 'bg-label-primary',
              accepted: 'bg-label-primary',
              started: 'bg-label-dark',
              'in pickup point': 'bg-label-dark',
              loading: 'bg-label-dark',
              'in the way': 'bg-label-dark',
              'in delivery point': 'bg-label-dark',
              unloading: 'bg-label-dark',
              completed: 'bg-label-success',
              canceled: 'bg-label-danger',
              refund: 'bg-label-danger'
            };

            let badgeClass = statusClasses[data] || 'bg-label-light';
            let closedBadge = full.is_closed
              ? '<span class="badge badge-dot bg-success ms-1" title="Closed"></span>'
              : '<span class="badge badge-dot bg-warning ms-1" title="Open"></span>';

            return `
              <div class="d-flex align-items-center">
                <span class="badge ${badgeClass} text-capitalize">${data.replace('_', ' ')}</span>
                ${closedBadge}
              </div>
            `;
          }
        },
        {
          targets: 10,
          render: function (data, type, full, meta) {
            return `<span class="text-muted small">${data}</span>`;
          }
        },
        {
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `
              <div class="d-flex align-items-center gap-1">
                <a href="${baseUrl}admin/tasks/${full.id}" class="btn btn-sm " title="View Task">
                  <i class="ti ti-eye"></i>
                </a>

              </div>
            `;
          }
        }
      ],
      order: [[9, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 25,
      lengthMenu: [10, 25, 50, 75, 100],
      language: {
        search: '',
        searchPlaceholder: 'Search commissions...',
        lengthMenu: '_MENU_',
        info: 'Showing _START_ to _END_ of _TOTAL_ commissions',
        infoEmpty: 'No commissions found',
        infoFiltered: '(filtered from _MAX_ total commissions)',
        paginate: {
          first: 'First',
          last: 'Last',
          next: 'Next',
          previous: 'Previous'
        }
      },
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Commission Details for Task #' + data.id;
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.hidden
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

  // Load statistics
  function loadStatistics() {
    $.ajax({
      url: walletView + 'statistics',
      method: 'GET',
      data: {
        date_from: $('#dateFrom').val(),
        date_to: $('#dateTo').val(),
        task_status: $('#taskStatus').val(),
        is_closed: $('#isClosed').val(),
        payment_status: $('#paymentStatus').val(),
        commission_type: $('#commissionType').val()
      },
      success: function (response) {
        console.log('Statistics response:', response); // Debug log
        if (response.success && response.data) {
          updateStatisticsCards(response.data);
        } else {
          console.error('Invalid statistics response:', response);
          showAlert('error', 'Invalid statistics data received');
        }
      },
      error: function (xhr, status, error) {
        console.error('Statistics AJAX error:', error);
        console.error('Status:', status);
        console.error('Response:', xhr.responseText);
        showAlert('error', 'Failed to load statistics: ' + error);
      }
    });
  }

  // Update statistics cards
  function updateStatisticsCards(data) {
    const statisticsHTML = `
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100">
          <div class="card-body d-flex align-items-center">
            <div class="stats-icon bg-primary me-3">
              <i class="ti ti-wallet"></i>
            </div>
            <div>
              <p class="stats-value text-primary">${data.total_commissions.toLocaleString()} SAR</p>
              <p class="stats-label">Total Commissions</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100">
          <div class="card-body d-flex align-items-center">
            <div class="stats-icon bg-success me-3">
              <i class="ti ti-check"></i>
            </div>
            <div>
              <p class="stats-value text-success">${data.paid_commissions.toLocaleString()} SAR</p>
              <p class="stats-label">Paid Commissions</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100">
          <div class="card-body d-flex align-items-center">
            <div class="stats-icon bg-warning me-3">
              <i class="ti ti-clock"></i>
            </div>
            <div>
              <p class="stats-value text-warning">${data.pending_commissions.toLocaleString()} SAR</p>
              <p class="stats-label">Pending Commissions</p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card h-100">
          <div class="card-body d-flex align-items-center">
            <div class="stats-icon bg-info me-3">
              <i class="ti ti-percentage"></i>
            </div>
            <div>
              <p class="stats-value text-info">${data.collection_rate}%</p>
              <p class="stats-label">Collection Rate</p>
            </div>
          </div>
        </div>
      </div>
    `;

    $('#statisticsCards').html(statisticsHTML);
  }

  // Apply filters
  $('#applyFilters').on('click', function () {
    dt_platform.ajax.reload();
    loadStatistics();
  });

  // Clear filters
  $('#clearFilters').on('click', function () {
    $('#dateFrom, #dateTo, #paymentStatus, #commissionType, #taskStatus, #isClosed').val('');
    dt_platform.ajax.reload();
    loadStatistics();
  });

  // Refresh table
  $('#refreshTable').on('click', function () {
    dt_platform.ajax.reload();
    loadStatistics();
  });

  // Export data CSV
  $('#exportBtn').on('click', function () {
    const params = new URLSearchParams({
      date_from: $('#dateFrom').val() || '',
      date_to: $('#dateTo').val() || '',
      payment_status: $('#paymentStatus').val() || '',
      commission_type: $('#commissionType').val() || '',
      task_status: $('#taskStatus').val() || '',
      is_closed: $('#isClosed').val() || ''
    });

    window.open(walletView + 'export?' + params.toString(), '_blank');
  });

  // Export data Excel
  $('#exportExcel').on('click', function () {
    const params = new URLSearchParams({
      date_from: $('#dateFrom').val() || '',
      date_to: $('#dateTo').val() || '',
      payment_status: $('#paymentStatus').val() || '',
      commission_type: $('#commissionType').val() || '',
      task_status: $('#taskStatus').val() || '',
      is_closed: $('#isClosed').val() || ''
    });

    window.open(walletView + 'export-excel?' + params.toString(), '_blank');
  });

  // View details
  $(document).on('click', '.view-details', function () {
    const taskId = $(this).data('id');
    window.open(baseUrl + 'admin/tasks/' + taskId, '_blank');
  });

  // Initialize
  loadStatistics();
});
