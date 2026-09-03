import { generateFields } from '../../ajax';

/**
 * Force Edit Task Handler for Admin Panel
 * Uses password verification and standard #submitModal with locked vehicle fields
 */

$(document).ready(function () {
  function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  /**
   * Request password before allowing force edit
   */
  window.requestForceEditPassword = function (taskId, callback) {
    if (!taskId) return;

    Swal.fire({
      title: __('تأكيد التعديل الإجباري'),
      html: `
        <p class="text-muted mb-3">${__('يرجى إدخال كلمة المرور الخاصة بحسابك للتحقق قبل المتابعة للتعديل الإجباري للمهمة')} <strong class="text-warning">#${taskId}</strong></p>
      `,
      input: 'password',
      inputPlaceholder: __('أدخل كلمة المرور...'),
      inputAttributes: {
        autocapitalize: 'off',
        autocorrect: 'off',
        autocomplete: 'current-password',
        id: 'force-edit-password-input'
      },
      showCancelButton: true,
      confirmButtonText: `<i class="ti ti-lock-open me-1"></i> ${__('تحقق ومتابعة')}`,
      cancelButtonText: __('إلغاء'),
      showLoaderOnConfirm: true,
      customClass: {
        confirmButton: 'btn btn-warning me-3 waves-effect waves-light',
        cancelButton: 'btn btn-label-secondary waves-effect waves-light'
      },
      buttonsStyling: false,
      preConfirm: (password) => {
        if (!password) {
          Swal.showValidationMessage(__('يرجى إدخال كلمة المرور'));
          return false;
        }

        return $.ajax({
          url: `${baseUrl}admin/tasks/verify-force-edit-password`,
          type: 'POST',
          data: {
            password: password,
            task_id: taskId,
            _token: $('meta[name="csrf-token"]').attr('content')
          }
        }).then(response => {
          if (response.status !== 1) {
            throw new Error(response.message || __('كلمة المرور غير صحيحة'));
          }
          return response;
        }).catch(error => {
          let msg = __('كلمة المرور غير صحيحة، يرجى المحاولة مرة أخرى.');
          if (error.responseJSON && error.responseJSON.message) {
            msg = error.responseJSON.message;
          } else if (error.message) {
            msg = error.message;
          }
          Swal.showValidationMessage(msg);
        });
      },
      allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
      if (result.isConfirmed) {
        if (typeof callback === 'function') {
          callback(taskId);
        }
      }
    });
  };

  /**
   * Open the standard #submitModal populated with force-edit data
   */
  window.openStandardForceEditModal = function (taskId) {
    if (!taskId) return;

    Swal.fire({
      title: __('جاري تحميل بيانات المهمة...'),
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    $.get(`${baseUrl}admin/tasks/force-edit/${taskId}`, async function (data) {
      Swal.close();

      if (data.status === 2) {
        Swal.fire({
          icon: 'error',
          title: __('خطأ'),
          text: data.error || __('تعذر تحميل بيانات المهمة')
        });
        return;
      }

      // 1. Configure modal for Force Edit
      $('#task-form').attr('action', `${baseUrl}admin/tasks/force-update`);
      $('#modelTitle').html(`${__('Force Edit Task')}: <span class="badge bg-warning text-dark px-2 rounded">#${taskId}</span>`);
      $('#is-force-update').val('1');
      $('#force-update-notice').show();

      // 2. Populate basic info
      $('#task-id').val(data.id);
      $('#task-owner').val(data.owner).trigger('change');
      $('#task-customer').val(data.customer_id).trigger('change');

      // 3. Populate broker details
      $('#task-broker-id').val(data.broker_id).trigger('change');
      $('#task-broker-commission-type').val(data.broker_commission_type).trigger('change');
      $('#task-broker-commission-value').val(data.broker_commission_value);

      // 4. Populate dates & notes
      if (data.created_at) {
        let date = new Date(data.created_at);
        let formattedDate = date.toISOString().slice(0, 16);
        $('#task_created_at').val(formattedDate);
      }
      $('#conditions').val(data.conditions || '');

      // 5. Populate vehicle data and visually LOCK vehicle inputs
      $('.vehicle-quantity').hide();
      $('.vehicle-select').val(data.vehicle).trigger('change');

      $('#submitModal').modal('show');

      await delay(1000);
      $('.vehicle-type-select').val(data.vehicle_type).trigger('change');

      await delay(1000);
      $('.vehicle-size-select').val(data.vehicle_size_id).trigger('change');

      // Lock vehicle inputs visually so the user cannot change them, while preserving their values in FormData
      $('.vehicle-select, .vehicle-type-select, .vehicle-size-select')
        .prop('disabled', false)
        .css({
          'pointer-events': 'none',
          'background-color': '#e9ecef',
          'cursor': 'not-allowed',
          'opacity': '0.85'
        })
        .attr('tabindex', '-1');

      // 6. Dynamic Form Template & Fields
      $('#additional-form').html('');
      var currentTemplateId = data.form_template_id;
      if (!currentTemplateId && typeof templateId !== 'undefined') {
        currentTemplateId = templateId;
      }
      $('#select-template').val(currentTemplateId || '');

      var additionalData = data.additional_data || {};
      if (typeof additionalData === 'string') {
        try {
          additionalData = JSON.parse(additionalData);
        } catch (e) {
          additionalData = {};
        }
      }

      var genFn = (typeof generateFields === 'function') ? generateFields : window.generateFields;

      if (data.fields && data.fields.length > 0) {
        if (typeof genFn === 'function') {
          genFn(data.fields, additionalData);
        }
      } else if (currentTemplateId) {
        $.get(`${baseUrl}admin/settings/templates/fields`, { id: currentTemplateId }, function (res) {
          if (res && res.fields && typeof genFn === 'function') {
            genFn(res.fields, additionalData);
          }
        });
      }

      // 7. Pricing attributes
      if (data.pricing_history) {
        var pricingHist = data.pricing_history;
        if (typeof pricingHist === 'string') {
          try { pricingHist = JSON.parse(pricingHist); } catch (e) {}
        }
        $('#task-id').attr('data-method', pricingHist.pricing_method_id !== undefined ? pricingHist.pricing_method_id : '');
        $('#task-id').attr('data-point', pricingHist.point_id || '');

        if (pricingHist.pricing_method_id == 0 && data.ad) {
          $('#task-id').attr('data-min', data.ad.lowest_price || 0.0);
          $('#task-id').attr('data-max', data.ad.highest_price || 0.0);
          $('#task-id').attr('data-note', data.ad.description || '');
          $('#task-id').attr('data-included', data.ad.included || false);
        }
      }

      // 8. Pickup details
      if (data.pickup) {
        $('#pickup-contact-name').val(data.pickup.contact_name || '');
        $('#pickup-contact-phone').val(data.pickup.contact_phone || '');
        $('#pickup-contact-email').val(data.pickup.contact_emil || '');
        $('#pickup-before').val(data.pickup.scheduled_time || '');
        $('#pickup-address').val(data.pickup.address || '');
        $('#pickup-longitude').val(data.pickup.longitude || '');
        $('#pickup-latitude').val(data.pickup.latitude || '');
        $('#pickup-note').val(data.pickup.note || '');
      }

      // 9. Delivery details
      if (data.delivery) {
        $('#delivery-contact-name').val(data.delivery.contact_name || '');
        $('#delivery-contact-phone').val(data.delivery.contact_phone || '');
        $('#delivery-contact-email').val(data.delivery.contact_emil || '');
        $('#delivery-before').val(data.delivery.scheduled_time || '');
        $('#delivery-address').val(data.delivery.address || '');
        $('#delivery-longitude').val(data.delivery.longitude || '');
        $('#delivery-latitude').val(data.delivery.latitude || '');
        $('#delivery-note').val(data.delivery.note || '');
      }

      // 10. Pricing details
      if (data.pricing_type === 'manual') {
        $('#total-price').val(data.total_price);
      }
      if (data.commission_type === 'manual') {
        $('#task-commission').val(data.commission);
      }

      if (typeof renderPricingDetails === 'function') {
        renderPricingDetails(data.pricing_details);
      }
    }).fail(function (xhr) {
      Swal.close();
      var msg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : __('تعذر الاتصال بالخادم');
      Swal.fire({
        icon: 'error',
        title: __('خطأ'),
        text: msg
      });
    });
  };

  /**
   * Main entry point for Force Edit
   */
  window.openForceEditModal = function (taskId) {
    window.requestForceEditPassword(taskId, function (confirmedTaskId) {
      window.openStandardForceEditModal(confirmedTaskId);
    });
  };

  /**
   * Listen for force-edit click event across all pages
   */
  $(document).on('click', '.force-edit-task', function (e) {
    e.preventDefault();
    var taskId = $(this).data('id');
    window.openForceEditModal(taskId);
  });

  /**
   * Reset modal state cleanly back to initial create mode
   */
  window.resetTaskSubmitModal = function () {
    // 1. Reset form and native values
    if ($('#task-form').length) {
      $('#task-form')[0].reset();
      $('.form_submit').trigger('reset');
    }

    // 2. Reset Tabs to Step 1
    if (document.querySelector('#tab-step1')) {
      new bootstrap.Tab(document.querySelector('#tab-step1')).show();
    }

    // 3. Clear Dynamic content & fields
    $('#additional-form').html('');
    $('#taskFinalDetails').html('');
    $('#params-select-wrapper').remove();
    $('.text-error, span.text-error').html('');
    $('#task-id').val('').removeAttr('data-method').removeAttr('data-point');
    $('#task_id').val('');
    $('.task-priceing-hint').remove();
    $('#pricing-details-container').html('');

    // 4. Reset Select2 & Dropdowns
    $('#task-owner').val('admin').trigger('change');
    $('#task-customer').val('').trigger('change');
    $('#task-broker-id').val('').trigger('change');
    $('.vehicle-select').val('').trigger('change');
    $('.vehicle-type-select').val('').trigger('change');
    $('.vehicle-size-select').val('').trigger('change');

    if (typeof templateId !== 'undefined' && templateId) {
      $('#select-template').val(templateId).trigger('change');
    } else {
      $('#select-template').val('').trigger('change');
    }

    // 5. Reset Force Edit Flags & Styles
    $('#is-force-update').val('0');
    $('#force-update-notice').hide();
    $('.vehicle-select, .vehicle-type-select, .vehicle-size-select')
      .prop('disabled', false)
      .css({
        'pointer-events': '',
        'background-color': '',
        'cursor': '',
        'opacity': ''
      })
      .removeAttr('tabindex');
    $('.vehicle-quantity').show();

    // 6. Reset Form Action & Title
    $('#task-form').attr('action', `${baseUrl}admin/tasks`);
    $('#modelTitle').html(__('Add New Tasks'));

    if (typeof Detailsindex !== 'undefined') {
      Detailsindex = 0;
    }
  };

  /**
   * Hide modal on form submission across views
   */
  document.addEventListener('formSubmitted', function (event) {
    setTimeout(() => {
      $('#submitModal').modal('hide');
    }, 1200);
  });

  /**
   * Reset modal state on hidden
   */
  $('#submitModal').on('hidden.bs.modal', function () {
    window.resetTaskSubmitModal();
  });
});
