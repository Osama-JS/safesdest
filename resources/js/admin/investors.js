/**
 * Page User List
 */

'use strict';

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
    var $this = select2;
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'اختر العملاء',
      dropdownParent: $this.parent()
    });
  }

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
            return '<span class="fw-bold text-success">' + data + ' ر.س</span>';
          }
        },
        {
          // Status
          targets: 6,
          render: function (data, type, full, meta) {
            var $status_number = data;
            var $status = {
              active: { title: 'نشط', class: 'bg-label-success' },
              inactive: { title: 'غير نشط', class: 'bg-label-danger' },
              pending: { title: 'قيد المراجعة', class: 'bg-label-warning' }
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
          render: function (data, type, full, meta) {
            var $status = full['reset_password'];
            return (
              '<label class="switch">' +
              '<input type="checkbox" class="switch-input reset-password" data-id="' +
              full['id'] +
              '" ' +
              ($status == 1 ? 'checked' : '') +
              '>' +
              '<span class="switch-toggle-slider">' +
              '<span class="switch-on"></span>' +
              '<span class="switch-off"></span>' +
              '</span>' +
              '</label>'
            );
          }
        },
        {
          // Actions
          targets: -1,
          title: 'الإجراءات',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center">' +
              '<a href="javascript:;" class="text-body view-record" data-id="' + full['id'] + '" data-bs-toggle="modal" data-bs-target="#viewInvestorModal" title="عرض التفاصيل"><i class="ti ti-eye ti-sm me-2"></i></a>' +
              '<a href="' + baseUrl + 'admin/investors/' + full['id'] + '/invest-wallet" class="text-body" title="محفظة الاستثمار"><i class="ti ti-wallet ti-sm me-2 text-primary"></i></a>' +
              '<a href="' + baseUrl + 'admin/users/' + full['id'] + '/wallet" class="text-body" title="محفظة العمولات"><i class="ti ti-coins ti-sm me-2 text-success"></i></a>' +
              '<a href="javascript:;" class="text-body edit-record" data-id="' + full['id'] + '" data-bs-toggle="modal" data-bs-target="#investorModal" title="تعديل"><i class="ti ti-edit ti-sm me-2"></i></a>' +
              '<a href="javascript:;" class="text-body delete-record" data-id="' + full['id'] + '" title="حذف"><i class="ti ti-trash ti-sm mx-2"></i></a>' +
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
        searchPlaceholder: 'بحث...',
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
      title: 'هل أنت متأكد؟',
      text: "لن تتمكن من التراجع عن هذا!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'نعم، احذف!',
      cancelButtonText: 'إلغاء',
      customClass: {
        confirmButton: 'btn btn-primary me-3',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(function (result) {
      if (result.value) {
        $.ajax({
          type: 'DELETE',
          url: baseUrl + 'admin/investors/delete/' + id,
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (data) {
            dt_user.draw();
            if (data.summary) {
              $('#total-investors').text(data.summary.total);
              $('#active-investors').text(data.summary.active);
              $('#task-based-investors').text(data.summary.task_based);
              $('#general-based-investors').text(data.summary.general_based);
            }
            Swal.fire({
              icon: 'success',
              title: 'تم الحذف!',
              text: 'تم حذف المستثمر بنجاح.',
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
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
    $('#modalTitle').text('تعديل بيانات المستثمر');
    $('#pass-hint').show();
    
    $.get(baseUrl + 'admin/investors/show/' + id, function (data) {
      $('#investor_id').val(data.id);
      $('input[name="name"]').val(data.name);
      $('input[name="email"]').val(data.email);
      $('input[name="phone"]').val(data.phone);
      $('select[name="phone_code"]').val(data.phone_code);
      $('select[name="status"]').val(data.status);
      
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
      }
    });
  });

  $('#btn-add-investor').on('click', function () {
    $('#modalTitle').text('إضافة مستثمر جديد');
    $('#pass-hint').hide();
    userForm[0].reset();
    $('#investor_id').val('');
    $('#customer_ids').val([]).trigger('change');
  });

  // Handle Form Submit
  userForm.on('submit', function (e) {
    e.preventDefault();
    var formData = $(this).serialize();
    
    $.ajax({
      url: baseUrl + 'admin/investors/store',
      type: 'POST',
      data: formData,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (data) {
        if (data.status == 1) {
          $('#investorModal').modal('hide');
          dt_user.draw();
          Swal.fire({
            icon: 'success',
            title: 'تم بنجاح!',
            text: data.success,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
        } else {
          // Validation errors handling
          let msg = '';
          if (typeof data.error === 'object') {
            $.each(data.error, function (key, value) {
              msg += value + '<br>';
            });
          } else {
            msg = data.error;
          }
          Swal.fire({
            icon: 'error',
            title: 'خطأ!',
            html: msg,
            customClass: {
              confirmButton: 'btn btn-danger'
            }
          });
        }
      }
    });
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
          toastr.success('تم تغيير حالة إعادة تعيين كلمة المرور بنجاح');
        } else {
          toastr.error('حدث خطأ أثناء تغيير الحالة');
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
      $('#view-invest-balance').text((data.investor_wallet ? data.investor_wallet.balance : '0.00') + ' ر.س');
      $('#view-commission-balance').text((data.user_wallet ? data.user_wallet.balance : '0.00') + ' ر.س');
      
      // Contract
      if (data.active_investment_contract) {
        let c = data.active_investment_contract;
        $('#view-contract-type').text(c.contract_type == 'task_investment' ? 'بالمهام' : 'عام');
        $('#view-contract-commission').text(c.commission_value + (c.commission_type == 'percentage' ? '%' : ' ر.س'));
        $('#view-contract-start').text(c.start_date.split('T')[0]);
        $('#view-contract-end').text(c.end_date ? c.end_date.split('T')[0] : 'مفتوح');
        $('#view-contract-min').text(c.min_commission_threshold + ' ر.س');
      } else {
        $('#view-contract-type').text('لا يوجد عقد نشط');
        $('#view-contract-commission, #view-contract-start, #view-contract-end, #view-contract-min').text('-');
      }
    });
  });
});
