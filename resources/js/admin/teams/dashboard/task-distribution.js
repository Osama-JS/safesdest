/**
 * Team Task Distribution Page
 */

'use strict';
import { initDashboard, showAlert, setButtonLoading, validateForm, resetFormValidation } from './common.js';

$(function () {
  // Initialize common dashboard functionality
  initDashboard();
  // Initialize Select2
  $('.select2').select2({
    placeholder: 'Choose a driver...',
    allowClear: true
  });

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Assignment type change handler
  $('#assignment-type').on('change', function () {
    const assignmentType = $(this).val();

    if (assignmentType === 'specific') {
      $('#specific-driver-section').show();
      $('#selected-driver').prop('required', true);
    } else {
      $('#specific-driver-section').hide();
      $('#selected-driver').prop('required', false);
      $('#selected-driver').val('').trigger('change');
    }
  });

  // Form validation and submission
  $('#taskDistributionForm').on('submit', function (e) {
    e.preventDefault();

    if (!validateForm('#taskDistributionForm')) {
      return;
    }

    const formData = new FormData(this);
    const submitBtn = $(this).find('button[type="submit"]');

    setButtonLoading(submitBtn, true, 'Assigning...');

    // Simulate task assignment (replace with actual API call)
    setTimeout(() => {
      showAlert('success', 'Task has been assigned successfully', 'Success!');
      resetForm();
      setButtonLoading(submitBtn, false);
    }, 2000);
  });

  // Reset form
  $('#reset-form').on('click', function () {
    resetForm();
  });

  // Preview task
  $('#preview-task').on('click', function () {
    if (!validateForm('#taskDistributionForm')) {
      showAlert('warning', 'Please fill in all required fields before previewing', 'Validation Error');
      return;
    }

    generateTaskPreview();
    $('#taskPreviewModal').modal('show');
  });

  // Confirm assignment from preview
  $('#confirm-assignment').on('click', function () {
    $('#taskPreviewModal').modal('hide');
    $('#taskDistributionForm').submit();
  });

  // Real-time driver status updates (simulate)
  setInterval(updateDriverStatus, 30000); // Update every 30 seconds

  // Form field validations
  $('#task-price').on('input', function () {
    const price = parseFloat($(this).val());
    if (price < 0) {
      $(this).val(0);
    }
  });

  // Auto-suggest based on assignment type
  $('#assignment-type').on('change', function () {
    const type = $(this).val();

    if (type === 'auto') {
      suggestBestDriver();
    }
  });
});

/**
 * Reset form to initial state
 */
function resetForm() {
  $('#taskDistributionForm')[0].reset();
  resetFormValidation('#taskDistributionForm');
  $('#selected-driver').val('').trigger('change');
  $('#assignment-type').trigger('change');

  // Reset datetime inputs to current time + 1 hour
  const now = new Date();
  const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000);
  const twoHoursLater = new Date(now.getTime() + 2 * 60 * 60 * 1000);

  $('#start-time').val(formatDateTimeLocal(oneHourLater));
  $('#complete-time').val(formatDateTimeLocal(twoHoursLater));
}

/**
 * Generate task preview
 */
