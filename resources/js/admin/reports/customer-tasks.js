/**
 * Customer Tasks Report JavaScript
 * SafeDests Platform Reports
 */
console.log('hayyyyy');
class CustomerTasksReport {
  constructor() {
    this.availableColumns = {
      task_id: { name: 'Task ID', required: true },
      customer_name: { name: 'اسم العميل', required: false },
      total_price: { name: 'Task Price', required: true },
      driver_price: { name: 'سعر السائق', required: false },
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
      closed_at: { name: 'Closing Date', required: false },
      delivery_number: { name: 'رقم مذكرة التوصيل', required: false }
    };

    if (window.driverExtraColumns) {
      Object.assign(this.availableColumns, window.driverExtraColumns);
    }

    this.selectedColumns = ['task_id', 'total_price'];
    this.reportData = null;

    this.init();
  }

  init() {
    console.log('CustomerTasksReport: Starting initialization...');

    // Check if jQuery is available
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

    console.log('CustomerTasksReport: Initialization complete');
  }

  bindEvents() {
    $('#previewBtn').on('click', () => this.previewReport());
    $('#resetBtn').on('click', () => this.resetFilters());
    $('#exportExcelBtn').on('click', () => this.exportReport('excel'));
    $('#exportPdfBtn').on('click', () => this.exportReport('pdf'));

    console.log('CustomerTasksReport: Events bound');
  }

  initializeDateRangePicker() {
    console.log('CustomerTasksReport: Initializing DateRangePicker...');

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

    try {
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

      console.log('CustomerTasksReport: DateRangePicker initialized successfully');
    } catch (error) {
      console.error('Error initializing DateRangePicker:', error);
    }
  }

