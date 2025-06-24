$(function () {
  'use strict';

  var dt_wallet_table = $('.datatables-wallet');

  // Wallet Transactions DataTable
  if (dt_wallet_table.length) {
    var dt_wallet = dt_wallet_table.DataTable({
      ajax: {
        url: baseUrl + 'customer/wallet/data',
        data: function (d) {
          d.from_date = $('#from_date').val();
          d.to_date = $('#to_date').val();
          d.status = $('#type_filter').val();
        }
      },
      columns: [
        { data: '' },
        { data: 'fake_id' },
        { data: 'amount' },
        { data: 'transaction_type' },
        { data: 'description' },
        { data: 'task' },
        { data: 'maturity' },
        { data: 'status' },
        { data: 'created_at' }
      ],
      columnDefs: [
        {
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
          visible: false
        },
        {
          targets: 2,
          render: function (data, type, full, meta) {
            var colorClass = full.transaction_type === 'Credit' ? 'text-success' : 'text-danger';
            return `<span class="${colorClass} fw-bold">${full.amount}</span>`;
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            var badgeClass = full.transaction_type === 'Credit' ? 'bg-label-success' : 'bg-label-danger';
            var icon = full.transaction_type === 'Credit' ? 'ti-arrow-up' : 'ti-arrow-down';
            return `<span class="badge ${badgeClass}"><i class="ti ${icon} me-1"></i>${full.transaction_type}</span>`;
          }
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            return `<span class="text-truncate" title="${full.description}">${full.description}</span>`;
          }
        },
        {
          targets: 5,
          render: function (data, type, full, meta) {
            if (full.task && full.task !== 'N/A') {
              return `<a href="${baseUrl}customer/tasks/show/${full.task.replace('#', '')}" class="text-primary">${full.task}</a>`;
            }
            return '<span class="text-muted">N/A</span>';
          }
        },
        {
          targets: 6,
          render: function (data, type, full, meta) {
            return full.maturity || '<span class="text-muted">N/A</span>';
          }
        },
        {
          targets: 7,
          render: function (data, type, full, meta) {
            var statusObj = {
              'Pending': { title: 'Pending', class: 'bg-label-warning' },
              'Completed': { title: 'Completed', class: 'bg-label-success' },
              'Failed': { title: 'Failed', class: 'bg-label-danger' },
              'Cancelled': { title: 'Cancelled', class: 'bg-label-secondary' }
            };
            return (
              '<span class="badge ' +
              (statusObj[full.status]?.class || 'bg-label-secondary') +
              '">' +
              (statusObj[full.status]?.title || full.status) +
              '</span>'
            );
          }
        },
        {
          targets: 8,
          render: function (data, type, full, meta) {
            return full.created_at;
          }
        }
      ],
      order: [[1, 'desc']],
      dom: '<"row mx-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      displayLength: 10,
      lengthMenu: [10, 25, 50, 75, 100],
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-label-secondary dropdown-toggle mx-3',
          text: '<i class="ti ti-screen-share me-1 ti-xs"></i>Export',
          buttons: [
            {
              extend: 'print',
              text: '<i class="ti ti-printer me-2" ></i>Print',
              className: 'dropdown-item',
              exportOptions: {
                columns: [2, 3, 4, 5, 6, 7, 8],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              },
              customize: function (win) {
                $(win.document.body)
                  .css('color', headingColor)
                  .css('border-color', borderColor)
                  .css('background-color', bodyBg);
                $(win.document.body)
                  .find('table')
                  .addClass('compact')
                  .css('color', 'inherit')
                  .css('border-color', 'inherit')
                  .css('background-color', 'inherit');
              }
            },
            {
              extend: 'csv',
              text: '<i class="ti ti-file-text me-2" ></i>Csv',
              className: 'dropdown-item',
              exportOptions: {
                columns: [2, 3, 4, 5, 6, 7, 8],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'excel',
              text: '<i class="ti ti-file-spreadsheet me-2"></i>Excel',
              className: 'dropdown-item',
              exportOptions: {
                columns: [2, 3, 4, 5, 6, 7, 8],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'pdf',
              text: '<i class="ti ti-file-code-2 me-2"></i>Pdf',
              className: 'dropdown-item',
              exportOptions: {
                columns: [2, 3, 4, 5, 6, 7, 8],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'copy',
              text: '<i class="ti ti-copy me-2" ></i>Copy',
              className: 'dropdown-item',
              exportOptions: {
                columns: [2, 3, 4, 5, 6, 7, 8],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.classList !== undefined && item.classList.contains('user-name')) {
                        result = result + item.lastChild.firstChild.textContent;
                      } else if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else result = result + item.innerText;
                    });
                    return result;
                  }
                }
              }
            }
          ]
        }
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Transaction Details';
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

  // Date range picker
  $('#from_date, #to_date').datepicker({
    format: 'yyyy-mm-dd',
    autoclose: true,
    todayHighlight: true
  });

  // Filter handlers
  $('#from_date, #to_date, #type_filter').on('change', function () {
    dt_wallet.draw();
  });
});
