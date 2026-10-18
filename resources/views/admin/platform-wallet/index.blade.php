@extends('layouts/layoutMaster')

@section('title', __('Platform Wallet'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

<!-- Page Styles -->
@section('page-style')
    @vite(['resources/css/app.css'])
    <style>
        .stats-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0;
        }



        .commission-badge {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }

        .payment-status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
        }



        /* .dataTables_wrapper .dataTables_length,
                                        .dataTables_wrapper .dataTables_filter {
                                            margin-bottom: 1rem;
                                        } */

        .btn-export {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            color: white;
        }
    </style>
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    <script>
        const taskShowBaseUrl = "{{ url('admin/tasks/list/show') }}/";
    </script>
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/admin/platform-wallet/index.js'])
@endsection

@section('content')


    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">
                                <i class="tf-icons ti ti-cash me-2 fs-3 text-white bg-primary rounded p-1"></i>
                                {{ __('Platform Wallet') }}
                            </h5>
                            <p class="text-muted mb-0">{{ __('Track platform commissions from completed tasks') }}</p>
                        </div>

                    </div>


                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4" id="statisticsCards">
        <!-- Statistics will be loaded here -->
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card filter-card">
                <div class="card-body">
                    <h5 class="card-title  mb-3">
                        <i class="ti ti-filter me-2"></i>{{ __('Filters') }}
                    </h5>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label ">{{ __('Date From') }}</label>
                            <input type="date" class="form-control" id="dateFrom" name="date_from">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label ">{{ __('Date To') }}</label>
                            <input type="date" class="form-control" id="dateTo" name="date_to">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label ">{{ __('Task Status') }}</label>
                            <select class="form-select" id="taskStatus" name="task_status">
                                <option value="">{{ __('All Statuses (Excl. Canceled/Refund)') }}</option>
                                <option value="pending_payment">{{ __('Pending Payment') }}</option>
                                <option value="advertised">{{ __('Advertised') }}</option>
                                <option value="in_progress">{{ __('In Progress') }}</option>
                                <option value="assign">{{ __('Assign') }}</option>
                                <option value="accepted">{{ __('Accepted') }}</option>
                                <option value="started">{{ __('Started') }}</option>
                                <option value="in pickup point">{{ __('In Pickup Point') }}</option>
                                <option value="loading">{{ __('Loading') }}</option>
                                <option value="in the way">{{ __('In the Way') }}</option>
                                <option value="in delivery point">{{ __('In Delivery Point') }}</option>
                                <option value="unloading">{{ __('Unloading') }}</option>
                                <option value="completed">{{ __('Completed') }}</option>
                                <option value="canceled">{{ __('Canceled') }}</option>
                                <option value="refund">{{ __('Refund') }}</option>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label ">{{ __('Closed Status') }}</label>
                            <select class="form-select" id="isClosed" name="is_closed">
                                <option value="">{{ __('All') }}</option>
                                <option value="1">{{ __('Closed Only') }}</option>
                                <option value="0">{{ __('Open Only') }}</option>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label ">{{ __('Commission Type') }}</label>
                            <select class="form-select" id="commissionType" name="commission_type">
                                <option value="">{{ __('All Types') }}</option>
                                <option value="dynamic">{{ __('Dynamic') }}</option>
                                <option value="manual">{{ __('Manual') }}</option>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label ">{{ __('Payment Status') }}</label>
                            <select class="form-select" id="paymentStatus" name="payment_status">
                                <option value="">{{ __('All Statuses') }}</option>
                                <option value="pending">{{ __('Pending') }}</option>
                                <option value="just_commission">{{ __('Commission Only') }}</option>
                                <option value="all">{{ __('Fully Paid') }}</option>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="netCommissionFilter" name="net_commission_filter" value="1">
                                <label class="form-check-label fw-bold" for="netCommissionFilter">{{ __('Net Platform Commission') }}</label>
                            </div>
                        </div>

                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-primary" id="applyFilters">
                                <i class="ti ti-search me-1"></i>{{ __('Apply Filters') }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                                <i class="ti ti-refresh me-1"></i>{{ __('Clear Filters') }}
                            </button>
                        </div>
                    </div>
                    <div class="row">

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-list me-2"></i>{{ __('Commission Records') }}
                    </h5>
                    <div class="card-actions d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="exportExcel">
                            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('Export Excel') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="refreshTable">
                            <i class="ti ti-refresh me-1"></i>{{ __('Refresh') }}
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover datatables-platform-wallet" id="platformWalletTable">
                            <thead>
                                <tr>
                                    <th>{{ __('Task ID') }}</th>
                                    <th>{{ __('Customer') }}</th>
                                    <th>{{ __('Driver') }}</th>
                                    <th>{{ __('Team') }}</th>
                                    <th>{{ __('Route') }}</th>
                                    <th>{{ __('Total Price') }}</th>
                                    <th>{{ __('Commission') }}</th>
                                    <th class="net-commission-col" style="display:none;">{{ __('Net Commission') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Payment Status') }}</th>
                                    <th>{{ __('Task Status') }}</th>
                                    <th>{{ __('Completed At') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <!-- Modal: تفاصيل استقطاعات عمولة المنصة للمهمة -->
    <!-- ───────────────────────────────────────────────────────────────────────── -->
    <div class="modal fade" id="taskCommissionDeductionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-white border-bottom py-3">
                    <h5 class="modal-title fw-bold text-primary mb-0 d-flex align-items-center">
                        <i class="ti ti-receipt-2 ti-md me-2 text-primary"></i>
                        <span>{{ __('تفاصيل استقطاعات عمولة المنصة') }} - <span id="modalTaskNumber" class="text-primary fw-bold">#</span></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body py-4">
                    <!-- Task Summary Banner -->
                    <div class="card bg-white border mb-4 shadow-xs">
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-sm-6 col-md-3">
                                    <span class="text-muted d-block small mb-1">{{ __('العميل') }}:</span>
                                    <strong class="text-dark" id="modalCustomerName">-</strong>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <span class="text-muted d-block small mb-1">{{ __('السائق') }}:</span>
                                    <strong class="text-dark" id="modalDriverName">-</strong>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <span class="text-muted d-block small mb-1">{{ __('الفريق') }}:</span>
                                    <strong class="text-dark" id="modalTeamName">-</strong>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <span class="text-muted d-block small mb-1">{{ __('قيمة المهمة') }}:</span>
                                    <strong class="text-dark"><span id="modalTotalPrice">0.00</span> ر.س</strong>
                                </div>
                            </div>
                            <hr class="my-3">
                            <!-- Financial Calculation Summary Row -->
                            <div class="row g-3 text-center">
                                <div class="col-4">
                                    <div class="p-2 rounded bg-label-primary">
                                        <small class="text-primary d-block fw-semibold">{{ __('إجمالي عمولة المنصة') }}</small>
                                        <span class="fs-5 fw-bold text-primary" id="modalGrossCommission">0.00</span> <small>ر.س</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 rounded bg-label-danger">
                                        <small class="text-danger d-block fw-semibold">{{ __('إجمالي الاستقطاعات') }}</small>
                                        <span class="fs-5 fw-bold text-danger" id="modalTotalDeductions">0.00</span> <small>ر.س</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="p-2 rounded bg-label-success">
                                        <small class="text-success d-block fw-semibold">{{ __('صافي المنصة النهائي') }}</small>
                                        <span class="fs-5 fw-bold text-success" id="modalNetCommission">0.00</span> <small>ر.س</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions Table -->
                    <h6 class="fw-bold text-secondary mb-2 d-flex justify-content-between align-items-center">
                        <span><i class="ti ti-arrows-split me-1 text-primary"></i> {{ __('قائمة الحصص والعمولات المستقطعة') }}</span>
                        <span class="badge bg-label-primary rounded-pill" id="modalDeductionsCount">0</span>
                    </h6>

                    <div class="border rounded bg-white">
                        <table class="table table-hover table-sm align-middle mb-0 w-100" style="table-layout: fixed;">
                            <thead class="bg-white border-bottom sticky-top">
                                <tr>
                                    <th style="width: 5%; background-color: #ffffff;">#</th>
                                    <th style="width: 25%; background-color: #ffffff;">{{ __('نوع العمولة / المستفيد') }}</th>
                                    <th style="width: 20%; background-color: #ffffff;">{{ __('الجهة / المصدر') }}</th>
                                    <th style="width: 25%; background-color: #ffffff;">{{ __('التفاصيل والبيان') }}</th>
                                    <th style="width: 12%; background-color: #ffffff;">{{ __('التاريخ') }}</th>
                                    <th style="width: 13%; background-color: #ffffff;" class="text-end">{{ __('المبلغ المستقطع') }}</th>
                                </tr>
                            </thead>
                            <tbody id="modalDeductionsTableBody">
                                <!-- Populated dynamically via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-white border-top">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i> {{ __('إغلاق') }}
                    </button>
                    <a href="#" target="_blank" class="btn btn-primary btn-sm" id="modalViewTaskLink">
                        <i class="ti ti-eye me-1"></i> {{ __('عرض المهمة بالكامل') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

@endsection
