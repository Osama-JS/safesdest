/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, showFormModal } from '../ajax';

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
          title: 'Actions',
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
        searchPlaceholder: 'Search...',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="ti ti-chevron-right"></i>',
          previous: '<i class="ti ti-chevron-left"></i>'
        }
      },
      buttons: [
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="Search driver" />
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

  /* ==================== Map Control   ======================== */
  const verticalExample = document.getElementById('vertical-scroll');
  if (verticalExample) {
    new PerfectScrollbar(verticalExample, { wheelPropagation: false });
  }

  mapboxgl.accessToken = 'pk.eyJ1Ijoib3NhbWExOTk4IiwiYSI6ImNtOWk3eXd4MjBkbWcycHF2MDkxYmI3NjcifQ.2axcu5Sk9dx6GX3NtjjAvA'; // حط مفتاحك هنا

  const map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/mapbox/streets-v12',
    center: [46.6753, 24.7136],
    zoom: 10
  });

  let markers = [];
  let coords = [];

  function updateCoordinatesInput() {
    $('#block-coordinates').val(JSON.stringify(coords));
  }

  function drawLine() {
    if (map.getSource('line')) {
      map.getSource('line').setData({
        type: 'Feature',
        geometry: {
          type: 'LineString',
          coordinates: coords
        }
      });
    } else {
      map.addSource('line', {
        type: 'geojson',
        data: {
          type: 'Feature',
          geometry: {
            type: 'LineString',
            coordinates: coords
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
          'line-color': '#ff0000',
          'line-width': 4
        }
      });
    }
  }

  map.on('click', function (e) {
    const blockType = document.getElementById('block-type').value;

    if (!blockType) {
      showAlert('warning', 'Choose the Block type first', 2000, true);
      return;
    }

    const lngLat = [e.lngLat.lng, e.lngLat.lat];

    if (blockType === 'point') {
      // إذا كان فيه ماركر موجود، فقط حركه
      if (markers.length > 0) {
        markers[0].setLngLat(lngLat);
        coords[0] = lngLat;
      } else {
        const marker = new mapboxgl.Marker({ draggable: true }).setLngLat(lngLat).addTo(map);

        marker.on('dragend', function () {
          const newLngLat = marker.getLngLat();
          coords[0] = [newLngLat.lng, newLngLat.lat];
          updateCoordinatesInput();
        });

        markers.push(marker);
        coords.push(lngLat);
      }

      updateCoordinatesInput();

      // إذا كان فيه خط مرسوم سابقاً احذفه
      if (map.getLayer('line')) map.removeLayer('line');
      if (map.getSource('line')) map.removeSource('line');
    } else if (blockType === 'line') {
      // عادي يسمح بإضافة عدة نقاط
      const marker = new mapboxgl.Marker({ draggable: true }).setLngLat(lngLat).addTo(map);

      marker.on('dragend', function () {
        const newLngLat = marker.getLngLat();
        const index = markers.indexOf(marker);
        if (index !== -1) {
          coords[index] = [newLngLat.lng, newLngLat.lat];
          updateCoordinatesInput();
          drawLine();
        }
      });

      markers.push(marker);
      coords.push(lngLat);
      updateCoordinatesInput();
      drawLine();
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

  $(document).on('click', '.edit-record', function () {
    var data_id = $(this).data('id'),
      dtrModal = $('.dtr-bs-modal.show');
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }
    $.get(`${baseUrl}admin/settings/blockages/edit/${data_id}`, function (data) {
      console.log(data.teamsIds);
      $('.text-error').html('');
      $('#block_id').val(data.id);
      $('#block-type').val(data.type);
      $('#block-description').val(data.description);
      $('#block-coordinates').val(data.coordinates);

      $('#modelTitle').html(`Edit Point: <span class="bg-info text-white px-2 rounded">${data.name}</span>`);
    });
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
