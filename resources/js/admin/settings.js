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

    updateSetting(settingKey, settingValue);
  });

  $('.update-setting-checkbox').on('change', function () {
    var settingKey = $(this).data('key');
    var settingValue = $(this).is(':checked') ? '1' : '0';

    updateSetting(settingKey, settingValue);
  });

  function updateSetting(key, value) {
    if (!key) return;

    $.ajax({
      url: baseUrl + 'admin/settings/set-template',
      type: 'POST',
      data: {
        _token: $('meta[name="csrf-token"]').attr('content'),
        key: key,
        value: value
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
  }

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

  // HyperPay Webhook Token helpers
  $('#btn_generate_hp_token').on('click', function () {
    var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var token = '';
    for (var i = 0; i < 32; i++) {
      token += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    $('#hyperpay_webhook_token').val(token).trigger('change');
  });

  $('#hyperpay_webhook_token').on('input change', function () {
    var token = $(this).val().trim();
    var baseUrl = $('#hyperpay_webhook_url_display').data('base-url') || '';
    if (token) {
      $('#hyperpay_webhook_url_display').val(baseUrl + '?token=' + encodeURIComponent(token));
    } else {
      $('#hyperpay_webhook_url_display').val(baseUrl);
    }
  });

  $('#btn_copy_hp_url').on('click', function () {
    var copyText = $('#hyperpay_webhook_url_display').val();
    if (!copyText) return;

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(copyText).then(function () {
        showAlert('success', 'تم نسخ رابط الـ Webhook إلى الحافظة بنجاح', 3000, true);
      }).catch(function () {
        fallbackCopyText(copyText);
      });
    } else {
      fallbackCopyText(copyText);
    }
  });

  function fallbackCopyText(text) {
    var tempInput = $('<input>');
    $('body').append(tempInput);
    tempInput.val(text).select();
    document.execCommand('copy');
    tempInput.remove();
    showAlert('success', 'تم نسخ رابط الـ Webhook إلى الحافظة بنجاح', 3000, true);
  }
});
