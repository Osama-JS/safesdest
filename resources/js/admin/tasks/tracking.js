'use strict';
import { mapsConfig } from '../../mapbox-helper';

$(function () {
  mapboxgl.accessToken = mapsConfig.token;
  mapboxgl.setRTLTextPlugin(
    'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-rtl-text/v0.2.3/mapbox-gl-rtl-text.js',
    null,
    true // تحميل فقط عند الحاجة (lazy load)
  );
  const taskMap = new mapboxgl.Map({
    container: 'taskMap',
    style: 'mapbox://styles/' + mapsConfig.style,
    center: [window.taskData.pickup.lng, window.taskData.pickup.lat],
    zoom: 12
  });

  // إضافة التحكمات

  taskMap.addControl(new mapboxgl.NavigationControl());

  const bounds = new mapboxgl.LngLatBounds();

  // وظيفة لإنشاء ماركر مخصص برمز
  function createLabeledMarker(lng, lat, label, styles, popupText) {
    const el = document.createElement('div');
    el.className = 'custom-marker';
    Object.assign(el.style, {
      backgroundColor: styles.bg,
      color: styles.text,
      border: `3px solid ${styles.border}`,
      width: '36px',
      height: '36px',
      borderRadius: '50%',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      fontWeight: 'bold',
      fontSize: '14px',
      boxShadow: '0 2px 5px rgba(0, 0, 0, 0.3)',
      cursor: 'pointer'
    });
    el.textContent = label;

    new mapboxgl.Marker(el).setLngLat([lng, lat]).setPopup(new mapboxgl.Popup().setText(popupText)).addTo(taskMap);

    bounds.extend([lng, lat]);
  }

  // إضافة نقاط المهمة
  const { pickup, dropoff, driver } = window.taskData;
  if (pickup) {
    createLabeledMarker(
      pickup.lng,
      pickup.lat,
      'P',
      {
        bg: '#d4f8d4',
        text: '#006400',
        border: '#006400'
      },
      'Pickup Location'
    );
  }

  if (dropoff) {
    createLabeledMarker(
      dropoff.lng,
      dropoff.lat,
      'D',
      {
        bg: '#f8d4d4',
        text: '#8b0000',
        border: '#8b0000'
      },
      'Dropoff Location'
    );
  }

  if (driver) {
    createLabeledMarker(
      driver.lng,
      driver.lat,
      'S',
      {
        bg: '#d4e2f8',
        text: '#003399',
        border: '#003399'
      },
      'Driver Location'
    );
  }

  // ضبط الخريطة لتلائم جميع النقاط
  if (!bounds.isEmpty()) {
    taskMap.fitBounds(bounds, { padding: 50, maxZoom: 16 });
  }
});
