/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, showFormModal } from '../ajax';
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
    console.log('ad_id', adId);
    $.ajax({
      url: baseUrl + 'driver/ads/offers/show',
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
              <h6 class="text-muted mb-2">No Offers Yet</h6>
              <p class="text-muted mb-0">Be the first to submit an offer for this task!</p>
            </div>
          `);
          return;
        }

        response.data.forEach((offer, index) => {
          const driver = offer.driver;
          const isAccepted = offer.accepted;
          const isMyOffer = offer.is_my_offer; // Assuming this field exists

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
            <div class="offer-card ${isAccepted ? 'offer-accepted' : ''} ${isMyOffer ? 'my-offer' : ''}" data-offer-id="${offer.id}">
              <div class="offer-header">
                <div class="driver-info">
                  <div class="driver-avatar">
                    ${
                      driver.image
                        ? `<img src="${baseUrl + driver.image}" alt="${driver.name}" class="avatar-img">`
                        : `<div class="avatar-initial">${driverInitials}</div>`
                    }
                    ${isMyOffer ? '<div class="my-offer-badge"><i class="ti ti-user"></i></div>' : ''}
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
                  ${isMyOffer ? '<div class="my-offer-label">Your Offer</div>' : ''}
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
            </div>
          `;

          container.append(card);
        });
      }
    });
  }

  loadOffers();

  $(document).on('click', '#accept-task', function () {
    var id = $(this).data('id');
    Swal.fire({
      title: `Confirm task acceptance ? `,
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
          url: ` ${baseUrl}driver/ads/offers/accept/task/${id}`,
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
            showAlert('error', 'Field to Accept the Task', 10000, true);
          }
        });
      }
    });
  });

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');
    setTimeout(() => {
      $('#offerModal').modal('hide');
    }, 2000);

    loadOffers();
  });

  document.addEventListener('deletedSuccess', function (event) {
    loadOffers();
  });
});
