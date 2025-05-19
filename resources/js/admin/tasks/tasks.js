/**
 * Page Task List
 */

'use strict';
import { deleteRecord, showAlert, showFormModal, generateFields, handleErrors, showBlockAlert } from '../../ajax';
import { mapsConfig } from '../../mapbox-helper';

$(function () {
  let pointIndex = 0;
  if (templateId != null) {
    console.log(templateId);
    $('#select-template').val(templateId).trigger('change');
  }
  /* ===========  MapBox  accessToken   ===========*/

  mapboxgl.accessToken = mapsConfig.token;

  console.log('access token: ', mapboxgl.accessToken);

  /* ===========  ajax setup   ===========*/
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  /* ===========  Chose Task owner Code   ===========*/
  $('#task-owner').on('change', function () {
    if ($(this).val() === 'customer') {
      $('#customers-wrapper').show();
    } else {
      $('#customers-wrapper').hide();
      $('#task-customer').val('');
    }
  });

  /* ===========  PreviewMap Code   ===========*/

  let blockedPoints = [];
  let blockedLines = [];

  async function loadBlockages() {
    try {
      const response = await fetch(`${baseUrl}admin/settings/blockages/get`); // <-- تأكد أن هذا هو الـ endpoint الصحيح
      const data = await response.json();
      console.log(data);

      blockedPoints = data.points || [];
      blockedLines = data.lines || [];
    } catch (error) {
      console.error('فشل تحميل الإغلاقات:', error);
    }
  }

  mapboxgl.setRTLTextPlugin(
    'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-rtl-text/v0.2.3/mapbox-gl-rtl-text.js',
    null,
    true // تحميل فقط عند الحاجة (lazy load)
  );

  const previewMap = new mapboxgl.Map({
    container: `preview-map`,
    style: 'mapbox://styles/' + mapsConfig.style,
    center: mapsConfig.center,
    zoom: 10
  });

  $('#submitModal').on('shown.bs.modal', function () {
    previewMap.resize();
  });

  let pickupMarker = null;
  let deliveryMarker = null;
  let routeLine = null;

  let pickupCoords = null;
  let deliveryCoords = null;

  // 🔍 دالة للتحقق هل نقطة قرب نقطة مغلقة
  function isNearPoint(coord, point, threshold = 0.0003) {
    const dx = coord[0] - point.lng;
    const dy = coord[1] - point.lat;
    return Math.sqrt(dx * dx + dy * dy) < threshold;
  }

  // 🔍 دالة للتحقق هل مسار يقطع خط مغلق (تقريبياً)
  function isNearLine(coord, line, threshold = 0.0003) {
    for (let i = 0; i < line.coordinates.length - 1; i++) {
      const [x1, y1] = line.coordinates[i];
      const [x2, y2] = line.coordinates[i + 1];

      const A = coord[0] - x1;
      const B = coord[1] - y1;
      const C = x2 - x1;
      const D = y2 - y1;

      const dot = A * C + B * D;
      const len_sq = C * C + D * D;
      const param = len_sq !== 0 ? dot / len_sq : -1;

      let xx, yy;
      if (param < 0) {
        xx = x1;
        yy = y1;
      } else if (param > 1) {
        xx = x2;
        yy = y2;
      } else {
        xx = x1 + param * C;
        yy = y1 + param * D;
      }

      const dx = coord[0] - xx;
      const dy = coord[1] - yy;
      const dist = Math.sqrt(dx * dx + dy * dy);

      if (dist < threshold) return true;
    }
    return false;
  }

  // 🔍 تحقق شامل
  function isRouteBlocked(route) {
    return route.geometry.coordinates.some(
      coord =>
        blockedPoints.some(point => isNearPoint(coord, point)) || blockedLines.some(line => isNearLine(coord, line))
    );
  }

  // 🧠 تحديث وعرض المسار
  async function updatePreviewRoute(pickupCoords, deliveryCoords) {
    console.log('updatePreviewRoute', pickupCoords, deliveryCoords);

    if (pickupMarker) pickupMarker.remove();
    if (deliveryMarker) deliveryMarker.remove();
    if (previewMap.getLayer('route-line')) previewMap.removeLayer('route-line');
    if (previewMap.getSource('route')) previewMap.removeSource('route');
    if (previewMap.getLayer('blocked-roads')) previewMap.removeLayer('blocked-roads');
    if (previewMap.getSource('blocked-roads')) previewMap.removeSource('blocked-roads');
    if (previewMap.getLayer('blocked-lines')) previewMap.removeLayer('blocked-lines');
    if (previewMap.getSource('blocked-lines')) previewMap.removeSource('blocked-lines');

    pickupMarker = new mapboxgl.Marker({ color: 'green' })

      .setLngLat(pickupCoords)
      .setPopup(new mapboxgl.Popup().setText('Pickup Point'))
      .addTo(previewMap);

    deliveryMarker = new mapboxgl.Marker({ color: 'red' })
      .setLngLat(deliveryCoords)
      .setPopup(new mapboxgl.Popup().setText('Delivery Point'))
      .addTo(previewMap);

    await loadBlockages(); // 📥 تحميل الإغلاقات من قاعدة البيانات

    async function fetchRouteWithRetry(pickup, delivery) {
      const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${pickup.join(',')};${delivery.join(',')}?geometries=geojson&steps=true&overview=full&access_token=${mapboxgl.accessToken}`;
      const res = await fetch(url);
      const data = await res.json();

      if (!data.routes || data.routes.length === 0) {
        throw new Error('No route found');
      }

      const route = data.routes[0];
      if (isRouteBlocked(route)) {
        console.warn('🚫 المسار يمر بمكان مغلق!');
        throw new Error('Route blocked');
      }

      return route;
    }

    let finalRoute = null;
    let attempts = 0;

    while (!finalRoute && attempts < 3) {
      try {
        finalRoute = await fetchRouteWithRetry(pickupCoords, deliveryCoords);
      } catch (e) {
        attempts++;
        if (attempts === 3) {
          alert('🚫 لا يمكن العثور على مسار صالح بدون المرور بمكان مغلق');
          return;
        }
      }
    }

    function addLayers() {
      previewMap.addSource('route', {
        type: 'geojson',
        data: {
          type: 'Feature',
          geometry: finalRoute.geometry
        }
      });

      previewMap.addLayer({
        id: 'route-line',
        type: 'line',
        source: 'route',
        layout: { 'line-join': 'round', 'line-cap': 'round' },
        paint: { 'line-color': '#007cbf', 'line-width': 4 }
      });

      // 🎯 إضافة النقاط المغلقة
      previewMap.addSource('blocked-roads', {
        type: 'geojson',
        data: {
          type: 'FeatureCollection',
          features: blockedPoints.map(point => ({
            type: 'Feature',
            geometry: { type: 'Point', coordinates: [point.lng, point.lat] }
          }))
        }
      });

      previewMap.addLayer({
        id: 'blocked-roads',
        type: 'circle',
        source: 'blocked-roads',
        paint: {
          'circle-radius': 8,
          'circle-color': '#ff0000',
          'circle-stroke-width': 2,
          'circle-stroke-color': '#ffffff'
        }
      });

      // 🎯 إضافة الخطوط المغلقة
      previewMap.addSource('blocked-lines', {
        type: 'geojson',
        data: {
          type: 'FeatureCollection',
          features: blockedLines.map(line => ({
            type: 'Feature',
            geometry: {
              type: 'LineString',
              coordinates: line.coordinates
            }
          }))
        }
      });

      previewMap.addLayer({
        id: 'blocked-lines',
        type: 'line',
        source: 'blocked-lines',
        paint: {
          'line-color': '#ff0000',
          'line-width': 3,
          'line-dasharray': [2, 2]
        }
      });

      // زوم تلقائي
      const bounds = new mapboxgl.LngLatBounds();
      finalRoute.geometry.coordinates.forEach(coord => bounds.extend(coord));
      previewMap.fitBounds(bounds, { padding: 50 });

      const distanceKm = (finalRoute.distance / 1000).toFixed(2);
      document.getElementById('distance-info').textContent = `📍 Distance: ${distanceKm} km`;
    }

    if (!previewMap.isStyleLoaded()) {
      previewMap.once('styledata', addLayers);
    } else {
      addLayers();
    }
  }

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
            if (!selectedTypes.has(type.id.toString())) {
              $typeSelect.append(`<option value="${type.id}">${type.name}</option>`);
            }
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

  /* ==========================  Form Tabs Code  ========================== */
  $('#back-to-step1').on('click', function () {
    new bootstrap.Tab(document.querySelector('#tab-step1')).show();
  });
  $('#back-to-step2').on('click', function () {
    new bootstrap.Tab(document.querySelector('#tab-step2')).show();
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
      url: baseUrl + 'admin/tasks/validate-step1',
      method: 'POST',
      data: new FormData($('#task-form')[0]),
      processData: false,
      contentType: false,

      success: function (data) {
        $('span.text-error').text(''); // إعادة تعيين الأخطاء

        $('#task-form').unblock({
          onUnblock: function () {
            if (data.status == 0) {
              showAlert('error', 'يرجى تصحيح الأخطاء قبل المتابعة', 10000, true);
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

  function setupMethodSelection(methods) {
    const methodsMap = {};

    methods.forEach(method => {
      methodsMap[method.id] = method;
    });

    // حدث تغيير اختيار الميثود
    $('#pricing-method-select')
      .off('change')
      .on('change', function () {
        const selectedId = $(this).val();
        const selectedMethod = methodsMap[selectedId];

        $('#params-select-wrapper').remove(); // إزالة أي اختيار سابق

        if (selectedMethod && selectedMethod.type === 'points' && selectedMethod.params.length > 0) {
          let selectHTML = `
          <div id="params-select-wrapper" class="mt-3">

            <select class="form-select" name="params_select" id="params-select">
              <option value="">-- Choose the path --</option>`;

          selectedMethod.params.forEach((param, index) => {
            selectHTML += `
            <option value="${param.param}"
              data-from-lat="${param.from_point.latitude}"
              data-from-lng="${param.from_point.longitude}"
              data-to-lat="${param.to_point.latitude}"
              data-to-lng="${param.to_point.longitude}">
              من ${param.from_point.name} إلى ${param.to_point.name} - السعر: ${parseFloat(param.price).toFixed(0)} ريال
            </option>`;
          });

          selectHTML += `</select>
            <span class="params_select-error text-danger text-error"></span>


          </div>`;

          $('#pricing-method-select').after(selectHTML);
          $('#delivery-map-section').hide();
          $('#pickup-map-section').hide();
        } else {
          $('#delivery-map-section').show();
          $('#pickup-map-section').show();

          $('#pickup-latitude').val('');
          $('#pickup-longitude').val('');
          $('#delivery-latitude').val('');
          $('#delivery-longitude').val('');
        }
      });

    // حدث تغيير اختيار param لتحديث الإحداثيات
    $(document)
      .off('change', '#params-select')
      .on('change', '#params-select', function () {
        const selectedOption = $(this).find('option:selected');

        $('#pickup-latitude').val(selectedOption.data('from-lat'));
        $('#pickup-longitude').val(selectedOption.data('from-lng'));
        $('#delivery-latitude').val(selectedOption.data('to-lat'));
        $('#delivery-longitude').val(selectedOption.data('to-lng'));
      });
  }

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
      url: baseUrl + 'admin/tasks/validate-step2',
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
              showAlert('error', 'يرجى تصحيح الأخطاء قبل المتابعة', 10000, true);
              handleErrors(data.error);
              showBlockAlert('warning', 'حدث خطأ أثناء الإرسال!');
            } else if (data.status == 1) {
              renderPricingDetails(data.data);
              handlePricingResponse(data.data.drivers);

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
});

function renderPricingDetails(data) {
  console.log(data);
  $('#assign-section').hide();

  let html = `
    <div class="card p-4 shadow-sm rounded-3" style="font-family: Arial, sans-serif;">
      <h2 class="mb-4 text-center">تفاصيل التسعير</h2>
  `;

  if (data.pricing_role) {
    html += `<div class="mb-2"><strong>Pricing Role:</strong> ${data.pricing_role}</div>`;
  }

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

  if (data.vat_commission) {
    html += `<div class="mb-2"><strong>VAT commission:</strong> ${parseFloat(data.vat_commission).toFixed(2)} %</div>`;
  }

  if (data.service_tax_commission) {
    html += `<div class="mb-2"><strong>Service tax commission:</strong> ${parseFloat(data.service_tax_commission).toFixed(2)} %</div>`;
  }

  if (data.discount_percentage) {
    html += `<div class="mb-2"><strong>Discount Percentage:</strong> ${parseFloat(data.discount_percentage).toFixed(2)} %</div>`;
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
      <div class="mb-3 row">
        <div class="col-md-6">
          <label for="min-price">* Min Price</label>
          <input type="number" name="min_price" id="min-price"  class="form-control" step="any" value="0.00" >
          <span class="min_price-error text-danger text-error"></span>
        </div>
         <div class="col-md-6">
          <label for="max-price">* Max Price</label>
          <input type="number" name="max_price" id="max-price"  class="form-control" step="any" value="0.00" >
          <span class="max_price-error text-danger text-error"></span>
        </div>
      </div>
      <div class="mb-3">
          <label for="not-price">Note</label>
          <textarea name="note_price" id="not-price" class="form-control"></textarea>
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
    $('#total-price').attr('placeholder', data.total_price);
  }

  html += `</div>`;
  document.getElementById('taskFinalDetails').innerHTML = html;
}

// تهيئة جميع select2 الموجودين
var select2 = $('.select2');
if (select2.length) {
  select2.each(function () {
    var $this = $(this);
    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: 'Select driver',
      dropdownParent: $this.parent(),
      closeOnSelect: true // يفضل جعله true لتجربة المستخدم أفضل
    });
  });
}

// دالة التعامل مع البيانات الراجعة من السيرفر
function handlePricingResponse(response) {
  const drivers = response; // استخرج قائمة السائقين

  const select = $('#task-driver-select');
  select.empty();
  select.append('<option value="">Select Driver</option>');

  drivers.forEach(driver => {
    select.append(`<option value="${driver.id}">${driver.name}</option>`);
  });

  // تأكد أن select2 تعمل بشكل صحيح
  select.trigger('change');
}

$('#task-driver-select').parent().hide(); // اخفائها بدايةً

$('#driver-automatically').on('change', function () {
  if (this.checked) {
    $('#task-driver-select').parent().hide();
    $('#task-driver-select').val('').trigger('change');
  }
});

$('#driver-manual').on('change', function () {
  if (this.checked) {
    $('#task-driver-select').parent().show();
  }
});
