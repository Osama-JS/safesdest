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

  // Enhanced loadAds function with filters and pagination
  let currentPage = 1;
  let currentFilters = {
    search: '',
    status: '',
    price: ''
  };

  function loadAds(page = 1, filters = {}) {
    // Show loading state
    $('#ads-container').html(`
      <div class="loading-state">
        <div class="loading-spinner"></div>
        <p>Loading ads...</p>
      </div>
    `);

    // Clear pagination
    $('#pagination-container').html('');

    $.ajax({
      url: baseUrl + 'customer/ads/data',
      type: 'GET',
      data: {
        page: page,
        per_page: 8,
        search: filters.search || '',
        status: filters.status || '',
        price: filters.price || ''
      },
      success: function (response) {
        $('#ads-container').html('');

        // Check if no data available
        if (!response.data.data || response.data.data.length === 0) {
          $('#ads-container').html(`
            <div class="empty-state">
              <div class="empty-state-icon">
                <i class="ti ti-speakerphone-off"></i>
              </div>
              <h5>No Ads Found</h5>
              <p class="text-muted">No ads found matching your search criteria</p>
            </div>
          `);
          return;
        }

        // Enhanced card rendering
        response.data.data.forEach(ad => {
          let avatarHtml = '';
          let name = ad.customer.name;
          let initials = name.match(/\b\w/g) || [];
          initials = (initials.shift() || '') + (initials.pop() || '');
          let colors = ['success', 'danger', 'warning', 'info', 'dark', 'primary'];
          let color = colors[Math.floor(Math.random() * colors.length)];

          if (ad.customer.image === null) {
            avatarHtml = `
              <div class="avatar bg-label-${color}">
                <span class="avatar-initial">${initials.toUpperCase()}</span>
              </div>`;
          } else {
            avatarHtml = `
              <div class="avatar">
                <img src="${ad.customer.image}" alt="Customer Avatar"/>
              </div>`;
          }

          let priceHtml = '';

          // Price display matching admin
          if (ad.low_price > 0 && ad.high_price > 0) {
            priceHtml = `Lowest price: ${ad.low_price} SAR - Highest price: ${ad.high_price} SAR`;
          } else if (ad.low_price > 0) {
            priceHtml = `Lowest price: ${ad.low_price} SAR`;
          } else if (ad.high_price > 0) {
            priceHtml = `Highest price: ${ad.high_price} SAR`;
          } else {
            priceHtml = 'Price not specified';
          }

          let ownershipBadge = '';
          if (ad.user === ad.customer.id) {
            ownershipBadge = `<span class="badge badge-ownership">My Ad</span>`;
          }

          let statusBadge = '';
          if (ad.status === 'running') {
            statusBadge = `<span class="badge badge-running">Running</span>`;
          } else {
            statusBadge = `<span class="badge badge-closed">Closed</span>`;
          }

          let cardHtml = `
            <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4 fade-in">
              <div class="ad-card">
                ${ownershipBadge}
                ${statusBadge}

                <div class="map-container" id="map-${ad.id}"></div>

                <div class="card-body">
                  <div class="d-flex align-items-center mb-3">
                    ${avatarHtml}
                    <h5 class="card-title">${ad.customer.name}</h5>
                  </div>

                  <div class="address-info">
                    <p><strong>From:</strong> ${ad.from_address}</p>
                    <p><strong>To:</strong> ${ad.to_address}</p>
                  </div>

                  ${ad.note ? `<p class="card-text">${ad.note}</p>` : ''}
                </div>

                <div class="card-footer">
                  <div class="price-info">${priceHtml}</div>
                  <a href="${baseUrl}customer/ads/show/${ad.id}" class="btn btn-outline-primary w-100">
                    <i class="ti ti-eye me-1"></i>
                    View Details
                  </a>
                </div>
              </div>
            </div>
          `;
          $('#ads-container').append(cardHtml);

          // Initialize map for the ad
          initMapForAd(ad.id, ad.from_location);
        });

        // Render pagination if available
        if (response.data.pagination) {
          renderPagination(response.data.pagination);
        }
      },
      error: function () {
        $('#ads-container').html(`
          <div class="empty-state">
            <div class="empty-state-icon">
              <i class="ti ti-alert-circle"></i>
            </div>
            <h5>Loading Error</h5>
            <p class="text-muted">An error occurred while loading ads. Please try again.</p>
            <button class="btn btn-primary" onclick="loadAds()">Retry</button>
          </div>
        `);
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

  // Enhanced pagination function
  function renderPagination(pagination) {
    if (pagination.last_page <= 1) return;

    let paginationHtml = `
      <div class="pagination-wrapper">
        <div class="d-flex justify-content-between align-items-center">
          <div class="pagination-info">
            <span class="text-muted">
              Showing ${pagination.from} to ${pagination.to} of ${pagination.total} ads
            </span>
          </div>
          <nav>
            <ul class="pagination mb-0">
    `;

    // Previous button
    if (pagination.current_page > 1) {
      paginationHtml += `
        <li class="page-item">
          <a class="page-link" href="#" data-page="${pagination.current_page - 1}">
            <i class="ti ti-chevron-right"></i>
          </a>
        </li>
      `;
    }

    // Page numbers
    for (
      let i = Math.max(1, pagination.current_page - 2);
      i <= Math.min(pagination.last_page, pagination.current_page + 2);
      i++
    ) {
      paginationHtml += `
        <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
          <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>
      `;
    }

    // Next button
    if (pagination.current_page < pagination.last_page) {
      paginationHtml += `
        <li class="page-item">
          <a class="page-link" href="#" data-page="${pagination.current_page + 1}">
            <i class="ti ti-chevron-left"></i>
          </a>
        </li>
      `;
    }

    paginationHtml += `
            </ul>
          </nav>
        </div>
      </div>
    `;

    $('#pagination-container').html(paginationHtml);
  }

  // Filter functionality
  $('#apply-filters').on('click', function () {
    currentFilters = {
      search: $('#search-ads').val(),
      status: $('#filter-status').val(),
      price: $('#filter-price').val()
    };
    currentPage = 1;
    loadAds(currentPage, currentFilters);
  });

  $('#clear-filters').on('click', function () {
    $('#search-ads').val('');
    $('#filter-status').val('');
    $('#filter-price').val('');
    currentFilters = { search: '', status: '', price: '' };
    currentPage = 1;
    loadAds(currentPage, currentFilters);
  });

  // Search on Enter key
  $('#search-ads').on('keypress', function (e) {
    if (e.which === 13) {
      $('#apply-filters').click();
    }
  });

  // Pagination click handler
  $(document).on('click', '.pagination .page-link', function (e) {
    e.preventDefault();
    const page = $(this).data('page');
    if (page) {
      currentPage = page;
      loadAds(currentPage, currentFilters);
    }
  });

  // Initialize ads loading
  loadAds();

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');

    // Reload ads with current filters
    loadAds(currentPage, currentFilters);
    setTimeout(() => {
      $('#submitModal').modal('hide');
    }, 2000);
  });

  document.addEventListener('deletedSuccess', function (event) {
    // Reload ads with current filters
    loadAds(currentPage, currentFilters);
  });

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
