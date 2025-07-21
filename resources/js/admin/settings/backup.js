/**
 * Backup Management
 */

'use strict';
import { deleteRecord, showAlert, showFormModal, generateFields, handleErrors, showBlockAlert } from '../../ajax';

$(function () {
  // Variable declaration for table
  var dt_data_table = $('.datatables-backups'),
    backupView = baseUrl + 'admin/settings/backup/';

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Load statistics
  loadStatistics();

  // Backups datatable
  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: false,
      ajax: {
        url: baseUrl + 'admin/settings/backup/data',
        dataSrc: function (json) {
          // Load statistics separately since this endpoint only returns backup data
          loadStatistics();
          return json;
        },
        error: function (xhr, error, thrown) {
          console.error('DataTable AJAX error:', error, thrown);
          showAlert('error', 'Failed to load backup data. Please refresh the page.');
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
          render: function (data, type, full, meta) {
            return `<span class="fw-medium">${full.name}</span>`;
          }
        },
        {
          targets: 1,
          render: function (data, type, full, meta) {
            var typeMap = {
              full: { text: 'Full Backup', class: 'bg-label-primary' },
              database_only: { text: 'Database', class: 'bg-label-info' },
              files_only: { text: 'Files Only', class: 'bg-label-warning' }
            };

            var type = typeMap[full.type] || { text: full.type, class: 'bg-label-secondary' };
            return `<span class="badge ${type.class}">${type.text}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return full.description || '<span class="text-muted">-</span>';
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return formatFileSize(full.size);
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            var statusMap = {
              completed: { text: 'Completed', class: 'bg-label-success' },
              processing: { text: 'Processing', class: 'bg-label-warning' },
              failed: { text: 'Failed', class: 'bg-label-danger' }
            };

            var status = statusMap[full.status] || { text: full.status, class: 'bg-label-secondary' };
            return `<span class="badge ${status.class}">${status.text}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return `<span class="text-nowrap">${formatDate(full.created_at)}</span>`;
          }
        },
        {
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            var actions = `
              <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-icon btn-text-info rounded-pill waves-effect download-backup" data-name="${full.name}" data-bs-toggle="tooltip" title="Download">
                  <i class="ti ti-download ti-md"></i>
                </button>
                <button class="btn btn-sm btn-icon btn-text-warning rounded-pill waves-effect restore-backup" data-name="${full.name}" data-bs-toggle="tooltip" title="Restore">
                  <i class="ti ti-restore ti-md"></i>
                </button>
                <button class="btn btn-sm btn-icon btn-text-danger rounded-pill waves-effect delete-backup" data-name="${full.name}" data-bs-toggle="tooltip" title="Delete">
                  <i class="ti ti-trash ti-md"></i>
                </button>
              </div>
            `;
            return actions;
          }
        }
      ],
      order: [[5, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 25,
      lengthMenu: [10, 25, 50, 75, 100]
    });
  }

  // Refresh backups
  $('#refreshBackups').on('click', function () {
    dt_data.ajax.reload();
    loadStatistics();
  });

  // Create backup form submission
  $('#createBackupForm').on('submit', function (e) {
    e.preventDefault();

    var formData = new FormData(this);
    var submitBtn = $(this).find('button[type="submit"]');
    var originalText = submitBtn.html();

    submitBtn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>Creating...');

    $.ajax({
      url: baseUrl + 'admin/settings/backup/create',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 1) {
          $('#createBackupModal').modal('hide');
          dt_data.ajax.reload();
          loadStatistics();
          showAlert('success', response.success);
          $('#createBackupForm')[0].reset();
        } else {
          showAlert('error', response.error);
        }
      },
      error: function (xhr) {
        var errorMsg = 'An error occurred while creating backup';
        if (xhr.responseJSON && xhr.responseJSON.error) {
          errorMsg = xhr.responseJSON.error;
        }
        showAlert('error', errorMsg);
      },
      complete: function () {
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });

  // Download backup

  $(document).on('click', '.download-backup', function () {
    var backupName = $(this).data('name');
    showAlert('info', 'The backup is being sent to your email....');

    $.get(baseUrl + 'admin/settings/backup/download/' + backupName)
      .done(function (response) {
        if (response.status === 1) {
          showAlert('success', response.message);
        } else {
          showAlert('error', response.error);
        }
      })
      .fail(function (xhr) {
        let error = xhr.responseJSON?.message || 'فشل في الاتصال بالخادم.';

        showAlert('error', error);
      });
  });

  // Restore backup
  $(document).on('click', '.restore-backup', function () {
    var backupName = $(this).data('name');
    $('#restoreBackupName').val(backupName);
    $('#restoreBackupModal').modal('show');
  });

  // Restore backup form submission
  $('#restoreBackupForm').on('submit', function (e) {
    e.preventDefault();

    var formData = new FormData(this);
    var submitBtn = $(this).find('button[type="submit"]');
    var originalText = submitBtn.html();

    // Show confirmation
    if (!confirm('Are you sure you want to restore this backup? This process will replace current data.')) {
      return;
    }

    submitBtn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i>Restoring...');

    $.ajax({
      url: baseUrl + 'admin/settings/backup/restore',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 1) {
          $('#restoreBackupModal').modal('hide');
          showAlert('success', response.success);
          // Reload page after successful restore
          setTimeout(() => {
            window.location.reload();
          }, 2000);
        } else {
          showAlert('error', response.error);
        }
      },
      error: function (xhr) {
        var errorMsg = 'An error occurred while restoring backup';
        if (xhr.responseJSON && xhr.responseJSON.error) {
          errorMsg = xhr.responseJSON.error;
        }
        showAlert('error', errorMsg);
      },
      complete: function () {
        submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });

  // Delete backup
  $(document).on('click', '.delete-backup', function () {
    let url = baseUrl + 'admin/settings/backup/delete/' + $(this).data('name');
    deleteRecord('Backup : ' + $(this).data('name'), url);
  });

  document.addEventListener('deletedSuccess', function (event) {
    dt_data.ajax.reload();
    loadStatistics();
  });

  // Show statistics modal
  $('#statisticsModal').on('show.bs.modal', function () {
    loadDetailedStatistics();
  });

  // Helper functions
  function loadStatistics() {
    $.get(baseUrl + 'admin/settings/backup/statistics')
      .done(function (response) {
        if (response.status === 1) {
          updateStatisticsCards(response.data);
        }
      })
      .fail(function () {
        console.log('Failed to load statistics');
      });
  }

  function updateStatisticsCards(data) {
    $('#totalBackups').text(data.total_backups || 0);
    $('#totalSize').text(formatFileSize(data.total_size || 0));
    $('#latestBackup').text(data.latest_backup ? formatDate(data.latest_backup) : '-');

    // Update backup health based on latest backup age
    var health = 'Good';
    if (data.latest_backup) {
      var daysSinceLastBackup = Math.floor((new Date() - new Date(data.latest_backup)) / (1000 * 60 * 60 * 24));
      if (daysSinceLastBackup > 7) {
        health = 'Needs Backup';
      } else if (daysSinceLastBackup > 3) {
        health = 'Average';
      }
    } else {
      health = 'No Backups';
    }
    $('#backupHealth').text(health);
  }

  function loadDetailedStatistics() {
    $('#statisticsContent').html(
      '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>'
    );

    $.get(baseUrl + 'admin/settings/backup/statistics')
      .done(function (response) {
        if (response.status === 1) {
          var data = response.data;
          var html = `
            <div class="row g-3">
              <div class="col-md-4">
                <div class="card ">
                  <div class="card-body text-center">
                    <h3 class="card-title">${data.total_backups || 0}</h3>
                    <p class="card-text">Total Backups</p>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card ">
                  <div class="card-body text-center">
                    <h3 class="card-title">${formatFileSize(data.total_size || 0)}</h3>
                    <p class="card-text">Total Size</p>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card ">
                  <div class="card-body text-center">
                    <h3 class="card-title">${data.latest_backup ? formatDate(data.latest_backup) : '-'}</h3>
                    <p class="card-text">Latest Backup</p>
                  </div>
                </div>
              </div>
            </div>
          `;

          if (data.backup_types) {
            html +=
              '<div class="row mt-4"><div class="col-12"><h6>Backup Types Distribution</h6><div class="d-flex justify-content-between">';
            for (var type in data.backup_types) {
              var typeLabel = type === 'full' ? 'Full' : type === 'database_only' ? 'Database' : 'Files';
              html += `<span>${typeLabel}: ${data.backup_types[type]}</span>`;
            }
            html += '</div></div></div>';
          }

          $('#statisticsContent').html(html);
        } else {
          $('#statisticsContent').html(
            '<div class="alert alert-danger">An error occurred while loading statistics</div>'
          );
        }
      })
      .fail(function () {
        $('#statisticsContent').html(
          '<div class="alert alert-danger">An error occurred while loading statistics</div>'
        );
      });
  }

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    var k = 1024;
    var sizes = ['B', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  function formatDate(dateString) {
    const date = new Date(dateString);
    return (
      date.toLocaleDateString('ar-EG', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        calendar: 'gregory' // تأكيد استخدام التقويم الميلادي
      }) +
      ' ' +
      date.toLocaleTimeString('ar-EG', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      })
    );
  }

  // Upload and restore backup
  $('#uploadRestoreForm').on('submit', function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    var submitBtn = $(this).find('button[type="submit"]');
    submitBtn.prop('disabled', true).text('جاري الاستعادة...');
    $.ajax({
      url: baseUrl + 'admin/settings/backup/upload-restore',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
      success: function (response) {
        if (response.status === 1) {
          showAlert('success', response.success);
          $('#uploadRestoreModal').modal('hide');
          dt_data.ajax.reload();
        } else {
          showAlert('error', response.error);
        }
      },
      error: function (xhr) {
        showAlert('error', 'حدث خطأ أثناء استعادة النسخة الاحتياطية');
      },
      complete: function () {
        submitBtn.prop('disabled', false).text('استعادة النسخة');
      }
    });
  });
});
