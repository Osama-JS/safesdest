/**
 * Driver Tasks Report JavaScript
 * SafeDests Platform Reports
 */
class DriverTasksReport {
  constructor() {
    this.availableColumns = {
      task_id: { name: 'Task ID', required: true },
      total_price: { name: 'Task Price', required: true },
      pickup_info: { name: 'Pickup Information', required: false },
      delivery_info: { name: 'Delivery Information', required: false },
      vehicle_name: { name: 'Vehicle Name', required: false },
      driver_info: { name: 'Driver Information', required: true },
      status: { name: 'Task Status', required: false },
      payment_status: { name: 'Payment Status', required: false },
      payment_method: { name: 'Payment Method', required: false },
      created_by: { name: 'Created By', required: false },
      created_at: { name: 'Creation Date', required: false },
      completed_at: { name: 'Completion Date', required: false },
      closed_at: { name: 'Closing Date', required: false },
      driver_commission: { name: 'Driver Commission', required: false },
      company_commission: { name: 'Company Commission', required: false }
    };

    if (window.driverExtraColumns) {
      Object.assign(this.availableColumns, window.driverExtraColumns);
    }

    this.selectedColumns = ['task_id', 'total_price', 'driver_info'];
    this.reportData = null;

    this.init();
  }

  init() {
    console.log('DriverTasksReport: Starting initialization...');

    if (typeof $ === 'undefined') {
      console.error('jQuery is not available');
      return;
    }

    this.initializeDateRangePicker();
    this.initializeSelect2();
    this.initializeColumnSelector();
    this.loadSavedPreferences();
    this.bindEvents();
    this.initializeLoadingModal();

    console.log('DriverTasksReport: Initialization complete');
  }

  bindEvents() {
    $('#previewBtn').on('click', () => this.previewReport());
    $('#resetBtn').on('click', () => this.resetFilters());
    $('#exportExcelBtn').on('click', () => this.exportReport('excel'));
    $('#exportPdfBtn').on('click', () => this.exportReport('pdf'));
  }

