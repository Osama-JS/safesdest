/**
 * B2B End Clients Management
 */

/* ===========  MapBox  accessToken   ===========*/
mapboxgl.accessToken = 'pk.eyJ1Ijoib3NhbWExOTk4IiwiYSI6ImNtOWk3eXd4MjBkbWcycHF2MDkxYmI3NjcifQ.2axcu5Sk9dx6GX3NtjjAvA';

$(function () {
  var dt_table = $('.datatables-clients');
  var clientForm = $('#client-form');
  var clientModal = $('#clientModal');
  var importModal = $('#importModal');
  var companyFilter = new URLSearchParams(window.location.search).get('company_id');

  if (dt_table.length) {
    var dt = dt_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/b2b/end-clients/data',
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
        { data: 'client_code' },
        { data: 'province' },
        { data: 'phone' },
        { data: 'status' },
        { data: null }
      ],
      columnDefs: [
        {
          targets: 1,
          render: function (data, type, full, meta) {
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
                    <button class="btn btn-sm btn-icon edit-client btn-label-primary me-1" data-id="${full.id}"><i class="ti ti-edit"></i></button>
                    <button class="btn btn-sm btn-icon text-danger delete-client btn-label-danger" data-id="${full.id}"><i class="ti ti-trash"></i></button>
                </div>
            `;
          }
        }
      ],
      dom: '<"row"<"col-md-2"l><"col-md-10 d-flex justify-content-end"B>>t<"row"<"col-md-6"i><"col-md-6"p>>',
      buttons: [
        {
          text: '<i class="ti ti-plus me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">Add End Client</span>',
          className: 'add-new btn btn-primary ms-3',
          action: function () {
            var selectedCo = $('#filter-company').val() || companyFilter;
            if(!selectedCo) {
                Swal.fire('Note', 'Please select a company first in the filter.', 'info');
                return;
            }
            $('#client-id').val('');
            clientForm[0].reset();
            $('#client-company').val(selectedCo).trigger('change');
            $('#pricing-rows').html('');
            clientModal.modal('show');
          }
        },
        {
          text: '<i class="ti ti-file-import me-1"></i> Import Excel',
          className: 'btn btn-outline-success ms-2',
          action: function () {
            var selectedCo = $('#filter-company').val() || companyFilter;
            if(!selectedCo) {
                Swal.fire('Note', 'Please select a company first in the filter.', 'info');
                return;
            }
            $('#import-company-id').val(selectedCo).trigger('change');
            importModal.modal('show');
          }
        }
      ],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Client...',
        emptyTable: 'Please select a company first to view its end clients.'
      }
    });

    $('#filter-company').on('change', function () {
      const companyId = $(this).val();
      if (companyId) {
          window.location.href = baseUrl + 'admin/b2b/end-clients?company_id=' + companyId;
      } else {
          window.location.href = baseUrl + 'admin/b2b/end-clients';
      }
    });

    $('#filter-province, #filter-status').on('change', function () {
      dt.draw();
    });

    $('#filter-search').on('input', function () {
      dt.draw();
    });
  }

  $('.select2').each(function () {
    var $this = $(this);
    $this.select2({
      dropdownParent: $this.closest('.modal-content').length ? $this.closest('.modal-content') : $(document.body)
    });
  });

  // ── Mapbox Logic ───────────────────────────────────────────────
  let map, marker, selectedCoords = null;

  function initMap() {
    if (map) return;
    map = new mapboxgl.Map({
      container: 'client-map',
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

    geocoder.addTo('#client-geocoder');
    $('#client-geocoder .mapboxgl-ctrl-geocoder').css('width', '100%');

    geocoder.on('result', function (e) {
      const coords = e.result.geometry.coordinates;
      const placeName = e.result.place_name;
      selectedCoords = coords;
      $('#client-address').val(placeName); // Auto-fill address
      showMap(coords);
    });

    map.on('click', function (e) {
      if($('#client-map-container').is(':visible')) {
        updateMarker([e.lngLat.lng, e.lngLat.lat]);
      }
    });

    // --- Link Parsing Logic ---
    $('#client-toggle-link-input').on('click', function () {
      $('#client-link-input-wrapper').slideToggle();
    });

    $('#client-parse-link').on('click', function () {
      const link = $('#client-map-link').val().trim();
      const coords = extractCoordinatesFromLink(link);

      if (coords) {
        selectedCoords = coords;
        $('#client-lat').val(coords[1]);
        $('#client-lng').val(coords[0]);
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
    $('#client-map-container').show();
    if (!map) initMap();
    
    setTimeout(() => {
      map.resize();
      map.flyTo({ center: coords, zoom: 14 });
      updateMarker(coords);
    }, 200);
  }

  function updateMarker(coords) {
    selectedCoords = coords;
    $('#client-lat').val(coords[1]);
    $('#client-lng').val(coords[0]);

    if (marker) marker.remove();
    marker = new mapboxgl.Marker({ draggable: true }).setLngLat(coords).addTo(map);

    marker.on('dragend', function () {
      const lngLat = marker.getLngLat();
      selectedCoords = [lngLat.lng, lngLat.lat];
      $('#client-lat').val(lngLat.lat);
      $('#client-lng').val(lngLat.lng);
    });
  }

  $('#client-manual-btn').on('click', function() {
    const lat = parseFloat($('#client-lat').val());
    const lng = parseFloat($('#client-lng').val());
    if(!isNaN(lat) && !isNaN(lng)) {
      showMap([lng, lat]);
    } else {
      showMap();
    }
  });

  $('#client-getCurrentLocation').on('click', function () {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function (position) {
        showMap([position.coords.longitude, position.coords.latitude]);
      });
    } else {
      Swal.fire('Error', 'Geolocation is not supported by your browser', 'error');
    }
  });

  $('#confirm-client-location').on('click', function() {
    $('#client-map-container').slideUp();
  });

  $('#client-lat, #client-lng').on('input', function() {
    const lat = parseFloat($('#client-lat').val());
    const lng = parseFloat($('#client-lng').val());
    if (!isNaN(lat) && !isNaN(lng)) {
      updateMarker([lng, lat]);
      map.flyTo({ center: [lng, lat] });
    }
  });

  // Handle Dynamic Pricing Generation
  $('#client-company').on('change', function () {
    var companyId = $(this).val();
    if (companyId) {
      $.get(baseUrl + 'admin/b2b/get-warehouses/' + companyId, function (warehouses) {
        var html = '';
        warehouses.forEach(function (w) {
          html += `<div class="card mb-2 border shadow-none">
                <div class="card-header py-2 bg-light"><strong>${w.name}</strong></div>
                <div class="card-body py-2">
                    <div class="row">
                        ${vehicleSizes.map(v => `
                            <div class="col-md-4 mb-2">
                                <label class="form-label small">${v.name}</label>
                                <input type="number" name="pricing[${w.id}][${v.id}]" class="form-control form-control-sm client-pricing-input" data-warehouse="${w.id}" data-vehicle="${v.id}" placeholder="Price (SAR)">
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>`;
        });
        $('#pricing-rows').html(html);
      });
    } else {
      $('#pricing-rows').html('');
    }
  });

  clientForm.on('submit', function (e) {
    e.preventDefault();
    var formData = $(this).serialize();
    
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.text-error').text('');

    $.post(baseUrl + 'admin/b2b/end-clients/store', formData, function (res) {
      if (res.status === 1) {
        Swal.fire('Success', res.success, 'success');
        clientModal.modal('hide');
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

  $(document).on('click', '.edit-client', function () {
    var id = $(this).data('id');
    $.get(baseUrl + 'admin/b2b/end-clients/' + id, function (data) {
      var client = data.client;
      $('#client-id').val(client.id);
      $('#client-company').val(client.company_id).trigger('change');
      $('#client-name').val(client.name);
      $('#client-province').val(client.province_id).trigger('change');
      $('#client-phone').val(client.phone);
      $('#client-phone2').val(client.phone_2);
      $('#client-address').val(client.address);
      $('#client-notes').val(client.notes);
      $('#client-code').val(client.client_code);
      $('#client-lat').val(client.latitude);
      $('#client-lng').val(client.longitude);

      if(client.latitude && client.longitude) {
        showMap([parseFloat(client.longitude), parseFloat(client.latitude)]);
      } else {
        $('#client-map-container').hide();
      }

      // Wait for the company change handler to populate warehouse rows, then set prices
      setTimeout(function () {
        $('.client-pricing-input').val('');
        if (data.pricing) {
          data.pricing.forEach(function (p) {
            $(`.client-pricing-input[data-warehouse="${p.warehouse_id}"][data-vehicle="${p.vehicle_size_id}"]`).val(p.price);
          });
        }
      }, 1000);

      clientModal.modal('show');
    });
  });

  // Excel Import
  $('#import-form').on('submit', function (e) {
    e.preventDefault();
    var formData = new FormData(this);
    var btn = $('#import-submit-btn');
    
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.text-error').text('');

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

    $.ajax({
      url: baseUrl + 'admin/b2b/end-clients/import',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (res) {
        btn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Start Bulk Upload');
        if (res.status === 1) {
          Swal.fire('Success', res.success, 'success');
          importModal.modal('hide');
          dt.draw();
        } else if (res.status === 0) {
          if (typeof res.error === 'object') {
            $.each(res.error, function (key, messages) {
              var field = $(`[name="${key}"]`);
              field.addClass('is-invalid');
              $(`.${key}-error`).text(messages[0]);
            });
          }
          var errorMsg = (typeof res.error === 'string' ? res.error : 'Validation failed') + '<br>';
          if (res.failures) {
            res.failures.forEach(function (f) {
              errorMsg += `Row ${f.row}: ${f.errors.join(', ')}<br>`;
            });
          }
          Swal.fire({ icon: 'error', title: 'Import Failed', html: errorMsg });
        } else {
          Swal.fire('Error', res.error || 'Import failed', 'error');
        }
      },
      error: function () {
        btn.prop('disabled', false).html('<i class="ti ti-upload me-1"></i> Start Bulk Upload');
        Swal.fire('Error', 'Server error occurred', 'error');
      }
    });
  });

  $(document).on('click', '.delete-client', function () {
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
          url: baseUrl + 'admin/b2b/end-clients/' + id,
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

  clientModal.on('hidden.bs.modal', function () {
    clientForm[0].reset();
    $('#client-id').val('');
    $('.is-invalid').removeClass('is-invalid');
    $('.text-error').text('');
    $('#client-map-container').hide();
    $('#client-link-input-wrapper').hide();
    $('#client-map-link').val('');
    if (marker) marker.remove();
    marker = null;
  });
});
