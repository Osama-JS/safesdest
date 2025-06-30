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
      data: {
        from_date: start_from,
        to_date: end_to,
        owner: $('#owner-fillter').val(),
        team: $('#team-fillter').val(),
        driver: $('#driver-fillter').val(),
        search: search,
        filter: filter,
        page: page
      },
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
      const driverHtml = task.driver
        ? `<div class="mt-2  small bg-primary  text-white p-1 rounded ">
            <i class="bi bi-truck"></i> Driver: ${task.driver.name} (${task.driver.phone_code} ${task.driver.phone})
         </div>`
        : '';
      const teamHtml =
        task.driver && task.driver.team
          ? `<div class="mt-2  small text-white bg-success p-1 rounded  ">
        <i class="bi bi-truck"></i> Team: ${task.driver.team}
     </div>`
          : '';

      const completeAt = task.complete_at
        ? `
        <div class='mt-2 text-muted small text-white  '>
          <i class='bi bi-truck'></i> Complete At: ${task.complete_at}
        </div>`
        : '';

      const card = `
        <div class="mb-4">
          <div class="card p-3 shadow-sm task-card" data-task-id="${task.id}">
            <div class="d-flex justify-content-between">
              <div class="d-flex align-items-center">
                <img src="${task.avatar}" class="rounded-circle me-3" width="60" height="60" style="object-fit: cover;" alt="Avatar">
                <div class="px-3">
                  <h6 class="mb-1">${task.name}</h6>
                  <p>${task.point.address || ''} (${task.point.longitude} - ${task.point.latitude})</p>
                   ${driverHtml}
                ${teamHtml}
                ${completeAt}
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
                    ${task.data.status !== 'advertised' ? `<li><a href="javascript:;" class="dropdown-item edit-task-pricing" data-id="${task.data.id}" >Edit Task Pricing</a></li>` : ``}
                    ${task.data.status === 'advertised' ? `<li><a href="javascript:;" class="dropdown-item edit-task-ad" data-id="${task.data.id}" >Edit Task Ad</a></li>` : ``}
                    ${!task.data.closed ? `<li><a href="${baseUrl}admin/tasks/tracking/${task.data.id}" target="_blank"  class="dropdown-item "  >Tracking Task</a></li>` : ``}
                    <li><a href="javascript:;" class="dropdown-item assign-task" data-id="${task.data.id}"  >Assign Driver</a></li>
                    <li><a href="javascript:;" class="dropdown-item status-record" data-id="${task.data.id}" data-name="${task.data.id}" data-status="${task.data.status}">Change Status</a></li>
                    <li><a href="javascript:;" class="dropdown-item task-report" data-id="${task.data.id}">download task status report</a></li>

                  </ul>
              </div>
            </div>
          `);

        const driverInfo = task.data.driver
          ? `
               <div class="divider text-start">
                      <div class="divider-text"><strong>Driver info</strong></div>
                  </div>
              <div class=" d-flex align-items-center">
                <img src="${baseUrl}${task.data.driver.image || 'assets/img/person.png'}"
                    alt="Driver Image"
                    class="rounded-circle me-3 border"
                    style="width: 70px; height: 70px; object-fit: cover;">
                <ul class="list-unstyled mb-0">
                  <li><strong>Name:</strong> ${task.data.driver.name}</li>
                  <li class="my-2"><strong>Phone:</strong> ${task.data.driver.phone}</li>
                  <li><strong>Email:</strong> ${task.data.driver.email}</li>
                  <li><a href="https://wa.me/${task.data.driver.whatsapp}" target="_blank" class="btn btn-sm btn-success mt-2"> <i class="ti ti-brand-whatsapp me-1"></i> ${task.data.driver.whatsapp}</a></li>
                </ul>
              </div>

            `
          : '';

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
                ${driverInfo}
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
                  ${task.data.pickup.image ? ` <img style=" width: 100px;" src="${baseUrl + task.data.pickup.image || '—'}" >` : ''}

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
                  ${task.data.delivery.image ? ` <img style=" width: 100px;" src="${baseUrl + task.data.delivery.image || '—'}" >` : ''}
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
      case 'started':
      case 'in pickup point':
      case 'loading':
      case 'in the way':
      case 'in delivery point':
      case 'unloading':
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

  /* ===========  Advanced Filtering System   ===========*/

  // Initialize filtering variables
  let start_from = moment().startOf('day').format('YYYY-MM-DD');
  let end_to = moment().endOf('day').format('YYYY-MM-DD');

  // Initialize date range picker
  $('#dateRange').daterangepicker(
    {
      startDate: start_from,
      endDate: end_to,
      locale: {
        format: 'YYYY-MM-DD',
        separator: ' to ',
        applyLabel: 'Apply',
        cancelLabel: 'Cancel',
        fromLabel: 'From',
        toLabel: 'To',
        customRangeLabel: 'Custom',
        weekLabel: 'W',
        daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
        monthNames: [
          'January',
          'February',
          'March',
          'April',
          'May',
          'June',
          'July',
          'August',
          'September',
          'October',
          'November',
          'December'
        ],
        firstDay: 1
      },
      ranges: {
        Today: [moment(), moment()],
        Yesterday: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
      }
    },
    function (start, end, label) {
      start_from = start.format('YYYY-MM-DD');
      end_to = end.format('YYYY-MM-DD');
      console.log('Date range changed:', start_from, 'to', end_to);

      // Reload map data with new filters
      loadTasks();
    }
  );

  // Initialize Select2 for drivers dropdown
  var select_driver = $('.task-drivers-select2');
  if (select_driver.length) {
    var $this = select_driver;

    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: 'All drivers',
      dropdownParent: $this.parent(),
      closeOnSelect: false,
      ajax: {
        url: baseUrl + 'admin/drivers/git',
        dataType: 'json',
        delay: 250,
        data: function (params) {
          return {
            search: params.term
          };
        },
        processResults: function (data) {
          console.log(data);
          return {
            results: data.map(driver => ({
              id: driver.id,
              text: driver.name
            }))
          };
        },
        cache: true
      }
    });
  }

  // Initialize Select2 for teams dropdown
  var select_team = $('.task-teams-select2');
  if (select_team.length) {
    select_team.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: 'All teams',
      dropdownParent: select_team.parent()
    });
  }

  // Filter change handlers
  $('#owner-fillter, #team-fillter, #driver-fillter').on('change', function () {
    console.log('Filter changed:', $(this).attr('id'), $(this).val());
    loadTasks();
  });

  loadTasks();

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');
    setTimeout(() => {
      $('#submitModal').modal('hide');
      $('#assignModal').modal('hide');
      $('#adModal').modal('hide');
      $('#pricingModal').modal('hide');
      loadTasks();
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
          <option value="started" ${status === 'started' ? 'selected' : ''}>started</option>
          <option value="in pickup point" ${status === 'in pickup point' ? 'selected' : ''}>in pickup point</option>
          <option value="loading" ${status === 'loading' ? 'selected' : ''}>loading</option>
          <option value="in the way" ${status === 'in the way' ? 'selected' : ''}>in the way</option>
          <option value="in delivery point" ${status === 'in delivery point' ? 'selected' : ''}>in delivery point</option>
          <option value="unloading" ${status === 'unloading' ? 'selected' : ''}>unloading</option>
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
  document.addEventListener('statusChange', function (event) {
    loadTasks();
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

  $(document).on('click', '.edit-task-pricing', function () {
    const id = $(this).data('id');

    $.get(`${baseUrl}admin/tasks/pricing/edit/${id}`, function (data) {
      if (data.status === 2) {
        showAlert('error', data.error);
        return;
      }
      $('#pricing-id').val(data.data.id);
      $('#pricing-total-price').val(data.data.total_price);

      $('#pricing-commission').val(data.data.commission);
      renderPricingDetails(data.data.pricing_details, $('#pricing-pricing-details-container'));
      $('#pricingModal').modal('show');
      $('#pricingTitle').html(`Edit Task Pricing: <span class="bg-info text-white px-2 rounded">#${id}</span>`);
    });
  });

  $(document).on('click', '.edit-task-ad', function () {
    const id = $(this).data('id');

    $.get(`${baseUrl}admin/ads/task/edit/${id}`, function (data) {
      if (data.status === 2) {
        showAlert('error', data.error);
        return;
      }
      $('#ad-id').val(data.data.id);
      $('#ad-min-price').val(data.data.lowest_price);
      $('#ad-max-price').val(data.data.highest_price);
      $('#ad-not-price').text(data.data.description);
      $('#adModal').modal('show');
      $('#adTitle').html(`Edit Task Ad: <span class="bg-info text-white px-2 rounded">#${id}</span>`);
    });
  });

  $(document).on('click', '.task-report', function () {
    const id = $(this).data('id');
    const reportWindow = window.open(`${baseUrl}admin/task/${id}/report`, '_blank');
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
      $('#task-form').attr('action', `${baseUrl}admin/tasks/edit`);

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

      if (data.pricing_history.pricing_method_id == 0) {
        $('#task-id').attr('data-min', data.ad.lowest_price || 0.0);
        $('#task-id').attr('data-max', data.ad.highest_price || 0.0);
        $('#task-id').attr('data-note', data.ad.description || '');
      }

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

      if (data.pricing_type === 'manual') {
        $('#total-price').val(data.total_price);
        $(`<span  class="ms-2 badge bg-success task-priceing-hint">${__('the price set manual')}</span>`).insertAfter(
          '#total-price'
        );
      }
      if (data.commission_type === 'manual') {
        $('#task-commission').val(data.commission);
        $(
          `<span class="ms-2 badge bg-success task-priceing-hint">${__('the commission set manual')}</span>`
        ).insertAfter('#task-commission');
      }

      renderPricingDetails(data.pricing_details);

      console.log(data);
    });
  });

  let Detailsindex = 0;

  $('#pricing-add-pricing-details').on('click', function () {
    const detailHTML = `
            <div class="row mb-2 pricing-detail-row">
                <div class="col-md-6">
                    <input type="text" name="pricing_details[${Detailsindex}][label]" class="form-control" placeholder="Detail description" required>
                </div>
                <div class="col-md-4">
                    <input type="number" name="pricing_details[${Detailsindex}][amount]" step="any" class="form-control" placeholder="Amount" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-detail">&times;</button>
                </div>
            </div>
        `;
    $('#pricing-pricing-details-container').append(detailHTML);
    Detailsindex++;
  });

  $('#add-pricing-details').on('click', function () {
    const detailHTML = `
            <div class="row mb-2 pricing-detail-row">
                <div class="col-md-6">
                    <input type="text" name="pricing_details[${Detailsindex}][label]" class="form-control" placeholder="Detail description" required>
                </div>
                <div class="col-md-4">
                    <input type="number" name="pricing_details[${Detailsindex}][amount]" step="any" class="form-control" placeholder="Amount" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-detail">&times;</button>
                </div>
            </div>
        `;
    $('#pricing-details-container').append(detailHTML);
    Detailsindex++;
  });

  function renderPricingDetails(pricing_details, container = $('#pricing-details-container')) {
    container.empty();

    if (Array.isArray(pricing_details) && pricing_details.length > 0) {
      pricing_details.forEach((detail, index) => {
        const detailHTML = `
        <div class="row mb-2 pricing-detail-row">
          <div class="col-md-6">
            <input type="text" name="pricing_details[${index}][label]" class="form-control" value="${detail.label}" placeholder="Detail description" required>
          </div>
          <div class="col-md-4">
            <input type="number" name="pricing_details[${index}][amount]" step="any" class="form-control" value="${detail.amount}" placeholder="Amount" required>
          </div>
          <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger btn-sm remove-detail">&times;</button>
          </div>
        </div>
      `;
        container.append(detailHTML);
      });

      // تحديث Detailsindex إلى العدد الحالي
      console.log(pricing_details.length);
      Detailsindex = pricing_details.length; // يرجعها إلى 5
    } else {
      Detailsindex = 0; // يرجعها إلى 5
    }
  }

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/teams/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  $('#submitModal, #assignModal, #adModal, #pricingModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    new bootstrap.Tab(document.querySelector('#tab-step1')).show();
    $('#taskFinalDetails').html('');
    $('#params-select-wrapper').remove();
    $('.text-error').html('');
    $('#task_id').val('');
    $('.task-priceing-hint').remove();
    $('#pricing-details-container').html('');
    $('.vehicle-select').val('').trigger('change');
    $('#select-template').val(templateId).trigger('change');
    $('#modelTitle').html('Add New Tasks');
    Detailsindex = 0;
  });
});
