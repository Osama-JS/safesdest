/**
 * Common functionality for Team Dashboard pages
 */

'use strict';

/**
 * Show alert message using SweetAlert2 or fallback
 */
export function showAlert(type, message, title = null) {
  if (typeof Swal !== 'undefined') {
    const config = {
      text: message,
      icon: type,
      confirmButtonText: 'OK',
      customClass: {
        confirmButton: 'btn btn-primary'
      }
    };
    
    if (title) {
      config.title = title;
    }
    
    Swal.fire(config);
  } else {
    // Fallback to browser alert
    alert((title ? title + ': ' : '') + message);
  }
}

/**
 * Show confirmation dialog
 */
export function showConfirmation(title, message, confirmText = 'Yes', cancelText = 'No') {
  return new Promise((resolve) => {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        title: title,
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        customClass: {
          confirmButton: 'btn btn-primary',
          cancelButton: 'btn btn-secondary'
        }
      }).then((result) => {
        resolve(result.isConfirmed);
      });
    } else {
      resolve(confirm(title + '\n' + message));
    }
  });
}

/**
 * Format number with thousand separators
 */
export function formatNumber(num) {
  if (typeof num !== 'number') {
    num = parseFloat(num) || 0;
  }
  
  return new Intl.NumberFormat('en-US').format(num);
}

/**
 * Format currency
 */
export function formatCurrency(amount, currency = 'SAR') {
  if (typeof amount !== 'number') {
    amount = parseFloat(amount) || 0;
  }
  
  return new Intl.NumberFormat('en-US', {
    style: 'decimal',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount) + ' ' + currency;
}

/**
 * Format date for display
 */
export function formatDate(date, format = 'short') {
  if (!date) return '';
  
  const dateObj = typeof date === 'string' ? new Date(date) : date;
  
  if (format === 'short') {
    return dateObj.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  } else if (format === 'long') {
    return dateObj.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
  
  return dateObj.toLocaleDateString();
}

/**
 * Debounce function
 */
export function debounce(func, wait, immediate = false) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      timeout = null;
      if (!immediate) func.apply(this, args);
    };
    const callNow = immediate && !timeout;
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    if (callNow) func.apply(this, args);
  };
}

/**
 * Show loading state on button
 */
export function setButtonLoading(button, loading = true, originalText = null) {
  const $btn = $(button);
  
  if (loading) {
    if (!originalText) {
      originalText = $btn.html();
      $btn.data('original-text', originalText);
    }
    $btn.html('<i class="ti ti-loader ti-spin me-1"></i>Loading...').prop('disabled', true);
  } else {
    const storedText = $btn.data('original-text') || originalText || 'Submit';
    $btn.html(storedText).prop('disabled', false);
    $btn.removeData('original-text');
  }
}

/**
 * Initialize tooltips
 */
export function initTooltips(selector = '[data-bs-toggle="tooltip"]') {
  const tooltipTriggerList = [].slice.call(document.querySelectorAll(selector));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
}

/**
 * Initialize popovers
 */
export function initPopovers(selector = '[data-bs-toggle="popover"]') {
  const popoverTriggerList = [].slice.call(document.querySelectorAll(selector));
  popoverTriggerList.map(function (popoverTriggerEl) {
    return new bootstrap.Popover(popoverTriggerEl);
  });
}

/**
 * Handle AJAX errors
 */
export function handleAjaxError(xhr, textStatus, errorThrown) {
  let errorMessage = 'An error occurred';
  
  if (xhr.responseJSON && xhr.responseJSON.message) {
    errorMessage = xhr.responseJSON.message;
  } else if (xhr.responseJSON && xhr.responseJSON.error) {
    errorMessage = xhr.responseJSON.error;
  } else if (xhr.responseText) {
    try {
      const response = JSON.parse(xhr.responseText);
      errorMessage = response.message || response.error || errorMessage;
    } catch (e) {
      errorMessage = xhr.responseText;
    }
  }
  
  showAlert('error', errorMessage);
}

/**
 * Setup CSRF token for AJAX requests
 */
export function setupCSRF() {
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
}

/**
 * Validate form fields
 */
export function validateForm(formSelector) {
  const form = document.querySelector(formSelector);
  if (!form) return false;
  
  // Add Bootstrap validation classes
  form.classList.add('was-validated');
  
  // Check HTML5 validity
  return form.checkValidity();
}

/**
 * Reset form validation
 */
export function resetFormValidation(formSelector) {
  const form = document.querySelector(formSelector);
  if (!form) return;
  
  form.classList.remove('was-validated');
  
  // Clear custom error messages
  form.querySelectorAll('.text-error').forEach(el => {
    el.textContent = '';
  });
  
  // Remove invalid classes
  form.querySelectorAll('.is-invalid').forEach(el => {
    el.classList.remove('is-invalid');
  });
}

/**
 * Display form validation errors
 */
export function displayFormErrors(errors, formSelector = null) {
  // Clear previous errors
  if (formSelector) {
    resetFormValidation(formSelector);
  }
  
  Object.keys(errors).forEach(field => {
    const errorElement = $(`.${field}-error`);
    const inputElement = $(`[name="${field}"]`);
    
    if (errorElement.length) {
      errorElement.text(Array.isArray(errors[field]) ? errors[field][0] : errors[field]);
    }
    
    if (inputElement.length) {
      inputElement.addClass('is-invalid');
    }
  });
}

/**
 * Get URL parameter
 */
export function getUrlParameter(name) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(name);
}

/**
 * Update URL parameter without page reload
 */
export function updateUrlParameter(key, value) {
  const url = new URL(window.location);
  if (value) {
    url.searchParams.set(key, value);
  } else {
    url.searchParams.delete(key);
  }
  window.history.replaceState({}, '', url);
}

/**
 * Copy text to clipboard
 */
export function copyToClipboard(text) {
  if (navigator.clipboard && window.isSecureContext) {
    return navigator.clipboard.writeText(text);
  } else {
    // Fallback for older browsers
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    return new Promise((resolve, reject) => {
      try {
        document.execCommand('copy');
        textArea.remove();
        resolve();
      } catch (error) {
        textArea.remove();
        reject(error);
      }
    });
  }
}

/**
 * Initialize common dashboard functionality
 */
export function initDashboard() {
  // Setup CSRF token
  setupCSRF();
  
  // Initialize tooltips and popovers
  initTooltips();
  initPopovers();
  
  // Handle navigation active states
  updateNavigationActiveState();
  
  // Setup common event handlers
  setupCommonEventHandlers();
}

/**
 * Update navigation active state
 */
function updateNavigationActiveState() {
  const currentPath = window.location.pathname;
  $('.nav-link').each(function() {
    const href = $(this).attr('href');
    if (href && currentPath.includes(href.split('/').pop())) {
      $(this).addClass('active');
    }
  });
}

/**
 * Setup common event handlers
 */
function setupCommonEventHandlers() {
  // Handle copy buttons
  $(document).on('click', '[data-copy]', function() {
    const textToCopy = $(this).data('copy');
    copyToClipboard(textToCopy).then(() => {
      showAlert('success', 'Copied to clipboard');
    }).catch(() => {
      showAlert('error', 'Failed to copy to clipboard');
    });
  });
  
  // Handle refresh buttons
  $(document).on('click', '[data-refresh]', function() {
    const target = $(this).data('refresh');
    if (target === 'page') {
      window.location.reload();
    } else if (target && window[target] && typeof window[target].draw === 'function') {
      window[target].draw();
    }
  });
}
