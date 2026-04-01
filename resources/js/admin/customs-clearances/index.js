/**
 * Admin Customs Clearances Management
 */

'use strict';
import { deleteRecord, showAlert, showFormModal, generateFields, handleErrors, showBlockAlert } from '../../ajax';

$(function () {
  // Variable declaration for table
  var dt_data_table = $('.datatables-customs-clearances'),
    clearanceView = baseUrl + 'admin/customs-clearances/';

  if (templateId != null) {
    $('#select-template').val(templateId).trigger('change');
  }
  var start_from;
  var end_to;
  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Load statistics
  loadStatistics();

  // Customs Clearances datatable
  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/customs-clearances/data',
        data: function (d) {
          d.search = $('#searchFilter').val();
          d.status = $('#statusFilter').val();
          d.closed = $('#closedFilter').val();
          d.payment = $('#paymentFilter').val();
          d.owner_type = $('#ownerTypeFilter').val();
          d.form_template_id = $('#templateFilter').val();
          d.date_from = start_from;
          d.date_to = end_to;
        },
        dataSrc: function (json) {
          if (json.summary) {
            updateStatisticsCards(json.summary);
          }
          return json.data;
        }
      },
      columns: [
        { data: '' }, // للـ control (responsive)
        { data: 'id' },
        { data: 'price_info' },
        { data: 'owner' },
        { data: 'clearance_agent' },
        { data: 'offers_count' },
        { data: 'public' },
        { data: 'status' },
        { data: 'closed' },
        { data: 'payment' },
        { data: 'created_at' },
        { data: 'actions' }
      ],
      columnDefs: [
        {
          // For Responsive
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          targets: 1,
          searchable: false,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            return `<span class="fw-medium">#${full.id}</span>`;
          }
        },
        {
          targets: 2,
          className: 'text-nowrap w-auto',

          render: function (data, type, full, meta) {
            return full.price_info;
          }
        },
        {
          targets: 3,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            if (full.owner && typeof full.owner === 'object') {
              return `
                <div class="d-flex flex-column">
                  <span class="fw-medium">${full.owner.name}</span>
                  ${full.owner.badge}
                </div>
              `;
            }
            return '<span class="text-muted">غير محدد</span>';
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span class="text-nowrap">${full.clearance_agent}</span><br> <a href="tel:${full.agent_phone}">${full.agent_phone}</a> <br> <a href="mailto:${full.agent_email}"> ${full.agent_email}</a>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return full.offers_count;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            return full.public
              ? '<span class="badge bg-success text-white px-2 py-1 rounded">Public</span>'
              : '<span class="badge bg-info text-white px-2 py-1 rounded">Privet</span>';
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            return full.status;
          }
        },
        {
          targets: 8,
          render: function (data, type, full, meta) {
            return full.closed
              ? '<span class="badge bg-success text-white px-2 py-1 rounded">Closed</span>'
              : '<span class="badge bg-secondary text-white px-2 py-1 rounded">Open</span>';
          }
        },
        {
          targets: 9,
          render: function (data, type, full, meta) {
            return full.payment !== 'completed'
              ? `<span class="badge bg-secondary text-white px-2 py-1 rounded">${full.payment}</span>`
              : `<span class="badge bg-success text-white px-2 py-1 rounded">${full.payment}</span>`;
          }
        },

        {
          targets: 10,
          render: function (data, type, full, meta) {
            return `<span class="text-nowrap">${full.created_at}</span>`;
          }
        },
        {
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return full.actions;
          }
        }
      ],
      order: [[1, 'desc']],
      dom: '<"row"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      lengthMenu: [10, 25, 50, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Customs Clearances...'
      },
      //['waiting', 'completed', 'pending']
      buttons: [
        ` <label class="me-2">
            <select class="form-select mt-5" id="paymentFilter">
                <option value="">${__('Payment Status ')}</option>
                <option value="waiting">${__('waiting')}</option>
                <option value="pending">${__('pending')}</option>
                <option value="completed">${__('completed')}</option>
            </select>
        </lable>`,
        ` <label class="me-2">
            <select class="form-select mt-5" id="closedFilter">
                <option value="">${__('Closed Status ')}</option>
                <option value="true">${__('closed')}</option>
                <option value="false">${__('open')}</option>
            </select>
        </lable>`,
        ` <label class="me-2">
            <select class="form-select mt-5" id="statusFilter">
                <option value="">${__('All Status')}</option>
                <option value="in_progress">${__('In Progress')}</option>
                <option value="assign">${__('Assigned')}</option>
                <option value="start">${__('Started')}</option>
                <option value="completed">${__('Completed')}</option>
                <option value="canceled">${__('Canceled')}</option>
            </select>
        </lable>`,
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="Search Customs Clearances..." />
          </label>`
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of Customs Clearance #' + data['id'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== ''
                ? '<tr data-dt-row="' +
                    col.rowIndex +
                    '" data-dt-column="' +
                    col.columnIndex +
                    '">' +
                    '<td>' +
                    col.title +
                    ':' +
                    '</td> ' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });
  }

  $('.dataTables_filter').hide();

  // Filter event handlers
  $('#searchFilter').on('input', function () {
    dt_data.draw();
  });

  $('#statusFilter, #ownerTypeFilter, #templateFilter').on('change', function () {
    dt_data.draw();
  });

  $('#closedFilter, #ownerTypeFilter, #templateFilter').on('change', function () {
    dt_data.draw();
  });

  $('#paymentFilter, #ownerTypeFilter, #templateFilter').on('change', function () {
    dt_data.draw();
  });

  $('#dateFromFilter, #dateToFilter').on('change', function () {
    dt_data.draw();
  });

  $('#clearFilters').on('click', function () {
    $('#statusFilter, #ownerTypeFilter, #templateFilter').val('').trigger('change');
    $('#dateFromFilter, #dateToFilter').val('');
    start_from = null;
    end_to = null;
    dt_data.draw();
  });

  $('#refreshTable').on('click', function () {
    dt_data.draw();
    loadStatistics();
  });

  document.addEventListener('formSubmitted', function (event) {
    let id = $('#customs_clearance_id').val();
    $('.form_submit').trigger('reset');

    setTimeout(() => {
      $('#submitModal').modal('hide');
      $('#assignModal').modal('hide');
      $('#paymentModal').modal('hide');
    }, 2000);

    if (dt_data) {
      dt_data.draw();
    }
  });

  document.addEventListener('deletedSuccess', function (event) {
    if (dt_data) {
      dt_data.draw();
    }
  });

  // Owner type change handler
  $('#owner_type').on('change', function () {
    const ownerType = $(this).val();
    const customerDiv = $('#customer_select_div');

    if (ownerType === 'customer') {
      customerDiv.show();
      $('#customer_id').prop('required', true);
    } else {
      customerDiv.hide();
      $('#customer_id').prop('required', false).val('');
    }
  });

  // Template selection handler
  $('#select-template').on('change', function () {
    const templateId = $(this).val();
    const additionalForm = $('#additional-form');

    if (templateId) {
      // Generate fields using the existing function
      generateFields(templateId, additionalForm);
    } else {
      additionalForm.empty();
    }
  });

  // Price input handler withe vat and service commission
  $('#customs-price').on('input', function () {
    var price = $(this).val().trim();

    if (price !== '' && parseFloat(price) > 0) {
      $('#price-includes-info').slideDown(); // إظهار الـ div
    } else {
      $('#price-includes-info').slideUp(); // إخفاؤه إذا تم حذف السعر
    }
  });

  // Edit record
  // Edit record
  $(document).on('click', '.edit-record', function () {
    var data_id = $(this).data('id'),
      dtrModal = $('.dtr-bs-modal.show');

    if (dtrModal.length) {
      dtrModal.modal('hide');
    }

    $.get(`${clearanceView}${data_id}/edit`, function (data) {
      $('.form_submit').trigger('reset');
      $('.text-error').html('');

      // Fill basic form fields
      $('#customs_clearance_id').val(data.id);

      // Set owner type and customer
      $('#owner_type')
        .val(data.customer_id ? 'customer' : 'admin')
        .trigger('change');
      if (data.customer_id) {
        $('#customer_id').val(data.customer_id).toggle('change');
      }

      $('#customs-price').val(data.total_price);
      $('#customs-notes').val(data.notes);
      $('#customs-is-public').prop('checked', data.public === 1 || data.public === true);
      $('#customs-included').prop('checked', data.included === 1 || data.included === true);
      if (data.total_price > 0) {
        console.log('osam');
        $('#price-includes-info').slideDown();
      }

      // Set template and generate fields
      const additionalForm = $('#additional-form');
      $('#additional-form').html('');
      $('#select-template').val(data.form_template_id);

      generateFields(data.fields, data.additional_data);
      if (data.form_template_id === null) {
        $('#select-template').val(templateId).trigger('change');
      }

      // Update modal title
      $('#modelTitle').html(`Edit Customs Clearance: <span class="bg-info text-white px-2 rounded">#${data.id}</span>`);
      $('#submitModal').modal('show');
    }).fail(function () {
      showAlert('error', 'Failed to load record data');
    });
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

            <option value="completed" ${status === 'completed' ? 'selected' : ''}>completed</option>
            <option value="canceled" ${status === 'canceled' ? 'selected' : ''}>canceled</option>
          </select>
        `;

    showFormModal({
      title: `Change Task: ${name} Status`,
      icon: 'info',
      fields: fields,
      url: `${baseUrl}admin/customs-clearances/status`,
      method: 'POST'
    });
  });
  document.addEventListener('statusChange', function (event) {
    dt_data.draw();
  });

  // Delete record
  $(document).on('click', '.delete-record', function () {
    const id = $(this).data('id');
    deleteRecord(id, `${clearanceView}/delete${id}`, dt_data);
  });

  $(document).on('click', '.delete-record-force', function () {
    const id = $(this).data('id');
    deleteRecord(id, `${clearanceView}delete-all/${id}`, dt_data);
  });

  // Assign clearance agent
  $(document).on('click', '.assign-clearance', function () {
    const id = $(this).data('id');

    $.get(`${baseUrl}admin/customs-clearances/assign/${id}`, function (data) {
      if (data.status === 2) {
        showAlert('error', data.error);
        return;
      }
      $('#assignModal').modal('show');
      $('#assignTitle').html(`Assign Customs Clearance: <span class="bg-info text-white px-2 rounded">#${id}</span>`);

      $('#task-assign-id').val(id);
      $('#clearance-price').val(data.total_price || 0.0);
      $('#clearance-commission').val(data.commission || 0.0);
      let option = ''; // changed from const to let
      data.brokers.forEach(key => {
        option += `<option value="${key.id}" ${data.clearance_agent_id === key.id ? 'selected' : ''}>
                  ${key.name}
                </option>`;
      });
      $('#clearance-broker').html(option);
      console.log(data);
    });
  });

  // Change Visibility
  $(document).on('click', '.create-ad', function () {
    const id = $(this).data('id');
    const status = $(this).data('public');

    Swal.fire({
      title: `Change Visibility To ${status ? 'Privet' : 'Public'}`,
      text: 'This will The Customs Clearance Visibility Status',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Change it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
        cancelButton: 'btn btn-label-secondary waves-effect waves-light'
      },
      buttonsStyling: false
    }).then(result => {
      if (result.isConfirmed) {
        $.ajax({
          url: `${clearanceView}${id}/create-ad`,
          method: 'POST',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.success) {
              showAlert('success', response.message);

              dt_data.draw();
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
    }).then(result => {
      if (result.isConfirmed) {
        $.ajax({
          url: `${clearanceView}${id}/close`,
          method: 'POST',
          data: {
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.success) {
              showAlert('success', response.message);
              dt_data.draw();
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

  $(document)
    .off('submit', '.payment_submit')
    .on('submit', '.payment_submit', function (e) {
      e.preventDefault();
      const $this = $(this);

      if ($this.hasClass('submitting')) return;
      $this.addClass('submitting');

      $this.block({
        message:
          '<div class="d-flex justify-content-center"><p class="mb-0">Please wait...</p> <div class="sk-wave m-0"><div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div> <div class="sk-rect sk-wave-rect"></div></div> </div>',
        css: {
          backgroundColor: 'transparent',
          color: '#fff',
          border: '0'
        },
        overlayCSS: {
          opacity: 0.5
        }
      });

      // إرسال الطلب Ajax
      $.ajax({
        url: $this.attr('action'),
        method: $this.attr('method'),
        data: new FormData(this),
        processData: false,
        dataType: 'json',
        contentType: false,
        success: function (data) {
          $('span.text-error').text(''); // إعادة تعيين الأخطاء
          console.log(data);
          $this.unblock({
            onUnblock: function () {
              $this.removeClass('submitting'); // إتاحة الإرسال مرة أخرى

              if (data.status === 0) {
                console.log(data.error);
                handleErrors(data.error);
                showBlockAlert('warning', 'حدث خطأ أثناء الإرسال!');
              } else if (data.status === 1) {
                showBlockAlert('success', data.success, 1700);
                showAlert('success', data.success, 5000, true);
                if (data.hyperpay) {
                  setTimeout(function () {
                    window.location.href = data.url;
                  }, 2000);
                } else {
                  dt_data.draw();
                  $('.payment_submit').trigger('reset');
                  $('#paymentModal').modal('hide');
                }
              } else if (data.status === 2) {
                showAlert('error', data.error, 10000, true);
                showBlockAlert('warning', data.error);
              }
            }
          });
        },
        error: function (jqXHR, textStatus, errorThrown) {
          $this.unblock({
            onUnblock: function () {
              $this.removeClass('submitting');
              console.log(errorThrown);
              showAlert('error', `فشل الطلب: ${textStatus}, ${errorThrown}`);
            }
          });
        }
      });
    });
  $(document).on('click', '.payment-record', function () {
    const id = $(this).data('id');
    $('#paymentTitle').html(`Pay Customs Clearance : <span class="bg-info text-white px-2 rounded">#${id}</span>`);
    $.get(`${baseUrl}admin/customs-clearances/payment/${id}`, function (data) {
      console.log(data);
      if (data.customer_id == null) {
        $('#wallet-option').hide();
      } else {
        $('#wallet-option').show();
      }
      if (data.status === 2) {
        showAlert('error', data.error);
        return;
      } else if (data.status === 3) {
        showAlert('success', data.message);
        $('#paymentTitle').html(
          `Check Payment Customs Clearance: <span class="bg-info text-white px-2 rounded">#${id}</span>`
        );
        $('#checkPaymentModal').modal('show');
        $('#task-payment-id').val(id);

        var checkButtons = '';
        if (data.data.status === 'pending') {
          checkButtons = `
              <button type="button" data-id="${data.data.reference_id}"  class="btn btn-primary confirm-payment">Confirm the payment</button>
              <button type="button" data-id="${data.data.reference_id}"  class="btn btn-danger cancel-payment">Undo the process</button>

              <div class="alert alert-danger mt-2">When you cancel a payment, it will be completely deleted along with its files.</div>`;
        } else if (data.data.status === 'failed') {
          checkButtons = `
              <div class="alert alert-danger mt-2">The payment attempt has failed. You can try paying again.</div>
              <button type="button" data-id="${data.data.reference_id}"  class="btn btn-danger cancel-payment">Try Again</button>`;
        }

        var checkHtml = `
          <div class="alert alert-light alert-dismissible">
            <h4 class="alert-heading">Check Payment</h4>
            <p>Customs Clearance ID: <span class="px-3 py-0 bg-info text-white rounded">#${data.data.reference_id}</span></p>
            <p>Transaction ID: <span class="px-3 py-0 bg-info text-white rounded">#${data.data.id}</span></p>
            <p>Amount: <span class="px-3 py-0 bg-info text-white rounded">${data.data.amount} SAR </span></p>
            <p>Payment Method: <span class="px-3 py-0 bg-info text-white rounded"> ${data.data.payment_type} </span></p>
            <p>Payment Status: <span class="px-3 py-0 bg-warning text-white rounded">${data.data.status}</span></p>
            ${
              data.data.payment_type === 'banking'
                ? `
               <p>Payment Receipt: <span class="px-3 py-0 bg-info text-white rounded">${data.data.receipt_number}</span></p>
            <img src="${baseUrl + data.data.receipt_image}" alt="Receipt" class="img-fluid mb-2" style="max-width: 100%; height: auto; "/>

              `
                : ''
            }

            ${data.data.note ? `<p>Payment Note: <span class="px-3 py-0 bg-info text-white rounded">${data.data.note}</span></p>` : ''}
            <p>Payment Created At: <span class="px-3 py-0 bg-info text-white rounded">${data.data.created_at}</span></p>
            <p>Payment Checked By: <span class="px-3 py-0 bg-info text-white rounded">${data.data.user ? data.data.user.name : 'not checked yet'}</span></p>

            <div>
              ${checkButtons}
            </div>

          </div>

          `;
        $('#checkPaymentContainer').html(checkHtml);

        console.log(data);
        return;
      } else {
        $('#task-payment-commission').val(data.commission);
        $('#task-payment-total').val(data.total_price);
        $('#assignTitle').html(`Payment Task: <span class="bg-info text-white px-2 rounded">#${id}</span>`);
        $('#paymentModal').modal('show');
        $('#task-payment-id').val(id);
        $('#pay-price').text(data.total_price + ' SAR');
      }
    });
  });

  $(document).on('click', '.confirm-payment', function () {
    console.log('confirm payment');
    const id = $(this).data('id');
    fetch(baseUrl + 'admin/customs-clearances/payment/confirm/' + id, {
      method: 'GET',
      headers: {
        Accept: 'application/json'
      }
    })
      .then(response => response.json())
      .then(data => {
        if (data.status === 1) {
          showAlert('success', data.message, 5000, true);
          $('#checkPaymentModal').modal('hide');
          dt_data.draw();
        } else {
          showAlert('danger', data.message, 5000, true);
        }
      })
      .catch(error => {
        showAlert('danger', 'Error to connect with server', 5000, true);
        console.error(error);
      });
  });

  $(document).on('click', '.cancel-payment', function () {
    console.log('confirm payment');
    const id = $(this).data('id');
    fetch(baseUrl + 'admin/customs-clearances/payment/cancel/' + id, {
      method: 'GET',
      headers: {
        Accept: 'application/json'
      }
    })
      .then(response => response.json())
      .then(data => {
        if (data.status === 1) {
          showAlert('success', data.message, 5000, true);
          $('#checkPaymentModal').modal('hide');
          dt_data.draw();
        } else {
          showAlert('danger', data.message, 5000, true);
        }
      })
      .catch(error => {
        showAlert('danger', 'Error to connect with server', 5000, true);
        console.error(error);
      });
  });

  $(document).on('change', '#task-payment-method', function () {
    const method = $(this).val();
    if (method === 'credit') {
      $('#receipt-section').hide();

      $('#pay-price').text($('#task-payment-total').val() + ' SAR');
    } else if (method === 'cash') {
      $('#receipt-section').hide();

      $('#pay-price').text('You need to Bay :' + $('#task-payment-commission').val() + ' SAR by credit card');
    } else if (method === 'wallet') {
      $('#receipt-section').hide();
      $('#pay-price').html(
        'You need to Bay' +
          $('#task-payment-total').val() +
          ' SAR From your wallet </br> <h6 class="alert alert-info">Check if your wallet and have the enough balance</h6>'
      );
    } else {
      $('#receipt-section').show();
      $('#pay-price').text($('#task-payment-total').val() + ' SAR');
    }
  });

  // Modal reset on hide
  $('#submitModal').on('hidden.bs.modal', function () {
    $('.form_submit')[0].reset();
    $('#additional-form').empty();
    $('#customer_select_div').hide();
    $('#customs_clearance_id').val('');
    $('#modelTitle').text('Add New Customs Clearance');
    $('#price-includes-info').slideUp();
    $('.text-error').text('');
    $('.current-file').remove();
  });

  // Initialize Select2 for customer dropdown
  $('#customer_id').select2({
    dropdownParent: $('#submitModal'),
    placeholder: 'Select Customer',
    allowClear: true
  });

  // Initialize flatpickr for date filters
  if (typeof flatpickr !== 'undefined') {
    $('.flatpickr-date').flatpickr({
      dateFormat: 'Y-m-d',
      allowInput: true
    });
  }

  // Load statistics function
  function loadStatistics() {
    $.ajax({
      url: baseUrl + 'admin/customs-clearances/statistics',
      method: 'GET',
      success: function (response) {
        if (response.success) {
          updateStatisticsCards(response.data);
        }
      },
      error: function () {
        console.log('Failed to load statistics');
      }
    });
  }

  // Update statistics cards
  function updateStatisticsCards(stats) {
    // Update statistics cards if they exist
    $('.stat-total').text(stats.total || 0);
    $('.stat-in-progress').text(stats.in_progress || 0);
    $('.stat-completed').text(stats.completed || 0);
    $('.stat-canceled').text(stats.canceled || 0);
    $('.stat-total-value').text(stats.total_value ? `${stats.total_value.toLocaleString()} ر.س` : '0 ر.س');
  }

  $('#dateRange').daterangepicker(
    {
      opens: 'left',
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
      },
      startDate: moment().startOf('month'),
      endDate: moment().endOf('month')
    },
    function (start, end, label) {
      const startDate = start.format('YYYY-MM-DD');
      const endDate = end.format('YYYY-MM-DD');
      start_from = startDate;
      end_to = endDate;
      dt_data.draw();
    }
  );
  // Initialize tooltips
  $('[data-bs-toggle="tooltip"]').tooltip();
});
