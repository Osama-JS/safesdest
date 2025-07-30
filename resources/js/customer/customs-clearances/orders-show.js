/**
 * Admin Customs Clearances Management
 */

'use strict';
import { deleteRecord, showAlert, showFormModal, generateFields, handleErrors, showBlockAlert } from '../../ajax';
const buttons = document.querySelectorAll('.step-circle');

buttons.forEach(button => {
  button.addEventListener('click', function () {
    const form = this.closest('form');
    const status = this.getAttribute('data-status');

    Swal.fire({
      title: __('Are you sure?'),
      text: `${__('The task status will be changed to')} "${__(status)}"`,
      icon: 'warning',
      confirmButtonColor: '#3085d6',
      confirmButtonText: __('Yes, change status'),
      showCancelButton: true,
      customClass: {
        confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
        cancelButton: 'btn btn-label-secondary waves-effect waves-light'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  });
});
