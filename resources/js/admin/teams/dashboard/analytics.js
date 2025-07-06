/**
 * Team Analytics Dashboard
 */

'use strict';
import { initDashboard, showAlert, setButtonLoading } from './common.js';

$(function () {
  // Initialize common dashboard functionality
  initDashboard();
  // Initialize date range picker
  if ($('#analytics-date-range').length) {
    $('#analytics-date-range').flatpickr({
      mode: 'range',
      dateFormat: 'Y-m-d',
      defaultDate: [
        new Date(Date.now() - 30 * 24 * 60 * 60 * 1000), // 30 days ago
        new Date() // today
      ]
    });
  }

  // Initialize all charts
  initTasksOverviewChart();
  initTaskStatusChart();
  initRevenueChart();

  // Update analytics button
  $('#update-analytics').on('click', function () {
    updateAllCharts();
  });

  // Export analytics
  $('#export-analytics').on('click', function () {
    exportAnalyticsReport();
  });

  // Metric type change handler
  $('#metric-type').on('change', function () {
    updateChartsBasedOnMetric($(this).val());
  });
});

/**
 * Initialize Tasks Overview Chart
 */
function initTasksOverviewChart() {
  const chartElement = document.querySelector('#tasksOverviewChart');

  if (!chartElement) return;

  // Generate sample data for the last 30 days
  const dates = [];
  const completedTasks = [];
  const pendingTasks = [];
  const canceledTasks = [];

  for (let i = 29; i >= 0; i--) {
    const date = new Date();
    date.setDate(date.getDate() - i);
    dates.push(date.toISOString().split('T')[0]);

    // Generate random data for demo
    completedTasks.push(Math.floor(Math.random() * 10) + 5);
    pendingTasks.push(Math.floor(Math.random() * 5) + 2);
    canceledTasks.push(Math.floor(Math.random() * 3));
  }

  const chartConfig = {
    chart: {
      type: 'area',
      height: 350,
      fontFamily: 'Inter, sans-serif',
      toolbar: {
        show: true,
        tools: {
          download: true,
          selection: true,
          zoom: true,
          zoomin: true,
          zoomout: true,
          pan: true,
          reset: true
        }
      }
    },
    series: [
      {
        name: 'Completed',
        data: completedTasks,
        color: '#28c76f'
      },
      {
        name: 'Pending',
        data: pendingTasks,
        color: '#ff9f43'
      },
      {
        name: 'Canceled',
        data: canceledTasks,
        color: '#ea5455'
      }
    ],
    xaxis: {
      categories: dates,
      type: 'datetime',
      labels: {
        format: 'MMM dd'
      }
    },
    yaxis: {
      title: {
        text: 'Number of Tasks'
      }
    },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.7,
        opacityTo: 0.3
      }
    },
    stroke: {
      curve: 'smooth',
      width: 2
    },
    legend: {
      position: 'top',
      horizontalAlign: 'right'
    },
    tooltip: {
      x: {
        format: 'dd MMM yyyy'
      }
    },
    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: 300
          }
        }
      }
    ]
  };

  const chart = new ApexCharts(chartElement, chartConfig);
  chart.render();

  // Store chart instance for updates
  window.tasksOverviewChart = chart;
}

/**
 * Initialize Task Status Chart
 */
function initTaskStatusChart() {
  const chartElement = document.querySelector('#taskStatusChart');

  if (!chartElement || typeof analyticsData === 'undefined') return;

  const statusData = analyticsData.task_status_distribution || {};
  const series = Object.values(statusData);
  const labels = Object.keys(statusData).map(
    status => status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ')
  );

  if (series.length === 0) {
    chartElement.innerHTML = `
      <div class="text-center py-4">
        <i class="ti ti-chart-pie-off text-muted" style="font-size: 3rem;"></i>
        <p class="text-muted mt-2">No task status data available</p>
      </div>
    `;
    return;
  }

  const chartConfig = {
    chart: {
      type: 'donut',
      height: 300,
      fontFamily: 'Inter, sans-serif'
    },
    series: series,
    labels: labels,
    colors: ['#28c76f', '#ff9f43', '#00cfe8', '#7367f0', '#ea5455'],
    plotOptions: {
      pie: {
        donut: {
          size: '60%',
          labels: {
            show: true,
            name: {
              show: true,
              fontSize: '14px',
              fontWeight: 600
            },
            value: {
              show: true,
              fontSize: '18px',
              fontWeight: 700
            },
            total: {
              show: true,
              label: 'Total',
              fontSize: '12px',
              formatter: function (w) {
                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
              }
            }
          }
        }
      }
    },
    legend: {
      show: true,
      position: 'bottom'
    },
    dataLabels: {
      enabled: false
    },
    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: 250
          }
        }
      }
    ]
  };

  const chart = new ApexCharts(chartElement, chartConfig);
  chart.render();

  window.taskStatusChart = chart;
}

