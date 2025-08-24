/**
 * Notes Management JavaScript
 * Handles CRUD operations for user notes with AJAX
 */

'use strict';

import { showAlert } from './ajax';
$(document).ready(function () {
  // AJAX setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Notes sidebar elements
  const $notesSidebar = $('#notes-sidebar');
  const $notesToggle = $('#notes-toggle');
  const $sidebarClose = $('#notes-sidebar-close');
  const $sidebarOverlay = $('#notes-sidebar-overlay');
  const $notesList = $('#notes-list');
  const $notesLoading = $('#notes-loading');
  const $noNotes = $('#no-notes');
  const $addNoteForm = $('#add-note-form');
  const $editNoteForm = $('#edit-note-form');
  const $editNoteModal = $('#edit-note-modal');

  // Toggle sidebar
  $notesToggle.on('click', function () {
    toggleSidebar();
  });

  // Close sidebar
  $sidebarClose.on('click', closeSidebar);
  $sidebarOverlay.on('click', closeSidebar);

  // Close sidebar on escape key
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape' && $notesSidebar.hasClass('show')) {
      closeSidebar();
    }
  });

  // Add note form submission
  $addNoteForm.on('submit', function (e) {
    e.preventDefault();
    addNote();
  });

  // Cancel add note
  $('#cancel-add-note').on('click', function () {
    resetAddForm();
  });

  // Edit note form submission
  $('#save-note-changes').on('click', function () {
    updateNote();
  });

  // Functions
  function toggleSidebar() {
    if ($notesSidebar.hasClass('show')) {
      closeSidebar();
    } else {
      openSidebar();
    }
  }

  function openSidebar() {
    $notesSidebar.addClass('show');
    $('body').addClass('notes-sidebar-open');
    loadNotes();
  }

  function closeSidebar() {
    $notesSidebar.removeClass('show');
    $('body').removeClass('notes-sidebar-open');
    resetAddForm();
  }

  function loadNotes() {
    showLoading();

    $.ajax({
      url: baseUrl + 'admin/notes',
      method: 'GET',
      success: function (response) {
        hideLoading();
        if (response.status === 1) {
          displayNotes(response.data);
        } else {
          showAlert('error', 'Failed to load notes', 10000, true);
        }
      },
      error: function (xhr) {
        hideLoading();
        showAlert('error', 'Error loading notes: ' + getErrorMessage(xhr), 10000, true);
      }
    });
  }

  function displayNotes(notes) {
    if (notes.length === 0) {
      $noNotes.removeClass('d-none');
      $notesList.find('.note-card').remove();
      return;
    }

    $noNotes.addClass('d-none');
    $notesList.find('.note-card').remove();

    notes.forEach(function (note) {
      const noteCard = createNoteCard(note);
      $notesList.append(noteCard);
    });
  }

  function createNoteCard(note) {
    const createdAt = new Date(note.created_at).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });

    return `
            <div class="note-card" data-note-id="${note.id}">
                <div class="note-title">${escapeHtml(note.title)}</div>
                <div class="note-content">${escapeHtml(note.content)}</div>
                <div class="note-meta">
                    <span>${createdAt}</span>
                    <div class="note-actions">
                        <button type="button" class="btn btn-outline-primary btn-sm edit-note" data-note-id="${note.id}">
                            <i class="ti ti-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm delete-note" data-note-id="${note.id}">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
  }

  function addNote() {
    const formData = new FormData($addNoteForm[0]);

    // Clear previous errors
    $('.text-error').text('');

    // Show loading on submit button
    const $submitBtn = $addNoteForm.find('button[type="submit"]');
    const originalText = $submitBtn.html();
    $submitBtn.html('<i class="ti ti-loader ti-spin me-1"></i>Adding...').prop('disabled', true);

    $.ajax({
      url: baseUrl + 'admin/notes',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        $submitBtn.html(originalText).prop('disabled', false);

        if (response.status === 1) {
          showAlert('success', 'Note added successfully', 3000, true);
          resetAddForm();
          loadNotes();
        } else {
          showAlert('error', response.message || 'Failed to add note', 10000, true);
        }
      },
      error: function (xhr) {
        $submitBtn.html(originalText).prop('disabled', false);
        handleFormErrors(xhr, $addNoteForm);
      }
    });
  }

  function editNote(noteId) {
    const $noteCard = $(`.note-card[data-note-id="${noteId}"]`);
    const title = $noteCard.find('.note-title').text();
    const content = $noteCard.find('.note-content').text();

    $('#edit-note-id').val(noteId);
    $('#edit-note-title').val(title);
    $('#edit-note-content').val(content);

    // Clear previous errors
    $editNoteForm.find('.text-error').text('');

    $editNoteModal.modal('show');
  }

  function updateNote() {
    const noteId = $('#edit-note-id').val();
    const formData = new FormData($editNoteForm[0]);

    // Clear previous errors
    $editNoteForm.find('.text-error').text('');

    // Show loading on save button
    const $saveBtn = $('#save-note-changes');
    const originalText = $saveBtn.html();
    $saveBtn.html('<i class="ti ti-loader ti-spin me-1"></i>Saving...').prop('disabled', true);

    $.ajax({
      url: `${baseUrl}admin/notes/${noteId}`,
      method: 'PUT',
      data: {
        title: formData.get('title'),
        content: formData.get('content'),
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        $saveBtn.html(originalText).prop('disabled', false);

        if (response.status === 1) {
          showAlert('success', 'Note updated successfully', 3000, true);

          $editNoteModal.modal('hide');
          loadNotes();
        } else {
          showAlert('error', response.message || 'Failed to update note', 10000, true);
        }
      },
      error: function (xhr) {
        $saveBtn.html(originalText).prop('disabled', false);
        handleFormErrors(xhr, $editNoteForm);
      }
    });
  }

  function deleteNote(noteId) {
    Swal.fire({
      title: 'Delete Note?',
      text: 'Are you sure you want to delete this note? This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel',
      customClass: {
        confirmButton: 'btn btn-danger me-3',
        cancelButton: 'btn btn-outline-secondary'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        $.ajax({
          url: `${baseUrl}admin/notes/${noteId}`,
          method: 'DELETE',
          success: function (response) {
            if (response.status === 1) {
              showAlert('success', 'Note deleted successfully', 3000, true);

              loadNotes();
            } else {
              showAlert('error', response.message || 'Failed to delete note', 10000, true);
            }
          },
          error: function (xhr) {
            showError('Error deleting note: ' + getErrorMessage(xhr));
          }
        });
      }
    });
  }

  // Event delegation for dynamic elements
  $notesList.on('click', '.edit-note', function () {
    const noteId = $(this).data('note-id');
    editNote(noteId);
  });

  $notesList.on('click', '.delete-note', function () {
    const noteId = $(this).data('note-id');
    deleteNote(noteId);
  });

  // Utility functions
  function resetAddForm() {
    $addNoteForm[0].reset();
    $addNoteForm.find('.text-error').text('');
  }

  function showLoading() {
    $notesLoading.removeClass('d-none');
    $noNotes.addClass('d-none');
    $notesList.find('.note-card').remove();
  }

  function hideLoading() {
    $notesLoading.addClass('d-none');
  }

  function escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function (m) {
      return map[m];
    });
  }

  function getErrorMessage(xhr) {
    if (xhr.responseJSON && xhr.responseJSON.message) {
      return xhr.responseJSON.message;
    }
    return 'An unexpected error occurred';
  }

  function handleFormErrors(xhr, $form) {
    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
      const errors = xhr.responseJSON.errors;
      Object.keys(errors).forEach(function (field) {
        $form.find(`.${field}-error`).text(errors[field][0]);
      });
    } else {
      showError('Error: ' + getErrorMessage(xhr));
    }
  }

  function showSuccess(message) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: message,
        timer: 3000,
        showConfirmButton: false
      });
    } else {
      alert(message);
    }
  }

  function showError(message) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: message
      });
    } else {
      alert(message);
    }
  }
});
