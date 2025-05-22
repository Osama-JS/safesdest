/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, showFormModal, generateFields } from '../../ajax';
import { mapsConfig } from '../../mapbox-helper';

// Datatable (jquery)
$(function () {
  const verticalExample = document.getElementById('vertical-example');
  if (verticalExample) {
    new PerfectScrollbar(verticalExample, {
      wheelPropagation: false
    });
  }

  var select2 = $('.task-driver');
  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: 'Select Driver',
      dropdownParent: $this.parent(),
      closeOnSelect: false
    });
  }

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  let taskMarkers = {};
  // تهيئة الخريطة باستخدام اسم جديد لتجنب التعارض
  const taskMapInstance = new mapboxgl.Map({
    container: 'taskMap',
    style: 'mapbox://styles/' + mapsConfig.style,
    center: mapsConfig.center,
    zoom: 10
  });

  // تحميل المهام بعد تهيئة الخريطة
  taskMapInstance.on('load', function () {
    loadTasks(); // تحميل المهام بعد تحميل الخريطة
  });

  // منع تداخل الخريطة مع عناصر أخرى على الصفحة

  $(document).on('change', '#filter-by-day', function () {
    console.log($(this).val());
    loadTasks();
  });

  // دالة لتحميل المهام
  function loadTasks(page = 1, search = '', retries = 3) {
    const filter = $('#filter-by-day').val();
    console.log('run');
    $('.body-container-block').block({
      message: `
      <div class="d-flex justify-content-center">
        <p class="mb-0">Please wait...</p>
        <div class="sk-wave m-0">
          <div class="sk-rect sk-wave-rect"></div>
          <div class="sk-rect sk-wave-rect"></div>
          <div class="sk-rect sk-wave-rect"></div>
          <div class="sk-rect sk-wave-rect"></div>
          <div class="sk-rect sk-wave-rect"></div>
        </div>
      </div>`,
      css: {
        backgroundColor: 'transparent',
        color: '#fff',
        border: '0'
      },
      overlayCSS: {
        opacity: 0.5
      }
    });
    $.ajax({
      url: baseUrl + 'admin/tasks/data',
      type: 'GET',
      data: { search: search, filter: filter, page: page },
      success: function (response) {
        $('.body-container-block').unblock({
          onUnblock: function () {
            const unassigned = response.data.unassigned;
            const assigned = response.data.assigned;
            const completed = response.data.completed;
            console.log('in');

            renderTasks(unassigned, '#task-unassigned-container', '.count-unassigned');
            renderTasks(assigned, '#task-assigned-container', '.count-assigned');
            renderTasks(completed, '#task-completed-container', '.count-completed');

            const allTasks = [...unassigned, ...assigned, ...completed];
            // تحديث معالم الخريطة
            updateMapMarkers(taskMapInstance, allTasks);
          }
        });
      },
      error: function () {
        if (retries > 0) {
          setTimeout(() => loadTasks(page, search, retries - 1), 2000);
        } else {
          $('.body-container-block').unblock({
            onUnblock: function () {
              showAlert('warning', 'Error loading Data. Please refresh the page');
              $('#task-unassigned-container, #task-assigned-container, #task-completed-container').html(
                '<div class="alert alert-danger">Error loading Data. Please refresh the page</div>'
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

  // دالة لعرض المهام على الخريطة
  function updateMapMarkers(map, tasks) {
    // إزالة المصدر السابق إن وجد
    if (map.getSource('tasks')) {
      map.removeLayer('clusters');
      map.removeLayer('cluster-count');
      map.removeLayer('unclustered-point');
      map.removeSource('tasks');
    }

    const features = tasks
      .filter(task => task.point?.latitude && task.point?.longitude)
      .map(task => ({
        type: 'Feature',
        geometry: {
          type: 'Point',
          coordinates: [task.point.longitude, task.point.latitude]
        },
        properties: {
          id: task.id,
          name: task.name,
          address: task.point.address || '',
          type: 'pickup'
        }
      }));

    map.addSource('tasks', {
      type: 'geojson',
      data: {
        type: 'FeatureCollection',
        features: features
      },
      cluster: true,
      clusterMaxZoom: 14,
      clusterRadius: 50
    });

    // طبقة التجميع
    map.addLayer({
      id: 'clusters',
      type: 'circle',
      source: 'tasks',
      filter: ['has', 'point_count'],
      paint: {
        'circle-color': '#007cbf',
        'circle-radius': ['step', ['get', 'point_count'], 20, 10, 30, 30, 40],
        'circle-opacity': 0.8
      }
    });

    // عدد المهام داخل التجميع
    map.addLayer({
      id: 'cluster-count',
      type: 'symbol',
      source: 'tasks',
      filter: ['has', 'point_count'],
      layout: {
        'text-field': '{point_count_abbreviated}',
        'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold'],
        'text-size': 14
      },
      paint: {
        'text-color': '#ffffff'
      }
    });

    // النقاط الفردية
    map.addLayer({
      id: 'unclustered-point',
      type: 'symbol',
      source: 'tasks',
      filter: ['!', ['has', 'point_count']],
      layout: {
        'icon-image': 'custom-p-icon',
        'icon-size': 1,
        'icon-allow-overlap': true,
        'icon-ignore-placement': true // ✅ يضمن أن الأيقونة تُرسم دائمًا
      }
    });

    // صورة الإبرة المخصصة
    if (!map.hasImage('custom-p-icon')) {
      const canvas = document.createElement('canvas');
      canvas.width = 40;
      canvas.height = 40;
      const ctx = canvas.getContext('2d');

      ctx.fillStyle = '#ff5722';
      ctx.beginPath();
      ctx.arc(20, 20, 16, 0, Math.PI * 2);
      ctx.fill();

      ctx.fillStyle = '#fff';
      ctx.font = 'bold 20px sans-serif';
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText('P', 20, 22);

      const imageData = canvas.toDataURL();
      const img = new Image();
      img.onload = () => {
        const image = {
          width: 40,
          height: 40,
          data: ctx.getImageData(0, 0, 40, 40).data.buffer
        };
        map.addImage('custom-p-icon', {
          width: 40,
          height: 40,
          data: new Uint8Array(image.data)
        });
      };
      img.src = imageData;
    }

    // مركز الخريطة نحو النقاط
    if (features.length > 0) {
      const bounds = new mapboxgl.LngLatBounds();
      features.forEach(f => bounds.extend(f.geometry.coordinates));
      map.fitBounds(bounds, {
        padding: 50,
        maxZoom: 11
      });
    }

    // ✅ عند الضغط على نقطة فردية نعرض تفاصيلها
    map.on('click', 'unclustered-point', function (e) {
      const features = map.queryRenderedFeatures(e.point, {
        layers: ['unclustered-point']
      });

      if (features.length) {
        const taskId = features[0].properties.id;
        showTaskDetails(taskId);
      }
    });

    // ✅ عند الضغط على تجميع نعمل زووم لتفصيله
    map.on('click', 'clusters', function (e) {
      const features = map.queryRenderedFeatures(e.point, {
        layers: ['clusters']
      });

      if (!features.length) return;

      const clusterId = features[0].properties.cluster_id;

      map.getSource('tasks').getClusterExpansionZoom(clusterId, function (err, zoom) {
        if (err) return;

        map.easeTo({
          center: features[0].geometry.coordinates,
          zoom: zoom
        });
      });
    });

    map.on('mouseenter', 'unclustered-point', () => {
      map.getCanvas().style.cursor = 'pointer';
    });
    map.on('mouseleave', 'unclustered-point', () => {
      map.getCanvas().style.cursor = '';
    });

    // تغيير المؤشر على نقاط التجميع
    map.on('mouseenter', 'clusters', () => {
      map.getCanvas().style.cursor = 'pointer';
    });
    map.on('mouseleave', 'clusters', () => {
      map.getCanvas().style.cursor = '';
    });
  }

  function showTaskDetails(taskId) {
    $.ajax({
      url: `${baseUrl}admin/tasks/show/${taskId}`, // تأكد أن هذا المسار يتوافق مع ما أنشأته في الباك
      type: 'GET',
      success: function (task) {
        const statusClass = getStatusBadgeClass(task.data.status);

        console.log(task);
        $('#taskDetailsControl').html(`
           <h5>#${task.data.id}</h5>
           <div class="d-flex">
               <button id="close-task-details" class="btn btn-sm  mb-3">
                  <i class="ti ti-x"></i>
                </button>
              <div class="dropdown ">
                  <button class="btn btn-sm btn-icon  dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end " style="z-index:1100">
                    <li><a href="javascript:;" class="dropdown-item edit-task" data-id="${task.data.id}" >Edit Task</a></li>
                    <li><a href="javascript:;" class="dropdown-item assign-task" data-id="${task.data.id}"  >Assign Driver</a></li>
                    <li><a href="javascript:;" class="dropdown-item status-record" data-id="${task.data.id}" data-name="${task.data.id}" data-status="${task.data.status}">Change Status</a></li>
                  </ul>
              </div>
            </div>
          `);

        const htmlDetails = `
          <div class="card shadow-sm ">
            <div class="card-body">
              <h5 class="card-title mb-3"><i class="ti ti-clipboard-text me-2"></i>Task Details</h5>
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <strong>Status</strong>
                  <span class="badge bg-${statusClass} text-capitalize">${task.data.status}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <strong>Order</strong>
                  <span>${task.data.order_id}</span>
                </li>
                 <li class="list-group-item d-flex justify-content-between align-items-center">
                  <strong>Created At</strong>
                  <span>${task.data.created_at || '—'}</span>
                </li>
                </ul>
                 <div class="divider text-start">
                      <div class="divider-text"><strong>Pickup info</strong></div>
                  </div>
                  <ul class="bg-light list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                  <strong>Name</strong>
                  <span>${task.data.pickup.contact_name || '—'}</span>
                </li>

                  <li class="list-group-item d-flex justify-content-between">
                  <strong>Phone</strong>
                  <span>${task.data.pickup.contact_phone || '—'}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <strong>Email</strong>
                  <span>${task.data.pickup.contact_email || '—'}</span>
                </li>
                 <li class="list-group-item d-flex justify-content-between">
                  <strong>Address</strong>
                  <span>${task.data.pickup.address || '—'}</span>
                </li>

                <li class="list-group-item d-flex justify-content-between">
                  <strong>Pickup Before</strong>
                  <span>${task.data.pickup.scheduled_time || '—'}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <strong>Pickup Note</strong>
                  <span>${task.data.pickup.note || '—'}</span>
                </li>

                <li class="list-group-item d-flex justify-content-between">
                  <strong>Pickup Reference Image</strong>
                  <img src="${task.data.pickup.note || '—'}" >
                </li>
                </ul>


                <div class="divider text-start">
                      <div class="divider-text"><strong>Delivery info</strong></div>
                  </div>
                  <ul class="bg-light list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                  <strong>Name</strong>
                  <span>${task.data.delivery.contact_name || '—'}</span>
                </li>

                  <li class="list-group-item d-flex justify-content-between">
                  <strong>Phone</strong>
                  <span>${task.data.delivery.contact_phone || '—'}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <strong>Email</strong>
                  <span>${task.data.delivery.contact_email || '—'}</span>
                </li>
                 <li class="list-group-item d-flex justify-content-between">
                  <strong>Address</strong>
                  <span>${task.data.delivery.address || '—'}</span>
                </li>

                <li class="list-group-item d-flex justify-content-between">
                  <strong>Delivery Before</strong>
                  <span>${task.data.delivery.scheduled_time || '—'}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <strong> Note</strong>
                  <span>${task.data.delivery.note || '—'}</span>
                </li>

                <li class="list-group-item d-flex justify-content-between">
                  <strong> Reference Image</strong>
                  <img src="${task.data.delivery.note || '—'}" >
                </li>


              </ul>
            </div>
          </div>
          `;

        const htmlCustomer = `
          <div class="card shadow-sm ">
            <div class="card-body">
              <h5 class="card-title mb-3"><i class="ti ti-user me-2"></i>Customer Info</h5>
              <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">
                  <strong>Owner</strong>
                  <span>${task.data.customer.owner || '—'}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <strong>Name</strong>
                  <span>${task.data.customer.name || '—'}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <strong>Phone</strong>
                  <span>${task.data.customer.phone || '—'}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <strong>Email</strong>
                  <span>${task.data.customer.email || '—'}</span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <strong>Address</strong>
                  <span>${task.data.customer.address || '—'}</span>
                </li>
              </ul>
            </div>
          </div>
          `;

        console.log(task.data);
        const htmlHistory = `
        <div class="card shadow-sm ">
          <div class="card-body">
            <h5 class="card-title mb-3">
              <i class="ti ti-clock-hour-4 me-2 mb-3"></i>History
            </h5>
            <ul class="timeline mb-0">
              ${(task.data.history || [])
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

        $('#task-details-view').slideDown(300, () => {
          // تأكيد تفعيل التاب الأول (details)
          const tabTrigger = new bootstrap.Tab(document.querySelector('[data-bs-target="#navs-justified-details"]'));
          tabTrigger.show();

          $('#task-details-content').html(htmlDetails);
          $('#task-owner-content').html(htmlCustomer);
          $('#task-history-content').html(htmlHistory);
        });

        // زر الإغلاق
        $('#close-task-details').on('click', function () {
          $('#task-details-view').stop().fadeOut(200);
        });

        // تحريك الخريطة لنقطة المهمة
        if (task.data.point.latitude && task.data.point.longitude) {
          taskMapInstance.flyTo({
            center: [task.data.point.longitude, task.data.point.latitude],
            zoom: 15,
            speed: 0.8
          });

          if (task.data.point.latitude && task.data.point.longitude) {
            taskMapInstance.flyTo({
              center: [task.data.point.longitude, task.data.point.latitude],
              zoom: 16,
              speed: 0.8
            });

            // إغلاق أي نافذة منبثقة مفتوحة مسبقًا
            if (window.taskPopupInstance) {
              window.taskPopupInstance.remove();
            }

            // إنشاء نافذة منبثقة بدون إبرة
            const popup = new mapboxgl.Popup({ offset: 15, closeOnClick: true })
              .setLngLat([task.data.point.longitude, task.data.point.latitude])
              .setHTML(
                `
                  <div>
                    <strong>Task ID:</strong> ${task.data.id}<br>
                    <strong>Customer:</strong> ${task.data.customer.name || 'No name'}<br>
                    <strong>Status:</strong> ${task.data.status}
                  </div>
                `
              )
              .addTo(taskMapInstance);

            // حفظ النافذة عالميًا لإغلاقها لاحقًا إن لزم
            window.taskPopupInstance = popup;
          }
        }
      }
    });
  }

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
    loadTasks(1, $(this).val());
  });

  loadTasks();

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');
    console.log('osama');
    loadTasks();
    setTimeout(() => {
      $('#submitModal').modal('hide');
      $('#assignModal').modal('hide');
    }, 2000);
  });

  document.addEventListener('deletedSuccess', function (event) {
    loadTasks();
  });

  $(document).on('click', '.status-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const status = $(this).data('status');

    const fields = `
        <input type="hidden" name="id" value="${id}">
        <select class="form-select" name="status">
          <option value="in_progress" ${status === 'in_progress' ? 'selected' : ''}>in progress</option>
          <option value="assign" ${status === 'assign' ? 'selected' : ''}>assign</option>
          <option value="start" ${status === 'start' ? 'selected' : ''}>start</option>
          <option value="completed" ${status === 'completed' ? 'selected' : ''}>completed</option>
          <option value="canceled" ${status === 'canceled' ? 'selected' : ''}>canceled</option>
        </select>
      `;

    showFormModal({
      title: `Change Task: ${name} Status`,
      icon: 'info',
      fields: fields,
      url: `${baseUrl}admin/tasks/status`,
      method: 'POST'
    });
  });

  $(document).on('click', '.assign-task', function () {
    const id = $(this).data('id');

    $.get(`${baseUrl}admin/tasks/assign/${id}`, function (data) {
      if (data.status === 2) {
        showAlert('error', data.error);
        return;
      }
      $('#assignModal').modal('show');
      $('#assignTitle').html(`Assign Task: <span class="bg-info text-white px-2 rounded">#${id}</span>`);

      $('#task-assign-id').val(id);
      let option = ''; // changed from const to let
      data.drivers.forEach(key => {
        option += `<option value="${key.id}" ${data.driver_id === key.id ? 'selected' : ''}>
                ${key.name}
              </option>`;
      });
      $('#task-driver').html(option);
      console.log(data);
    });
  });

  function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  $(document).on('click', '.edit-task', function () {
    var taskId = $(this).data('id');

    $.get(`${baseUrl}admin/tasks/edit/${taskId}`, async function (data) {
      if (data.status === 2) {
        showAlert('error', data.error);
        return;
      }
      $('#task-form').attr('action', `${baseUrl}admin/task/edit`);

      $('#modelTitle').html(`Edit Task: <span class="bg-info text-white px-2 rounded">#${taskId}</span>`);
      // get data
      $('#task-id').val(data.id);
      $('#task-owner').val(data.owner).trigger('change');
      $('#task-customer').val(data.customer_id).trigger('change');
      $('.vehicle-quantity').hide();
      $('.vehicle-select').val(data.vehicle).trigger('change');
      $('#submitModal').modal('show');

      await delay(1000);
      $('.vehicle-type-select').val(data.vehicle_type).trigger('change');

      await delay(1000);
      $('.vehicle-size-select').val(data.vehicle_size_id).trigger('change');

      $('#additional-form').html('');
      $('#select-template').val(data.form_template_id);

      if (data.form_template_id === null) {
        $('#select-template').val(templateId).trigger('change');
      }
      generateFields(data.fields, data.additional_data);

      $('#task-id').attr('data-method', data.pricing_history.pricing_method_id);
      $('#task-id').attr('data-point', data.pricing_history.point_id);

      $('#pickup-contact-name').val(data.pickup.contact_name);
      $('#pickup-contact-phone').val(data.pickup.contact_phone);
      $('#pickup-contact-email').val(data.pickup.contact_emil);
      $('#pickup-before').val(data.pickup.scheduled_time);
      $('#pickup-address').val(data.pickup.address);
      $('#pickup-longitude').val(data.pickup.longitude);
      $('#pickup-latitude').val(data.pickup.latitude);
      $('#pickup-note').val(data.pickup.note);

      $('#delivery-contact-name').val(data.delivery.contact_name);
      $('#delivery-contact-phone').val(data.delivery.contact_phone);
      $('#delivery-contact-email').val(data.delivery.contact_emil);
      $('#delivery-before').val(data.delivery.scheduled_time);
      $('#delivery-address').val(data.delivery.address);
      $('#delivery-longitude').val(data.delivery.longitude);
      $('#delivery-latitude').val(data.delivery.latitude);
      $('#delivery-note').val(data.delivery.note);

      console.log(data);
    });
  });

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/teams/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    new bootstrap.Tab(document.querySelector('#tab-step1')).show();
    $('#taskFinalDetails').html('');
    $('#params-select-wrapper').remove();
    $('.text-error').html('');
    $('#task_id').val('');
    $('.vehicle-select').val('').trigger('change');
    $('#select-template').val(templateId).trigger('change');
    $('#modelTitle').html('Add New Tasks');
  });
});
