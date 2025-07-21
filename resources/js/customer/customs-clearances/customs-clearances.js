/**
 * Customer Customs Clearances Management
 */

'use strict';
import { deleteRecord, showAlert, showFormModal, generateFields, handleErrors, showBlockAlert } from '../../ajax';

$(function () {
  // Variable declaration for table
  var dt_data_table = $('.datatables-customs-clearances'),
    clearanceView = baseUrl + 'customer/customs-clearances/';

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
        url: baseUrl + 'customer/customs-clearances/data',
        data: function (d) {
          d.status = $('#statusFilter').val();
          d.closed = $('#closedFilter').val();
          d.date_from = $('#dateFromFilter').val();
          d.date_to = $('#dateToFilter').val();
        },
        dataSrc: function (json) {
          updateStatisticsCards(json.summary);
          return json.data;
        }
      },
      columns: [
        { data: '' }, // للـ control (responsive)
        { data: 'id' },
        { data: 'clearance_agent' },
        { data: 'status' },
        { data: 'price_info' },
        { data: 'offers_count' },
        { data: 'created_at' },
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
            return `<span class="fw-medium">#${full.id}</span>`;
          }
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            if (!full.clearance_agent) return '<span class="text-muted">غير معين</span>';
            
            var agentType = full.clearance_agent_type === 'customer' ? 'عميل' : 'مدير';
            var badgeClass = full.clearance_agent_type === 'customer' ? 'bg-label-success' : 'bg-label-warning';
            
            return `
              <div class="d-flex flex-column">
                <span class="fw-medium">${full.clearance_agent.name}</span>
                <small class="badge ${badgeClass}">${agentType}</small>
              </div>
            `;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            var statusMap = {
              'in_progress': { text: 'قيد المعالجة', class: 'bg-label-warning' },
              'advertised': { text: 'معلن', class: 'bg-label-info' },
              'assigned': { text: 'معين', class: 'bg-label-primary' },
              'accepted': { text: 'مقبول', class: 'bg-label-success' },
              'started': { text: 'بدأ', class: 'bg-label-secondary' },
              'completed': { text: 'مكتمل', class: 'bg-label-success' },
              'cancelled': { text: 'ملغي', class: 'bg-label-danger' }
            };
            
            var status = statusMap[full.status] || { text: full.status, class: 'bg-label-secondary' };
            return `<span class="badge ${status.class}">${status.text}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            if (!full.price || full.price == 0) {
              return '<span class="text-muted">غير محدد</span>';
            }
            
            var html = `<div class="d-flex flex-column">`;
            html += `<span class="fw-medium">${parseFloat(full.price).toFixed(2)} ر.س</span>`;
            
            if (full.commission && full.commission > 0) {
              html += `<small class="text-muted">عمولة: ${parseFloat(full.commission).toFixed(2)} ر.س</small>`;
            }
            
            html += `</div>`;
            return html;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            var count = full.offers_count || 0;
            var badgeClass = count > 0 ? 'bg-label-success' : 'bg-label-secondary';
            return `<span class="badge ${badgeClass}">${count} عرض</span>`;
          }
        },
        {
          targets: 6,
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
            var actions = `
              <div class="d-flex align-items-center">
                <a href="${clearanceView}${full.id}" class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect" data-bs-toggle="tooltip" title="عرض التفاصيل">
                  <i class="ti ti-eye ti-md"></i>
                </a>
            `;
            
            // إجراءات حسب الحالة والصلاحيات
            if (full.can_edit) {
              actions += `
                <a href="${clearanceView}${full.id}/edit" class="btn btn-sm btn-icon btn-text-primary rounded-pill waves-effect" data-bs-toggle="tooltip" title="تعديل">
                  <i class="ti ti-edit ti-md"></i>
                </a>
              `;
            }
            
            if (full.can_delete) {
              actions += `
                <button class="btn btn-sm btn-icon btn-text-danger rounded-pill waves-effect delete-record" data-id="${full.id}" data-bs-toggle="tooltip" title="حذف">
                  <i class="ti ti-trash ti-md"></i>
                </button>
              `;
            }
            
            actions += `</div>`;
            return actions;
          }
        }
      ],
      order: [[1, 'desc']],
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 25,
      lengthMenu: [10, 25, 50, 75, 100],
      language: {
        url: baseUrl + 'assets/json/datatable-ar.json'
      },
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'تفاصيل الطلب #' + data.id;
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

  // Filter functionality
  $('#applyFilters').on('click', function () {
    dt_data.draw();
  });

  $('#clearFilters').on('click', function () {
    $('#statusFilter, #closedFilter').val('').trigger('change');
    $('#dateFromFilter, #dateToFilter').val('');
    dt_data.draw();
  });

  $('#refreshTable').on('click', function () {
    dt_data.draw();
    loadStatistics();
  });

  // Initialize date pickers
  if ($('.flatpickr-date').length) {
    $('.flatpickr-date').flatpickr({
      dateFormat: 'Y-m-d'
    });
  }

  // Initialize Select2
  if ($('.select2').length) {
    $('.select2').select2({
      allowClear: true,
      placeholder: 'اختر...'
    });
  }

  // Event handlers
  $(document).on('click', '.delete-record', function () {
    var id = $(this).data('id');
    deleteRecord('Customs Clearance', baseUrl + 'customer/customs-clearances/' + id);
  });

  // Form submissions
  document.addEventListener('formSubmitted', function(e) {
    if (e.detail.status === 1) {
      dt_data.draw();
      loadStatistics();
      $('.modal').modal('hide');
    }
  });

  // Helper functions
  function loadStatistics() {
    $.get(baseUrl + 'customer/customs-clearances/statistics')
      .done(function(response) {
        if (response.status === 1) {
          updateStatisticsCards(response.data);
        }
      })
      .fail(function() {
        console.log('Failed to load statistics');
      });
  }

  function updateStatisticsCards(data) {
    $('#totalRequests').text(data.total_requests || 0);
    $('#inProgressRequests').text(data.in_progress || 0);
    $('#completedRequests').text(data.completed || 0);
    $('#totalSpent').text(parseFloat(data.total_spent || 0).toFixed(2) + ' ر.س');
  }
});
