/**
 * Page User List
 */

'use strict';
import { generateFields } from '../ajax';

$(function () {
  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Variable declaration for table
  var dt_user_table = $('.datatables-investors'),
    select2 = $('.select2'),
    userForm = $('#investorForm');

  if (select2.length) {
    select2.each(function () {
      var $this = $(this);
      $this.wrap('<div class="position-relative"></div>').select2({
        placeholder: $this.attr('id') === 'broker_id' ? __('Select broker') : __('Select customers'),
        dropdownParent: $this.parent()
      });
    });
  }

  // Bank fields functionality for investors
  function toggleCustomBankField() {
    const bankSelect = $('#user-bank-name');
    const customBankField = $('#user-custom-bank-field');
    const bicInput = $('#user-bic-code');
    const countrySelect = $('#user-bank-country');

    const bicMapping = {
      'البنك الأهلي السعودي': 'NCBKSA22',
      'مصرف الراجحي': 'RJHISARI',
      'بنك الرياض': 'RYADSA22',
      'البنك السعودي الأول': 'SABBSARI',
      'بنك البلاد': 'ALBISARI',
      'مصرف الإنماء': 'INMASARI',
      'البنك السعودي للاستثمار': 'SISISARI',
      'البنك العربي الوطني': 'ARABSARI',
      'بنك الجزيرة': 'BJAZSARI',
      'البنك السعودي الفرنسي': 'BSFRSARI'
    };

    const selectedBank = bankSelect.val();

    if (selectedBank === 'other') {
      customBankField.show();
      $('#user-custom-bank-name').attr('required', true);
      bicInput.val('').prop('readonly', false);
      countrySelect.val('أخرى');
    } else if (selectedBank && bicMapping[selectedBank]) {
      customBankField.hide();
      $('#user-custom-bank-name').attr('required', false).val('');
      bicInput.val(bicMapping[selectedBank]).prop('readonly', true);
      countrySelect.val('السعودية');
    } else {
      customBankField.hide();
      $('#user-custom-bank-name').attr('required', false).val('');
      bicInput.val('').prop('readonly', false);
      if (!selectedBank) countrySelect.val('السعودية');
    }
  }

  // Handle bank selection change
  $(document).on('change', '#user-bank-name', function () {
    toggleCustomBankField();
  });

  // Format account number (numbers only)
  $(document).on('input', '#user-account-number', function () {
    this.value = this.value.replace(/[^0-9]/g, '');
  });

  // Format IBAN number
  $(document).on('input', '#user-iban-number', function () {
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

  // Users datatable
  if (dt_user_table.length) {
    var dt_user = dt_user_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/investors/data'
      },
      columns: [
        { data: 'fake_id' },
        { data: 'name' },
        { data: 'email' },
        { data: 'wallet_balance' },
        { data: 'contract_type' },
        { data: 'commission' },
        { data: 'status' },
        { data: 'reset_password' },
        { data: 'id' }
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
            return data;
          }
        },
        {
          // User full name and email
          targets: 1,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            var $name = full['name'];
            return '<span class="fw-medium">' + $name + '</span>';
          }
        },
        {
          // Wallet Balance
          targets: 3,
          render: function (data, type, full, meta) {
            return '<span class="fw-bold text-success">' + data + ' ' + __('SAR') + '</span>';
          }
        },
        {
          // Status
          targets: 6,
          render: function (data, type, full, meta) {
            var $status_number = data;
            var $status = {
              active: { title: __('Active'), class: 'bg-label-success' },
              inactive: { title: __('Inactive'), class: 'bg-label-danger' },
              pending: { title: __('Pending Review'), class: 'bg-label-warning' }
            };
            if (typeof $status[$status_number] === 'undefined') {
              return data;
            }
            return (
              '<span class="badge ' + $status[$status_number].class + '">' + $status[$status_number].title + '</span>'
            );
          }
        },
        {
          // Reset Password
          targets: 7,
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            var $status = full['reset_password'];
            return (
              '<label class="switch switch-success">' +
              '<input type="checkbox" class="switch-input reset-password" data-id="' +
              full['id'] +
              '" ' +
              ($status == 1 ? 'checked' : '') +
              '>' +
              '<span class="switch-toggle-slider">' +
              '<span class="switch-on">' +
              '<i class="ti ti-check"></i>' +
              '</span>' +
              '<span class="switch-off">' +
              '<i class="ti ti-x"></i>' +
              '</span>' +
              '</span>' +
              '</label>'
            );
          }
        },
        {
          // Actions
          targets: -1,
          title: __('Actions'),
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            let fundTaskBtn = '';
            if (full['raw_contract_type']) {
                fundTaskBtn = '<a href="' + baseUrl + 'admin/users/' + full['id'] + '/wallet/tasks-funding" class="text-body" title="تمويل المهام"><i class="ti ti-cash ti-sm me-2 text-info"></i></a>';
            }

            return (
              '<div class="d-flex align-items-center">' +
              '<a href="javascript:;" class="text-body view-record" data-id="' + full['id'] + '" data-bs-toggle="modal" data-bs-target="#viewInvestorModal" title="' + __('View details') + '"><i class="ti ti-eye ti-sm me-2"></i></a>' +
              '<a href="' + baseUrl + 'admin/investors/' + full['id'] + '/invest-wallet" class="text-body" title="' + __('Investment wallet') + '"><i class="ti ti-wallet ti-sm me-2 text-primary"></i></a>' +
              '<a href="' + baseUrl + 'admin/users/' + full['id'] + '/wallet" class="text-body" title="' + __('Commission wallet') + '"><i class="ti ti-coins ti-sm me-2 text-success"></i></a>' +
              fundTaskBtn +
              '<a href="javascript:;" class="text-body link-tasks" data-id="' + full['id'] + '" data-name="' + full['name'] + '" data-bs-toggle="modal" data-bs-target="#linkTasksModal" title="' + __('Link historical tasks') + '"><i class="ti ti-link ti-sm me-2 text-warning"></i></a>' +
              '<a href="javascript:;" class="text-body edit-record" data-id="' + full['id'] + '" data-bs-toggle="modal" data-bs-target="#investorModal" title="' + __('Edit') + '"><i class="ti ti-edit ti-sm me-2"></i></a>' +
              '<a href="javascript:;" class="text-body delete-record" data-id="' + full['id'] + '" title="' + __('Delete') + '"><i class="ti ti-trash ti-sm mx-2"></i></a>' +
              '</div>'
            );
          }
        }
      ],
      order: [[0, 'desc']],
      dom:
        '<"row me-2"' +
        '<"col-md-2"<"me-3"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>' +
        '>t' +
        '<"row mx-2"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: __('Search...'),
        paginate: {
          next: '<i class="ti ti-chevron-left ti-sm"></i>',
          previous: '<i class="ti ti-chevron-right ti-sm"></i>'
        }
      },
      buttons: []
    });

    dt_user.on('xhr.dt', function (e, settings, json, xhr) {
      if (json && json.summary) {
        $('#total-investors').text(json.summary.total);
        $('#active-investors').text(json.summary.active);
        $('#task-based-investors').text(json.summary.task_based);
        $('#general-based-investors').text(json.summary.general_based);
      }
    });
  }

  // Delete Record
  $('.datatables-investors tbody').on('click', '.delete-record', function () {
    var id = $(this).data('id');
    Swal.fire({
      title: __('Are you sure?'),
      text: __('You will not be able to revert this! Please enter your password to confirm.'),
      input: 'password',
      inputPlaceholder: __('Enter your password'),
      inputAttributes: {
        autocapitalize: 'off',
        autocorrect: 'off'
      },
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: __('Yes, delete it!'),
      cancelButtonText: __('Cancel'),
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false,
      preConfirm: (password) => {
        if (!password) {
          Swal.showValidationMessage(__('Password is required'));
        }
        return password;
      }
    }).then(function (result) {
      if (result.isConfirmed) {
        $.ajax({
          type: 'DELETE',
          url: baseUrl + 'admin/investors/delete/' + id,
          data: {
            password: result.value
          },
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (data) {
            if (data.status == 1) {
              dt_user.draw();
              if (data.summary) {
                $('#total-investors').text(data.summary.total);
                $('#active-investors').text(data.summary.active);
                $('#task-based-investors').text(data.summary.task_based);
                $('#general-based-investors').text(data.summary.general_based);
              }
              Swal.fire({
                icon: 'success',
                title: __('Deleted!'),
                text: __('Investor deleted successfully'),
                customClass: {
                  confirmButton: 'btn btn-success'
                }
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: __('Error'),
                text: data.error,
                customClass: {
                  confirmButton: 'btn btn-primary'
                }
              });
            }
          },
          error: function (error) {
            console.log(error);
          }
        });
      }
    });
  });

  // Edit Record
  $('.datatables-investors tbody').on('click', '.edit-record', function () {
    var id = $(this).data('id');
    $('#modalTitle').text(__('Edit Investor'));
    $('#pass-hint').show();
    $('#additional-form').html('');
    
    $.get(baseUrl + 'admin/investors/show/' + id, function (data) {
      $('#investor_id').val(data.id);
      $('input[name="name"]').val(data.name);
      $('input[name="email"]').val(data.email);
      $('input[name="phone"]').val(data.phone);
      $('select[name="phone_code"]').val(data.phone_code);
      $('select[name="status"]').val(data.status);
      
      $('#select-template').val(data.form_template_id || '');
      if (data.form_template_id) {
        generateFields(data.fields, data.additional_data);
      }

      // Load bank details
      $('#user-bank-name').val(data.bank_name || '');
      $('#user-account-number').val(data.account_number || '');
      $('#user-iban-number').val(data.iban_number || '');
      $('#user-bic-code').val(data.bic_code || '');
      $('#user-beneficiary-name').val(data.beneficiary_name || '');
      $('#user-bank-address1').val(data.bank_address1 || '');
      $('#user-bank-address2').val(data.bank_address2 || '');
      $('#user-bank-city').val(data.bank_city || '');
      $('#user-bank-country').val(data.bank_country || '');

      // Handle custom bank name
      if (data.bank_name && !$('#user-bank-name option[value="' + data.bank_name + '"]').length) {
        $('#user-bank-name').val('other');
        $('#user-custom-bank-name').val(data.bank_name);
      }
      toggleCustomBankField();

      if (data.active_investment_contract) {
        let c = data.active_investment_contract;
        $('select[name="contract_type"]').val(c.contract_type);
        $('select[name="commission_type"]').val(c.commission_type);
        $('input[name="commission_value"]').val(c.commission_value);
        $('input[name="start_date"]').val(c.start_date.split('T')[0]);
        $('input[name="end_date"]').val(c.end_date ? c.end_date.split('T')[0] : '');
        $('input[name="min_commission_threshold"]').val(c.min_commission_threshold);
        
        if (c.filter_customer_ids) {
            $('#customer_ids').val(c.filter_customer_ids).trigger('change');
        } else {
            $('#customer_ids').val([]).trigger('change');
        }

        $('#broker_id').val(c.broker_id || '').trigger('change');
        $('select[name="broker_commission_source"]').val(c.broker_commission_source || 'investor_commission');
        $('select[name="broker_commission_type"]').val(c.broker_commission_type || 'percentage');
        $('input[name="broker_commission_value"]').val(c.broker_commission_value || '0.00');
      } else {
        $('#broker_id').val('').trigger('change');
        $('select[name="broker_commission_source"]').val('investor_commission');
        $('select[name="broker_commission_type"]').val('percentage');
        $('input[name="broker_commission_value"]').val('0.00');
      }
    });
  });

  $('#btn-add-investor').on('click', function () {
    $('#modalTitle').text(__('Add New Investor'));
    $('#pass-hint').hide();
    $('#contract_type').prop('disabled', false);
    $('#additional-form').html('');
    $('#select-template').val('');
    $('#user-bank-name').val('');
    $('#user-custom-bank-name').val('');
    $('#user-bic-code').val('');
    $('#user-beneficiary-name').val('');
    $('#user-bank-address1').val('');
    $('#user-bank-address2').val('');
    $('#user-bank-city').val('');
    $('#user-bank-country').val('');
    toggleCustomBankField();
    userForm[0].reset();
    $('#investor_id').val('');
    $('#customer_ids').val([]).trigger('change');
    $('#broker_id').val('').trigger('change');
    $('select[name="broker_commission_source"]').val('investor_commission');
    $('select[name="broker_commission_type"]').val('percentage');
    $('input[name="broker_commission_value"]').val('0.00');
  });

  // Handle Form Submit Success
  document.addEventListener('formSubmitted', function (event) {
    if (event.detail.status == 1) {
      $('#investorModal').modal('hide');
      dt_user.draw();
      // Reset form is handled by ajax.js for .form_submit
    }
  });

  // Reset Password Toggle
  $('.datatables-investors tbody').on('change', '.reset-password', function () {
    var id = $(this).data('id');
    $.ajax({
      url: baseUrl + 'admin/investors/reset-password',
      type: 'POST',
      data: { id: id },
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (data) {
        if (data.status == 1) {
          toastr.success(__('Reset password status changed'));
        } else {
          toastr.error(__('Error changing status'));
        }
      }
    });
  });

  // View Record
  $('.datatables-investors tbody').on('click', '.view-record', function () {
    var id = $(this).data('id');
    
    $.get(baseUrl + 'admin/investors/show/' + id, function (data) {
      $('#view-name').text(data.name);
      $('#view-email').text(data.email);
      $('#view-phone').text(data.phone_code + data.phone);
      $('#view-status').html('<span class="badge bg-label-success">' + data.status + '</span>');
      
      // Balances
      $('#view-invest-balance').text((data.investor_wallet ? data.investor_wallet.balance : '0.00') + ' ' + __('SAR'));
      $('#view-commission-balance').text((data.user_wallet ? data.user_wallet.balance : '0.00') + ' ' + __('SAR'));
      
      // Contract
      if (data.active_investment_contract) {
        let c = data.active_investment_contract;
        $('#view-contract-type').text(c.contract_type == 'task_investment' ? __('Task-based investment') : __('General investment'));
        $('#view-contract-commission').text(c.commission_value + (c.commission_type == 'percentage' ? '%' : ' ' + __('SAR')));
        $('#view-contract-start').text(c.start_date.split('T')[0]);
        $('#view-contract-end').text(c.end_date ? c.end_date.split('T')[0] : __('Open'));
        $('#view-contract-min').text(c.min_commission_threshold + ' ' + __('SAR'));
      } else {
        $('#view-contract-type').text(__('No active contract for investor'));
        $('#view-contract-commission, #view-contract-start, #view-contract-end, #view-contract-min').text('-');
      }
    });
  });

  // Historical Tasks Linking
  let selectedInvestorId = null;

  $('.datatables-investors tbody').on('click', '.link-tasks', function () {
    var id = $(this).data('id');
    var name = $(this).data('name');
    selectedInvestorId = id;
    $('#investor-name-modal').text(name);
    $('#historicalTasksBody').html('<tr><td colspan="8" class="text-center"><div class="spinner-border text-primary" role="status"></div> ' + __('Loading tasks...') + '</td></tr>');
    $('#selectAllTasks').prop('checked', false);

    $.get(baseUrl + 'admin/investors/' + id + '/available-tasks', function (data) {
      if (data.status == 1) {
        let html = '';
        if (data.tasks.length === 0) {
          html = '<tr><td colspan="8" class="text-center">' + __('No tasks available for linking') + '</td></tr>';
        } else {
          data.tasks.forEach(task => {
            let driverName = task.driver ? task.driver.name : '<span class="text-muted">' + __('Not specified') + '</span>';
            let vehicleName = '-';
            if (task.vehicle_size && task.vehicle_size.type && task.vehicle_size.type.vehicle) {
              vehicleName = task.vehicle_size.type.vehicle.name + ' - ' + task.vehicle_size.type.name + ' - ' + task.vehicle_size.name;
            }
            let fromAddr = task.pickup ? task.pickup.address : '-';
            let toAddr = task.delivery ? task.delivery.address : '-';

            html += `
              <tr>
                <td><input type="checkbox" class="form-check-input task-checkbox" value="${task.id}"></td>
                <td>#${task.id}</td>
                <td>${task.customer ? task.customer.name : __('General customer')}</td>
                <td>${driverName}</td>
                <td><small>${vehicleName}</small></td>
                <td><small>${__('From')}: ${fromAddr}<br>${__('To')}: ${toAddr}</small></td>
                <td>${task.total_price} ${__('SAR')}</td>
                <td>${new Date(task.created_at).toLocaleDateString('ar-SA')}</td>
              </tr>
            `;
          });
        }
        $('#historicalTasksBody').html(html);
      } else {
        $('#historicalTasksBody').html('<tr><td colspan="5" class="text-center text-danger">' + data.error + '</td></tr>');
      }
    });
  });

  // Select/Deselect All
  $('#selectAllTasks').on('change', function() {
    $('.task-checkbox').prop('checked', $(this).prop('checked'));
  });

  // Submit Linking
  $('#btnLinkTasks').on('click', function() {
    let taskIds = [];
    $('.task-checkbox:checked').each(function() {
      taskIds.push($(this).val());
    });

    if (taskIds.length === 0) {
      Swal.fire({ icon: 'warning', title: __('Alert'), text: __('Please select at least one task') });
      return;
    }

    Swal.fire({
      title: __('Are you sure?'),
      text: __('Tasks will be linked confirm', { count: taskIds.length }),
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: __('Yes, link now'),
      cancelButtonText: __('Cancel'),
      customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        $.ajax({
          url: baseUrl + 'admin/investors/link-tasks',
          type: 'POST',
          data: {
            investor_id: selectedInvestorId,
            task_ids: taskIds
          },
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
          success: function (data) {
            if (data.status == 1) {
              $('#linkTasksModal').modal('hide');
              dt_user.draw();
              Swal.fire({ icon: 'success', title: __('Done!'), text: data.success });
            } else {
              Swal.fire({ icon: 'error', title: __('Error'), text: data.error });
            }
          }
        });
      }
    });
  });
});
