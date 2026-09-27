/**
 * Admin Notification Settings JS
 */
'use strict';

$(function () {
  const modalTargetRoles = $('#modal_target_roles');
  const modalTargetUsers = $('#modal_target_user_ids');
  const editModal = $('#editNotificationModal');

  // Initialize Select2 in modal
  if (modalTargetRoles.length && $.fn.select2) {
    modalTargetRoles.select2({
      dropdownParent: editModal,
      width: '100%'
    });
  }

  if (modalTargetUsers.length && $.fn.select2) {
    modalTargetUsers.select2({
      dropdownParent: editModal,
      width: '100%'
    });
  }

  // Quick channel toggle
  $(document).on('change', '.channel-toggle', function () {
    const toggle = $(this);
    const id = toggle.data('id');
    const channel = toggle.data('channel');
    const status = toggle.is(':checked') ? 1 : 0;
    const baseUrl = window.notificationSettingsConfig ? window.notificationSettingsConfig.toggleUrl : '/admin/settings/notifications';
    const csrfToken = window.notificationSettingsConfig ? window.notificationSettingsConfig.csrfToken : $('meta[name="csrf-token"]').attr('content');

    $.ajax({
      url: `${baseUrl}/${id}/toggle`,
      type: 'POST',
      data: {
        _token: csrfToken,
        channel: channel,
        status: status
      },
      success: function (res) {
        // Optional subtle toast or sound if desired
      },
      error: function () {
        toggle.prop('checked', !status);
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: 'تعذر تحديث القناة، يرجى المحاولة لاحقاً.'
          });
        } else {
          alert('تعذر تحديث القناة، يرجى المحاولة لاحقاً.');
        }
      }
    });
  });

  // Open Edit Modal
  $(document).on('click', '.btn-edit-setting', function () {
    const btn = $(this);
    const id = btn.data('id');
    const name = btn.data('name');
    const roles = btn.data('roles') || [];
    const users = btn.data('users') || [];
    const emails = btn.data('emails') || '';
    const priority = btn.data('priority') || 'normal';

    $('#edit_setting_id').val(id);
    $('#modalSettingTitle').text('تخصيص: ' + name);

    if (modalTargetRoles.length) {
      modalTargetRoles.val(roles).trigger('change');
    }
    if (modalTargetUsers.length) {
      modalTargetUsers.val(users).trigger('change');
    }

    $('#modal_custom_emails').val(emails);
    $('#modal_priority').val(priority);

    editModal.modal('show');
  });

  // Submit Edit Form
  $('#editSettingForm').on('submit', function (e) {
    e.preventDefault();
    const id = $('#edit_setting_id').val();
    const btn = $('#btnSaveSetting');
    const baseUrl = window.notificationSettingsConfig ? window.notificationSettingsConfig.toggleUrl : '/admin/settings/notifications';
    const csrfToken = window.notificationSettingsConfig ? window.notificationSettingsConfig.csrfToken : $('meta[name="csrf-token"]').attr('content');

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> جاري الحفظ...');

    $.ajax({
      url: `${baseUrl}/${id}/update`,
      type: 'POST',
      data: {
        _token: csrfToken,
        target_roles: modalTargetRoles.val(),
        target_user_ids: modalTargetUsers.val(),
        custom_emails: $('#modal_custom_emails').val(),
        priority: $('#modal_priority').val()
      },
      success: function (res) {
        btn.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> حفظ التعديلات');
        editModal.modal('hide');

        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'تم بنجاح!',
            text: res.message || 'تم تحديث الإعدادات بنجاح.',
            timer: 1500,
            showConfirmButton: false
          }).then(() => {
            location.reload();
          });
        } else {
          location.reload();
        }
      },
      error: function () {
        btn.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> حفظ التعديلات');
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: 'حدث خطأ أثناء حفظ الإعدادات.'
          });
        } else {
          alert('حدث خطأ أثناء حفظ الإعدادات.');
        }
      }
    });
  });
});
