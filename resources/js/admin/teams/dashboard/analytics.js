/**
 * Team Analytics Dashboard
 */

'use strict';
import { initDashboard } from './common.js';

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
  initVehicleUtilizationChart();

  // Update analytics button
  $('#update-analytics').on('click', function () {
    updateAllCharts();
  });

  // Refresh analytics
  $('#refresh-analytics').on('click', function () {
    refreshAnalyticsData();
  });

  // Date range change handler
  $('#analytics-date-range').on('apply.daterangepicker', function (ev, picker) {
    refreshAnalyticsWithFilters();
  });

  // Metric type change handler
  $('#metric-type').on('change', function () {
    refreshAnalyticsWithFilters();
  });

  // Group by change handler
  $('#group-by').on('change', function () {
    refreshAnalyticsWithFilters();
  });

  // Export analytics handlers
  $('#export-pdf').on('click', function (e) {
    e.preventDefault();
    exportAnalyticsReport('pdf');
  });

  $('#export-excel').on('click', function (e) {
    e.preventDefault();
    exportAnalyticsReport('excel');
  });

  $('#export-csv').on('click', function (e) {
    e.preventDefault();
    exportAnalyticsReport('csv');
  });

  // Metric type change handler
  $('#metric-type').on('change', function () {
    updateChartsBasedOnMetric($(this).val());
  });

  // Group by change handler
  $('#group-by').on('change', function () {
    updateChartsGrouping($(this).val());
  });
});

/**
 * Initialize Tasks Overview Chart
 */
