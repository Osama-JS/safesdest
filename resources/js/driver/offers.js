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
          container.html('<div class="text-center text-muted alert alert-info">There are no offers yet</div>');
          return;
        }

        response.data.forEach(offer => {
          const driver = offer.driver;

          const card = `
          <div class=" mb-3 border-bottom rounded overflow-hidden">
            <div class="d-flex gap-3 p-2 rounded border border-success" >
              <img src="${driver.image ? baseUrl + driver.image : 'https://via.placeholder.com/80'}" alt="${driver.name}"
                   class="rounded-circle border" style="width: 60px; height: 60px; object-fit: cover;">

              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div>
                    <h6 class="mb-1">${driver.name}</h6>
                    <small class="text-muted">${driver.phone_code} ${driver.phone}</small>
                  </div>

                </div>

                <div class="mb-2">
                  <strong>Price:</strong>
                  <span class="text-primary fw-bold">${Number(offer.price).toLocaleString()} SAR</span>
                </div>

                <div class="mb-2">
                  <strong>Notes:</strong>
                  <span class="mb-1">${offer.description || 'No notes '}</span>
                </div>
              </div>
            </div>
          </div>
        `;

          container.append(card);
        });
      }
    });
  }

  loadOffers();
});
