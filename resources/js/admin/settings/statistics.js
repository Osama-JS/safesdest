/**
 * System Statistics
 */

'use strict';
import { showAlert } from '../../ajax';

$(function () {
  // Charts variables
  var dailyTasksChart, dailyRevenueChart, statusDistributionChart, dailyCommissionChart;

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize date pickers
  if ($('.flatpickr-date').length) {
    $('.flatpickr-date').flatpickr({
      dateFormat: 'Y-m-d',
      locale: 'ar'
    });

    // Set default date range (last 30 days)
    var today = new Date();
    var thirtyDaysAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);

    $('#dateFromFilter').val(thirtyDaysAgo.toISOString().split('T')[0]);
    $('#dateToFilter').val(today.toISOString().split('T')[0]);
  }

  // Load initial statistics
  loadStatistics();

  // Update statistics button
  $('#updateStatistics').on('click', function () {
    loadStatistics();
  });

  // Export statistics button
  $('#exportStatistics').on('click', function () {
    $('#exportModal').modal('show');
  });

  // Export form submission
  $('#exportForm').on('submit', function (e) {
    e.preventDefault();

    var formData = new FormData(this);
    formData.append('date_from', $('#dateFromFilter').val());
    formData.append('date_to', $('#dateToFilter').val());

    var submitBtn = $(this).find('button[type="submit"]');
    var originalText = submitBtn.html();

    submitBtn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>Exporting...');

    $.ajax({
      url: baseUrl + 'admin/settings/statistics/export',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 1) {
          $('#exportModal').modal('hide');
          showAlert('success', 'Statistics exported successfully');
        } else {
          showAlert('error', response.error);
        }
      },
      error: function () {
        showAlert('error', 'An error occurred while exporting statistics');
      },
      complete: function () {
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });

  // Load statistics function
  function loadStatistics() {
    var dateFrom = $('#dateFromFilter').val();
    var dateTo = $('#dateToFilter').val();

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
          updateCharts(response.data.charts);
          updateDetailsTables(response.data);
        } else {
          showAlert('error', response.error);
        }
      },
      error: function () {
        showAlert('error', 'An error occurred while loading statistics');
      }
    });
  }

  // Update statistics cards
  function updateStatisticsCards(data) {
    $('#totalTasks').text(data.tasks.total_tasks || 0);
    $('#completedTasks').text(data.tasks.completed_tasks || 0);
    $('#totalRevenue').text(formatCurrency(data.financial.total_revenue || 0));
    $('#totalCommission').text(formatCurrency(data.financial.total_commission || 0));

    $('#inProgressTasks').text(data.tasks.in_progress_tasks || 0);
    $('#cancelledTasks').text(data.tasks.cancelled_tasks || 0);
    $('#platformIncome').text(formatCurrency(data.financial.platform_income || 0));
    $('#averageTaskPrice').text(formatCurrency(data.financial.average_task_price || 0));
    $('#completionRate').text((data.tasks.completion_rate || 0) + '%');
    $('#closedTasks').text(data.tasks.closed_tasks || 0);
  }

  // Update charts
  function updateCharts(chartsData) {
    // Daily Tasks Chart
    if (dailyTasksChart) {
      dailyTasksChart.destroy();
    }

    var dailyTasksCtx = document.getElementById('dailyTasksChart');
    if (dailyTasksCtx) {
      dailyTasksChart = new ApexCharts(dailyTasksCtx, {
        series: [
          {
            name: 'Tasks',
            data: chartsData.daily_tasks.data
          }
        ],
        chart: {
          type: 'area',
          height: 300,
          toolbar: { show: false }
        },
        colors: ['#28c76f'],
        dataLabels: { enabled: false },
        stroke: {
          curve: 'smooth',
          width: 2
        },
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.3
          }
        },
        xaxis: {
          categories: chartsData.daily_tasks.labels
        },
        yaxis: {
          title: { text: 'Number of Tasks' }
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return val + ' tasks';
            }
          }
        }
      });
      dailyTasksChart.render();
    }

    // Daily Revenue Chart
    if (dailyRevenueChart) {
      dailyRevenueChart.destroy();
    }

    var dailyRevenueCtx = document.getElementById('dailyRevenueChart');
    if (dailyRevenueCtx) {
      dailyRevenueChart = new ApexCharts(dailyRevenueCtx, {
        series: [
          {
            name: 'Revenue',
            data: chartsData.daily_revenue.data
          }
        ],
        chart: {
          type: 'line',
          height: 300,
          toolbar: { show: false }
        },
        colors: ['#00cfe8'],
        dataLabels: { enabled: false },
        stroke: {
          curve: 'smooth',
          width: 3
        },
        xaxis: {
          categories: chartsData.daily_revenue.labels
        },
        yaxis: {
          title: { text: 'Revenue (SAR)' }
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
    if (statusDistributionCtx) {
      statusDistributionChart = new ApexCharts(statusDistributionCtx, {
        series: chartsData.status_distribution.map(item => item.value),
        chart: {
          type: 'donut',
          height: 300
        },
        labels: chartsData.status_distribution.map(item => item.label),
        colors: ['#28c76f', '#00cfe8', '#ffab00', '#ff5722', '#9c27b0', '#607d8b', '#795548'],
        legend: {
          position: 'bottom'
        },
        tooltip: {
          y: {
            formatter: function (val) {
              return val + ' tasks';
            }
          }
        }
      });
      statusDistributionChart.render();
    }

    // Daily Commission Chart
    if (dailyCommissionChart) {
      dailyCommissionChart.destroy();
    }

    var dailyCommissionCtx = document.getElementById('dailyCommissionChart');
    if (dailyCommissionCtx) {
      dailyCommissionChart = new ApexCharts(dailyCommissionCtx, {
        series: [
          {
            name: 'Commission',
            data: chartsData.daily_commission.data
          }
        ],
        chart: {
          type: 'column',
          height: 300,
          toolbar: { show: false }
        },
        colors: ['#ffab00'],
        dataLabels: { enabled: false },
        xaxis: {
          categories: chartsData.daily_commission.labels
        },
        yaxis: {
          title: { text: 'Commission (SAR)' }
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

  // Update details tables
  function updateDetailsTables(data) {
    // Status details table
    var statusTableHtml = '';
    var totalTasks = data.tasks.total_tasks || 1;

    for (var status in data.tasks.tasks_by_status) {
      var count = data.tasks.tasks_by_status[status];
      var percentage = ((count / totalTasks) * 100).toFixed(1);
      var statusLabel = getStatusLabel(status);

      statusTableHtml += `
        <tr>
          <td>${statusLabel}</td>
          <td>${count}</td>
          <td>${percentage}%</td>
        </tr>
      `;
    }
    $('#statusDetailsTable').html(statusTableHtml);

    // Payment details table
    var paymentTableHtml = '';
    var totalRevenue = data.financial.total_revenue || 1;

    for (var method in data.financial.revenue_by_payment_method) {
      var amount = data.financial.revenue_by_payment_method[method];
      var percentage = ((amount / totalRevenue) * 100).toFixed(1);
      var methodLabel = getPaymentMethodLabel(method);

      paymentTableHtml += `
        <tr>
          <td>${methodLabel}</td>
          <td>${formatCurrency(amount)}</td>
          <td>${percentage}%</td>
        </tr>
      `;
    }
    $('#paymentDetailsTable').html(paymentTableHtml);
  }

  // Helper functions
  function formatCurrency(amount) {
    return parseFloat(amount || 0).toFixed(2) + ' SAR';
  }

  function getStatusLabel(status) {
    var labels = {
      in_progress: 'In Progress',
      advertised: 'Advertised',
      assign: 'Assigned',
      accepted: 'Accepted',
      start: 'Started',
      completed: 'Completed',
      canceled: 'Canceled'
    };
    return labels[status] || status;
  }

  function getPaymentMethodLabel(method) {
    var labels = {
      cash: 'Cash',
      card: 'Card',
      bank_transfer: 'Bank Transfer',
      wallet: 'E-Wallet'
    };
    return labels[method] || method;
  }
});
