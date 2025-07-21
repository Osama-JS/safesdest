/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, generateFields, handleErrors, showBlockAlert, showFormModal } from '../ajax';
import { setupMethodSelection } from '../admin/tasks/tasks';
import { mapsConfig } from '../mapbox-helper';

// Datatable (jquery)
$(function () {
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

  /* ===========  MapBox  accessToken   ===========*/

  mapboxgl.accessToken = mapsConfig.token;

  /* ===========  ajax setup   ===========*/
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
  mapboxgl.setRTLTextPlugin(
    'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-rtl-text/v0.2.3/mapbox-gl-rtl-text.js',
    null,
    true // تحميل فقط عند الحاجة (lazy load)
  );

  /* ================  Enhanced Render Tasks with Filters and Pagination   =============== */
  let currentPage = 1;
  let currentFilters = {
    search: '',
    status: '',
    date: ''
  };

  function loadTasks(page = 1, filters = {}) {
    // Show loading state
    $('#tasks-container').html(`
      <div class="loading-state">
        <div class="loading-spinner"></div>
        <p>جاري تحميل المهام...</p>
      </div>
    `);

    // Clear pagination
    $('#pagination-container').html('');

    $.ajax({
      url: baseUrl + 'customer/tasks/get/tasks',
      type: 'GET',
      data: {
        page: page,
        per_page: 6,
        search: filters.search || '',
        status: filters.status || '',
        date: filters.date || ''
      },
      success: function (response) {
        $('#tasks-container').html('');

        if (!response.data || response.data.length === 0) {
          $('#tasks-container').html(`
            <div class="empty-state">
              <div class="empty-state-icon">
                <i class="ti ti-clipboard-off"></i>
              </div>
              <h5>No Tasks Found</h5>
              <p class="text-muted">No tasks found matching your search criteria</p>
            </div>
          `);
          return;
        }

        console.log(response);

        response.data.forEach(task => {
          const driverInfo = task.driver
            ? `
               <div class="driver-info-section">
                 <div class="divider text-start mb-3">
                   <div class="divider-text"><strong><i class="ti ti-user me-2"></i>Driver Information</strong></div>
                 </div>
                 <div class="d-flex align-items-center">
                   <img src="${baseUrl}${task.driver.image || 'assets/img/person.png'}"
                       alt="Driver Image"
                       class="driver-avatar me-3">
                   <div class="flex-grow-1">
                     <ul class="list-unstyled mb-0">
                       <li class="mb-2"><strong>Name:</strong> ${task.driver.name}</li>
                       <li class="mb-2"><strong>Phone:</strong> ${task.driver.phone}</li>
                       <li class="mb-2"><strong>Email:</strong> ${task.driver.email}</li>
                     </ul>
                   </div>
                   <div class="ms-3">
                     <a href="https://wa.me/${task.driver.phone.replace(/[^0-9]/g, '')}?text=Hello%20${encodeURIComponent(task.driver.name)},%20I%20am%20contacting%20you%20regarding%20Task%20%23${task.id}"
                        target="_blank"
                        class="btn btn-success btn-sm">
                       <i class="ti ti-brand-whatsapp me-1"></i>
                       WhatsApp
                     </a>
                   </div>
                 </div>
               </div>
            `
            : '';

          // Get status class for styling
          const statusClass = getStatusClass(task.status);
          const statusText = getStatusText(task.status);

          let taskCard = `
              <div class="task-card fade-in">
                  <div class="task-card-header">
                      <div class="d-flex justify-content-between align-items-center flex-wrap">
                          <div class="task-id">#${task.id}</div>
                          <div class="d-flex align-items-center gap-3">
                             <div class="task-price">${task.total_price} ريال</div>
                             <div class="task-status ${statusClass}">${statusText}</div>
                             <div class="dropdown">
                              <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                <i class="ti ti-dots-vertical"></i>
                              </button>
                              <div class="dropdown-menu dropdown-menu-end">
                                  <a href="javascript:;" class="dropdown-item edit-task" data-id="${task.id}">
                                    <i class="ti ti-edit me-2"></i>Edit Task
                                  </a>
                                  ${
                                    !task.closed
                                      ? `
                                    <a href="${baseUrl}customer/tasks/tracking/${task.id}" target="_blank" class="dropdown-item">
                                      <i class="ti ti-map-pin me-2"></i>Track Task
                                    </a>
                                  `
                                      : ''
                                  }
                                  <a href="${baseUrl + 'customer/tasks/details/' + task.id}" class="dropdown-item">
                                    <i class="ti ti-eye me-2"></i>View Details
                                  </a>
                              </div>
                             </div>
                          </div>
                      </div>
                  </div>

                  <div class="p-4">
                    <div class="row">
                      <div class="col-md-6 mb-3">
                          <div class="location-card pickup">
                              <h6 class="location-title pickup d-flex align-items-center justify-content-between">
                                  <span>
                                      <i class="ti ti-map-pin me-2"></i>Pickup Point
                                  </span>
                                  <a href="https://www.google.com/maps?q=${task.pickup.latitude},${task.pickup.longitude}"
                                      target="_blank" class="maps-btn">
                                      <i class="ti ti-external-link me-1"></i>Map
                                  </a>
                              </h6>
                              <ul class="list-unstyled mb-0">
                                  <li class="mb-2"><strong>Address:</strong> ${task.pickup.address}</li>
                                  <li class="mb-2"><strong>Contact Name:</strong> ${task.pickup.contact_name}</li>
                                  <li><strong>Phone:</strong> ${task.pickup.contact_phone}</li>
                              </ul>
                          </div>
                      </div>
                      <div class="col-md-6 mb-3">
                          <div class="location-card delivery">
                              <h6 class="location-title delivery d-flex align-items-center justify-content-between">
                                  <span>
                                      <i class="ti ti-truck me-2"></i>Delivery Point
                                  </span>
                                  <a href="https://www.google.com/maps?q=${task.delivery.latitude},${task.delivery.longitude}"
                                      target="_blank" class="maps-btn">
                                      <i class="ti ti-external-link me-1"></i>Map
                                  </a>
                              </h6>
                              <ul class="list-unstyled mb-0">
                                  <li class="mb-2"><strong>Address:</strong> ${task.delivery.address}</li>
                                  <li class="mb-2"><strong>Contact Name:</strong> ${task.delivery.contact_name}</li>
                                  <li><strong>Phone:</strong> ${task.delivery.contact_phone}</li>
                              </ul>
                          </div>
                      </div>
                    </div>
                    ${driverInfo}
                  </div>
              </div>
          `;
          $('#tasks-container').append(taskCard);
        });

        // Render pagination if available
        if (response.pagination) {
          renderPagination(response.pagination);
        }
      },
      error: function () {
        $('#tasks-container').html(`
          <div class="empty-state">
            <div class="empty-state-icon">
              <i class="ti ti-alert-circle"></i>
            </div>
            <h5>Loading Error</h5>
            <p class="text-muted">An error occurred while loading tasks. Please try again.</p>
            <button class="btn btn-primary" onclick="loadTasks()">Retry</button>
          </div>
        `);
      }
    });
  }

  // Helper functions for status styling
  function getStatusClass(status) {
    const statusClasses = {
      completed: 'completed',
      in_progress: 'in_progress',
      pending: 'pending',
      canceled: 'canceled'
    };
    return statusClasses[status] || 'pending';
  }

  function getStatusText(status) {
    const statusTexts = {
      completed: 'Completed',
      in_progress: 'In Progress',
      pending: 'Pending',
      canceled: 'Canceled'
    };
    return statusTexts[status] || status;
  }

  // Render pagination
  function renderPagination(pagination) {
    if (pagination.last_page <= 1) return;

    let paginationHtml = `
      <div class="pagination-wrapper">
        <div class="d-flex justify-content-between align-items-center">
          <div class="pagination-info">
            <span class="text-muted">
              Showing ${pagination.from} to ${pagination.to} of ${pagination.total} tasks
            </span>
          </div>
          <nav>
            <ul class="pagination mb-0">
    `;

    // Previous button
    if (pagination.current_page > 1) {
      paginationHtml += `
        <li class="page-item">
          <a class="page-link" href="#" data-page="${pagination.current_page - 1}">
            <i class="ti ti-chevron-right"></i>
          </a>
        </li>
      `;
    }

    // Page numbers
    for (
      let i = Math.max(1, pagination.current_page - 2);
      i <= Math.min(pagination.last_page, pagination.current_page + 2);
      i++
    ) {
      paginationHtml += `
        <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
          <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>
      `;
    }

    // Next button
    if (pagination.current_page < pagination.last_page) {
      paginationHtml += `
        <li class="page-item">
          <a class="page-link" href="#" data-page="${pagination.current_page + 1}">
            <i class="ti ti-chevron-left"></i>
          </a>
        </li>
      `;
    }

    paginationHtml += `
            </ul>
          </nav>
        </div>
      </div>
    `;

    $('#pagination-container').html(paginationHtml);
  }

  // Filter functionality
  $('#apply-filters').on('click', function () {
    currentFilters = {
      search: $('#search-tasks').val(),
      status: $('#filter-status').val(),
      date: $('#filter-date').val()
    };
    currentPage = 1;
    loadTasks(currentPage, currentFilters);
  });

  $('#clear-filters').on('click', function () {
    $('#search-tasks').val('');
    $('#filter-status').val('');
    $('#filter-date').val('');
    currentFilters = { search: '', status: '', date: '' };
    currentPage = 1;
    loadTasks(currentPage, currentFilters);
  });

  // Search on Enter key
  $('#search-tasks').on('keypress', function (e) {
    if (e.which === 13) {
      $('#apply-filters').click();
    }
  });

  // Pagination click handler
  $(document).on('click', '.pagination .page-link', function (e) {
    e.preventDefault();
    const page = $(this).data('page');
    if (page) {
      currentPage = page;
      loadTasks(currentPage, currentFilters);
    }
  });

  // Initialize tasks loading
  loadTasks();

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
        $.get(`${baseUrl}chosen/vehicles/types/${vehicleId}`, function (types) {
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
        $.get(`${baseUrl}chosen/vehicles/sizes/${typeId}`, function (sizes) {
          $sizeSelect.empty().append('<option value="">Select a vehicle size</option>');
          sizes.forEach(size => {
            $sizeSelect.append(`<option value="${size.id}">${size.name}</option>`);
          });
          $sizeSelect.prop('disabled', false);
        });
      }
    });
  }

  const $newRow = $(createVehicleRow(vehicleIndex++));
  $('#vehicle-selection-container').append($newRow);
  updateVehicleRowEvents($newRow);

  /* ===========  Set pickup and delivery Points Map   ===========*/
  function setupMapboxLocationHandlers(prefix) {
    const map = new mapboxgl.Map({
      container: `${prefix}-map`,
      style: 'mapbox://styles/' + mapsConfig.style,
      center: mapsConfig.center,
      zoom: 10
    });

    let marker;
    let selectedCoords = null;

    // get coordinates   by searching address
    const geocoder = new MapboxGeocoder({
      accessToken: mapboxgl.accessToken,
      mapboxgl: mapboxgl,
      placeholder: 'Search for the location...',
      marker: false,
      flyTo: false
    });

    // add the geocoder to html dev
    geocoder.addTo(`#${prefix}-geocoder`);
    $(`#${prefix}-geocoder .mapboxgl-ctrl-geocoder`).css('width', '100%');
    geocoder.on('result', function (e) {
      const coords = e.result.geometry.coordinates;
      const placeName = e.result.place_name;

      $(`#${prefix}-address`).val(placeName);
      selectedCoords = coords;
      showMap(coords);
    });

    $(`#${prefix}-parse-link`).on('click', function () {
      console.log('google');
      const link = $(`#${prefix}-map-link`).val().trim();
      const coords = extractCoordinatesFromLink(link);

      if (coords) {
        selectedCoords = coords;
        showMap(coords);
      } else {
        showAlert('error', 'تعذر استخراج الإحداثيات من الرابط', 3000, true);
      }
    });

    // get coordinates by manual using the map
    $(`#${prefix}-manual-btn`).on('click', function () {
      showMap();
      map.once('click', function (e) {
        const lng = e.lngLat.lng;
        const lat = e.lngLat.lat;

        selectedCoords = [lng, lat];
        updateMarker([lng, lat]);
      });
    });

    // get coordinates by Current locations using jps on the map
    $(`#${prefix}-getCurrentLocation`).on('click', function () {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;

          selectedCoords = [lng, lat];
          showMap([lng, lat]);
        });
      } else {
        showAlert('error', 'the Browser dose not support th GPS', 3000, true);
      }
    });

    // add the point coordinates to the field direct
    $(`#${prefix}-latitude, #${prefix}-longitude`).on('input', updateFromManualCoords);
    function updateFromManualCoords() {
      const lat = parseFloat($(`#${prefix}-latitude`).val());
      const lng = parseFloat($(`#${prefix}-longitude`).val());

      if (!isNaN(lat) && !isNaN(lng)) {
        selectedCoords = [lng, lat];
        showMap([lng, lat]);
      }
    }

    // Confirm coordinates button
    $(`#${prefix}-confirm-location`).on('click', function () {
      if (selectedCoords) {
        $(`#${prefix}-latitude`).val(selectedCoords[1]);
        $(`#${prefix}-longitude`).val(selectedCoords[0]);

        // ✅ Store coordinates in global variables
        if (prefix === 'pickup') {
          pickupCoords = selectedCoords;
        } else if (prefix === 'delivery') {
          deliveryCoords = selectedCoords;
        }

        console.log('pickupCoords', pickupCoords);
        console.log('deliveryCoords', deliveryCoords);
        // ✅ If the two points are given, draw the path.
        if (pickupCoords && deliveryCoords) {
          updatePreviewRoute(pickupCoords, deliveryCoords);
        }

        setTimeout(() => {
          $(`#${prefix}-map`).hide();
          $(`#${prefix}-confirm-location`).hide();
          $(`#${prefix}-map-container`).hide();
        }, 1000);
      }
    });

    function showMap(coords = [46.6753, 24.7136]) {
      $(`#${prefix}-map`).show();
      $(`#${prefix}-confirm-location`).show();
      $(`#${prefix}-map-container`).show();

      map.resize();
      map.flyTo({ center: coords, zoom: 14 });

      updateMarker(coords);
    }

    function updateMarker(coords) {
      if (marker) marker.remove();
      marker = new mapboxgl.Marker({ draggable: true }).setLngLat(coords).addTo(map);

      marker.on('dragend', function () {
        const lngLat = marker.getLngLat();
        selectedCoords = [lngLat.lng, lngLat.lat];
      });
    }
  }

  function extractCoordinatesFromLink(link) {
    // 1. regex to match lat,lng in URL
    const regex = /([-+]?\d{1,3}(?:\.\d+)?),\s*([-+]?\d{1,3}(?:\.\d+)?)/;

    const match = link.match(regex);
    if (match) {
      const lat = parseFloat(match[1]);
      const lng = parseFloat(match[2]);

      if (!isNaN(lat) && !isNaN(lng)) {
        return [lng, lat]; // Mapbox expects [lng, lat]
      }
    }

    return null;
  }

  $('#pickup-toggle-link-input').on('click', function () {
    $('#pickup-link-input-wrapper').slideToggle();
  });

  $('#delivery-toggle-link-input').on('click', function () {
    $('#delivery-link-input-wrapper').slideToggle();
  });

  setupMapboxLocationHandlers('pickup');
  setupMapboxLocationHandlers('delivery');

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

    // Reload tasks with current filters
    loadTasks(currentPage, currentFilters);
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

  $('#go-to-step2').on('click', function () {
    $('#task-form').block({
      message:
        '<div class="d-flex justify-content-center"><p class="mb-0">Please wait...</p> <div class="sk-wave m-0"><div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div></div> </div>',
      css: {
        backgroundColor: 'transparent',
        color: '#fff',
        border: '0'
      },
      overlayCSS: {
        opacity: 0.5
      }
    });
    $.ajax({
      url: baseUrl + 'customer/tasks/validate-step1',
      method: 'POST',
      data: new FormData($('#task-form')[0]),
      processData: false,
      contentType: false,

      success: function (data) {
        $('span.text-error').text(''); // إعادة تعيين الأخطاء
        $('#task-form').unblock({
          onUnblock: function () {
            if (data.status == 0) {
              showAlert('error', 'يجب عليك التأكد من جميع البيانات المدخلة', 10000, true);
              console.log(data.error);
              handleErrors(data.error);
              showBlockAlert('warning', 'حدث خطأ أثناء الإرسال!');
            } else if (data.status == 1) {
              const select = $('#pricing-method-select');
              select.empty();
              // قسم للديناميكي
              select.append(`<option value="" data-distance="">--- Select Pricing Method</option>`);

              select.append('<optgroup label="Dynamic Pricing">');

              $.each(data.data, function (index, method) {
                select.append(`<option value="${method.id}" data-distance="${method.type}">${method.name}</option>`);
              });
              select.append('</optgroup>');
              select.append('<optgroup label="Manual pricing">');
              select.append(`<option value="0" data-distance="manual">Place your offer</option>`);
              select.append('</optgroup>');

              $('span.text-error').text('');
              // تنفيذ دالة الإعداد بعد نجاح الطلب
              $('#params-select-wrapper').remove();
              setupMethodSelection(data.data);
              if ($('#task-id').val() !== '') {
                select.val($('#task-id').attr('data-method')).trigger('change');
              }
              new bootstrap.Tab(document.querySelector('#tab-step2')).show();
            } else {
              showAlert('error', data.error, 10000, true);
            }
            console.log(data.data);
          }
        });
      },
      error: function (xhr) {
        $('#task-form').unblock({
          onUnblock: function () {
            const errors = xhr.responseJSON.errors;
            $('.text-error').text('');

            for (const field in errors) {
              $(`.${field}-error`).text(errors[field][0]);
            }

            showAlert('error', 'يرجى تصحيح الأخطاء', 10000, true);
          }
        });
      }
    });
  });

  $('#go-to-step3').on('click', function () {
    $('#task-form').block({
      message:
        '<div class="d-flex justify-content-center"><p class="mb-0">Please wait...</p> <div class="sk-wave m-0"><div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div></div> </div>',
      css: {
        backgroundColor: 'transparent',
        color: '#fff',
        border: '0'
      },
      overlayCSS: {
        opacity: 0.5
      }
    });
    $.ajax({
      url: baseUrl + 'customer/tasks/validate-step2',
      method: 'POST',
      data: new FormData($('#task-form')[0]),
      processData: false,
      contentType: false,

      success: function (data) {
        $('span.text-error').text(''); // إعادة تعيين الأخطاء

        $('#task-form').unblock({
          onUnblock: function () {
            if (data.status == 0) {
              console.log(data.error);
              showAlert('error', 'يجب عليك التأكد من جميع البيانات المدخلة', 10000, true);
              handleErrors(data.error);
              showBlockAlert('warning', 'حدث خطأ أثناء الإرسال!');
            } else if (data.status == 1) {
              renderPricingDetails(data.data);

              new bootstrap.Tab(document.querySelector('#tab-step3')).show();
            } else {
              showAlert('error', data.error, 10000, true);
            }
          }
        });
      },
      error: function (xhr) {
        $('#task-form').unblock({
          onUnblock: function () {
            const errors = xhr.responseJSON.errors;
            $('.text-error').text('');

            for (const field in errors) {
              $(`.${field}-error`).text(errors[field][0]);
            }
            showAlert('error', 'يرجى تصحيح الأخطاء قبل المتابعة', 10000, true);
          }
        });
      }
    });
  });

  function renderPricingDetails(data) {
    console.log(data);
    $('#assign-section').hide();

    let html = `
    <div class="card p-4 shadow-sm rounded-3" style="font-family: Arial, sans-serif;">
      <h2 class="mb-4 text-center">تفاصيل التسعير</h2>
  `;

    if (data.pricing_method) {
      html += `<div class="mb-2"><strong>Pricing Method:</strong> ${data.pricing_method}</div>`;
    }

    if (data.distance) {
      html += `<div class="mb-2"><strong>Total distance:</strong> ${parseFloat(data.distance).toFixed(2)} كم</div>`;
    }

    if (data.distance_price_kilo) {
      html += `<div class="mb-2"><strong>Price per kilo:</strong> ${parseFloat(data.distance_price_kilo).toFixed(2)} ريال</div>`;
    }

    if (data.distance_price) {
      html += `<div class="mb-2"><strong>Distance Total price:</strong> ${parseFloat(data.distance_price).toFixed(2)} ريال</div>`;
    }

    if (data.service_tax_commission) {
      html += `<div class="mb-2"><strong>Service commission:</strong> ${parseFloat(data.service_tax_commission).toFixed(2)} %</div>`;
    }

    if (data.discount_percentage) {
      html += `<div class="mb-2"><strong>Discount Percentage:</strong> ${parseFloat(data.discount_percentage).toFixed(2)} %</div>`;
    }

    if (data.vat_commission) {
      html += `<div class="mb-2"><strong>VAT commission:</strong> ${parseFloat(data.vat_commission).toFixed(2)} %</div>`;
    }

    if (data.points) {
      html += `<div class="mb-2"><strong>Points:</strong> ${data.points}</div>`;
    }
    if (data.vehicles) {
      html += `<div class="mb-2 alert alert-info"><strong>Nte:</strong> ${data.vehicles}</div>`;
    }

    if (data.fields) {
      html += `
    <div class="mb-3"><strong>Fields:</strong><ul class="list-group">
  `;

      const fieldsArray = Array.isArray(data.fields) ? data.fields : [data.fields];

      fieldsArray.forEach(field => {
        html += `
      <li class="list-group-item">
        <strong>${field.name || ''}:</strong> ${field.value || ''}
        (زيادة: ${parseFloat(field.increase || 0).toFixed(2)} ريال)
      </li>`;
      });

      html += `</ul></div>`;
    }

    if (data.manual) {
      html += `<div class="mb-2">
      <h4>Place your offer</h4>
      <div class="alert alert-info mb-3 d-flex flex-column" role="alert" style="max-width: 600px;">
        <div class="form-check mb-2">
          <input type="checkbox"
                name="included"
                id="included-price"
                class="form-check-input"
                value="1"
                ${$('#task-id').data('included') ? 'checked' : ''}>
          <label class="form-check-label fw-bold" for="not-price">
            Including VAT and service charge
          </label>
        </div>

        <p class="small text-muted">
              If you do not select this option, both the VAT and the service commission will be calculated on top of the amount you display.
        </p>
         <p class="small text-muted">
          This means the following will be added on top of the price you enter:
          ${data.service_tax_commission ? parseFloat(data.service_tax_commission).toFixed(2) + (data.service_commission_type === 'percentage' ? '% service commission' : ' SAR service commission') : ''}
          ${data.vat_commission ? parseFloat(data.vat_commission).toFixed(2) + '% VAT (Value Added Tax)' : ''}
        </p>


        <span class="included-error text-danger mt-2"></span>
      </div>
      <div class="mb-3 row">
        <div class="col-md-6">
          <label for="min-price">* Min Price</label>
          <input type="number" name="min_price"  id="min-price"  class="form-control" step="any" value="${$('#task-id').data('min')}" >
          <span class="min_price-error text-danger text-error"></span>
        </div>
         <div class="col-md-6">
          <label for="max-price">* Max Price</label>
          <input type="number" name="max_price" id="max-price"  class="form-control" step="any" value="${$('#task-id').data('max')}" >
          <span class="max_price-error text-danger text-error"></span>
        </div>
      </div>
      <div class="mb-3">
          <label for="not-price">Note</label>
          <textarea name="note_price" id="not-price" class="form-control">${$('#task-id').data('note')}</textarea>
          <span class="note_price-error text-danger text-error"></span>
      </div>
    </div>`;
    }

    if (Array.isArray(data.geo_fence) && data.geo_fence.length > 0) {
      html += `
      <div class="mb-3"><strong>Geo Fence:</strong><ul class="list-group">
    `;
      data.geo_fence.forEach(g => {
        html += `
        <li class="list-group-item">
          <strong>${g.name || ''}</strong> (زيادة: ${parseFloat(g.increase || 0).toFixed(2)} ريال)
        </li>`;
      });
      html += `</ul></div>`;
    }

    html += `<hr>`;

    if (data.total_price) {
      html += `
      <div class="text-center">
        <h3>الإجمالي النهائي: ${parseFloat(data.total_price).toFixed(2)} ريال</h3>
      </div>
    `;
      $('#assign-section').show();
      $('#total-price').attr('placeholder', parseFloat(data.total_price).toFixed(2));
    }

    html += `</div>`;
    document.getElementById('taskFinalDetails').innerHTML = html;
  }

  function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  $(document).on('click', '.edit-task', function () {
    var taskId = $(this).data('id');

    $.get(`${baseUrl}customer/tasks/edit/${taskId}`, async function (data) {
      if (data.status === 2) {
        showAlert('error', data.error);
        return;
      }
      $('#task-form').attr('action', `${baseUrl}customer/tasks/edit`);

      $('#modelTitle').html(`Edit Task: <span class="bg-info text-white px-2 rounded">#${taskId}</span>`);
      // get data
      $('#task-id').val(data.id);
      $('#task-owner').val(data.owner).trigger('change');
      $('#task-customer').val(data.customer_id).trigger('change');
      $('.vehicle-quantity').hide();
      $('.vehicle-select').val(data.vehicle).trigger('change');
      $('#submitModal').modal('show');

      await delay(1000);
      $('.vehicle-type-select').val(data.vehicle_type).trigger('change');

      await delay(1000);
      $('.vehicle-size-select').val(data.vehicle_size_id).trigger('change');

      $('#additional-form').html('');
      $('#select-template').val(data.form_template_id);

      if (data.form_template_id === null) {
        $('#select-template').val(templateId).trigger('change');
      }
      generateFields(data.fields, data.additional_data);

      $('#task-id').attr('data-method', data.pricing_history.pricing_method_id);
      $('#task-id').attr('data-point', data.pricing_history.point_id);

      if (data.pricing_history.pricing_method_id == 0) {
        $('#task-id').attr('data-min', data.ad.lowest_price || 0.0);
        $('#task-id').attr('data-max', data.ad.highest_price || 0.0);
        $('#task-id').attr('data-note', data.ad.description || '');
        $('#task-id').attr('data-included', data.ad.included || false);
      }

      $('#pickup-contact-name').val(data.pickup.contact_name);
      $('#pickup-contact-phone').val(data.pickup.contact_phone);
      $('#pickup-contact-email').val(data.pickup.contact_emil);
      $('#pickup-before').val(data.pickup.scheduled_time);
      $('#pickup-address').val(data.pickup.address);
      $('#pickup-longitude').val(data.pickup.longitude);
      $('#pickup-latitude').val(data.pickup.latitude);
      $('#pickup-note').val(data.pickup.note);

      $('#delivery-contact-name').val(data.delivery.contact_name);
      $('#delivery-contact-phone').val(data.delivery.contact_phone);
      $('#delivery-contact-email').val(data.delivery.contact_emil);
      $('#delivery-before').val(data.delivery.scheduled_time);
      $('#delivery-address').val(data.delivery.address);
      $('#delivery-longitude').val(data.delivery.longitude);
      $('#delivery-latitude').val(data.delivery.latitude);
      $('#delivery-note').val(data.delivery.note);

      renderPricingDetails(data.pricing_details);
    });
  });

  $('#submitModal, #assignModal, #adModal, #pricingModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    new bootstrap.Tab(document.querySelector('#tab-step1')).show();
    $('#taskFinalDetails').html('');
    $('#params-select-wrapper').remove();
    $('.text-error').html('');
    $('#task_id').val('');
    $('.task-priceing-hint').remove();
    $('#pricing-details-container').html('');
    $('.vehicle-select').val('').trigger('change');
    $('#modelTitle').html('Add New Tasks');
    Detailsindex = 0;
  });
});

$(document).on('click', '.task_type_template', function () {
  const type = $(this).data('template');
  $('#additional-form').html('');
  switch (type) {
    case 'normal':
      generateFields(taskTemplate);
      break;
    case 'task_from':
      generateFields(taskTemplateFrom);
      break;
    case 'task_to':
      generateFields(taskTemplateTo);
      break;
    default:
      generateFields(taskTemplate);
      break;
  }
  $('#taskTypeModal').modal('hide');
  $('#submitModal').modal('show');
});
generateFields(taskTemplate);
