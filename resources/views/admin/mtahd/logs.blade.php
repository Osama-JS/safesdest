@extends('layouts/layoutMaster')

@section('title', __('سجلات عمليات متعهد (أمن)'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
    ])
    <style>
        .json-viewer {
            background-color: #1e1e2d;
            color: #72e128;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.85rem;
            max-height: 400px;
            overflow-y: auto;
            border-radius: 6px;
            padding: 15px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .copy-btn {
            cursor: pointer;
            transition: all 0.2s;
        }
        .copy-btn:hover {
            transform: scale(1.1);
        }
    </style>
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js'
    ])
@endsection

@section('content')
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">{{ __('الإدارة') }} /</span> {{ __('سجلات عمليات متعهد (Amnn / Mtahd Logs)') }}
    </h4>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted small">{{ __('إجمالي العمليات') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2 text-primary fw-bold">{{ number_format($totalOperations) }}</h4>
                            </div>
                            <small class="text-muted">{{ __('جميع الحركات') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-activity ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted small">{{ __('العمليات الناجحة') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2 text-success fw-bold">{{ number_format($successfulOperations) }}</h4>
                            </div>
                            <small class="text-success">{{ __('اكتملت بنجاح') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-circle-check ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted small">{{ __('العمليات الفاشلة') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2 text-danger fw-bold">{{ number_format($failedOperations) }}</h4>
                            </div>
                            <small class="text-danger">{{ __('تحتاج مراجعة') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-alert-triangle ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted small">{{ __('تحرير الضمان') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2 text-info fw-bold">{{ number_format($releasedFundsCount) }}</h4>
                            </div>
                            <small class="text-info">{{ __('أموال تم صرفها') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-cash ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted small">{{ __('صفقات ملغاة') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h4 class="mb-0 me-2 text-warning fw-bold">{{ number_format($cancelledDealsCount) }}</h4>
                            </div>
                            <small class="text-warning">{{ __('تم استردادها') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-ban ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-2">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-muted small">{{ __('إجمالي الصفقات') }}</span>
                            <div class="d-flex align-items-center my-2">
                                <h5 class="mb-0 me-2 text-dark fw-bold">{{ number_format($totalAmount, 2) }}</h5>
                            </div>
                            <small class="text-muted">{{ __('ريال سعودي') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-secondary">
                                <i class="ti ti-wallet ti-sm"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header border-bottom py-3">
            <h5 class="card-title mb-0"><i class="ti ti-filter me-2 text-primary"></i>{{ __('فلاتر البحث المتقدم') }}</h5>
        </div>
        <div class="card-body pt-3">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">{{ __('الحالة') }}</label>
                    <select id="filter_status" class="form-select">
                        <option value="">{{ __('جميع الحالات') }}</option>
                        <option value="success">{{ __('ناجحة (Success)') }}</option>
                        <option value="failed">{{ __('فاشلة (Failed)') }}</option>
                        <option value="pending">{{ __('معلقة (Pending)') }}</option>
                        <option value="info">{{ __('معلومات (Info)') }}</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">{{ __('نوع العملية') }}</label>
                    <select id="filter_action" class="form-select">
                        <option value="">{{ __('جميع العمليات') }}</option>
                        <option value="create_deal">{{ __('إنشاء صفقة جديدة') }}</option>
                        <option value="create_customer">{{ __('إنشاء عميل في أمن') }}</option>
                        <option value="add_parties">{{ __('إضافة أطراف الصفقة') }}</option>
                        <option value="submit_deal">{{ __('اعتماد وتأكيد الصفقة') }}</option>
                        <option value="release_funds">{{ __('تحرير وصرف الضمان') }}</option>
                        <option value="cancel_deal">{{ __('إلغاء الصفقة واسترداد الضمان') }}</option>
                        <option value="deliver_deal">{{ __('تأكيد التسليم') }}</option>
                        <option value="get_deal">{{ __('استعلام عن الصفقة') }}</option>
                        <option value="webhook_received">{{ __('إشعار لحظي (Webhook)') }}</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-bold">{{ __('نطاق التاريخ') }}</label>
                    <div class="input-group">
                        <input type="text" id="filter_date_range" class="form-control" placeholder="{{ __('اختر الفترة الزمنية...') }}">
                        <span class="input-group-text"><i class="ti ti-calendar"></i></span>
                    </div>
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="button" class="btn btn-primary w-100" id="btn_apply_filter">
                        <i class="ti ti-search me-1"></i>{{ __('تصفية') }}
                    </button>
                    <button type="button" class="btn btn-label-secondary" id="btn_reset_filter" title="{{ __('إعادة ضبط') }}">
                        <i class="ti ti-refresh"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-header border-bottom py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="ti ti-receipt-2 me-2 text-primary"></i>{{ __('سجل عمليات وعقود منصة متعهد (Amnn Logs)') }}
            </h5>
            <div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btn_reload_table">
                    <i class="ti ti-refresh me-1"></i>{{ __('تحديث الجدول') }}
                </button>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-hover border-top" id="mtahd_logs_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('التاريخ / الوقت') }}</th>
                        <th>{{ __('رقم الصفقة (Deal #)') }}</th>
                        <th>{{ __('المهمة') }}</th>
                        <th>{{ __('العملية') }}</th>
                        <th>{{ __('المبلغ') }}</th>
                        <th>{{ __('الحالة') }}</th>
                        <th>{{ __('المنفذ') }}</th>
                        <th>{{ __('الإجراءات') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Log Details & JSON Payload Modal -->
    <div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="ti ti-code me-2 text-primary"></i>{{ __('تفاصيل العملية وحمولة البيانات (API Payload & Response)') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('إغلاق') }}"></button>
                </div>
                <div class="modal-body">
                    <!-- Quick Info Grid -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="border rounded p-2 bg-light">
                                <small class="text-muted d-block">{{ __('رقم الصفقة') }}</small>
                                <span id="modal_deal_number" class="fw-bold text-primary font-monospace"></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-2 bg-light">
                                <small class="text-muted d-block">{{ __('نوع العملية') }}</small>
                                <span id="modal_action_label" class="fw-bold"></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-2 bg-light">
                                <small class="text-muted d-block">{{ __('الحالة وكود الاستجابة') }}</small>
                                <span id="modal_status" class="me-2"></span>
                                <span id="modal_http_status" class="badge bg-secondary"></span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-2 bg-light">
                                <small class="text-muted d-block">{{ __('تاريخ التنفيذ') }}</small>
                                <span id="modal_created_at" class="fw-semibold"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message Alert if exists -->
                    <div id="modal_error_box" class="alert alert-danger d-none mb-3" role="alert">
                        <div class="d-flex">
                            <i class="ti ti-alert-circle ti-md me-2"></i>
                            <div>
                                <h6 class="alert-heading mb-1">{{ __('رسالة الخطأ:') }}</h6>
                                <p id="modal_error_message" class="mb-0"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Payloads Tabs -->
                    <ul class="nav nav-tabs nav-fill mb-3" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#tab-request">
                                <i class="ti ti-arrow-up-circle me-1"></i>{{ __('البيانات المرسلة (Request Payload)') }}
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#tab-response">
                                <i class="ti ti-arrow-down-circle me-1"></i>{{ __('استجابة الـ API (API Response)') }}
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content p-0">
                        <div class="tab-pane fade show active" id="tab-request" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">{{ __('البيانات التي تم إرسالها إلى منصة أمن:') }}</span>
                                <button type="button" class="btn btn-xs btn-outline-secondary copy-btn" id="btn_copy_request">
                                    <i class="ti ti-copy me-1"></i>{{ __('نسخ JSON') }}
                                </button>
                            </div>
                            <pre class="json-viewer" id="modal_request_payload"></pre>
                        </div>

                        <div class="tab-pane fade" id="tab-response" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted small">{{ __('الاستجابة المستلمة من منصة أمن:') }}</span>
                                <button type="button" class="btn btn-xs btn-outline-secondary copy-btn" id="btn_copy_response">
                                    <i class="ti ti-copy me-1"></i>{{ __('نسخ JSON') }}
                                </button>
                            </div>
                            <pre class="json-viewer" id="modal_response_payload"></pre>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('إغلاق') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Flatpickr Date Range Picker
        let fromDate = '', toDate = '';
        const datePicker = $('#filter_date_range').flatpickr({
            mode: 'range',
            dateFormat: 'Y-m-d',
            locale: {
                rangeSeparator: ' إلى '
            },
            onClose: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    fromDate = instance.formatDate(selectedDates[0], 'Y-m-d');
                    toDate = instance.formatDate(selectedDates[1], 'Y-m-d');
                } else {
                    fromDate = '';
                    toDate = '';
                }
            }
        });

        // Initialize DataTable
        const table = $('#mtahd_logs_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.mtahd.data') }}",
                type: 'GET',
                data: function (d) {
                    d.status = $('#filter_status').val();
                    d.action_filter = $('#filter_action').val();
                    d.from_date = fromDate;
                    d.to_date = toDate;
                }
            },
            columns: [
                { data: 'id', name: 'id', width: '5%' },
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function (data, type, row) {
                        return `<div><span class="fw-semibold">${data}</span><br><small class="text-muted">${row.created_at_human}</small></div>`;
                    }
                },
                {
                    data: 'deal_number',
                    name: 'deal_number',
                    render: function (data) {
                        if (!data || data === '-') return '<span class="text-muted">-</span>';
                        return `<span class="badge bg-label-dark font-monospace copy-btn" title="{{ __('انقر للنسخ') }}" onclick="navigator.clipboard.writeText('${data}'); Swal.fire({toast: true, position: 'top-end', icon: 'success', title: 'تم نسخ رقم الصفقة', showConfirmButton: false, timer: 1500});"><i class="ti ti-copy ti-xs me-1"></i>${data}</span>`;
                    }
                },
                {
                    data: 'task_id',
                    name: 'task_id',
                    render: function (data) {
                        if (!data || data === '-') return '<span class="text-muted">-</span>';
                        return `<span class="badge bg-label-info fw-bold">${data}</span>`;
                    }
                },
                {
                    data: 'action_label',
                    name: 'action',
                    render: function (data, type, row) {
                        let icon = 'ti-activity';
                        if (row.action === 'release_funds') icon = 'ti-cash';
                        else if (row.action === 'cancel_deal') icon = 'ti-ban';
                        else if (row.action === 'create_deal') icon = 'ti-file-plus';
                        else if (row.action === 'submit_deal') icon = 'ti-send';
                        else if (row.action === 'webhook_received') icon = 'ti-webhook';

                        return `<span class="fw-semibold"><i class="ti ${icon} me-1 text-primary"></i>${data}</span>`;
                    }
                },
                {
                    data: 'amount',
                    name: 'amount',
                    render: function (data) {
                        if (!data || data === '-') return '<span class="text-muted">-</span>';
                        return `<span class="fw-bold text-success">${data}</span>`;
                    }
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function (data, type, row) {
                        return `<span class="${row.status_badge}">${data.toUpperCase()}</span>`;
                    }
                },
                {
                    data: 'performed_by',
                    name: 'performed_by',
                    render: function (data, type, row) {
                        return `<small class="text-muted">${data}<br><span class="font-monospace">${row.ip_address}</span></small>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        let actions = `<div class="d-flex align-items-center gap-1">`;

                        // View Details Button
                        actions += `<button type="button" class="btn btn-sm btn-icon btn-label-primary btn-view-log" data-id="${row.id}" title="{{ __('عرض حمولة البيانات') }}"><i class="ti ti-eye"></i></button>`;

                        // Live Sync / Check Status if deal number exists
                        if (row.deal_number && row.deal_number !== '-') {
                            actions += `<button type="button" class="btn btn-sm btn-icon btn-label-info btn-check-deal" data-deal="${row.deal_number}" title="{{ __('استعلام مباشر من أمن') }}"><i class="ti ti-refresh"></i></button>`;
                        }

                        // Release Funds Button
                        if (row.deal_number && row.deal_number !== '-' && row.status === 'success' && row.action === 'submit_deal') {
                            actions += `<button type="button" class="btn btn-sm btn-icon btn-label-success btn-release-deal" data-deal="${row.deal_number}" title="{{ __('تحرير الضمان المالي') }}"><i class="ti ti-cash"></i></button>`;
                        }

                        // Cancel Deal Button
                        if (row.deal_number && row.deal_number !== '-' && row.status === 'success' && row.action !== 'cancel_deal') {
                            actions += `<button type="button" class="btn btn-sm btn-icon btn-label-danger btn-cancel-deal" data-deal="${row.deal_number}" title="{{ __('إلغاء الصفقة') }}"><i class="ti ti-ban"></i></button>`;
                        }

                        actions += `</div>`;
                        return actions;
                    }
                }
            ],
            order: [[0, 'desc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/ar.json'
            }
        });

        // Filter and Reload Handlers
        $('#btn_apply_filter').on('click', function () {
            table.draw();
        });

        $('#btn_reset_filter').on('click', function () {
            $('#filter_status').val('');
            $('#filter_action').val('');
            $('#filter_date_range').val('');
            fromDate = '';
            toDate = '';
            table.draw();
        });

        $('#btn_reload_table').on('click', function () {
            table.ajax.reload(null, false);
        });

        // View Log Details Modal
        $(document).on('click', '.btn-view-log', function () {
            const logId = $(this).data('id');

            $.get(`{{ url('admin/mtahd-logs') }}/${logId}`, function (res) {
                if (res.status) {
                    const log = res.log;
                    $('#modal_deal_number').text(log.deal_number || '-');
                    $('#modal_action_label').text(log.action_label || '-');
                    $('#modal_status').html(`<span class="${log.status_badge}">${log.status.toUpperCase()}</span>`);
                    $('#modal_http_status').text(`HTTP: ${log.http_status || 'N/A'}`);
                    $('#modal_created_at').text(log.created_at);

                    if (log.error_message) {
                        $('#modal_error_box').removeClass('d-none');
                        $('#modal_error_message').text(log.error_message);
                    } else {
                        $('#modal_error_box').addClass('d-none');
                    }

                    $('#modal_request_payload').text(log.request_payload ? JSON.stringify(log.request_payload, null, 2) : 'لا توجد بيانات مرسلة (Null)');
                    $('#modal_response_payload').text(log.response_payload ? JSON.stringify(log.response_payload, null, 2) : 'لا توجد استجابة (Null)');

                    $('#logDetailsModal').modal('show');
                } else {
                    Swal.fire({ icon: 'error', title: 'خطأ', text: 'تعذر جلب تفاصيل السجل' });
                }
            }).fail(function () {
                Swal.fire({ icon: 'error', title: 'خطأ', text: 'حدث خطأ أثناء الاتصال بالسيرفر' });
            });
        });

        // Copy Payload Buttons
        $('#btn_copy_request').on('click', function () {
            const text = $('#modal_request_payload').text();
            navigator.clipboard.writeText(text);
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'تم نسخ حمولة الطلب', showConfirmButton: false, timer: 1500 });
        });

        $('#btn_copy_response').on('click', function () {
            const text = $('#modal_response_payload').text();
            navigator.clipboard.writeText(text);
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'تم نسخ استجابة الـ API', showConfirmButton: false, timer: 1500 });
        });

        // Live Check Deal Status from Amnn API
        $(document).on('click', '.btn-check-deal', function () {
            const dealNumber = $(this).data('deal');

            Swal.fire({
                title: 'جاري الاستعلام...',
                text: `الاستعلام عن الصفقة ${dealNumber} من منصة أمن`,
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.post(`{{ url('admin/mtahd-deals') }}/${dealNumber}/check-status`, { _token: '{{ csrf_token() }}' }, function (res) {
                if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: 'نتيجة الاستعلام',
                        html: `<div class="text-start"><pre class="json-viewer">${JSON.stringify(res.data, null, 2)}</pre></div>`,
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'فشل الاستعلام', text: res.error || 'تعذر الاستعلام عن الصفقة' });
                }
            }).fail(function () {
                Swal.fire({ icon: 'error', title: 'خطأ', text: 'فشل الاتصال بمنصة متعهد' });
            });
        });

        // Release Deal Funds
        $(document).on('click', '.btn-release-deal', function () {
            const dealNumber = $(this).data('deal');

            Swal.fire({
                title: 'هل أنت متأكد من تحرير الضمان؟',
                text: `سيتم تحرير وصرف المبالغ المحجوزة للصفقة ${dealNumber} فوراً للبائع/الناقل!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، قم بتحرير الضمان',
                cancelButtonText: 'إلغاء',
                customClass: { confirmButton: 'btn btn-success me-2', cancelButton: 'btn btn-label-secondary' },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'جاري تنفيذ التحرير...', didOpen: () => { Swal.showLoading(); } });

                    $.post(`{{ url('admin/mtahd-deals') }}/${dealNumber}/release`, { _token: '{{ csrf_token() }}' }, function (res) {
                        if (res.status) {
                            Swal.fire({ icon: 'success', title: 'تم التحرير بنجاح', text: res.message || 'تم تحرير الضمان المالي في منصة أمن' });
                            table.ajax.reload(null, false);
                        } else {
                            Swal.fire({ icon: 'error', title: 'فشل التحرير', text: res.error || 'تعذر تحرير الضمان' });
                        }
                    }).fail(function () {
                        Swal.fire({ icon: 'error', title: 'خطأ', text: 'حدث خطأ أثناء الاتصال' });
                    });
                }
            });
        });

        // Cancel Deal
        $(document).on('click', '.btn-cancel-deal', function () {
            const dealNumber = $(this).data('deal');

            Swal.fire({
                title: 'إلغاء الصفقة واسترداد الضمان',
                text: `أدخل سبب إلغاء الصفقة ${dealNumber}:`,
                input: 'textarea',
                inputPlaceholder: 'اكتب سبب الإلغاء هنا...',
                showCancelButton: true,
                confirmButtonText: 'تأكيد الإلغاء',
                cancelButtonText: 'تراجع',
                customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-label-secondary' },
                buttonsStyling: false,
                inputValidator: (value) => {
                    if (!value) {
                        return 'يرجى كتابة سبب الإلغاء!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: 'جاري الإلغاء...', didOpen: () => { Swal.showLoading(); } });

                    $.post(`{{ url('admin/mtahd-deals') }}/${dealNumber}/cancel`, { _token: '{{ csrf_token() }}', reason: result.value }, function (res) {
                        if (res.status) {
                            Swal.fire({ icon: 'success', title: 'تم الإلغاء بنجاح', text: res.message || 'تم إلغاء الصفقة واسترداد الضمان' });
                            table.ajax.reload(null, false);
                        } else {
                            Swal.fire({ icon: 'error', title: 'فشل الإلغاء', text: res.error || 'تعذر إلغاء الصفقة' });
                        }
                    }).fail(function () {
                        Swal.fire({ icon: 'error', title: 'خطأ', text: 'حدث خطأ أثناء الاتصال' });
                    });
                }
            });
        });
    });
</script>
@endsection
