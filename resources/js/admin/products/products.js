/**
 * Page Products List
 */

'use strict';
import { deleteRecord, showAlert, showFormModal } from '../../ajax';

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

  // Products datatable
  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/products/data',
        data: function (d) {
          d.status = $('#statusFilter').val();
          d.search = $('#searchFilter').val();
        },
        dataSrc: function (json) {
          $('#total').text(json.summary.total);
          $('#total-active').text(json.summary.total_active);
          $('#total-blocked').text(json.summary.total_blocked);
          return json.data;
        }
      },
      columns: [
        { data: '' }, // للـ control (responsive)
        { data: 'fake_id' }, // الترقيم التسلسلي
        { data: 'image' }, // الصورة
        { data: 'name' }, // الاسم
        { data: 'code' }, // الكود
        { data: 'price' }, // السعر
        { data: 'minimum_order' }, // الحد الأدنى
        { data: 'increase' }, // الزيادة
        { data: 'status' }, // الحالة
        { data: 'created_at' }, // تاريخ الإنشاء
        { data: null } // actions
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
            if (full.image === null || full.image === '') {
              return `<div class="avatar bg-label-secondary rounded-circle">
                        <span class="avatar-initial">
                          <i class="ti ti-package ti-sm"></i>
                        </span>
                      </div>`;
            } else {
              return `<div class="avatar rounded">
                        <img src="${full.image}" class="rounded object-cover" style="width: 40px; height: 40px;"/>
                      </div>`;
            }
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<div class="d-flex flex-column">
                      <span class="fw-medium">${full.name}</span>
                      <small class="text-muted">${full.description || ''}</small>
                    </div>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span class="badge bg-label-info">${full.code}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return `<span class="fw-medium">${full.price} ${__('SAR')}</span>`;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            return `<span>${full.min}</span>`;
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            return `<span>${full.increase}</span>`;
          }
        },
        {
          targets: 8,
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
          targets: 9,
          render: function (data, type, full, meta) {
            return `<span>${full.created_at}</span>`;
          }
        },
        {
          targets: -1,
          title: __('Actions'),
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center gap-50">' +
              `<button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${full.id}" data-bs-toggle="tooltip" title="${__('Edit')}"><i class="ti ti-edit"></i></button>` +
              `<a href="${baseUrl}admin/products/details/${full.id}/${full.name}" class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect" data-bs-toggle="tooltip" title="${__('View Details')}"><i class="ti ti-eye"></i></a>` +
              `<button class="btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect" data-id="${full.id}" data-name="${full.name}" data-bs-toggle="tooltip" title="${__('Delete')}"><i class="ti ti-trash"></i></button>` +
              '</div>'
            );
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
      lengthMenu: [10, 20, 50, 70, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: __('Search Products'),
        info: __('Displaying _START_ to _END_ of _TOTAL_ entries'),
        paginate: {
          next: __('Next'),
          previous: __('Previous')
        }
      },
      buttons: [
        `<label class='me-2'>
          <select id='statusFilter' class='form-select d-inline-block w-auto ms-2 mt-5'>
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Blocked</option>
          </select>
        </label>`,
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="Search Products" />
          </label>`
      ]
    });

    $('#statusFilter').on('change', function () {
      dt_data.draw();
    });

    $('#searchFilter').on('input', function () {
      dt_data.draw();
    });
  }

  $('.dataTables_filter').hide();

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');
    $('.preview-image').attr('src', baseUrl + 'assets/img/person.png');

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

  // Edit record
  $(document).on('click', '.edit-record', function () {
    var productId = $(this).data('id');
    console.log('Product ID: ', productId);
    $.get(baseUrl + 'admin/products/edit/' + productId, function (data) {
      var product = data;

      $('#product_id').val(product.id);
      $('#product-name').val(product.name);
      $('#product-code').val(product.code);
      $('#product-unit').val(product.unit);
      $('#product-price').val(product.price);
      $('#product-min-order').val(product.minimum_order);
      $('#product-increase').val(product.increase);
      $('#product-status').val(product.status);
      $('#product-description').val(product.description);
      $('#product-contact-name').val(product.contact_name);
      $('#product-contact-phone').val(product.contact_phone);
      $('#product-contact-email').val(product.contact_email);
      $('#product-address').val(product.address);
      $('#product-longitude').val(product.longitude);
      $('#product-latitude').val(product.latitude);
      $('#product-notes').val(product.notes);

      if (product.image) {
        $('.preview-image').attr('src', baseUrl + product.image);
      }
      console.log('Product: ', product);
      $('#modelTitle').html(__('Edit Product'));
      $('#submitModal').modal('show');
    });
  });

  // Delete record
  $(document).on('click', '.delete-record', function () {
    var productId = $(this).data('id');
    var url = baseUrl + 'admin/products/delete/' + productId;
    deleteRecord($(this).data('name'), url);
  });

  // Change status
  $(document).on('click', '.edit_status', function () {
    var productId = $(this).data('id');
    var currentStatus = $(this).data('status');

    $.post(
      baseUrl + 'admin/products/status',
      {
        id: productId,
        status: currentStatus == 1 ? 0 : 1
      },
      function (data) {
        if (data.status === 1) {
          showAlert('success', data.success);
          dt_data.draw();
        } else {
          showAlert('error', data.error);
        }
      }
    );
  });

  // Form submission
  // $(document).on('submit', '.form_submit', function (e) {
  //   e.preventDefault();

  //   var formData = new FormData(this);
  //   var url = $(this).attr('action');

  //   $.ajax({
  //     url: url,
  //     type: 'POST',
  //     data: formData,
  //     processData: false,
  //     contentType: false,
  //     success: function (data) {
  //       if (data.status === 1) {
  //         showAlert('success', data.success);
  //         $('#submitModal').modal('hide');
  //         dt_data.draw();
  //       } else {
  //         showAlert('error', data.error);
  //       }
  //     },
  //     error: function (xhr) {
  //       $('.text-error').html('');
  //       if (xhr.status === 422) {
  //         var errors = xhr.responseJSON.errors;
  //         $.each(errors, function (key, value) {
  //           $('.' + key + '-error').html(value[0]);
  //         });
  //       } else {
  //         showAlert('error', __('An error occurred'));
  //       }
  //     }
  //   });
  // });

  // Reset modal on hide
  $('#submitModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    $('.preview-image').attr('src', baseUrl + 'assets/img/placeholder.jpg');
    $('.text-error').html('');
    $('#product_id').val('');
    $('#modelTitle').html(__('Add New Product'));

    // Reset map if exists
    if (window.productMap) {
      window.productMap.remove();
      window.productMap = null;
    }
    $('#product-map-container').hide();
    $('#product-link-input-wrapper').hide();
  });

  // Setup Mapbox Location Handlers for Products
  function setupProductMapboxLocationHandlers() {
    let productMap = null;
    let productMarker = null;
    let productGeocoder = null;

    // Initialize geocoder
    function initProductGeocoder() {
      if (productGeocoder) {
        productGeocoder.clear();
        document.getElementById('product-geocoder').innerHTML = '';
      }

      productGeocoder = new MapboxGeocoder({
        accessToken: mapboxgl.accessToken,
        mapboxgl: mapboxgl,
        placeholder: 'ابحث عن موقع...',
        language: 'ar'
      });

      document.getElementById('product-geocoder').appendChild(productGeocoder.onAdd());

      productGeocoder.on('result', function (e) {
        const coordinates = e.result.center;
        const address = e.result.place_name;

        $('#product-longitude').val(coordinates[0]);
        $('#product-latitude').val(coordinates[1]);
        $('#product-address').val(address);

        if (productMap) {
          updateProductMapLocation(coordinates[1], coordinates[0]);
        }
      });
    }

    // Initialize map
    function initProductMap(lat = 24.7136, lng = 46.6753) {
      if (productMap) {
        productMap.remove();
      }

      productMap = new mapboxgl.Map({
        container: 'product-map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [lng, lat],
        zoom: 13
      });

      productMarker = new mapboxgl.Marker({ draggable: true }).setLngLat([lng, lat]).addTo(productMap);

      productMarker.on('dragend', function () {
        const lngLat = productMarker.getLngLat();
        $('#product-longitude').val(lngLat.lng);
        $('#product-latitude').val(lngLat.lat);

        // Reverse geocoding
        fetch(
          `https://api.mapbox.com/geocoding/v5/mapbox.places/${lngLat.lng},${lngLat.lat}.json?access_token=${mapboxgl.accessToken}&language=ar`
        )
          .then(response => response.json())
          .then(data => {
            if (data.features && data.features.length > 0) {
              $('#product-address').val(data.features[0].place_name);
            }
          });
      });

      productMap.on('click', function (e) {
        const coordinates = e.lngLat;
        productMarker.setLngLat(coordinates);
        $('#product-longitude').val(coordinates.lng);
        $('#product-latitude').val(coordinates.lat);

        // Reverse geocoding
        fetch(
          `https://api.mapbox.com/geocoding/v5/mapbox.places/${coordinates.lng},${coordinates.lat}.json?access_token=${mapboxgl.accessToken}&language=ar`
        )
          .then(response => response.json())
          .then(data => {
            if (data.features && data.features.length > 0) {
              $('#product-address').val(data.features[0].place_name);
            }
          });
      });

      window.productMap = productMap;
    }

    // Update map location
    function updateProductMapLocation(lat, lng) {
      if (productMap && productMarker) {
        productMap.setCenter([lng, lat]);
        productMarker.setLngLat([lng, lat]);
      }
    }

    // Manual button click
    $(document).on('click', '#product-manual-btn', function () {
      $('#product-map-container').show();
      $('#product-map').show();

      setTimeout(() => {
        const lat = parseFloat($('#product-latitude').val()) || 24.7136;
        const lng = parseFloat($('#product-longitude').val()) || 46.6753;
        initProductMap(lat, lng);
        initProductGeocoder();
      }, 100);
    });

    // Current location button
    $(document).on('click', '#product-getCurrentLocation', function () {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;

          $('#product-latitude').val(lat);
          $('#product-longitude').val(lng);

          $('#product-map-container').show();
          $('#product-map').show();

          setTimeout(() => {
            initProductMap(lat, lng);
            initProductGeocoder();
          }, 100);

          // Reverse geocoding
          fetch(
            `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?access_token=${mapboxgl.accessToken}&language=ar`
          )
            .then(response => response.json())
            .then(data => {
              if (data.features && data.features.length > 0) {
                $('#product-address').val(data.features[0].place_name);
              }
            });
        });
      }
    });

    // Toggle link input
    $(document).on('click', '#product-toggle-link-input', function () {
      $('#product-link-input-wrapper').toggle();
    });

    // Parse map link
    $(document).on('click', '#product-parse-link', function () {
      const link = $('#product-map-link').val();
      const coordinates = extractCoordinatesFromLink(link);

      if (coordinates) {
        $('#product-latitude').val(coordinates.lat);
        $('#product-longitude').val(coordinates.lng);

        $('#product-map-container').show();
        $('#product-map').show();

        setTimeout(() => {
          initProductMap(coordinates.lat, coordinates.lng);
          initProductGeocoder();
        }, 100);

        // Reverse geocoding
        fetch(
          `https://api.mapbox.com/geocoding/v5/mapbox.places/${coordinates.lng},${coordinates.lat}.json?access_token=${mapboxgl.accessToken}&language=ar`
        )
          .then(response => response.json())
          .then(data => {
            if (data.features && data.features.length > 0) {
              $('#product-address').val(data.features[0].place_name);
            }
          });
      } else {
        showAlert('error', 'لم يتم العثور على إحداثيات صحيحة في الرابط');
      }
    });

    // Coordinate inputs change
    $(document).on('input', '#product-latitude, #product-longitude', function () {
      const lat = parseFloat($('#product-latitude').val());
      const lng = parseFloat($('#product-longitude').val());

      if (!isNaN(lat) && !isNaN(lng)) {
        if (productMap && productMarker) {
          updateProductMapLocation(lat, lng);
        }
      }
    });
  }

  // Extract coordinates from various map links
  function extractCoordinatesFromLink(link) {
    // Google Maps patterns
    const googlePatterns = [
      /@(-?\d+\.?\d*),(-?\d+\.?\d*)/,
      /q=(-?\d+\.?\d*),(-?\d+\.?\d*)/,
      /ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/
    ];

    for (let pattern of googlePatterns) {
      const match = link.match(pattern);
      if (match) {
        return { lat: parseFloat(match[1]), lng: parseFloat(match[2]) };
      }
    }

    return null;
  }

  // Initialize map handlers
  setupProductMapboxLocationHandlers();
});
