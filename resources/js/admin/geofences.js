/**
 * Page User List
 */

'use strict';

// Datatable (jquery)
$(function () {
  var select2 = $('.select2');
  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: 'Select teams',
      dropdownParent: $this.parent(),
      closeOnSelect: false
    });
  }

  var submit_map = $('#submit-map'),
    basicMap;

  if (submit_map.length) {
    basicMap = L.map('submit-map').setView([24.774265, 46.738586], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: 'Map data &copy; OpenStreetMap contributors',
      maxZoom: 18
    }).addTo(basicMap);

    var drawnItems = new L.FeatureGroup();
    basicMap.addLayer(drawnItems);

    var drawControl = new L.Control.Draw({
      draw: {
        polygon: true, // تمكين رسم المضلع (الحدود الجغرافية)
        rectangle: true, // تمكين رسم المستطيلات
        polyline: false,
        circle: false,
        marker: false,
        circlemarker: false
      },
      edit: {
        featureGroup: drawnItems,
        remove: true // تمكين إزالة الرسومات
      }
    });

    basicMap.addControl(drawControl);

    basicMap.on('draw:created', function (event) {
      var layer = event.layer;
      drawnItems.clearLayers();
      drawnItems.addLayer(layer);

      var coordinates = layer.getLatLngs()[0].map(coord => ({ lat: coord.lat, lng: coord.lng }));
      var wktPolygon = convertToWKT(coordinates);

      document.getElementById('geo-coordinates').value = wktPolygon;
    });

    basicMap.on('draw:edited', function () {
      var layers = drawnItems.getLayers();
      if (layers.length > 0) {
        var coordinates = layers[0].getLatLngs()[0].map(coord => ({ lat: coord.lat, lng: coord.lng }));
        var wktPolygon = convertToWKT(coordinates);

        document.getElementById('geo-coordinates').value = wktPolygon;
      }
    });

    basicMap.on('draw:deleted', function () {
      document.getElementById('geo-coordinates').value = '';
    });
  }

  function convertToWKT(latlngs) {
    if (!latlngs || latlngs.length === 0) return '';

    let wkt = 'POLYGON((';
    latlngs.forEach((point, index) => {
      wkt += `${point.lng} ${point.lat}, `;
    });

    // إضافة النقطة الأولى في النهاية لإغلاق المضلع
    wkt += `${latlngs[0].lng} ${latlngs[0].lat}))`;

    return wkt;
  }

  $('#submitModal').on('shown.bs.modal', function () {
    setTimeout(function () {
      basicMap.invalidateSize();
    }, 400);
  });
});
