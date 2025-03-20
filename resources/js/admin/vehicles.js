/**
 * Page User List
 */

'use strict';

// Datatable (jquery)
$(function () {
  function loadData(vehicle = '', type = '') {
    $.ajax({
      url: baseUrl + 'admin/settings/vehicles/data',
      type: 'GET',
      data: { vehicle: vehicle, type: type },
      success: function (response) {
        console.log(response);
        var vehicles = response.data.vehicles.map((vehicle, index) => {
          return `<tr>
                    <td>${index + 1}</td>
                    <td>${vehicle.name} - ${vehicle.en_name}</td>
                    <td>${vehicle.types}</td>
                    <td>
                      <button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${vehicle.id}" data-name="${vehicle.name}"  data-enname="${vehicle.en_name}" data-bs-toggle="modal" data-bs-target="#largeModal"><i class="ti ti-edit"></i></button>
                      <button class="btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect" data-id="${vehicle.id}" data-name="${vehicle.name}"><i class="ti ti-trash"></i></button>
                    </td>
                  </tr>`;
        });
        $('#vehicle-table').html(vehicles);

        var types = response.data.types.map((type, index) => {
          return `<tr>
                    <td>${index + 1}</td>
                    <td>${type.vehicle}</td>
                    <td>${type.name} - ${type.en_name}</td>
                    <td>${type.sizes}</td>
                    <td>
                      <button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${type.id}" data-name="${type.name}"  data-enname="${type.en_name}" data-bs-toggle="modal" data-bs-target="#largeModal"><i class="ti ti-edit"></i></button>
                      <button class="btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect" data-id="${type.id}" data-name="${type.name}"><i class="ti ti-trash"></i></button>
                    </td>
                  </tr>`;
        });
        $('#types-table').html(types);

        var sizes = response.data.sizes.map((size, index) => {
          return `<tr>
                    <td>${index + 1}</td>
                    <td>${size.vehicle}</td>
                    <td>${size.type}</td>
                    <td>${size.name}</td>
                    <td>
                      <button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${size.id}" data-name="${size.name}"  data-enname="${size.en_name}" data-bs-toggle="modal" data-bs-target="#largeModal"><i class="ti ti-edit"></i></button>
                      <button class="btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect" data-id="${size.id}" data-name="${size.name}"><i class="ti ti-trash"></i></button>
                    </td>
                  </tr>`;
        });
        $('#sizes-table').html(sizes);

        var vehicle_options = ` <option value="">-- select vehicle</option>`;
        vehicle_options += response.data.vehicles.map((option, index) => {
          return `<option value="${option.id}">${option.name} - ${option.en_name}</option>`;
        });
        $('.vehicle-type-vehicle').html(vehicle_options);

        var vehicle_type_options = ` <option value="">-- select vehicle type</option>`;
        vehicle_type_options += response.data.types.map((option, index) => {
          return `<option value="${option.id}">${option.vehicle} - ${option.name} - ${option.en_name}  </option>`;
        });
        $('.vehicle-sizes-vehicle').html(vehicle_type_options);
      }
    });
  }

  loadData();

  $(document).on('change', '.vehicle-type-vehicle', function () {
    var vehicle = $(this).val();
    loadData(vehicle);
    $(this).val(vehicle);
  });
  document.addEventListener('formSubmitted', function (event) {
    loadData();
  });

  $(document).on('click', '.edit-record', function () {
    var teamId = $(this).data('id');
    var teamName = $(this).data('name');

    $('#submitModal').modal('show');

    $('#modelTitle').html(`Edit Team: <span class="bg-info text-white px-2 rounded">${teamName}</span>`);

    $('.form_submit').attr('action', `${baseUrl}admin/teams/edit`);

    // get data
    $.get(`${baseUrl}admin/teams/edit/${teamId}`, function (data) {
      $('#team_id').val(data.id);
      $('#team-name').val(data.name);
      $('#team-address').val(data.address);
      $('#team-location_update').val(data.location_update_interval);
      $('#team-commission-type').val(data.team_commission_type);
      $('#team-commission').val(data.team_commission_value);
      $('#team-note').val(data.note);
    });
  });
});
