/**
 * Page User List
 */

'use strict';
import { set } from 'lodash';
import { deleteRecord, showAlert, showBlockAlert, generateFields, showFormModal } from '../../ajax';

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

  var start_from = moment().startOf('month').format('YYYY-MM-DD');
  var end_to = moment().endOf('month').format('YYYY-MM-DD');
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
        }
      },
      columns: [
        { data: '' }, // للـ control (responsive)
        { data: 'id' }, // الترقيم التسلسلي
        { data: 'order' }, // الاسم مع الأفاتار
        { data: 'team' }, // البريد
        { data: 'driver' }, // الجوال
        { data: 'address' }, // الحالة
        { data: 'start' }, // الحالة
        { data: 'complete' }, // الحالة
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
            return `<span>${full.id}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            return `<span>${full.order}</span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return `<span>${full.team}</span>`;
          }
        },
        {
          targets: 4,
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
          targets: 5,
          render: function (data, type, full, meta) {
            return `<span>${full.owner}</span>`;
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            return `<span>${full.address}</span>`;
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            return `<span>${full.start}</span>`;
          }
        },
        {
          targets: 8,
          render: function (data, type, full, meta) {
            return `<span>${full.complete}</span>`;
          }
        },
        {
          targets: 9,
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
          targets: 10,
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
          targets: 11,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `
              <div class="d-flex align-items-center gap-2">

                <div class="dropdown">
                  <button class="btn btn-sm btn-icon  dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                    <i class="ti ti-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li><a href="javascript:;" class="dropdown-item payment-task"  data-id="${full.id}">Payment Task</a></li>
                    <li><a href="javascript:;" class="dropdown-item status-record" data-id="${full.id}" data-name="${full.name}" data-status="${full.status}">Change Status</a></li>
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
              return 'Details of ' + data.name;
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

  $(document).on('click', '.payment-task', function () {
    const id = $(this).data('id');
    $.get(`${baseUrl}admin/tasks/payment/${id}`, function (data) {
      console.log(data);
      if (data.status === 2) {
        showAlert('error', data.error);
        return;
      }
      $('#task-payment-commission').val(data.commission);
      $('#task-payment-total').val(data.total_price);
      $('#assignTitle').html(`Payment Task: <span class="bg-info text-white px-2 rounded">#${id}</span>`);
      $('#paymentModal').modal('show');
      $('#task-payment-id').val(id);
      $('#pay-price').text(data.total_price + ' SAR');
    });
  });

  $(document).on('change', '#task-payment-method', function () {
    const method = $(this).val();
    const commission = $('#task-payment-commission').val();
    if (method === 'credit') {
      $('#receipt-section').hide();

      $('#pay-price').text($('#task-payment-total').val() + ' SAR');
    } else if (method === 'cash') {
      $('#receipt-section').hide();

      $('#pay-price').text('You need to Bay :' + $('#task-payment-commission').val() + ' SAR by credit card');
    } else if (method === 'wallet') {
      $('#receipt-section').hide();

      $('#pay-price').text($('#task-payment-total').val() + ' SAR');
    } else {
      $('#receipt-section').show();
      $('#pay-price').text($('#task-payment-total').val() + ' SAR');
    }
  });

  $('#dateRange').daterangepicker(
    {
      opens: 'left',
      locale: {
        format: 'DD MMM YYYY',
        cancelLabel: 'Cancel',
        applyLabel: 'Apply'
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
                }
              } else if (data.status === 2) {
                showAlert('error', data.error, 10000, true);
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
});
