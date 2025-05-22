/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, showFormModal } from '../ajax';

$(function () {
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  var select2 = $('.select2');
  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: __('Select Teams'),
      dropdownParent: $this.parent(),
      closeOnSelect: false
    });
  }

  const verticalExample = document.getElementById('vertical-scroll');
  if (verticalExample) {
    new PerfectScrollbar(verticalExample, { wheelPropagation: false });
  }

  const map = L.map('shapehMap').setView([24.774265, 46.738586], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: 'Map data &copy; OpenStreetMap contributors',
    maxZoom: 18
  }).addTo(map);

  const geofenceLayer = L.layerGroup().addTo(map);

  function wktToGeoJSON(wkt) {
    if (!wkt || typeof wkt !== 'string' || !wkt.startsWith('POLYGON')) return null;
    try {
      const match = wkt.match(/\(\((.*)\)\)/);
      if (!match) return null;
      const coords = match[1].split(',').map(coord => {
        const [lng, lat] = coord.trim().split(' ').map(Number);
        return [lng, lat];
      });
      return { type: 'Feature', geometry: { type: 'Polygon', coordinates: [coords] }, properties: {} };
    } catch {
      return null;
    }
  }

  function loadGeofences(search = '') {
    $.getJSON(`${baseUrl}admin/settings/geofences/data`, { search }, function (response) {
      if (response.status !== 1) return;

      $('#geofence-list').empty();
      geofenceLayer.clearLayers();

      let activeLayer = null,
        activeLabel = null,
        activeDiv = null;

      if (response.data.length === 0) {
        $('#geofence-list').append(
          '<div class="text-center alert alert-secondary">' + __('No data available') + '</div>'
        );
      }

      response.data.forEach(geofence => {
        const geofenceDiv = $(
          `<div class="mb-4 geofence-item" data-id="${geofence.id}">
            <div class="card p-3 shadow-sm">
              <div class="d-flex justify-content-between">
                <h5>${geofence.name}</h5>
                <div class="d-flex align-items-center gap-50">
                  <button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${geofence.id}" data-name="${geofence.name}" data-bs-toggle="modal" data-bs-target="#largeModal">
                    <i class="ti ti-edit"></i>
                  </button>
                  <button class='btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect' data-id="${geofence.id}" data-name="${geofence.name}">
                    <i class='ti ti-trash'></i>
                  </button>
                  <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end m-0">
                    <a href="#" class="dropdown-item">View</a>
                    <a href="javascript:;" class="dropdown-item">Suspend</a>
                  </div>
                </div>
              </div>
              <div class="d-flex flex-wrap">
                ${geofence.description || 'بدون وصف'}
              </div>
            </div>
          </div>`
        );

        $('#geofence-list').append(geofenceDiv);

        if (!geofence.geometry) return;
        const geoJsonFeature = wktToGeoJSON(geofence.geometry);
        if (!geoJsonFeature) return;

        const geoLayer = L.geoJSON(geoJsonFeature, {
          style: { color: 'blue', weight: 2, opacity: 0.6, fillOpacity: 0.2 }
        }).addTo(geofenceLayer);

        geoLayer.geofenceId = geofence.id;
        geoLayer.geofenceName = geofence.name;
        geoLayer.defaultStyle = { color: 'blue', weight: 2, opacity: 0.6, fillOpacity: 0.2 };

        const center = geoLayer.getBounds().getCenter();
        const label = L.marker(center, {
          icon: L.divIcon({
            className: 'geofence-label hidden-label',
            html: `<strong>${geofence.name}</strong>`,
            iconSize: [100, 40],
            iconAnchor: [50, 20]
          })
        }).addTo(geofenceLayer);

        const highlightGeofence = (layer, label, listItem) => {
          if (activeLayer) activeLayer.setStyle(activeLayer.defaultStyle);
          if (activeLabel) activeLabel._icon.classList.add('hidden-label');
          if (activeDiv) activeDiv.removeClass('selected-geofence');

          layer.setStyle({ color: 'red', weight: 3, opacity: 1, fillOpacity: 0.4 });
          label._icon.classList.remove('hidden-label');
          layer.bindPopup(`<b>${layer.geofenceName}</b>`).openPopup();
          listItem.addClass('selected-geofence');

          activeLayer = layer;
          activeLabel = label;
          activeDiv = listItem;
        };

        geofenceDiv.on('click', () => highlightGeofence(geoLayer, label, geofenceDiv));
        geoLayer.on('click', () => highlightGeofence(geoLayer, label, geofenceDiv));
      });
    });
  }

  $('#search-geo').on('input', function () {
    loadGeofences($(this).val());
  });
  loadGeofences();

  const submit_map = $('#submit-map');
  const drawnItems = new L.FeatureGroup();

  let basicMap;
  if (submit_map.length) {
    basicMap = L.map('submit-map').setView([24.774265, 46.738586], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: 'Map data &copy; OpenStreetMap contributors',
      maxZoom: 18
    }).addTo(basicMap);

    basicMap.addLayer(drawnItems);

    const drawControl = new L.Control.Draw({
      draw: { polygon: true, rectangle: true, polyline: false, circle: false, marker: false, circlemarker: false },
      edit: { featureGroup: drawnItems, remove: true }
    });

    basicMap.addControl(drawControl);

    const updateCoordinates = () => {
      const layers = drawnItems.getLayers();
      if (!layers.length) return;
      const coordinates = layers[0].getLatLngs()[0].map(coord => ({ lat: coord.lat, lng: coord.lng }));
      document.getElementById('geo-coordinates').value = convertToWKT(coordinates);
    };

    basicMap.on('draw:created', function (event) {
      drawnItems.clearLayers();
      drawnItems.addLayer(event.layer);
      updateCoordinates();
    });

    basicMap.on('draw:edited', updateCoordinates);

    basicMap.on('draw:deleted', function () {
      document.getElementById('geo-coordinates').value = '';
    });
  }

  document.addEventListener('formSubmitted', function () {
    loadGeofences();
    $('#geo-id').val('');
    $('.form_submit').trigger('reset');
    $('#geo-teams').val([]).trigger('change');

    setTimeout(() => $('#submitModal').modal('hide'), 2000);
  });

  document.addEventListener('deletedSuccess', function () {
    loadGeofences();
  });

  function convertToWKT(latlngs) {
    if (!latlngs.length) return '';
    const wkt =
      'POLYGON((' + latlngs.map(p => `${p.lng} ${p.lat}`).join(', ') + `, ${latlngs[0].lng} ${latlngs[0].lat}))`;
    return wkt;
  }

  $('#submitModal').on('shown.bs.modal', function () {
    setTimeout(() => basicMap.invalidateSize(), 400);
  });

  $(document).on('click', '.edit-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');

    $('#submitModal').modal('show');
    $('#modelTitle').html(`Edit Geo-fence: <span class="bg-info text-white px-2 rounded">${name}</span>`);

    $.get(`${baseUrl}admin/settings/geofences/edit/${id}`, function (data) {
      $('#geo-id').val(data.id);
      $('#geo-name').val(data.name);
      $('#geo-description').val(data.description);
      $('#geo-teams').val(data.teamsIds).trigger('change');
      $('#geo-coordinates').val(data.coordinates_wkt);

      drawnItems.clearLayers();

      if (data.coordinates_wkt) {
        const geoJsonFeature = wktToGeoJSON(data.coordinates_wkt);
        if (geoJsonFeature) {
          const coordinates = geoJsonFeature.geometry.coordinates[0].map(coord => L.latLng(coord[1], coord[0]));

          // إنشاء بوليجون بنفس الطريقة التي يرسم بها المستخدم
          const polygon = L.polygon(coordinates, { color: 'blue' });

          // إضافته إلى مجموعة العناصر المرسومة كما لو أن المستخدم رسمه
          drawnItems.addLayer(polygon);

          // إجبار النظام على تحديث القيمة
          const updateCoordinates = () => {
            const layers = drawnItems.getLayers();
            if (!layers.length) return;
            const coords = layers[0].getLatLngs()[0].map(coord => ({ lat: coord.lat, lng: coord.lng }));
            document.getElementById('geo-coordinates').value = convertToWKT(coords);
          };

          updateCoordinates();
        }
      }

      // Force coordinate update on modal open (in case shape is edited)
      basicMap.once('draw:edited', function () {
        const layers = drawnItems.getLayers();
        if (!layers.length) return;
        const coordinates = layers[0].getLatLngs()[0].map(coord => ({ lat: coord.lat, lng: coord.lng }));
        document.getElementById('geo-coordinates').value = convertToWKT(coordinates);
      });
    });
  });

  $(document).on('click', '.delete-record', function () {
    const url = `${baseUrl}admin/settings/geofences/delete/${$(this).data('id')}`;
    deleteRecord($(this).data('name'), url);
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    $('#modelTitle').html(__('Add Geo-fence'));
    $('#geo-teams').val([]).trigger('change');

    document.getElementById('geo-coordinates').value = '';
    drawnItems.clearLayers();
  });
});
