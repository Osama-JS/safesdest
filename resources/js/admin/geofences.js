/**
 * Page User List
 */

'use strict';

// Datatable (jquery)
$(function () {
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
  const verticalExample = document.getElementById('vertical-scroll');

  if (verticalExample) {
    new PerfectScrollbar(verticalExample, {
      wheelPropagation: false
    });
  }
  var map = L.map('shapehMap').setView([24.774265, 46.738586], 6);

  // إضافة خريطة الأساس
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: 'Map data &copy; OpenStreetMap contributors',
    maxZoom: 18
  }).addTo(map);

  var geofenceLayer = L.layerGroup().addTo(map);

  function wktToGeoJSON(wkt) {
    // التحقق من أن الإدخال صحيح وليس فارغًا
    if (!wkt || typeof wkt !== 'string') {
      console.error('⚠️ WKT غير صالح أو فارغ:', wkt);
      return null;
    }

    // التحقق مما إذا كان النص يحتوي على POLYGON
    if (!wkt.startsWith('POLYGON')) {
      console.error('⚠️ WKT غير مدعوم:', wkt);
      return null;
    }

    try {
      // استخراج الإحداثيات من نص WKT باستخدام التعبير النمطي regex
      let coordinatesTextMatch = wkt.match(/\(\((.*)\)\)/);
      if (!coordinatesTextMatch) {
        console.error('⚠️ WKT غير صالح:', wkt);
        return null;
      }

      let coordinatesText = coordinatesTextMatch[1];

      // تحويل الإحداثيات إلى مصفوفة أرقام
      let coordinates = coordinatesText.split(',').map(coord => {
        let [lng, lat] = coord.trim().split(' ').map(Number);
        return [lng, lat];
      });

      // إعادة GeoJSON بتنسيق صحيح
      return {
        type: 'Feature',
        geometry: {
          type: 'Polygon',
          coordinates: [coordinates] // Leaflet يتطلب مصفوفة من الحلقات
        },
        properties: {}
      };
    } catch (error) {
      console.error('⚠️ خطأ أثناء تحليل WKT:', error);
      return null;
    }
  }

  function loadGeofences(search = '') {
    $.getJSON(baseUrl + 'admin/settings/geofences/data', { search: search }, function (response) {
      if (response.status === 1) {
        $('#geofence-list').empty(); // مسح القائمة السابقة
        geofenceLayer.clearLayers(); // مسح الحدود السابقة من الخريطة

        let activeLayer = null; // تتبع المنطقة النشطة
        let activeLabel = null; // تتبع التسمية النشطة
        let activeDiv = null; // تتبع العنصر المحدد في القائمة

        response.data.forEach(function (geofence) {
          let geofenceDiv = $(`
                    <div class="mb-4 geofence-item" data-id="${geofence.id}">
                        <div class="card p-3 shadow-sm">
                            <div class="d-flex justify-content-between">
                                <h5>${geofence.name}</h5>
                                <div class="d-flex align-items-center gap-50">
                                    <button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect"
                                            data-id="${geofence.id}"
                                            data-name="${geofence.name}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#largeModal">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button class='btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect'  data-id="${geofence.id}"
                                            data-name="${geofence.name}">
                                        <i class='ti ti-trash'></i>
                                    </button>
                                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end m-0">
                                        <a href="" class="dropdown-item">View</a>
                                        <a href="javascript:;" class="dropdown-item">Suspend</a>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap">
                                ${geofence.description || 'بدون وصف'}
                            </div>
                        </div>
                    </div>
                `);

          $('#geofence-list').append(geofenceDiv);

          if (!geofence.geometry) {
            console.error('⚠️ لا توجد إحداثيات لهذا الـ Geofence:', geofence);
            return;
          }

          try {
            var geoJsonFeature = wktToGeoJSON(geofence.geometry);

            if (geoJsonFeature) {
              var geoLayer = L.geoJSON(geoJsonFeature, {
                style: {
                  color: 'blue',
                  weight: 2,
                  opacity: 0.6,
                  fillOpacity: 0.2
                }
              }).addTo(geofenceLayer);

              geoLayer.geofenceId = geofence.id; // حفظ ID المنطقة داخل الطبقة
              geoLayer.geofenceName = geofence.name; // حفظ ID المنطقة داخل الطبقة
              geoLayer.defaultStyle = { color: 'blue', weight: 2, opacity: 0.6, fillOpacity: 0.2 }; // حفظ النمط الافتراضي

              var center = geoLayer.getBounds().getCenter();

              var label = L.marker(center, {
                icon: L.divIcon({
                  className: 'geofence-label hidden-label', // مخفية افتراضيًا
                  html: `<strong>${geofence.name}</strong>`,
                  iconSize: [100, 40],
                  iconAnchor: [50, 20] // لجعل النص متمركزًا
                })
              }).addTo(geofenceLayer);

              // عند النقر على القائمة
              geofenceDiv.on('click', function () {
                highlightGeofence(geoLayer, label, geofenceDiv);
              });

              // عند النقر على الخريطة
              geoLayer.on('click', function () {
                highlightGeofence(geoLayer, label, geofenceDiv);
              });
            }
          } catch (error) {
            console.error('⚠️ خطأ في تحويل WKT إلى GeoJSON:', error);
          }
        });

        function highlightGeofence(layer, label, listItem) {
          if (activeLayer) {
            activeLayer.setStyle(activeLayer.defaultStyle); // إعادة اللون السابق
            activeLayer.closePopup();
          }
          if (activeLabel) {
            activeLabel._icon.classList.add('hidden-label'); // إخفاء التسمية السابقة
          }
          if (activeDiv) {
            activeDiv.removeClass('selected-geofence'); // إزالة التأثير من العنصر السابق
          }

          // تغيير لون المنطقة إلى الأحمر
          layer.setStyle({ color: 'red', weight: 3, opacity: 1, fillOpacity: 0.4 });

          // إظهار الاسم فقط عند التحديد
          label._icon.classList.remove('hidden-label');

          // فتح Popup عند الضغط
          layer.bindPopup(`<b>${layer.geofenceName}</b>`).openPopup();

          // تمييز العنصر في القائمة
          listItem.addClass('selected-geofence');

          // تحديث المتغيرات النشطة
          activeLayer = layer;
          activeLabel = label;
          activeDiv = listItem;
        }
      }
    });
  }

  $('#search-geo').on('input', function () {
    loadGeofences($(this).val());
  });

  // تحميل البيانات عند تحميل الصفحة
  loadGeofences();

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

  document.addEventListener('formSubmitted', function (event) {
    loadGeofences();
    setTimeout(() => {
      $('#submitModal').modal('hide');
    }, 2000);
  });

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

  $(document).on('click', '.edit-record', function () {
    var id = $(this).data('id');
    var name = $(this).data('name');

    $('#submitModal').modal('show');
    $('#modelTitle').html(`Edit Geo-fence: <span class="bg-info text-white px-2 rounded">${name}</span>`);

    $.get(`${baseUrl}admin/settings/geofences/edit/${id}`, function (data) {
      $('#geo-id').val(data.id);
      $('#geo-name').val(data.name);
      $('#geo-description').val(data.description);
      $('#geo-coordinates').val(data.coordinates_wkt);

      // مسح المضلع السابق قبل رسم الجديد
      drawnItems.clearLayers();

      if (data.coordinates_wkt) {
        var geoJsonFeature = wktToGeoJSON(data.coordinates_wkt);

        if (geoJsonFeature) {
          var polygonLayer = L.geoJSON(geoJsonFeature).addTo(drawnItems);

          // تفعيل أداة التعديل بعد رسم المضلع
          setTimeout(() => {
            polygonLayer.eachLayer(layer => {
              if (layer instanceof L.Polygon) {
                layer.editing.enable(); // 🔥 تفعيل التعديل
              }
            });
          }, 500);
        }
      }
    });
  });

  $(document).on('click', '.delete-record', function () {
    var Id = $(this).data('id');
    var name = $(this).data('name');

    Swal.fire({
      title: `Delete ${name}?`,
      text: 'You will not be able to undo this action!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
        cancelButton: 'btn btn-label-secondary waves-effect waves-light'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        $.ajax({
          url: `${baseUrl}admin/settings/geofences/delete/${Id}`,
          type: 'post',

          success: function (response) {
            if (response.status === 1) {
              Swal.fire({
                title: response.success,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
              });

              loadGeofences();
            } else {
              Swal.fire('Error!', response.error, 'error');
            }
          },
          error: function () {
            Swal.fire('Error!', 'Failed to delete the Geo-fence', 'error');
          }
        });
      }
    });
  });
});
