/**
 * Page User List
 */

'use strict';
import { deleteRecord } from '../ajax';
import { mapsConfig } from '../mapbox-helper';

// Datatable (jquery)
$(function () {
  console.log(typeof Lang);

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  document.addEventListener('formSubmitted', function (event) {
    setTimeout(() => {
      window.location.reload();
    }, 2000);
  });
  // دالة لتحميل الخريطة باستخدام Mapbox
  function initMapForAd(adId, location) {
    let mapContainer = document.getElementById(`map-${adId}`);

    if (!mapContainer) return;

    mapboxgl.accessToken = mapsConfig.token;
    let map = new mapboxgl.Map({
      container: mapContainer,
      style: 'mapbox://styles/' + mapsConfig.style,
      center: [location[0], location[1]],
      zoom: 12
    });

    new mapboxgl.Marker().setLngLat([location[0], location[1]]).addTo(map);
  }
});
