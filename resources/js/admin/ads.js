/**
 * Page User List
 */

'use strict';
import { deleteRecord } from '../ajax';
import { mapsConfig } from '../mapbox-helper';

// Datatable (jquery)
$(function () {
  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Global variables for filters
  let currentFilters = {
    search: '',
    status: '',
    price_range: '',
    date: '',
    owner: '',
    sort: 'newest',
    per_page: 9
  };

  function loadAds(page = 1, filters = {}) {
    // Show loading state
    showLoadingState();

    // Merge current filters with new ones
    const requestData = {
      page: page,
      ...currentFilters,
      ...filters
    };

    $.ajax({
      url: baseUrl + 'admin/ads/data',
      type: 'GET',
      data: requestData,
      success: function (response) {
        hideLoadingState();
        $('#ads-container').html(''); // مسح المحتوى الحالي

        console.log(response);

        // Update stats
        updateStats(response.stats || {});

        // التحقق من عدم وجود بيانات
        if (response.data.data.length === 0) {
          showEmptyState();
          $('#pagination').html(''); // مسح التصفح
          return;
        }

        // تكرار البيانات وإضافة البطاقات
        response.data.data.forEach(ad => {
          let avatarHtml = '';
          let name = ad.customer.name;
          let initials = name.match(/\b\w/g) || [];
          initials = (initials.shift() || '') + (initials.pop() || '');
          let colors = ['success', 'danger', 'warning', 'info', 'dark', 'primary'];
          let color = colors[Math.floor(Math.random() * colors.length)];

          if (ad.customer.image === null) {
            avatarHtml = `
              <div class="avatar bg-label-${color} rounded-circle">
                <span class="avatar-initial">${initials.toUpperCase()}</span>
              </div>`;
          } else {
            avatarHtml = `
              <div class="avatar">
                <img src="${ad.customer.image}" class="rounded-circle object-cover"/>
              </div>`;
          }

          let priceHtml = '';
          let adStatus = '';

          // التحقق من السعر الأدنى والأعلى
          if (ad.low_price > 0 && ad.high_price > 0) {
            priceHtml = `<span>Lowest price: ${ad.low_price} ريال - Highest price: ${ad.high_price} ريال</span>`;
          } else if (ad.low_price > 0) {
            priceHtml = `<span>Lowest price: ${ad.low_price} ريال</span>`;
          } else if (ad.high_price > 0) {
            priceHtml = `<span>Highest price: ${ad.high_price} ريال</span>`;
          }

          // إذا لم يكن هناك أي سعر متاح، يتم ترك السعر فارغًا
          if (priceHtml === '') {
            priceHtml = '<span>Price not specified</span>';
          }

          let ownershipBadge = '';
          if (ad.user === ad.customer.id) {
            ownershipBadge = `
                <span class="badge badge-ownership">
                  <i class="ti ti-user me-1"></i>My Ad
                </span>
              `;
          }

          if (ad.status === 'running') {
            adStatus = `
                <span class="badge badge-status badge-running">
                  <i class="ti ti-play me-1"></i>Running
                </span>
              `;
          } else {
            adStatus = `
                <span class="badge badge-status badge-closed">
                  <i class="ti ti-square me-1"></i>Closed
                </span>
              `;
          }

          let cardHtml = `
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4">
              <div class="card ad-card h-100">
                ${ownershipBadge}
                ${adStatus}

                <div class="map-container" id="map-${ad.id}"></div>

                <div class="card-body d-flex flex-column">
                  <div class="d-flex align-items-center mb-3">
                    ${avatarHtml}
                    <h5 class="card-title mb-0">${ad.customer.name}</h5>
                  </div>

                  <div class="mb-3 d-flex gap-2">
                    <span class="badge bg-label-primary"><i class="ti ti-hash me-1"></i>Task #${ad.task_id}</span>
                    <span class="badge bg-label-info"><i class="ti ti-speakerphone me-1"></i>Ad #${ad.id}</span>
                  </div>

                  <div class="address-info">
                    <p><strong>From:</strong> ${ad.from_address}</p>
                    <p><strong>To:</strong> ${ad.to_address}</p>
                  </div>

                  <p class="card-text">${ad.note || 'No description available'}</p>
                </div>

                <div class="p-3">

                  <div class="price-info">${priceHtml}</div>
                  <a href="${baseUrl}admin/ads/show/${ad.id}" class="btn btn-outline-primary w-100">
                    <i class="ti ti-eye me-1"></i>
                    View Details
                  </a>
                </div>
              </div>
            </div>
          `;
          $('#ads-container').append(cardHtml);

          // تحميل الخريطة باستخدام Mapbox
          initMapForAd(ad.id, ad.from_location);
        });

        updatePagination(response.data);
      }
    });
  }

  mapboxgl.setRTLTextPlugin(
    'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-rtl-text/v0.2.3/mapbox-gl-rtl-text.js',
    null,
    true // تحميل فقط عند الحاجة (lazy load)
  );

  // دالة لتحميل الخريطة باستخدام Mapbox
  function initMapForAd(adId, location) {
    let mapContainer = document.getElementById(`map-${adId}`);

    if (!mapContainer) return;

    // إنشاء الخريطة باستخدام Mapbox
    mapboxgl.accessToken = mapsConfig.token; // استبدل هذا برمز التوثيق الخاص بك
    let map = new mapboxgl.Map({
      container: mapContainer,
      style: 'mapbox://styles/' + mapsConfig.style, // اختر الأسلوب الذي تفضله
      center: [location[0], location[1]], // الموقع الأول (longitude, latitude)
      zoom: 13
    });

    // إضافة مؤشر على الخريطة
    new mapboxgl.Marker().setLngLat([location[0], location[1]]).addTo(map);
  }

  function updatePagination(data) {
    let totalPages = data.last_page;
    let currentPage = data.current_page;
    let paginationHtml = '';

    if (totalPages <= 1) {
      $('#pagination').html('');
      return;
    }

    // Previous button
    if (currentPage > 1) {
      paginationHtml += `
        <li class="page-item">
          <a class="page-link" href="#" onclick="loadAds(${currentPage - 1}, currentFilters)">
            <i class="ti ti-chevron-left"></i>
          </a>
        </li>
      `;
    }

    // Page numbers
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
      paginationHtml += `
        <li class="page-item">
          <a class="page-link" href="#" onclick="loadAds(1, currentFilters)">1</a>
        </li>
      `;
      if (startPage > 2) {
        paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      paginationHtml += `
        <li class="page-item ${i === currentPage ? 'active' : ''}">
          <a class="page-link" href="#" onclick="loadAds(${i}, currentFilters)">${i}</a>
        </li>
      `;
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
      }
      paginationHtml += `
        <li class="page-item">
          <a class="page-link" href="#" onclick="loadAds(${totalPages}, currentFilters)">${totalPages}</a>
        </li>
      `;
    }

    // Next button
    if (currentPage < totalPages) {
      paginationHtml += `
        <li class="page-item">
          <a class="page-link" href="#" onclick="loadAds(${currentPage + 1}, currentFilters)">
            <i class="ti ti-chevron-right"></i>
          </a>
        </li>
      `;
    }

    $('#pagination').html(paginationHtml);
  }

  $(document).on('click', '.page-link', function (e) {
    e.preventDefault(); // منع إعادة تحميل الصفحة

    let page = $(this).data('page'); // جلب رقم الصفحة من الزر
    if (page) {
      loadAds(page); // استدعاء الدالة مع رقم الصفحة الجديد
    }
  });

  // Initialize filters functionality
  initializeFilters();

  // Load initial ads
  loadAds();

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');
    loadAds();
    setTimeout(() => {
      $('#submitModal').modal('hide');
    }, 2000);
  });

  document.addEventListener('deletedSuccess', function (event) {
    loadAds();
  });

  // Helper Functions
  function showLoadingState() {
    $('#loading-card').show();
    $('#ads-container').hide();
    $('#stats-cards').hide();
  }

  function hideLoadingState() {
    $('#loading-card').hide();
    $('#ads-container').show();
    $('#stats-cards').show();
  }

  function showEmptyState() {
    $('#ads-container').html(`
      <div class="col-12">
        <div class="empty-state">
          <i class="ti ti-speakerphone-off"></i>
          <h5>No advertisements found</h5>
          <p>Try adjusting your filters or check back later for new ads.</p>
        </div>
      </div>
    `);
  }

  function updateStats(stats) {
    $('#total-ads').text(stats.total || 0);
    $('#running-ads').text(stats.running || 0);
    $('#closed-ads').text(stats.closed || 0);
    $('#avg-price').text(stats.avg_price || 0);
  }

  function initializeFilters() {
    // Toggle filters card
    $('#toggle-filters').on('click', function () {
      $('#filters-card').slideToggle();
      const icon = $(this).find('i');
      icon.toggleClass('ti-filter ti-filter-off');
    });

    // Apply filters
    $('#apply-filters').on('click', function () {
      applyFilters();
    });

    // Clear filters
    $('#clear-filters').on('click', function () {
      clearFilters();
    });

    // Refresh ads
    $('#refresh-ads').on('click', function () {
      loadAds(1, currentFilters);
    });

    // Real-time search
    let searchTimeout;
    $('#search-input').on('input', function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        currentFilters.search = $(this).val();
        loadAds(1, currentFilters);
      }, 500);
    });
  }

  function applyFilters() {
    currentFilters = {
      search: $('#search-input').val(),
      status: $('#status-filter').val(),
      price_range: $('#price-range-filter').val(),
      date: $('#date-filter').val(),
      owner: $('#owner-filter').val(),
      sort: $('#sort-filter').val(),
      per_page: $('#per-page-filter').val()
    };

    loadAds(1, currentFilters);
    $('#filters-card').slideUp();
  }

  function clearFilters() {
    // Reset form
    $('#filters-form')[0].reset();

    // Reset current filters
    currentFilters = {
      search: '',
      status: '',
      price_range: '',
      date: '',
      owner: '',
      sort: 'newest',
      per_page: 9
    };

    // Reload ads
    loadAds(1, currentFilters);
  }

  $(document).on('click', '.edit-record', function () {
    var teamId = $(this).data('id');
    var teamName = $(this).data('name');

    $('#submitModal').modal('show');

    $('#modelTitle').html(`Edit Team: <span class="bg-info text-white px-2 rounded">${teamName}</span>`);

    // get data
    $.get(`${baseUrl}admin/teams/edit/${teamId}`, function (data) {
      $('#team_id').val(data.id);
      $('#team-name').val(data.name);
      $('#team-address').val(data.address);
      $('#team-location_update').val(data.location_update_interval);
      $('#team-commission-type').val(data.team_commission_type);
      $('#team-commission').val(data.team_commission_value);
      $('#team-note').val(data.note);
    });
  });

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/teams/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    $('.text-error').html('');
    $('#team_id').val('');
    $('#modelTitle').html('Add New Team');
  });
});
