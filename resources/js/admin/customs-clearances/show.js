/**
 * Admin Customs Clearances Show Page
 */

'use strict';
import { deleteRecord, showAlert } from '../../ajax';

$(function () {
  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Edit clearance
  $(document).on('click', '.edit-clearance', function () {
    const id = $(this).data('id');
    window.location.href = baseUrl + 'admin/customs-clearances?edit=' + id;
  });

  // Assign clearance agent
  $(document).on('click', '.assign-clearance', function () {
    const id = $(this).data('id');
    
    Swal.fire({
      title: 'Assign Clearance Agent',
      html: `
        <select id="agent-select" class="form-select">
          <option value="">Select Agent...</option>
        </select>
      `,
      showCancelButton: true,
      confirmButtonText: 'Assign',
      preConfirm: () => {
        const agentId = $('#agent-select').val();
        if (!agentId) {
          Swal.showValidationMessage('Please select an agent');
          return false;
        }
        return agentId;
      },
      didOpen: () => {
        // Load agents
        $.ajax({
          url: baseUrl + 'admin/clearance-agents/list',
          method: 'GET',
          success: function (response) {
            if (response.success) {
              const select = $('#agent-select');
              response.data.forEach(agent => {
                select.append(`<option value="${agent.id}">${agent.name}</option>`);
              });
            }
          }
        });
      }
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: baseUrl + `admin/customs-clearances/${id}/assign`,
          method: 'POST',
          data: {
            clearance_agent_id: result.value,
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.success) {
              showAlert('success', response.message);
              location.reload();
            } else {
              showAlert('error', response.message);
            }
          },
          error: function () {
            showAlert('error', 'Failed to assign agent');
          }
        });
      }
    });
  });

  // Create advertisement
  $(document).on('click', '.create-ad', function () {
    const id = $(this).data('id');
    
    Swal.fire({
      title: 'Create Advertisement',
      text: 'This will create a public advertisement for this customs clearance request.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Create Advertisement',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: baseUrl + `admin/customs-clearances/${id}/create-ad`,
          method: 'POST',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.success) {
              showAlert('success', response.message);
              location.reload();
            } else {
              showAlert('error', response.message);
            }
          },
          error: function () {
            showAlert('error', 'Failed to create advertisement');
          }
        });
      }
    });
  });

  // Close clearance
  $(document).on('click', '.close-clearance', function () {
    const id = $(this).data('id');
    
    Swal.fire({
      title: 'Close Customs Clearance',
      text: 'Are you sure you want to close this customs clearance request? This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Close It',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#d33'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: baseUrl + `admin/customs-clearances/${id}/close`,
          method: 'POST',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.success) {
              showAlert('success', response.message);
              location.reload();
            } else {
              showAlert('error', response.message);
            }
          },
          error: function () {
            showAlert('error', 'Failed to close clearance');
          }
        });
      }
    });
  });

  // Delete clearance
  $(document).on('click', '.delete-clearance', function () {
    const id = $(this).data('id');
    
    Swal.fire({
      title: 'Delete Customs Clearance',
      text: 'Are you sure you want to delete this customs clearance request? This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Delete It',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#d33'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: baseUrl + `admin/customs-clearances/${id}`,
          method: 'DELETE',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.success || response.status === 1) {
              showAlert('success', response.message || response.success);
              window.location.href = baseUrl + 'admin/customs-clearances';
            } else {
              showAlert('error', response.message || response.error);
            }
          },
          error: function () {
            showAlert('error', 'Failed to delete clearance');
          }
        });
      }
    });
  });

  // Initialize tooltips
  $('[data-bs-toggle="tooltip"]').tooltip();
});
