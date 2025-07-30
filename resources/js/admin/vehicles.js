/**
 * Page User List
 */

'use strict';
import { deleteRecord } from '../ajax';

// Datatable (jquery)
$(function () {
  // Check if baseUrl is defined
  if (typeof baseUrl === 'undefined') {
    console.error('baseUrl is not defined. Make sure config.js is loaded before this script.');
    return;
  }

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
  // Global pagination state
  let currentPage = {
    vehicles: 1,
    types: 1,
    sizes: 1
  };

  let perPage = {
    vehicles: 10,
    types: 10,
    sizes: 10
  };

  function loadData(vehicle = '', type = '', lodeType = true, loadAll = true, loadSize = true, tab = 'all', page = 1) {
    console.log('loadData called with:', { vehicle, type, lodeType, loadAll, loadSize, tab, page });

    // Update current page for the specific tab
    if (tab !== 'all') {
      currentPage[tab] = page;
    }

    $.ajax({
      url: baseUrl + 'admin/settings/vehicles/data',
      type: 'GET',
      data: {
        vehicle: vehicle,
        type: type,
        tab: tab,
        page: page,
        per_page:
          tab === 'vehicles' ? perPage.vehicles : tab === 'types' ? perPage.types : tab === 'sizes' ? perPage.sizes : 10
      },
      success: function (response) {
        console.log('AJAX Response:', response); // Debug log

        // Check if response has the expected structure
        if (!response || !response.data) {
          console.error('Invalid response structure:', response);
          return;
        }

        // Update statistics
        updateStatistics(response.data);

        // تحديث جدول المركبات
        if ((loadAll || tab === 'vehicles') && response.data.vehicles) {
          console.log('Updating vehicles table with data:', response.data.vehicles);
          var vehiclesData = response.data.vehicles.data || response.data.vehicles || [];
          var vehicles = vehiclesData
            .map((vehicle, index) => {
              // Calculate the correct index based on pagination
              let actualIndex =
                response.data.vehicles && response.data.vehicles.pagination
                  ? (response.data.vehicles.pagination.current_page - 1) * response.data.vehicles.pagination.per_page +
                    index +
                    1
                  : index + 1;

              return `
          <tr>
            <td class="text-center"><strong>${actualIndex}</strong></td>
            <td>
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-2">
                  <div class="avatar-initial bg-label-primary rounded">
                    <i class="ti ti-car"></i>
                  </div>
                </div>
                <div>
                  <h6 class="mb-0">${vehicle.name}</h6>
                  <small class="text-muted">${vehicle.en_name}</small>
                </div>
              </div>
            </td>
            <td>
              <span class="badge bg-label-info">${vehicle.types} ${__('types')}</span>
            </td>
            <td class="text-center">
              <button class="btn btn-action btn-sm btn-outline-primary edit-v-record"
                data-id="${vehicle.id}" data-name="${vehicle.name}" data-enname="${vehicle.en_name}"
                title="${__('Edit')}">
                <i class="ti ti-edit"></i>
              </button>
              <button class="btn btn-action btn-sm btn-outline-danger delete-v-record"
                data-id="${vehicle.id}" data-name="${vehicle.name}"
                title="${__('Delete')}">
                <i class="ti ti-trash"></i>
              </button>
            </td>
          </tr>`;
            })
            .join('');

          if (!vehiclesData || vehiclesData.length === 0) {
            vehicles = `<tr>
              <td colspan="4" class="text-center text-muted">
                <div class="empty-state">
                  <i class="ti ti-car-off"></i>
                  <h6>${__('No Vehicles Found')}</h6>
                  <p>${__('Start by adding your first vehicle above')}</p>
                </div>
              </td>
            </tr>`;
          }
          console.log('Updating #vehicle-table with HTML:', vehicles.substring(0, 200) + '...');
          $('#vehicle-table').html(vehicles);

          // Update pagination for vehicles
          if (response.data.vehicles && response.data.vehicles.pagination) {
            console.log('Updating vehicles pagination:', response.data.vehicles.pagination);
            updatePagination('vehicles', response.data.vehicles.pagination);
          } else {
            console.log('No pagination data for vehicles');
          }
        }

        // تحديث جدول الأنواع
        if ((lodeType || tab === 'types' || tab === 'all') && response.data.types) {
          var typesData =
            response.data.types && response.data.types.data ? response.data.types.data : response.data.types || [];
          var types = typesData
            .map((type, index) => {
              // Calculate the correct index based on pagination
              let actualIndex =
                response.data.types && response.data.types.pagination
                  ? (response.data.types.pagination.current_page - 1) * response.data.types.pagination.per_page +
                    index +
                    1
                  : index + 1;

              return `
          <tr>
            <td class="text-center"><strong>${actualIndex}</strong></td>
            <td>
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-2">
                  <div class="avatar-initial bg-label-success rounded">
                    <i class="ti ti-car"></i>
                  </div>
                </div>
                <span class="fw-medium">${type.vehicle}</span>
              </div>
            </td>
            <td>
              <div>
                <h6 class="mb-0">${type.name}</h6>
                <small class="text-muted">${type.en_name}</small>
              </div>
            </td>
            <td>
              <span class="badge bg-label-warning">${type.sizes} ${__('sizes')}</span>
            </td>
            <td class="text-center">
              <button class="btn btn-action btn-sm btn-outline-primary edit-t-record"
                data-id="${type.id}" data-name="${type.name}" data-enname="${type.en_name}" data-vehicle="${type.vehicle_id}"
                title="${__('Edit')}">
                <i class="ti ti-edit"></i>
              </button>
              <button class="btn btn-action btn-sm btn-outline-danger delete-t-record"
                data-id="${type.id}" data-name="${type.name}"
                title="${__('Delete')}">
                <i class="ti ti-trash"></i>
              </button>
            </td>
          </tr>`;
            })
            .join('');

          if (!typesData || typesData.length === 0) {
            types = `<tr>
                <td colspan="5" class="text-center text-muted">
                  <div class="empty-state">
                    <i class="ti ti-category-off"></i>
                    <h6>${__('No Vehicle Types Found')}</h6>
                    <p>${__('Add vehicle types to organize your fleet')}</p>
                  </div>
                </td>
              </tr>`;
          }
          $('#types-table').html(types);

          // Update pagination for types
          if (response.data.types && response.data.types.pagination) {
            updatePagination('types', response.data.types.pagination);
          }
        }

        // تحديث جدول الأحجام
        if ((loadSize || tab === 'sizes' || tab === 'all') && response.data.sizes) {
          var sizesData =
            response.data.sizes && response.data.sizes.data ? response.data.sizes.data : response.data.sizes || [];
          var sizes = sizesData
            .map((size, index) => {
              // Calculate the correct index based on pagination
              let actualIndex =
                response.data.sizes && response.data.sizes.pagination
                  ? (response.data.sizes.pagination.current_page - 1) * response.data.sizes.pagination.per_page +
                    index +
                    1
                  : index + 1;

              return `
          <tr>
            <td class="text-center"><strong>${actualIndex}</strong></td>
            <td>
              <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-2">
                  <div class="avatar-initial bg-label-success rounded">
                    <i class="ti ti-car"></i>
                  </div>
                </div>
                <span class="fw-medium">${size.vehicle}</span>
              </div>
            </td>
            <td>
              <span class="badge bg-label-info">${size.type}</span>
            </td>
            <td>
              <div class="d-flex align-items-center">
                <i class="ti ti-ruler me-2 text-muted"></i>
                <span class="fw-medium">${size.name}</span>
              </div>
            </td>
            <td class="text-center">
              <button class="btn btn-action btn-sm btn-outline-primary edit-s-record"
                data-id="${size.id}" data-name="${size.name}" data-type="${size.type_id}" data-vehicle="${size.vehicle_id}"
                title="${__('Edit')}">
                <i class="ti ti-edit"></i>
              </button>
              <button class="btn btn-action btn-sm btn-outline-danger delete-s-record"
                data-id="${size.id}" data-name="${size.name}"
                title="${__('Delete')}">
                <i class="ti ti-trash"></i>
              </button>
            </td>
          </tr>`;
            })
            .join('');

          if (!sizesData || sizesData.length === 0) {
            sizes = `<tr>
                  <td colspan="5" class="text-center text-muted">
                    <div class="empty-state">
                      <i class="ti ti-dimensions-off"></i>
                      <h6>${__('No Vehicle Sizes Found')}</h6>
                      <p>${__('Add vehicle sizes to complete your fleet configuration')}</p>
                    </div>
                  </td>
                </tr>`;
          }
          $('#sizes-table').html(sizes);

          // Update pagination for sizes
          if (response.data.sizes && response.data.sizes.pagination) {
            updatePagination('sizes', response.data.sizes.pagination);
          }
        }

        // توليد القوائم المنسدلة - فقط في الصفحة الأولى أو عند تحميل جميع البيانات
        if (page == 1 || tab === 'all') {
          // استخدام all_vehicles للمركبات
          var vehiclesForDropdown = response.data.all_vehicles || [];
          var vehicle_options = ` <option value="">-- ${__('Select vehicle')} --</option>`;

          if (vehiclesForDropdown && vehiclesForDropdown.length > 0) {
            vehicle_options += vehiclesForDropdown
              .map(
                option => `
            <option value="${option.id}">${option.name} - ${option.en_name}</option>
          `
              )
              .join('');
          }

          if (lodeType) {
            $('.vehicle-type-vehicle').html(vehicle_options);
          }

          // استخدام البيانات المتاحة للأنواع - فقط إذا كانت متاحة
          if (response.data.types) {
            var typesForDropdown =
              response.data.types && response.data.types.data ? response.data.types.data : response.data.types || [];
            var vehicle_type_options = ` <option value="">-- ${__('select vehicle type')} --</option>`;

            if (typesForDropdown && typesForDropdown.length > 0) {
              vehicle_type_options += typesForDropdown
                .map(
                  option => `
              <option value="${option.id}"> ${option.name} - ${option.en_name}</option>
            `
                )
                .join('');
            }

            if (loadSize) {
              $('.vehicle-sizes-vehicle').html(vehicle_type_options);
            }
            $('.size-type-flitter').html(vehicle_type_options);
          }

          // استخدام البيانات المتاحة للأحجام - فقط إذا كانت متاحة
          if (response.data.sizes && loadSize) {
            var sizesForDropdown =
              response.data.sizes && response.data.sizes.data ? response.data.sizes.data : response.data.sizes || [];
            var vehicle_sizes_options = ` <option value="">-- ${__('select vehicle Size')} --</option>`;

            if (sizesForDropdown && sizesForDropdown.length > 0) {
              vehicle_sizes_options += sizesForDropdown
                .map(
                  size => `
              <option value="${size.id}"> ${size.name}</option>
            `
                )
                .join('');
            }

            if (loadSize) {
              $('#size-vehicle').html(vehicle_sizes_options);
            }
          }

          // تحديث القوائم الأساسية
          $('.vehicle-select').html(vehicle_options);
          $('.type-vehicle-flitter').html(vehicle_options);
          $('.size-vehicle-flitter').html(vehicle_options);
        }
      },
      error: function (xhr, status, error) {
        console.error('AJAX Error:', error);
        console.error('Status:', status);
        console.error('Response:', xhr.responseText);

        // Show user-friendly error message
        if (xhr.status === 404) {
          console.error('Route not found: admin/settings/vehicles/data');
        } else if (xhr.status === 500) {
          console.error('Server error occurred');
        } else {
          console.error('Request failed: ' + error);
        }
      }
    });
  }

  // Function to update statistics
  function updateStatistics(data) {
    // Use statistics data if available, otherwise fallback to array lengths
    if (data.statistics) {
      animateCounter('#vehicles-count', data.statistics.total_vehicles);
      animateCounter('#types-count', data.statistics.total_types);
      animateCounter('#sizes-count', data.statistics.total_sizes);
    } else {
      // Fallback for backward compatibility
      let vehiclesCount = data.vehicles ? (data.vehicles.data ? data.vehicles.data.length : data.vehicles.length) : 0;
      let typesCount = data.types ? (data.types.data ? data.types.data.length : data.types.length) : 0;
      let sizesCount = data.sizes ? (data.sizes.data ? data.sizes.data.length : data.sizes.length) : 0;

      animateCounter('#vehicles-count', vehiclesCount);
      animateCounter('#types-count', typesCount);
      animateCounter('#sizes-count', sizesCount);
    }
  }

  // Function to update pagination
  function updatePagination(type, paginationData) {
    console.log(`updatePagination called for ${type}:`, paginationData);
    const paginationContainer = $(`#${type}-pagination`);
    const paginationInfo = $(`#${type}-pagination-info`);
    const paginationWrapper = $(`#${type}-pagination-wrapper`);

    console.log(
      `Pagination elements found: container=${paginationContainer.length}, info=${paginationInfo.length}, wrapper=${paginationWrapper.length}`
    );

    if (!paginationData || paginationData.last_page <= 1) {
      paginationWrapper.addClass('d-none');
      return;
    }

    paginationWrapper.removeClass('d-none');

    // Update pagination info
    paginationInfo.text(
      `${__('Showing')} ${paginationData.from || 0} ${__('to')} ${paginationData.to || 0} ${__('of')} ${paginationData.total} ${__('entries')}`
    );

    // Generate pagination buttons
    let paginationHtml = '';

    // Previous button
    if (paginationData.current_page > 1) {
      paginationHtml += `<li class="page-item">
        <a class="page-link" href="#" data-page="${paginationData.current_page - 1}" data-type="${type}">
          <i class="ti ti-chevron-left"></i>
        </a>
      </li>`;
    } else {
      paginationHtml += `<li class="page-item disabled">
        <span class="page-link"><i class="ti ti-chevron-left"></i></span>
      </li>`;
    }

    // Page numbers
    let startPage = Math.max(1, paginationData.current_page - 2);
    let endPage = Math.min(paginationData.last_page, paginationData.current_page + 2);

    if (startPage > 1) {
      paginationHtml += `<li class="page-item">
        <a class="page-link" href="#" data-page="1" data-type="${type}">1</a>
      </li>`;
      if (startPage > 2) {
        paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      paginationHtml += `<li class="page-item ${i === paginationData.current_page ? 'active' : ''}">
        <a class="page-link" href="#" data-page="${i}" data-type="${type}">${i}</a>
      </li>`;
    }

    if (endPage < paginationData.last_page) {
      if (endPage < paginationData.last_page - 1) {
        paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
      }
      paginationHtml += `<li class="page-item">
        <a class="page-link" href="#" data-page="${paginationData.last_page}" data-type="${type}">${paginationData.last_page}</a>
      </li>`;
    }

    // Next button
    if (paginationData.current_page < paginationData.last_page) {
      paginationHtml += `<li class="page-item">
        <a class="page-link" href="#" data-page="${paginationData.current_page + 1}" data-type="${type}">
          <i class="ti ti-chevron-right"></i>
        </a>
      </li>`;
    } else {
      paginationHtml += `<li class="page-item disabled">
        <span class="page-link"><i class="ti ti-chevron-right"></i></span>
      </li>`;
    }

    console.log(`Setting pagination HTML for ${type}:`, paginationHtml.substring(0, 200) + '...');
    paginationContainer.html(paginationHtml);
  }

  // Function to animate counter
  function animateCounter(selector, targetValue) {
    const element = $(selector);
    const currentValue = parseInt(element.text()) || 0;

    if (currentValue !== targetValue) {
      $({ counter: currentValue }).animate(
        { counter: targetValue },
        {
          duration: 1000,
          easing: 'swing',
          step: function () {
            element.text(Math.ceil(this.counter));
          },
          complete: function () {
            element.text(targetValue);
          }
        }
      );
    }
  }

  // تحميل البيانات الأولي
  console.log('Initial data load...');
  loadData('', '', true, true, true, 'all', 1);

  // Pagination event handlers
  $(document).on('click', '.page-link', function (e) {
    e.preventDefault();
    const page = $(this).data('page');
    const type = $(this).data('type');

    console.log('Pagination clicked:', { page, type, element: this });

    if (page && type) {
      if (type === 'vehicles') {
        // تحديث الصفحة الحالية
        currentPage.vehicles = page;
        // تحميل المركبات فقط مع الصفحة المحددة
        loadData('', '', true, false, false, 'vehicles', page);
      } else if (type === 'types') {
        currentPage.types = page;
        const vehicle = $('#type-vehicle-flitter').val();
        loadData(vehicle, '', false, false, false, 'types', page);
      } else if (type === 'sizes') {
        currentPage.sizes = page;
        const vehicle = $('#size-vehicle-flitter').val();
        const typeFilter = $('#size-type-flitter').val();
        loadData(vehicle, typeFilter, false, false, true, 'sizes', page);
      }
    }
  });

  // Per page change handlers
  $(document).on('change', '#vehicles-per-page', function () {
    const newPerPage = parseInt($(this).val());
    console.log('Vehicles per page changing from', perPage.vehicles, 'to', newPerPage);
    perPage.vehicles = newPerPage;
    currentPage.vehicles = 1; // إعادة تعيين الصفحة إلى الأولى
    console.log('Loading vehicles data with new per page setting...');
    loadData('', '', true, false, false, 'vehicles', 1);
  });

  $(document).on('change', '#types-per-page', function () {
    perPage.types = parseInt($(this).val());
    currentPage.types = 1;
    const vehicle = $('#type-vehicle-flitter').val();
    console.log('Types per page changed to:', perPage.types);
    loadData(vehicle, '', false, true, false, 'types', 1);
  });

  $(document).on('change', '#sizes-per-page', function () {
    perPage.sizes = parseInt($(this).val());
    currentPage.sizes = 1;
    const vehicle = $('#size-vehicle-flitter').val();
    const typeFilter = $('#size-type-flitter').val();
    console.log('Sizes per page changed to:', perPage.sizes);
    loadData(vehicle, typeFilter, false, false, true, 'sizes', 1);
  });

  $(document).on('change', '#type-vehicle-flitter', function () {
    var vehicle = $(this).val();
    currentPage.types = 1; // Reset to first page when filtering
    loadData(vehicle, '', false, false, false, 'types', 1);
  });

  $(document).on('change', '#vehicle-size-vehicle', function () {
    var vehicle = $(this).val();
    loadData(vehicle, '', false, false);
  });

  $(document).on('change', '#size-vehicle-flitter', function () {
    var vehicle = $(this).val();
    currentPage.sizes = 1; // Reset to first page when filtering
    loadData(vehicle, '', false, false, true, 'sizes', 1);
  });

  $(document).on('change', '#size-type-flitter', function () {
    var typeFilter = $(this).val();
    var vehicle = $('#size-vehicle-flitter').val();
    currentPage.sizes = 1; // Reset to first page when filtering
    loadData(vehicle, typeFilter, false, false, true, 'sizes', 1);
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
