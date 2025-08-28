/**
 * Customer Tasks Report JavaScript
 * SafeDests Platform Reports
 */

console.log('reports');

class CustomerTasksReport {
  constructor() {
    this.availableColumns = {
      task_id: { name: 'Task ID', required: true },
      total_price: { name: 'Task Price', required: true },
      pickup_info: { name: 'Pickup Information', required: false },
      delivery_info: { name: 'Delivery Information', required: false },
      vehicle_name: { name: 'Vehicle Name', required: false },
      driver_info: { name: 'Driver Information', required: false },
      status: { name: 'Task Status', required: false },
      payment_status: { name: 'Payment Status', required: false },
      payment_method: { name: 'Payment Method', required: false },
      created_by: { name: 'Created By', required: false },
      created_at: { name: 'Creation Date', required: false },
      completed_at: { name: 'Completion Date', required: false },
      closed_at: { name: 'Closing Date', required: false }
    };

    this.selectedColumns = ['task_id', 'total_price'];
    this.reportData = null;

    this.init();
  }

  init() {
    this.initializeDateRangePicker();
    this.initializeSelect2();
    this.initializeColumnSelector();
    this.loadSavedPreferences();
    this.bindEvents();
  }

  bindEvents() {
    $('#previewBtn').on('click', () => this.previewReport());
    $('#resetBtn').on('click', () => this.resetFilters());
    $('#exportExcelBtn').on('click', () => this.exportReport('excel'));
    $('#exportPdfBtn').on('click', () => this.exportReport('pdf'));
  }

  initializeDateRangePicker() {
    // Check if moment and daterangepicker are available
    if (typeof moment === 'undefined') {
      console.error('Moment.js is not available');
      return;
    }

    if (typeof $.fn.daterangepicker === 'undefined') {
      console.error('DateRangePicker is not available');
      return;
    }

    const $dateRange = $('#dateRange');
    if ($dateRange.length === 0) {
      console.error('Date range input not found');
      return;
    }

    $dateRange.daterangepicker(
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
        startDate: moment().subtract(29, 'days'),
        endDate: moment()
      },
      (start, end, label) => {
        $('#date_from').val(start.format('YYYY-MM-DD'));
        $('#date_to').val(end.format('YYYY-MM-DD'));
      }
    );

    // Set initial values
    $('#date_from').val(moment().subtract(29, 'days').format('YYYY-MM-DD'));
    $('#date_to').val(moment().format('YYYY-MM-DD'));

