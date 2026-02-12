/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, generateFields, showFormModal } from '../../ajax';

// Datatable (jquery)
$(function () {
  // Variable declaration for table
  var dt_data_table = $('.datatables-users'),
    userView = baseUrl + 'admin/customers/account/';

  if (templateId != null) {
    $('#select-template').val(templateId).trigger('change');
  }

  var select2 = $('.select2');
  if (select2.length) {
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: __('Select Tags'),
      dropdownParent: $this.parent(),
      closeOnSelect: false
    });
  }

  // Bank fields functionality for customers
  function toggleCustomBankFieldCustomer() {
    const bankSelect = $('#customer-bank-name');
    const customBankField = $('#customer-custom-bank-field');

    if (bankSelect.val() === 'other') {
      customBankField.show();
      $('#customer-custom-bank-name').attr('required', true);
    } else {
      customBankField.hide();
      $('#customer-custom-bank-name').attr('required', false).val('');
    }
  }

  // Handle bank selection change
  $(document).on('change', '#customer-bank-name', function () {
    toggleCustomBankFieldCustomer();
  });

  // Format account number (numbers only)
  $(document).on('input', '#customer-account-number', function () {
    this.value = this.value.replace(/[^0-9]/g, '');
  });

  // Format IBAN number
  $(document).on('input', '#customer-iban-number', function () {
    let value = this.value.replace(/[^0-9SA]/g, '').toUpperCase();

    // Ensure it starts with SA
    if (value && !value.startsWith('SA')) {
      if (value.startsWith('S')) {
        value = 'SA' + value.substring(1);
      } else {
        value = 'SA' + value;
      }
    }

    // Limit to SA + 22 digits
    if (value.length > 24) {
      value = value.substring(0, 24);
    }

    // Format with spaces for readability
    if (value.length > 2) {
      value =
        value.substring(0, 2) +
        value
          .substring(2)
          .replace(/(.{4})/g, '$1 ')
          .trim();
    }

    this.value = value;
  });
  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  // Users datatable
  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/customers/data',
        data: function (d) {
          d.status = $('#statusFilter').val();
          d.search = $('#searchFilter').val();
        },
        dataSrc: function (json) {
          $('#total').text(json.summary.total);
          $('#total-active').text(json.summary.total_active);
          $('#total-active + p').text(`(${((json.summary.total_active / json.summary.total) * 100).toFixed(1)})%`);
          $('#total-verified').text(json.summary.total_verified);
          $('#total-verified + p').text(`(${((json.summary.total_verified / json.summary.total) * 100).toFixed(1)})%`);
          $('#total-blocked').text(json.summary.total_blocked);
          $('#total-blocked + p').text(`(${((json.summary.total_blocked / json.summary.total) * 100).toFixed(1)})%`);

          return json.data;
        }
      },
      columns: [
        { data: '' }, // للـ control (responsive)
        { data: 'fake_id' }, // الترقيم التسلسلي
        { data: 'name' }, // الاسم مع الأفاتار
        { data: 'email' }, // البريد
        { data: 'phone' }, // الجوال
        { data: 'tags' }, // الحالة
        { data: 'role' }, // الحالة
        { data: 'status' }, // الحالة
        { data: 'created_at' }, // تاريخ الإنشاء
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
          render: function (data, type, full, meta) {
            return `<span>${full.fake_id}</span>`;
          }
        },
        {
          targets: 2,
          responsivePriority: 2,
          render: function (data, type, full, meta) {
            var $name = full.name;
            if (full.image === null) {
              var initials = $name.match(/\b\w/g) || [];
              initials = (initials.shift() || '') + (initials.pop() || '');
              var colors = ['success', 'danger', 'warning', 'info', 'dark', 'primary'];
              var color = colors[Math.floor(Math.random() * colors.length)];
              var img = `<div class="avatar  bg-label-${color} rounded-circle">
                      <span class="avatar-initial">${initials.toUpperCase()}</span>
                    </div>`;
            } else {
              var img = `<div class="avatar  bg-label-${color} rounded-circle">
                <img src="${full.image}"  class="rounded-circle  object-cover"/>
            </div>`;
            }

            var broker = full.broker ? '<span class=" badge bg-label-primary ">Customs Clearance Agent</span>' : '';
            return `
              <div class="d-flex align-items-center">
                <div class="avatar-wrapper me-3">
                  ${img}
                </div>
                <div class="d-flex flex-column">
                  <span class="fw-medium">${$name}</span>
                  <span class="fw-medium">${broker}</span>

                </div>
              </div>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span>${full.email}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span>${full.phone}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            return `<span>${full.role}</span>`;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            return `<span>${full.tags}</span>`;
          }
        },
        {
          targets: 7,
          className: 'text-center',
          render: function (data, type, full, meta) {
            let icon = '';
            let status = full.status;

            switch (status) {
              case 'active':
                icon = '<i class="ti ti-shield-check text-success fs-5 ms-2"></i>';
                break;
              case 'blocked':
                icon = '<i class="ti ti-shield-x text-danger fs-5 ms-2"></i>';
                break;
              case 'verified':
                icon = '<i class="ti ti-hourglass text-warning fs-5 ms-2"></i>';
                break;
            }

            return `<span class="bg-label-${status}">${status}</span> ${icon}`;
          }
        },
        {
          targets: 8,
          render: function (data, type, full, meta) {
            return full.created_at;
          }
        },
        {
          targets: 9,
          title: __('Actions'),
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `
              <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-icon btn-primary send-notification-btn"
                        data-id="${full.id}"
                        data-name="${full.name}"
                        title="إرسال إشعار">
                  <i class="ti ti-bell"></i>
                </button>
                <button class="btn btn-sm btn-icon edit-record " data-id="${full.id}" data-bs-toggle="modal" data-bs-target="#submitModal">
                  <i class="ti ti-edit"></i>
                </button>
                <button class="btn btn-sm btn-icon delete-record " data-id="${full.id}"  data-name="${full.name}">
                  <i class="ti ti-trash"></i>
                </button>
                <div class="dropdown">
                  <button class="btn btn-sm btn-icon  dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a href="${userView}${full.id}/${full.name}" class="dropdown-item"><i class="ti ti-eye me-2"></i>${__('View')}</a></li>
                    ${!full.can_commission ? '' : `<li><a href="javascript:;" class="dropdown-item manage-commissions" data-id="${full.id}" data-name="${full.name}"><i class="ti ti-percentage me-2"></i>${__('Manage Commissions')}</a></li>`}
                    <li><a href="javascript:;" class="dropdown-item status-record" data-id="${full.id}" data-name="${full.name}" data-status="${full.status}"><i class="ti ti-switch-horizontal me-2"></i>${__('Change Status')}</a></li>
                    <li><a href="javascript:;" class="dropdown-item status-broker-record" data-id="${full.id}" data-name="${full.name}" data-status="${full.broker}"><i class="ti ti-switch-horizontal me-2"></i> ${__('Change Broker Status')}</a></li>
                    <li><a href="javascript:;" class="dropdown-item wallet-record" data-id="${full.id}" data-name="${full.name}" ><i class="ti ti-wallet me-2"></i>${__('Create Wallet')}</a></li>
                    <li><a href="javascript:;" class="dropdown-item signature-record" data-id="${full.id}" data-name="${full.name}"><i class="ti ti-signature me-2"></i>${__('Manage Signature')}</a></li>
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
        searchPlaceholder: __('Search...'),
        info: __('Showing _START_ to _END_ of _TOTAL_ entries'),
        paginate: {
          next: '<i class="ti ti-chevron-right"></i>',
          previous: '<i class="ti ti-chevron-left"></i>'
        }
      },
      buttons: [
        `<label class='me-2'>
          <select id='statusFilter' class='form-select d-inline-block w-auto ms-2 mt-5'>
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="verified">Unverified</option>
            <option value="blocked">Blocked</option>
          </select>
        </label>`,
        ` <label class="me-2">
              <input id="searchFilter" class="form-control d-inline-block w-auto ms-2 mt-5" placeholder="Search driver" />
          </label>`
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return __('Details of') + ' ' + data.name;
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col) {
              return col.title
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

  $('.dataTables_filter').hide();

  document.addEventListener('formSubmitted', function (event) {
    $('.form_submit').trigger('reset');
    $('.preview-image').attr('src', baseUrl + 'assets/img/person.png');
    $('#additional-form').html('');
    $('#select-template').val('');
    $('#customer-tags').val([]).trigger('change');

    setTimeout(() => {
      $('#submitModal').modal('hide');
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

  $(document).on('click', '.edit-record', function () {
    var data_id = $(this).data('id'),
      dtrModal = $('.dtr-bs-modal.show');
    if (dtrModal.length) {
      dtrModal.modal('hide');
    }
    $.get(`${baseUrl}admin/customers/edit/${data_id}`, function (data) {
      $('.form_submit').trigger('reset');

      $('.text-error').html('');
      $('#customer_id').val(data.id);
      $('#customer-fullname').val(data.name);
      $('#customer-email').val(data.email);
      $('#customer-phone').val(data.phone);
      $('#phone-code').val(data.phone_code);
      $('#customer-role').val(data.role_id);
      $('#customer-c_name').val(data.company_name);
      $('#customer-c_address').val(data.company_address);
      $('#customer-policy-file').val(data.policy_file_name);
      $('#customer-tags').val(data.tagsIds).trigger('change');
      if (data.img !== null) {
        $('.preview-image').attr('src', data.img);
      }
      $('#additional-form').html('');
      $('#select-template').val(data.form_template_id);

      if (data.form_template_id === null) {
        $('#select-template').val(templateId).trigger('change');
      }

      // Load bank details
      $('#customer-bank-name').val(data.bank_name || '');
      $('#customer-account-number').val(data.account_number || '');
      $('#customer-iban-number').val(data.iban_number || '');

      // Handle custom bank name
      if (data.bank_name && !$('#customer-bank-name option[value="' + data.bank_name + '"]').length) {
        $('#customer-bank-name').val('other');
        $('#customer-custom-bank-name').val(data.bank_name);
      }
      toggleCustomBankFieldCustomer();

      // Load task numbering
      $('#customer-task-number-start').val(data.task_number_start || '');
      $('#customer-task-number-next').val(data.task_number_next || '');

      generateFields(data.fields, data.additional_data);

      $('#modelTitle').html(`Edit User: <span class="bg-info text-white px-2 rounded">${data.name}</span>`);
    });
  });

  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/customers/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });

  $(document).on('click', '.status-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const status = $(this).data('status');

    const fields = `
      <input type="hidden" name="id" value="${id}">
      <select class="form-select" name="status">
        <option value="active" ${status === 'active' ? 'selected' : ''}>Active</option>
        <option value="verified" ${status === 'verified' ? 'selected' : ''}>Unverified</option>
        <option value="blocked" ${status === 'blocked' ? 'selected' : ''}>Blocked</option>
      </select>
    `;

    showFormModal({
      title: `Change Customer: ${name} Status`,
      icon: 'info',
      fields: fields,
      url: `${baseUrl}admin/customers/status`,
      method: 'POST',
      dataTable: dt_data // إعادة تحميل الجدول إذا موجود
    });
  });

  $(document).on('click', '.status-broker-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const status = $(this).data('status');

    const fields = `
      <input type="hidden" name="id" value="${id}">
      <select class="form-select" name="status">
        <option value="active" ${status ? 'selected' : ''}>Active</option>
        <option value="inactive" ${!status ? 'selected' : ''}>Inactive</option>
      </select>
    `;

    showFormModal({
      title: `Change Broker: ${name} Status`,
      icon: 'info',
      fields: fields,
      url: `${baseUrl}admin/customers/broker/status`,
      method: 'POST',
      dataTable: dt_data // إعادة تحميل الجدول إذا موجود
    });
  });

  $(document).on('click', '.wallet-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const fields = `
      <input type="hidden" name="id" value="${id}">
    `;

    showFormModal({
      title: `Create Wallet For Customer: <h4> <span class="bg-info p-0 px-2 rounded text-white"> ${name} </span> </h4>`,
      icon: 'info',
      fields: fields,
      url: `${baseUrl}admin/customers/wallet/create`,
      method: 'POST',
      dataTable: dt_data // إعادة تحميل الجدول إذا موجود
    });
  });

  $('#submitModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    $('.preview-image').attr('src', baseUrl + 'assets/img/person.png');
    $('#customer-tags').val([]).trigger('change');
    $('.text-error').html('');
    $('#customer_id').val('');
    $('#modelTitle').html(__('Add New Customer'));
    $('#additional-form').html('');
    $('#select-template').val('');
    // Reset task numbering fields
    $('#customer-task-number-start').val('');
    $('#customer-task-number-next').val('');
  });

  // Handle send notification button click for customers
  $(document).on('click', '.send-notification-btn', function () {
    const customerId = $(this).data('id');
    const customerName = $(this).data('name');

    console.log('Notification button clicked for customer:', customerId, customerName);

    // Call the global function
    window.openNotificationModal(customerId, 'customer', customerName);
  });

  // Manage Commissions
  $(document).on('click', '.manage-commissions', function () {
    var customerId = $(this).data('id');
    var customerName = $(this).data('name');

    $('#commissionsModal').modal('show');
    $('#commissionsModalTitle').text(__('Manage Commissions for') + ' ' + customerName);
    $('#current_customer_id').val(customerId);

    loadCustomerCommissions(customerId);
  });

  // Load Customer Commissions
  function loadCustomerCommissions(customerId) {
    $.get(baseUrl + 'admin/commissions/customer/' + customerId, function (response) {
      var commissionsHtml = '';
      var data = response.data || [];

      if (data.length > 0) {
        data.forEach(function (commission, index) {
          commissionsHtml += `
            <div class="commission-item border rounded p-3 mb-3" data-commission-id="${commission.id}">
              <div class="row align-items-center">
                <div class="col-md-4">
                  <label class="form-label">${__('User')}</label>
                  <select class="form-select user-select" name="commissions[${index}][user_id]" required>
                    <option value="">${__('Select User')}</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">${__('Commission Type')}</label>
                  <select class="form-select commission-type" name="commissions[${index}][commission_type]" required>
                    <option value="percentage" ${commission.commission_type === 'percentage' ? 'selected' : ''}>${__('Percentage')}</option>
                    <option value="fixed" ${commission.commission_type === 'fixed' ? 'selected' : ''}>${__('Fixed Amount')}</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label commission-value-label">${commission.commission_type === 'percentage' ? __('Percentage') + ' (%)' : __('Amount') + ' (SAR)'}</label>
                  <input type="number" class="form-control commission-value" name="commissions[${index}][commission_value]" value="${commission.commission_value}" step="0.01" min="0" required>
                </div>
                <div class="col-md-2">
                  <label class="form-label">&nbsp;</label>
                  <div class="d-flex gap-2">
                    <button type="button" class="btn  btn-icon text-danger remove-commission">
                      <i class="ti ti-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
              <input type="hidden" name="commissions[${index}][id]" value="${commission.id}">
            </div>
          `;
        });
      }

      $('#commissions-container').html(commissionsHtml);
      loadUsersForSelects();

      // Set selected users
      $(document).on('usersLoaded', function () {
        data.forEach(function (commission, index) {
          $(`.commission-item[data-commission-id="${commission.id}"] .user-select`)
            .val(commission.user_id)
            .trigger('change');
        });
      });
    });
  }

  // Load Users for Select Dropdowns
  function loadUsersForSelects() {
    $.get(baseUrl + 'admin/users/data?all=1', function (response) {
      var usersOptions = '<option value="">' + __('Select User') + '</option>';
      response.data.forEach(function (user) {
        usersOptions += `<option value="${user.id}">${user.name}</option>`;
      });
      $('.user-select').html(usersOptions);
      $(document).trigger('usersLoaded');
    });
  }

  // Add New Commission
  $(document).on('click', '#add-commission', function () {
    var index = $('.commission-item').length;
    var newCommissionHtml = `
      <div class="commission-item border rounded p-3 mb-3">
        <div class="row align-items-center">
          <div class="col-md-4">
            <label class="form-label">${__('User')}</label>
            <select class="form-select user-select" name="commissions[${index}][user_id]" required>
              <option value="">${__('Select User')}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">${__('Commission Type')}</label>
            <select class="form-select commission-type" name="commissions[${index}][commission_type]" required>
              <option value="percentage">${__('Percentage')}</option>
              <option value="fixed">${__('Fixed Amount')}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label commission-value-label">${__('Percentage')} (%)</label>
            <input type="number" class="form-control commission-value" name="commissions[${index}][commission_value]" step="0.01" min="0" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">&nbsp;</label>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-danger remove-commission">
                <i class="ti ti-trash"></i>
              </button>
            </div>
          </div>
        </div>
        <input type="hidden" name="commissions[${index}][id]" value="">
      </div>
    `;

    $('#commissions-container').append(newCommissionHtml);
    loadUsersForSelects();
  });

  // Remove Commission
  $(document).on('click', '.remove-commission', function () {
    $(this).closest('.commission-item').remove();
    updateCommissionIndexes();
  });

  // Update Commission Type Label
  $(document).on('change', '.commission-type', function () {
    var $item = $(this).closest('.commission-item');
    var type = $(this).val();
    var $label = $item.find('.commission-value-label');

    if (type === 'percentage') {
      $label.text(__('Percentage') + ' (%)');
    } else {
      $label.text(__('Amount') + ' (SAR)');
    }
  });

  // Update Commission Indexes
  function updateCommissionIndexes() {
    $('.commission-item').each(function (index) {
      $(this)
        .find('select, input')
        .each(function () {
          var name = $(this).attr('name');
          if (name) {
            var newName = name.replace(/\[\d+\]/, '[' + index + ']');
            $(this).attr('name', newName);
          }
        });
    });
  }

  // Save Commissions
  $(document).on('submit', '#commissionsForm', function (e) {
    e.preventDefault();
    var formData = new FormData(this);

    $.ajax({
      url: baseUrl + 'admin/commissions',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 1) {
          $('#commissionsModal').modal('hide');
          showAlert('success', response.success);
        } else {
          if (response.errors) {
            showAlert('error', response.error || 'Validation errors occurred');
          } else {
            showAlert('error', response.error);
          }
        }
      },
      error: function (xhr) {
        showAlert('error', 'An error occurred while processing your request.');
      }
    });
  });

  $(document).on('click', '.signature-record', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    if (window.signatureModalManager) {
      window.signatureModalManager.open('customer', id);
    }
  });

  window.refreshDataTable = function () {
    if (dt_data) {
      dt_data.draw();
    }
  };
});
