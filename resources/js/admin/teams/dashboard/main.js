/**
 * Team Dashboard Main Page
 */

'use strict';
import { initDashboard, showAlert, formatNumber, formatCurrency } from './common.js';

$(function () {
  // Initialize common dashboard functionality
  initDashboard();

  // Initialize task status chart
  initTaskStatusChart();

  // Initialize any other dashboard components
  initDashboardComponents();
});

/**
 * Initialize Task Status Chart
 */
function initTaskStatusChart() {
  const chartElement = document.querySelector('#taskStatusChart');

  if (!chartElement || typeof teamStats === 'undefined') {
    return;
  }

  // Prepare chart data
  const totalTasks = teamStats.tasks_count || 0;
  const pendingTasks = teamStats.pending_tasks || 0;
  const completedTasks = teamStats.completed_tasks || 0;
  const inProgressTasks = totalTasks - pendingTasks - completedTasks;

  if (totalTasks === 0) {
    chartElement.innerHTML = `
      <div class="text-center py-4">
        <i class="ti ti-chart-pie-off text-muted" style="font-size: 3rem;"></i>
        <p class="text-muted mt-2">No tasks data available</p>
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
    series: [pendingTasks, inProgressTasks, completedTasks],
    labels: ['Pending', 'In Progress', 'Completed'],
    colors: ['#ff9f43', '#00cfe8', '#28c76f'],
    plotOptions: {
      pie: {
        donut: {
          size: '60%',
          labels: {
            show: true,
            name: {
              show: true,
              fontSize: '16px',
              fontWeight: 600,
              offsetY: -10
            },
            value: {
              show: true,
              fontSize: '24px',
              fontWeight: 700,
              offsetY: 10,
              formatter: function (val) {
                return val;
              }
            },
            total: {
              show: true,
              label: 'Total Tasks',
              fontSize: '14px',
              fontWeight: 400,
              formatter: function () {
                return totalTasks;
              }
            }
          }
        }
      }
    },
    legend: {
      show: true,
      position: 'bottom',
      horizontalAlign: 'center',
      fontSize: '14px',
      fontWeight: 500,
      markers: {
        width: 8,
        height: 8,
        radius: 4
      },
      itemMargin: {
        horizontal: 15,
        vertical: 5
      }
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
}

/**
 * Initialize other dashboard components
 */
function initDashboardComponents() {
  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Add click handlers for quick action buttons
  $('.btn[href*="dashboard"]').on('click', function (e) {
    // Add loading state
    const $btn = $(this);
    const originalText = $btn.html();

    $btn.html('<i class="ti ti-loader ti-spin me-2"></i>Loading...');

    // Remove loading state after navigation (fallback)
    setTimeout(() => {
      $btn.html(originalText);
    }, 2000);
  });

  // Auto-refresh stats every 5 minutes
  setInterval(refreshDashboardStats, 5 * 60 * 1000);
}

/**
 * Refresh dashboard statistics
 */
function refreshDashboardStats() {
  if (typeof teamId === 'undefined') {
    return;
  }

  // This would typically make an AJAX call to get updated stats
  // For now, we'll just add a visual indicator that data is being refreshed
  const statsCards = document.querySelectorAll('.card .avatar-initial');

  statsCards.forEach(card => {
    card.style.opacity = '0.6';
    setTimeout(() => {
      card.style.opacity = '1';
    }, 1000);
  });
}

/**
 * Format numbers for display
 */
function formatNumber(num) {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M';
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K';
  }
  return num.toString();
}

/**
 * Format currency for display
 */
function formatCurrency(amount, currency = 'SAR') {
  return (
    new Intl.NumberFormat('en-US', {
      style: 'decimal',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount) +
    ' ' +
    currency
  );
}
