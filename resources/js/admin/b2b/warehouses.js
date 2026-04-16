/**
 * B2B Warehouses Management
 */

/* ===========  MapBox  accessToken   ===========*/
mapboxgl.accessToken = 'pk.eyJ1Ijoib3NhbWExOTk4IiwiYSI6ImNtOWk3eXd4MjBkbWcycHF2MDkxYmI3NjcifQ.2axcu5Sk9dx6GX3NtjjAvA';

$(function () {
  var dt_table = $('.datatables-warehouses');
  var warehouseForm = $('#warehouse-form');
  var warehouseModal = $('#warehouseModal');
  var companyFilter = new URLSearchParams(window.location.search).get('company_id');

  if (dt_table.length) {
    var dt = dt_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/b2b/warehouses/data',
        data: function (d) {
          d.company_id = $('#filter-company').val() || companyFilter;
          d.province_id = $('#filter-province').val();
          d.status = $('#filter-status').val();
          d.q = $('#filter-search').val();
        }
      },
      columns: [
        { data: 'id' },
        { data: 'company_name' },
        { data: 'name' },
        { data: 'province' },
        { data: 'address' },
        { data: 'status' },
        { data: null }
      ],
      columnDefs: [
        {
            targets: 1,
            render: function(data, type, full, meta) {
                return `<span class="fw-medium text-heading">${data}</span>`;
            }
        },
        {
          targets: -2,
          render: function (data, type, full, meta) {
            let badge = data === 'active' ? 'bg-label-success' : 'bg-label-danger';
            return `<span class="badge ${badge} text-capitalize">${data}</span>`;
          }
        },
        {
          targets: -1,
          title: 'Actions',
          orderable: false,
          render: function (data, type, full, meta) {
            return `
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-icon edit-warehouse btn-label-primary me-1" data-id="${full.id}"><i class="ti ti-edit"></i></button>
                    <button class="btn btn-sm btn-icon text-danger delete-warehouse btn-label-danger" data-id="${full.id}"><i class="ti ti-trash"></i></button>
                </div>
            `;
          }
        }
      ],
      dom: '<"row"<"col-md-2"l><"col-md-10 d-flex justify-content-end"B>>t<"row"<"col-md-6"i><"col-md-6"p>>',
      buttons: [
        {
          text: '<i class="ti ti-plus me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">Add Warehouse</span>',
          className: 'add-new btn btn-primary ms-3',
          action: function () {
            var selectedCo = $('#filter-company').val() || companyFilter;
            if(!selectedCo) {
                Swal.fire('Note', 'Please select a company first in the filter.', 'info');
                return;
            }
            $('#warehouse-id').val('');
            warehouseForm[0].reset();
            $('#warehouse-company').val(selectedCo).trigger('change');
            warehouseModal.modal('show');
          }
        }
      ],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Warehouse...',
        emptyTable: 'Please select a company first to view its warehouses.'
      }
    });

    $('#filter-company').on('change', function () {
        const companyId = $(this).val();
        if (companyId) {
            window.location.href = baseUrl + 'admin/b2b/warehouses?company_id=' + companyId;
        } else {
            window.location.href = baseUrl + 'admin/b2b/warehouses';
        }
    });

    $('#filter-search').on('input', function () {
        dt.draw();
    });

    $('#filter-province, #filter-status').on('change', function () {
        dt.draw();
    });
  }

  $('.select2').each(function() {
      $(this).select2({
          dropdownParent: $(this).closest('.modal-content').length ? warehouseModal : $(document.body)
      });
  });

  // ── Mapbox Logic ───────────────────────────────────────────────
  let map, marker, selectedCoords = null;

  function initMap() {
    if (map) return;
    map = new mapboxgl.Map({
      container: 'warehouse-map',
      style: 'mapbox://styles/mapbox/streets-v12',
      center: [46.6753, 24.7136],
      zoom: 10
    });

    const geocoder = new MapboxGeocoder({
      accessToken: mapboxgl.accessToken,
      mapboxgl: mapboxgl,
      placeholder: __('Search for the location...'),
      marker: false,
      flyTo: false
    });

    geocoder.addTo('#warehouse-geocoder');
    $('#warehouse-geocoder .mapboxgl-ctrl-geocoder').css('width', '100%');

    geocoder.on('result', function (e) {
      const coords = e.result.geometry.coordinates;
      const placeName = e.result.place_name;
      selectedCoords = coords;
      $('#warehouse-address').val(placeName); // Auto-fill address
      showMap(coords);
    });

    map.on('click', function (e) {
      if($('#warehouse-map-container').is(':visible')) {
        updateMarker([e.lngLat.lng, e.lngLat.lat]);
      }
    });

    // --- Link Parsing Logic ---
    $('#warehouse-toggle-link-input').on('click', function () {
      $('#warehouse-link-input-wrapper').slideToggle();
    });

    $('#warehouse-parse-link').on('click', function () {
      const link = $('#warehouse-map-link').val().trim();
      const coords = extractCoordinatesFromLink(link);

      if (coords) {
        selectedCoords = coords;
        $('#warehouse-lat').val(coords[1]);
        $('#warehouse-lng').val(coords[0]);
        showMap(coords);
      } else {
        Swal.fire(__('Error'), __('Could not extract coordinates from link'), 'error');
      }
    });
  }

  function extractCoordinatesFromLink(link) {
    const regex = /([-+]?\d{1,3}(?:\.\d+)?),\s*([-+]?\d{1,3}(?:\.\d+)?)/;
    const match = link.match(regex);
    if (match) {
      const lat = parseFloat(match[1]);
      const lng = parseFloat(match[2]);
      if (!isNaN(lat) && !isNaN(lng)) return [lng, lat];
    }
    return null;
  }

  function showMap(coords = [46.6753, 24.7136]) {
    $('#warehouse-map-container').show();
    if (!map) initMap();
    
    setTimeout(() => {
      map.resize();
      map.flyTo({ center: coords, zoom: 14 });
      updateMarker(coords);
    }, 200);
  }

  function updateMarker(coords) {
    selectedCoords = coords;
    $('#warehouse-lat').val(coords[1]);
    $('#warehouse-lng').val(coords[0]);

    if (marker) marker.remove();
    marker = new mapboxgl.Marker({ draggable: true }).setLngLat(coords).addTo(map);

    marker.on('dragend', function () {
      const lngLat = marker.getLngLat();
      selectedCoords = [lngLat.lng, lngLat.lat];
      $('#warehouse-lat').val(lngLat.lat);
      $('#warehouse-lng').val(lngLat.lng);
    });
  }

  $('#warehouse-manual-btn').on('click', function() {
    const lat = parseFloat($('#warehouse-lat').val());
    const lng = parseFloat($('#warehouse-lng').val());
    if(!isNaN(lat) && !isNaN(lng)) {
      showMap([lng, lat]);
    } else {
      showMap();
    }
  });

  $('#warehouse-getCurrentLocation').on('click', function () {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function (position) {
        showMap([position.coords.longitude, position.coords.latitude]);
      });
    } else {
      Swal.fire('Error', 'Geolocation is not supported by your browser', 'error');
    }
  });

  $('#confirm-warehouse-location').on('click', function() {
    $('#warehouse-map-container').slideUp();
  });

  $('#warehouse-lat, #warehouse-lng').on('input', function() {
    const lat = parseFloat($('#warehouse-lat').val());
    const lng = parseFloat($('#warehouse-lng').val());
    if (!isNaN(lat) && !isNaN(lng)) {
      updateMarker([lng, lat]);
      map.flyTo({ center: [lng, lat] });
    }
  });

  warehouseForm.on('submit', function (e) {
    e.preventDefault();
    var formData = $(this).serialize();
    
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.text-error').text('');

    $.post(baseUrl + 'admin/b2b/warehouses/store', formData, function (res) {
      if (res.status === 1) {
        Swal.fire('Success', res.success, 'success');
        warehouseModal.modal('hide');
        dt.draw();
      } else if (res.status === 0) {
        // Validation Errors
        if (typeof res.error === 'object') {
          $.each(res.error, function (key, messages) {
            var field = $(`[name="${key}"]`);
            field.addClass('is-invalid');
            $(`.${key}-error`).text(messages[0]);
          });
          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please check the highlighted fields.',
            customClass: { confirmButton: 'btn btn-primary' }
          });
        } else {
          Swal.fire('Error', res.error, 'error');
        }
      } else {
        Swal.fire('Error', res.error || 'Something went wrong', 'error');
      }
    });
  });

  $(document).on('click', '.edit-warehouse', function () {
    var id = $(this).data('id');
    $.get(baseUrl + 'admin/b2b/warehouses/' + id, function (data) {
      $('#warehouse-id').val(data.id);
      $('#warehouse-company').val(data.company_id).trigger('change');
      $('#warehouse-name').val(data.name);
      $('#warehouse-province').val(data.province_id).trigger('change');
      $('#warehouse-address').val(data.address);
      $('#warehouse-contact-name').val(data.contact_name);
      $('#warehouse-contact-phone').val(data.contact_phone);
      $('#warehouse-lat').val(data.latitude);
      $('#warehouse-lng').val(data.longitude);

      if(data.latitude && data.longitude) {
        showMap([parseFloat(data.longitude), parseFloat(data.latitude)]);
      } else {
        $('#warehouse-map-container').hide();
      }

      // Load pricing
      $('.pricing-input').val('');
      if (data.route_pricings) {
        data.route_pricings.forEach(function (p) {
          $(`.pricing-input[data-province="${p.destination_province_id}"]`).val(p.default_price);
        });
      }

      warehouseModal.modal('show');
    });
  });

  $(document).on('click', '.delete-warehouse', function () {
    var id = $(this).data('id');
    Swal.fire({
      title: 'Are you sure?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: baseUrl + 'admin/b2b/warehouses/' + id,
          type: 'DELETE',
          data: { _token: $('meta[name="csrf-token"]').attr('content') },
          success: function (res) {
            Swal.fire('Deleted!', res.success, 'success');
            dt.draw();
          }
        });
      }
    });
  });

  warehouseModal.on('hidden.bs.modal', function () {
    warehouseForm[0].reset();
    $('#warehouse-id').val('');
    $('.is-invalid').removeClass('is-invalid');
    $('.text-error').text('');
    $('#warehouse-map-container').hide();
    $('#warehouse-link-input-wrapper').hide();
    $('#warehouse-map-link').val('');
    if (marker) marker.remove();
    marker = null;
  });
});