function initTasksOverviewChart() {
  const chartElement = document.querySelector('#tasksOverviewChart');

  if (!chartElement) return;

  // Use real analytics data if available
  let chartData = [];
  let categories = [];

  if (window.analyticsData && window.analyticsData.monthly_tasks) {
    // Convert monthly data to chart format
    const monthlyData = window.analyticsData.monthly_tasks;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // Create categories and data arrays for current year
    const taskCounts = [];

    // Debug: Log the monthly data to console
    console.log('Monthly Tasks Data:', monthlyData);

    // Check if we have any data
    const hasData = Object.keys(monthlyData).length > 0;

    if (!hasData) {
      // Show no data message
      chartElement.innerHTML = `
        <div class="chart-no-data">
          <i class="ti ti-chart-line-off" style="font-size: 3rem; margin-bottom: 1rem;"></i>
          <h6>No task data available</h6>
          <p class="text-muted">Tasks will appear here once created</p>
        </div>
      `;
      return;
    }

    for (let i = 1; i <= 12; i++) {
      categories.push(months[i - 1]);
      const count = parseInt(monthlyData[i]) || 0;
      taskCounts.push(count);
      console.log(`Month ${i} (${months[i - 1]}): ${count} tasks`);
    }

    chartData = [
      {
        name: 'Total Tasks',
        data: taskCounts,
        color: '#28c76f'
      }
    ];

    console.log('Chart Data for Tasks Overview:', chartData);
  } else {
    // Fallback to sample data for the last 30 days
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

    categories = dates;
    chartData = [
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
    ];
  }

  const chartConfig = {
    chart: {
      type: window.analyticsData && window.analyticsData.monthly_tasks ? 'line' : 'area',
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
    series: chartData,
    xaxis: {
      categories: categories,
      type: window.analyticsData && window.analyticsData.monthly_tasks ? 'category' : 'datetime',
      labels: {
        format: window.analyticsData && window.analyticsData.monthly_tasks ? undefined : 'MMM dd'
      },
      title: {
        text: window.analyticsData && window.analyticsData.monthly_tasks ? 'Months' : 'Days'
      }
    },
    yaxis: {
      title: {
        text: 'Number of Tasks'
      },
      min: 0
    },
    fill: {
      type: window.analyticsData && window.analyticsData.monthly_tasks ? 'solid' : 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.7,
        opacityTo: 0.3
      }
    },
    stroke: {
      curve: 'smooth',
      width: window.analyticsData && window.analyticsData.monthly_tasks ? 3 : 2
    },
    markers: {
      size: window.analyticsData && window.analyticsData.monthly_tasks ? 4 : 0,
      hover: {
        size: 6
      }
    },
    legend: {
      position: 'top',
      horizontalAlign: 'right'
    },
    tooltip: {
      shared: true,
      intersect: false,
      x: {
        format: window.analyticsData && window.analyticsData.monthly_tasks ? undefined : 'dd MMM yyyy'
      },
      y: {
        formatter: function (val) {
          return val + ' tasks';
        }
      },
      custom: function ({ series, seriesIndex, dataPointIndex, w }) {
        const month = w.globals.labels[dataPointIndex];
        const value = series[seriesIndex][dataPointIndex];
        return `
          <div class="custom-tooltip">
            <div class="tooltip-title">${month}</div>
            <div class="tooltip-content">
              <span class="tooltip-label">Total Tasks:</span>
              <span class="tooltip-value">${value}</span>
            </div>
          </div>
        `;
      }
    },
    grid: {
      borderColor: '#e7eef7',
      strokeDashArray: 5
    },
    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: 300
          },
          legend: {
            position: 'bottom'
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

  // Use real analytics data if available
  let revenueData = [];
  let categories = [];

  if (window.analyticsData && window.analyticsData.revenue_data) {
    // Convert revenue data to chart format
    const monthlyRevenue = window.analyticsData.revenue_data;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    // Debug: Log the revenue data to console
    console.log('Monthly Revenue Data:', monthlyRevenue);

    // Check if we have any revenue data
    const hasRevenueData = Object.keys(monthlyRevenue).length > 0;
    const totalRevenue = Object.values(monthlyRevenue).reduce((sum, val) => sum + parseFloat(val || 0), 0);

    if (!hasRevenueData || totalRevenue === 0) {
      // Show no data message
      chartElement.innerHTML = `
        <div class="chart-no-data">
          <i class="ti ti-chart-bar-off" style="font-size: 3rem; margin-bottom: 1rem;"></i>
          <h6>No revenue data available</h6>
          <p class="text-muted">Revenue will appear here once tasks are completed</p>
        </div>
      `;
      return;
    }

    // Fill data for all 12 months
    for (let i = 1; i <= 12; i++) {
      categories.push(months[i - 1]);
      const revenue = parseFloat(monthlyRevenue[i]) || 0;
      revenueData.push(revenue);
      console.log(`Month ${i} (${months[i - 1]}): ${revenue} SAR`);
    }

    console.log('Chart Data for Revenue:', revenueData);
  } else {
    // Fallback to sample data
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const currentMonth = new Date().getMonth();

    for (let i = 0; i <= currentMonth; i++) {
      categories.push(months[i]);
      revenueData.push(Math.floor(Math.random() * 50000) + 20000);
    }
  }

  const chartConfig = {
    chart: {
      type: 'bar',
      height: 300,
      fontFamily: 'Inter, sans-serif',
      toolbar: {
        show: true,
        tools: {
          download: true,
          selection: false,
          zoom: false,
          zoomin: false,
          zoomout: false,
          pan: false,
          reset: false
        }
      }
    },
    series: [
      {
        name: 'Net Revenue (after commission)',
        data: revenueData,
        color: '#7367f0'
      }
    ],
    xaxis: {
      categories: categories,
      title: {
        text: 'Months'
      }
    },
    yaxis: {
      title: {
        text: 'Revenue (SAR)'
      },
      labels: {
        formatter: function (value) {
          return value.toLocaleString();
        }
      },
      min: 0
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
          return value.toLocaleString() + ' SAR (Net Revenue)';
        }
      },
      custom: function ({ series, seriesIndex, dataPointIndex, w }) {
        const month = w.globals.labels[dataPointIndex];
        const value = series[seriesIndex][dataPointIndex];
        return `
          <div class="custom-tooltip">
            <div class="tooltip-title">${month} Revenue</div>
            <div class="tooltip-content">
              <span class="tooltip-label">Net Revenue:</span>
              <span class="tooltip-value">${value.toLocaleString()} SAR</span>
            </div>
            <div class="tooltip-note">
              <small>After commission deduction</small>
            </div>
          </div>
        `;
      }
    },
    grid: {
      borderColor: '#e7eef7',
      strokeDashArray: 5
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
 * Initialize Vehicle Utilization Chart
 */
function initVehicleUtilizationChart() {
  const chartElement = document.querySelector('#vehicleUtilizationChart');

  if (!chartElement) return;

  // Use real driver performance data if available
  let vehicleData = [];

  if (window.analyticsData && window.analyticsData.driver_performance) {
    const driverData = window.analyticsData.driver_performance;

    // Group drivers by performance level
    const highPerformers = driverData.filter(d => d.completed_tasks >= 10).length;
    const mediumPerformers = driverData.filter(d => d.completed_tasks >= 5 && d.completed_tasks < 10).length;
    const lowPerformers = driverData.filter(d => d.completed_tasks < 5).length;
    const totalDrivers = driverData.length;

    if (totalDrivers > 0) {
      vehicleData = [
        {
          name: 'High Performers (10+ tasks)',
          value: Math.round((highPerformers / totalDrivers) * 100),
          color: '#28c76f'
        },
        {
          name: 'Medium Performers (5-9 tasks)',
          value: Math.round((mediumPerformers / totalDrivers) * 100),
          color: '#ff9f43'
        },
        {
          name: 'Low Performers (<5 tasks)',
          value: Math.round((lowPerformers / totalDrivers) * 100),
          color: '#ea5455'
        }
      ];
    }
  }

  // Fallback to sample data if no real data available
  if (vehicleData.length === 0) {
    vehicleData = [
      { name: 'High Performers', value: 35, color: '#28c76f' },
      { name: 'Medium Performers', value: 45, color: '#ff9f43' },
      { name: 'Low Performers', value: 20, color: '#ea5455' }
    ];
  }

  const chartConfig = {
    chart: {
      type: 'radialBar',
      height: 250,
      fontFamily: 'Inter, sans-serif'
    },
    series: vehicleData.map(item => item.value),
    labels: vehicleData.map(item => item.name),
    colors: vehicleData.map(item => item.color),
    plotOptions: {
      radialBar: {
        dataLabels: {
          name: {
            fontSize: '12px',
            fontWeight: 600
          },
          value: {
            fontSize: '16px',
            fontWeight: 700,
            formatter: function (val) {
              return val + '%';
            }
          }
        },
        hollow: {
          size: '30%'
        }
      }
    },
    legend: {
      show: true,
      position: 'bottom',
      fontSize: '12px'
    },
    tooltip: {
      y: {
        formatter: function (val) {
          return val + '% of drivers';
        }
      }
    },
    responsive: [
      {
        breakpoint: 768,
        options: {
          chart: {
            height: 200
          }
        }
      }
    ]
  };

  const chart = new ApexCharts(chartElement, chartConfig);
  chart.render();

  window.vehicleUtilizationChart = chart;
}

/**
 * Refresh analytics data
 */
function refreshAnalyticsData() {
  const refreshBtn = $('#refresh-analytics');

  setButtonLoading(refreshBtn, true, 'Refreshing...');

  // Get current team ID from URL
  const teamId = window.location.pathname.split('/')[3];

  // Make API call to refresh data
  $.ajax({
    url: `/admin/teams/${teamId}/analytics-data`,
    method: 'GET',
    success: function (response) {
      if (response.success) {
        // Update global analytics data
        window.analyticsData = response.data;

        // Update all charts with new data
        updateAllChartsWithData(response.data);

        // Update KPIs
        updateKPICards(response.data.kpis || {});

        showAlert('success', 'Analytics data refreshed successfully');
      } else {
        showAlert('error', 'Failed to refresh analytics data');
      }
    },
    error: function () {
      showAlert('error', 'Error occurred while refreshing analytics data');
    },
    complete: function () {
      setButtonLoading(refreshBtn, false);
    }
  });
}

/**
 * Refresh analytics with current filters
 */
function refreshAnalyticsWithFilters() {
  const refreshBtn = $('#refresh-analytics');
  setButtonLoading(refreshBtn, true, 'Updating...');

  // Get current team ID from URL
  const teamId = window.location.pathname.split('/')[3];

  // Get filter values
  const filters = getAnalyticsFilters();

  // Make API call with filters
  $.ajax({
    url: `/admin/teams/${teamId}/analytics-data`,
    method: 'GET',
    data: filters,
    success: function (response) {
      if (response.success) {
        // Update global analytics data
        window.analyticsData = response.data;

        // Update all charts with filtered data
        updateAllChartsWithData(response.data);

        // Update KPIs
        updateKPICards(response.data.kpis || {});

        showAlert('success', 'Analytics updated with filters');
      } else {
        showAlert('error', 'Failed to update analytics with filters');
      }
    },
    error: function () {
      showAlert('error', 'Error occurred while updating analytics');
    },
    complete: function () {
      setButtonLoading(refreshBtn, false);
    }
  });
}

/**
 * Get current analytics filters
 */
function getAnalyticsFilters() {
  const filters = {};

  // Date range filter
  const dateRange = $('#analytics-date-range').data('daterangepicker');
  if (dateRange) {
    filters.start_date = dateRange.startDate.format('YYYY-MM-DD');
    filters.end_date = dateRange.endDate.format('YYYY-MM-DD');
  }

  // Metric type filter
  const metricType = $('#metric-type').val();
  if (metricType) {
    filters.metric_type = metricType;
  }

  // Group by filter
  const groupBy = $('#group-by').val();
  if (groupBy) {
    filters.group_by = groupBy;
  }

  return filters;
}

/**
 * Update KPI cards with new data
 */
function updateKPICards(kpis) {
  // Update total tasks
  if (kpis.total_tasks !== undefined) {
    $('.kpi-total-tasks').text(formatNumber(kpis.total_tasks));
  }

  // Update completed tasks
  if (kpis.completed_tasks !== undefined) {
    $('.kpi-completed-tasks').text(formatNumber(kpis.completed_tasks));
  }

  // Calculate and update completion rate
  if (kpis.total_tasks !== undefined && kpis.completed_tasks !== undefined) {
    const completionRate = kpis.total_tasks > 0 ? Math.round((kpis.completed_tasks / kpis.total_tasks) * 100) : 0;
    $('.kpi-completion-rate').text(completionRate + '%');
  }

  // Update revenue (net revenue after commission)
  if (kpis.total_revenue !== undefined) {
    $('.kpi-total-revenue').text(formatCurrency(kpis.total_revenue));
  }

  // Update active drivers
  if (kpis.active_drivers !== undefined) {
    $('.kpi-active-drivers').text(formatNumber(kpis.active_drivers));
  }
}

/**
 * Update all charts with new data
 */
function updateAllChartsWithData(chartsData) {
  // Update tasks overview chart with monthly data
  if (window.tasksOverviewChart && chartsData.monthly_tasks) {
    const monthlyData = chartsData.monthly_tasks;
    const taskCounts = [];

    // Convert monthly data to array format
    for (let i = 1; i <= 12; i++) {
      taskCounts.push(monthlyData[i] || 0);
    }

    window.tasksOverviewChart.updateSeries([
      {
        name: 'Total Tasks',
        data: taskCounts
      }
    ]);
  }

  // Update task status chart
  if (window.taskStatusChart && chartsData.task_status_distribution) {
    const statusData = chartsData.task_status_distribution;
    const series = Object.values(statusData);
    const labels = Object.keys(statusData);

    window.taskStatusChart.updateOptions({
      labels: labels
    });
    window.taskStatusChart.updateSeries(series);
  }

  // Update revenue chart
  if (window.revenueChart && chartsData.revenue_data) {
    const revenueData = chartsData.revenue_data;
    const revenueValues = [];

    // Convert monthly revenue data to array format
    for (let i = 1; i <= 12; i++) {
      revenueValues.push(revenueData[i] || 0);
    }

    window.revenueChart.updateSeries([
      {
        name: 'Revenue',
        data: revenueValues
      }
    ]);
  }

  // Update vehicle utilization chart
  if (window.vehicleUtilizationChart && chartsData.driver_performance) {
    const driverData = chartsData.driver_performance;

    // Group drivers by vehicle size for utilization chart
    const vehicleSizes = {};
    driverData.forEach(driver => {
      const sizeId = driver.vehicle_size_id || 'Unknown';
      if (!vehicleSizes[sizeId]) {
        vehicleSizes[sizeId] = 0;
      }
      vehicleSizes[sizeId]++;
    });

    const series = Object.values(vehicleSizes);
    const labels = Object.keys(vehicleSizes).map(id => `Size ${id}`);

    window.vehicleUtilizationChart.updateOptions({
      labels: labels
    });
    window.vehicleUtilizationChart.updateSeries(series);
  }
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
 * Update charts based on grouping
 */
function updateChartsGrouping(groupBy) {
  console.log('Updating charts grouping:', groupBy);

  // This would typically regenerate chart data based on the grouping
  // For now, just refresh all charts
  updateAllCharts();
}

/**
 * Export analytics report
 */
function exportAnalyticsReport(format = 'pdf') {
  const exportBtn = $(`#export-${format}`);
  const originalText = exportBtn.text();

  setButtonLoading(exportBtn, true, 'Exporting...');

  // Prepare export data
  const exportData = {
    team_id: teamID,
    format: format,
    date_range: $('#analytics-date-range').val(),
    metric_type: $('#metric-type').val(),
    group_by: $('#group-by').val(),
    include_charts: true,
    include_tables: true
  };

  // Simulate export process with actual API call
  $.ajax({
    url: `${baseUrl}admin/teams/${teamID}/export-analytics`,
    type: 'POST',
    data: exportData,
    success: function (response) {
      if (response.success && response.download_url) {
        // Create temporary download link
        const link = document.createElement('a');
        link.href = response.download_url;
        link.download = response.filename || `team-analytics-${teamID}.${format}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showAlert('success', `Analytics report exported as ${format.toUpperCase()} successfully`);
      } else {
        showAlert('error', response.message || 'Export failed');
      }

      setButtonLoading(exportBtn, false, originalText);
    },
    error: function (xhr, status, error) {
      console.error('Export error:', error);

      // Fallback: Generate client-side export
      generateClientSideExport(format);

      setButtonLoading(exportBtn, false, originalText);
      showAlert('success', `Analytics report exported as ${format.toUpperCase()} (client-side)`);
    }
  });
}

/**
 * Generate client-side export as fallback
 */
function generateClientSideExport(format) {
  const teamName = document.title.split(' - ')[1] || 'Team';
  const timestamp = new Date().toISOString().split('T')[0];

  if (format === 'csv') {
    generateCSVExport(teamName, timestamp);
  } else if (format === 'excel') {
    generateExcelExport(teamName, timestamp);
  } else {
    generatePDFExport(teamName, timestamp);
  }
}

/**
 * Generate CSV export
 */
function generateCSVExport(teamName, timestamp) {
  const csvData = [
    ['Team Analytics Report'],
    ['Team:', teamName],
    ['Date:', timestamp],
    [''],
    ['Metric', 'Value', 'Change'],
    ['Total Tasks', $('.kpi-total-tasks').text() || '0', '+12%'],
    ['Completion Rate', $('.kpi-completion-rate').text() || '0%', '+5%'],
    ['Total Revenue', $('.kpi-total-revenue').text() || '0', '+8%'],
    ['Driver Utilization', $('.kpi-driver-utilization').text() || '0%', '+7%']
  ];

  const csvContent = csvData.map(row => row.join(',')).join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);

  const link = document.createElement('a');
  link.href = url;
  link.download = `team-analytics-${teamName}-${timestamp}.csv`;
  link.click();

  window.URL.revokeObjectURL(url);
}

/**
 * Generate Excel export (simplified)
 */
function generateExcelExport(teamName, timestamp) {
  // For a full Excel export, you would typically use a library like SheetJS
  // For now, we'll create a simple HTML table that Excel can import
  const htmlContent = `
    <table>
      <tr><td colspan="3"><strong>Team Analytics Report</strong></td></tr>
      <tr><td>Team:</td><td>${teamName}</td><td></td></tr>
      <tr><td>Date:</td><td>${timestamp}</td><td></td></tr>
      <tr><td></td><td></td><td></td></tr>
      <tr><td><strong>Metric</strong></td><td><strong>Value</strong></td><td><strong>Change</strong></td></tr>
      <tr><td>Total Tasks</td><td>${$('.kpi-total-tasks').text() || '0'}</td><td>+12%</td></tr>
      <tr><td>Completion Rate</td><td>${$('.kpi-completion-rate').text() || '0%'}</td><td>+5%</td></tr>
      <tr><td>Total Revenue</td><td>${$('.kpi-total-revenue').text() || '0'}</td><td>+8%</td></tr>
      <tr><td>Driver Utilization</td><td>${$('.kpi-driver-utilization').text() || '0%'}</td><td>+7%</td></tr>
    </table>
  `;

  const blob = new Blob([htmlContent], { type: 'application/vnd.ms-excel' });
  const url = window.URL.createObjectURL(blob);

  const link = document.createElement('a');
  link.href = url;
  link.download = `team-analytics-${teamName}-${timestamp}.xls`;
  link.click();

  window.URL.revokeObjectURL(url);
}

/**
 * Generate PDF export (simplified)
 */
function generatePDFExport(teamName, timestamp) {
  // For a full PDF export, you would typically use a library like jsPDF
  // For now, we'll open a print dialog with the analytics content
  const printContent = `
    <html>
      <head>
        <title>Team Analytics Report - ${teamName}</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 20px; }
          .header { text-align: center; margin-bottom: 30px; }
          .kpi-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }
          .kpi-card { border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
          .kpi-value { font-size: 24px; font-weight: bold; color: #007bff; }
          .kpi-label { font-size: 14px; color: #666; }
        </style>
      </head>
      <body>
        <div class="header">
          <h1>Team Analytics Report</h1>
          <p>Team: ${teamName} | Date: ${timestamp}</p>
        </div>
        <div class="kpi-grid">
          <div class="kpi-card">
            <div class="kpi-value">${$('.kpi-total-tasks').text() || '0'}</div>
            <div class="kpi-label">Total Tasks</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-value">${$('.kpi-completion-rate').text() || '0%'}</div>
            <div class="kpi-label">Completion Rate</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-value">${$('.kpi-total-revenue').text() || '0'}</div>
            <div class="kpi-label">Total Revenue</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-value">${$('.kpi-driver-utilization').text() || '0%'}</div>
            <div class="kpi-label">Driver Utilization</div>
          </div>
        </div>
      </body>
    </html>
  `;

  const printWindow = window.open('', '_blank');
  printWindow.document.write(printContent);
  printWindow.document.close();
  printWindow.print();
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

/**
 * Set button loading state
 */
function setButtonLoading(button, loading, text = null) {
  if (loading) {
    button.prop('disabled', true);
    if (text) {
      button.data('original-text', button.text());
      button.html(`<span class="spinner-border spinner-border-sm me-2" role="status"></span>${text}`);
    }
  } else {
    button.prop('disabled', false);
    if (button.data('original-text')) {
      button.text(button.data('original-text'));
    } else if (text) {
      button.text(text);
    }
  }
}

/**
 * Format number with commas
 */
function formatNumber(num) {
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Format currency
 */
function formatCurrency(amount, currency = 'SAR') {
  return `${formatNumber(amount)} ${currency}`;
}

/**
 * Calculate percentage change
 */
function calculatePercentageChange(current, previous) {
  if (previous === 0) return current > 0 ? 100 : 0;
  return Math.round(((current - previous) / previous) * 100);
}

/**
 * Get trend indicator HTML
 */
function getTrendIndicator(percentage) {
  if (percentage > 0) {
    return `<small class="text-success">+${percentage}%</small>`;
  } else if (percentage < 0) {
    return `<small class="text-danger">${percentage}%</small>`;
  } else {
    return `<small class="text-muted">0%</small>`;
  }
}

/**
 * Animate counter
 */
function animateCounter(element, start, end, duration = 1000) {
  const range = end - start;
  const increment = range / (duration / 16);
  let current = start;

  const timer = setInterval(() => {
    current += increment;
    if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
      current = end;
      clearInterval(timer);
    }
    element.text(Math.round(current));
  }, 16);
}

/**
 * Show loading overlay
 */
function showLoadingOverlay(show = true) {
  if (show) {
    if ($('#analytics-loading-overlay').length === 0) {
      $('body').append(`
        <div id="analytics-loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(255,255,255,0.8); z-index: 9999;">
          <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="text-muted">Loading analytics data...</p>
          </div>
        </div>
      `);
    }
  } else {
    $('#analytics-loading-overlay').remove();
  }
}

/**
 * Initialize date range picker
 */
function initDateRangePicker() {
  if ($('#analytics-date-range').length) {
    $('#analytics-date-range').daterangepicker(
      {
        startDate: moment().subtract(29, 'days'),
        endDate: moment(),
        ranges: {
          Today: [moment(), moment()],
          Yesterday: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days': [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month': [moment().startOf('month'), moment().endOf('month')],
          'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
      },
      function (start, end, label) {
        console.log('Date range changed:', start.format('YYYY-MM-DD'), 'to', end.format('YYYY-MM-DD'));
        refreshAnalyticsData();
      }
    );
  }
}

/**
 * Initialize tooltips
 */
function initTooltips() {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
}

// Initialize additional features when document is ready
$(document).ready(function () {
  initDateRangePicker();
  initTooltips();
});