  initializeSelect2() {
    console.log('CustomerTasksReport: Initializing Select2...');

    // Check if Select2 is available
    if (typeof $.fn.select2 === 'undefined') {
      console.error('Select2 is not available');
      return;
    }

    try {
      $('.filter-select').each(function () {
        const $this = $(this);
        if (!$this.hasClass('select2-hidden-accessible')) {
          $this.select2({
            placeholder: $this.attr('multiple') ? 'Select one or more...' : 'Select...',
            allowClear: true,
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

      console.log('CustomerTasksReport: Select2 initialized for', $('.filter-select').length, 'elements');
    } catch (error) {
      console.error('Error initializing Select2:', error);
    }
  }

  initializeColumnSelector() {
    console.log('CustomerTasksReport: Initializing Column Selector...');

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
              ${column.name} ${isRequired ? '(Required)' : ''}
            </label>
          </div>
        </div>
      `);

      container.append(columnItem);
    });

    // Handle column selection
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
      const order = Object.keys(this.availableColumns);
      this.selectedColumns.sort((a, b) => order.indexOf(a) - order.indexOf(b));
      this.updateSelectedCount();
      this.savePreferences();
    });

    this.updateSelectedCount();
    console.log(
      'CustomerTasksReport: Column selector initialized with',
      Object.keys(this.availableColumns).length,
      'columns'
    );
  }

  updateSelectedCount() {
    $('#selectedCount').text(this.selectedColumns.length);

    // Enable/disable export buttons based on minimum requirement
    const canExport = this.selectedColumns.length >= 4;
    $('#exportExcelBtn, #exportPdfBtn').prop('disabled', !canExport || !this.reportData);
  }

  previewReport() {
    console.log('CustomerTasksReport: Preview report requested');

    if (!this.validateForm()) return;

    console.log('CustomerTasksReport: About to show loading modal');

    // Small delay to ensure DOM is ready
    setTimeout(() => {
      this.showLoading('Loading Report Data');

      const formData = this.getFormData();

      $.ajax({
        url: window.routes.preview,
        method: 'POST',
        data: { ...formData, columns: this.selectedColumns },
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: response => {
          console.log('CustomerTasksReport: Preview response received', response);
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
          console.error('CustomerTasksReport: Preview error', xhr);
          this.showError('Error occurred while fetching data');
        },
        complete: () => {
          this.hideLoading();
        }
      });
    }, 100);
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
        if (this.availableColumns[column]) {
          html += `<td>${this.formatCellValue(column, row)}</td>`;
        }
      });
      html += '</tr>';
    });

    html += `</tbody></table></div>`;
    return html;
  }

  formatCellValue(column, row) {
    const showCurrency = $('#show_currency').is(':checked');
    const briefData = $('#brief_data').is(':checked');

    switch (column) {
      case 'task_id':
        return row.id;
      case 'customer_name':
        return row.customer_name;
      case 'total_price':
        let priceText = parseFloat(row.total_price).toLocaleString();
        if (showCurrency) {
          priceText += ' SAR';
        }
        return priceText;
      case 'driver_price':
        let driverPriceText = parseFloat(row.driver_price).toLocaleString();
        if (showCurrency) {
          driverPriceText += ' SAR';
        }
        return driverPriceText;
      case 'pickup_info':
        if (briefData) {
          return row.pickup_address;
        }
        return `${row.pickup_address}<br><small>Contact: ${row.pickup_contact_name}<br>Phone: ${row.pickup_contact_phone}</small>`;
      case 'delivery_info':
        if (briefData) {
          return row.delivery_address;
        }
        return `${row.delivery_address}<br><small>Contact: ${row.delivery_contact_name}<br>Phone: ${row.delivery_contact_phone}</small>`;
      case 'vehicle_name':
        return row.vehicle_name;
      case 'driver_info':
        if (briefData) {
          return row.driver_name;
        }
        return `${row.driver_name}<br><small>Phone: ${row.driver_phone}<br>Team: ${row.team_name}</small>`;
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
        return row.completed_at_formatted || 'Not completed yet';
      case 'closed_at':
        return row.closed_at_formatted || 'Not closed yet';
      case 'delivery_number':
        return row.delivery_number || 'غير محدد';
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
          <div class="col-md-3"><strong>Average Price:</strong> ${parseFloat(summary.average_amount).toLocaleString()} SAR</div>
          <div class="col-md-3"><small class="text-muted">Showing first 50 records for preview</small></div>
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
        lengthMenu: 'Show _MENU_ entries',
        zeroRecords: 'No matching records found',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        infoEmpty: 'Showing 0 to 0 of 0 entries',
        infoFiltered: '(filtered from _MAX_ total entries)',
        search: 'Search:',
        paginate: {
          first: 'First',
          last: 'Last',
          next: 'Next',
          previous: 'Previous'
        }
      }
    });
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
    const form = $('<form>', {
      method: 'POST',
      action: window.routes.generate
    });

    form.append(
      $('<input>', {
        type: 'hidden',
        name: '_token',
        value: $('meta[name="csrf-token"]').attr('content')
      })
    );

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
      created_by: $('#created_by').val(),
      show_currency: $('#show_currency').is(':checked') ? 1 : 0,
      brief_data: $('#brief_data').is(':checked') ? 1 : 0
    };
  }

  validateForm() {
    const customerIds = $('#customer_ids').val();
    const dateFrom = $('#date_from').val();
    const dateTo = $('#date_to').val();

    if (!dateFrom || !dateTo) {
      this.showError('Please specify the time period');
      return false;
    }

    return true;
  }

  resetFilters() {
    $('#reportForm')[0].reset();
    $('.filter-select').val(null).trigger('change');

    if ($('#dateRange').data('daterangepicker')) {
      $('#dateRange').data('daterangepicker').setStartDate(moment().subtract(29, 'days'));
      $('#dateRange').data('daterangepicker').setEndDate(moment());
    }

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
        const order = Object.keys(this.availableColumns);
        this.selectedColumns = Array.from(new Set(['task_id', 'total_price', ...savedColumns]))
            .filter(col => this.availableColumns[col] !== undefined)
            .sort((a, b) => order.indexOf(a) - order.indexOf(b));
        this.initializeColumnSelector();
      } catch (e) {
        console.log('Error loading saved preferences');
      }
    }
  }

  showLoading(message = null) {
    console.log('CustomerTasksReport: showLoading called with message:', message);

    // Update modal text if message provided
    if (message) {
      $('#loadingModal .modal-body h6').text(message);
    }

    // Check if modal exists
    if ($('#loadingModal').length === 0) {
      console.error('CustomerTasksReport: Loading modal not found in DOM');
      this.showSimpleLoading();
      return;
    }

    // Simple jQuery modal show
    try {
      $('#loadingModal')
        .modal({
          backdrop: 'static',
          keyboard: false
        })
        .modal('show');
      console.log('CustomerTasksReport: Loading modal shown successfully');
    } catch (error) {
      console.error('CustomerTasksReport: Error showing modal:', error);
      this.showSimpleLoading();
    }
  }

  hideLoading() {
    console.log('CustomerTasksReport: hideLoading called');

    try {
      // Simple jQuery modal hide
      $('#loadingModal').modal('hide');
      // Reset to default text
      $('#loadingModal .modal-body h6').text('Processing Request');
      console.log('CustomerTasksReport: Loading modal hidden successfully');
    } catch (error) {
      console.error('CustomerTasksReport: Error hiding modal:', error);
      this.hideSimpleLoading();
    }
  }

  showSimpleLoading() {
    // Create simple loading overlay if modal doesn't work
    if ($('#simpleLoadingOverlay').length === 0) {
      $('body').append(`
        <div id="simpleLoadingOverlay" style="
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(0,0,0,0.5);
          display: flex;
          justify-content: center;
          align-items: center;
          z-index: 9999;
        ">
          <div style="
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
          ">
            <div class="spinner-border text-primary mb-3"></div>
            <h6>Processing Request</h6>
            <p class="text-muted small">Please wait...</p>
          </div>
        </div>
      `);
    }
    $('#simpleLoadingOverlay').show();
  }

  hideSimpleLoading() {
    $('#simpleLoadingOverlay').hide();
  }

  initializeLoadingModal() {
    console.log('CustomerTasksReport: Initializing Loading Modal...');

    // Check if modal exists in DOM
    const modalExists = $('#loadingModal').length > 0;
    console.log('CustomerTasksReport: Modal exists in DOM:', modalExists);

    if (!modalExists) {
      console.warn('CustomerTasksReport: Loading modal not found in DOM');
      return;
    }

    // Check if Bootstrap is available
    const bootstrapAvailable = typeof $.fn.modal !== 'undefined';
    console.log('CustomerTasksReport: Bootstrap modal available:', bootstrapAvailable);

    if (!bootstrapAvailable) {
      console.warn('CustomerTasksReport: Bootstrap modal not available');
      return;
    }

    console.log('CustomerTasksReport: Loading modal initialized successfully');
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

// Class will be initialized from the Blade template
window.CustomerTasksReport = CustomerTasksReport;
