/**
 * Driver Ads Management Page
 */

'use strict';
import { deleteRecord } from '../../ajax';
import { mapsConfig } from '../../mapbox-helper';

// Global variables
let currentPage = 1;
let currentFilters = {
  search: '',
  price_range: '',
  date: '',
  sort: 'newest',
  per_page: 9
};

// Datatable (jquery)
$(function () {
  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Initialize page
  initializePage();

  function initializePage() {
    loadAds();
    bindEvents();
  }

  function bindEvents() {
    // Toggle filters
    $('#toggle-filters').on('click', function () {
      $('#filters-card').slideToggle();
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
      loadAds();
    });

    // Real-time search
    $('#search-input').on(
      'input',
      debounce(function () {
        currentFilters.search = $(this).val();
        loadAds(1);
      }, 500)
    );
  }

  function applyFilters() {
    currentFilters = {
      search: $('#search-input').val(),
      price_range: $('#price-range-filter').val(),
      date: $('#date-filter').val(),
      sort: $('#sort-filter').val(),
      per_page: $('#per-page-filter').val()
    };
    currentPage = 1;
    loadAds();
  }

  function clearFilters() {
    $('#filters-form')[0].reset();
    currentFilters = {
      search: '',
      price_range: '',
      date: '',
      sort: 'newest',
      per_page: 9
    };
    currentPage = 1;
    loadAds();
  }

  function loadAds(page = 1) {
    currentPage = page;
    showLoading();

    const requestData = {
      page: page,
      ...currentFilters
    };
    $.ajax({
      url: baseUrl + 'customer/customs-clearances/ads/data',
      type: 'GET',
      data: requestData,
      success: function (response) {
        hideLoading();
        $('#ads-container').html('');

        // Update stats
        updateStats(response);

        // Check if no data
        if (response.data.data.length === 0) {
          showEmptyState();
          $('#pagination').html('');
          return;
        }

        // Hide empty state
        $('#empty-state').hide();

        // Render ads
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

          // التحقق من السعر الأدنى والأعلى
          if (ad.price > 0) {
            priceHtml = `<span class="badge bg-success p-2 ">price: ${ad.price} SAR</span>`;
          } else {
            priceHtml = '<span class="badge bg-secondary p-2">Price not specified</span>';
          }

          //  <div class="d-flex align-items-center mb-3">
          //       ${avatarHtml}
          //       <h5 class="card-title">${ad.customer.name}</h5>
          //  </div>
          // Generate status badges
          let statusBadges = generateStatusBadges(ad);

          // Generate action button
          let actionButton = generateActionButton(ad);

          let cardHtml = `
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-4">
              <div class="driver-ad-card">
                <!-- Card Header -->
                <div class="card-header">
                  <div class="d-flex align-items-center">
                    ${avatarHtml}
                    <div class="flex-grow-1">
                      <h6 class="card-title mb-0">${ad.customer.name}</h6>
                      <small class="text-muted">Customs Clearance #${ad.id}</small>
                    </div>
                  </div>
                </div>

                <!-- Card Body -->
                <div class="card-body">
                  <!-- Description -->
                  <p class="card-text">${ad.note || 'No description available'}</p>

                  <!-- Offer Information -->
                  ${generateOfferInfo(ad)}
                </div>

                <!-- Card Footer -->
                <div class="card-footer">
                  ${priceHtml}
                  ${actionButton}

                </div>
              </div>
            </div>
          `;
          $('#ads-container').append(cardHtml);
        });

        updatePagination(response.data);
      }
    });
  }

  function updatePagination(data) {
    let totalPages = data.last_page;
    let currentPage = data.current_page;
    let paginationHtml = '';

    for (let i = 1; i <= totalPages; i++) {
      paginationHtml += `
        <button class="btn btn-link ${i === currentPage ? 'active' : ''}" onclick="loadAds(${i})">${i}</button>
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

  // Helper functions
  function updateStats(response) {
    $('#total-ads').text(response.data.total || 0);
    $('#my-offers').text(response.my_offers || 0);
    $('#avg-price').text(response.avg_price ? response.avg_price + ' SAR' : '0 SAR');
    $('#vehicle-match').text('100%'); // Always 100% since we filter by vehicle size
    $('#stats-cards').show();
  }

  function showLoading() {
    $('#loading-card').show();
    $('#ads-container').hide();
    $('#empty-state').hide();
  }

  function hideLoading() {
    $('#loading-card').hide();
    $('#ads-container').show();
  }

  function showEmptyState() {
    $('#empty-state').show();
    $('#ads-container').hide();
  }

  function showError(message) {
    // You can implement a toast notification or alert here
    alert(message);
  }

  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Generate status badges for ad card
  function generateStatusBadges(ad) {
    let badges = '';

    // Ad status badge
    let statusClass = ad.status === 'running' ? 'running' : 'closed';
    let statusIcon = ad.status === 'running' ? 'ti-play' : 'ti-lock';
    badges += `<span class="ad-status-badge ${statusClass}">
      <i class="ti ${statusIcon} me-1"></i>
      ${ad.status === 'running' ? 'Running' : 'Closed'}
    </span>`;

    // Offer status badge
    if (ad.has_offer) {
      if (ad.offer_accepted) {
        badges += `<span class="offer-status-badge accepted">
          <i class="ti ti-check-circle me-1"></i>
          Offer Accepted
        </span>`;
      } else {
        badges += `<span class="offer-status-badge submitted">
          <i class="ti ti-clock me-1"></i>
          Offer Submitted
        </span>`;
      }
    } else if (ad.status === 'running') {
      badges += `<span class="offer-status-badge not-submitted">
        <i class="ti ti-plus me-1"></i>
        No Offer
      </span>`;
    }

    return badges;
  }

  // Generate offer information section
  function generateOfferInfo(ad) {
    if (!ad.has_offer) {
      return '';
    }

    let offerInfo = `<div class="offer-info-section">`;

    if (ad.offer_accepted) {
      offerInfo += `
        <div class="alert alert-success mb-2">
          <i class="ti ti-check-circle me-2"></i>
          <strong>Congratulations!</strong> Your offer of ${ad.offer_price} SAR has been accepted.
        </div>`;
    } else {
      offerInfo += `
        <div class="alert alert-info mb-2">
          <i class="ti ti-clock me-2"></i>
          Your offer of ${ad.offer_price} SAR is pending review.
        </div>`;
    }

    offerInfo += `</div>`;
    return offerInfo;
  }

  // Generate action button based on ad status and permissions
  function generateActionButton(ad) {
    let buttonText = 'View Details';
    let buttonClass = 'btn-primary';
    let buttonIcon = 'ti-eye';

    if (ad.status === 'running' && ad.can_submit_offer) {
      if (ad.has_offer) {
        buttonText = 'Edit Offer';
        buttonIcon = 'ti-edit';
      } else {
        buttonText = 'Submit Offer';
        buttonIcon = 'ti-plus';
      }
    } else if (ad.status === 'closed') {
      buttonText = 'View Details';
      buttonClass = 'btn-outline-secondary';
      buttonIcon = 'ti-eye';
    }

    return `<a href="${baseUrl}customer/customs-clearances/ads/show/${ad.id}" class="btn ${buttonClass} w-100 mt-2">
      <i class="ti ${buttonIcon} me-1"></i>
      ${buttonText}
    </a>`;
  }

  // Make loadAds globally accessible for pagination
  window.loadAds = loadAds;

  loadAds();
});
