/**
 * Page User List
 */

'use strict';

// Datatable (jquery)
$(function () {
  var templateId = $('#template_id').val();
  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  fetchPricingData();

  function fetchPricingData() {
    $.ajax({
      url: baseUrl + 'admin/settings/templates/pricing/data/' + templateId,
      type: 'GET',
      dataType: 'json',
      success: function (response) {
        let tableBody = $('#pricing-table tbody');
        tableBody.empty();

        if (response.data.length > 0) {
          $.each(response.data, function (index, item) {
            let row = `<tr>
                          <td>${item.role_name}</td>
                          <td>${item.created_at}</td>
                          <td>
                              <button class="btn btn-sm btn-danger" onclick="deleteRow(${item.id})">Delete</button>
                          </td>
                      </tr>`;
            tableBody.append(row);
          });
        } else {
          tableBody.append('<tr><td colspan="3" class="text-center">No data available</td></tr>');
        }
      },
      error: function () {
        alert('حدث خطأ أثناء جلب البيانات.');
      }
    });
  }

  document.addEventListener('formSubmitted', function (event) {
    dt_user.draw();
    setTimeout(() => {
      $('#submitModal').modal('hide');
    }, 2000);
  });

  $(document).on('change', '#roleFilter', function () {
    dt_user.ajax.reload();
  });

  $(document).on('change', '.edit_status', function () {
    var Id = $(this).data('id');
    $.ajax({
      url: `${baseUrl}admin/settings/pricing/status/${Id}`,
      type: 'post',

      success: function (response) {
        if (response.status != 1) {
          showAlert('error', data.error, 10000, true);
        }
      },
      error: function () {
        showAlert('Error!', 'Failed Request', 'error');
      }
    });
  });

  $(document).on('click', '.edit-record', function () {
    var Id = $(this).data('id');
    var name = $(this).data('name');

    $('#modelTitle').html(`Edit Method: <span class="bg-info text-white px-2 rounded">${name}</span>`);
    // get data
    $.get(`${baseUrl}admin/settings/pricing/edit/${Id}`, function (data) {
      $('#submitModal').modal('show');

      $('#pricing_id').val(data.id);
      $('#pricing-name').val(data.name);
      $('#pricing-description').val(data.description);
      if (data.distance_calculation == true) {
        $('#pricing-distance').attr('checked', true);
      }
    });
  });

  $(document).on('click', '.delete-record', function () {
    var Id = $(this).data('id');
    var name = $(this).data('name');

    Swal.fire({
      title: `Delete ${name}?`,
      text: 'You will not be able to undo this action!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
        cancelButton: 'btn btn-label-secondary waves-effect waves-light'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        $.ajax({
          url: `${baseUrl}admin/settings/pricing/delete/${Id}`,
          type: 'post',

          success: function (response) {
            if (response.status === 1) {
              Swal.fire({
                title: response.success,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
              });

              dt_user.draw();
            } else {
              Swal.fire('Error!', response.error, 'error');
            }
          },
          error: function () {
            Swal.fire('Error!', 'Failed to delete the method', 'error');
          }
        });
      }
    });
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    document.querySelector('.form_submit').reset();
    $('.text-error').html('');
    $('#pricing-distance').attr('checked', false);
    $('#modelTitle').html('Add New Method');
  });

  function showAlert(icon, title, timer, showConfirmButton = false) {
    toastr.options = {
      closeButton: true,
      progressBar: true,
      timeOut: timer || 5000, // زمن الإغلاق التلقائي
      extendedTimeOut: 5000,
      positionClass: 'toast-top-center',
      preventDuplicates: true,
      showMethod: 'fadeIn', // تأثير عند الظهور
      hideMethod: 'fadeOut', // تأثير عند الاختفاء
      showEasing: 'swing',
      hideEasing: 'linear'
    };

    // تحديد نوع التوست حسب الأيقونة
    let toastType =
      icon === 'success' ? 'success' : icon === 'error' ? 'error' : icon === 'warning' ? 'warning' : 'info';

    // عرض الإشعار
    let $toast = toastr[toastType](title);

    // إضافة تأثير tada بعد ظهور التوست
    if ($toast) {
      $toast.addClass('animate__animated animate__tada');
    }
  }
});
