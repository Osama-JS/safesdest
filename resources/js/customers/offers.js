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
      url: baseUrl + 'customer/ads/offers/show',
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

        const hasAccepted = response.data.some(offer => offer.accepted);

        response.data.forEach(offer => {
          const driver = offer.driver;

          const card = `
    <div class="mb-3 border-bottom rounded overflow-hidden">
      <div class="d-flex gap-3 p-2 rounded border border-success ${offer.accepted ? 'bg-light' : ''}">
        <img src="${driver.image ? baseUrl + driver.image : baseUrl + 'assets/img/person.png'}" alt="${driver.name}"
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
            <span class="mb-1">${offer.description || 'No notes'}</span>
          </div>

          ${
            offer.accepted
              ? `<button class="btn btn-danger retract-offer" data-id="${offer.id}">Retract acceptance</button>`
              : !hasAccepted
                ? `<button class="btn btn-info accept-offer" data-id="${offer.id}">Accept this offer</button>`
                : ''
          }
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
          url: ` ${baseUrl}customer/ads/offers/accept/${id}`,
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
          url: ` ${baseUrl}customer/ads/offers/retract/${id}`,
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
