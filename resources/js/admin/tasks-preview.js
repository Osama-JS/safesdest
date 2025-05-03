/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert } from '../ajax';
import { initializeMap, onMapReady } from '../mapbox-helper';

// Datatable (jquery)
$(function () {
  const verticalExample = document.getElementById('vertical-example');
  if (verticalExample) {
    new PerfectScrollbar(verticalExample, {
      wheelPropagation: false
    });
  }
  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  let taskMarkers = {};
  initializeMap('taskMap', [39.85791, 21.3891], 10, () => {
    loadTeams(); // نبدأ بعد تحميل الخريطة فقط
  });

  $('.body-container-block').block({
    message:
      '<div class="d-flex justify-content-center"><p class="mb-0">Please wait...</p> <div class="sk-wave m-0"><div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div></div> </div>',
    css: {
      backgroundColor: 'transparent',
      color: '#fff',
      border: '0'
    },
    overlayCSS: {
      opacity: 0.5
    }
  });

  function loadTeams(page = 1, search = '', retries = 3) {
    $.ajax({
      url: baseUrl + 'admin/tasks/data',
      type: 'GET',
      data: { search: search, page: page },
      success: function (response) {
        $('.body-container-block').unblock({
          onUnblock: function () {
            const unassigned = response.data.unassigned;
            const assigned = response.data.assigned;
            const completed = response.data.completed;

            renderTasks(unassigned, '#task-unassigned-container', '.count-unassigned');
            renderTasks(assigned, '#task-assigned-container', '.count-assigned');
            renderTasks(completed, '#task-completed-container', '.count-completed');

            const allTasks = [...unassigned, ...assigned, ...completed];
            onMapReady(map => updateMapMarkers(map, allTasks));
          }
        });
      },
      error: function () {
        if (retries > 0) {
          setTimeout(() => loadTeams(page, search, retries - 1), 2000);
        } else {
          $('.body-container-block').unblock({
            onUnblock: function () {
              showAlert('warning', 'Error to loading Data. please refresh the page');
              $('#task-unassigned-container, #task-assigned-container, #task-completed-container').html(
                '<div class="alert alert-danger">Error to loading Data. please refresh the page</div>'
              );
            }
          });
        }
      }
    });
  }

  function renderTasks(tasks, containerSelector, countSelector) {
    $(countSelector).text(tasks.length);
    $(containerSelector).html('');

    tasks.forEach(task => {
      const statusClass = getStatusBadgeClass(task.status);

      const card = `
        <div class="mb-4">
          <div class="card p-3 shadow-sm task-card" data-task-id="${task.id}">
            <div class="d-flex justify-content-between">
              <div class="d-flex align-items-center">
                <img src="${task.avatar}" class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;" alt="Avatar">
                <div class="px-3">
                  <h6 class="mb-1">${task.name}</h6>
                  <p>${task.point.address || ''} (${task.point.longitude} - ${task.point.latitude})</p>
                </div>
              </div>
              <div class="d-flex align-items-center gap-50">
                <span class="badge bg-${statusClass} text-capitalize">${task.status.replace('_', ' ')}</span>
              </div>
            </div>
          </div>
        </div>
      `;

      $(containerSelector).append(card);
    });

    // إعادة ربط الحدث لكل بطاقة مهمة
    $(`${containerSelector} .task-card`).on('click', function () {
      const taskId = $(this).data('task-id');
      showTaskDetails(taskId);
    });
  }

  function updateMapMarkers(map, tasks) {
    // حذف الإبر السابقة
    Object.values(taskMarkers).forEach(marker => marker.remove());
    taskMarkers = {};
    console.log(tasks);

    tasks.forEach(task => {
      if (task.point?.latitude && task.point?.longitude) {
        const popup = new mapboxgl.Popup({ offset: 25 }).setHTML(
          `<strong>${task.name}</strong><br>${task.point.address || ''}`
        );

        const el = document.createElement('div');
        el.className = 'custom-marker';
        el.style.backgroundImage = 'url(https://cdn-icons-png.flaticon.com/512/684/684908.png)';
        el.style.width = '30px';
        el.style.height = '30px';
        el.style.backgroundSize = 'cover';
        el.style.borderRadius = '50%';

        const marker = new mapboxgl.Marker(el)
          .setLngLat([task.point.longitude, task.point.latitude])
          .setPopup(popup)
          .addTo(map);

        taskMarkers[task.id] = marker;
      }
    });
  }

  function showTaskDetails(taskId) {
    $.ajax({
      url: `${baseUrl}admin/tasks/${taskId}`, // تأكد أن هذا المسار يتوافق مع ما أنشأته في الباك
      type: 'GET',
      success: function (task) {
        const statusClass = getStatusBadgeClass(task.data.status);

        console.log(task);

        const htmlDetails = `
        <table class="table " >
          <tr>
            <td>
              status
            </td>
            <td>
              ${task.data.status}
            </td>
          </tr>
          <tr>
            <td>
              status
            </td>
            <td>
              ${task.data.status}
            </td>
          </tr>
          <tr>
            <td>
              pickup notes
            </td>
            <td>

            </td>
          </tr>
          <tr>
            <td>
              delivery notes
            </td>
            <td>

            </td>
          </tr>

        </table>
          <div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="badge bg-${statusClass}"></span>
            </div>
            <hr>

          </div>
        `;

        const htmlCustomer = `
        <table class="table " >
          <tr>
            <td>
              owner
            </td>
            <td>
              ${task.data.customer.owner}
            </td>
          </tr>
          <tr>
            <td>
              name
            </td>
            <td>
              ${task.data.customer.name}
            </td>
          </tr>
          <tr>
            <td>
              phone
            </td>
            <td>
            ${task.data.customer.phone}
            </td>
          </tr>
          <tr>
            <td>
              email
            </td>
            <td>
            ${task.data.customer.email}

            </td>
          </tr>
          <tr>
            <td>
              address
            </td>
            <td>
            ${task.data.customer.address}

            </td>
          </tr>

        </table>
          <div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="badge bg-${statusClass}"></span>
            </div>
            <hr>

          </div>
        `;
        $('#task-details-content').html(htmlDetails);
        $('#task-owner-content').html(htmlCustomer);

        $('#task-details-view').stop().fadeIn(300);

        // تحريك الخريطة لنقطة المهمة
        if (task.data.point.latitude && task.data.point.longitude) {
          map.flyTo({
            center: [task.data.point.longitude, task.data.point.latitude],
            zoom: 14,
            speed: 0.8
          });

          if (taskMarkers[task.data.id]) {
            taskMarkers[task.data.id].getPopup().addTo(map);
          }
        }
      }
    });
  }

  // زر الإغلاق
  $('#close-task-details').on('click', function () {
    $('#task-details-view').stop().fadeOut(200);
  });

  function getStatusBadgeClass(status) {
    switch (status) {
      case 'pending_payment':
        return 'warning';
      case 'payment_failed':
        return 'danger';
      case 'advertised':
        return 'secondary';
      case 'in_progress':
        return 'info';
      case 'assign':
      case 'accepted':
        return 'primary';
      case 'start':
        return 'dark';
      case 'completed':
        return 'success';
      case 'canceled':
        return 'danger';
      default:
        return 'light';
    }
  }

  $('#search-team').on('input', function () {
    loadTeams(1, $(this).val());
  });

  loadTeams();

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');

    loadTeams();
    setTimeout(() => {
      $('#submitModal').modal('hide');
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
