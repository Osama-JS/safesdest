/**
 * Admin Customs Clearances Management
 */

'use strict';
import { locale } from 'moment/moment';
import { deleteRecord, showAlert, showFormModal, generateFields, handleErrors, showBlockAlert } from '../../ajax';

document.addEventListener('formSubmitted', function (event) {
  $('.form_submit').trigger('reset');
  $('#total-price').text('');
  setTimeout(() => {
    $('#addNoteModal').modal('hide');
    location.reload();
  }, 2000);

  loadOffers();
});

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
