/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, showFormModal } from '../ajax';
import { mapsConfig } from '../mapbox-helper';

$(function () {
  var dt_data_table = $('.datatables-blockages');
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  /* ==================== Datatable Control  ======================== */

  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/settings/blockages/data',
        data: function (d) {
          d.search = $('#searchFilter').val();
        }
      },
      columns: [
        { data: '' },
        { data: 'fake_id' },
        { data: 'type' },
        { data: 'description' },
        { data: 'coordinates' },
        { data: 'status' },
        { data: 'created_at' },
        { data: null }
      ],
      columnDefs: [
        {
          targets: 0,
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 1,
          render: function () {
            return '';
          }
        },
        {
          targets: 1,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `<span>${full.fake_id}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return `<span>${full.type}</span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span>${full.description}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span>${full.coordinates}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            var html = `<label class="switch switch-success">
              <input type="checkbox" class="switch-input edit_status" data-id=${full['id']} ${full['status'] == 1 ? 'checked' : ''} />
              <span class="switch-toggle-slider">
                <span class="switch-on">
                  <i class="ti ti-check"></i>
                </span>
                <span class="switch-off">
                  <i class="ti ti-x"></i>
                </span>
              </span>
            </label>`;
            return html;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            return full.created_at;
          }
        },
        {
          targets: 7,
          title: __('Actions'),
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `
              <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-icon edit-record " data-id="${full.id}" data-bs-toggle="modal" data-bs-target="#submitModal">
                  <i class="ti ti-edit"></i>
                </button>
                <button class="btn btn-sm btn-icon delete-record " data-id="${full.id}"  data-name="${full.type}">
                  <i class="ti ti-trash"></i>
                </button>
              </div>`;
          }
        }
      ],
      order: [[1, 'desc']],
      dom:
        '<"row"' +
        '<"col-md-2"l>' +
        '<"col-md-10 d-flex justify-content-end"fB>' +
        '>t' +
        '<"row mt-3"' +
        '<"col-md-6"i>' +
        '<"col-md-6"p>' +
        '>',
      lengthMenu: [10, 25, 50, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: __('Search...'),
        info: __('Showing _START_ to _END_ of _TOTAL_ entries'),
        paginate: {
          next: '<i class="ti ti-chevron-right"></i>',
          previous: '<i class="ti ti-chevron-left"></i>'
        }
      },
      buttons: [
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="${__('Search...')}" />
          </label>`
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return __('Details of') + ' ' + data.name;
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col) {
              return col.title
                ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                      <td>${col.title}:</td>
                      <td>${col.data}</td>
                   </tr>`
                : '';
            }).join('');
            return $('<table class="table"/><tbody />').append(data);
          }
        }
      }
    });

    $('#searchFilter').on('input', function () {
      dt_data.draw();
    });

    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_data }));
  }
  $('.dataTables_filter').hide();

  /* ==================== Map Control   ======================== */

  // 🔵 إعداد السكروول
  const verticalExample = document.getElementById('vertical-scroll');
  if (verticalExample) {
    new PerfectScrollbar(verticalExample, { wheelPropagation: false });
  }
  mapboxgl.setRTLTextPlugin(
    'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-rtl-text/v0.2.3/mapbox-gl-rtl-text.js',
    null,
    true // تحميل فقط عند الحاجة (lazy load)
  );

  mapboxgl.accessToken = mapsConfig.token;

  const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/' + mapsConfig.style,
    center: mapsConfig.center,
    zoom: 10
  });

  $('#submitModal').on('shown.bs.modal', function () {
    map.resize();
  });

  let markers = [];
  let coords = [];
  let preventClick = false;

  // تحديث حقل الإحداثيات
  function updateCoordinatesInput() {
    const input = document.getElementById('coordinates');
    if (input) input.value = JSON.stringify(coords);
  }

  // حذف الخط إذا كان مرسومًا
  function removeLineIfExists() {
    if (map.getLayer('line')) map.removeLayer('line');
    if (map.getSource('line')) map.removeSource('line');
  }

  // رسم خط باستخدام الإحداثيات
  async function drawLine() {
    if (coords.length < 2) {
      removeLineIfExists();
      return;
    }

    const snappedCoords = await snapMultipleToRoad(coords);
    if (!snappedCoords || snappedCoords.length < 2) return;

    removeLineIfExists();

    map.addSource('line', {
      type: 'geojson',
      data: {
        type: 'Feature',
        geometry: {
          type: 'LineString',
          coordinates: snappedCoords
        }
      }
    });

    map.addLayer({
      id: 'line',
      type: 'line',
      source: 'line',
      layout: {
        'line-join': 'round',
        'line-cap': 'round'
      },
      paint: {
        'line-color': '#FF0000',
        'line-width': 4
      }
    });

    coords = snappedCoords;
    updateCoordinatesInput();
  }

  // سناب نقطة واحدة للطريق
  async function snapToRoad(lngLat) {
    const fakePath = `${lngLat[0]},${lngLat[1]};${lngLat[0]},${lngLat[1]}`;
    const url = `https://api.mapbox.com/matching/v5/mapbox/driving/${fakePath}?geometries=geojson&access_token=${mapboxgl.accessToken}`;

    try {
      const response = await fetch(url);
      const data = await response.json();

      if (data.matchings?.length > 0) {
        return data.matchings[0].geometry.coordinates[0];
      } else {
        return null;
      }
    } catch (err) {
      console.error('Snap failed:', err);
      return null;
    }
  }

  // سناب عدة نقاط دفعة واحدة لرسم خط
  async function snapMultipleToRoad(coords) {
    if (coords.length < 2) return coords;

    const path = coords.map(c => `${c[0]},${c[1]}`).join(';');
    const url = `https://api.mapbox.com/matching/v5/mapbox/driving/${path}?geometries=geojson&access_token=${mapboxgl.accessToken}`;

    try {
      const response = await fetch(url);
      const data = await response.json();

      if (data.matchings?.length > 0) {
        return data.matchings[0].geometry.coordinates;
      } else {
        console.warn('لم يتم العثور على طريق للخط!');
        return coords;
      }
    } catch (err) {
      console.error('Snap failed:', err);
      return coords;
    }
  }

  // إنشاء ماركر وتفعيل الحذف والسحب
  function createMarker(lngLat) {
    const marker = new mapboxgl.Marker({ draggable: true }).setLngLat(lngLat).addTo(map);

    marker.on('dragend', async () => {
      const newLngLat = [marker.getLngLat().lng, marker.getLngLat().lat];
      const snapped = await snapToRoad(newLngLat);
      if (snapped) {
        marker.setLngLat(snapped);
        const index = markers.indexOf(marker);
        if (index !== -1) coords[index] = snapped;
        updateCoordinatesInput();

        if ($('#block-type').val() === 'line') {
          await drawLine();
        }
      }
    });

    enableMarkerDelete(marker);
    return marker;
  }

  // حذف النقطة عند النقر المزدوج عليها
  function enableMarkerDelete(marker) {
    marker.getElement().addEventListener('dblclick', e => {
      e.stopPropagation();
      preventClick = true;
      setTimeout(() => (preventClick = false), 250);

      const index = markers.indexOf(marker);
      if (index !== -1) {
        marker.remove();
        markers.splice(index, 1);
        coords.splice(index, 1);
        updateCoordinatesInput();

        if ($('#block-type').val() === 'line') {
          drawLine();
        } else {
          removeLineIfExists();
        }
      }
    });
  }

  // منع النقر بعد dblclick
  map.on('dblclick', () => {
    preventClick = true;
    setTimeout(() => (preventClick = false), 250);
  });

  // التعامل مع النقر على الخريطة
  map.on('click', async e => {
    if (preventClick) return;

    const blockType = $('#block-type').val();
    if (!blockType) {
      alert('اختر نوع الإغلاق أولاً!');
      return;
    }

    let lngLat = [e.lngLat.lng, e.lngLat.lat];
    const snapped = await snapToRoad(lngLat);

    if (!snapped) {
      alert('النقطة لا تقع على طريق، يرجى اختيار نقطة على الطريق.');
      return;
    }

    lngLat = snapped;

    if (blockType === 'point') {
      if (markers.length > 0) {
        markers[0].setLngLat(lngLat);
        coords[0] = lngLat;
      } else {
        const marker = createMarker(lngLat);
        markers.push(marker);
        coords.push(lngLat);
      }
      updateCoordinatesInput();
      removeLineIfExists();
    } else if (blockType === 'line') {
      const marker = createMarker(lngLat);
      markers.push(marker);
      coords.push(lngLat);
      updateCoordinatesInput();
      await drawLine();
    }
  });

  /* ==================== Actions  Control   ======================== */
  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');

    setTimeout(() => {
      $('#submitModal').modal('hide');
    }, 2000);

    if (dt_data) {
      dt_data.draw();
    }
  });

  document.addEventListener('deletedSuccess', function (event) {
    if (dt_data) {
      dt_data.draw();
    }
  });

  $(document).on('click', '.edit-record', async function () {
    const data_id = $(this).data('id');
    const dtrModal = $('.dtr-bs-modal.show');
    if (dtrModal.length) dtrModal.modal('hide');

    // تنظيف الخريطة قبل التعديل
    markers.forEach(m => m.remove());
    markers = [];
    coords = [];
    removeLineIfExists();

    // جلب البيانات
    const data = await $.get(`${baseUrl}admin/settings/blockages/edit/${data_id}`);
    console.log(data.teamsIds);

    // تعبئة الحقول
    $('.text-error').html('');
    $('#block_id').val(data.id);
    $('#block-type').val(data.type);
    $('#block-description').val(data.description);
    $('#coordinates').val(data.coordinates);
    $('#modelTitle').html(__('Add a new Blockage'));

    // تحويل الإحداثيات من JSON إلى مصفوفة
    try {
      const storedCoords = JSON.parse(data.coordinates);
      if (!Array.isArray(storedCoords) || storedCoords.length === 0) return;

      // أضف النقاط إلى الخريطة
      for (const lngLat of storedCoords) {
        const marker = createMarker(lngLat); // تستخدم نفس createMarker من الكود السابق
        markers.push(marker);
        coords.push(lngLat);
      }

      // في حالة LINE، ارسم المسار
      if (data.type === 'line') {
        await drawLine();
      }
    } catch (e) {
      console.error('فشل في تحليل الإحداثيات:', e);
    }
  });

  $(document).on('change', '.edit_status', function () {
    var Id = $(this).data('id');
    $.ajax({
      url: `${baseUrl}admin/settings/blockages/status/${Id}`,
      type: 'post',

      success: function (response) {
        if (response.status != 1) {
          showAlert('error', data.error, 10000, true);
        }
      },
      error: function () {
        showAlert('Error!', 'Failed Request', 'error');
      }
    });
  });

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/settings/blockages/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });
});
