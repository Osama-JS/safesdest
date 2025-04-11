/**
 * Page User List
 */

'use strict';
import { deleteRecord } from '../ajax';

// Datatable (jquery)
$(function () {
  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
  function loadData(vehicle = '', type = '', lodeType = true, loadAll = true, loadSize = true) {
    $.ajax({
      url: baseUrl + 'admin/settings/vehicles/data',
      type: 'GET',
      data: { vehicle: vehicle, type: type },
      success: function (response) {
        if (loadAll) {
          var vehicles = response.data.vehicles
            .map(
              (vehicle, index) => `
          <tr>
            <td>${index + 1}</td>
            <td>${vehicle.name} - ${vehicle.en_name}</td>
            <td>${vehicle.types}</td>
            <td>
              <button class="btn btn-sm btn-icon edit-v-record btn-text-secondary rounded-pill waves-effect"
                data-id="${vehicle.id}" data-name="${vehicle.name}"  data-enname="${vehicle.en_name}"
                >
                <i class="ti ti-edit"></i>
              </button>
              <button class="btn btn-sm btn-icon delete-v-record btn-text-secondary rounded-pill waves-effect"
                data-id="${vehicle.id}" data-name="${vehicle.name}">
                <i class="ti ti-trash"></i>
              </button>
            </td>
          </tr>`
            )
            .join('');

          if (response.data.vehicles.length === 0) {
            vehicles = `<tr>
              <td colspan="4" class="text-center">No data available</td>
            </tr>`;
          }
          $('#vehicle-table').html(vehicles);

          var types = response.data.types
            .map(
              (type, index) => `
          <tr>
            <td>${index + 1}</td>
            <td>${type.vehicle}</td>
            <td>${type.name} - ${type.en_name}</td>
            <td>${type.sizes}</td>
            <td>
              <button class="btn btn-sm btn-icon edit-t-record btn-text-secondary rounded-pill waves-effect"
                data-id="${type.id}" data-name="${type.name}" data-enname="${type.en_name}" data-vehicle="${type.vehicle_id}">
                <i class="ti ti-edit"></i>
              </button>
              <button class="btn btn-sm btn-icon delete-t-record btn-text-secondary rounded-pill waves-effect"
                data-id="${type.id}" data-name="${type.name}">
                <i class="ti ti-trash"></i>
              </button>
            </td>
          </tr>`
            )
            .join('');

          if (response.data.types.length === 0) {
            types = `<tr>
                <td colspan="5" class="text-center">No data available</td>
              </tr>`;
          }
          $('#types-table').html(types);

          var sizes = response.data.sizes
            .map(
              (size, index) => `
          <tr>
            <td>${index + 1}</td>
            <td>${size.vehicle}</td>
            <td>${size.type}</td>
            <td>${size.name}</td>
            <td>
              <button class="btn btn-sm btn-icon edit-s-record btn-text-secondary rounded-pill waves-effect"
                data-id="${size.id}" data-name="${size.name}" data-type="${size.type_id}" data-vehicle="${size.vehicle_id}" >
                <i class="ti ti-edit"></i>
              </button>
              <button class="btn btn-sm btn-icon delete-s-record btn-text-secondary rounded-pill waves-effect"
                data-id="${size.id}" data-name="${size.name}">
                <i class="ti ti-trash"></i>
              </button>
            </td>
          </tr>`
            )
            .join('');

          if (response.data.sizes.length === 0) {
            sizes = `<tr>
                  <td colspan="5" class="text-center">No data available</td>
                </tr>`;
          }
          $('#sizes-table').html(sizes);
        }
        // توليد القوائم المنسدلة
        var vehicle_options = ` <option value="">-- Select vehicle --</option>`;
        vehicle_options += response.data.vehicles
          .map(
            option => `
          <option value="${option.id}">${option.name} - ${option.en_name}</option>
        `
          )
          .join('');
        if (lodeType) {
          $('.vehicle-type-vehicle').html(vehicle_options);
        }

        var vehicle_type_options = ` <option value="">-- select vehicle type --</option>`;
        vehicle_type_options += response.data.types
          .map(
            option => `
          <option value="${option.id}"> ${option.name} - ${option.en_name}</option>
        `
          )
          .join('');

        if (loadSize) {
          $('.vehicle-sizes-vehicle').html(vehicle_type_options);
        }

        var vehicle_sizes_options = ` <option value="">-- select vehicle Size --</option>`;
        vehicle_sizes_options += response.data.sizes
          .map(
            size => `
          <option value="${size.id}"> ${size.name}</option>
        `
          )
          .join('');

        if (loadSize) {
          $('#size-vehicle').html(vehicle_type_options);
        }
      }
    });
  }

  loadData();

  $(document).on('change', '#type-vehicle-flitter', function () {
    var vehicle = $(this).val();
    loadData(vehicle, '', false);
  });

  $(document).on('change', '#vehicle-size-vehicle', function () {
    var vehicle = $(this).val();
    loadData(vehicle, '', false, false);
  });

  $(document).on('change', '#size-vehicle-flitter', function () {
    var vehicle = $(this).val();
    loadData(vehicle, '', false, false);
  });

  $(document).on('change', '#size-type-flitter', function () {
    var vehicle = $(this).val();
    loadData(vehicle, vehicle, false, true, false);
  });

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');
    $('#vehicle-id').val('');
    $('#vehicle-type-id').val('');
    $('#vehicle-size-id').val('');

    loadData();
  });
  document.addEventListener('deletedSuccess', function (event) {
    loadData();
  });

  $(document).on('click', '.edit-v-record', function () {
    var Id = $(this).data('id');
    var name = $(this).data('name');
    var en_name = $(this).data('enname');

    $('#vehicle-name').val(name);
    $('#vehicle-en-name').val(en_name);
    $('#vehicle-id').val(Id);
  });

  $(document).on('click', '.edit-t-record', function () {
    var Id = $(this).data('id');
    var name = $(this).data('name');
    var en_name = $(this).data('enname');
    var vehicle = $(this).data('vehicle');

    $('#vehicle-type-name').val(name);
    $('#vehicle-type-en-name').val(en_name);
    $('#vehicle-type-vehicle').val(vehicle);
    $('#vehicle-type-id').val(Id);
  });

  $(document).on('click', '.edit-s-record', function () {
    var Id = $(this).data('id');
    var name = $(this).data('name');
    var vehicle = $(this).data('vehicle');
    var type = $(this).data('type');

    $('#vehicle-size-name').val(name);
    $('#vehicle-size-type').val(type);
    $('#vehicle-size-vehicle').val(vehicle);
    $('#vehicle-size-id').val(Id);
  });

  $(document).on('click', '.delete-v-record', function () {
    let url = baseUrl + 'admin/settings/vehicles/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  $(document).on('click', '.delete-t-record', function () {
    let url = baseUrl + 'admin/settings/vehicles/type/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  $(document).on('click', '.delete-s-record', function () {
    let url = baseUrl + 'admin/settings/vehicles/size/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });
});
