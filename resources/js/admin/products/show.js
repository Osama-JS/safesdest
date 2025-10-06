/**
 * Product Details Page
 */

'use strict';
import { deleteRecord, showAlert } from '../../ajax';

$(function () {
  var dt_vehicles_table = $('.datatables-vehicles');
  var dt_pricing_table = $('.datatables-pricing');
  var productId = $('input[name="product_id"]').val();

  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize Select2 for vehicle sizes
  function initVehicleSelect2() {
    $('#vehicle_size_id').select2({
      placeholder: __('Select Vehicle Size'),
      dropdownParent: $('#vehicleModal'),
      ajax: {
        url: baseUrl + 'admin/products/vehicles/get',
        dataType: 'json',
        delay: 250,
        processResults: function (data) {
          console.log(data);
          return {
            results: data.data.map(function (size) {
              return {
                id: size.id,
                text: size.name
              };
            })
          };
        },
        cache: true
      }
    });
  }

  // Initialize Select2 for customers
  function initCustomerSelect2() {
    $('#customer_id').select2({
      placeholder: __('Select Customer'),
      dropdownParent: $('#pricingModal'),
      ajax: {
        url: baseUrl + 'admin/customers/get/customers',
        dataType: 'json',
        delay: 250,
        processResults: function (data) {
          return {
            results: data.map(function (customer) {
              return {
                id: customer.id,
                text: customer.name
              };
            })
          };
        },
        cache: true
      }
    });
  }

  // Initialize datatables
  if (dt_vehicles_table.length) {
    var dt_vehicles = dt_vehicles_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/products/vehicles/data',
        data: function (d) {
          d.product_id = productId;
        }
      },
      columns: [
        { data: 'fake_id' },
        { data: 'vehicle_size' },
        { data: 'maximum_order' },
        { data: 'notes' },
        { data: null }
      ],
      columnDefs: [
        {
          targets: 0,
          render: function (data, type, full, meta) {
            return `<span>${full.fake_id}</span>`;
          }
        },
        {
          targets: 1,
          render: function (data, type, full, meta) {
            return `<span class="fw-medium">${full.vehicle_size}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return `<span>${full.maximum_order}</span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span>${full.notes || '-'}</span>`;
          }
        },
        {
          targets: -1,
          title: __('Actions'),
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center gap-50">' +
              `<button class="btn btn-sm btn-icon edit-vehicle btn-text-secondary rounded-pill waves-effect" data-id="${full.id}" data-bs-toggle="tooltip" title="${__('Edit')}"><i class="ti ti-edit"></i></button>` +
              `<button class="btn btn-sm btn-icon delete-vehicle btn-text-secondary rounded-pill waves-effect" data-id="${full.id}" data-name="${full.vehicle_size}" data-bs-toggle="tooltip" title="${__('Delete')}"><i class="ti ti-trash"></i></button>` +
              '</div>'
            );
          }
        }
      ],
      order: [[0, 'desc']],
      dom: 't<"row mx-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: {
        info: __('Displaying _START_ to _END_ of _TOTAL_ entries'),
        paginate: {
          next: __('Next'),
          previous: __('Previous')
        }
      }
    });
  }

  if (dt_pricing_table.length) {
    var dt_pricing = dt_pricing_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/products/pricing/data',
        data: function (d) {
          d.product_id = productId;
        }
      },
      columns: [{ data: 'fake_id' }, { data: 'customer' }, { data: 'price' }, { data: 'notes' }, { data: null }],
      columnDefs: [
        {
          targets: 0,
          render: function (data, type, full, meta) {
            return `<span>${full.fake_id}</span>`;
          }
        },
        {
          targets: 1,
          render: function (data, type, full, meta) {
            return `<span class="fw-medium">${full.customer}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return `<span class="fw-medium text-success">${full.price} ${__('SAR')}</span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span>${full.notes || '-'}</span>`;
          }
        },
        {
          targets: -1,
          title: __('Actions'),
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center gap-50">' +
              `<button class="btn btn-sm btn-icon edit-pricing btn-text-secondary rounded-pill waves-effect" data-id="${full.id}" data-bs-toggle="tooltip" title="${__('Edit')}"><i class="ti ti-edit"></i></button>` +
              `<button class="btn btn-sm btn-icon delete-pricing btn-text-secondary rounded-pill waves-effect" data-id="${full.id}" data-name="${full.customer}" data-bs-toggle="tooltip" title="${__('Delete')}"><i class="ti ti-trash"></i></button>` +
              '</div>'
            );
          }
        }
      ],
      order: [[0, 'desc']],
      dom: 't<"row mx-1"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: {
        info: __('Displaying _START_ to _END_ of _TOTAL_ entries'),
        paginate: {
          next: __('Next'),
          previous: __('Previous')
        }
      }
    });
  }

  // Initialize select2 when modals are shown
  $('#vehicleModal').on('shown.bs.modal', function () {
    initVehicleSelect2();
  });

  $('#pricingModal').on('shown.bs.modal', function () {
    initCustomerSelect2();
  });

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');

    setTimeout(() => {
      $('#vehicleModal').modal('hide');
      $('#pricingModal').modal('hide');
    }, 2000);

    if (dt_vehicles) {
      dt_vehicles.draw();
      dt_pricing.draw();
    }
  });
  document.addEventListener('deletedSuccess', function (event) {
    if (dt_vehicles) {
      dt_vehicles.draw();
      dt_pricing.draw();
    }
  });

  // Edit vehicle
  $(document).on('click', '.edit-vehicle', function () {
    var vehicleId = $(this).data('id');

    $.get(baseUrl + 'admin/products/vehicles/edit/' + vehicleId, function (data) {
      if (data.status === 1) {
        var vehicle = data.data;

        $('#vehicle_id').val(vehicle.id);
        $('#vehicle_size_id').append(new Option(vehicle.vehicle_size, vehicle.vehicle_size_id, true, true));
        $('#maximum_order').val(vehicle.maximum_order);
        $('#vehicle_notes').val(vehicle.notes);

        $('#vehicleModalTitle').html(__('Edit Vehicle'));
        $('#vehicleModal').modal('show');
      } else {
        showAlert('error', data.error);
      }
    });
  });

  // Edit pricing
  $(document).on('click', '.edit-pricing', function () {
    var pricingId = $(this).data('id');

    $.get(baseUrl + 'admin/products/pricing/edit/' + pricingId, function (data) {
      if (data.status === 1) {
        var pricing = data.data;

        $('#pricing_id').val(pricing.id);
        $('#customer_id').append(new Option(pricing.customer, pricing.customer_id, true, true));
        $('#special_price').val(pricing.price);
        $('#pricing_notes').val(pricing.notes);

        $('#pricingModalTitle').html(__('Edit Customer Price'));
        $('#pricingModal').modal('show');
      } else {
        showAlert('error', data.error);
      }
    });
  });

  // Delete vehicle
  $(document).on('click', '.delete-vehicle', function () {
    var vehicleId = $(this).data('id');
    var url = baseUrl + 'admin/products/vehicles/delete/' + vehicleId;
    deleteRecord($(this).data('name'), url);
  });

  // Delete pricing
  $(document).on('click', '.delete-pricing', function () {
    var pricingId = $(this).data('id');
    var url = baseUrl + 'admin/products/pricing/delete/' + pricingId;
    deleteRecord($(this).data('name'), url);
  });

  // Reset modals on hide
  $('#vehicleModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    $('.text-error').html('');
    $('#vehicle_id').val('');
    $('#vehicleModalTitle').html(__('Add Vehicle'));
    $('#vehicle_size_id').empty();
  });

  $('#pricingModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    $('.text-error').html('');
    $('#pricing_id').val('');
    $('#pricingModalTitle').html(__('Add Customer Price'));
    $('#customer_id').empty();
  });
});
