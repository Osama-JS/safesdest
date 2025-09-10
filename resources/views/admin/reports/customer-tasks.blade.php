@extends('layouts/layoutMaster')

@section('title', __('Customer Tasks Report'))

<!-- Vendor Styles -->
@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])

@endsection

<!-- Page Styles -->
@section('page-style')
    @vite(['resources/css/app.css'])
    {{-- <link rel="stylesheet" href="{{ asset('css/admin/reports.css') }}"> --}}
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

        .select2-container--bootstrap-5 .select2-selection--single {
            height: calc(2.25rem + 2px);
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding: 0;
            line-height: 1.5;
            color: #212529;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem);
            right: 0.75rem;
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

        .select2-container--bootstrap-5 .select2-results__option {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        .select2-container--bootstrap-5 .select2-results__option--highlighted {
            background-color: #0d6efd;
            color: #fff;
        }

        .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            border: 1px solid #d0d7de;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }

        /* RTL Support */
        [dir="rtl"] .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
            left: 0.75rem;
            right: auto;
        }

        [dir="rtl"] .select2-container--bootstrap-5 .select2-selection--single {
            padding: 0.375rem 0.75rem 0.375rem 2.25rem;
        }

        /* Column Selector Styles */
        .column-selector {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            background-color: #f8f9fa;
        }

        .column-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            cursor: move;
            transition: all 0.3s ease;
        }

        .column-item:hover {
            border-color: #adb5bd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .column-item.required {
            background-color: #e3f2fd;
            border-color: #2196f3;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            text-align: center;
        }
    </style>
@endsection
<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])

@endsection

<!-- Page Scripts -->
@section('page-script')
    <script src="{{ asset('js/admin/reports/customer-tasks.js') }}"></script>

    <script>
        // Set up routes for the JavaScript class
        window.routes = {
            preview: '{{ route('admin.reports.customer-tasks.preview') }}',
            generate: '{{ route('admin.reports.customer-tasks.generate') }}'
        };

        // Function to check if all required libraries are loaded
        function checkLibrariesLoaded() {
            return typeof $ !== 'undefined' &&
                typeof moment !== 'undefined' &&
                typeof Swal !== 'undefined' &&
                typeof $.fn.select2 !== 'undefined' &&
                typeof $.fn.daterangepicker !== 'undefined';
        }

        // Function to initialize the report system
        function initializeReport() {
            if (checkLibrariesLoaded()) {
                if (typeof CustomerTasksReport !== 'undefined') {
                    console.log('All libraries loaded. Initializing CustomerTasksReport...');
                    new CustomerTasksReport();
                } else {
                    console.error('CustomerTasksReport class not found');
                }
            } else {
                console.log('Waiting for libraries to load...');
                setTimeout(initializeReport, 500);
            }
        }

        // Start initialization when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeReport);
        } else {
            initializeReport();
        }
    </script>
@endsection

