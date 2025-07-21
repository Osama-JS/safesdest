/**
 * Page Geneal Settings
 */
import { deleteRecord, showAlert, showFormModal } from '../ajax';

$(function () {
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  $('.update-setting-select, .update-setting-input').on('change', function () {
    var settingKey = $(this).data('key');
    var settingValue = $(this).val();

    if (!settingKey) return;

    $.ajax({
      url: baseUrl + 'admin/settings/set-template',
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        key: settingKey,
        value: settingValue
      },
      success: function (response) {
        if (response.success) {
          showAlert('success', response.message, 5000, true);
        } else {
          showAlert('error', response.message, 5000, true);
        }
      },
      error: function (xhr) {
        showAlert('error', 'An error occurred:', xhr.responseText, 5000, true);
      }
    });
  });

  // Quick backup function
  window.createQuickBackup = function () {
    if (confirm('هل تريد إنشاء نسخة احتياطية سريعة؟')) {
      $.ajax({
        url: baseUrl + 'admin/settings/backup/create',
        type: 'POST',
        data: {
          _token: $('meta[name="csrf-token"]').attr('content'),
          backup_type: 'full',
          description: 'نسخة احتياطية سريعة من صفحة الإعدادات'
        },
        beforeSend: function () {
          showAlert('info', 'جاري إنشاء النسخة الاحتياطية...', 0, false);
        },
        success: function (response) {
          if (response.status === 1) {
            showAlert('success', response.success, 5000, true);
          } else {
            showAlert('error', response.error, 5000, true);
          }
        },
        error: function (xhr) {
          showAlert('error', 'حدث خطأ أثناء إنشاء النسخة الاحتياطية', 5000, true);
        }
      });
    }
  };

  // Quick statistics function
  window.showQuickStats = function () {
    var quickStatsSection = $('#quickStatsSection');

    if (quickStatsSection.is(':visible')) {
      quickStatsSection.slideUp();
      return;
    }

    // Show loading
    $('#quickTotalTasks, #quickCompletedTasks, #quickTotalRevenue, #quickTotalCommission').text('...');
    quickStatsSection.slideDown();

    $.ajax({
      url: baseUrl + 'admin/settings/statistics/data',
      type: 'GET',
      success: function (response) {
        if (response.status === 1) {
          var data = response.data;
          $('#quickTotalTasks').text(data.tasks.total_tasks || 0);
          $('#quickCompletedTasks').text(data.tasks.completed_tasks || 0);
          $('#quickTotalRevenue').text(formatCurrency(data.financial.total_revenue || 0));
          $('#quickTotalCommission').text(formatCurrency(data.financial.total_commission || 0));
        } else {
          showAlert('error', response.error, 5000, true);
        }
      },
      error: function () {
        showAlert('error', 'حدث خطأ أثناء تحميل الإحصائيات', 5000, true);
        $('#quickTotalTasks, #quickCompletedTasks, #quickTotalRevenue, #quickTotalCommission').text('-');
      }
    });
  };

  // Helper function to format currency
  function formatCurrency(amount) {
    return parseFloat(amount || 0).toFixed(2) + ' ر.س';
  }
});
