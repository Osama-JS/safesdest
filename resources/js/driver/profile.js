import { showAlert } from '../ajax';

$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  }
});

$(document).on('click', '#delete_account_btn', function () {
  let url = baseUrl + 'driver/profile/delete/' + $(this).data('id');

  Swal.fire({
    title: `Delete My Account`,
    text: `Your account will be deleted, and you will no longer be able to log in to the platform.
              However, please note that the tasks you have completed and your financial records will remain stored in the system
              and cannot be deleted as they are part of the platform’s records.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete My Account',
    customClass: {
      confirmButton: 'btn btn-danger me-3 waves-effect waves-light',
      cancelButton: 'btn btn-label-secondary waves-effect waves-light'
    },
    buttonsStyling: false
  }).then(result => {
    if (result.isConfirmed) {
      $.ajax({
        url: url,
        type: 'DELETE',
        success: function (response) {
          if (response.status === 1) {
            showAlert('success', response.success, 3000, true);

            // تسجيل الخروج بعد 3 ثواني عبر الفورم POST
            setTimeout(function () {
              document.getElementById('logout-form').submit();
            }, 3000);

            document.dispatchEvent(new CustomEvent('deletedSuccess'));
          } else {
            showAlert('error', response.error, 10000, true);
          }
        },
        error: function (xhr, status, error) {
          let errorMessage = 'Failed to delete the record';

          // إذا كان السيرفر رجع رسالة خطأ JSON
          if (xhr.responseJSON && xhr.responseJSON.error) {
            errorMessage = xhr.responseJSON.error;
          }
          // إذا كان فيه نص في الاستجابة
          else if (xhr.responseText) {
            errorMessage = xhr.responseText;
          }
          // أو رسالة Ajax نفسها
          else if (error) {
            errorMessage = error;
          }

          showAlert('error', errorMessage, 10000, true);
        }
      });
    }
  });
});
