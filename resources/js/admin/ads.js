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

  function loadAds(page = 1, search = '') {
    $.ajax({
      url: baseUrl + 'admin/ads/data',
      type: 'GET',
      data: { search: search, page: page },
      success: function (response) {
        $('#ads-container').html(''); // مسح المحتوى الحالي

        // التحقق من عدم وجود بيانات
        if (response.data.data.length === 0) {
          $('#ads-container').html("<p class='text-center p-5 alert alert-secondary'>No data available</p>");
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

          let cardHtml = `
            <div class="col-md-3 col-sm-6 col-12 mb-4">
              <div class="card">
                <div class="map-container" id="map-${ad.id}"></div>

                <div class="card-body">
                 <div class="d-flex align-items-center mb-3">
                    ${avatarHtml}
                       <h5 class="card-title">${ad.customer.name}</h5>
                  </div>
                  <div class="row">
                      <div class="col-6">
                       <p><strong>From:</strong> ${ad.from_address}</p>
                      </div>
                      <div class="col-6">
                       <p><strong>To:</strong> ${ad.to_address}</p>
                      </div>
                  </div>

                  <p class="card-text">${ad.note || 'No description available'}</p>

                </div>
                <div class="card-footer">
                  ${priceHtml}
                  <button class=" form-control btn btn-outline-primary mt-2">View Details</button>
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
      zoom: 12
    });

    // إضافة مؤشر على الخريطة
    new mapboxgl.Marker().setLngLat([location[0], location[1]]).addTo(map);
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
      loadAds(page, $('#search-team').val()); // استدعاء الدالة مع رقم الصفحة الجديد
    }
  });

  $('#search-team').on('input', function () {
    loadAds(1, $(this).val());
  });

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
