/**
 * Page User List
 */

'use strict';
import { set } from 'lodash';
import { deleteRecord, showAlert, showBlockAlert, generateFields, showFormModal, connectTeam } from '../../ajax';
import writtenNumber from 'written-number';

writtenNumber.defaults.lang = 'ar';

// Datatable (jquery)
$(function () {
  // Variable declaration for table
  var dt_data_table = $('.datatables-tasks'),
    userView = baseUrl + 'admin/customers/account/';

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

  var select_team = $('.task-teams-select2');
  if (select_team.length) {
    var $this = select_team;
    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: 'Select team',
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

  var start_from;
  var end_to;

  // Users datatable
  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/tasks/list/data',
        data: function (d) {
          d.from_date = start_from;
          d.to_date = end_to;
          d.owner = $('#owner-fillter').val();
          d.team = $('#team-fillter').val();
          d.driver = $('#driver-fillter').val();
          d.status_filter = $('#statusFilter').val();
          // البحث من الحقل المخصص
          if ($('#searchFilter').val()) {
            d.search = { value: $('#searchFilter').val() };
          }
        }
      },
      columns: [
        { data: '' }, // للـ control (responsive)
        { data: 'id' }, // للـ checkboxes
        { data: 'id' }, // الترقيم التسلسلي
        { data: 'customer_task_number' },
        { data: 'order' }, // الاسم مع الأفاتار
        { data: 'price', visible: (typeof canViewTotalPrice !== 'undefined' && canViewTotalPrice) }, // السعر الإجمالي (يظهر فقط لمن لديه الصلاحية)
        { data: 'driver_price' }, // الاسم مع الأفاتار
        { data: 'team' }, // البريد
        { data: 'driver' }, // الجوال
        { data: 'vehicle_info' }, // المركبة
        { data: 'owner' }, // المالك
        { data: 'address' }, // العنوان
        { data: 'start' }, // التاريخ
        { data: 'complete' }, // التاريخ
        { data: 'status' }, // الحالة
        { data: 'task' }, // الدفع
        { data: null }, // closed
        { data: null } // actions
      ],
      columnDefs: [
        {
          targets: 0,
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 1,
          render: function () {
            return '';
          }
        },
        {
          targets: 1,
          searchable: false,
          orderable: false,
          responsivePriority: 3,
          render: function (data, type, full, meta) {
            // لا يمكن تحديد المهام المرتبطة بسائق
            if (full.driver !== '-') {
              return '';
            }
            return `<input type="checkbox" class="form-check-input dt-checkboxes" value="${full.id}">`;
          }
        },
        {
          targets: 2,
          searchable: false,
          orderable: false,
          responsivePriority: 1,
          render: function (data, type, full, meta) {
            let html = `<span>${full.id}</span>`;
            if (full.investor_name) {
              html += `<br><small class="text-info fw-bold">ممولة من: ${full.investor_name}</small>`;
            }
            return html;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return full.customer_task_number
              ? `<span class="badge bg-info">C#${full.customer_task_number}</span>`
              : `<span>-</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span>${full.order || '-'}</span>`;
          }
        },
        {
          targets: 5,
          responsivePriority: 2,
          className: 'text-nowrap w-auto',
          render: function (data, type, full, meta) {
            return `<span class="border border-primary rounded text-primary px-2"><strong>${full.price} SAR</strong></span>`;
          }
        },
        {
          targets: 6,
          responsivePriority: 2,
          className: 'text-nowrap w-auto',
          render: function (data, type, full, meta) {
            return `<span class="border border-info rounded text-info px-2"><strong>${full.driver_price} SAR</strong></span>`;
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            return `<span>${full.team}</span>`;
          }
        },
        {
          targets: 8,
          responsivePriority: 7,
          render: function (data, type, full, meta) {
            return full.driver === '-'
              ? `<span>-</span>`
              : `
            <p class="p-0 m-0">ID: ${full.driver.id}</p>
            <p class="p-0 m-0">Name: ${full.driver.name}</p>
            <p class="p-0 m-0">Email: ${full.driver.email}</p>
            <p class="p-0 m-0">Username: ${full.driver.username}</p>
            `;
          }
        },
        {
          targets: 9,
          render: function (data, type, full, meta) {
            if (!full.vehicle_info) return '<span>-</span>';
            return `
              <div class="d-flex flex-column">
                <span class="text-nowrap fw-medium text-heading">${full.vehicle_info.truck_name}</span>
                <small class="text-truncate text-muted">${full.vehicle_info.type} (${full.vehicle_info.size})</small>
              </div>
            `;
          }
        },
        {
          targets: 10,
          render: function (data, type, full, meta) {
            return `<span>${full.owner} <br> (${full.owner_info})</span>`;
          }
        },
        {
          targets: 11,
          render: function (data, type, full, meta) {
            return `<span>${full.address}</span>`;
          }
        },
        {
          targets: 12,
          render: function (data, type, full, meta) {
            return `<span>${full.start}</span>`;
          }
        },
        {
          targets: 13,
          render: function (data, type, full, meta) {
            return `<span>${full.complete}</span>`;
          }
        },
        {
          targets: 14,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            let colorClass = '';

            switch (full.status) {
              case 'advertised':
                colorClass = 'badge bg-secondary'; // رمادي
                break;
              case 'in_progress':
                colorClass = 'badge bg-primary'; // أزرق
                break;
              case 'assign':
                colorClass = 'badge bg-info'; // سماوي
                break;
              case 'accepted':
                colorClass = 'badge bg-warning text-dark'; // أصفر
                break;
              case 'start':
                colorClass = 'badge bg-dark'; // أسود
                break;
              case 'completed':
                colorClass = 'badge bg-success'; // أخضر
                break;
              case 'canceled':
                colorClass = 'badge bg-danger'; // أحمر
                break;
              default:
                colorClass = 'badge bg-light text-dark'; // افتراضي
            }

            return `<span class="w-100 text-center ${colorClass}">${full.status.replace('_', ' ')}</span>`;
          }
        },
        {
          targets: 15,
          responsivePriority: 5,
          render: function (data, type, full, meta) {
            let colorClass = '';
            switch (full.payment) {
              case 'waiting':
                colorClass = 'badge bg-secondary';
                break;
              case 'completed':
                colorClass = 'badge bg-primary';
                break;
              case 'just_commission':
                colorClass = 'badge bg-info';
                break;
              default:
                colorClass = 'badge bg-light text-dark';
            }

            return `<span class="w-100 text-center ${colorClass}">${full.payment.replace('_', ' ')}</span>`;
          }
        },
        {
          targets: 16,
          responsivePriority: 7,
          render: function (data, type, full, meta) {
            if (full.closed) {
              var deliveryHtml = '';
              if (full.delivery) {
                deliveryHtml =
                  '<br><span class="text-muted small fw-medium mt-1 d-block" style="font-size: 0.75rem;">Delivery Number: ' +
                  full.delivery +
                  '</span>';
              }
              return '<span class="badge bg-success">Closed</span>' + deliveryHtml;
            }
            return '<span class="badge bg-danger">No</span>';
          }
        },
        {
          targets: 17,
          title: __('Actions'),
          searchable: false,
          orderable: false,
          responsivePriority: 3,
          render: function (data, type, full, meta) {
            // تحديد إمكانية الحذف بناءً على الحالة
            const canDelete =
              ['in_progress', 'advertised'].includes(full.status) &&
              full.payment !== 'completed' &&
              full.payment !== 'pending' &&
              !full.closed;

            return `
              <div class="d-flex align-items-center gap-2">

                <div class="dropdown">
                  <button class="btn btn-sm btn-icon  dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a href="javascript:;" class="dropdown-item payment-task"  data-id="${full.id}"><i class="ti ti-credit-card me-2"></i>${__('Payment Task')}</a></li>
                    ${1 == 1 ? `<li><a href="javascript:;" class="dropdown-item connect-task"  data-id="${full.id}">${__('Connect')}</a></li>` : ''}
                    <li><a href="${baseUrl}admin/tasks/list/show/${full.id}" class="dropdown-item status-record" data-id="${full.id}" data-name="${full.name}" data-status="${full.status}"><i class="ti ti-eye me-2"></i>${__('View Details')}</a></li>
                    ${full.closed ? '' : `<li><a href="javascript:;" class="dropdown-item closed-record" data-id="${full.id}" ><i class="ti ti-lock me-2"></i>${__('Close Task')}</a></li>`}
                    ${full.status === 'in_progress' && full.driver === '-' ? `<li><a href="javascript:;" class="dropdown-item share-task-whatsapp" data-id="${full.id}" data-price="${full.driver_price}" data-pickup="${full.pickup_address}" data-delivery="${full.delivery_address}" data-truck-name="${full.vehicle_info ? full.vehicle_info.truck_name : '-'}" data-vehicle-type="${full.vehicle_info ? full.vehicle_info.type : '-'}" data-vehicle-size="${full.vehicle_info ? full.vehicle_info.size : '-'}" data-customer="${full.customer_task_number}"><i class="ti ti-brand-whatsapp me-2"></i>${__('Share on WhatsApp')}</a></li>` : ''}
                    ${full.order ? `<li><a href="javascript:;" class="dropdown-item share-order-whatsapp" data-order-id="${full.order}" data-task-id="${full.id}"><i class="ti ti-brand-whatsapp me-2"></i>${__('Share Order on WhatsApp')}</a></li>` : ''}
                    <li><a href="javascript:;" class="dropdown-item  refund-task" data-id="${full.id}"><i class="ti ti-arrow-back me-2"></i>${__('Refund Task')}</a></li>
                    <li><a href="javascript:;" class="dropdown-item  fix-connection-task" data-id="${full.id}">${__('Fix Connection')}</a></li>
                    ${!full.closed ? `<li><a href="${baseUrl}admin/tasks/tracking/${full.id}" target="_blank" class="dropdown-item "><i class="ti ti-map-pin me-2"></i>${__('Tracking Task')}</a></li>` : ``}
                    <li><a href="${baseUrl}admin/tasks/${full.id}/report" class="dropdown-item task-report" data-id="${full.id}"><i class="ti ti-file me-2"></i>${__('download task status report')}</a></li>
                    <li><a href="javascript:;" class="dropdown-item edit-task-broker" data-id="${full.id}"><i class="ti ti-truck me-2"></i>${__('Connect Broker')}</a></li>

                    ${canDelete ? `<li><hr class="dropdown-divider"></li><li><a href="javascript:;" class="dropdown-item text-danger delete-task" data-id="${full.id}" data-status="${full.status}" data-payment="${full.payment}"><i class="ti ti-trash me-1"></i>${__('Delete Task')}</a></li>` : ''}
                  </ul>
                </div>
              </div>`;
          }
        }
      ],
      order: [[1, 'desc']],
      dom:
        '<"row"' +
        '<"col-md-2"l>' +
        '<"col-md-10 d-flex justify-content-end"fB>' +
        '>t' +
        '<"row mt-3"' +
        '<"col-md-6"i>' +
        '<"col-md-6"p>' +
        '>',
      lengthMenu: [10, 25, 50, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search...',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="ti ti-chevron-right"></i>',
          previous: '<i class="ti ti-chevron-left"></i>'
        }
      },
      buttons: [
        `<label class='me-2'>
          <select id="statusFilter" class="form-select d-inline-block w-auto ms-2 mt-5">
        <option value="">All Status</option>
        <option value="advertised">Advertised</option>
        <option value="in_progress">In Progress</option>
        <option value="assign">Assign</option>
        <option value="started">Started</option>
        <option value="in pickup point">In Pickup Point</option>
        <option value="loading">Loading</option>
        <option value="in the way">In The Way</option>
        <option value="in delivery point">In Delivery Point</option>
        <option value="unloading">Unloading</option>
        <option value="completed">Completed</option>
        <option value="canceleds">canceled</option>
      </select>

        </label>`,
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="Search Tasks" />
          </label>`
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data.name;
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col) {
              return col.hidden && col.title
                ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                      <td>${col.title}:</td>
                      <td>${col.data}</td>
                   </tr>`
                : '';
            }).join('');
            return $('<table class="table"/><tbody />').append(data);
          }
        }
      }
    });

    $('#statusFilter').on('change', function () {
      dt_data.draw();
    });

    $('#searchFilter').on('input', function () {
      dt_data.draw();
    });

    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_data }));
  }

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');

    setTimeout(() => {
      $('#paymentModal').modal('hide');
      $('#closedModal').modal('hide');
      $('#checkPaymentModal').modal('hide');
      $('#brokerModal').modal('hide');
      $('#assignTitle').html('');
      $('#refundModal').html('');
    }, 2000);

    if (dt_data) {
      dt_data.draw();
    }
  });

  $(document).on('click', '.edit-task-broker', function () {
    const id = $(this).data('id');

    $.get(`${baseUrl}admin/tasks/broker/edit/${id}`, function (data) {
      if (data.status === 2) {
        showAlert('error', data.error);
        return;
      }
      $('#broker-task-id').val(data.data.id);
      
      $('#task-brokers-container').empty();
      taskBrokerIndex = 0;
      if (data.data.brokers && data.data.brokers.length > 0) {
        data.data.brokers.forEach(broker => {
          let $row = $(createTaskBrokerRow(taskBrokerIndex));
          $row.find('[name="brokers['+taskBrokerIndex+'][broker_id]"]').val(broker.pivot.broker_id);
          $row.find('[name="brokers['+taskBrokerIndex+'][commission_type]"]').val(broker.pivot.commission_type);
          $row.find('[name="brokers['+taskBrokerIndex+'][commission_value]"]').val(broker.pivot.commission_value);
          $('#task-brokers-container').append($row);
          taskBrokerIndex++;
        });
      }
      $('#brokerModal').modal('show');
      $('#brokerTitle').html(`${__('Connect Broker')}: <span class="bg-info text-white px-2 rounded">#${id}</span>`);
    });
  });

  let taskBrokerIndex = 0;
  function createTaskBrokerRow(index) {
    return $('#task-broker-row-template').html().replaceAll('{index}', index);
  }

  $(document).on('click', '#add-task-broker-row', function() {
    const $newRow = $(createTaskBrokerRow(taskBrokerIndex++));
    $('#task-brokers-container').append($newRow);
  });

  $(document).on('click', '.remove-task-broker-row', function() {
    $(this).closest('.broker-row').remove();
  });

  $('#owner-fillter').on('change', function () {
    dt_data.draw();
  });
  $('#team-fillter').on('change', function () {
    dt_data.draw();
  });
  $('#driver-fillter').on('change', function () {
    dt_data.draw();
  });
  $('.dataTables_filter').hide();

  $(document).on('click', '.payment-task', function () {
    const id = $(this).data('id');
    $.get(`${baseUrl}admin/tasks/payment/${id}`, function (data) {
      console.log(data);
      if (data.owner !== 'customer') {
        $('#wallet-option').hide();
      } else {
        $('#wallet-option').show();
      }
      if (data.status === 2) {
        showAlert('error', data.error);
        return;
      } else if (data.status === 3) {
        showAlert('success', data.message);
        $('#assignTitle').html(`Check Payment Task: <span class="bg-info text-white px-2 rounded">#${id}</span>`);
        $('#checkPaymentModal').modal('show');
        $('#task-payment-id').val(id);

        if (!data.data) {
          showAlert('error', 'Payment details not found.');
          return;
        }

        var checkButtons = '';
        if (data.data.status === 'pending') {
          checkButtons = `
            <button type="button" data-id="${data.data.reference_id}"  class="btn btn-primary confirm-payment">Confirm the payment</button>
            <button type="button" data-id="${data.data.reference_id}"  class="btn btn-danger cancel-payment">Undo the process</button>

            <div class="alert alert-danger mt-2">When you cancel a payment, it will be completely deleted along with its files.</div>`;
        } else if (data.data.status === 'failed') {
          checkButtons = `
            <div class="alert alert-danger mt-2">The payment attempt has failed. You can try paying again.</div>
            <button type="button" data-id="${data.data.reference_id}" class="btn btn-danger cancel-payment">Try Again</button>`;
        }

        var checkHtml = `
        <div class="alert alert-light alert-dismissible">
          <h4 class="alert-heading">Check Payment</h4>
          ${
            data.data.is_investor_payment
              ? `<div class="alert alert-info py-2 mb-3 shadow-sm border-start border-4 border-info">
                  <i class="ti ti-user-check me-1"></i>
                  This task was funded by investor: <strong>${data.data.investor_name}</strong>
                 </div>`
              : ''
          }
          <p>Task ID: <span class="px-3 py-0 bg-info text-white rounded">#${data.data.reference_id}</span></p>
          <p>Transaction ID: <span class="px-3 py-0 bg-info text-white rounded">#${data.data.id}</span></p>
          <p>Amount: <span class="px-3 py-0 bg-info text-white rounded">${data.data.amount} SAR </span></p>
          <p>Payment Method: <span class="px-3 py-0 bg-info text-white rounded"> ${data.data.payment_type} </span></p>
          <p>Payment Status: <span class="px-3 py-0 bg-warning text-white rounded">${data.data.status}</span></p>
          ${
            data.data.payment_type !== 'credit' && data.data.receipt_image
              ? `
             <p>Payment Receipt: <span class="px-3 py-0 bg-info text-white rounded">${data.data.receipt_number}</span></p>
          <img src="${baseUrl + data.data.receipt_image}" alt="Receipt" class="img-fluid mb-2" style="max-width: 100%; height: auto; "/>

            `
              : ''
          }

          <p>Payment Note: <span class="px-3 py-0 bg-info text-white rounded">${data.data.note}</span></p>
          <p>Payment Created At: <span class="px-3 py-0 bg-info text-white rounded">${data.data.created_at}</span></p>
          <p>Payment Checked By: <span class="px-3 py-0 bg-info text-white rounded">${data.data.user ? data.data.user.name : 'not checked yet'}</span></p>

          <div class="d-flex gap-2">
            ${checkButtons}
            ${
              data.data.status === 'paid' || data.data.status === 'completed'
                ? `<button type="button" data-id="${data.data.reference_id}" class="btn btn-warning cancel-paid-payment"><i class="ti ti-refresh me-1"></i>Cancel Completed Payment</button>`
                : ''
            }
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
    fetch(baseUrl + 'admin/tasks/payment/confirm/' + id, {
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

  $(document).on('click', '.cancel-paid-payment', function () {
    const id = $(this).data('id');
    const $btn = $(this);

    Swal.fire({
      title: 'Are you sure?',
      text: 'This will revert the payment, restore the wallet balance (if applicable), and delete the payment records!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, cancel it!',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        $btn.prop('disabled', true).html('<i class="ti ti-loader rotate"></i> Processing...');

        const doCancelRequest = (password = '') => {
          let url = baseUrl + 'admin/tasks/payment/cancel-paid/' + id;
          if (password) {
            url += '?password=' + encodeURIComponent(password);
          }
          
          fetch(url, {
            method: 'GET',
            headers: {
              Accept: 'application/json'
            }
          })
            .then(response => response.json())
            .then(data => {
              if (data.status === 1) {
                showAlert('success', data.message, 5000, true);
                $('#paymentModal').modal('hide');
                $('#checkPaymentModal').modal('hide');
                if (dt_data) {
                  dt_data.draw();
                }
              } else if (data.status === 3) {
                // Requires password
                Swal.fire({
                  title: 'Admin Password Required',
                  text: data.message,
                  input: 'password',
                  inputPlaceholder: 'Enter admin password',
                  showCancelButton: true,
                  confirmButtonText: 'Submit',
                  customClass: {
                    confirmButton: 'btn btn-primary me-3',
                    cancelButton: 'btn btn-label-secondary'
                  },
                  buttonsStyling: false,
                  preConfirm: (pwd) => {
                    if (!pwd) {
                      Swal.showValidationMessage('Password cannot be empty');
                    }
                    return pwd;
                  }
                }).then((pwdResult) => {
                  if (pwdResult.isConfirmed) {
                    $btn.prop('disabled', true).html('<i class="ti ti-loader rotate"></i> Processing...');
                    doCancelRequest(pwdResult.value);
                  } else {
                    $btn.prop('disabled', false).html('<i class="ti ti-refresh me-1"></i>Cancel Completed Payment');
                  }
                });
              } else {
                showAlert('danger', data.message, 5000, true);
                $btn.prop('disabled', false).html('<i class="ti ti-refresh me-1"></i>Cancel Completed Payment');
              }
            })
            .catch(error => {
              showAlert('danger', 'Error connecting to server', 5000, true);
              $btn.prop('disabled', false).html('<i class="ti ti-refresh me-1"></i>Cancel Completed Payment');
              console.error(error);
            });
        };

        doCancelRequest();
      }
    });
  });

  $(document).on('click', '.cancel-payment', function () {
    console.log('confirm payment');
    const id = $(this).data('id');
    fetch(baseUrl + 'admin/tasks/payment/cancel/' + id, {
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

  $(document).on('click', '.closed-record', function () {
    var id = $(this).data('id');
    $('#task-id').val(id);
    $('#modelTitle').html('#' + id);
    $('#closedModal').modal('show');
  });

  $(document).on('click', '.refund-task', function () {
    var id = $(this).data('id');
    $('#task-refund-id').val(id);
    $('#modelRefundTitle').html('#' + id);
    $('#refundModal').modal('show');
  });

  $(document).on('click', '.connect-task', function () {
    let url = baseUrl + 'admin/tasks/connect/' + $(this).data('id');
    connectTeam($(this).data('name'), url);
  });

  //   // Payment Request Handler
  //   $(document).on('click', '.payment-request-task', function () {
  //     const taskId = $(this).data('id');

  //     // Get task details for payment request
  //     $.get(`${baseUrl}admin/tasks/payment-request/${taskId}`, function (data) {
  //       if (data.status === 0) {
  //         showAlert('error', data.error);
  //         return;
  //       }

  //       const task = data.task;
  //       const driverAmount = task.total_price - task.commission;

  //       // Fill task information
  //       $('#paymentRequestTaskId').text(`#${task.id}`);
  //       $('#taskInfoId').text(`#${task.id}`);
  //       $('#taskInfoDriverAmount').text(`${driverAmount.toFixed(2)} SAR`);
  //       $('#taskInfoOwner').text(task.owner_name || 'N/A');
  //       $('#taskInfoPickup').text(task.pickup_address || 'N/A');
  //       $('#taskInfoDelivery').text(task.delivery_address || 'N/A');

  //       // Set maximum amount
  //       $('#maxAmount').text(`${driverAmount.toFixed(2)} SAR`);
  //       $('#requestedAmount').attr('max', driverAmount);

  //       // Set hidden task ID
  //       $('#paymentRequestTaskIdInput').val(task.id);

  //       // Store task data for later use
  //       $('#paymentRequestModal').data('taskData', {
  //         id: task.id,
  //         total_price: task.total_price,
  //         commission: task.commission,
  //         driver_amount: driverAmount,
  //         driver_name: task.driver_name,
  //         driver_phone: task.driver_phone,
  //         driver_bank_name: task.driver_bank_name,
  //         driver_account_number: task.driver_account_number,
  //         driver_iban_number: task.driver_iban_number,
  //         driver_has_team: task.driver_has_team,
  //         team_leader_name: task.team_leader_name,
  //         team_leader_phone: task.team_leader_phone,
  //         team_leader_bank_name: task.team_leader_bank_name,
  //         team_leader_account_number: task.team_leader_account_number,
  //         team_leader_iban_number: task.team_leader_iban_number,
  //         owner_name: task.owner_name,
  //         pickup_address: task.pickup_address,
  //         delivery_address: task.delivery_address
  //       });

  //       // Handle team leader option visibility
  //       const teamLeaderOption = $('#paymentRecipient option[value="team_leader"]');
  //       if (task.driver_has_team) {
  //         teamLeaderOption.show();
  //       } else {
  //         teamLeaderOption.hide();
  //         // If team_leader was selected but driver has no team, reset to empty
  //         if ($('#paymentRecipient').val() === 'team_leader') {
  //           $('#paymentRecipient').val('');
  //         }
  //       }

  //       // Show modal
  //       $('#paymentRequestModal').modal('show');

  //       // Reset form
  //       $('#paymentRequestForm')[0].reset();
  //       $('.text-error').text('');
  //     }).fail(function () {
  //       showAlert('error', 'Error loading task details');
  //     });
  //   });

  //   // Format IBAN input
  //   $(document).on('input', '#ibanNumber', function () {
  //     let value = $(this).val().replace(/\s/g, '').toUpperCase();
  //     // Ensure it starts with SA
  //     if (value && !value.startsWith('SA')) {
  //       value = 'SA' + value.replace(/^SA/i, '');
  //     }
  //     let formatted = value.replace(/(.{4})/g, '$1 ').trim();
  //     $(this).val(formatted);
  //   });

  //   // Format Account Number input
  //   $(document).on('input', '#accountNumber', function () {
  //     let value = $(this).val().replace(/\D/g, '');
  //     $(this).val(value);
  //   });

  //   // Validate requested amount in real-time
  //   $(document).on('input', '#requestedAmount', function () {
  //     const value = parseFloat($(this).val());
  //     const maxAmount = parseFloat($(this).attr('max'));

  //     if (value > maxAmount) {
  //       $('.requested_amount-error').text(`المبلغ لا يمكن أن يكون أكبر من ${maxAmount.toFixed(2)} ريال`);
  //     } else {
  //       $('.requested_amount-error').text('');
  //     }
  //   });

  //   // Handle payment recipient change to auto-fill bank details
  //   $(document).on('change', '#paymentRecipient', function () {
  //     const recipient = $(this).val();
  //     const taskData = $('#paymentRequestModal').data('taskData');

  //     if (!taskData) return;

  //   });

  $(document).on('click', '.share-task-whatsapp', function () {
    var id = $(this).data('id');
    var price = $(this).data('price');
    var pickup = $(this).data('pickup');
    var delivery = $(this).data('delivery');
    var truckName = $(this).data('truck-name');
    var vehicleType = $(this).data('vehicle-type');
    var vehicleSize = $(this).data('vehicle-size');
    var customerRef = $(this).data('customer') || '';

    // Clean up HTML tags if present (just in case they come from elsewhere, though addresses should be clean now)
    var div = document.createElement('div');

    div.innerHTML = pickup;
    pickup = div.textContent || div.innerText || '';

    div.innerHTML = delivery;
    delivery = div.textContent || div.innerText || '';

    var link = `${baseUrl}share/task/${id}`;

    var message = `مهمة جديدة 🚚\n`;
    if (customerRef && customerRef !== 'undefined' && customerRef !== 'null') message += `رقم مرجعي: #${customerRef}\n`;
    message += `رقم المهمة: #${id}\n\n`;

    // Detailed Addresses
    message += `📍 من (Pickup):\n${pickup.trim()}\n\n`;
    message += `🏁 إلى (Delivery):\n${delivery.trim()}\n\n`;

    // Vehicle Info
    if (truckName && truckName !== '-') {
      message += `🚛 المركبة: ${truckName} - ${vehicleType} - ${vehicleSize}\n`;
    }

    message += `💰 السعر: ${price} ريال\n\n`;

    message += `🔗 رابط المهمة (للقبول): ${link}`;

    var whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
  });

  //     // Clear current bank details
  //     $('#bankName').val('');
  //     $('#customBankName').val('').hide();
  //     $('#accountNumber').val('');
  //     $('#ibanNumber').val('');

  //     if (recipient === 'driver') {
  //       // Fill driver bank details
  //       if (taskData.driver_bank_name) {
  //         // Check if bank exists in options
  //         const bankOption = $('#bankName option[value="' + taskData.driver_bank_name + '"]');
  //         if (bankOption.length > 0) {
  //           $('#bankName').val(taskData.driver_bank_name);
  //         } else {
  //           // Use "other" option and show custom field
  //           $('#bankName').val('other');
  //           $('#customBankName').val(taskData.driver_bank_name).show();
  //         }
  //       }

  //       if (taskData.driver_account_number) {
  //         $('#accountNumber').val(taskData.driver_account_number);
  //       }

  //       if (taskData.driver_iban_number) {
  //         // Format IBAN with spaces for display
  //         const formattedIban = taskData.driver_iban_number.replace(/(.{4})/g, '$1 ').trim();
  //         $('#ibanNumber').val(formattedIban);
  //       }
  //     } else if (recipient === 'team_leader') {
  //       // Check if driver has team before filling team leader details
  //       if (!taskData.driver_has_team) {
  //         // Driver has no team, reset selection and show warning
  //         $(this).val('');
  //         showAlert('warning', 'هذا السائق لا ينتمي إلى أي فريق. لا يمكن اختيار قائد الفريق كمستلم للدفع.');
  //         return;
  //       }

  //       // Fill team leader bank details
  //       if (taskData.team_leader_bank_name) {
  //         // Check if bank exists in options
  //         const bankOption = $('#bankName option[value="' + taskData.team_leader_bank_name + '"]');
  //         if (bankOption.length > 0) {
  //           $('#bankName').val(taskData.team_leader_bank_name);
  //         } else {
  //           // Use "other" option and show custom field
  //           $('#bankName').val('other');
  //           $('#customBankName').val(taskData.team_leader_bank_name).show();
  //         }
  //       }

  //       if (taskData.team_leader_account_number) {
  //         $('#accountNumber').val(taskData.team_leader_account_number);
  //       }

  //       if (taskData.team_leader_iban_number) {
  //         // Format IBAN with spaces for display
  //         const formattedIban = taskData.team_leader_iban_number.replace(/(.{4})/g, '$1 ').trim();
  //         $('#ibanNumber').val(formattedIban);
  //       }
  //     }
  //   });

  //   // Handle bank selection
  //   $(document).on('change', '#bankName', function () {
  //     const selectedValue = $(this).val();
  //     if (selectedValue === 'other') {
  //       $('#customBankName').show().attr('required', true);
  //     } else {
  //       $('#customBankName').hide().attr('required', false).val('');
  //     }
  //   });

  //   // Generate Payment Request Handler
  //   $(document).on('click', '#generatePaymentRequest', function () {
  //     const form = $('#paymentRequestForm');
  //     const taskData = $('#paymentRequestModal').data('taskData');

  //     // Validate form
  //     const requestedAmount = parseFloat($('#requestedAmount').val());
  //     let bankName = $('#bankName').val().trim();
  //     const customBankName = $('#customBankName').val().trim();
  //     const accountNumber = $('#accountNumber').val().trim();
  //     const ibanNumber = $('#ibanNumber').val().trim();
  //     const paymentRecipient = $('#paymentRecipient').val();

  //     // Use custom bank name if "other" is selected
  //     if (bankName === 'other') {
  //       bankName = customBankName;
  //     }

  //     // Clear previous errors
  //     $('.text-error').text('');

  //     let hasErrors = false;

  //     if (!requestedAmount || requestedAmount <= 0) {
  //       $('.requested_amount-error').text('المبلغ المطلوب مطلوب ويجب أن يكون أكبر من صفر');
  //       hasErrors = true;
  //     }

  //     if (requestedAmount > taskData.driver_amount) {
  //       $('.requested_amount-error').text(
  //         `المبلغ المطلوب لا يمكن أن يكون أكبر من المبلغ المستحق للسائق (${taskData.driver_amount.toFixed(2)} ريال)`
  //       );
  //       hasErrors = true;
  //     }

  //     if (!bankName || bankName.length < 2) {
  //       if ($('#bankName').val() === 'other') {
  //         $('.bank_name-error').text('يرجى إدخال اسم البنك في الحقل المخصص');
  //       } else {
  //         $('.bank_name-error').text('يرجى اختيار البنك');
  //       }
  //       hasErrors = true;
  //     }

  //     if (!accountNumber || accountNumber.length < 8) {
  //       $('.account_number-error').text('رقم الحساب مطلوب ويجب أن يكون على الأقل 8 أرقام');
  //       hasErrors = true;
  //     }

  //     if (!ibanNumber || ibanNumber.replace(/\s/g, '').length < 15) {
  //       $('.iban_number-error').text('رقم الآيبان مطلوب ويجب أن يكون صحيحاً (على الأقل 15 رقم)');
  //       hasErrors = true;
  //     }

  //     if (!paymentRecipient) {
  //       $('.payment_recipient-error').text('يجب تحديد المستفيد من السداد');
  //       hasErrors = true;
  //     }

  //     // Validate IBAN format (basic validation)
  //     if (ibanNumber && !ibanNumber.replace(/\s/g, '').match(/^SA\d{22}$/)) {
  //       $('.iban_number-error').text('تنسيق رقم الآيبان غير صحيح (يجب أن يبدأ بـ SA ويتبعه 22 رقم)');
  //       hasErrors = true;
  //     }

  //     if (hasErrors) {
  //       return;
  //     }

  //     // Generate payment request document
  //     generatePaymentRequestDocument({
  //       taskId: taskData.id,
  //       requestedAmount: requestedAmount,
  //       bankName: bankName,
  //       accountNumber: accountNumber,
  //       ibanNumber: ibanNumber,
  //       paymentRecipient: paymentRecipient,
  //       taskData: taskData
  //     });
  //   });

  //   // Function to generate payment request document
  //   function generatePaymentRequestDocument(data) {
  //     const today = new Date();
  //     const formattedDate = today.toLocaleDateString('ar-SA');
  //     const remainingAmount = data.taskData.driver_amount - data.requestedAmount;
  //     const recipientName =
  //       data.paymentRecipient === 'driver' ? data.taskData.driver_name : data.taskData.team_leader_name;
  //     const recipientPhone =
  //       data.paymentRecipient === 'driver' ? data.taskData.driver_phone : data.taskData.team_leader_phone;

  //     // Generate reference number: TaskID + Date (YYYYMMDD) + Random 3 digits
  //     const dateString =
  //       today.getFullYear().toString() +
  //       (today.getMonth() + 1).toString().padStart(2, '0') +
  //       today.getDate().toString().padStart(2, '0');
  //     const randomNumber = Math.floor(Math.random() * 900) + 100; // 3-digit random number
  //     const referenceNumber = `${data.taskId}${dateString}${randomNumber}`;

  //     // Convert number to Arabic words
  //     // const requestedAmountInWords = numberToArabicWords(data.requestedAmount);
  //     let amount = data.requestedAmount; // المبلغ من قاعدة البيانات أو الـ API
  //     let requestedAmountInWords = writtenNumber(amount, { lang: 'ar' }) + ' ريال سعودي';

  //     console.log(data);
  //     const printContent = `
  // <!DOCTYPE html>
  // <html dir="rtl" lang="ar">
  // <head>
  //   <meta charset="UTF-8">
  //   <title>طلب سداد - ${referenceNumber}</title>
  //   <style>
  //     body {
  //       font-family: 'Tajawal', Arial, sans-serif;
  //       margin: 0;
  //       padding: 20mm;
  //       font-size: 14px;
  //       color: #000;
  //       background: #fff;
  //     }

  //     .container {
  //       max-width: 210mm;
  //       margin: auto;
  //     }

  //     h1, h2, h3 {
  //       margin: 0 0 10px 0;
  //       font-weight: bold;
  //     }

  //     .title {
  //       text-align: center;
  //       margin-bottom: 20px;
  //     }

  //     .emp-name{
  //       font-size: 16px;
  //     }

  //     table {
  //       width: 100%;
  //       margin-bottom: 15px;
  //     }

  //     td {
  //       border: 1px solid #000;
  //       padding: 8px;
  //       vertical-align: top;
  //     }

  //     .label {
  //       width: 30%;
  //       font-weight: bold;
  //       background: #f7f7f7;
  //     }

  //     .amount-box {

  //       padding: 15px;
  //       margin: 20px 0;
  //       font-weight: bold;
  //       font-size: 16px;
  //     }

  //     .signatures td {
  //       height: 80px;
  //       text-align: center;
  //     }

  //     .amount-details{
  //       font-size: 16px;
  //     }
  //       .amount-details span{
  //         border:1px solid #000;
  //         padding: 5px 10px;
  //         margin: 5px;
  //         border-radius: 5px;
  //       }
  //     .footer {
  //       margin-top: 25px;
  //       text-align: center;
  //       font-size: 12px;
  //       color: #555;
  //     }

  //     @media print {
  //       body { margin: 0; padding: 15mm; font-size: 12px; }
  //       .container { width: auto; }
  //     }
  //   </style>
  // </head>
  // <body>
  //   <div class="container">

  //     <!-- Header -->
  //     <div class="title">
  //       <h1>Safedests</h1>
  //       <h2>طلب سداد مالي</h2>
  //       <p>رقم الطلب: ${referenceNumber}</p>
  //       <p>التاريخ: ${formattedDate}</p>
  //     </div>

  //     <!-- Employee -->

  //     <p class="emp-name">
  //         اسم الموظف طالب السداد : <strong> ${$('meta[name="user-name"]').attr('content') || 'المستخدم الحالي'}</strong>
  //     </p>

  //     <h3>بيانات السداد</h3>
  //     <!-- Amount -->
  //     <div class="amount-box">
  //       مبلغ السداد:

  //       (${requestedAmountInWords})
  //     </div>
  //     <div>
  //       <p class="amount-details">
  //       السداد:
  //       دفعة <span>${data.requestedAmount.toFixed(2)} ريال </span>
  //       باقي حساب <span> ${remainingAmount.toFixed(2)} ريال </span>
  //       إجمالي الحساب <span>${data.taskData.driver_amount.toFixed(2)} ريال </span>
  //       </p>
  //     </div>

  //     <!-- Bank Info -->
  //     <h3>بيانات البنك</h3>
  //     <table>
  //       <tr><td class="label">اسم البنك</td><td>${data.bankName}</td></tr>
  //       <tr><td class="label">رقم الحساب</td><td>${data.accountNumber}</td></tr>
  //       <tr><td class="label">رقم الآيبان</td><td>${data.ibanNumber}</td></tr>
  //       <tr><td class="label">اسم المورد</td><td>${recipientName || 'غير محدد'}  ${recipientPhone || ''}</td></tr>
  //     </table>

  //     <!-- Trip Info -->
  //     <h3>بيانات الرحلة</h3>
  //     <table>
  //       <tr><td class="label">رقم المهمة</td><td>#${data.taskId}</td></tr>
  //       <tr><td class="label">صاحب الرحلة</td><td>${data.taskData.owner_name}</td></tr>
  //       <tr><td class="label">الوجهة</td><td>من ${data.taskData.pickup_address} إلى ${data.taskData.delivery_address}</td></tr>
  //     </table>

  //     <!-- Signatures -->
  //     <h3>التوقيع</h3>

  //     <!-- Footer -->
  //     <div class="footer">
  //       <p>تم إنشاء المستند إلكترونياً بتاريخ ${new Date().toLocaleDateString('ar-SA')}</p>
  //     </div>

  //   </div>
  // </body>
  // </html>
  // `;

  //     // Open print window
  //     const printWindow = window.open('', '_blank');
  //     printWindow.document.write(printContent);
  //     printWindow.document.close();
  //     printWindow.focus();

  //     // Add event listener for print dialog
  //     printWindow.addEventListener('beforeprint', function () {
  //       console.log('Print dialog opened');
  //     });

  //     printWindow.addEventListener('afterprint', function () {
  //       console.log('Print dialog closed');
  //       printWindow.close();
  //     });

  //     // Handle print cancellation
  //     printWindow.onbeforeunload = function () {
  //       return null;
  //     };

  //     // Trigger print
  //     printWindow.print();

  //     // Fallback: close window if user cancels print (for some browsers)
  //     setTimeout(function () {
  //       if (!printWindow.closed) {
  //         printWindow.addEventListener('focus', function () {
  //           setTimeout(function () {
  //             if (!printWindow.closed) {
  //               printWindow.close();
  //             }
  //           }, 100);
  //         });
  //       }
  //     }, 1000);

  //     // Close modal after printing
  //     setTimeout(() => {
  //       $('#paymentRequestModal').modal('hide');
  //       $('#paymentRequestForm')[0].reset();
  //     }, 1000);
  //   }

  $(document).on('click', '.fix-connection-task', function () {
    const taskId = $(this).data('id');
    showAlert('warning', 'fixing connection Started ...', 5000, true);
    $.get(`${baseUrl}admin/tasks/fix-connection/${taskId}`, function (data) {
      if (data.status === 1) {
        showAlert('success', data.message, 5000, true);
        dt_data.draw();
      } else {
        showAlert('danger', data.message, 5000, true);
      }
    });
  });

  // 🗑️ معالج حذف المهمة باستخدام SweetAlert2
  $(document).on('click', '.delete-task', function () {
    const taskId = $(this).data('id');
    const taskStatus = $(this).data('status');
    const paymentStatus = $(this).data('payment');

    // استخدام SweetAlert2 للتأكيد
    Swal.fire({
      title: `Delete Task #${taskId}?`,
      html: `
        <div class="text-start">
          <p><strong>Task Details:</strong></p>
          <ul class="list-unstyled">
            <li><i class="ti ti-id me-2"></i><strong>Task ID:</strong> #${taskId}</li>
            <li><i class="ti ti-activity me-2"></i><strong>Status:</strong> <span class="badge bg-primary">${taskStatus}</span></li>
            <li><i class="ti ti-credit-card me-2"></i><strong>Payment:</strong> <span class="badge bg-info">${paymentStatus}</span></li>
          </ul>
          <div class="alert alert-warning">
            <i class="ti ti-alert-triangle me-2"></i>
            <strong>Warning:</strong> This action cannot be undone and will remove:
            <ul class="mt-2 mb-0">
              <li>All task data and history</li>
              <li>Related files and documents</li>
              <li>Task points and images</li>
              <li>Associated ads and offers</li>
            </ul>
          </div>
        </div>
      `,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: '<i class="ti ti-trash me-1"></i>Yes, delete it!',
      cancelButtonText: '<i class="ti ti-x me-1"></i>Cancel',
      customClass: {
        confirmButton: 'btn btn-danger me-3 waves-effect waves-light',
        cancelButton: 'btn btn-label-secondary waves-effect waves-light'
      },
      buttonsStyling: false,
      allowOutsideClick: false,
      allowEscapeKey: false
    }).then(result => {
      if (result.isConfirmed) {
        // إظهار loading مع SweetAlert2
        Swal.fire({
          title: 'Deleting Task...',
          html: `
            <div class="text-center">
              <div class="spinner-border text-danger mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p>Please wait while we delete Task #${taskId} and all related data...</p>
            </div>
          `,
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          showCancelButton: false,
          showCloseButton: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        // إرسال طلب الحذف
        $.ajax({
          url: baseUrl + 'admin/tasks/delete',
          method: 'DELETE',
          data: {
            id: taskId,
            _token: $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.status === 1) {
              // نجح الحذف
              Swal.fire({
                title: 'Deleted!',
                html: `
                  <div class="text-center">
                    <i class="ti ti-check-circle text-success" style="font-size: 3rem;"></i>
                    <p class="mt-3">Task #${taskId} has been successfully deleted.</p>
                    <p class="text-muted">All related data and files have been removed.</p>
                  </div>
                `,
                icon: 'success',
                confirmButtonText: 'OK',
                customClass: {
                  confirmButton: 'btn btn-success waves-effect waves-light'
                },
                buttonsStyling: false
              });

              // إعادة تحميل الجدول
              dt_data.draw();
            } else {
              // فشل الحذف
              Swal.fire({
                title: 'Deletion Failed!',
                html: `
                  <div class="text-center">
                    <i class="ti ti-x-circle text-danger" style="font-size: 3rem;"></i>
                    <p class="mt-3"><strong>Error:</strong> ${response.error}</p>
                    <p class="text-muted">Task #${taskId} could not be deleted.</p>
                  </div>
                `,
                icon: 'error',
                confirmButtonText: 'OK',
                customClass: {
                  confirmButton: 'btn btn-danger waves-effect waves-light'
                },
                buttonsStyling: false
              });
            }
          },
          error: function (xhr, status, error) {
            let errorMessage = 'Error deleting task';
            if (xhr.responseJSON && xhr.responseJSON.error) {
              errorMessage = xhr.responseJSON.error;
            }

            // خطأ في الشبكة أو الخادم
            Swal.fire({
              title: 'Connection Error!',
              html: `
                <div class="text-center">
                  <i class="ti ti-wifi-off text-warning" style="font-size: 3rem;"></i>
                  <p class="mt-3"><strong>Network Error:</strong> ${errorMessage}</p>
                  <p class="text-muted">Please check your connection and try again.</p>
                </div>
              `,
              icon: 'error',
              confirmButtonText: 'OK',
              customClass: {
                confirmButton: 'btn btn-warning waves-effect waves-light'
              },
              buttonsStyling: false
            });
          }
        });
      }
    });
  });

  $(document).on('click', '.share-order-whatsapp', function () {
    const orderId = $(this).data('order-id');
    const taskId = $(this).data('task-id');

    // إظهار لوادر
    Swal.fire({
      title: 'تحضير بيانات الطلب...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    fetch(`${baseUrl}admin/tasks/order-share-data/${orderId}`, {
      headers: {
        Accept: 'application/json'
      }
    })
      .then(response => response.json())
      .then(data => {
        Swal.close();
        if (data.status === 1) {
          const tasks = data.tasks;
          const orderNumber = data.order_id;
          const totalPrice = data.total_price;

          let message = `طلب جديد 🚚\n------------------\nرقم الطلب: #${orderNumber}\n\n`;

          tasks.forEach(group => {
            message += `عدد (${group.count}) مهام - ${group.vehicle_info.truck_name} - ${group.vehicle_info.size}\n`;
            message += `بسعر مفرد: ${group.price} - الإجمالي: ${group.group_total}\n`;
            message += `من: ${group.pickup}\n`;
            message += `إلى: ${group.delivery}\n`;
            if (group.ids && group.ids.length > 0) {
              message += `(أرقام المهام: #${group.ids.join(', #')})\n`;
            }
            message += `------------------\n`;
          });

          message += `\nإجمالي قيمة الطلب: ${totalPrice} ريال\n`;
          message += `\n${baseUrl}share/task/${btoa('TASK-' + taskId)}`;

          const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
          window.open(whatsappUrl, '_blank');
        } else {
          showAlert('danger', data.message || 'حدث خطأ في جلب بيانات الطلب');
        }
      })
      .catch(err => {
        Swal.close();
        console.error(err);
        showAlert('danger', 'خطأ في الاتصال بالخادم');
      });
  });

  // Handle select all checkbox
  $(document).on('change', '#selectAll', function () {
    $('.dt-checkboxes').prop('checked', $(this).prop('checked'));
    toggleBulkShareButton();
  });

  // Handle individual checkbox change
  $(document).on('change', '.dt-checkboxes', function () {
    if (!$(this).prop('checked')) {
      $('#selectAll').prop('checked', false);
    } else {
      if ($('.dt-checkboxes:checked').length === $('.dt-checkboxes').length && $('.dt-checkboxes').length > 0) {
        $('#selectAll').prop('checked', true);
      }
    }
    toggleBulkShareButton();
  });

  function toggleBulkShareButton() {
    if ($('.dt-checkboxes:checked').length > 0) {
      $('#shareSelectedWhatsapp').fadeIn();
    } else {
      $('#shareSelectedWhatsapp').fadeOut();
    }
  }

  // Bulk share handler
  $(document).on('click', '#shareSelectedWhatsapp', function () {
    const selectedIds = [];
    $('.dt-checkboxes:checked').each(function () {
      selectedIds.push($(this).val());
    });

    Swal.fire({
      title: 'تحضير البيانات المحددة...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    fetch(`${baseUrl}admin/tasks/bulk-share-data`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        Accept: 'application/json'
      },
      body: JSON.stringify({ ids: selectedIds })
    })
      .then(response => response.json())
      .then(data => {
        Swal.close();
        if (data.status === 1) {
          const tasks = data.tasks;
          const totalPrice = data.total_price;

          let message = `🚚 تفاصيل المهام المحددة\n------------------\n\n`;

          tasks.forEach(group => {
            message += `عدد (${group.count}) مهام - ${group.vehicle_info.truck_name} - ${group.vehicle_info.size}\n`;
            message += `بسعر مفرد: ${group.price} - الإجمالي: ${group.group_total}\n`;
            message += `من: ${group.pickup}\n`;
            message += `إلى: ${group.delivery}\n`;
            if (group.ids && group.ids.length > 0) {
              message += `(أرقام المهام: #${group.ids.join(', #')})\n`;
            }
            message += `------------------\n`;
          });

          message += `\nإجمالي القيمة: ${totalPrice} ريال\n`;
          message += `\n${baseUrl}share/task/${btoa('TASK-' + selectedIds[0])}`;

          const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
          window.open(whatsappUrl, '_blank');
        } else {
          showAlert('danger', data.message || 'حدث خطأ في جلب البيانات');
        }
      })
      .catch(err => {
        Swal.close();
        console.error(err);
        showAlert('danger', 'خطأ في الاتصال بالخادم');
      });
  });
});