function generateTaskPreview() {
  const formData = new FormData($('#taskDistributionForm')[0]);
  const assignmentType = formData.get('assignment_type');
  const selectedDriverId = formData.get('driver_id');

  let driverInfo = '';
  if (assignmentType === 'specific' && selectedDriverId) {
    const driver = availableDrivers.find(d => d.id == selectedDriverId);
    if (driver) {
      driverInfo = `
        <div class="alert alert-info">
          <h6><i class="ti ti-user me-2"></i>Assigned Driver</h6>
          <p class="mb-0"><strong>${driver.name}</strong> - ${driver.email}</p>
        </div>
      `;
    }
  } else if (assignmentType === 'broadcast') {
    driverInfo = `
      <div class="alert alert-warning">
        <h6><i class="ti ti-speakerphone me-2"></i>Broadcast Assignment</h6>
        <p class="mb-0">This task will be sent to all available drivers in the team</p>
      </div>
    `;
  } else if (assignmentType === 'auto') {
    driverInfo = `
      <div class="alert alert-success">
        <h6><i class="ti ti-robot me-2"></i>Auto Assignment</h6>
        <p class="mb-0">System will automatically assign to the best available driver</p>
      </div>
    `;
  }

  const previewContent = `
    <div class="row">
      <div class="col-md-6">
        <h6>Task Information</h6>
        <table class="table table-borderless">
          <tr><td><strong>Title:</strong></td><td>${formData.get('title')}</td></tr>
          <tr><td><strong>Priority:</strong></td><td><span class="badge bg-label-${getPriorityColor(formData.get('priority'))}">${formData.get('priority')}</span></td></tr>
          <tr><td><strong>Price:</strong></td><td>${formData.get('price')} SAR</td></tr>
          <tr><td><strong>Payment:</strong></td><td>${formData.get('payment_method')}</td></tr>
        </table>
      </div>
      <div class="col-md-6">
        <h6>Timing</h6>
        <table class="table table-borderless">
          <tr><td><strong>Start Before:</strong></td><td>${formData.get('start_before') || 'Not specified'}</td></tr>
          <tr><td><strong>Complete Before:</strong></td><td>${formData.get('complete_before') || 'Not specified'}</td></tr>
        </table>
      </div>
    </div>

    <div class="row mt-3">
      <div class="col-md-6">
        <h6>Pickup Location</h6>
        <p class="text-muted">${formData.get('pickup_address')}</p>
      </div>
      <div class="col-md-6">
        <h6>Delivery Location</h6>
        <p class="text-muted">${formData.get('delivery_address')}</p>
      </div>
    </div>

    <div class="mt-3">
      <h6>Description</h6>
      <p class="text-muted">${formData.get('description')}</p>
    </div>

    ${
      formData.get('notes')
        ? `
      <div class="mt-3">
        <h6>Additional Notes</h6>
        <p class="text-muted">${formData.get('notes')}</p>
      </div>
    `
        : ''
    }

    ${driverInfo}
  `;

  $('#task-preview-content').html(previewContent);
}

/**
 * Get priority color class
 */
function getPriorityColor(priority) {
  switch (priority) {
    case 'urgent':
      return 'danger';
    case 'high':
      return 'warning';
    case 'normal':
      return 'info';
    default:
      return 'secondary';
  }
}

/**
 * Format date for datetime-local input
 */
function formatDateTimeLocal(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');

  return `${year}-${month}-${day}T${hours}:${minutes}`;
}

/**
 * Update driver status (simulate real-time updates)
 */
function updateDriverStatus() {
  // This would typically make an AJAX call to get updated driver statuses
  // For now, we'll just add a visual indicator
  $('.badge').css('opacity', '0.7');
  setTimeout(() => {
    $('.badge').css('opacity', '1');
  }, 500);
}

/**
 * Suggest best driver for auto-assignment
 */
function suggestBestDriver() {
  // Simple algorithm to suggest best driver
  // In a real implementation, this would consider factors like:
  // - Driver location
  // - Current workload
  // - Performance rating
  // - Availability

  const onlineDrivers = availableDrivers.filter(driver => driver.status === 'active' && driver.online);

  if (onlineDrivers.length > 0) {
    // For demo, just pick the first online driver
    const suggestedDriver = onlineDrivers[0];

    Swal.fire({
      title: 'Driver Suggestion',
      html: `
        <p>Based on current availability and location, we suggest:</p>
        <div class="alert alert-info">
          <strong>${suggestedDriver.name}</strong><br>
          <small class="text-muted">${suggestedDriver.email}</small>
        </div>
        <p>Would you like to assign this task to ${suggestedDriver.name}?</p>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, assign to this driver',
      cancelButtonText: 'No, keep auto-assignment'
    }).then(result => {
      if (result.isConfirmed) {
        $('#assignment-type').val('specific').trigger('change');
        $('#selected-driver').val(suggestedDriver.id).trigger('change');
      }
    });
  } else {
    Swal.fire({
      title: 'No Available Drivers',
      text: 'No drivers are currently online and available for assignment.',
      icon: 'warning'
    });
  }
}

/**
 * Validate form fields in real-time
 */
function setupRealTimeValidation() {
  $('#task-title, #task-description, #pickup-address, #delivery-address, #task-price').on('blur', function () {
    if ($(this).val().trim() === '') {
      $(this).addClass('is-invalid');
    } else {
      $(this).removeClass('is-invalid').addClass('is-valid');
    }
  });
}

// Initialize real-time validation
setupRealTimeValidation();

// Initialize form with default values
$(document).ready(function () {
  resetForm();
});
