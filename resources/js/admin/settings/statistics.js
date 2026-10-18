/**
 * System Statistics Dashboard
 */

'use strict';
import { showAlert } from '../../ajax';

$(function () {
  // Global translator helper fallback
  const trans = typeof window.__ === 'function' ? window.__ : (key) => key;

  // Charts variables
  var dailyTasksChart, dailyRevenueChart, statusDistributionChart, dailyCommissionChart;

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Flatpickr instances with dynamic locale
  var dateFromPicker = null;
  var dateToPicker = null;
  var currentLocale = $('html').attr('lang') || 'ar';
  var flatpickrLocale = currentLocale.startsWith('ar') ? 'ar' : 'default';

  if ($('.flatpickr-date').length) {
    dateFromPicker = $('#dateFromFilter').flatpickr({
      dateFormat: 'Y-m-d',
      locale: flatpickrLocale
    });

    dateToPicker = $('#dateToFilter').flatpickr({
      dateFormat: 'Y-m-d',
      locale: flatpickrLocale
    });

    // Default to last 30 days
    setPresetRange('last_30_days', false);
  }

  // Load initial statistics
  loadStatistics();

  // Manual Update button
  $('#updateStatistics').on('click', function () {
    $('.preset-filter-btn').removeClass('active');
    loadStatistics();
  });

  // Quick Preset Filters
  $(document).on('click', '.preset-filter-btn', function () {
    var preset = $(this).data('preset');
    $('.preset-filter-btn').removeClass('active');
    $(this).addClass('active');

    setPresetRange(preset, true);
  });

  // Helper to format date YYYY-MM-DD in local time
  function formatDateLocal(date) {
    var year = date.getFullYear();
    var month = String(date.getMonth() + 1).padStart(2, '0');
    var day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  // Set Preset Date Ranges
  function setPresetRange(preset, triggerLoad = true) {
    var now = new Date();
    var fromDate = new Date();
    var toDate = new Date();

    switch (preset) {
      case 'today':
        break;

      case 'yesterday':
        fromDate.setDate(now.getDate() - 1);
        toDate.setDate(now.getDate() - 1);
        break;

      case 'last_7_days':
        fromDate.setDate(now.getDate() - 6);
        break;

      case 'last_30_days':
        fromDate.setDate(now.getDate() - 29);
        break;

      case 'this_month':
        fromDate = new Date(now.getFullYear(), now.getMonth(), 1);
        break;

      case 'last_month':
        fromDate = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        toDate = new Date(now.getFullYear(), now.getMonth(), 0);
        break;

      case 'this_year':
        fromDate = new Date(now.getFullYear(), 0, 1);
        break;

      default:
        fromDate.setDate(now.getDate() - 29);
        break;
    }

    var fromStr = formatDateLocal(fromDate);
    var toStr = formatDateLocal(toDate);

    if (dateFromPicker) {
      dateFromPicker.setDate(fromStr, true);
    } else {
      $('#dateFromFilter').val(fromStr);
    }

    if (dateToPicker) {
      dateToPicker.setDate(toStr, true);
    } else {
      $('#dateToFilter').val(toStr);
    }

    if (triggerLoad) {
      loadStatistics();
    }
  }

  // Load statistics function
  function loadStatistics() {
    var dateFrom = $('#dateFromFilter').val();
    var dateTo = $('#dateToFilter').val();

    var updateBtn = $('#updateStatistics');
    var originalBtnText = updateBtn.html();
    updateBtn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>');

    $.ajax({
      url: baseUrl + 'admin/settings/statistics/data',
      method: 'GET',
      data: {
        date_from: dateFrom,
        date_to: dateTo
      },
      success: function (response) {
        if (response.status === 1) {
          updateStatisticsCards(response.data);
          updateLeaderboards(response.data.top_performers);
          updateCharts(response.data.charts);
          updateDetailsTables(response.data);
        } else {
          showAlert('error', response.error || trans('Failed to load statistics'));
        }
      },
      error: function (xhr) {
        var msg = trans('An error occurred while loading statistics');
        if (xhr.responseJSON && xhr.responseJSON.message) {
          msg = xhr.responseJSON.message;
        }
        showAlert('error', msg);
      },
      complete: function () {
        updateBtn.prop('disabled', false).html(originalBtnText);
      }
    });
  }

  // Render growth badge helper
  function renderGrowthBadge($container, rate) {
    if (rate === null || rate === undefined) {
      $container.html('<span class="badge bg-label-secondary">--</span>');
      return;
    }

    var numericRate = parseFloat(rate);
    var id = $container.attr('id');
    var growthTitle = trans('Compared to previous period');

    if (numericRate > 0) {
      $container.replaceWith(`<span id="${id}" class="badge bg-label-success" title="${growthTitle}"><i class="ti ti-arrow-up-right me-1"></i>+${numericRate}%</span>`);
    } else if (numericRate < 0) {
      $container.replaceWith(`<span id="${id}" class="badge bg-label-danger" title="${growthTitle}"><i class="ti ti-arrow-down-right me-1"></i>${numericRate}%</span>`);
    } else {
      $container.replaceWith(`<span id="${id}" class="badge bg-label-secondary" title="${growthTitle}">0%</span>`);
    }
  }

  // Update statistics cards
  function updateStatisticsCards(data) {
    var tasks = data.tasks || {};
    var fin = data.financial || {};
    var op = data.operational || {};
    var growth = data.growth || {};

    // Card 1: Tasks
    $('#totalTasks').text(tasks.total_tasks || 0);
    $('#closedTasks').text(tasks.closed_tasks || 0);
    $('#unclosedTasks').text(tasks.unclosed_tasks || 0);

    // Card 2: Revenue (Total, Closed, Unclosed)
    $('#totalRevenue').text(formatCurrency(fin.total_revenue || 0));
    $('#closedRevenue').text(formatCurrency(fin.closed_revenue || 0));
    $('#unclosedRevenue').text(formatCurrency(fin.unclosed_revenue || 0));

    // Card 3: Commission (Total, Closed, Unclosed)
    $('#totalCommission').text(formatCurrency(fin.total_commission || 0));
    $('#closedCommission').text(formatCurrency(fin.closed_commission || 0));
    $('#unclosedCommission').text(formatCurrency(fin.unclosed_commission || 0));

    // Card 4: Net Realized Commission (Closed) & Pending/Deductions
    $('#closedNetCommission').text(formatCurrency(fin.closed_net_commission || fin.net_commission || 0));
    $('#unclosedNetCommission').text(formatCurrency(fin.unclosed_net_commission || 0));
    $('#totalDeductions').text(formatCurrency(fin.closed_deductions || fin.total_deductions || 0));

    // Growth rates
    renderGrowthBadge($('#tasksGrowthBadge'), growth.tasks);
    renderGrowthBadge($('#revenueGrowthBadge'), growth.revenue);
    renderGrowthBadge($('#commissionGrowthBadge'), growth.commission);
    renderGrowthBadge($('#netCommissionGrowthBadge'), growth.net_commission);

    // Secondary metrics
    $('#inProgressTasks').text(tasks.in_progress_tasks || 0);
    $('#completedTasks').text(tasks.completed_tasks || 0);
    $('#cancelledTasks').text(tasks.cancelled_tasks || 0);
    $('#completionRate').text((tasks.completion_rate || 0) + '%');

    // Driver payouts (Total & Closed)
    $('#totalDriverPayouts').text(formatCurrency(fin.total_driver_payouts || 0));
    $('#closedDriverPayouts').text(formatCurrency(fin.closed_driver_payouts || fin.driver_payouts || 0));

    // Operational fleet
    var activeDrivers = op.active_drivers || 0;
    var totalDrivers = op.total_drivers || 0;
    $('#operationalFleet').text(`${activeDrivers} / ${totalDrivers}`);
  }

  // Update Leaderboards (Top Customers and Top Drivers with Closed/Unclosed)
  function updateLeaderboards(topPerformers) {
    topPerformers = topPerformers || {};
    var customers = topPerformers.customers || [];
    var drivers = topPerformers.drivers || [];

    // Top Customers Table
    var customerRows = '';
    if (customers.length > 0) {
      customers.forEach(function (cust, index) {
        var rankBadge = getRankBadge(index + 1);
        customerRows += `
          <tr>
            <td>${rankBadge}</td>
            <td class="fw-semibold text-truncate" style="max-width: 130px;" title="${cust.name || ''}">${cust.name || '---'}</td>
            <td><span class="badge bg-label-info">${cust.tasks_count || 0}</span></td>
            <td class="text-success small fw-semibold">${formatCurrency(cust.closed_spent)}</td>
            <td class="text-warning small">${formatCurrency(cust.unclosed_spent)}</td>
            <td class="fw-bold text-body">${formatCurrency(cust.total_spent)}</td>
          </tr>
        `;
      });
    } else {
      customerRows = `<tr><td colspan="6" class="text-center text-muted py-3">${trans('No data available for the selected period')}</td></tr>`;
    }
    $('#topCustomersTable').html(customerRows);

    // Top Drivers Table
    var driverRows = '';
    if (drivers.length > 0) {
      drivers.forEach(function (drv, index) {
        var rankBadge = getRankBadge(index + 1);
        var closedTasksCount = drv.completed_tasks || 0;
        var totalTasksCount = drv.total_tasks || 0;

        driverRows += `
          <tr>
            <td>${rankBadge}</td>
            <td class="fw-semibold text-truncate" style="max-width: 130px;" title="${drv.name || ''}">${drv.name || '---'}</td>
            <td class="text-muted small">${drv.phone || '---'}</td>
            <td>
              <span class="badge bg-label-success">${closedTasksCount}</span>
              <span class="text-muted small">/ ${totalTasksCount}</span>
            </td>
            <td class="text-success small fw-semibold">${formatCurrency(drv.closed_amount)}</td>
            <td class="fw-bold text-body">${formatCurrency(drv.total_delivered_amount)}</td>
          </tr>
        `;
      });
    } else {
      driverRows = `<tr><td colspan="6" class="text-center text-muted py-3">${trans('No data available for the selected period')}</td></tr>`;
    }
    $('#topDriversTable').html(driverRows);
  }

  function getRankBadge(rank) {
    if (rank === 1) {
      return '<span class="badge rounded-pill bg-warning text-dark"><i class="ti ti-crown me-1"></i>1</span>';
    } else if (rank === 2) {
      return '<span class="badge rounded-pill bg-secondary">2</span>';
    } else if (rank === 3) {
      return '<span class="badge rounded-pill bg-info">3</span>';
    }
    return `<span class="badge rounded-pill bg-light text-muted border">${rank}</span>`;
  }

  // Update charts with Closed vs Unclosed Series and Localization
  function updateCharts(chartsData) {
    if (!chartsData) return;

    var sarUnit = trans('SAR');
    var tasksUnit = trans('tasks');

    // Daily Tasks Chart (Closed vs Unclosed)
    if (dailyTasksChart) {
      dailyTasksChart.destroy();
    }

    var dailyTasksCtx = document.getElementById('dailyTasksChart');
    if (dailyTasksCtx && chartsData.daily_tasks) {
      dailyTasksChart = new ApexCharts(dailyTasksCtx, {
        series: [
          {
            name: trans('Closed Tasks'),
            data: chartsData.daily_tasks.closed || []
          },
          {
            name: trans('Unclosed Tasks'),
            data: chartsData.daily_tasks.unclosed || []
          }
        ],
        chart: {
          type: 'area',
          height: 280,
          toolbar: { show: false },
          stacked: true
        },
        colors: ['#28c76f', '#ff9f43'],
        dataLabels: { enabled: false },
        stroke: {
          curve: 'smooth',
          width: 2
        },
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.6,
            opacityTo: 0.1
          }
        },
        xaxis: {
          categories: chartsData.daily_tasks.labels || []
        },
        yaxis: {
          title: { text: trans('Number of Tasks') }
        },
        legend: {
          position: 'top'
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return val + ' ' + tasksUnit;
            }
          }
        }
      });
      dailyTasksChart.render();
    }

    // Daily Revenue Chart (Closed vs Unclosed)
    if (dailyRevenueChart) {
      dailyRevenueChart.destroy();
    }

    var dailyRevenueCtx = document.getElementById('dailyRevenueChart');
    if (dailyRevenueCtx && chartsData.daily_revenue) {
      dailyRevenueChart = new ApexCharts(dailyRevenueCtx, {
        series: [
          {
            name: trans('Closed Revenue'),
            data: chartsData.daily_revenue.closed || []
          },
          {
            name: trans('Unclosed Revenue (Pending)'),
            data: chartsData.daily_revenue.unclosed || []
          }
        ],
        chart: {
          type: 'line',
          height: 280,
          toolbar: { show: false }
        },
        colors: ['#00cfe8', '#ff9f43'],
        dataLabels: { enabled: false },
        stroke: {
          curve: 'smooth',
          width: [3, 2],
          dashArray: [0, 4]
        },
        xaxis: {
          categories: chartsData.daily_revenue.labels || []
        },
        yaxis: {
          title: { text: `${trans('Revenue')} (${sarUnit})` }
        },
        legend: {
          position: 'top'
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return formatCurrency(val);
            }
          }
        }
      });
      dailyRevenueChart.render();
    }

    // Status Distribution Chart
    if (statusDistributionChart) {
      statusDistributionChart.destroy();
    }

    var statusDistributionCtx = document.getElementById('statusDistributionChart');
    if (statusDistributionCtx && chartsData.status_distribution) {
      statusDistributionChart = new ApexCharts(statusDistributionCtx, {
        series: chartsData.status_distribution.map(item => item.value),
        chart: {
          type: 'donut',
          height: 280
        },
        labels: chartsData.status_distribution.map(item => trans(item.label)),
        colors: ['#28c76f', '#7367f0', '#00cfe8', '#ff9f43', '#ea5455', '#a8aaae', '#82868b'],
        legend: {
          position: 'bottom'
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return val + ' ' + tasksUnit;
            }
          }
        }
      });
      statusDistributionChart.render();
    }

    // Daily Commission Chart (Stacked Columns: Closed vs Unclosed)
    if (dailyCommissionChart) {
      dailyCommissionChart.destroy();
    }

    var dailyCommissionCtx = document.getElementById('dailyCommissionChart');
    if (dailyCommissionCtx && chartsData.daily_commission) {
      dailyCommissionChart = new ApexCharts(dailyCommissionCtx, {
        series: [
          {
            name: trans('Closed Commission'),
            data: chartsData.daily_commission.closed || []
          },
          {
            name: trans('Unclosed Commission'),
            data: chartsData.daily_commission.unclosed || []
          }
        ],
        chart: {
          type: 'bar',
          height: 280,
          toolbar: { show: false },
          stacked: true
        },
        colors: ['#28c76f', '#ffab00'],
        plotOptions: {
          bar: {
            borderRadius: 4,
            columnWidth: '45%'
          }
        },
        dataLabels: { enabled: false },
        xaxis: {
          categories: chartsData.daily_commission.labels || []
        },
        yaxis: {
          title: { text: `${trans('Commission')} (${sarUnit})` }
        },
        legend: {
          position: 'top'
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return formatCurrency(val);
            }
          }
        }
      });
      dailyCommissionChart.render();
    }
  }

  // Update details tables with Closed vs Unclosed Amounts
  function updateDetailsTables(data) {
    // 1. Status details table
    var statusTableHtml = '';
    var totalTasks = (data.tasks && data.tasks.total_tasks) ? data.tasks.total_tasks : 0;
    var statusObj = (data.tasks && data.tasks.tasks_by_status) ? data.tasks.tasks_by_status : {};

    if (Object.keys(statusObj).length > 0) {
      for (var status in statusObj) {
        var item = statusObj[status];
        var count = item.count !== undefined ? item.count : item;
        var closedAmount = item.closed_amount !== undefined ? item.closed_amount : 0;
        var unclosedAmount = item.unclosed_amount !== undefined ? item.unclosed_amount : 0;
        var totalAmount = item.total_amount !== undefined ? item.total_amount : (parseFloat(closedAmount) + parseFloat(unclosedAmount));
        var statusLabel = getStatusLabel(status);

        statusTableHtml += `
          <tr>
            <td><span class="fw-medium">${statusLabel}</span></td>
            <td><span class="badge bg-label-primary">${count}</span></td>
            <td class="text-success fw-semibold">${formatCurrency(closedAmount)}</td>
            <td class="text-warning">${formatCurrency(unclosedAmount)}</td>
            <td class="fw-bold">${formatCurrency(totalAmount)}</td>
          </tr>
        `;
      }
    } else {
      statusTableHtml = `<tr><td colspan="5" class="text-center text-muted py-3">${trans('No data available')}</td></tr>`;
    }
    $('#statusDetailsTable').html(statusTableHtml);

    // 2. Payment details table with closed vs unclosed amounts
    var paymentTableHtml = '';
    var totalRevenue = (data.financial && data.financial.total_revenue) ? data.financial.total_revenue : 0;
    var payMethods = (data.financial && data.financial.revenue_by_payment_method) ? data.financial.revenue_by_payment_method : {};

    if (Object.keys(payMethods).length > 0) {
      for (var method in payMethods) {
        var pItem = payMethods[method];
        var pClosed = pItem.closed_amount !== undefined ? pItem.closed_amount : 0;
        var pUnclosed = pItem.unclosed_amount !== undefined ? pItem.unclosed_amount : 0;
        var pTotal = pItem.total_amount !== undefined ? pItem.total_amount : (typeof pItem === 'number' ? pItem : 0);
        var payPercentage = totalRevenue > 0 ? ((pTotal / totalRevenue) * 100).toFixed(1) : 0;
        var methodLabel = getPaymentMethodLabel(method);

        paymentTableHtml += `
          <tr>
            <td><span class="fw-medium">${methodLabel}</span></td>
            <td class="text-success fw-semibold">${formatCurrency(pClosed)}</td>
            <td class="text-warning">${formatCurrency(pUnclosed)}</td>
            <td class="fw-bold">${formatCurrency(pTotal)}</td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="progress w-100" style="height: 6px;">
                  <div class="progress-bar bg-info" style="width: ${payPercentage}%"></div>
                </div>
                <small class="text-muted">${payPercentage}%</small>
              </div>
            </td>
          </tr>
        `;
      }
    } else {
      paymentTableHtml = `<tr><td colspan="5" class="text-center text-muted py-3">${trans('No data available')}</td></tr>`;
    }
    $('#paymentDetailsTable').html(paymentTableHtml);
  }

  // Helper functions
  function formatCurrency(amount) {
    return (parseFloat(amount || 0)).toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ' ' + trans('SAR');
  }

  function getStatusLabel(status) {
    var labels = {
      in_progress: trans('In Progress'),
      advertised: trans('Advertised'),
      assign: trans('Assigned'),
      accepted: trans('Accepted'),
      start: trans('Started'),
      completed: trans('Completed'),
      canceled: trans('Canceled'),
      cancelled: trans('Cancelled'),
      refunded: trans('Refunded')
    };
    return labels[status] || trans(status);
  }

  function getPaymentMethodLabel(method) {
    var labels = {
      cash: trans('Cash'),
      card: trans('Credit Card'),
      bank_transfer: trans('Bank Transfer'),
      wallet: trans('E-Wallet')
    };
    return labels[method] || trans(method);
  }
});
