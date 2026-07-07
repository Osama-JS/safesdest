@extends('layouts/layoutMaster')

@section('title', __('Statistical Report'))

    @section('vendor-style')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
        @vite(['resources/assets/vendor/libs/select2/select2.scss'])
    @endsection

    @section('page-style')
        <style>
            /* Select2 Bootstrap 5 Compatibility */
            .select2-container--bootstrap-5 .select2-selection {
                border: 1px solid #d0d7de;
                border-radius: 0.375rem;
                min-height: calc(2.25rem + 2px);
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
                line-height: 1.5;
                background-color: #fff;
                transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            }

            .select2-container--bootstrap-5 .select2-selection:focus-within {
                border-color: #86b7fe;
                outline: 0;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            }

            .select2-container--bootstrap-5 .select2-selection--multiple {
                min-height: calc(2.25rem + 2px);
                padding: 0.125rem 0.75rem;
            }

            .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
                background-color: #0d6efd;
                border: 1px solid #0d6efd;
                border-radius: 0.25rem;
                color: #fff;
                font-size: 0.75rem;
                margin: 0.125rem 0.25rem 0.125rem 0;
                padding: 0.25rem 0.5rem;
            }

            .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
                color: #fff;
                margin-right: 0.25rem;
            }

            .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove:hover {
                color: #f8f9fa;
            }

            .select2-dropdown {
                border: 1px solid #d0d7de;
                border-radius: 0.375rem;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }

            .filter-card .card-header {
                background-color: transparent;
                border-bottom: 1px solid #ebeef4;
            }

            /* Sticky Columns Styles */
            .sticky-col-1-2 {
                position: sticky !important;
                left: 0;
                min-width: 300px;
                z-index: 3;
            }
            .sticky-col-1 {
                position: sticky !important;
                left: 0;
                min-width: 130px;
                max-width: 130px;
                z-index: 2;
                white-space: normal !important;
                word-wrap: break-word;
            }
            .sticky-col-2 {
                position: sticky !important;
                left: 130px;
                min-width: 170px;
                max-width: 170px;
                z-index: 2;
                background-color: #fff !important; 
                background-clip: padding-box;
                white-space: normal !important;
                word-wrap: break-word;
            }
            html.dark-style .sticky-col-2,
            .dark-style .sticky-col-2 {
                background-color: #2f3349 !important; /* Vuexy dark background */
            }
            .sticky-col-last {
                position: sticky !important;
                right: 0;
                min-width: 130px;
                z-index: 2;
            }
            #reportTable thead th {
                position: sticky;
                top: 0;
                z-index: 4;
            }
            #reportTable thead .sticky-col-1-2,
            #reportTable thead .sticky-col-last {
                z-index: 5;
            }
        </style>
    @endsection

    @section('content')
        <div class="card mb-4">
            <div class="card-header border-bottom">
                <h5 class="card-title mb-2">
                    <i class="tf-icons ti ti-chart-bar me-2 fs-3 text-white bg-primary rounded p-1"></i>
                    {{ __('Platform Reports') }} | {{ __('Statistical Report (Matrix)') }}
                </h5>
                <p>{{ __('Custom matrix-style report covering activity, profitability, and cash flow by day.') }}</p>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card filter-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-filter me-2"></i>
                            {{ __('Report Filters') }}
                        </h5>
                    </div>
                    <div class="card-body mt-4">
                        <form id="statisticalReportForm">
                            @csrf
                            <div class="row">
                                <!-- Customer Filter -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="customer_ids">{{ __('Select Customers') }}
                                        <small class="text-muted">({{ __('Leave empty to select all') }})</small>
                                    </label>
                                    <select id="customer_ids" name="customer_ids[]" class="form-select filter-select" multiple>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}
                                                {{ $customer->company_name ? '(' . $customer->company_name . ')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Date Range -->
                                <div class="col-md-4 mb-3">
                                    <label for="dateRange" class="form-label">{{ __('Date Range') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="dateRange" name="dateRange" required>
                                    <input type="hidden" id="date_from" name="date_from">
                                    <input type="hidden" id="date_to" name="date_to">
                                </div>

                                <!-- Options -->
                                <div class="col-md-12 mb-3 mt-2">
                                    <label class="form-label d-block">{{ __('خيارات العرض والاحتساب') }}</label>
                                    <div class="d-flex gap-4">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="show_currency"
                                                name="show_currency" value="1">
                                            <label class="form-check-label"
                                                for="show_currency">{{ __('إظهار كلمة SAR / ريال في المبالغ') }}</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="calc_net_commission"
                                                name="calc_net_commission" value="1" checked>
                                            <label class="form-check-label"
                                                for="calc_net_commission">{{ __('احتساب عمولة المنصة الصافية') }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div class="d-flex gap-2">
                                        <button type="button" id="previewBtn" class="btn btn-primary">
                                            <i class="ti ti-eye me-1"></i> عرض التقرير
                                        </button>
                                        <button type="button" id="exportBtn" class="btn btn-success">
                                            <i class="ti ti-file-spreadsheet me-1"></i> تصدير Excel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <!-- Report Results -->
        <div class="card d-none" id="reportResultsCard">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title m-0">نتائج التقرير</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive text-nowrap" style="overflow-x: auto; max-width: 100%;">
                    <table class="table table-bordered table-striped" id="reportTable">
                        <thead class="table-light">
                            <!-- Headers will be generated dynamically -->
                        </thead>
                        <tbody>
                            <!-- Body will be generated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @endsection

    @section('vendor-script')
        @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/select2/select2.js'])
    @endsection

    @section('page-script')
        <script>
            window.addEventListener('load', function () {
                $('.filter-select').each(function () {
                    const $this = $(this);
                    if (!$this.hasClass('select2-hidden-accessible')) {
                        $this.select2({
                            placeholder: $this.attr('multiple') ? 'Select one or more...' : 'Select...',
                            allowClear: true,
                            width: '100%'
                        });
                    }
                });

                $('#dateRange').daterangepicker({
                    opens: 'left',
                    autoUpdateInput: false,
                    locale: {
                        format: 'YYYY-MM-DD',
                        applyLabel: 'تطبيق',
                        cancelLabel: 'إلغاء',
                        customRangeLabel: 'فترة مخصصة'
                    },
                    ranges: {
                        'اليوم': [moment(), moment()],
                        'الأمس': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'آخر 7 أيام': [moment().subtract(6, 'days'), moment()],
                        'آخر 30 يوم': [moment().subtract(29, 'days'), moment()],
                        'هذا الشهر': [moment().startOf('month'), moment().endOf('month')],
                        'الشهر السابق': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    }
                });

                $('#dateRange').on('apply.daterangepicker', function (ev, picker) {
                    $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
                    $('#date_from').val(picker.startDate.format('YYYY-MM-DD'));
                    $('#date_to').val(picker.endDate.format('YYYY-MM-DD'));
                });

                $('#dateRange').on('cancel.daterangepicker', function (ev, picker) {
                    $(this).val('');
                    $('#date_from').val('');
                    $('#date_to').val('');
                });

                $('#previewBtn').click(function () {
                    let form = $('#statisticalReportForm');
                    if (!form[0].checkValidity()) {
                        form[0].reportValidity();
                        return;
                    }

                    let btn = $(this);
                    btn.prop('disabled', true).html('<i class="ti ti-loader ti-spin me-1"></i> جاري التحميل...');

                    $.ajax({
                        url: '{{ route("admin.reports.statistical.preview") }}',
                        type: 'POST',
                        data: form.serialize(),
                        success: function (response) {
                            btn.prop('disabled', false).html('<i class="ti ti-eye me-1"></i> عرض التقرير');
                            if (response.success) {
                                renderTable(response.data);
                                $('#reportResultsCard').removeClass('d-none');
                            } else {
                                alert('حدث خطأ: ' + response.message);
                            }
                        },
                        error: function () {
                            btn.prop('disabled', false).html('<i class="ti ti-eye me-1"></i> عرض التقرير');
                            alert('خطأ في الاتصال بالخادم');
                        }
                    });
                });

                $('#exportBtn').click(function () {
                    let form = $('#statisticalReportForm');
                    if (!form[0].checkValidity()) {
                        form[0].reportValidity();
                        return;
                    }

                    // Create a dynamic form to submit for file download
                    let formAction = '{{ route("admin.reports.statistical.generate") }}';
                    let oldAction = form.attr('action');
                    let oldMethod = form.attr('method');

                    form.attr('action', formAction);
                    form.attr('method', 'POST');
                    form.submit();

                    // Restore form
                    form.attr('action', oldAction || '');
                    form.attr('method', oldMethod || 'GET');
                });

                function formatCurrency(amount, showCurrency) {
                    amount = parseFloat(amount || 0).toFixed(2);
                    return showCurrency ? amount + ' SAR' : amount;
                }

                function renderTable(data) {
                    let thead = $('#reportTable thead');
                    let tbody = $('#reportTable tbody');
                    thead.empty();
                    tbody.empty();

                    let days = data.days;
                    let showCurrency = data.show_currency;
                    let calcNetCommission = data.calc_net_commission;

                    // Header Row
                    let headerRow = '<tr><th colspan="2" class="text-center bg-dark text-white sticky-col-1-2">المقاييس / الأيام</th>';
                    days.forEach(function (day) {
                        let parts = day.split('-');
                        let shortDate = parts[2] + '-' + parts[1]; // dd-mm
                        headerRow += '<th class="text-center">' + shortDate + '</th>';
                    });
                    headerRow += '<th class="text-center fw-bold bg-primary text-white sticky-col-last">Total</th></tr>';
                    thead.append(headerRow);

                    let act = data.activity;
                    let cash = data.cash;

                    // Totals calculation helper
                    const calcTotal = (arr) => Object.values(arr).reduce((a, b) => a + b, 0);

                    // Activity Section
                    let rowSpanActivity = calcNetCommission ? 7 : 6;

                    // Row 1: Number of Shipments
                    let tr1 = '<tr><td rowspan="' + rowSpanActivity + '" class="align-middle fw-bold text-center bg-label-primary sticky-col-1">النشاط والربحية</td>';
                    tr1 += '<td class="fw-bold sticky-col-2">Number of Shipments</td>';
                    days.forEach(day => { tr1 += '<td class="text-center">' + (act.shipments[day] || 0) + '</td>'; });
                    tr1 += '<td class="text-center fw-bold bg-label-primary sticky-col-last">' + calcTotal(act.shipments) + '</td></tr>';
                    tbody.append(tr1);

                    // Row 2: Active Customers
                    let tr2 = '<tr><td class="fw-bold sticky-col-2">Active Customer</td>';
                    days.forEach(day => { tr2 += '<td class="text-center">' + (act.active_customers[day] || 0) + '</td>'; });
                    tr2 += '<td class="text-center fw-bold bg-label-primary sticky-col-last">-</td></tr>'; // Total unique active customers across period is complex, skip or leave -
                    tbody.append(tr2);

                    // Row 3: Revenue
                    let tr3 = '<tr><td class="fw-bold sticky-col-2">Revenue</td>';
                    days.forEach(day => { tr3 += '<td class="text-center">' + formatCurrency(act.revenue[day], showCurrency) + '</td>'; });
                    tr3 += '<td class="text-center fw-bold bg-label-primary sticky-col-last">' + formatCurrency(calcTotal(act.revenue), showCurrency) + '</td></tr>';
                    tbody.append(tr3);

                    // Row 4: Carrier Cost
                    let tr4 = '<tr><td class="fw-bold sticky-col-2">Carrier Cost</td>';
                    days.forEach(day => { tr4 += '<td class="text-center">' + formatCurrency(act.carrier_cost[day], showCurrency) + '</td>'; });
                    tr4 += '<td class="text-center fw-bold bg-label-primary sticky-col-last">' + formatCurrency(calcTotal(act.carrier_cost), showCurrency) + '</td></tr>';
                    tbody.append(tr4);

                    // Row 5: Gross Margin
                    let tr5 = '<tr><td class="fw-bold sticky-col-2">Gross Margin</td>';
                    let totalGrossMargin = 0;
                    days.forEach(day => {
                        let margin = (act.revenue[day] || 0) - (act.carrier_cost[day] || 0);
                        totalGrossMargin += margin;
                        tr5 += '<td class="text-center">' + formatCurrency(margin, showCurrency) + '</td>';
                    });
                    tr5 += '<td class="text-center fw-bold bg-label-primary sticky-col-last">' + formatCurrency(totalGrossMargin, showCurrency) + '</td></tr>';
                    tbody.append(tr5);

                    // Row 6: Margin %
                    let tr6 = '<tr><td class="fw-bold sticky-col-2">Margin %</td>';
                    days.forEach(day => {
                        let margin = (act.revenue[day] || 0) - (act.carrier_cost[day] || 0);
                        let rev = act.revenue[day] || 0;
                        let perc = rev > 0 ? ((margin / rev) * 100).toFixed(2) + '%' : '0%';
                        tr6 += '<td class="text-center">' + perc + '</td>';
                    });
                    let totalRev = calcTotal(act.revenue);
                    let totalPerc = totalRev > 0 ? ((totalGrossMargin / totalRev) * 100).toFixed(2) + '%' : '0%';
                    tr6 += '<td class="text-center fw-bold bg-label-primary sticky-col-last">' + totalPerc + '</td></tr>';
                    tbody.append(tr6);

                    // Row 7 (Optional): Net Platform Commission
                    if (calcNetCommission) {
                        let tr7 = '<tr><td class="fw-bold text-success sticky-col-2">Net Platform Commission</td>';
                        days.forEach(day => { tr7 += '<td class="text-center text-success">' + formatCurrency(act.net_commission[day], showCurrency) + '</td>'; });
                        tr7 += '<td class="text-center fw-bold bg-label-success sticky-col-last">' + formatCurrency(calcTotal(act.net_commission), showCurrency) + '</td></tr>';
                        tbody.append(tr7);
                    }

                    // Cash Section
                    // Row 1: Cash Collected
                    let trC1 = '<tr><td rowspan="3" class="align-middle fw-bold text-center bg-label-info sticky-col-1">النقدية والتحصيل</td>';
                    trC1 += '<td class="fw-bold sticky-col-2">Cash Collected</td>';
                    days.forEach(day => { trC1 += '<td class="text-center">' + formatCurrency(cash.collected[day], showCurrency) + '</td>'; });
                    trC1 += '<td class="text-center fw-bold bg-label-info sticky-col-last">' + formatCurrency(calcTotal(cash.collected), showCurrency) + '</td></tr>';
                    tbody.append(trC1);

                    // Row 2: Paid to Carriers
                    let trC2 = '<tr><td class="fw-bold sticky-col-2">Paid to Carriers</td>';
                    days.forEach(day => { trC2 += '<td class="text-center">' + formatCurrency(cash.paid_to_carriers[day], showCurrency) + '</td>'; });
                    trC2 += '<td class="text-center fw-bold bg-label-info sticky-col-last">' + formatCurrency(calcTotal(cash.paid_to_carriers), showCurrency) + '</td></tr>';
                    tbody.append(trC2);

                    // Row 3: Cash Gap
                    let trC3 = '<tr><td class="fw-bold sticky-col-2">Cash Gap</td>';
                    let totalCashGap = 0;
                    days.forEach(day => {
                        let gap = (cash.collected[day] || 0) - (cash.paid_to_carriers[day] || 0);
                        totalCashGap += gap;
                        let colorClass = gap > 0 ? 'text-success' : (gap < 0 ? 'text-danger' : '');
                        trC3 += '<td class="text-center ' + colorClass + '">' + formatCurrency(gap, showCurrency) + '</td>';
                    });
                    let totalColorClass = totalCashGap > 0 ? 'bg-label-success text-success' : (totalCashGap < 0 ? 'bg-label-danger text-danger' : 'bg-label-info');
                    trC3 += '<td class="text-center fw-bold sticky-col-last ' + totalColorClass + '">' + formatCurrency(totalCashGap, showCurrency) + '</td></tr>';
                    tbody.append(trC3);

                }
            });
        </script>
    @endsection