import { showAlert } from '../../../ajax.js';

$(function () {
  /* ===========  AJAX Setup   ===========*/
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  /* ===========  Initialize   ===========*/
  loadFilteredTasks();

  /* ===========  Event Handlers   ===========*/

  // Refresh tasks button
  $('#refresh-tasks').on('click', function () {
    loadFilteredTasks();
  });

  // Assign task button click
  $(document).on('click', '.assign-task', function () {
    const taskId = $(this).data('id');
    const vehicleSizeId = $(this).data('vehicle-size-id');

    // Get drivers with matching vehicle size
    const matchingDrivers = availableDrivers.filter(driver => driver.vehicle_size_id == vehicleSizeId);

    if (matchingDrivers.length === 0) {
      showAlert('error', 'No drivers available with matching vehicle size for this task.');
      return;
    }

    // Show assignment modal
    $('#assignModal').modal('show');
    $('#assignTitle').html(`Assign Task: <span class="bg-info text-white px-2 rounded">#${taskId}</span>`);
    $('#task-assign-id').val(taskId);

    // Populate driver dropdown
    let driverOptions = '<option value="">Select Driver</option>';
    matchingDrivers.forEach(driver => {
      // Check if driver has active tasks (simplified check)
      const hasActiveTasks = driver.active_tasks_count > 0;
      const warningIcon = hasActiveTasks ? ' ⚠️' : '';
      const warningClass = hasActiveTasks ? 'text-warning' : '';

      driverOptions += `<option value="${driver.id}" class="${warningClass}">
        ${driver.name}${warningIcon}
        ${hasActiveTasks ? ' (Has active tasks)' : ''}
      </option>`;
    });

    $('#task-driver').html(driverOptions);
  });

  // Form submission handler (reuse existing form submission logic)
  document.addEventListener('formSubmitted', function (event) {
    setTimeout(() => {
      $('#assignModal').modal('hide');
      loadFilteredTasks(); // Refresh tasks after assignment
    }, 2000);
  });

  /* ===========  Functions   ===========*/

  function loadFilteredTasks() {
    showLoading(true);

    $.ajax({
      url: `${baseUrl}admin/teams/${teamID}/filtered-tasks`,
      type: 'GET',
      success: function (response) {
        showLoading(false);

        if (response.success) {
          updateTeamInfo(response.team_info);
          renderTasks(response.data);
          updateTasksCount(response.total_tasks);
        } else {
          showAlert('error', response.message || 'Failed to load tasks');
          showNoTasksMessage();
        }
      },
      error: function (xhr, status, error) {
        showLoading(false);
        console.error('Error loading tasks:', error);
        showAlert('error', 'Failed to load tasks. Please try again.');
        showNoTasksMessage();
      }
    });
  }

  function updateTeamInfo(teamInfo) {
    $('#team-geofences-count').text(teamInfo.geofence_count || 0);
    $('#vehicle-sizes-count').text(teamInfo.driver_vehicle_sizes ? teamInfo.driver_vehicle_sizes.length : 0);
  }

  function updateTasksCount(count) {
    $('#available-tasks-count').text(count);
  }

  function renderTasks(tasks) {
    const container = $('#tasks-container');
    container.empty();

    if (!tasks || tasks.length === 0) {
      showNoTasksMessage();
      return;
    }

    $('#no-tasks-message').hide();

    tasks.forEach(task => {
      const taskCard = createTaskCard(task);
      container.append(taskCard);
    });
  }

  function createTaskCard(task) {
    const pickupAddress = task.pickup_location.address || 'Address not available';
    const deliveryAddress = task.delivery_location.address || 'Address not available';
    const vehicleSizeName = task.vehicle_size_name || 'Unknown';
    const totalPrice = task.total_price || '0';
    const createdAt = new Date(task.created_at).toLocaleDateString();

    return `
      <div class="mb-4">
        <div class="card p-3 shadow-sm task-card" data-task-id="${task.id}">
          <div class="d-flex justify-content-between">
            <div class="d-flex align-items-start flex-grow-1">
              <div class="avatar avatar-sm me-3">
                <div class="avatar-initial bg-label-primary rounded">
                  <i class="ti ti-truck-delivery"></i>
                </div>
              </div>
              <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <h6 class="mb-0">Task #${task.id}</h6>
                  <span class="badge bg-warning text-capitalize">${task.status.replace('_', ' ')}</span>
                </div>

                <div class="row g-2 mb-2">
                  <div class="col-md-6">
                    <small class="text-muted d-block">
                      <i class="ti ti-map-pin me-1"></i><strong>Pickup:</strong>
                    </small>
                    <small class="text-truncate d-block" title="${pickupAddress}">
                      ${pickupAddress}
                    </small>
                  </div>
                  <div class="col-md-6">
                    <small class="text-muted d-block">
                      <i class="ti ti-map-pin-filled me-1"></i><strong>Delivery:</strong>
                    </small>
                    <small class="text-truncate d-block" title="${deliveryAddress}">
                      ${deliveryAddress}
                    </small>
                  </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                  <div class="d-flex gap-2">
                    <span class="badge bg-label-info">
                      <i class="ti ti-truck me-1"></i>${vehicleSizeName}
                    </span>
                    <span class="badge bg-label-success">
                      <i class="ti ti-currency-riyal me-1"></i>${totalPrice} SAR
                    </span>
                    <small class="text-muted">
                      <i class="ti ti-calendar me-1"></i>${createdAt}
                    </small>
                  </div>
                  <button type="button"
                          class="btn btn-sm btn-primary assign-task"
                          data-id="${task.id}"
                          data-vehicle-size-id="${task.vehicle_size_id}">
                    <i class="ti ti-user-plus me-1"></i>Assign
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  function showLoading(show) {
    if (show) {
      $('#tasks-loading').show();
      $('#tasks-container').hide();
      $('#no-tasks-message').hide();
    } else {
      $('#tasks-loading').hide();
      $('#tasks-container').show();
    }
  }

  function showNoTasksMessage() {
    $('#tasks-container').hide();
    $('#no-tasks-message').show();
  }

  function getStatusBadgeClass(status) {
    const statusClasses = {
      in_progress: 'warning',
      assign: 'info',
      started: 'primary',
      completed: 'success',
      canceled: 'danger',
      advertised: 'secondary'
    };
    return statusClasses[status] || 'secondary';
  }

  // Initialize Select2 for driver dropdown
  $('#task-driver').select2({
    dropdownParent: $('#assignModal'),
    placeholder: 'Select Driver',
    allowClear: true
  });
});
