/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, showFormModal, generateFields } from '../ajax';
import { mapsConfig } from '../mapbox-helper';

// Datatable (jquery)
$(function () {
  const verticalExample = document.getElementById('vertical-example');
  if (verticalExample) {
    new PerfectScrollbar(verticalExample, {
      wheelPropagation: false
    });
  }

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  let taskMarkers = {};
  mapboxgl.accessToken = mapsConfig.token;
  mapboxgl.setRTLTextPlugin(
    'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-rtl-text/v0.2.3/mapbox-gl-rtl-text.js',
    null,
    true // تحميل فقط عند الحاجة (lazy load)
  );

  // تهيئة الخريطة باستخدام اسم جديد لتجنب التعارض
  const driverMapInstance = new mapboxgl.Map({
    container: 'taskMap',
    style: 'mapbox://styles/' + mapsConfig.style,
    center: mapsConfig.center,
    zoom: 10
  });

  // تحميل المهام بعد تهيئة الخريطة
  driverMapInstance.on('load', function () {
    loadDrivers(); // تحميل المهام بعد تحميل الخريطة
  });

  // منع تداخل الخريطة مع عناصر أخرى على الصفحة

  $(document).on('change', '#filter-by-day', function () {
    console.log($(this).val());
    loadDrivers();
  });

  // دالة لتحميل المهام
  function loadDrivers(retries = 3) {
    $('.body-container-block').block({
      message: `
    <div class="d-flex justify-content-center">
      <p class="mb-0">Please wait...</p>
      <div class="sk-wave m-0">
        <div class="sk-rect sk-wave-rect"></div>
        <div class="sk-rect sk-wave-rect"></div>
        <div class="sk-rect sk-wave-rect"></div>
        <div class="sk-rect sk-wave-rect"></div>
        <div class="sk-rect sk-wave-rect"></div>
      </div>
    </div>`,
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
      url: baseUrl + 'admin/dashboard/drivers/data',
      type: 'GET',
      success: function (response) {
        $('.body-container-block').unblock({
          onUnblock: function () {
            const online = response.data.online;
            const offline = response.data.offline;
            const busy = response.data.busy;

            renderDrivers(online, '#drivers-online-container', '.count-drivers-online');
            renderDrivers(offline, '#drivers-offline-container', '.count-drivers-offline');
            renderDrivers(busy, '#drivers-busy-container', '.count-drivers-busy');
            updateMapMarkersForDrivers(driverMapInstance, [...online, ...offline, ...busy]);
          }
        });
      },
      error: function () {
        if (retries > 0) {
          setTimeout(() => loadDrivers(retries - 1), 2000);
        } else {
          $('.body-container-block').unblock({
            onUnblock: function () {
              showAlert('warning', 'Error loading Drivers. Please refresh the page');
              $('#drivers-online-container, #drivers-offline-container, #drivers-busy-container').html(
                '<div class="alert alert-danger">Error loading Drivers. Please refresh the page</div>'
              );
            }
          });
        }
      }
    });
  }

  function renderDrivers(drivers, containerSelector, countSelector, map) {
    if ($(containerSelector).length === 0) {
      console.error(`Container element ${containerSelector} not found in DOM.`);
      return;
    }
    if (!driverMapInstance) {
      console.error('Map instance is undefined.');
      return;
    }

    $(countSelector).text(drivers.length);
    $(containerSelector).html('');

    drivers.forEach(driver => {
      const card = `
      <div class="mb-4">
        <div
          class="card p-3 shadow-sm driver-card"
          id="driver-${driver.id}"
          data-driver-id="${driver.id}"
          data-lng="${driver.location.longitude}"
          data-lat="${driver.location.altitude}"
          data-name="${driver.name}"
          data-phone="${driver.phone_code} ${driver.phone}"
          >
          <div class="d-flex justify-content-between">
            <div class="d-flex align-items-center">
              <img src="${driver.avatar}" class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;" alt="Avatar">
              <div class="px-3">
                <h6 class="mb-1">${driver.name}</h6>
                <p class="mb-1">📞 ${driver.phone_code} ${driver.phone}</p>
                <p class="mb-1 text-muted small">📍 ${driver.location.longitude} - ${driver.location.altitude}</p>
                <p class="mb-1 text-muted small">🕒 Last seen: ${driver.last_seen_at || 'غير متوفر'}</p>
              </div>
            </div>
            <div class="d-flex align-items-center">
              <span class="badge bg-${driver.online ? 'success' : 'secondary'}">${driver.online ? 'Online' : 'Offline'}</span>
            </div>
          </div>
        </div>
      </div>
    `;

      $(containerSelector).append(card);
    });

    // تفويض حدث الضغط على بطاقة السائق
    $(containerSelector).off('click', '.driver-card'); // إزالة روابط الحدث السابقة
    $(containerSelector).on('click', '.driver-card', function () {
      handleDriverSelection(
        {
          id: $(this).data('driver-id'),
          lng: $(this).data('lng'),
          lat: $(this).data('lat'),
          name: $(this).data('name'),
          phone: $(this).data('phone')
        },
        driverMapInstance
      );
    });
  }

  // دالة لعرض المهام على الخريطة
  function updateMapMarkersForDrivers(map, drivers) {
    // إزالة المصدر والطبقات السابقة إذا وُجدت
    if (map.getSource('drivers')) {
      if (map.getLayer('driver-circle')) map.removeLayer('driver-circle');
      if (map.getLayer('driver-clusters')) map.removeLayer('driver-clusters');
      if (map.getLayer('driver-cluster-count')) map.removeLayer('driver-cluster-count');
      if (map.getLayer('driver-unclustered-point')) map.removeLayer('driver-unclustered-point');
      map.removeSource('drivers');
    }

    const features = drivers
      .filter(driver => driver.location?.longitude && driver.location?.altitude)
      .map(driver => ({
        type: 'Feature',
        geometry: {
          type: 'Point',
          coordinates: [driver.location.longitude, driver.location.altitude]
        },
        properties: {
          id: driver.id,
          name: driver.name,
          phone: `${driver.phone_code} ${driver.phone}`,
          status: driver.status || getDriverStatus(driver)
        }
      }));

    map.addSource('drivers', {
      type: 'geojson',
      data: {
        type: 'FeatureCollection',
        features: features
      },
      cluster: true,
      clusterMaxZoom: 14,
      clusterRadius: 50
    });

    // طبقة تجميع السائقين (clusters)
    map.addLayer({
      id: 'driver-clusters',
      type: 'circle',
      source: 'drivers',
      filter: ['has', 'point_count'],
      paint: {
        'circle-color': '#007cbf',
        'circle-radius': ['step', ['get', 'point_count'], 20, 10, 30, 30, 40],
        'circle-opacity': 0.8
      }
    });

    map.addLayer({
      id: 'driver-cluster-count',
      type: 'symbol',
      source: 'drivers',
      filter: ['has', 'point_count'],
      layout: {
        'text-field': '{point_count_abbreviated}',
        'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold'],
        'text-size': 14
      },
      paint: {
        'text-color': '#ffffff'
      }
    });

    // طبقة دائرة ملونة حسب حالة السائق للنقاط الفردية
    map.addLayer({
      id: 'driver-circle',
      type: 'circle',
      source: 'drivers',
      filter: ['!', ['has', 'point_count']],
      paint: {
        'circle-radius': 16,
        'circle-color': [
          'match',
          ['get', 'status'],
          'online',
          '#4CAF50',
          'busy',
          '#FFC107',
          'offline',
          '#9E9E9E',
          '#000000'
        ],
        'circle-stroke-width': 2,
        'circle-stroke-color': '#ffffff'
      }
    });

    // طبقة النص "D" فوق الدائرة
    map.addLayer({
      id: 'driver-unclustered-point',
      type: 'symbol',
      source: 'drivers',
      filter: ['!', ['has', 'point_count']],
      layout: {
        'text-field': 'D',
        'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold'],
        'text-size': 24,
        'text-anchor': 'center',
        'text-offset': [0, 0]
      },
      paint: {
        'text-color': '#ffffff'
      }
    });

    // تكبير الخريطة لتشمل جميع السائقين
    if (features.length > 0) {
      const bounds = new mapboxgl.LngLatBounds();
      features.forEach(f => bounds.extend(f.geometry.coordinates));
      map.fitBounds(bounds, { padding: 50, maxZoom: 11 });

      map._defaultBounds = bounds;
    }

    let lastClickTimestamp = 0;

    map.off('click', 'driver-unclustered-point');
    map.on('click', 'driver-unclustered-point', function (e) {
      const now = Date.now();
      if (now - lastClickTimestamp < 300) return; // تجاهل النقرات المتكررة خلال 300ms
      lastClickTimestamp = now;

      const features = map.queryRenderedFeatures(e.point, { layers: ['driver-unclustered-point'] });
      if (!features.length) return;

      const feature = features[0];

      handleDriverSelection(
        {
          id: feature.properties.id,
          lng: feature.geometry.coordinates[0],
          lat: feature.geometry.coordinates[1],
          name: feature.properties.name || 'اسم غير متوفر',
          phone: feature.properties.phone || 'رقم غير متوفر',
          status: feature.properties.status || null
        },
        map
      );
    });

    // تفاعل الضغط على cluster
    map.on('click', 'driver-clusters', function (e) {
      const features = map.queryRenderedFeatures(e.point, { layers: ['driver-clusters'] });
      if (!features.length) return;
      const clusterId = features[0].properties.cluster_id;
      map.getSource('drivers').getClusterExpansionZoom(clusterId, function (err, zoom) {
        if (err) return;
        map.easeTo({
          center: features[0].geometry.coordinates,
          zoom: zoom
        });
      });
    });

    // مؤشرات المؤشر عند المرور
    map.on('mouseenter', 'driver-unclustered-point', () => {
      map.getCanvas().style.cursor = 'pointer';
    });
    map.on('mouseleave', 'driver-unclustered-point', () => {
      map.getCanvas().style.cursor = '';
    });

    map.on('mouseenter', 'driver-clusters', () => {
      map.getCanvas().style.cursor = 'pointer';
    });
    map.on('mouseleave', 'driver-clusters', () => {
      map.getCanvas().style.cursor = '';
    });
  }

  function handleDriverSelection(driver, map) {
    console.log('handel');
    const card = document.getElementById(`driver-${driver.id}`);
    const lng = parseFloat(driver.lng);
    const lat = parseFloat(driver.lat);

    if (!card) return;
    console.log('handel2');

    const isSelected = card.classList.contains('selected');

    // إزالة التحديد من الجميع
    document.querySelectorAll('.driver-card.selected').forEach(el => el.classList.remove('selected'));

    // إزالة أي Popup مفتوح
    if (window.driverPopupInstance) {
      window.driverPopupInstance.remove();
      window.driverPopupInstance = null;
    }

    console.log('handel3');

    if (isSelected) {
      // إرجاع الخريطة إلى الحدود الأصلية
      if (map._defaultBounds) {
        map.fitBounds(map._defaultBounds, { padding: 50, maxZoom: 11 });
      } else {
        map.easeTo({ center: [0, 0], zoom: 2 });
      }
    } else {
      // تحديد البطاقة
      card.classList.add('selected');
      card.scrollIntoView({ behavior: 'smooth', block: 'center' });

      // إنشاء نافذة منبثقة جديدة
      if (!isNaN(lng) && !isNaN(lat)) {
        const popup = new mapboxgl.Popup({ offset: 15, closeOnClick: true })
          .setLngLat([lng, lat])
          .setHTML(
            `
          <div>
            <strong>${driver.name}</strong><br>
            <span>📞 ${driver.phone}</span>
          </div>
        `
          )
          .addTo(map);

        window.driverPopupInstance = popup;

        // تركيز الخريطة على السائق
        map.flyTo({ center: [lng, lat], zoom: 15, speed: 0.8 });
      }
    }
  }

  // دالة لتحديد الحالة من معلومات السائق
  function getDriverStatus(driver) {
    if (driver.online) return 'online';
    if (driver.busy) return 'busy';
    return 'offline';
  }

  loadDrivers();

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');
    setTimeout(() => {
      $('#submitModal').modal('hide');
      $('#assignModal').modal('hide');
      $('#adModal').modal('hide');
      $('#pricingModal').modal('hide');
      loadDrivers();
    }, 2000);
  });

  document.addEventListener('deletedSuccess', function (event) {
    loadDrivers();
  });

  $(document).on('click', '.status-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const status = $(this).data('status');

    const fields = `
        <input type="hidden" name="id" value="${id}">
        <select class="form-select" name="status">
          <option value="in_progress" ${status === 'in_progress' ? 'selected' : ''}>in progress</option>
          <option value="started" ${status === 'started' ? 'selected' : ''}>started</option>
          <option value="in pickup point" ${status === 'in pickup point' ? 'selected' : ''}>in pickup point</option>
          <option value="loading" ${status === 'loading' ? 'selected' : ''}>loading</option>
          <option value="in the way" ${status === 'in the way' ? 'selected' : ''}>in the way</option>
          <option value="in delivery point" ${status === 'in delivery point' ? 'selected' : ''}>in delivery point</option>
          <option value="unloading" ${status === 'unloading' ? 'selected' : ''}>unloading</option>
          <option value="completed" ${status === 'completed' ? 'selected' : ''}>completed</option>
          <option value="canceled" ${status === 'canceled' ? 'selected' : ''}>canceled</option>
        </select>
      `;

    showFormModal({
      title: `Change Task: ${name} Status`,
      icon: 'info',
      fields: fields,
      url: `${baseUrl}admin/tasks/status`,
      method: 'POST'
    });
  });
  document.addEventListener('statusChange', function (event) {
    loadDrivers();
  });
});
