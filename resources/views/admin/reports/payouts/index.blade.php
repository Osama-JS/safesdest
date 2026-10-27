@extends('layouts/layoutMaster')

@section('title', __('تقرير المدفوعات عبر Payout'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
    ])
@endsection

@section('page-style')
    @vite(['resources/css/app.css'])
    <style>
        .select2-container .select2-selection {
            border: 1px solid #dbdade !important;
            border-radius: 0.375rem !important;
            min-height: 42px !important;
            font-size: 0.9rem !important;
            background-color: #fff !important;
        }

        .select2-container--open .select2-selection,
        .select2-container .select2-selection:focus {
            border-color: #7367f0 !important;
            box-shadow: 0 0 0 0.2rem rgba(115, 103, 240, 0.15) !important;
        }

        .select2-container .select2-selection--single {
            height: 42px !important;
            display: flex !important;
            align-items: center !important;
            padding: 0 0.85rem !important;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            padding: 0 !important;
            color: #4b465c !important;
        }

        [dir="rtl"] .select2-container .select2-selection--single .select2-selection__arrow {
            left: 10px !important;
            right: auto !important;
            height: 100% !important;
            top: 0 !important;
        }

        /* KPI Cards */
        .kpi-card {
            border: none;
            border-radius: 0.75rem;
            transition: all 0.25s ease;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .preset-badge {
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }
        .preset-badge:hover {
            transform: scale(1.05);
        }
        .preset-badge.active {
            box-shadow: 0 2px 6px rgba(115, 103, 240, 0.4);
        }

        .iban-code {
            font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            letter-spacing: 0.5px;
            font-size: 0.85rem;
        }

        .copy-btn {
            padding: 2px 6px;
            font-size: 0.75rem;
            cursor: pointer;
        }
    </style>
@endsection

@section('content')
    <!-- Breadcrumb & Header -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between py-3">
            <div class="d-flex align-items-center mb-2 mb-md-0">
                <div class="avatar avatar-md me-3 bg-label-primary rounded p-2">
                    <i class="ti ti-credit-card fs-2"></i>
                </div>
                <div>
                    <h5 class="card-title mb-1 text-primary fw-bold">{{ __('تقرير المدفوعات والتحويلات عبر Payout') }}</h5>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-style1 mb-0 py-0">
                            <li class="breadcrumb-item">
                                <a href="{{ url('admin') }}">{{ __('Dashboard') }}</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('admin.reports.index') }}">{{ __('Platform Reports') }}</a>
                            </li>
                            <li class="breadcrumb-item active text-muted">{{ __('تقرير مدفوعات Payout') }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btnColumnsModal" data-bs-toggle="modal" data-bs-target="#columnsExportModal">
                    <i class="ti ti-columns me-1"></i> {{ __('تخصيص الأعمدة') }}
                </button>
                <a href="{{ route('admin.reports.index') }}" class="btn btn-label-secondary">
                    <i class="ti ti-arrow-left me-1"></i> {{ __('Back to Reports') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Form Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0 d-flex align-items-center fw-bold">
                <i class="ti ti-filter me-2 text-primary"></i>
                {{ __('فلاتر ومعايير التقرير') }}
            </h6>
            <!-- Quick Date Presets -->
            <div class="d-flex gap-1 flex-wrap">
                <span class="badge bg-label-primary preset-badge" data-range="today">{{ __('اليوم') }}</span>
                <span class="badge bg-label-info preset-badge" data-range="week">{{ __('هذا الأسبوع') }}</span>
                <span class="badge bg-label-success preset-badge" data-range="month">{{ __('هذا الشهر') }}</span>
                <span class="badge bg-label-warning preset-badge" data-range="year">{{ __('هذا العام') }}</span>
                <span class="badge bg-label-secondary preset-badge" data-range="clear">{{ __('مسح') }}</span>
            </div>
        </div>
        <div class="card-body pt-4">
            <form id="payoutReportFilterForm">
                @csrf
                <div class="row g-3">
                    <!-- Date From -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="date_from">{{ __('From Date') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-calendar"></i></span>
                            <input type="text" class="form-control flatpickr-date" id="date_from" name="date_from" placeholder="YYYY-MM-DD" autocomplete="off" />
                        </div>
                    </div>

                    <!-- Date To -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="date_to">{{ __('To Date') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-calendar"></i></span>
                            <input type="text" class="form-control flatpickr-date" id="date_to" name="date_to" placeholder="YYYY-MM-DD" autocomplete="off" />
                        </div>
                    </div>

                    <!-- Status -->
                    <!-- Status Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="status">{{ __('حالة عملية الـ Payout') }}</label>
                        <select class="form-select select2" id="status" name="status">
                            <option value="all">{{ __('جميع الحالات') }}</option>
                            <option value="completed">{{ __('ناجحة ومكتملة (Completed)') }}</option>
                            <option value="failed">{{ __('فاشلة (Failed)') }}</option>
                            <option value="rejected">{{ __('مرفوضة من الإدارة (Rejected)') }}</option>
                            <option value="failed_or_rejected">{{ __('الفاشلة والمرفوضة معاً') }}</option>
                            <option value="pending_approval">{{ __('بانتظار الموافقة (Pending Approval)') }}</option>
                            <option value="processing">{{ __('قيد المعالجة (Processing/Pending)') }}</option>
                        </select>
                    </div>

                    <!-- Payout Section / Entity Type -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="entity_type">{{ __('قسم الـ Payout (الجهة)') }}</label>
                        <select class="form-select select2" id="entity_type" name="entity_type">
                            <option value="all">{{ __('جميع أقسام الـ Payout') }}</option>
                            <option value="driver">{{ __('Payout السائقين (Driver Payout)') }}</option>
                            <option value="team">{{ __('Payout الفرق (Team Payout)') }}</option>
                            <option value="investor">{{ __('Payout المستثمرين (Investor Payout)') }}</option>
                            <option value="user">{{ __('Payout المستخدمين والموظفين (User Payout)') }}</option>
                        </select>
                    </div>

                    <!-- Specific Beneficiary -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="entity_id">{{ __('المستفيد المحدد') }}</label>
                        <select class="form-select select2" id="entity_id" name="entity_id">
                            <option value="">{{ __('جميع المستفيدين') }}</option>
                            <optgroup label="{{ __('السائقين') }}" id="optgroup-drivers">
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}" data-type="driver">{{ $driver->name }} ({{ $driver->phone }})</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="{{ __('الفرق') }}" id="optgroup-teams">
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}" data-type="team">{{ $team->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="{{ __('المستخدمين والمستثمرين') }}" id="optgroup-users">
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" data-type="user">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>

                    <!-- Bank / BIC -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" for="bic_code">{{ __('البنك المحول إليه عبر Payout') }}</label>
                        <select class="form-select select2" id="bic_code" name="bic_code">
                            <option value="all">{{ __('جميع البنوك') }}</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->code }}">{{ $bank->name }} ({{ $bank->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Amount Range -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">{{ __('نطاق المبلغ (SAR)') }}</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control" name="min_amount" id="min_amount" placeholder="{{ __('الحد الأدنى') }}" />
                            <span class="input-group-text">-</span>
                            <input type="number" step="0.01" class="form-control" name="max_amount" id="max_amount" placeholder="{{ __('الحد الأعلى') }}" />
                        </div>
                    </div>

                    <!-- General Search Query -->
                    <div class="col-md-9">
                        <label class="form-label fw-semibold" for="search_query">{{ __('بحث بالمرجع / معرّف HyperPay Payout ID / الآيبان / الاسم') }}</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" id="search_query" name="search_query" placeholder="{{ __('أدخل رقم مرجع Payout، أو رقم المعاملة، أو الآيبان، أو اسم المستفيد...') }}" />
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-md-6 d-flex align-items-end justify-content-end gap-2">
                        <button type="button" class="btn btn-primary" id="btnPreview">
                            <i class="ti ti-eye me-1"></i> {{ __('معاينة التقرير') }}
                        </button>
                        <button type="button" class="btn btn-success" id="btnExportExcel">
                            <i class="ti ti-file-spreadsheet me-1"></i> {{ __('تصدير Excel') }}
                        </button>
                        <button type="button" class="btn btn-secondary" id="btnExportPdf">
                            <i class="ti ti-file-text me-1"></i> {{ __('تصدير / طباعة PDF') }}
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="btnResetFilters" title="{{ __('إعادة ضبط') }}">
                            <i class="ti ti-refresh"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4" id="kpiContainer" style="display: none;">
        <!-- Total Transactions -->
        <div class="col-xl-3 col-sm-6">
            <div class="card kpi-card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-label-primary me-3">
                        <i class="ti ti-receipt text-primary"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold d-block">{{ __('إجمالي المبالغ والعمليات') }}</span>
                        <h4 class="mb-0 fw-bold text-primary" id="kpiTotalAmount">0.00 SAR</h4>
                        <small class="text-muted" id="kpiTotalCount">0 عملية</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed Transactions -->
        <div class="col-xl-3 col-sm-6">
            <div class="card kpi-card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-label-success me-3">
                        <i class="ti ti-circle-check text-success"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold d-block">{{ __('العمليات الناجحة') }}</span>
                        <h4 class="mb-0 fw-bold text-success" id="kpiCompletedAmount">0.00 SAR</h4>
                        <small class="text-muted" id="kpiCompletedCount">0 عملية ناجحة</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Failed & Rejected Transactions -->
        <div class="col-xl-3 col-sm-6">
            <div class="card kpi-card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-label-danger me-3">
                        <i class="ti ti-circle-x text-danger"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold d-block">{{ __('العمليات الفاشلة والمرفوضة') }}</span>
                        <h4 class="mb-0 fw-bold text-danger" id="kpiFailedAmount">0.00 SAR</h4>
                        <small class="text-muted" id="kpiFailedCount">0 عملية فاشلة/مرفوضة</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending / Processing Transactions -->
        <div class="col-xl-3 col-sm-6">
            <div class="card kpi-card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="kpi-icon bg-label-warning me-3">
                        <i class="ti ti-clock-hour-4 text-warning"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold d-block">{{ __('قيد المعالجة والانتظار') }}</span>
                        <h4 class="mb-0 fw-bold text-warning" id="kpiPendingAmount">0.00 SAR</h4>
                        <small class="text-muted" id="kpiPendingCount">0 عملية قيد التنفيذ</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Table Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0 d-flex align-items-center fw-bold">
                <i class="ti ti-table me-2 text-primary"></i>
                {{ __('معاينة سجلات المدفوعات') }}
                <span class="badge bg-label-primary ms-2" id="badgeRecordsCount">0</span>
            </h6>
            <div class="text-muted small">
                {{ __('يتم عرض حتى 250 سجل في المعاينة المباشرة (التصدير يشمل كافة السجلات)') }}
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover table-striped align-middle" id="payoutsTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>{{ __('مرجع Payout / المعرف') }}</th>
                        <th>{{ __('قسم الـ Payout') }}</th>
                        <th>{{ __('المستفيد البنكي') }}</th>
                        <th>{{ __('الآيبان والبنك') }}</th>
                        <th>{{ __('المبلغ (SAR)') }}</th>
                        <th>{{ __('حالة Payout والسبب') }}</th>
                        <th>{{ __('تاريخ التحويل') }}</th>
                        <th class="text-center" style="width: 80px;">{{ __('إجراءات') }}</th>
                    </tr>
                </thead>
                <tbody id="payoutsTableBody">
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="ti ti-filter fs-1 d-block mb-2 text-primary opacity-50"></i>
                            <h6 class="text-muted fw-semibold mb-1">{{ __('لا توجد بيانات معروضة حالياً') }}</h6>
                            <p class="small text-muted mb-0">{{ __('يرجى تحديد الفلاتر المطلوبة ثم الضغط على "معاينة التقرير" لعرض البيانات') }}</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Columns Export Customization -->
    <div class="modal fade" id="columnsExportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">
                        <i class="ti ti-columns me-1 text-primary"></i>
                        {{ __('تخصيص أعمدة التصدير (Excel & PDF)') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        {{ __('حدد الأعمدة التي ترغب في إظهارها في ملف التصدير:') }}
                    </p>
                    <div class="row g-2" id="columnsCheckboxContainer">
                        @php
                            $availableColumns = [
                                'id'               => __('المعرف (#)'),
                                'reference_id'     => __('المرجع (Reference ID)'),
                                'payout_id'        => __('رقم العملية (Payout ID)'),
                                'payout_type'      => __('نوع الدفعة'),
                                'entity_type'      => __('نوع المستفيد (سائق/فريق/مستخدم)'),
                                'entity_name'      => __('اسم المستفيد بالنظام'),
                                'beneficiary_name' => __('اسم المستفيد البنكي'),
                                'iban'             => __('رقم الآيبان (IBAN)'),
                                'bank_name'        => __('البنك / السويفت'),
                                'amount'           => __('المبلغ'),
                                'status'           => __('الحالة'),
                                'reason'           => __('سبب الفشل / الرفض'),
                                'created_at'       => __('تاريخ التحويل'),
                                'approver_name'    => __('المعتمد'),
                            ];
                        @endphp
                        @foreach($availableColumns as $colKey => $colLabel)
                            <div class="col-6">
                                <div class="form-check">
                                    <input class="form-check-input column-toggle-checkbox" type="checkbox" value="{{ $colKey }}" id="col_{{ $colKey }}" checked>
                                    <label class="form-check-label" for="col_{{ $colKey }}">
                                        {{ $colLabel }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-top d-flex justify-content-between">
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAllCols">{{ __('تحديد الكل') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAllCols">{{ __('إلغاء التحديد') }}</button>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">{{ __('حفظ وإغلاق') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Export Form for PDF / Excel POST Request -->
    <form id="exportForm" action="{{ route('admin.reports.payouts.generate') }}" method="POST" target="_blank" style="display: none;">
        @csrf
        <input type="hidden" name="export_type" id="export_type" value="excel" />
        <input type="hidden" name="date_from" id="exp_date_from" />
        <input type="hidden" name="date_to" id="exp_date_to" />
        <input type="hidden" name="status" id="exp_status" />
        <input type="hidden" name="payout_type" id="exp_payout_type" />
        <input type="hidden" name="entity_type" id="exp_entity_type" />
        <input type="hidden" name="entity_id" id="exp_entity_id" />
        <input type="hidden" name="bic_code" id="exp_bic_code" />
        <input type="hidden" name="min_amount" id="exp_min_amount" />
        <input type="hidden" name="max_amount" id="exp_max_amount" />
        <input type="hidden" name="search_query" id="exp_search_query" />
        <div id="hiddenColumnsInputs"></div>
    </form>
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js'
    ])
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Init Flatpickr
        const fpFrom = flatpickr('#date_from', {
            dateFormat: 'Y-m-d',
            allowInput: true
        });
        const fpTo = flatpickr('#date_to', {
            dateFormat: 'Y-m-d',
            allowInput: true
        });

        // Init Select2
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                width: '100%',
                dropdownParent: $('body')
            });
        }

        // Quick Date Presets
        $('.preset-badge').on('click', function () {
            $('.preset-badge').removeClass('active');
            $(this).addClass('active');

            const range = $(this).data('range');
            const now = new Date();
            let from = '';
            let to = '';

            const formatDate = (d) => {
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const day = String(d.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            if (range === 'today') {
                from = formatDate(now);
                to = formatDate(now);
            } else if (range === 'week') {
                const dayOfWeek = now.getDay(); // 0 is Sunday
                const firstDay = new Date(now);
                firstDay.setDate(now.getDate() - dayOfWeek);
                from = formatDate(firstDay);
                to = formatDate(now);
            } else if (range === 'month') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                from = formatDate(firstDay);
                to = formatDate(now);
            } else if (range === 'year') {
                const firstDay = new Date(now.getFullYear(), 0, 1);
                from = formatDate(firstDay);
                to = formatDate(now);
            } else if (range === 'clear') {
                fpFrom.clear();
                fpTo.clear();
                return;
            }

            fpFrom.setDate(from);
            fpTo.setDate(to);
        });

        // Dynamic Entity Type Filter Changing Entity Dropdown
        $('#entity_type').on('change', function () {
            const selectedType = $(this).val();
            const $entityId = $('#entity_id');

            $entityId.val('').trigger('change');

            if (selectedType === 'all') {
                $('#optgroup-drivers, #optgroup-teams, #optgroup-users').show();
            } else if (selectedType === 'driver') {
                $('#optgroup-drivers').show();
                $('#optgroup-teams, #optgroup-users').hide();
            } else if (selectedType === 'team') {
                $('#optgroup-teams').show();
                $('#optgroup-drivers, #optgroup-users').hide();
            } else if (selectedType === 'user' || selectedType === 'investor') {
                $('#optgroup-users').show();
                $('#optgroup-drivers, #optgroup-teams').hide();
            }
        });

        // Column selection helpers
        $('#btnSelectAllCols').on('click', function () {
            $('.column-toggle-checkbox').prop('checked', true);
        });
        $('#btnDeselectAllCols').on('click', function () {
            $('.column-toggle-checkbox').prop('checked', false);
        });

        // DataTable Instance
        let dataTableInstance = null;

        function initDataTable() {
            if ($.fn.DataTable.isDataTable('#payoutsTable')) {
                $('#payoutsTable').DataTable().destroy();
            }

            dataTableInstance = $('#payoutsTable').DataTable({
                paging: true,
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                ordering: true,
                order: [[7, 'desc']], // order by date desc
                info: true,
                responsive: true,
                language: {
                    url: '{{ asset("assets/json/datatable-arabic.json") }}',
                    emptyTable: '{{ __("لا توجد بيانات متاحة في الجدول") }}',
                    zeroRecords: '{{ __("لم يتم العثور على أية سجلات مطابقة") }}',
                    search: '{{ __("تصفية النتائج:") }}',
                    lengthMenu: '{{ __("عرض _MENU_ سجل") }}',
                    info: '{{ __("عرض _START_ إلى _END_ من أصل _TOTAL_ سجل") }}',
                    paginate: {
                        next: '{{ __("التالي") }}',
                        previous: '{{ __("السابق") }}'
                    }
                }
            });
        }

        // Preview Report Data
        $('#btnPreview').on('click', function () {
            const $btn = $(this);
            const originalHtml = $btn.html();
            $btn.html('<span class="spinner-border spinner-border-sm me-1"></span> {{ __("جاري التحميل...") }}').prop('disabled', true);

            const formData = $('#payoutReportFilterForm').serialize();

            $.ajax({
                url: '{{ route("admin.reports.payouts.preview") }}',
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function (res) {
                    $btn.html(originalHtml).prop('disabled', false);

                    if (res.success) {
                        // Reveal KPI Container
                        $('#kpiContainer').slideDown();

                        // Update KPIs
                        const s = res.summary;
                        $('#kpiTotalAmount').text(Number(s.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' SAR');
                        $('#kpiTotalCount').text(s.total_count + ' {{ __("عملية") }}');

                        $('#kpiCompletedAmount').text(Number(s.completed_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' SAR');
                        $('#kpiCompletedCount').text(s.completed_count + ' {{ __("عملية ناجحة") }}');

                        const failedSum = Number(s.failed_amount) + Number(s.rejected_amount);
                        const failedCnt = Number(s.failed_count) + Number(s.rejected_count);
                        $('#kpiFailedAmount').text(failedSum.toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' SAR');
                        $('#kpiFailedCount').text(failedCnt + ' {{ __("عملية فاشلة/مرفوضة") }}');

                        $('#kpiPendingAmount').text(Number(s.pending_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' SAR');
                        $('#kpiPendingCount').text(s.pending_count + ' {{ __("عملية معلقة") }}');

                        $('#badgeRecordsCount').text(res.count);

                        // Populate Table
                        if ($.fn.DataTable.isDataTable('#payoutsTable')) {
                            $('#payoutsTable').DataTable().destroy();
                        }

                        let rowsHtml = '';
                        if (res.data && res.data.length > 0) {
                            res.data.forEach(function (item, idx) {
                                const entityLink = item.entity_url
                                    ? `<a href="${item.entity_url}" target="_blank" class="fw-bold text-primary">${item.entity_name}</a>`
                                    : `<strong>${item.entity_name}</strong>`;

                                const copyBtn = `<button type="button" class="btn btn-xs btn-outline-secondary copy-btn ms-1" data-copy="${item.reference_id}" title="{{ __('نسخ المرجع') }}"><i class="ti ti-copy"></i></button>`;

                                const reasonHtml = item.reason
                                    ? `<div class="text-danger small mt-1" style="max-width: 180px; word-break: break-word;"><i class="ti ti-alert-circle me-1"></i>${item.reason}</div>`
                                    : '';

                                rowsHtml += `
                                    <tr>
                                        <td><span class="badge bg-label-secondary">#${item.id}</span></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <strong class="text-dark">${item.reference_id}</strong>
                                                ${copyBtn}
                                            </div>
                                            <small class="text-muted d-block">${item.payout_id !== '—' ? item.payout_id : ''}</small>
                                        </td>
                                        <td>
                                            <span class="badge ${item.section_badge}">${item.payout_section}</span>
                                            <div class="small text-muted mt-1">${item.payout_type_label}</div>
                                        </td>
                                        <td>
                                            ${entityLink}
                                            <div class="small text-muted">${item.beneficiary_name}</div>
                                        </td>
                                        <td>
                                            <div class="iban-code text-primary" dir="ltr">${item.iban}</div>
                                            <small class="text-muted"><i class="ti ti-building-bank me-1"></i>${item.bank_name}</small>
                                        </td>
                                        <td>
                                            <strong class="text-dark fs-6">${item.amount_formatted}</strong>
                                        </td>
                                        <td>
                                            <span class="badge ${item.status_badge}">${item.status_label}</span>
                                            ${reasonHtml}
                                        </td>
                                        <td>
                                            <span class="small">${item.created_at}</span>
                                            <small class="text-muted d-block">${item.created_at_human}</small>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-icon btn-label-secondary btn-view-details" 
                                                data-id="${item.id}"
                                                data-ref="${item.reference_id}"
                                                data-payout-id="${item.payout_id}"
                                                data-beneficiary="${item.beneficiary_name}"
                                                data-iban="${item.iban}"
                                                data-bank="${item.bank_name}"
                                                data-amount="${item.amount_formatted}"
                                                data-status="${item.status_label}"
                                                data-reason="${item.reason || '—'}"
                                                data-date="${item.created_at}"
                                                data-creator="${item.creator_name}"
                                                data-approver="${item.approver_name}"
                                                title="{{ __('عرض التفاصيل') }}">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });
                        } else {
                            rowsHtml = `
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">
                                        <i class="ti ti-info-circle fs-1 d-block mb-2 text-warning"></i>
                                        {{ __('لا توجد سجلات مدفوعات مطابقة لمعايير البحث المحددة') }}
                                    </td>
                                </tr>
                            `;
                        }

                        $('#payoutsTableBody').html(rowsHtml);

                        if (res.data && res.data.length > 0) {
                            initDataTable();
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("خطأ") }}',
                            text: res.message || '{{ __("حدث خطأ أثناء تحميل التقرير") }}'
                        });
                    }
                },
                error: function (xhr) {
                    $btn.html(originalHtml).prop('disabled', false);
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("خطأ في الخادم") }}',
                        text: xhr.responseJSON?.message || '{{ __("تعذر الاتصال بالخادم، يرجى المحاولة لاحقاً") }}'
                    });
                }
            });
        });

        // Copy Reference Button
        $(document).on('click', '.copy-btn', function () {
            const text = $(this).data('copy');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    const $btn = $(this);
                    $btn.html('<i class="ti ti-check text-success"></i>');
                    setTimeout(() => {
                        $btn.html('<i class="ti ti-copy"></i>');
                    }, 1500);
                });
            }
        });

        // View Details Modal / SweetAlert
        $(document).on('click', '.btn-view-details', function () {
            const d = $(this).data();
            Swal.fire({
                title: `<span class="fw-bold">${d.ref}</span>`,
                html: `
                    <div class="text-start p-2" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
                        <table class="table table-sm table-bordered">
                            <tr><th style="width:35%;">${'{{ __("رقم العملية HyperPay") }}'}:</th><td>${d.payoutId}</td></tr>
                            <tr><th>${'{{ __("المستفيد البنكي") }}'}:</th><td><strong>${d.beneficiary}</strong></td></tr>
                            <tr><th>${'{{ __("الآيبان") }}'}:</th><td dir="ltr" class="font-monospace">${d.iban}</td></tr>
                            <tr><th>${'{{ __("البنك / السويفت") }}'}:</th><td>${d.bank}</td></tr>
                            <tr><th>${'{{ __("المبلغ") }}'}:</th><td class="fw-bold text-primary">${d.amount}</td></tr>
                            <tr><th>${'{{ __("الحالة") }}'}:</th><td>${d.status}</td></tr>
                            <tr><th>${'{{ __("سبب الفشل/الرفض") }}'}:</th><td class="text-danger">${d.reason}</td></tr>
                            <tr><th>${'{{ __("تاريخ التحويل") }}'}:</th><td>${d.date}</td></tr>
                            <tr><th>${'{{ __("منشئ الطلب") }}'}:</th><td>${d.creator}</td></tr>
                            <tr><th>${'{{ __("المعتمد") }}'}:</th><td>${d.approver}</td></tr>
                        </table>
                    </div>
                `,
                confirmButtonText: '{{ __("إغلاق") }}',
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
        });

        // Reset Filters Button
        $('#btnResetFilters').on('click', function () {
            $('#payoutReportFilterForm')[0].reset();
            fpFrom.clear();
            fpTo.clear();
            $('.select2').val('all').trigger('change');
            $('#entity_id').val('').trigger('change');
            $('.preset-badge').removeClass('active');
            
            // Hide KPIs and reset table
            $('#kpiContainer').slideUp();
            if ($.fn.DataTable.isDataTable('#payoutsTable')) {
                $('#payoutsTable').DataTable().destroy();
            }

            $('#payoutsTableBody').html(`
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="ti ti-filter fs-1 d-block mb-2 text-primary opacity-50"></i>
                        <h6 class="text-muted fw-semibold mb-1">{{ __('لا توجد بيانات معروضة حالياً') }}</h6>
                        <p class="small text-muted mb-0">{{ __('يرجى تحديد الفلاتر المطلوبة ثم الضغط على "معاينة التقرير" لعرض البيانات') }}</p>
                    </td>
                </tr>
            `);
            $('#badgeRecordsCount').text('0');
        });

        // Trigger Export Helper
        function triggerExport(type) {
            $('#export_type').val(type);
            $('#exp_date_from').val($('#date_from').val());
            $('#exp_date_to').val($('#date_to').val());
            $('#exp_status').val($('#status').val());
            $('#exp_payout_type').val($('#payout_type').val());
            $('#exp_entity_type').val($('#entity_type').val());
            $('#exp_entity_id').val($('#entity_id').val());
            $('#exp_bic_code').val($('#bic_code').val());
            $('#exp_min_amount').val($('#min_amount').val());
            $('#exp_max_amount').val($('#max_amount').val());
            $('#exp_search_query').val($('#search_query').val());

            // Append selected columns
            let hiddenColsHtml = '';
            $('.column-toggle-checkbox:checked').each(function () {
                hiddenColsHtml += `<input type="hidden" name="columns[]" value="${$(this).val()}" />`;
            });
            $('#hiddenColumnsInputs').html(hiddenColsHtml);

            $('#exportForm').submit();
        }

        // Export Excel
        $('#btnExportExcel').on('click', function () {
            triggerExport('excel');
        });

        // Export PDF
        $('#btnExportPdf').on('click', function () {
            triggerExport('pdf');
        });
    });
</script>
@endsection
