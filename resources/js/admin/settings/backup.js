/**
 * Backup Management & Cloud Automation
 */

'use strict';
import { deleteRecord, showAlert } from '../../ajax';

$(function () {
  var dt_data_table = $('.datatables-backups'),
    baseUrl = window.baseUrl || '/';

  // Ensure trailing slash on baseUrl
  if (!baseUrl.endsWith('/')) {
    baseUrl += '/';
  }

  // Ajax Setup CSRF
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Load statistics and initial config
  loadStatistics();
  loadCloudConfig();

  // Encryption password toggle
  $('#is_encrypted').on('change', function () {
    if ($(this).is(':checked')) {
      $('#passwordInputContainer').slideDown(200);
    } else {
      $('#passwordInputContainer').slideUp(200);
    }
  });

  // Backups DataTable
  var dt_data = null;
  if (dt_data_table.length) {
    dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: baseUrl + 'admin/settings/backup/data',
        dataSrc: function (json) {
          loadStatistics();
          return json;
        },
        error: function (xhr, error, thrown) {
          console.error('DataTable AJAX error:', error, thrown);
          showAlert('error', 'تعذر تحميل بيانات النسخ الاحتياطية. يرجى تحديث الصفحة.');
        }
      },
      columns: [
        { data: 'name' },
        { data: 'type' },
        { data: 'description' },
        { data: 'size' },
        { data: 'status' },
        { data: 'created_at' },
        { data: null }
      ],
      columnDefs: [
        {
          targets: 0,
          render: function (data, type, full) {
            var lockIcon = full.is_encrypted
              ? '<i class="ti ti-lock text-warning ms-1" data-bs-toggle="tooltip" title="مشفرة (AES-256)"></i>'
              : '';
            return `<span class="fw-bold text-heading">${full.name}</span> ${lockIcon}`;
          }
        },
        {
          targets: 1,
          render: function (data, type, full) {
            var typeMap = {
              full: { text: 'كامل (قاعدة وبيانات)', class: 'bg-label-primary' },
              database_only: { text: 'قاعدة بيانات', class: 'bg-label-info' },
              files_only: { text: 'ملفات فقط', class: 'bg-label-warning' }
            };
            var t = typeMap[full.type] || { text: full.type, class: 'bg-label-secondary' };
            return `<span class="badge ${t.class}">${t.text}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full) {
            return full.description ? `<span class="text-truncate d-inline-block" style="max-width: 200px;">${full.description}</span>` : '<span class="text-muted">-</span>';
          }
        },
        {
          targets: 3,
          render: function (data, type, full) {
            return `<span class="badge bg-label-secondary">${full.size_human || formatFileSize(full.size)}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full) {
            var statusMap = {
              completed: { text: 'مكتملة', class: 'bg-label-success' },
              processing: { text: 'قيد المعالجة', class: 'bg-label-warning' },
              failed: { text: 'فشلت', class: 'bg-label-danger' }
            };
            var s = statusMap[full.status] || { text: full.status, class: 'bg-label-secondary' };
            return `<span class="badge ${s.class}">${s.text}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full) {
            return `<span class="text-nowrap">${formatDate(full.created_at)}</span>`;
          }
        },
        {
          targets: -1,
          title: 'الإجراءات',
          searchable: false,
          orderable: false,
          className: 'text-center',
          render: function (data, type, full) {
            return `
              <div class="d-inline-flex gap-1 align-items-center">
                <a href="${baseUrl}admin/settings/backup/direct-download/${full.name}" 
                   class="btn btn-sm btn-icon btn-text-primary rounded-pill waves-effect" 
                   data-bs-toggle="tooltip" title="تنزيل مباشر للجهاز">
                  <i class="ti ti-download ti-md"></i>
                </a>
                <button type="button" class="btn btn-sm btn-icon btn-text-info rounded-pill waves-effect btn-dispatch-telegram" 
                        data-name="${full.name}" data-bs-toggle="tooltip" title="إرسال إلى تليجرام">
                  <i class="ti ti-brand-telegram ti-md"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon btn-text-danger rounded-pill waves-effect btn-dispatch-email" 
                        data-name="${full.name}" data-bs-toggle="tooltip" title="إرسال إلى البريد الإلكتروني">
                  <i class="ti ti-mail ti-md"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon btn-text-warning rounded-pill waves-effect restore-backup" 
                        data-name="${full.name}" data-bs-toggle="tooltip" title="استعادة النظام من النسخة">
                  <i class="ti ti-restore ti-md"></i>
                </button>
                <button type="button" class="btn btn-sm btn-icon btn-text-danger rounded-pill waves-effect delete-backup" 
                        data-name="${full.name}" data-bs-toggle="tooltip" title="حذف النسخة">
                  <i class="ti ti-trash ti-md"></i>
                </button>
              </div>
            `;
          }
        }
      ],
      order: [[5, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 25,
      lengthMenu: [10, 25, 50, 100]
    });
  }

  // Refresh
  $('#refreshBackups').on('click', function () {
    if (dt_data) dt_data.ajax.reload();
    loadStatistics();
  });

  // Create Backup Form
  $('#createBackupForm').on('submit', function (e) {
    e.preventDefault();

    var formData = new FormData(this);
    var submitBtn = $(this).find('button[type="submit"]');
    var originalText = submitBtn.html();

    submitBtn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>جاري إنشاء النسخة...');

    $.ajax({
      url: baseUrl + 'admin/settings/backup/create',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 1) {
          $('#createBackupModal').modal('hide');
          if (dt_data) dt_data.ajax.reload();
          loadStatistics();
          showAlert('success', response.success || 'تم إنشاء النسخة الاحتياطية بنجاح');
          $('#createBackupForm')[0].reset();
          $('#passwordInputContainer').hide();
        } else {
          showAlert('error', response.error || 'فشل في إنشاء النسخة الاحتياطية');
        }
      },
      error: function (xhr) {
        var errorMsg = xhr.responseJSON?.error || xhr.responseJSON?.message || 'حدث خطأ أثناء إنشاء النسخة الاحتياطية';
        showAlert('error', errorMsg);
      },
      complete: function () {
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });

  // Instant Dispatch to Telegram
  $(document).on('click', '.btn-dispatch-telegram', function () {
    var backupName = $(this).data('name');
    var btn = $(this);
    var originalHtml = btn.html();

    if (!confirm('هل تريد إرسال النسخة الاحتياطية (' + backupName + ') إلى بوت تليجرام الآن؟')) {
      return;
    }

    btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin"></i>');
    showAlert('info', 'جاري رفع وإرسال النسخة إلى تليجرام...');

    $.post(baseUrl + 'admin/settings/backup/dispatch/' + backupName, { destination: 'telegram' })
      .done(function (res) {
        if (res.status === 1) {
          showAlert('success', res.message);
        } else {
          showAlert('error', res.error || 'تعذر الإرسال إلى تليجرام');
        }
      })
      .fail(function (xhr) {
        var err = xhr.responseJSON?.error || xhr.responseJSON?.message || 'فشل الاتصال بخدمة تليجرام';
        showAlert('error', err);
      })
      .always(function () {
        btn.prop('disabled', false).html(originalHtml);
      });
  });

  // Instant Dispatch to Email
  $(document).on('click', '.btn-dispatch-email', function () {
    var backupName = $(this).data('name');
    var btn = $(this);
    var originalHtml = btn.html();

    if (!confirm('هل تريد إرسال النسخة الاحتياطية (' + backupName + ') إلى البريد الإلكتروني الآن؟')) {
      return;
    }

    btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin"></i>');
    showAlert('info', 'جاري إرسال النسخة الاحتياطية إلى البريد الإلكتروني...');

    $.post(baseUrl + 'admin/settings/backup/dispatch/' + backupName, { destination: 'email' })
      .done(function (res) {
        if (res.status === 1) {
          showAlert('success', res.message);
        } else {
          showAlert('error', res.error || 'تعذر الإرسال إلى البريد الإلكتروني');
        }
      })
      .fail(function (xhr) {
        var err = xhr.responseJSON?.error || xhr.responseJSON?.message || 'فشل إرسال البريد الإلكتروني';
        showAlert('error', err);
      })
      .always(function () {
        btn.prop('disabled', false).html(originalHtml);
      });
  });

  // Restore Modal Trigger
  $(document).on('click', '.restore-backup', function () {
    var backupName = $(this).data('name');
    $('#restoreBackupName').val(backupName);
    $('#restoreBackupModal').modal('show');
  });

  // Restore Form Submit
  $('#restoreBackupForm').on('submit', function (e) {
    e.preventDefault();

    if (!$('#confirmRestore').is(':checked')) {
      showAlert('warning', 'يرجى تأكيد الموافقة على عملية الاستبدال');
      return;
    }

    var formData = new FormData(this);
    var submitBtn = $(this).find('button[type="submit"]');
    var originalText = submitBtn.html();

    submitBtn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>جاري الاستعادة...');

    $.ajax({
      url: baseUrl + 'admin/settings/backup/restore',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 1) {
          $('#restoreBackupModal').modal('hide');
          showAlert('success', response.success || 'تمت الاستعادة بنجاح');
          setTimeout(() => {
            window.location.reload();
          }, 2000);
        } else {
          showAlert('error', response.error || 'فشلت الاستعادة');
        }
      },
      error: function (xhr) {
        var errorMsg = xhr.responseJSON?.error || 'حدث خطأ أثناء استعادة النسخة الاحتياطية';
        showAlert('error', errorMsg);
      },
      complete: function () {
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });

  // Delete Backup
  $(document).on('click', '.delete-backup', function () {
    var name = $(this).data('name');
    var url = baseUrl + 'admin/settings/backup/delete/' + name;
    deleteRecord('النسخة الاحتياطية: ' + name, url);
  });

  document.addEventListener('deletedSuccess', function () {
    if (dt_data) dt_data.ajax.reload();
    loadStatistics();
  });

  // Upload and Restore Form Submit
  $('#uploadRestoreForm').on('submit', function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    var submitBtn = $(this).find('button[type="submit"]');
    submitBtn.prop('disabled', true).text('جاري فحص واستعادة النسخة...');

    $.ajax({
      url: baseUrl + 'admin/settings/backup/upload-restore',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 1) {
          showAlert('success', response.success);
          $('#uploadRestoreModal').modal('hide');
          if (dt_data) dt_data.ajax.reload();
          setTimeout(() => {
            window.location.reload();
          }, 2000);
        } else {
          showAlert('error', response.error || 'فشلت الاستعادة من الملف المرفوع');
        }
      },
      error: function (xhr) {
        showAlert('error', xhr.responseJSON?.error || 'حدث خطأ أثناء معالجة الملف المرفوع');
      },
      complete: function () {
        submitBtn.prop('disabled', false).text('استعادة النسخة');
      }
    });
  });

  // Cloud & Auto-Backup Settings Modal
  $('#cloudSettingsModal').on('show.bs.modal', function () {
    loadCloudConfig();
  });

  function loadCloudConfig() {
    $.get(baseUrl + 'admin/settings/backup/config').done(function (res) {
      if (res.status === 1 && res.config) {
        var c = res.config;
        $('#cfg_backup_auto_enabled').prop('checked', !!c.backup_auto_enabled);
        $('#cfg_backup_schedule_frequency').val(c.backup_schedule_frequency || 'daily');
        $('#cfg_backup_schedule_time').val(c.backup_schedule_time || '02:00');
        $('#cfg_backup_max_retention_count').val(c.backup_max_retention_count || 15);
        $('#cfg_backup_auto_destinations').val(c.backup_auto_destinations || 'local,telegram');
        $('#cfg_backup_auto_encrypt').prop('checked', !!c.backup_auto_encrypt);

        $('#cfg_telegram_backup_enabled').prop('checked', !!c.telegram_backup_enabled);
        $('#cfg_backup_telegram_bot_token').val(c.backup_telegram_bot_token || '');
        $('#cfg_backup_telegram_chat_id').val(c.backup_telegram_chat_id || '');

        $('#cfg_email_backup_enabled').prop('checked', !!c.email_backup_enabled);
        $('#cfg_backup_email_recipient').val(c.backup_email_recipient || '');

        // Update auto backup header card
        if (c.backup_auto_enabled) {
          $('#autoBackupStatus').html('<span class="text-dark fw-bold">مُفعل (' + (c.backup_schedule_time || '02:00') + ')</span>');
        } else {
          $('#autoBackupStatus').html('<span class="text-muted">مُعطل</span>');
        }
      }
    });
  }

  // Save Cloud Settings Form
  $('#cloudBackupSettingsForm').on('submit', function (e) {
    e.preventDefault();
    var formData = $(this).serialize();
    var submitBtn = $(this).find('button[type="submit"]');
    var originalText = submitBtn.html();

    submitBtn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>جاري الحفظ...');

    $.post(baseUrl + 'admin/settings/backup/config', formData)
      .done(function (res) {
        if (res.status === 1) {
          showAlert('success', res.message || 'تم حفظ الإعدادات بنجاح');
          $('#cloudSettingsModal').modal('hide');
          loadStatistics();
          loadCloudConfig();
        } else {
          showAlert('error', res.error || 'فشل حفظ الإعدادات');
        }
      })
      .fail(function (xhr) {
        showAlert('error', xhr.responseJSON?.error || 'حدث خطأ أثناء حفظ الإعدادات');
      })
      .always(function () {
        submitBtn.prop('disabled', false).html(originalText);
      });
  });

  // Test Telegram Button
  $('#btnTestTelegram').on('click', function () {
    var btn = $(this);
    var orig = btn.html();
    var token = $('#cfg_backup_telegram_bot_token').val();
    var chatId = $('#cfg_backup_telegram_chat_id').val();

    if (!token || !chatId) {
      showAlert('warning', 'يرجى إدخال Bot Token و Chat ID أولاً');
      return;
    }

    btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>جاري اختبار الاتصال...');

    $.post(baseUrl + 'admin/settings/backup/test-destination', {
      type: 'telegram',
      telegram_backup_bot_token: token,
      telegram_backup_chat_id: chatId
    })
      .done(function (res) {
        if (res.status === 1) {
          showAlert('success', res.message);
        } else {
          showAlert('error', res.error || 'فشل اختبار اتصال تليجرام');
        }
      })
      .fail(function (xhr) {
        showAlert('error', xhr.responseJSON?.error || 'فشل اختبار تليجرام');
      })
      .always(function () {
        btn.prop('disabled', false).html(orig);
      });
  });

  // Test Email Button
  $('#btnTestEmail').on('click', function () {
    var btn = $(this);
    var orig = btn.html();
    var email = $('#cfg_backup_email_recipient').val();

    if (!email) {
      showAlert('warning', 'يرجى إدخال البريد الإلكتروني للمستلم أولاً');
      return;
    }

    btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>جاري اختبار إرسال البريد...');

    $.post(baseUrl + 'admin/settings/backup/test-destination', {
      type: 'email',
      backup_email_recipient: email
    })
      .done(function (res) {
        if (res.status === 1) {
          showAlert('success', res.message);
        } else {
          showAlert('error', res.error || 'فشل اختبار البريد');
        }
      })
      .fail(function (xhr) {
        showAlert('error', xhr.responseJSON?.error || 'فشل اختبار البريد');
      })
      .always(function () {
        btn.prop('disabled', false).html(orig);
      });
  });

  // Statistics Modal
  $('#statisticsModal').on('show.bs.modal', function () {
    loadDetailedStatistics();
  });

  function loadStatistics() {
    $.get(baseUrl + 'admin/settings/backup/statistics')
      .done(function (response) {
        if (response.status === 1 && response.data) {
          var d = response.data;
          $('#totalBackups').text(d.total_backups || 0);
          $('#totalSize').text(d.total_size_human || formatFileSize(d.total_size || 0));
          $('#latestBackup').text(d.latest_backup ? formatDate(d.latest_backup) : '-');
        }
      });
  }

  function loadDetailedStatistics() {
    $('#statisticsContent').html(
      '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>'
    );

    $.get(baseUrl + 'admin/settings/backup/statistics')
      .done(function (response) {
        if (response.status === 1 && response.data) {
          var data = response.data;
          var html = `
            <div class="row g-3">
              <div class="col-md-4">
                <div class="card bg-white border shadow-none text-center p-3">
                  <h3 class="card-title text-dark mb-1">${data.total_backups || 0}</h3>
                  <p class="card-text text-muted mb-0">إجمالي النسخ</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card bg-white border shadow-none text-center p-3">
                  <h3 class="card-title text-dark mb-1">${data.total_size_human || formatFileSize(data.total_size || 0)}</h3>
                  <p class="card-text text-muted mb-0">الحجم الكلي</p>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card bg-white border shadow-none text-center p-3">
                  <h3 class="card-title text-dark mb-1">${data.free_disk_human || '-'}</h3>
                  <p class="card-text text-muted mb-0">المساحة الشاغرة بالقرص</p>
                </div>
              </div>
            </div>
          `;

          if (data.backup_types) {
            html += `
              <div class="row mt-4">
                <div class="col-12">
                  <h6 class="fw-bold mb-2">توزيع أنواع النسخ:</h6>
                  <div class="d-flex justify-content-around border rounded p-3 bg-light">
                    <span><strong>قاعدة البيانات:</strong> ${data.backup_types.database_only || 0}</span>
                    <span><strong>شامل (بيانات وملفات):</strong> ${data.backup_types.full || 0}</span>
                    <span><strong>ملفات فقط:</strong> ${data.backup_types.files_only || 0}</span>
                  </div>
                </div>
              </div>
            `;
          }

          $('#statisticsContent').html(html);
        } else {
          $('#statisticsContent').html('<div class="alert alert-danger">حدث خطأ أثناء تحميل الإحصائيات</div>');
        }
      })
      .fail(function () {
        $('#statisticsContent').html('<div class="alert alert-danger">فشل في جلب الإحصائيات</div>');
      });
  }

  function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    var k = 1024;
    var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  function formatDate(dateString) {
    if (!dateString) return '-';
    var date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return (
      date.toLocaleDateString('ar-EG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }) +
      ' ' +
      date.toLocaleTimeString('ar-EG', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
      })
    );
  }
});
