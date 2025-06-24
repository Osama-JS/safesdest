/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert } from '../ajax';

/* ===========  MapBox  accessToken   ===========*/
mapboxgl.accessToken = 'pk.eyJ1Ijoib3NhbWExOTk4IiwiYSI6ImNtOWk3eXd4MjBkbWcycHF2MDkxYmI3NjcifQ.2axcu5Sk9dx6GX3NtjjAvA';

$(function () {
  var dt_data_table = $('.datatables-users');
  var isInitialLoad = true;

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
      placeholder: __('Select Customer'),
      dropdownParent: $this.parent(),
      closeOnSelect: false,
      ajax: {
        url: baseUrl + 'admin/customers/get/customers',
        dataType: 'json',
        delay: 250, // لتقليل عدد الطلبات عند الكتابة
        processResults: function (data) {
          return {
            results: data.map(function (customer) {
              return {
                id: customer.id,
                text: customer.name
              };
            })
          };
        },
        cache: true
      }
    });
  }

  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/settings/points/data',
        data: function (d) {
          d.search = $('#searchFilter').val();
        }
      },
      columns: [
        { data: '' },
        { data: 'fake_id' },
        { data: 'name' },
        { data: 'address' },
        { data: 'customer' },
        { data: 'status' },
        { data: null }
      ],
      columnDefs: [
        {
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 1,
          targets: 0,
          render: function (data, type, full, meta) {
            if (full.is_parent) {
              return `<i class="fas fa-chevron-down toggle-icon text-primary" style="cursor: pointer; transition: transform 0.3s ease;"></i>`;
            }
            return '';
          }
        },
        {
          targets: 1,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            if (full.is_parent) {
              return '-';
            }
            return `<span>${full.fake_id}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            if (full.is_parent) {
              if (full.parent_type === 'customer') {
                return `<div class="customer-badge">
                  <i class="fas fa-building"></i>
                  ${full.name}
                  <span class="points-count">${full.points_count} ${__('points')}</span>
                </div>`;
              } else {
                return `<div class="general-badge">
                  <i class="fas fa-globe"></i>
                  ${full.name}
                  <span class="points-count">${full.points_count} ${__('points')}</span>
                </div>`;
              }
            }
            return `<div class="point-name">${full.name}</div>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            if (full.is_parent) {
              return '';
            }
            return `<div class="point-address">
              <i class="fas fa-map-marker-alt text-muted me-1"></i>
              ${full.address}
            </div>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            if (full.is_parent) {
              return '';
            }
            return `<span>${full.customer}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            if (full.is_parent) {
              return '-';
            }
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
          title: __('Actions'),
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            if (full.is_parent) {
              return '-';
            }
            return `
              <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-icon edit-record" data-id="${full.id}" data-bs-toggle="modal" data-bs-target="#submitModal">
                  <i class="ti ti-edit"></i>
                </button>
                <button class="btn btn-sm btn-icon delete-record" data-id="${full.id}" data-name="${full.name}">
                  <i class="ti ti-trash"></i>
                </button>
              </div>`;
          }
        }
      ],
      order: [[1, 'asc']],
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

    // إضافة وظائف التوسيع والطي للصفوف الهرمية
    dt_data_table.on('click', '.toggle-icon', function (e) {
      e.stopPropagation();

      var icon = $(this);
      var row = icon.closest('tr');
      var rowData = dt_data.row(row).data();

      if (rowData && rowData.is_parent) {
        var parentId = rowData.id;

        // البحث عن جميع الصفوف التابعة لهذا الصف الرئيسي
        var childRows = $();
        dt_data.rows().every(function () {
          var currentRowData = this.data();
          if (currentRowData && currentRowData.parent_id === parentId) {
            childRows = childRows.add($(this.node()));
          }
        });

        // تبديل حالة الرؤية
        if (icon.hasClass('collapsed')) {
          // توسيع الصفوف - إظهار الصفوف المخفية
          childRows.show();
          icon.removeClass('collapsed');
          icon.css('transform', 'rotate(0deg)');
        } else {
          // طي الصفوف - إخفاء الصفوف الظاهرة
          childRows.hide();
          icon.addClass('collapsed');
          icon.css('transform', 'rotate(-90deg)');
        }
      }
    });

    // إضافة وظيفة النقر على الصف الرئيسي بالكامل
    dt_data_table.on('click', '.parent-row', function (e) {
      // تجنب التفعيل المزدوج إذا تم النقر على الأيقونة مباشرة
      if ($(e.target).hasClass('toggle-icon')) {
        return;
      }

      var toggleIcon = $(this).find('.toggle-icon');
      if (toggleIcon.length) {
        toggleIcon.trigger('click');
      }
    });

    // تطبيق التنسيق على الصفوف بعد رسم الجدول
    dt_data_table.on('draw.dt', function () {
      var api = dt_data;

      api.rows().every(function () {
        var data = this.data();
        var node = $(this.node());

        if (data.is_parent) {
          node.addClass('parent-row');
          node.css({
            'background-color': '#f8f9fa',
            'font-weight': 'bold',
            cursor: 'pointer',
            'border-left': '4px solid #0d6efd'
          });
        } else {
          node.addClass('child-row');
          node.css({
            'background-color': '#ffffff',
            'border-left': '4px solid #dee2e6'
          });
          // إضافة padding للصف الفرعي
          node.find('td:first-child').css('padding-right', '40px');
        }
      });

      // بدء الجدول مع جميع المجموعات مطوية (فقط في التحميل الأولي)
      if (isInitialLoad) {
        setTimeout(function () {
          dt_data_table.find('.toggle-icon').each(function () {
            var icon = $(this);
            if (!icon.hasClass('collapsed')) {
              icon.trigger('click');
            }
          });
          isInitialLoad = false;
        }, 100);
      }
    });

    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_data }));
  }

  $('.dataTables_filter').hide();

  const map = new mapboxgl.Map({
    container: `point-map`,
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
  geocoder.addTo(`#point-geocoder`);
  $(`#point-geocoder .mapboxgl-ctrl-geocoder`).css('width', '100%');
  geocoder.on('result', function (e) {
    const coords = e.result.geometry.coordinates;
    const placeName = e.result.place_name;

    $(`#point-address`).val(placeName);
    selectedCoords = coords;
    showMap(coords);
  });

  // get coordinates by manual using the map
  $(`#point-manual-btn`).on('click', function () {
    showMap();
    map.once('click', function (e) {
      const lng = e.lngLat.lng;
      const lat = e.lngLat.lat;

      selectedCoords = [lng, lat];
      updateMarker([lng, lat]);
    });
  });

  // get coordinates by Current locations using jps on the map
  $(`#point-getCurrentLocation`).on('click', function () {
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
  $(`#point-latitude, #point-longitude`).on('input', updateFromManualCoords);
  function updateFromManualCoords() {
    const lat = parseFloat($(`#point-latitude`).val());
    const lng = parseFloat($(`#point-longitude`).val());

    if (!isNaN(lat) && !isNaN(lng)) {
      selectedCoords = [lng, lat];
      showMap([lng, lat]);
    }
  }

  // Confirm coordinates button
  $(`#confirm-location`).on('click', function () {
    if (selectedCoords) {
      $(`#point-latitude`).val(selectedCoords[1]);
      $(`#point-longitude`).val(selectedCoords[0]);

      setTimeout(() => {
        $(`#point-map`).hide();
        $(`#confirm-location`).hide();
        $(`#point-map-container`).hide();
      }, 1000);
    }
  });

  function showMap(coords = [46.6753, 24.7136]) {
    $(`#point-map`).show();
    $(`#confirm-location`).show();
    $(`#point-map-container`).show();

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

  $('#point-toggle-link-input').on('click', function () {
    $('#point-link-input-wrapper').slideToggle();
  });

  $(`#point-parse-link`).on('click', function () {
    console.log('google');
    const link = $(`#point-map-link`).val().trim();
    const coords = extractCoordinatesFromLink(link);

    if (coords) {
      selectedCoords = coords;
      $(`#point-latitude`).val(selectedCoords[1]);
      $(`#point-longitude`).val(selectedCoords[0]);
      showMap(coords);
    } else {
      showAlert('error', 'تعذر استخراج الإحداثيات من الرابط', 3000, true);
    }
  });
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

  $(document).on('change', '.edit_status', function () {
    var Id = $(this).data('id');

    // التأكد من أن المعرف رقمي (ليس صف رئيسي)
    if (isNaN(Id) || Id.toString().includes('customer-') || Id === 'general') {
      return;
    }

    $.ajax({
      url: `${baseUrl}admin/settings/points/status/${Id}`,
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

  $(document).on('click', '.edit-record', function () {
    var data_id = $(this).data('id'),
      dtrModal = $('.dtr-bs-modal.show');

    // التأكد من أن المعرف رقمي (ليس صف رئيسي)
    if (isNaN(data_id) || data_id.toString().includes('customer-') || data_id === 'general') {
      return;
    }

    if (dtrModal.length) {
      dtrModal.modal('hide');
    }
    $.get(`${baseUrl}admin/settings/points/edit/${data_id}`, function (data) {
      console.log(data.teamsIds);
      $('.text-error').html('');
      $('#point_id').val(data.id);
      $('#point-name').val(data.name);
      $('#point-address').val(data.address);
      $('#point-contact_name').val(data.contact_name);
      $('#point-contact_phone').val(data.contact_phone);
      $('#point-longitude').val(data.longitude);
      $('#point-latitude').val(data.latitude);
      console.log('data', data);

      if (data.customer && data.customer.name) {
        var newOption = new Option(data.customer.name, data.customer_id, true, true);
        $('#point-customer').append(newOption).trigger('change');
      }
      $('#modelTitle').html(`Edit Point: <span class="bg-info text-white px-2 rounded">${data.name}</span>`);
    });
  });

  $(document).on('click', '.delete-record', function () {
    var data_id = $(this).data('id');

    // التأكد من أن المعرف رقمي (ليس صف رئيسي)
    if (isNaN(data_id) || data_id.toString().includes('customer-') || data_id === 'general') {
      return;
    }

    let url = baseUrl + 'admin/settings/points/delete/' + data_id;
    deleteRecord($(this).data('name'), url);
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    $('#point_id').val('');
    $('.text-error').html('');
    $('#tag_id').val('');
    $('#point-customer').val('').trigger('change');
    $('#modelTitle').html(__('Add New Point'));
  });
});
