/**
 * Page User List
 */

'use strict';
import { deleteRecord } from '../ajax';
import { mapsConfig } from '../mapbox-helper';

// Datatable (jquery)
$(function () {
  console.log(typeof Lang);

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // دالة لتحميل الخريطة باستخدام Mapbox
  function initMapForAd(adId, location) {
    let mapContainer = document.getElementById(`map-${adId}`);

    if (!mapContainer) return;

    mapboxgl.accessToken = mapsConfig.token;
    let map = new mapboxgl.Map({
      container: mapContainer,
      style: 'mapbox://styles/' + mapsConfig.style,
      center: [location[0], location[1]],
      zoom: 12
    });

    new mapboxgl.Marker().setLngLat([location[0], location[1]]).addTo(map);
  }

  function loadHistory(page = 1, search = '') {
    let id = $('#task-id-history').val();
    $.ajax({
      url: baseUrl + 'driver/task/current/history/' + id,
      type: 'GET',
      success: function (response) {
        if (response.status === 2) {
          $('#task-history-container').html(`<p class='text-center p-5 alert alert-secondary'>${response.error}</p>`);
        }
        if (response.data.length === 0) {
          $('#task-history-container').html("<p class='text-center p-5 alert alert-secondary'>No data available</p>");
          return;
        }

        const htmlHistory = `
        <div class="card shadow-sm ">
          <div class="card-body">
            <h5 class="card-title mb-3">
              <i class="ti ti-clock-hour-4 me-2 mb-3"></i>History
            </h5>
            <ul class="timeline mb-0">
              ${(response.data || [])
                .map(event => {
                  const userInfo = event.user ? `<div class="text-muted small mb-1">By: ${event.user}</div>` : '';
                  const driverInfo = event.driver
                    ? `<div class="text-muted small mb-1">${event.type === 'assign' ? 'To' : 'By'}: ${event.driver}</div>`
                    : '';
                  const fileInfo = event.file
                    ? `
                      <div class="d-flex align-items-center mt-2">
                        <div class="badge bg-lighter rounded d-flex align-items-center p-2">
                          <img src="/assets/img/icons/misc/${event.file.type || 'file'}.png" alt="file" width="16" class="me-2" />
                          <a href="${event.file.url}" target="_blank" class="text-body small fw-bold text-decoration-underline">
                            ${event.file.name}
                          </a>
                        </div>
                      </div>
                    `
                    : '';

                  return `
                    <li class="timeline-item timeline-item-transparent">
                      <span class="timeline-point timeline-point-${event.color || 'secundary'}"></span>
                      <div class="timeline-event">
                        <div class="timeline-header mb-2">
                          <h6 class="mb-0 border rounded border-${event.color || 'secundary'}  px-3 py-2">${event.type || 'Unknown Action'}</h6>
                          <small class="text-muted">${event.date || ''}</small>
                        </div>
                        ${userInfo}
                        ${driverInfo}
                        <p class="mb-2">${event.description || ''}</p>
                        ${fileInfo}
                      </div>
                    </li>
                  `;
                })
                .join('')}
            </ul>
          </div>
        </div>
      `;

        $('#task-history-container').html(htmlHistory);
      }
    });
  }
  loadHistory();

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');
    loadHistory();
  });

  const buttons = document.querySelectorAll('.step-circle');

  buttons.forEach(button => {
    button.addEventListener('click', function () {
      const form = this.closest('form');
      const status = this.getAttribute('data-status');

      Swal.fire({
        title: __('Are you sure?'),
        text: `${__('The task status will be changed to')} "${__(status)}"`,
        icon: 'warning',
        confirmButtonColor: '#3085d6',
        confirmButtonText: __('Yes, change status'),
        showCancelButton: true,
        customClass: {
          confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
          cancelButton: 'btn btn-label-secondary waves-effect waves-light'
        },
        buttonsStyling: false
      }).then(result => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
});
