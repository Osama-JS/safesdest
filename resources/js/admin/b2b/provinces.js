/**
 * B2B Provinces Management
 */

'use strict';

$(function () {
  var dt_provinces_table = $('.datatables-provinces');
  var provinceForm = $('#province-form');
  var provinceModal = $('#provinceModal');

  if (dt_provinces_table.length) {
    var dt_provinces = dt_provinces_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: baseUrl + 'admin/b2b/provinces/data',
      columns: [
        { data: 'id' },
        { data: 'name_ar' },
        { data: 'name_en' },
        { data: 'region' },
        { data: 'status' },
        { data: null }
      ],
      columnDefs: [
        {
          targets: 1,
          render: function (data, type, full, meta) {
            return `<span class="fw-medium text-heading">${data}</span>`;
          }
        },
        {
          targets: -2,
          render: function (data, type, full, meta) {
            let badge = data === 'active' ? 'bg-label-success' : 'bg-label-danger';
            return `<span class="badge ${badge} text-capitalize">${data}</span>`;
          }
        },
        {
          targets: -1,
          title: 'Actions',
          orderable: false,
          render: function (data, type, full, meta) {
            return `
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-icon edit-province btn-label-primary me-1" data-id="${full.id}"><i class="ti ti-edit"></i></button>
                    <button class="btn btn-sm btn-icon text-danger delete-province btn-label-danger" data-id="${full.id}"><i class="ti ti-trash"></i></button>
                </div>
            `;
          }
        }
      ],
      dom: '<"row"<"col-md-2"l><"col-md-10 d-flex justify-content-end"fB>>t<"row"<"col-md-6"i><"col-md-6"p>>',
      buttons: [
        {
          text: '<i class="ti ti-plus me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">Add Province</span>',
          className: 'add-new btn btn-primary ms-3',
          action: function () {
            $('#province-id').val('');
            provinceForm[0].reset();
            $('#province-status').prop('checked', true);
            provinceModal.modal('show');
          }
        }
      ],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Province...'
      }
    });
  }

  // Handle Form Submission
  provinceForm.on('submit', function (e) {
    e.preventDefault();
    var formData = $(this).serialize();
    
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.text-error').text('');

    $.post(baseUrl + 'admin/b2b/provinces/store', formData, function (res) {
      if (res.status === 1) {
        Swal.fire('Success', res.success, 'success');
        provinceModal.modal('hide');
        dt_provinces.draw();
      } else if (res.status === 0) {
        // Validation Errors
        if (typeof res.error === 'object') {
          $.each(res.error, function (key, messages) {
            var field = $(`[name="${key}"]`);
            field.addClass('is-invalid');
            $(`.${key}-error`).text(messages[0]);
          });
          Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please check the highlighted fields.',
            customClass: { confirmButton: 'btn btn-primary' }
          });
        } else {
          Swal.fire('Error', res.error, 'error');
        }
      } else {
        Swal.fire('Error', res.error || 'Something went wrong', 'error');
      }
    });
  });

  // Edit Province
  $(document).on('click', '.edit-province', function () {
    var id = $(this).data('id');
    $.get(baseUrl + 'admin/b2b/provinces/' + id, function (data) {
      $('#province-id').val(data.id);
      $('#province-name-ar').val(data.name_ar);
      $('#province-name-en').val(data.name_en);
      $('#province-region').val(data.region);
      $('#province-status').prop('checked', data.is_active == 1);
      provinceModal.modal('show');
    });
  });

  // Delete Province
  $(document).on('click', '.delete-province', function () {
    var id = $(this).data('id');
    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: baseUrl + 'admin/b2b/provinces/' + id,
          type: 'DELETE',
          data: { _token: $('meta[name="csrf-token"]').attr('content') },
          success: function (res) {
            Swal.fire('Deleted!', res.success, 'success');
            dt_provinces.draw();
          }
        });
      }
    });
  });
});
