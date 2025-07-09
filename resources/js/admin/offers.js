/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert } from '../ajax';
import { mapsConfig } from '../mapbox-helper';

// Datatable (jquery)
$(function () {
  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  function loadOffers() {
    $.ajax({
      url: baseUrl + 'admin/ads/offers/show',
      type: 'GET',
      data: { id: adId },
      success: function (response) {
        $('#total-offers-counter').text(response.count);
        console.log(response);

        const container = $('#offers-container');
        container.empty();

        if (response.data.length === 0) {
          container.html(`
            <div class="empty-state p-5 text-center">
              <i class="ti ti-users-off fs-1 text-muted mb-3"></i>
              <h6 class="text-muted mb-2">No Offers Submitted</h6>
              <p class="text-muted mb-0">No drivers have submitted offers for this task yet.</p>
            </div>
          `);
          return;
        }

        const hasAccepted = response.data.some(offer => offer.accepted);

        response.data.forEach((offer, index) => {
          const driver = offer.driver;
          const isAccepted = offer.accepted;

          // Generate driver initials for avatar fallback
          const driverInitials = driver.name
            .split(' ')
            .map(word => word.charAt(0).toUpperCase())
            .slice(0, 2)
            .join('');

          // Format offer time
          const offerTime = new Date(offer.created_at).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          });

          const card = `
            <div class="offer-card ${isAccepted ? 'offer-accepted' : ''}" data-offer-id="${offer.id}">
              <div class="offer-header">
                <div class="driver-info">
                  <div class="driver-avatar">
                    ${
                      driver.image
                        ? `<img src="${baseUrl + driver.image}" alt="${driver.name}" class="avatar-img">`
                        : `<div class="avatar-initial">${driverInitials}</div>`
                    }
                  </div>
                  <div class="driver-details">
                    <h6 class="driver-name">${driver.name}</h6>
                    <div class="driver-contact">
                      <i class="ti ti-phone me-1"></i>
                      <span>${driver.phone_code} ${driver.phone}</span>
                    </div>
                    <div class="offer-time">
                      <i class="ti ti-clock me-1"></i>
                      <span>${offerTime}</span>
                    </div>
                  </div>
                </div>

                <div class="offer-status">
                  ${
                    isAccepted
                      ? '<div class="status-badge accepted"><i class="ti ti-check-circle me-1"></i>Accepted</div>'
                      : '<div class="status-badge pending"><i class="ti ti-clock me-1"></i>Pending</div>'
                  }
                </div>
              </div>

              <div class="offer-content">
                <div class="price-section">
                  <div class="price-label">Offered Price</div>
                  <div class="price-value">
                    <span class="amount">${Number(offer.price).toLocaleString()}</span>
                    <span class="currency">SAR</span>
                  </div>
                </div>

                ${
                  offer.description
                    ? `
                  <div class="notes-section">
                    <div class="notes-label">
                      <i class="ti ti-notes me-1"></i>Driver Notes
                    </div>
                    <div class="notes-content">${offer.description}</div>
                  </div>
                `
                    : ''
                }
              </div>

              ${
                driver.rating
                  ? `
                <div class="offer-footer">
                  <div class="driver-rating">
                    <div class="rating-stars">
                      ${Array.from(
                        { length: 5 },
                        (_, i) =>
                          `<i class="ti ti-star${i < Math.floor(driver.rating) ? '-filled' : ''} text-warning"></i>`
                      ).join('')}
                    </div>
                    <span class="rating-value">${driver.rating}/5</span>
                    <span class="rating-count">(${driver.reviews_count || 0} reviews)</span>
                  </div>
                </div>
              `
                  : ''
              }

              <div class="offer-actions">
                ${
                  offer.ad_status == 'running'
                    ? isAccepted
                      ? `<button class="btn btn-outline-danger retract-offer w-100" data-id="${offer.id}">
                    <i class="ti ti-x me-1"></i>Retract Acceptance
                  </button>`
                      : !hasAccepted
                        ? `<button class="btn btn-primary accept-offer w-100" data-id="${offer.id}">
                      <i class="ti ti-check me-1"></i>Accept This Offer
                    </button>`
                        : `<div class="text-center text-muted py-2">
                      <small><i class="ti ti-info-circle me-1"></i>Another offer has been accepted</small>
                    </div>`
                    : ''
                }
              </div>
            </div>
          `;

          container.append(card);
        });
      }
    });
  }

  loadOffers();

  $(document).on('click', '.accept-offer', function () {
    var id = $(this).data('id');
    Swal.fire({
      title: `Accept The Offer ? `,
      text: 'You will not be able to undo this action!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Accept it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
        cancelButton: 'btn btn-label-secondary waves-effect waves-light'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        $.ajax({
          url: ` ${baseUrl}admin/ads/offers/accept/${id}`,
          type: 'GET',
          success: function (response) {
            if (response.status === 1) {
              showAlert('success', response.success, 10000, true);
              loadOffers();
            } else {
              showAlert('error', response.error, 10000, true);
            }
          },
          error: function () {
            showAlert('error', 'Field to Accept the Offer', 10000, true);
          }
        });
      }
    });
  });

  $(document).on('click', '.retract-offer', function () {
    var id = $(this).data('id');
    Swal.fire({
      title: `Retract acceptance ? `,
      text: 'You will not be able to undo this action!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Retract it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
        cancelButton: 'btn btn-label-secondary waves-effect waves-light'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        $.ajax({
          url: ` ${baseUrl}admin/ads/offers/retract/${id}`,
          type: 'GET',
          success: function (response) {
            if (response.status === 1) {
              showAlert('success', response.success, 10000, true);
              loadOffers();
            } else {
              showAlert('error', response.error, 10000, true);
            }
          },
          error: function () {
            showAlert('error', 'Field to Retract the Offer', 10000, true);
          }
        });
      }
    });
  });
});
