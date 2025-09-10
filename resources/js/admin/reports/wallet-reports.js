/**
 * Wallet Reports JavaScript
 * Handles wallet report generation with dynamic filters
 */

'use strict';

class WalletReports {
  constructor() {
    this.form = $('#walletReportForm');
    this.walletTypeSelect = $('#walletType');
    this.ownerSelect = $('#ownerId');
    this.dateRangeInput = $('#dateRange');
    this.previewBtn = $('#previewReportBtn');
    this.generateBtn = $('#generatePdfBtn');
    this.clearBtn = $('#clearFiltersBtn');
    this.loadingModal = $('#loadingModal');
    this.previewSection = $('#previewSection');

    this.walletData = null;

    this.init();
  }

  init() {
    console.log('WalletReports: Initializing...');

    this.initializeDateRangePicker();
    this.initializeSelect2();
    this.bindEvents();

    console.log('WalletReports: Initialization complete');
  }

  initializeDateRangePicker() {
    if (this.dateRangeInput.length) {
      this.dateRangeInput.daterangepicker({
        opens: 'left',
        locale: {
          format: 'DD/MM/YYYY',
          separator: ' - ',
          applyLabel: 'تطبيق',
          cancelLabel: 'إلغاء',
          fromLabel: 'من',
          toLabel: 'إلى',
          customRangeLabel: 'فترة مخصصة',
          weekLabel: 'أ',
          daysOfWeek: ['أح', 'إث', 'ث', 'أر', 'خ', 'ج', 'س'],
          monthNames: [
            'يناير',
            'فبراير',
            'مارس',
            'أبريل',
            'مايو',
            'يونيو',
            'يوليو',
            'أغسطس',
            'سبتمبر',
            'أكتوبر',
            'نوفمبر',
            'ديسمبر'
          ],
          firstDay: 0
        },
        ranges: {
          اليوم: [moment(), moment()],
          أمس: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'آخر 7 أيام': [moment().subtract(6, 'days'), moment()],
          'آخر 30 يوم': [moment().subtract(29, 'days'), moment()],
          'هذا الشهر': [moment().startOf('month'), moment().endOf('month')],
          'الشهر الماضي': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        startDate: moment().subtract(29, 'days'),
        endDate: moment()
      });

      this.dateRangeInput.on('apply.daterangepicker', (ev, picker) => {
        $('#fromDate').val(picker.startDate.format('YYYY-MM-DD'));
        $('#toDate').val(picker.endDate.format('YYYY-MM-DD'));
      });

      // Set initial values
      $('#fromDate').val(moment().subtract(29, 'days').format('YYYY-MM-DD'));
      $('#toDate').val(moment().format('YYYY-MM-DD'));
    }
  }

  initializeSelect2() {
    // Initialize wallet type select
    if (this.walletTypeSelect.length) {
      this.walletTypeSelect.select2({
        placeholder: 'اختر نوع المحفظة',
        allowClear: false
      });
    }

    // Initialize owner select
    if (this.ownerSelect.length) {
      this.ownerSelect.select2({
        placeholder: 'اختر المالك',
        allowClear: false
      });
    }

    // Initialize other selects
    $('#transactionType, #status').select2({
      allowClear: true
    });
  }

  bindEvents() {
    // Wallet type change event
    this.walletTypeSelect.on('change', e => {
      this.handleWalletTypeChange(e.target.value);
    });

    // Preview button
    this.previewBtn.on('click', () => {
      this.previewWalletData();
    });

    // Generate PDF button
    this.generateBtn.on('click', () => {
      this.generateReport();
    });

    // Clear filters button
    this.clearBtn.on('click', () => {
      this.clearAllFilters();
    });
  }

  handleWalletTypeChange(walletType) {
    console.log('WalletReports: Wallet type changed to:', walletType);

    // Reset owner select
    this.ownerSelect.empty().append('<option value="">اختر المالك</option>');

    if (!walletType) {
      this.ownerSelect.prop('disabled', true);
      $('#statusFilterContainer').show();
      return;
    }

    // Show/hide status filter based on wallet type
    if (walletType === 'team') {
      $('#statusFilterContainer').hide();
    } else {
      $('#statusFilterContainer').show();
    }

    // Enable owner select and load owners
    this.ownerSelect.prop('disabled', false);
    this.loadOwners(walletType);
  }

  loadOwners(walletType) {
    console.log('WalletReports: Loading owners for type:', walletType);

    $.ajax({
      url: walletReportRoutes.getOwners,
      method: 'POST',
      data: {
        type: walletType,
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: response => {
        if (response.status === 1) {
          this.populateOwnerSelect(response.data);
        } else {
          this.showError(response.error || 'خطأ في تحميل البيانات');
        }
      },
      error: xhr => {
        console.error('WalletReports: Error loading owners:', xhr);
        this.showError('خطأ في الاتصال بالخادم');
      }
    });
  }

  populateOwnerSelect(owners) {
    this.ownerSelect.empty().append('<option value="">اختر المالك</option>');

    owners.forEach(owner => {
      this.ownerSelect.append(`<option value="${owner.id}">${owner.name}</option>`);
    });

    console.log('WalletReports: Loaded', owners.length, 'owners');
  }

  previewWalletData() {
    if (!this.validateForm()) {
      return;
    }

    console.log('WalletReports: Previewing wallet data...');

    this.showLoading();

    const formData = this.getFormData();

    $.ajax({
      url: walletReportRoutes.preview,
      method: 'POST',
      data: formData,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: response => {
        console.log('WalletReports: Preview response received', response);
        if (response.success) {
          this.walletData = response.data;
          this.displayPreview(response.data);
          this.previewSection.show();
        } else {
          this.showError(response.message);
        }
      },
      error: xhr => {
        console.error('WalletReports: Preview error', xhr);
        this.showError('خطأ في جلب بيانات المحفظة');
      },
      complete: () => {
        this.hideLoading();
      }
    });
  }

  displayPreview(data) {
    // Display wallet summary
    this.displayWalletSummary(data.wallet_data, data.summary);

    // Display transactions table
    this.displayTransactionsTable(data.transactions);
  }

  displayWalletSummary(walletData, summary) {
    const ownerInfo = walletData.owner;
    const walletType = walletData.type;

    let ownerTypeAr = '';
    switch (walletType) {
      case 'customer':
        ownerTypeAr = 'عميل';
        break;
      case 'driver':
        ownerTypeAr = 'سائق';
        break;
      case 'team':
        ownerTypeAr = 'فريق';
        break;
    }

    const summaryHtml = `
      <div class="row">
        <div class="col-md-6">
          <div class="card bg-light">
            <div class="card-body">
              <h6 class="card-title">معلومات المحفظة</h6>
              <p><strong>المالك:</strong> ${ownerInfo.name}</p>
              <p><strong>النوع:</strong> ${ownerTypeAr}</p>
              <p><strong>رقم المحفظة:</strong> #${walletData.wallet.id}</p>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="row">
            <div class="col-6">
              <div class="card text-center bg-success text-white">
                <div class="card-body">
                  <h5>${parseFloat(summary.total_credit).toLocaleString()} ريال</h5>
                  <small>إجمالي الإيداعات</small>
                </div>
              </div>
            </div>
            <div class="col-6">
              <div class="card text-center bg-danger text-white">
                <div class="card-body">
                  <h5>${parseFloat(summary.total_debit).toLocaleString()} ريال</h5>
                  <small>إجمالي السحوبات</small>
                </div>
              </div>
            </div>
            <div class="col-6 mt-2">
              <div class="card text-center bg-primary text-white">
                <div class="card-body">
                  <h5>${parseFloat(summary.current_balance).toLocaleString()} ريال</h5>
                  <small>الرصيد الحالي</small>
                </div>
              </div>
            </div>
            <div class="col-6 mt-2">
              <div class="card text-center bg-info text-white">
                <div class="card-body">
                  <h5>${summary.total_transactions}</h5>
                  <small>عدد المعاملات</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;

    $('#walletSummary').html(summaryHtml);
  }

  displayTransactionsTable(transactions) {
    if (transactions.length === 0) {
      $('#previewTable').html(
        '<div class="text-center py-4"><p class="text-muted">لا توجد معاملات في الفترة المحددة</p></div>'
      );
      return;
    }

    let tableHtml = this.buildTransactionsTable(transactions);
    $('#previewTable').html(tableHtml);
  }

  buildTransactionsTable(transactions) {
    let html = `
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="table-dark">
            <tr>
              <th>رقم المعاملة</th>
              <th>الرصيد بعد المعاملة</th>
              <th>المبلغ</th>
              <th>النوع</th>
              <th>الوصف</th>
              <th>التاريخ</th>
              <th>رقم المهمة</th>
            </tr>
          </thead>
          <tbody>
    `;

    // عرض أول 50 معاملة فقط للمعاينة
    const previewTransactions = transactions.slice(0, 50);

    previewTransactions.forEach(transaction => {
      const amountClass = transaction.type === 'credit' ? 'text-success' : 'text-danger';
      const amountSign = transaction.type === 'credit' ? '+' : '-';

      html += `
        <tr>
          <td><strong>#${transaction.id}</strong></td>
          <td class="text-primary"><strong>${transaction.balance_after || '0.00'} ريال</strong></td>
          <td class="${amountClass}"><strong>${amountSign}${transaction.amount} ريال</strong></td>

          <td><span class="badge bg-${transaction.type === 'credit' ? 'success' : 'danger'}">${transaction.type_ar}</span></td>
          <td>${transaction.description}</td>
          <td>${transaction.date}</td>

          <td>${transaction.task_id || '-'}</td>
        </tr>
      `;
    });

    html += `</tbody></table>`;

    // إضافة ملاحظة إذا كان هناك معاملات أكثر من 50
    if (transactions.length > 50) {
      html += `
        <div class="alert alert-info mt-3">
          <i class="ti ti-info-circle me-2"></i>
          يتم عرض أول 50 معاملة فقط للمعاينة. إجمالي المعاملات: <strong>${transactions.length}</strong>
          <br>سيتم تضمين جميع المعاملات في تقرير PDF.
        </div>
      `;
    }

    html += `</div>`;
    return html;
  }

  getFormData() {
    return {
      wallet_type: this.walletTypeSelect.val(),
      owner_id: this.ownerSelect.val(),
      from_date: $('#fromDate').val(),
      to_date: $('#toDate').val(),
      transaction_type: $('#transactionType').val(),
      status: $('#status').val()
    };
  }

  generateReport() {
    if (!this.walletData) {
      this.showError('يرجى معاينة البيانات أولاً');
      return;
    }

    console.log('WalletReports: Generating PDF report...');

    this.showLoading();

    const formData = this.getFormData();

    // Create a form and submit it to open PDF in new tab
    const form = $('<form>', {
      method: 'POST',
      action: walletReportRoutes.generate,
      target: '_blank'
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
      if (formData[key]) {
        form.append(
          $('<input>', {
            type: 'hidden',
            name: key,
            value: formData[key]
          })
        );
      }
    });

    // Submit form
    $('body').append(form);
    form.submit();
    form.remove();

    // Hide loading after a delay
    setTimeout(() => {
      this.hideLoading();
    }, 2000);
  }

  validateForm() {
    const walletType = this.walletTypeSelect.val();
    const ownerId = this.ownerSelect.val();
    const fromDate = $('#fromDate').val();
    const toDate = $('#toDate').val();

    if (!walletType) {
      this.showError('يرجى اختيار نوع المحفظة');
      return false;
    }

    if (!ownerId) {
      this.showError('يرجى اختيار المالك');
      return false;
    }

    if (!fromDate || !toDate) {
      this.showError('يرجى اختيار الفترة الزمنية');
      return false;
    }

    return true;
  }

  clearAllFilters() {
    console.log('WalletReports: Clearing all filters');

    this.walletTypeSelect.val('').trigger('change');
    this.ownerSelect.empty().append('<option value="">اختر المالك</option>').prop('disabled', true);
    $('#transactionType').val('').trigger('change');
    $('#status').val('').trigger('change');

    // Reset date range to last 30 days
    this.dateRangeInput.data('daterangepicker').setStartDate(moment().subtract(29, 'days'));
    this.dateRangeInput.data('daterangepicker').setEndDate(moment());
    $('#fromDate').val(moment().subtract(29, 'days').format('YYYY-MM-DD'));
    $('#toDate').val(moment().format('YYYY-MM-DD'));

    // Hide preview section
    this.previewSection.hide();
    this.walletData = null;

    this.showSuccess('تم مسح جميع الفلاتر');
  }

  showLoading() {
    this.previewBtn.prop('disabled', true).html('<i class="ti ti-loader me-2"></i>جاري التحميل...');
    this.generateBtn.prop('disabled', true);
    this.loadingModal.modal('show');
  }

  hideLoading() {
    this.previewBtn.prop('disabled', false).html('<i class="ti ti-eye me-2"></i>معاينة البيانات');
    this.generateBtn.prop('disabled', false);
    this.loadingModal.modal('hide');
  }

  showError(message) {
    Swal.fire({
      title: 'خطأ!',
      text: message,
      icon: 'error',
      confirmButtonText: 'موافق'
    });
  }

  showSuccess(message) {
    Swal.fire({
      title: 'نجح!',
      text: message,
      icon: 'success',
      confirmButtonText: 'موافق',
      timer: 2000
    });
  }

  showInfo(message) {
    Swal.fire({
      title: 'معلومات',
      text: message,
      icon: 'info',
      confirmButtonText: 'موافق'
    });
  }
}

// Initialize when document is ready
$(document).ready(() => {
  new WalletReports();
});