/**
 * Initialize Revenue Chart
 */
function initRevenueChart() {
  const chartElement = document.querySelector('#revenueChart');

  if (!chartElement) return;

  // Generate sample monthly revenue data
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const currentMonth = new Date().getMonth();
  const revenueData = [];

  for (let i = 0; i <= currentMonth; i++) {
    revenueData.push(Math.floor(Math.random() * 50000) + 20000);
  }

  const chartConfig = {
    chart: {
      type: 'bar',
      height: 300,
      fontFamily: 'Inter, sans-serif',
      toolbar: {
        show: false
      }
    },
    series: [
      {
        name: 'Revenue',
        data: revenueData,
        color: '#7367f0'
      }
    ],
    xaxis: {
      categories: months.slice(0, currentMonth + 1)
    },
    yaxis: {
      title: {
        text: 'Revenue (SAR)'
      },
      labels: {
        formatter: function (value) {
          return value.toLocaleString();
        }
      }
    },
    plotOptions: {
      bar: {
        borderRadius: 4,
        columnWidth: '60%'
      }
    },
    dataLabels: {
      enabled: false
    },
    tooltip: {
      y: {
        formatter: function (value) {
          return value.toLocaleString() + ' SAR';
        }
      }
    },
    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: 250
          }
        }
      }
    ]
  };

  const chart = new ApexCharts(chartElement, chartConfig);
  chart.render();

  window.revenueChart = chart;
}

/**
 * Update all charts
 */
function updateAllCharts() {
  const updateBtn = $('#update-analytics');

  setButtonLoading(updateBtn, true, 'Updating...');

  // Simulate API call
  setTimeout(() => {
    // Update charts with new data
    if (window.tasksOverviewChart) {
      // Generate new random data
      const newData = generateRandomTaskData();
      window.tasksOverviewChart.updateSeries(newData);
    }

    if (window.taskStatusChart) {
      // Update status chart
      const newStatusData = generateRandomStatusData();
      window.taskStatusChart.updateSeries(newStatusData.series);
    }

    if (window.revenueChart) {
      // Update revenue chart
      const newRevenueData = generateRandomRevenueData();
      window.revenueChart.updateSeries([{ data: newRevenueData }]);
    }

    setButtonLoading(updateBtn, false);

    // Show success message
    showAlert('success', 'Analytics updated successfully');
  }, 2000);
}

/**
 * Update charts based on selected metric
 */
function updateChartsBasedOnMetric(metric) {
  // This would typically make different API calls based on the metric type
  console.log('Updating charts for metric:', metric);

  // For demo purposes, just update the charts
  updateAllCharts();
}

/**
 * Export analytics report
 */
function exportAnalyticsReport() {
  const exportBtn = $('#export-analytics');

  setButtonLoading(exportBtn, true, 'Exporting...');

  // Simulate export process
  setTimeout(() => {
    setButtonLoading(exportBtn, false);
    showAlert('success', 'Analytics report exported successfully');
  }, 2000);
}

/**
 * Generate random task data for demo
 */
function generateRandomTaskData() {
  const completedTasks = [];
  const pendingTasks = [];
  const canceledTasks = [];

  for (let i = 0; i < 30; i++) {
    completedTasks.push(Math.floor(Math.random() * 10) + 5);
    pendingTasks.push(Math.floor(Math.random() * 5) + 2);
    canceledTasks.push(Math.floor(Math.random() * 3));
  }

  return [
    { name: 'Completed', data: completedTasks },
    { name: 'Pending', data: pendingTasks },
    { name: 'Canceled', data: canceledTasks }
  ];
}

/**
 * Generate random status data for demo
 */
function generateRandomStatusData() {
  const statuses = ['completed', 'pending', 'in_progress', 'canceled'];
  const series = statuses.map(() => Math.floor(Math.random() * 20) + 5);

  return { series, labels: statuses };
}

/**
 * Generate random revenue data for demo
 */
function generateRandomRevenueData() {
  const currentMonth = new Date().getMonth();
  const data = [];

  for (let i = 0; i <= currentMonth; i++) {
    data.push(Math.floor(Math.random() * 50000) + 20000);
  }

  return data;
}

/**
 * Show alert message
 */
function showAlert(type, message) {
  // This would typically use the existing alert system
  console.log(`${type.toUpperCase()}: ${message}`);

  // For demo, create a simple toast
  const toast = $(`
    <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'primary'} border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  `);

  $('body').append(toast);
  const bsToast = new bootstrap.Toast(toast[0]);
  bsToast.show();

  // Remove toast after it's hidden
  toast.on('hidden.bs.toast', function () {
    $(this).remove();
  });
}