  initializeDateRangePicker() {
    if (typeof moment === 'undefined' || typeof $.fn.daterangepicker === 'undefined') {
      return;
    }

    const $dateRange = $('#dateRange');
    if ($dateRange.length === 0) return;

    $dateRange.daterangepicker(
      {
        opens: 'left',
        locale: {
          format: 'YYYY-MM-DD',
          separator: ' to ',
          applyLabel: 'Apply',
          cancelLabel: 'Cancel',
          customRangeLabel: 'Custom'
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
      (start, end) => {
        $('#date_from').val(start.format('YYYY-MM-DD'));
        $('#date_to').val(end.format('YYYY-MM-DD'));
      }
    );

    $('#date_from').val(moment().subtract(29, 'days').format('YYYY-MM-DD'));
    $('#date_to').val(moment().format('YYYY-MM-DD'));
  }

  initializeSelect2() {
    if (typeof $.fn.select2 === 'undefined') return;

    $('.filter-select').each(function () {
      const $this = $(this);
      if (!$this.hasClass('select2-hidden-accessible')) {
        $this.select2({
          theme: 'bootstrap-5',
          placeholder: $this.attr('multiple') ? 'Select one or more...' : 'Select...',
          allowClear: true
        });
      }
    });
  }

  initializeColumnSelector() {
    const container = $('#columnSelector');
    if (container.length === 0) return;

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
              ${column.name} ${isRequired ? '(Required)' : ''}
            </label>
          </div>
        </div>
      `);

      container.append(columnItem);
    });

    container.off('change', '.form-check-input');
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
  }

  updateSelectedCount() {
    $('#selectedCount').text(this.selectedColumns.length);
    const canExport = this.selectedColumns.length >= 4;
    $('#exportExcelBtn, #exportPdfBtn').prop('disabled', !canExport || !this.reportData);
  }

  previewReport() {
    if (!this.validateForm()) return;

    this.showLoading('Loading Report Data');
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
      error: () => this.showError('Error occurred while fetching data'),
      complete: () => this.hideLoading()
    });
  }

  displayPreview(data, summary) {
    let html = this.buildPreviewTable(data);
    html += this.buildSummarySection(summary);
    $('#previewTable').html(html);
    this.initializeDataTable();
  }

  buildPreviewTable(data) {
    let html = `
      <div class="table-responsive">
        <table class="table table-striped table-hover" id="previewDataTable">
          <thead class="table-dark"><tr>
    `;

    this.selectedColumns.forEach(column => {
      if (this.availableColumns[column]) {
        html += `<th>${this.availableColumns[column].name}</th>`;
      }
    });

    html += `</tr></thead><tbody>`;

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
        return parseFloat(row.total_price).toLocaleString() + ' SAR';
      case 'pickup_info':
        return `${row.pickup_address}<br><small>${row.pickup_contact_name}</small>`;
      case 'delivery_info':
        return `${row.delivery_address}<br><small>${row.delivery_contact_name}</small>`;
      case 'vehicle_name':
        return row.vehicle_name;
      case 'driver_info':
        return `${row.driver_name}<br><small>${row.driver_phone}</small>`;
      case 'status':
        return `<span class="badge bg-primary">${row.status_ar}</span>`;
      case 'payment_status':
        return `<span class="badge bg-success">${row.payment_status_ar}</span>`;
      case 'payment_method':
        return row.payment_method_ar;
      case 'created_by':
        return row.created_by_name;
      case 'created_at':
        return row.created_at_formatted;
      case 'completed_at':
        return row.completed_at_formatted || '-';
      case 'closed_at':
        return row.closed_at_formatted || '-';
      case 'driver_commission':
        return parseFloat(row.driver_commission || 0).toLocaleString() + ' SAR';
      case 'company_commission':
        return parseFloat(row.company_commission || 0).toLocaleString() + ' SAR';
      default:
        if (column.startsWith('driver_extra:')) {
          return row[column] || 'غير محدد';
        }
        return '';
    }
  }

  buildSummarySection(summary) {
    return `
      <div class="mt-3 p-3 bg-light rounded">
        <h6>Report Summary:</h6>
        <div class="row">
          <div class="col-md-3"><strong>Total Tasks:</strong> ${summary.total_tasks}</div>
          <div class="col-md-3"><strong>Total Amount:</strong> ${parseFloat(summary.total_amount).toLocaleString()} SAR</div>
          <div class="col-md-3"><strong>Driver Commissions:</strong> ${parseFloat(summary.total_driver_commission).toLocaleString()} SAR</div>
          <div class="col-md-3"><strong>Company Net:</strong> ${parseFloat(summary.total_company_commission).toLocaleString()} SAR</div>
        </div>
      </div>
    `;
  }

  initializeDataTable() {
    if ($.fn.DataTable.isDataTable('#previewDataTable')) {
      $('#previewDataTable').DataTable().destroy();
    }
    $('#previewDataTable').DataTable({ responsive: true, pageLength: 25 });
  }

  exportReport(type) {
    if (!this.validateForm() || this.selectedColumns.length < 4) {
      this.showError('Please select at least 4 columns');
      return;
    }
    this.showLoading('Generating Report');
    const formData = this.getFormData();
    formData.columns = this.selectedColumns;
    formData.export_type = type;

    const form = this.createExportForm(formData);
    if (type === 'pdf') form.attr('target', '_blank');
    form.submit();
    form.remove();
    setTimeout(() => this.hideLoading(), 2000);
  }

  createExportForm(formData) {
    const form = $('<form>', { method: 'POST', action: window.routes.generate });
    form.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
    Object.keys(formData).forEach(key => {
      if (Array.isArray(formData[key])) {
        formData[key].forEach(v => form.append($('<input>', { type: 'hidden', name: key + '[]', value: v })));
      } else {
        form.append($('<input>', { type: 'hidden', name: key, value: formData[key] }));
      }
    });
    $('body').append(form);
    return form;
  }

  getFormData() {
    return {
      driver_ids: $('#driver_ids').val() || [],
      date_from: $('#date_from').val(),
      date_to: $('#date_to').val(),
      task_statuses: $('#task_statuses').val() || [],
      payment_status: $('#payment_status').val(),
      payment_method: $('#payment_method').val(),
      customer_ids: $('#customer_ids').val() || [],
      team_ids: $('#team_ids').val() || [],
      created_by: $('#created_by').val()
    };
  }

  validateForm() {
    if (!$('#driver_ids').val()?.length) {
      this.showError('Please select at least one driver');
      return false;
    }
    if (!$('#date_from').val() || !$('#date_to').val()) {
      this.showError('Please specify the time period');
      return false;
    }
    return true;
  }

  resetFilters() {
    $('#reportForm')[0].reset();
    $('.filter-select').val(null).trigger('change');
    this.initializeDateRangePicker();
    $('.preview-section').hide();
    this.reportData = null;
    this.selectedColumns = ['task_id', 'total_price', 'driver_info'];
    this.initializeColumnSelector();
  }

  savePreferences() {
    localStorage.setItem('driverTasksReportColumns', JSON.stringify(this.selectedColumns));
  }
  loadSavedPreferences() {
    const saved = localStorage.getItem('driverTasksReportColumns');
    if (saved) {
      try {
        this.selectedColumns = JSON.parse(saved);
        this.initializeColumnSelector();
      } catch (e) {}
    }
  }

  showLoading(message = 'Processing Request') {
    $('#loadingModal .modal-body h6').text(message);
    $('#loadingModal').modal({ backdrop: 'static', keyboard: false }).modal('show');
  }

  hideLoading() {
    $('#loadingModal').modal('hide');
  }
  initializeLoadingModal() {}

  showError(message) {
    Swal.fire({ icon: 'error', title: 'Error', text: message });
  }
}

// Expose to window for global access
window.DriverTasksReport = DriverTasksReport;
