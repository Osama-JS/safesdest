/**
 * Page User List
 */

'use strict';
import { deleteRecord } from '../../ajax';

// Datatable (jquery)
$(function () {
  console.log(typeof Lang);

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  function loadTeams(page = 1, search = '') {
    $.ajax({
      url: baseUrl + 'admin/teams/data',
      type: 'GET',
      data: { search: search, page: page },
      success: function (response) {
        $('#teams-container').html('');

        if (response.data.data.length === 0) {
          $('#teams-container').html("<p class='text-center p-5 alert alert-secondary'>No data available</p>");
          $('#pagination').html('');
          return;
        }

        response.data.data.forEach(team => {
          // For Avatar badge

          let driversHtml = team.drivers
            .slice(0, 5) // عرض أول 5 سائقين فقط
            .map(driver => {
              // تحقق من البيانات
              var stateNum = Math.floor(Math.random() * 6);
              var states = ['success', 'danger', 'warning', 'info', 'dark', 'primary', 'secondary'];
              var $state = states[stateNum],
                $name = driver.name ?? 'driver',
                $initials = $name.match(/\b\w/g) || [],
                $output = '';

              $initials = (($initials.shift() || '') + ($initials.pop() || '')).toUpperCase();

              // إذا كانت الصورة موجودة
              if (driver.image) {
                $output = `<img src="${baseUrl + driver.image}" alt="${$name}" class="avatar-initial rounded-circle driver-avatar" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" data-bs-toggle="tooltip" title="${$name}">`;
              } else {
                // إذا كانت الصورة غير موجودة
                $output = `<span class="avatar-initial rounded-circle bg-label-${$state} driver-avatar" style="width: 50px; height: 50px; display: flex; justify-content: center; align-items: center; font-size: 20px; color: white; cursor: pointer;" data-bs-toggle="tooltip" title="${$name}">${$initials}</span>`;
              }

              return $output;
            })
            .join('');

          // إذا كان عدد السائقين أكبر من 5، أضف عدد السائقين المتبقيين
          if (team.drivers.length > 5) {
            const remainingDrivers = team.drivers.length - 5;
            driversHtml += `
                  <div class="avatar-initial rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 18px; cursor: pointer;">
                      +${remainingDrivers}
                  </div>
              `;
          }

          let teamCard = `
              <div class="col-md-4 mb-4">
                  <div class="card p-3 shadow-sm">
                      <div class="d-flex justify-content-between">
                          <h5>${team.name}</h5>
                          <div class="d-flex align-items-center gap-50">
                              <button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${team.id}" data-name="${team.name}" data-bs-toggle="modal" data-bs-target="#largeModal"><i class="ti ti-edit"></i></button>
                              <button class='btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect' data-id="${team.id}" data-name="${team.name}">
                                  <i class='ti ti-trash'></i>
                              </button>
                              <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                              <div class="dropdown-menu dropdown-menu-end m-0">
                                  <a href="${baseUrl + 'admin/teams/details/' + team.id}" class="dropdown-item">View</a>
                                  <a href="${baseUrl + 'admin/teams/wallets/' + team.id + '/' + team.name}" class="dropdown-item">Team Wallet</a>
                              </div>
                          </div>
                      </div>
                      <p class="text-muted">Id: ${team.id}</p>
                      <div class="d-flex flex-wrap">
                          ${driversHtml}
                      </div>
                  </div>
              </div>
          `;

          $('#teams-container').append(teamCard);

          // تفعيل خاصية العرض التفاعلي عند مرور الماوس (tooltip)
          $('[data-bs-toggle="tooltip"]').tooltip();
        });

        updatePagination(response.data);
      }
    });
  }

  function updatePagination(data) {
    let paginationHtml = '';
    for (let i = 1; i <= data.last_page; i++) {
      paginationHtml += `<li class="page-item ${i === data.current_page ? 'active' : ''}">
            <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>`;
    }

    $('#pagination').html(paginationHtml);
  }

  $(document).on('click', '.page-link', function (e) {
    e.preventDefault(); // منع إعادة تحميل الصفحة

    let page = $(this).data('page'); // جلب رقم الصفحة من الزر
    if (page) {
      loadTeams(page, $('#search-team').val()); // استدعاء الدالة مع رقم الصفحة الجديد
    }
  });

  $('#search-team').on('input', function () {
    loadTeams(1, $(this).val());
  });

  loadTeams();

  document.addEventListener('formSubmitted', function (event) {
    loadTeams();
    setTimeout(() => {
      $('#submitModal').modal('hide');
      $('.form_submit').trigger('reset');
    }, 2000);
  });

  document.addEventListener('deletedSuccess', function (event) {
    loadTeams();
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

      // إصلاح تعيين حالة is_public - معالجة جميع الحالات المحتملة
      console.log('is_public value:', data.is_public, 'type:', typeof data.is_public);

      let isPublic = false;
      if (data.is_public !== undefined && data.is_public !== null) {
        // تحويل القيمة إلى boolean بشكل صحيح
        if (typeof data.is_public === 'boolean') {
          isPublic = data.is_public;
        } else if (typeof data.is_public === 'string') {
          isPublic = data.is_public.toLowerCase() === 'true' || data.is_public === '1';
        } else if (typeof data.is_public === 'number') {
          isPublic = data.is_public === 1;
        }
      } else {
        // القيمة الافتراضية إذا كانت null أو undefined
        isPublic = true;
      }

      $('#team-is-public').prop('checked', isPublic);
      console.log('Setting switch to:', isPublic);
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
    // إعادة تعيين is_public إلى القيمة الافتراضية (true)
    $('#team-is-public').prop('checked', true);
  });
});
