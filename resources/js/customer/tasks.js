/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, generateFields, showFormModal } from '../ajax';

// Datatable (jquery)
$(function () {
  generateFields(taskTemplate);

  var select2 = $('.select2');
  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: __('Select Tags'),
      dropdownParent: $this.parent(),
      closeOnSelect: false
    });
  }
  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  /* ================  Select Vehicles Code   =============== */
  let vehicleIndex = 0;
  const selectedTypes = new Set();

  function createVehicleRow(index) {
    return $('#vehicle-row-template').html().replaceAll('{index}', index);
  }

  function updateVehicleRowEvents($row) {
    const $vehicleSelect = $row.find('.vehicle-select');
    const $typeSelect = $row.find('.vehicle-type-select');
    const $sizeSelect = $row.find('.vehicle-size-select');

    $vehicleSelect.on('change', function () {
      const vehicleId = $(this).val();
      $typeSelect.prop('disabled', true).empty().append('<option>Loading...</option>');
      $sizeSelect.prop('disabled', true).empty().append('<option>Select a vehicle size</option>');

      if (vehicleId) {
        $.get(`${baseUrl}admin/settings/vehicles/types/${vehicleId}`, function (types) {
          $typeSelect.empty().append('<option value="">Select a vehicle type</option>');
          types.forEach(type => {
            // if (!selectedTypes.has(type.id.toString())) {
            $typeSelect.append(`<option value="${type.id}">${type.name}</option>`);
            // }
          });
          $typeSelect.prop('disabled', false);
        });
      }
    });

    $typeSelect.on('change', function () {
      const typeId = $(this).val();
      $sizeSelect.prop('disabled', true).empty().append('<option>Loading...</option>');

      if (typeId) {
        selectedTypes.add(typeId);
        $.get(`${baseUrl}admin/settings/vehicles/sizes/${typeId}`, function (sizes) {
          $sizeSelect.empty().append('<option value="">Select a vehicle size</option>');
          sizes.forEach(size => {
            $sizeSelect.append(`<option value="${size.id}">${size.name}</option>`);
          });
          $sizeSelect.prop('disabled', false);
        });
      }
    });

    // $row.find('.remove-vehicle-btn').on('click', function () {
    //   const removedTypeId = $typeSelect.val();
    //   if (removedTypeId) {
    //     selectedTypes.delete(removedTypeId);
    //   }
    //   $row.remove();
    // });
  }

  const $newRow = $(createVehicleRow(vehicleIndex++));
  $('#vehicle-selection-container').append($newRow);
  updateVehicleRowEvents($newRow);

  /* ================  Form Template Code   =============== */

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');
    $('.preview-image').attr('src', baseUrl + 'assets/img/person.png');
    $('#additional-form').html('');
    $('#select-template').val('');
    $('#customer-tags').val([]).trigger('change');

    setTimeout(() => {
      $('#submitModal').modal('hide');
    }, 2000);

    if (dt_data) {
      dt_data.draw();
    }
  });

  document.addEventListener('deletedSuccess', function (event) {
    if (dt_data) {
      dt_data.draw();
    }
  });

  $(document).on('click', '.edit-record', function () {
    var data_id = $(this).data('id'),
      dtrModal = $('.dtr-bs-modal.show');
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }
    $.get(`${baseUrl}admin/customers/edit/${data_id}`, function (data) {
      $('.form_submit').trigger('reset');

      $('.text-error').html('');
      $('#customer_id').val(data.id);
      $('#customer-fullname').val(data.name);
      $('#customer-email').val(data.email);
      $('#customer-phone').val(data.phone);
      $('#phone-code').val(data.phone_code);
      $('#customer-role').val(data.role_id);
      $('#customer-c_name').val(data.company_name);
      $('#customer-c_address').val(data.company_address);
      $('#customer-tags').val(data.tagsIds).trigger('change');
      if (data.img !== null) {
        $('.preview-image').attr('src', data.img);
      }
      $('#additional-form').html('');
      $('#select-template').val(data.form_template_id);

      generateFields(data.fields, data.additional_data);

      $('#modelTitle').html(`Edit User: <span class="bg-info text-white px-2 rounded">${data.name}</span>`);
    });
  });

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/customers/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  $(document).on('click', '.status-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const status = $(this).data('status');

    const fields = `
      <input type="hidden" name="id" value="${id}">
      <select class="form-select" name="status">
        <option value="active" ${status === 'active' ? 'selected' : ''}>Active</option>
        <option value="verified" ${status === 'verified' ? 'selected' : ''}>Unverified</option>
        <option value="blocked" ${status === 'blocked' ? 'selected' : ''}>Blocked</option>
      </select>
    `;

    showFormModal({
      title: `Change Customer: ${name} Status`,
      icon: 'info',
      fields: fields,
      url: `${baseUrl}admin/customers/status`,
      method: 'POST',
      dataTable: dt_data // إعادة تحميل الجدول إذا موجود
    });
  });

  $(document).on('click', '.wallet-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const fields = `
      <input type="hidden" name="id" value="${id}">
    `;

    showFormModal({
      title: `Create Wallet For Customer: <h4> <span class="bg-info p-0 px-2 rounded text-white"> ${name} </span> </h4>`,
      icon: 'info',
      fields: fields,
      url: `${baseUrl}admin/customers/wallet/create`,
      method: 'POST',
      dataTable: dt_data // إعادة تحميل الجدول إذا موجود
    });
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    $('.preview-image').attr('src', baseUrl + 'assets/img/person.png');
    $('#customer-tags').val([]).trigger('change');
    $('.text-error').html('');
    $('#customer_id').val('');
    $('#modelTitle').html(__('Add New Customer'));
    $('#additional-form').html('');
    $('#select-template').val('');
  });
});