@section('content')

    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">
                <i class="tf-icons ti ti-report me-2 fs-3 text-white bg-primary rounded p-1"></i>

                {{ __('Platform Reports') }} | {{ __('Customer Tasks Report') }}
            </h5>
            <p>{{ __('Generate detailed reports for customer tasks with customizable filters') }}
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid">
        <!-- Filters Section -->
        <div class="row">
            <div class="col-12">
                <div class="card filter-card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-filter me-2"></i>
                            {{ __('Report Filters') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="reportForm">
                            <div class="row">
                                <!-- Customer Selection -->
                                <div class="col-md-4 mb-3">
                                    <label for="customer_ids" class="form-label">{{ __('Select Customers') }} <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select  flitter-select" id="customer_ids" name="customer_ids[]"
                                        multiple required>
                                        @foreach ($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }} @if ($customer->company_name)
                                                    - {{ $customer->company_name }}
                                                @endif
                                            </option>
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

                                <!-- Task Status -->
                                <div class="col-md-4 mb-3">
                                    <label for="task_statuses" class="form-label">{{ __('Task Status') }}</label>
                                    <select class="form-select flitter-select" id="task_statuses" name="task_statuses[]"
                                        multiple>
                                        @foreach ($taskStatuses as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Payment Status -->
                                <div class="col-md-4 mb-3">
                                    <label for="payment_status" class="form-label">{{ __('Payment Status') }}</label>
                                    <select class=" flitter-select form-select" id="payment_status" name="payment_status">
                                        <option value="">{{ __('All Payment Statuses') }}</option>
                                        @foreach ($paymentStatuses as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Payment Method -->
                                <div class="col-md-4 mb-3">
                                    <label for="payment_method" class="form-label">{{ __('Payment Method') }}</label>
                                    <select class="form-select flitter-select" id="payment_method" name="payment_method">
                                        <option value="">{{ __('All Payment Methods') }}</option>
                                        @foreach ($paymentMethods as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Driver Selection -->
                                <div class="col-md-4 mb-3">
                                    <label for="driver_ids" class="form-label">{{ __('Select Drivers') }}</label>
                                    <select class="form-select flitter-select" id="driver_ids" name="driver_ids[]" multiple>
                                        @foreach ($drivers as $driver)
                                            <option value="{{ $driver->id }}">{{ $driver->name }} -
                                                {{ $driver->phone }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Team Selection -->
                                <div class="col-md-4 mb-3">
                                    <label for="team_ids" class="form-label">{{ __('Select Teams') }}</label>
                                    <select class="form-select flitter-select" id="team_ids" name="team_ids[]" multiple>
                                        @foreach ($teams as $team)
                                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Created By -->
                                <div class="col-md-4 mb-3">
                                    <label for="created_by" class="form-label">{{ __('Created By') }}</label>
                                    <select class="form-select" id="created_by" name="created_by">
                                        <option value="">{{ __('All') }}</option>
                                        <option value="customer">{{ __('Customer') }}</option>
                                        <option value="admin">{{ __('Admin') }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary" id="previewBtn">
                                            <i class="ti ti-eye me-1"></i>
                                            {{ __('Preview Report') }}
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="resetBtn">
                                            <i class="ti ti-refresh me-1"></i>
                                            {{ __('Reset Filters') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Column Selection Section -->
        <div class="row preview-section" id="columnSection">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-columns me-2"></i>
                            {{ __('Select Report Columns') }}
                        </h5>
                        <small class="text-muted">{{ __('Minimum 4 columns required. Drag to reorder.') }}</small>
                    </div>
                    <div class="card-body">
                        <div class="column-selector" id="columnSelector">
                            <!-- Columns will be populated by JavaScript -->
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-info">{{ __('Selected') }}: <span id="selectedCount">0</span></span>
                            <span class="badge bg-warning ms-2">{{ __('Minimum Required') }}: 4</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Section -->
        <div class="row preview-section" id="previewSection">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-table me-2"></i>
                            {{ __('Report Preview') }}
                        </h5>
                        <div class="export-buttons d-flex">
                            <button type="button" class="btn btn-success" id="exportExcelBtn">
                                <i class="ti ti-file-spreadsheet me-1"></i>
                                {{ __('Export to Excel') }}
                            </button>
                            <button type="button" class="btn btn-danger" id="exportPdfBtn">
                                <i class="ti ti-file-text me-1"></i>
                                {{ __('Export to PDF') }}
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="previewTable">
                            <!-- Table will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                    </div>
                    <h6 class="mb-2">{{ __('Processing Request') }}</h6>
                    <p class="text-muted small mb-0">
                        {{ __('Please wait while we process your request...') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                    </div>
                    <h6 class="mb-2">{{ __('Processing Request') }}</h6>
                    <p class="text-muted small mb-0">{{ __('Please wait while we process your request...') }}</p>
                </div>
            </div>
        </div>
    </div>

@endsection
