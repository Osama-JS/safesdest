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

  $('.update-setting-select').on('change', function () {
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
});
