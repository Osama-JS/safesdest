/**
 * Page Task List
 */

'use strict';
import { deleteRecord, showAlert, showFormModal, generateFields, handleErrors } from '../ajax';

$(function () {
  let pointIndex = 0;

  /* ===========  MapBox  accessToken   ===========*/
  mapboxgl.accessToken = 'pk.eyJ1Ijoib3NhbWExOTk4IiwiYSI6ImNtOWk3eXd4MjBkbWcycHF2MDkxYmI3NjcifQ.2axcu5Sk9dx6GX3NtjjAvA';

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
  const previewMap = new mapboxgl.Map({
    container: `preview-map`,
    style: 'mapbox://styles/mapbox/streets-v12',
    center: [46.6753, 24.7136],
    zoom: 10
  });

  let pickupMarker = null;
  let deliveryMarker = null;
  let routeLine = null;

  let pickupCoords = null;
  let deliveryCoords = null;

  // Call this function after specifying the coordinates of the two points.
  async function updatePreviewRoute(pickupCoords, deliveryCoords) {
    console.log('updatePreviewRoute', pickupCoords, deliveryCoords);

    // 🧹 Clean out old items
    if (pickupMarker) pickupMarker.remove();
    if (deliveryMarker) deliveryMarker.remove();
    if (previewMap.getLayer('route-line')) previewMap.removeLayer('route-line');
    if (previewMap.getSource('route')) previewMap.removeSource('route');
    if (previewMap.getLayer('blocked-roads')) previewMap.removeLayer('blocked-roads');
    if (previewMap.getSource('blocked-roads')) previewMap.removeSource('blocked-roads');

    // 📍 Apply markers
    pickupMarker = new mapboxgl.Marker({ color: 'green' })
      .setLngLat(pickupCoords)
      .setPopup(new mapboxgl.Popup().setText('Pickup Point'))
      .addTo(previewMap);

    deliveryMarker = new mapboxgl.Marker({ color: 'red' })
      .setLngLat(deliveryCoords)
      .setPopup(new mapboxgl.Popup().setText('Delivery Point'))
      .addTo(previewMap);

    // 🚫 Coordinates for closed roads (examples - you can edit)
    const blockedAreas = [
      { lat: 24.774265, lng: 46.738586 },
      { lat: 24.798524, lng: 46.675214 },
      { lat: 24.761234, lng: 46.69 }
    ];

    // 🔁 Comparison function
    function isNear(coord1, coord2, threshold = 0.003) {
      return Math.abs(coord1[0] - coord2.lng) < threshold && Math.abs(coord1[1] - coord2.lat) < threshold;
    }

    // 🔍 Track check
    function isRouteBlocked(route) {
      return route.geometry.coordinates.some(coord => blockedAreas.some(block => isNear(coord, block)));
    }

    // 🧠 Fetch path
    async function fetchRouteWithRetry(pickup, delivery) {
      const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${pickup.join(',')};${delivery.join(',')}?geometries=geojson&steps=true&overview=full&access_token=${mapboxgl.accessToken}`;
      const res = await fetch(url);
      const data = await res.json();

      if (!data.routes || data.routes.length === 0) {
        throw new Error('No route found');
      }

      const route = data.routes[0];
      if (isRouteBlocked(route)) {
        console.warn('🚫 المسار يمر بطريق مغلق!');
        throw new Error('Route blocked');
      }

      return route;
    }

    // 🔄 Trying to find a valid path
    let finalRoute = null;
    let attempts = 0;

    while (!finalRoute && attempts < 3) {
      try {
        finalRoute = await fetchRouteWithRetry(pickupCoords, deliveryCoords);
      } catch (e) {
        attempts++;
        if (attempts === 3) {
          alert('لا يمكن العثور على مسار بدون المرور بطرق مغلقة 🚧');
          return;
        }
      }
    }

    // 📌 Make sure the pattern is ready and then add the layers.
    function addLayers() {
      // ➕ Add path
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
        layout: {
          'line-join': 'round',
          'line-cap': 'round'
        },
        paint: {
          'line-color': '#007cbf',
          'line-width': 4
        }
      });

      // 🟥 Show closed roads
      const blockedGeojson = {
        type: 'FeatureCollection',
        features: blockedAreas.map(block => ({
          type: 'Feature',
          geometry: {
            type: 'Point',
            coordinates: [block.lng, block.lat]
          }
        }))
      };

      previewMap.addSource('blocked-roads', {
        type: 'geojson',
        data: blockedGeojson
      });

      previewMap.addLayer({
        id: 'blocked-roads',
        type: 'circle',
        source: 'blocked-roads',
        paint: {
          'circle-radius': 9,
          'circle-color': '#ff0000',
          'circle-stroke-width': 2,
          'circle-stroke-color': '#ffffff'
        }
      });

      // 🔍 Zoom in view
      const bounds = new mapboxgl.LngLatBounds();
      finalRoute.geometry.coordinates.forEach(coord => bounds.extend(coord));
      previewMap.fitBounds(bounds, { padding: 50 });

      // 📏 Distance
      const distanceKm = (finalRoute.distance / 1000).toFixed(2);
      document.getElementById('distance-info').textContent = `📍 Distance: ${distanceKm} km`;
    }

    // Make sure the pattern is loaded
    if (!previewMap.isStyleLoaded()) {
      previewMap.once('styledata', addLayers);
    } else {
      addLayers();
    }
  }

  // const pickup = [46.675214, 24.798524]; // نقطة انطلاق
  // const delivery = [46.738586, 24.774265]; // وجهة، تمر بطريق مغلق افتراضيًا
  // updatePreviewRoute(pickup, delivery);

  /* ===========  Set pickup and delivery Points Map   ===========*/
  function setupMapboxLocationHandlers(prefix) {
    const map = new mapboxgl.Map({
      container: `${prefix}-map`,
      style: 'mapbox://styles/mapbox/streets-v12',
      center: [46.6753, 24.7136],
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

  // function call for the tow points
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

  // $('#add-vehicle-btn').on('click', function () {
  //   const $newRow = $(createVehicleRow(vehicleIndex++));
  //   $('#vehicle-selection-container').append($newRow);
  //   updateVehicleRowEvents($newRow);
  // });

  // // أول سطر بشكل افتراضي
  // $('#add-vehicle-btn').trigger('click');

  /* ================  Form Template Code   =============== */

  $('#task-select-template').on('change', function () {
    $.ajax({
      url: baseUrl + 'admin/settings/templates/pricing',
      type: 'GET',
      data: { id: $(this).val() },
      success: function (response) {
        generateFields(response.fields);
      },
      error: function () {
        console.log('Error loading template fields.');
      }
    });
  });

  /* ==========================  Form Tabs Code  ========================== */
  $('#go-to-step2').on('click', function () {
    const formData = $('#task-form').serialize();

    $.ajax({
      url: baseUrl + 'admin/tasks/validate-step1',
      method: 'POST',
      data: formData,

      success: function (data) {
        if (data.status == 0) {
          showAlert('error', 'يرجى تصحيح الأخطاء قبل المتابعة', 10000, true);
          console.log(data.error);
          handleErrors(data.error);
        } else if (data.status == 1) {
          const select = $('#pricing-method-select');
          select.empty();
          // قسم للديناميكي
          select.append('<optgroup label="Dynamic Methods">');
          $.each(data.data, function (index, method) {
            select.append(
              `<option value="${method.id}" data-distance="${method.distance_calculation}">${method.name}</option>`
            );
          });
          select.append('</optgroup>');
          $('span.text-error').text('');
          new bootstrap.Tab(document.querySelector('#tab-step2')).show();
        } else {
          showAlert('error', data.error, 10000, true);
        }
      },
      error: function (xhr) {
        const errors = xhr.responseJSON.errors;
        $('.text-error').text('');

        for (const field in errors) {
          $(`.${field}-error`).text(errors[field][0]);
        }

        showAlert('error', 'يرجى تصحيح الأخطاء قبل المتابعة', 10000, true);
      }
    });
  });
});
