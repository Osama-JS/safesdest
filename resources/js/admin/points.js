/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert } from '../ajax';

/* ===========  MapBox  accessToken   ===========*/
mapboxgl.accessToken = 'pk.eyJ1Ijoib3NhbWExOTk4IiwiYSI6ImNtOWk3eXd4MjBkbWcycHF2MDkxYmI3NjcifQ.2axcu5Sk9dx6GX3NtjjAvA';

$(function () {
  var dt_data_table = $('.datatables-users');

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
      placeholder: 'Select Customer',
      dropdownParent: $this.parent(),
      closeOnSelect: false,
      ajax: {
        url: baseUrl + 'admin/customers/get/customers', // ← غيّر هذا حسب رابط API عندك
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
            return `<span>${full.name}</span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span>${full.address}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span>${full.customer}</span>`;
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
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `
              <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-icon edit-record " data-id="${full.id}" data-bs-toggle="modal" data-bs-target="#submitModal">
                  <i class="ti ti-edit"></i>
                </button>
                <button class="btn btn-sm btn-icon delete-record " data-id="${full.id}"  data-name="${full.name}">
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
        searchPlaceholder: 'Search...',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="ti ti-chevron-right"></i>',
          previous: '<i class="ti ti-chevron-left"></i>'
        }
      },
      buttons: [
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="Search..." />
          </label>`
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data.name;
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

      var newOption = new Option(data.customer.name, data.customer_id, true, true);
      $('#point-customer').append(newOption).trigger('change');
      $('#modelTitle').html(`Edit Point: <span class="bg-info text-white px-2 rounded">${data.name}</span>`);
    });
  });

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/settings/points/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    $('#point_id').val('');
    $('.text-error').html('');
    $('#tag_id').val('');
    $('#point-customer').val('').trigger('change');
    $('#modelTitle').html('Add New Tag');
  });
});