    console.log('DateRangePicker initialized');
  }

  initializeSelect2() {
    // Check if Select2 is available
    if (typeof $.fn.select2 === 'undefined') {
      console.error('Select2 is not available');
      return;
    }

    $('.select2').each(function () {
      const $this = $(this);
      if (!$this.hasClass('select2-hidden-accessible')) {
        $this.select2({
          theme: 'bootstrap-5',
          placeholder: $this.attr('multiple') ? 'Select one or more...' : 'Select...',
          allowClear: true,
          width: '100%',
          language: {
            noResults: function () {
              return 'No results found';
            },
            searching: function () {
              return 'Searching...';
            }
          }
        });
      }
    });

    console.log('Select2 initialized for', $('.select2').length, 'elements');
  }

  initializeColumnSelector() {
    const container = $('#columnSelector');
    if (container.length === 0) {
      console.error('Column selector container not found');
      return;
    }

    container.empty();

    Object.keys(this.availableColumns).forEach(key => {
      const column = this.availableColumns[key];
      const isSelected = this.selectedColumns.includes(key);
      const isRequired = column.required;

      const columnItem = $(`
                <div class="column-item ${isRequired ? 'required' : ''}" data-column="${key}">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               id="col_${key}" ${isSelected ? 'checked' : ''}
                               ${isRequired ? 'disabled' : ''}>
                        <label class="form-check-label" for="col_${key}">
                            ${column.name} ${isRequired ? '(مطلوب)' : ''}
                        </label>
                    </div>
                </div>
            `);

      container.append(columnItem);
    });

    // Handle column selection
    container.off('change', '.form-check-input'); // Remove existing handlers
    container.on('change', '.form-check-input', e => {
      const column = $(e.target).closest('.column-item').data('column');
      if (e.target.checked) {
        if (!this.selectedColumns.includes(column)) {
          this.selectedColumns.push(column);
        }
      } else {
        this.selectedColumns = this.selectedColumns.filter(col => col !== column);
      }
      this.updateSelectedCount();
      this.savePreferences();
    });

    this.updateSelectedCount();
    console.log('Column selector initialized with', Object.keys(this.availableColumns).length, 'columns');
  }

  updateSelectedCount() {
    $('#selectedCount').text(this.selectedColumns.length);

    // Enable/disable export buttons based on minimum requirement
    const canExport = this.selectedColumns.length >= 4;
    $('#exportExcelBtn, #exportPdfBtn').prop('disabled', !canExport || !this.reportData);
  }

  previewReport() {
    if (!this.validateForm()) return;

    this.showLoading();

    const formData = this.getFormData();

    $.ajax({
      url: window.routes.preview,
      method: 'POST',
      data: formData,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: response => {
        if (response.success) {
          this.reportData = response.data;
          this.displayPreview(response.data, response.summary);
          $('.preview-section').show();
          this.updateSelectedCount();
        } else {
          this.showError(response.message);
        }
      },
      error: xhr => {
        this.showError('Error occurred while fetching data');
      },
      complete: () => {
        this.hideLoading();
      }
    });
  }

  displayPreview(data, summary) {
    let tableHtml = this.buildPreviewTable(data);
    tableHtml += this.buildSummarySection(summary);

    $('#previewTable').html(tableHtml);
    this.initializeDataTable();
  }

  buildPreviewTable(data) {
    let html = `
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="previewDataTable">
                    <thead class="table-dark">
                        <tr>
        `;

    // Add headers
    this.selectedColumns.forEach(column => {
      if (this.availableColumns[column]) {
        html += `<th>${this.availableColumns[column].name}</th>`;
      }
    });

    html += `</tr></thead><tbody>`;

    // Add data rows (limit to first 50 for preview)
    const previewData = data.slice(0, 50);
    previewData.forEach(row => {
      html += '<tr>';
      this.selectedColumns.forEach(column => {
        html += `<td>${this.formatCellValue(column, row)}</td>`;
      });
      html += '</tr>';
    });

    html += `</tbody></table></div>`;
    return html;
  }

  formatCellValue(column, row) {
    switch (column) {
      case 'task_id':
        return row.id;
      case 'total_price':
        return parseFloat(row.total_price).toLocaleString('ar-SA') + ' ريال';
      case 'pickup_info':
        return `${row.pickup_address}<br><small>المسؤول: ${row.pickup_contact_name}<br>الهاتف: ${row.pickup_contact_phone}</small>`;
      case 'delivery_info':
        return `${row.delivery_address}<br><small>المسؤول: ${row.delivery_contact_name}<br>الهاتف: ${row.delivery_contact_phone}</small>`;
      case 'vehicle_name':
        return row.vehicle_name;
      case 'driver_info':
        return `${row.driver_name}<br><small>الهاتف: ${row.driver_phone}<br>الفريق: ${row.team_name}</small>`;
      case 'status':
        return `<span class="badge bg-primary">${row.status_ar}</span>`;
      case 'payment_status':
        return `<span class="badge bg-success">${row.payment_status_ar}</span>`;
      case 'payment_method':
        return row.payment_method_ar;
      case 'created_by':
        return `${row.created_by}<br><small>${row.created_by_name}</small>`;
      case 'created_at':
        return row.created_at_formatted;
      case 'completed_at':
        return row.completed_at_formatted || 'لم تكتمل بعد';
      case 'closed_at':
        return row.closed_at_formatted || 'لم تُغلق بعد';
      default:
        return '';
    }
  }

  buildSummarySection(summary) {
    return `
            <div class="mt-3 p-3 bg-light rounded">
                <h6>ملخص التقرير:</h6>
                <div class="row">
                    <div class="col-md-3">
                        <strong>إجمالي المهام:</strong> ${summary.total_tasks}
                    </div>
                    <div class="col-md-3">
                        <strong>إجمالي المبلغ:</strong> ${parseFloat(summary.total_amount).toLocaleString('ar-SA')} ريال
                    </div>
                    <div class="col-md-3">
                        <strong>متوسط السعر:</strong> ${parseFloat(summary.average_amount).toLocaleString('ar-SA')} ريال
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">عرض أول 50 سجل للمعاينة</small>
                    </div>
                </div>
            </div>
        `;
  }

  initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#previewDataTable')) {
      $('#previewDataTable').DataTable().destroy();
    }

    $('#previewDataTable').DataTable({
      responsive: true,
      pageLength: 25,
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
      }
    });
  }

  exportReport(type) {
    if (!this.validateForm() || this.selectedColumns.length < 4) {
      this.showError('Please select at least 4 columns');
      return;
    }

    this.showLoading();

    const formData = this.getFormData();
    formData.columns = this.selectedColumns;
    formData.export_type = type;

    // Create form and submit
    const form = this.createExportForm(formData);

    if (type === 'pdf') {
      form.attr('target', '_blank');
    }

    form.submit();
    form.remove();

    setTimeout(() => {
      this.hideLoading();
    }, 2000);
  }

  createExportForm(formData) {
    const form = $('<form>', {
      method: 'POST',
      action: window.routes.generate
    });

    // Add CSRF token
    form.append(
      $('<input>', {
        type: 'hidden',
        name: '_token',
        value: $('meta[name="csrf-token"]').attr('content')
      })
    );

    // Add form data
    Object.keys(formData).forEach(key => {
      if (Array.isArray(formData[key])) {
        formData[key].forEach(value => {
          form.append(
            $('<input>', {
              type: 'hidden',
              name: key + '[]',
              value: value
            })
          );
        });
      } else {
        form.append(
          $('<input>', {
            type: 'hidden',
            name: key,
            value: formData[key]
          })
        );
      }
    });

    $('body').append(form);
    return form;
  }

  getFormData() {
    return {
      customer_ids: $('#customer_ids').val() || [],
      date_from: $('#date_from').val(),
      date_to: $('#date_to').val(),
      task_statuses: $('#task_statuses').val() || [],
      payment_status: $('#payment_status').val(),
      payment_method: $('#payment_method').val(),
      driver_ids: $('#driver_ids').val() || [],
      team_ids: $('#team_ids').val() || [],
      created_by: $('#created_by').val()
    };
  }

  validateForm() {
    const customerIds = $('#customer_ids').val();
    const dateFrom = $('#date_from').val();
    const dateTo = $('#date_to').val();

    if (!customerIds || customerIds.length === 0) {
      this.showError('Please select at least one customer');
      return false;
    }

    if (!dateFrom || !dateTo) {
      this.showError('Please specify the time period');
      return false;
    }

    return true;
  }

  resetFilters() {
    $('#reportForm')[0].reset();
    $('.select2').val(null).trigger('change');
    $('#dateRange').data('daterangepicker').setStartDate(moment().subtract(29, 'days'));
    $('#dateRange').data('daterangepicker').setEndDate(moment());
    $('#date_from').val(moment().subtract(29, 'days').format('YYYY-MM-DD'));
    $('#date_to').val(moment().format('YYYY-MM-DD'));
    $('.preview-section').hide();
    this.reportData = null;
    this.selectedColumns = ['task_id', 'total_price'];
    this.initializeColumnSelector();
  }

  savePreferences() {
    localStorage.setItem('customerTasksReportColumns', JSON.stringify(this.selectedColumns));
  }

  loadSavedPreferences() {
    const saved = localStorage.getItem('customerTasksReportColumns');
    if (saved) {
      try {
        const savedColumns = JSON.parse(saved);
        // Ensure required columns are always included
        this.selectedColumns = [
          'task_id',
          'total_price',
          ...savedColumns.filter(col => !['task_id', 'total_price'].includes(col))
        ];
        this.initializeColumnSelector();
      } catch (e) {
        console.log('Error loading saved preferences');
      }
    }
  }

  // Utility functions
  showLoading() {
    $('#loadingOverlay').show();
  }

  hideLoading() {
    $('#loadingOverlay').hide();
  }

  showError(message) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: message,
      confirmButtonText: 'OK'
    });
  }

  showSuccess(message) {
    Swal.fire({
      icon: 'success',
      title: 'Success',
      text: message,
      confirmButtonText: 'OK'
    });
  }
}

// Class is now initialized from the Blade template
// No automatic initialization here
