@extends('layouts/layoutMaster')

@section('title', __('Tasks List'))

<!-- Vendor Styles -->
@section('vendor-style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    @vite('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.scss')


    @vite(['resources/css/app.css'])
    @vite(['resources/css/payment-request-print.css'])



@endsection

<!-- Vendor Scripts -->
@section('vendor-script')

    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    @vite('resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js')

    <!-- Daterangepicker JS -->
@endsection

<!-- Page Scripts -->
@section('page-script')

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/admin/tasks/list.js'])
    @vite(['resources/js/spical.js'])
    <script>
        const canViewCommissions = {{ auth()->user()->can('view_task_commissions') ? 'true' : 'false' }};
        const canViewTotalPrice = {{ auth()->user()->can('view_task_total_price') ? 'true' : 'false' }};
        const canSubmitGoTech = {{ (auth()->user()->can('submit_tasks_gotech') || auth()->user()->can('edit_tasks')) ? 'true' : 'false' }};
        const canCancelGoTech = {{ (auth()->user()->can('cancel_tasks_gotech') || auth()->user()->can('edit_tasks')) ? 'true' : 'false' }};
    </script>
    <script>
        const navContent = document.querySelector('#navbar-custom-nav-container');
        const mobileContainer = document.querySelector('#mobile-custom-nav');
        const originalContent = navContent?.innerHTML;

        function moveCustomNav() {
            if (window.innerWidth < 1124) {
                // Ø´Ø§Ø´Ø© ØµØºÙŠØ±Ø©ØŒ Ø§Ù†Ù‚Ù„ Ø§Ù„Ù…Ø­ØªÙˆÙ‰ Ø¥Ù„Ù‰ Ø§Ù„Ø£Ø³ÙÙ„
                if (originalContent && mobileContainer && mobileContainer.innerHTML.trim() === '') {
                    mobileContainer.innerHTML = originalContent;
                    navContent.innerHTML = '';
                }
            } else {
                // Ø´Ø§Ø´Ø© ÙƒØ¨ÙŠØ±Ø©ØŒ Ø£Ø¹Ø¯ Ø§Ù„Ù…Ø­ØªÙˆÙ‰ Ø¥Ù„Ù‰ Ù…ÙƒØ§Ù†Ù‡ Ø§Ù„Ø£ØµÙ„ÙŠ
                if (originalContent && navContent && navContent.innerHTML.trim() === '') {
                    navContent.innerHTML = originalContent;
                    mobileContainer.innerHTML = '';
                }
            }
        }

        moveCustomNav(); // ØªÙ†Ù ÙŠØ° Ø£ÙˆÙ„ÙŠ
        window.addEventListener('resize', moveCustomNav); // ØªÙ†Ù ÙŠØ° Ø¹Ù†Ø¯ ØªØºÙŠÙŠØ± Ø­Ø¬Ù… Ø§Ù„Ø´Ø§Ø´Ø©

        // =========================================================================
        // Diagnostic Logging & Handlers for Brokers Modal (safely wait for jQuery)
        // =========================================================================
        document.addEventListener('DOMContentLoaded', function () {
            function initBrokersSystem() {
                if (typeof $ === 'undefined') {
                    setTimeout(initBrokersSystem, 50);
                    return;
                }
                runBrokersSystem();
            }
            initBrokersSystem();
        });

        function runBrokersSystem() {
            console.log('%c[Brokers System] Script Initialized in Page', 'background: #6f42c1; color: white; padding: 2px 6px; border-radius: 4px; font-weight: bold;');
            console.log('[Brokers System] DOM Check:', {
                '#viewBrokersBreakdownModal exists': $('#viewBrokersBreakdownModal').length > 0,
                '#brokerModal exists': $('#brokerModal').length > 0,
                '#task-broker-row-template exists': $('#task-broker-row-template').length > 0,
                'jQuery version': typeof $ !== 'undefined' ? $.fn.jquery : 'Not Loaded',
                'jQuery modal plugin': typeof $ !== 'undefined' && typeof $.fn.modal === 'function',
                'bootstrap global': typeof bootstrap !== 'undefined' ? bootstrap : 'Not Global',
                'baseUrl': typeof baseUrl !== 'undefined' ? baseUrl : (typeof window.baseUrl !== 'undefined' ? window.baseUrl : 'Not Defined')
            });
        });

        // Handler for View Brokers Breakdown Modal (عرض تفاصيل وحصص الوسطاء)
        $(document).off('click', '.view-task-brokers-breakdown-btn').on('click', '.view-task-brokers-breakdown-btn', function (e) {
            e.preventDefault();
            const $btn = $(this);
            const taskId = $btn.data('id');

            console.group('%c[Brokers Breakdown] Button Clicked! Task ID: ' + taskId, 'background: #0d6efd; color: white; padding: 3px 8px; border-radius: 4px; font-weight: bold;');
            console.log('1. Click Event Target:', this);
            console.log('2. Task ID extracted:', taskId);

            if (!taskId) {
                console.error('[Brokers Breakdown] Error: No data-id found on clicked button!');
                console.groupEnd();
                return;
            }

            if (typeof canViewCommissions !== 'undefined' && !canViewCommissions) {
                console.warn('[Brokers Breakdown] Permission Denied: view_task_commissions is required!');
                Swal.fire({
                    icon: 'error',
                    title: '{{ __('Unauthorized') }}',
                    text: '{{ __('You do not have permission to view broker commissions and details.') }}'
                });
                console.groupEnd();
                return;
            }

            const modalEl = document.getElementById('viewBrokersBreakdownModal');
            console.log('3. Modal Element in DOM (#viewBrokersBreakdownModal):', modalEl);

            if (!modalEl) {
                console.error('[Brokers Breakdown] CRITICAL: Modal element #viewBrokersBreakdownModal NOT FOUND in DOM!');
                alert('خطأ: عنصر المودال #viewBrokersBreakdownModal غير موجود في الصفحة!');
                console.groupEnd();
                return;
            }

            const showModal = () => {
                if (typeof $ !== 'undefined' && typeof $('#viewBrokersBreakdownModal').modal === 'function') {
                    console.log('4. Showing modal using jQuery plugin: $(\'#viewBrokersBreakdownModal\').modal(\'show\')');
                    $('#viewBrokersBreakdownModal').modal('show');
                } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    console.log('4. Showing modal using Bootstrap 5 Native: bootstrap.Modal.getOrCreateInstance');
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                } else {
                    console.error('4. Neither jQuery modal nor Bootstrap.Modal is available!');
                }
            };

            const hideModal = () => {
                if (typeof $ !== 'undefined' && typeof $('#viewBrokersBreakdownModal').modal === 'function') {
                    $('#viewBrokersBreakdownModal').modal('hide');
                } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                }
            };

            showModal();

            $('#vbb-task-id-badge').text('#' + taskId);
            $('#vbb-loading').show();
            $('#vbb-content').hide();

            const apiBase = typeof baseUrl !== 'undefined' ? baseUrl : (typeof window.baseUrl !== 'undefined' ? window.baseUrl : '/');
            const targetUrl = `${apiBase}admin/tasks/brokers-breakdown/${taskId}`;

            console.log('5. Sending AJAX Request:', {
                'Method': 'GET',
                'URL': targetUrl,
                'apiBase': apiBase,
                'taskId': taskId
            });

            $.ajax({
                url: targetUrl,
                type: 'GET',
                dataType: 'json',
                beforeSend: function (xhr) {
                    console.log('6. AJAX beforeSend: Request dispatched to server...');
                },
                success: function (res, _status, xhr) {
                    console.log('%c7. AJAX Success! HTTP Status: ' + xhr.status, 'color: green; font-weight: bold;', res);

                    if (res.status !== 1) {
                        console.warn('[Brokers Breakdown] Server returned non-success status:', res);
                        hideModal();
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('Error') }}',
                            text: res.error || '{{ __('Unable to load brokers data') }}'
                        });
                        console.groupEnd();
                        return;
                    }

                    const d = res.data;
                    console.log('8. Parsed Breakdown Data:', {
                        'Total Price': d.total_price,
                        'Driver Price': d.driver ? d.driver.driver_price : null,
                        'Driver Name': d.driver ? d.driver.name : null,
                        'Brokers Count': d.brokers_count,
                        'Total Brokers Share': d.total_brokers_share,
                        'Platform Gross': d.platform_gross,
                        'Platform Remaining': d.platform_remaining,
                        'Brokers List': d.brokers
                    });

                    $('#vbb-loading').hide();
                    $('#vbb-content').show();

                    // Subtitle info
                    const taskStatus = d.closed ? 'مغلقة' : d.status;
                    $('#vbb-task-subtitle').text(`حالة المهمة: ${taskStatus} | السائق: ${d.driver ? d.driver.name : '-'}`);

                    // 1. Stat cards
                    $('#vbb-total-price').text(parseFloat(d.total_price || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ر.س');
                    $('#vbb-driver-price').text(parseFloat(d.driver ? d.driver.driver_price : 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ر.س');
                    $('#vbb-driver-name').text((d.driver ? d.driver.name : '-') + (d.driver && d.driver.team && d.driver.team !== '-' ? ` (${d.driver.team})` : ''));
                    
                    $('#vbb-brokers-share').text(parseFloat(d.total_brokers_share || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ر.س');
                    $('#vbb-brokers-count-text').text(`${d.brokers_count} ${d.brokers_count === 1 ? 'وسيط' : 'وسطاء'}`);

                    $('#vbb-platform-remaining').text(parseFloat(d.platform_remaining || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ر.س');
                    $('#vbb-platform-gross-text').text(`إجمالي العمولة: ${parseFloat(d.platform_gross || 0).toFixed(2)} ر.س`);

                    // 2. Progress Split Bar
                    const total = parseFloat(d.total_price) || 1;
                    const driverPct = Math.min(100, (parseFloat(d.driver ? d.driver.driver_price : 0) / total) * 100);
                    const brokersPct = Math.min(100, (parseFloat(d.total_brokers_share || 0) / total) * 100);
                    const platformPct = Math.max(0, 100 - driverPct - brokersPct);

                    $('#vbb-bar-driver').css('width', driverPct + '%').attr('title', `السائق: ${driverPct.toFixed(1)}%`);
                    $('#vbb-bar-brokers').css('width', brokersPct + '%').attr('title', `الوسطاء: ${brokersPct.toFixed(1)}%`);
                    $('#vbb-bar-platform').css('width', platformPct + '%').attr('title', `المنصة: ${platformPct.toFixed(1)}%`);

                    // 3. Populate Table
                    const $tbody = $('#vbb-brokers-table-body');
                    $tbody.empty();

                    if (d.brokers && d.brokers.length > 0) {
                        $('#vbb-no-brokers').hide();
                        d.brokers.forEach((b, idx) => {
                            const paidBadge = b.is_paid
                                ? '<span class="badge bg-label-success"><i class="ti ti-check me-1"></i>تم الإيداع بالمحفظة</span>'
                                : '<span class="badge bg-label-warning"><i class="ti ti-clock me-1"></i>بانتظار الصرف</span>';

                            const sourceBadge = b.source_type === 'direct'
                                ? '<span class="badge bg-label-primary">مباشر على المهمة</span>'
                                : '<span class="badge bg-label-info">' + b.source + '</span>';

                            const row = `
                                <tr>
                                    <td class="fw-bold">${idx + 1}</td>
                                    <td>
                                        <div class="fw-bold text-dark">${b.name}</div>
                                        <small class="text-muted"><i class="ti ti-phone me-1"></i>${b.phone}</small>
                                    </td>
                                    <td>${sourceBadge}</td>
                                    <td>
                                        <span class="badge bg-label-secondary font-monospace">${b.commission_text}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success fs-6">${parseFloat(b.share).toLocaleString('en-US', {minimumFractionDigits: 2})} ر.س</span>
                                    </td>
                                    <td>${paidBadge}</td>
                                </tr>
                            `;
                            $tbody.append(row);
                        });
                        console.log('9. Rendered ' + d.brokers.length + ' broker rows into table.');
                    } else {
                        $('#vbb-no-brokers').show();
                        console.log('9. No brokers linked to this task. Showing empty state.');
                    }

                    // Quick Connect Button handler
                    $('#vbb-connect-more-btn, #vbb-empty-connect-btn').off('click').on('click', function () {
                        hideModal();
                        console.log('[Brokers Breakdown] Triggering Connect Broker for Task #' + taskId);
                        $(`.edit-task-broker[data-id="${taskId}"]`).first().trigger('click');
                    });

                    console.groupEnd();
                },
                error: function (xhr, status, error) {
                    console.error('%c[Brokers Breakdown] AJAX Request FAILED!', 'background: red; color: white; font-weight: bold;', {
                        'Status': xhr.status,
                        'StatusText': xhr.statusText,
                        'ResponseText': xhr.responseText,
                        'Error': error
                    });
                    hideModal();
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ (' + xhr.status + ')',
                        text: 'فشل الاتصال بالخادم لجلب بيانات الوسطاء: ' + (xhr.responseJSON?.message || error || 'خطأ غير معروف')
                    });
                    console.groupEnd();
                }
            });
        });

        // Handler for Connect Broker (ربط وسيط)
        $(document).off('click', '.edit-task-broker').on('click', '.edit-task-broker', function (e) {
            const id = $(this).data('id');
            console.group('%c[Connect Broker] Clicked! Task ID: ' + id, 'background: #198754; color: white; padding: 3px 8px; border-radius: 4px; font-weight: bold;');
            console.log('Task ID:', id);

            const apiBase = typeof baseUrl !== 'undefined' ? baseUrl : (typeof window.baseUrl !== 'undefined' ? window.baseUrl : '/');
            const targetUrl = `${apiBase}admin/tasks/broker/edit/${id}`;
            console.log('Request URL:', targetUrl);

            $.get(targetUrl, function (data) {
                console.log('Connect Broker response:', data);
                if (data.status === 2) {
                    console.warn('Connect Broker status 2 error:', data.error);
                    if (typeof showAlert === 'function') {
                        showAlert('error', data.error);
                    } else {
                        alert(data.error);
                    }
                    console.groupEnd();
                    return;
                }
                $('#broker-task-id').val(data.data.id);
                $('#task-brokers-container').empty();

                if (typeof taskBrokerIndex !== 'undefined') {
                    taskBrokerIndex = 0;
                }

                if (data.data.brokers && data.data.brokers.length > 0) {
                    data.data.brokers.forEach(broker => {
                        if (typeof createTaskBrokerRow === 'function') {
                            const idx = typeof taskBrokerIndex !== 'undefined' ? taskBrokerIndex : 0;
                            let $row = $(createTaskBrokerRow(idx));
                            $row.find('[name="brokers['+idx+'][broker_id]"]').val(broker.pivot.broker_id);
                            $row.find('[name="brokers['+idx+'][commission_type]"]').val(broker.pivot.commission_type);
                            $row.find('[name="brokers['+idx+'][commission_value]"]').val(broker.pivot.commission_value);
                            $('#task-brokers-container').append($row);
                            const $select = $row.find('.broker-select');
                            $select.wrap('<div class="position-relative"></div>').select2({
                                dropdownParent: $select.parent(),
                                width: '100%'
                            });
                            if (typeof taskBrokerIndex !== 'undefined') taskBrokerIndex++;
                        }
                    });
                }

                if (typeof $ !== 'undefined' && typeof $('#brokerModal').modal === 'function') {
                    console.log('Opening #brokerModal via jQuery');
                    $('#brokerModal').modal('show');
                } else {
                    const bEl = document.getElementById('brokerModal');
                    if (bEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        console.log('Opening #brokerModal via bootstrap.Modal');
                        bootstrap.Modal.getOrCreateInstance(bEl).show();
                    }
                }
                $('#brokerTitle').html(`{{ __('Connect Broker') }}: <span class="bg-info text-white px-2 rounded">#${id}</span>`);
                console.groupEnd();
            }).fail(function (xhr, status, error) {
                console.error('Connect Broker AJAX FAILED:', xhr.status, xhr.responseText);
                console.groupEnd();
            });
        });
        } // end runBrokersSystem
    </script>
@endsection
@section('task-isactive')
    active
@endsection
@section('navbar-custom-nav')

    <!-- Toggle Buttons -->
    <div class="btn-group me-3 my-2" role="group" aria-label="Map and Table toggle">
        <a href="{{ route('tasks.tasks') }}" class="btn btn-outline-secondary" title="{{ __('View Map Layout') }}">
            <i class="fas fa-map-marked-alt mx-1"></i> {{ __('Map') }}
        </a>
        <a href="{{ route('tasks.list') }}" class="btn btn-secondary" title="{{ __('view Table layout') }}">
            <i class="fas fa-table mx-1"></i> {{ __('Table') }}
        </a>
    </div>

    <!-- Filters Section -->
    <div class="d-flex flex-wrap align-items-center gap-2 my-2">
        <!-- Date Range -->
        <div>
            <input type="text" id="dateRange" class="form-control" placeholder="{{ __('Select Date Range') }}">
        </div>

        <!-- Owner Type Dropdown -->
        <div>
            <select class="form-select" id="owner-fillter">
                <option value="">{{ __('All') }}</option>
                <option value="admin">{{ __('Admin') }}</option>
                <option value="customer">{{ __('Customer') }}</option>
            </select>
        </div>

        <!-- Teams Dropdown -->
        <div>
            <select class="form-select task-teams-select2" id="team-fillter">
                <option value="">{{ __('All Teams') }}</option>
                @foreach ($teams as $key)
                    <option value="{{ $key->id }}">{{ $key->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Drivers Dropdown -->
        <div>
            <select class="form-select task-drivers-select2" id="driver-fillter">
                <option value="">{{ __('All Driver') }}</option>
                {{-- Populate via JS if needed --}}
            </select>
        </div>

        <!-- Go-Tech Funding Filter -->
        <div>
            <select class="form-select" id="gotech-fillter">
                <option value="">{{ __('تمويل Go-Tech (الكل)') }}</option>
                <option value="linked">{{ __('مربوطة بـ Go-Tech (الكل)') }}</option>
                <option value="pending">{{ __('Go-Tech: قيد التمويل (لم تمول)') }}</option>
                <option value="funded">{{ __('Go-Tech: تم التمويل بنجاح') }}</option>
                <option value="not_linked">{{ __('غير معروضة في Go-Tech') }}</option>
            </select>
        </div>

    </div>



@endsection
@section('content')
    <!-- Ø®Ø§Ø±Ø¬ Ø§Ù„Ù€ navbar (Ø£Ø³ÙÙ„Ù‡Ø§ Ù…Ø¨Ø§Ø´Ø±Ø©) -->
    <div id="mobile-custom-nav" class="d-lg-none  z-1 card shadow mb-3 p-2" style="white-space: nowrap;">
    </div>
    <!-- /Search -->
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="card-title mb-0">{{ __('Tasks') }}</h5>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Button when 1 task is selected: Broadcast WhatsApp notification to eligible drivers of same vehicle -->
                <button type="button" class="btn btn-success text-white shadow-sm"
                    id="broadcastTaskWhatsapp" style="display: none; background-color: #198754;">
                    <i class="ti ti-brand-whatsapp me-1 fs-5"></i> {{ __('إرسال إشعار للسائقين (واتساب)') }}
                </button>

                <!-- Button when tasks are selected: Share selected -->
                <button type="button" class="btn btn-brand-whatsapp text-white share-selected-whatsapp shadow-sm"
                    id="shareSelectedWhatsapp" style="background-color: #25D366; display: none;">
                    <i class="ti ti-share me-1"></i> {{ __('Share Selected') }}
                </button>

                <!-- Button: Open Go-Tech Funding Modal -->
                @canany(['submit_tasks_gotech', 'edit_tasks'])
                <button type="button" class="btn btn-primary text-white shadow-sm waves-effect waves-light"
                    id="btnOpenGoTechFundingModal">
                    <i class="ti ti-cash me-1 fs-5"></i> {{ __('طرح مهام للتمويل (Go-Tech)') }}
                </button>
                @endcanany

                @if(auth()->check() && auth()->user()->email === 'osama.samomy@gmail.com')
                    <button type="button" class="btn btn-warning waves-effect waves-light" onclick="openInvestmentConflictsModal()">
                        <i class="ti ti-alert-triangle me-1"></i> فحص تعارض الاستثمار
                    </button>
                @endif
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-tasks table ">
                <thead class="border ">
                    <tr>
                        <th></th>
                        <th class="px-2 select-checkbox-header"><input type="checkbox" class="form-check-input select-all"
                                id="selectAll"></th>
                        <th>{{ __('task id') }}</th>
                        <th>{{ __('customer task no') }}</th>
                        <th>{{ __('order id') }}</th>
                        <th>{{ __('price') }}</th>
                        <th>{{ __('Driver price') }}</th>
                        <th>{{ __('team') }}</th>
                        <th>{{ __('driver') }}</th>
                        <th>{{ __('vehicle') }}</th>
                        <th>{{ __('owner') }}</th>
                        <th>{{ __('address') }}</th>
                        <th>{{ __('start before') }}</th>
                        <th>{{ __('complete before') }}</th>
                        <th>{{ __('status') }}</th>
                        <th>{{ __('payment') }}</th>
                        <th>{{ __('closed') }}</th>
                        <th>{{ __('action') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade " id="paymentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignTitle">{{ __('Assign Task') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="add-new-user pt-0 payment_submit payment_form" method="POST"
                    action="{{ route('payment.initiate') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="col-xl-12">
                            <div class="nav-align-top">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <input type="hidden" name="id" id="task-payment-id">
                                        <input type="hidden" name="commission" id="task-payment-commission">
                                        <input type="hidden" name="total" id="task-payment-total">
                                        <p>{{ __('You Need to Pay: ') }}</p>
                                        <h4 id="pay-price"> </h4>
                                        <span class="id-error text-danger text-error"></span>
                                        <div class="mb-4">
                                            <label class="form-label" for="task-payment-method">*
                                                {{ __('Payment Method') }}</label>
                                            <select name="payment_method" id="task-payment-method" class="form-select">
                                                <option value="hyperpay_mada">{{ __('Mada') }}</option>
                                                <option value="credit">{{ __('Credit Card') }}</option>
                                                <option value="banking">{{ __('Bank transfer') }}</option>
                                                <option value="wallet" id="wallet-option">{{ __('Use your Wallet') }}
                                                </option>
                                                @if(\App\Services\MtahdService::isServiceEnabled())
                                                    <option value="mtahd">{{ __('Mtahd Escrow (متعهد - ضمان مالي)') }}</option>
                                                @endif
                                                <option value="cash">{{ __('Cash On Delivery') }}</option>
                                            </select>
                                            <span class="payment_method-error text-danger text-error"></span>
                                        </div>
                                        <div class="mb-4" id="mtahd-section" style="display: none">
                                            <div class="alert alert-primary d-flex align-items-center mb-0" role="alert">
                                                <i class="ti ti-shield-check fs-2 me-2"></i>
                                                <div>
                                                    <h6 class="alert-heading mb-1 fw-bold">{{ __('خدمة الضمان المالي (متعهد)') }}</h6>
                                                    <p class="mb-0 small">{{ __('سيتم إنشاء صفقة ضمان مالي للمهمة وتوليد رابط سداد آمن للعميل في متعهد.') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-4" id="receipt-section" style="display: none">
                                            <div class="form-group mb-3">
                                                <label class="form-label" for="receipt_number">*
                                                    {{ __('Receipt Number') }}</label>
                                                <input type="text" name="receipt_number" id="receipt_number"
                                                    class="form-control" placeholder="{{ __('Receipt Number') }}">
                                                <span class="receipt_number-error text-danger text-error"></span>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="form-label" for="receipt_image">*
                                                    {{ __('Receipt Image') }}</label>
                                                <input type="file" name="receipt_image" id="receipt_image"
                                                    class="form-control">
                                                <span class="receipt_image-error text-danger text-error"></span>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label class="form-label" for="receipt_image">*
                                                    {{ __('Receipt Note') }}</label>
                                                <textarea name="note" id="receipt_note" cols="30" rows="5"
                                                    class="form-control"></textarea>

                                                <span class="receipt_image-error text-danger text-error"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">{{ __('Submit') }}</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <div class="modal fade " id="checkPaymentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignTitle">{{ __('Check Payment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="add-new-user pt-0 payment_submit payment_form" method="POST"
                    action="{{ route('payment.initiate') }}">
                    @csrf
                    <div class="modal-body">
                        <div id="checkPaymentContainer">

                        </div>

                    </div>

                </form>

            </div>
        </div>
    </div>


    <div class="modal fade " id="closedModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="closeTitle">{{ __('Close Task') }} <span id="modelTitle"
                            class="bg-success text-white rounded p-0 px-2 "></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('tasks.close') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="col-xl-12">
                            <div class="nav-align-top">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <input type="hidden" name="id" id="task-id">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            {{ __('You need to upload the delivery note and optionally provide delivery number to close this task') }}
                                        </div>
                                        <span class="id-error text-danger text-error"></span>

                                        <!-- Ø­Ù‚Ù„ Ø±Ù‚Ù… Ù…Ø°ÙƒØ±Ø© Ø§Ù„ØªÙˆØµÙŠÙ„ -->
                                        <div class="form-group mb-3">
                                            <label for="delivery_number" class="form-label">
                                                <i class="fas fa-hashtag me-1"></i>
                                                {{ __('Delivery Number') }} ({{ __('Optional') }})
                                            </label>
                                            <input type="text" name="delivery_number" class="form-control"
                                                id="delivery_number"
                                                placeholder="{{ __('Enter delivery number if available') }}">
                                            <span class="delivery_number-error text-danger text-error"></span>
                                        </div>

                                        <!-- Ø­Ù‚Ù„ Ù…Ù„Ù Ù…Ø°ÙƒØ±Ø© Ø§Ù„ØªÙˆØµÙŠÙ„ -->
                                        <!-- Ø­Ù‚Ù„ Ù…Ù„Ù  Ù…Ø°ÙƒØ±Ø© Ø§Ù„ØªÙˆØµÙŠÙ„ -->
                                        <div class="form-group mb-3">
                                            <label for="delivery_note" class="form-label">
                                                <i class="fas fa-file-upload me-1"></i>
                                                * {{ __('Delivery Note File') }}
                                            </label>
                                            <input type="file" name="delivery_note[]" class="form-control"
                                                id="delivery_note" multiple
                                                accept="image/png, image/gif, image/jpeg, image/jpg, application/pdf,.doc,.docx,.txt,.csv"
                                                required>
                                            <div class="form-text text-muted mt-1">
                                                <small>
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    {{ __('Supported formats: Images (JPEG, PNG, WebP), Documents (PDF, DOC, DOCX), Text files (TXT, CSV). Max size: 10MB') }}
                                                </small>
                                            </div>
                                            <span class="delivery_note-error text-danger text-error"></span>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">{{ __('Submit') }}</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <div class="modal fade " id="refundModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog " role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="closeTitle">{{ __('Refund Task') }} <span id="modelRefundTitle"
                            class="bg-success text-white rounded p-0 px-2 "></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('tasks.refund') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="col-xl-12">
                            <div class="nav-align-top">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <input type="hidden" name="id" id="task-refund-id">
                                        <span class="id-error text-danger text-error"></span>
                                        <!-- Ø­Ù‚Ù„ Ø±Ù‚Ù… Ù…Ø°ÙƒØ±Ø© Ø§Ù„ØªÙˆØµÙŠÙ„ -->
                                        <div class="form-group mb-3">
                                            <label for="reason" class="form-label">
                                                *
                                                {{ __('Refund Reason') }}
                                            </label>
                                            <textarea name="resone" class="form-control" id="reason"
                                                placeholder="{{ __('Enter The reason to refund this task') }}"></textarea>
                                            <span class="resone-error text-danger text-error"></span>
                                        </div>
                                        <div class="alert alert-warning shadow-sm border-0 rounded-3">
                                            <h5 class="fw-bold">
                                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                {{ __('Important Notice Before Requesting a Refund') }}
                                            </h5>
                                            <p class="mb-2">
                                                {{ __('When you request a refund for this task, the following actions will be performed automatically:') }}
                                            </p>
                                            <ul class="mb-3">
                                                <li>{{ __('The task will be canceled and its status will be changed to') }}
                                                    <strong>{{ __('Canceled') }}</strong>.
                                                </li>
                                                <li>{{ __('Any advertisements or offers related to the task will be deleted.') }}
                                                </li>
                                                <li>{{ __('All wallet and financial transactions linked to this task will be removed.') }}
                                                </li>
                                                <li>{{ __('Payments and the payment receipt image (if any) will be deleted.') }}
                                                </li>
                                                <li>{{ __('The assigned driver will be unassigned, and any delivery notes or delivery numbers will be cleared.') }}
                                                </li>
                                                <li>{{ __('The action will be recorded in the taskâ€™s activity history (History Log).') }}
                                                </li>
                                                <li>{{ __('Notifications will be sent to the user, the customer, and the driver about the refund.') }}
                                                </li>
                                            </ul>
                                            <div class="alert alert-danger p-2 rounded-3">
                                                <strong>{{ __('Note:') }}</strong>
                                                {{ __('Once the refund is confirmed, it cannot be undone.') }}
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">{{ __('Submit') }}</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Broker Modal -->
    <div class="modal fade " id="brokerModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="brokerTitle">{{ __('Connect Broker') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('admin.tasks.broker.update') }}" method="POST" id="brokerForm"
                    class="form_submit card shadow-sm p-4 border-0" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="broker-task-id">
                    <span class="task-error text-danger text-error"></span>

                    <div id="task-brokers-container"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="add-task-broker-row">
                        <i class="ti ti-plus me-1"></i> {{ __('Add Broker') }}
                    </button>

                    <script type="text/template" id="task-broker-row-template">
                        <div class="row broker-row mb-3 align-items-end" data-index="{index}">
                            <div class="col-md-5">
                                <label class="form-label">{{ __('Truck Broker') }}</label>
                                <select name="brokers[{index}][broker_id]" class="form-select broker-select" required>
                                    <option value="">{{ __('Select Broker') }}</option>
                                    @foreach ($brokers as $broker)
                                        <option value="{{ $broker->id }}">{{ $broker->name }} ({{ $broker->id }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Commission Type') }}</label>
                                <select name="brokers[{index}][commission_type]" class="form-select" required>
                                    <option value="percentage">{{ __('Percentage (%)') }}</option>
                                    <option value="fixed">{{ __('Fixed Amount') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Value') }}</label>
                                <input type="number" name="brokers[{index}][commission_value]" class="form-control" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-icon btn-danger remove-task-broker-row"><i class="ti ti-trash"></i></button>
                            </div>
                        </div>
                    </script>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Save changes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @can('view_task_commissions')
    <!-- View Brokers Breakdown Modal -->
    <div class="modal fade" id="viewBrokersBreakdownModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-label-primary py-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-sm bg-primary text-white rounded p-1 d-flex align-items-center justify-content-center">
                            <i class="ti ti-users fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold">
                                {{ __('وسطاء المهمة وتوزيع الحصص المالية') }} 
                                <span id="vbb-task-id-badge" class="badge bg-primary text-white ms-1 font-monospace"></span>
                            </h5>
                            <small class="text-muted" id="vbb-task-subtitle"></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Loading Spinner -->
                    <div id="vbb-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="text-muted mt-2 fw-semibold">{{ __('جاري جلب تفاصيل الوسطاء والحصص المالية...') }}</div>
                    </div>

                    <!-- Content Container -->
                    <div id="vbb-content" style="display: none;">
                        <!-- Summary Cards -->
                        <div class="row g-3 mb-4">
                            <!-- Total Price -->
                            <div class="col-6 col-md-3">
                                <div class="card bg-label-dark border border-secondary shadow-none h-100">
                                    <div class="card-body p-3 text-center">
                                        <span class="text-muted d-block small mb-1">{{ __('إجمالي السعر') }}</span>
                                        <h5 class="mb-0 fw-bold text-dark" id="vbb-total-price">0.00 ر.س</h5>
                                        <small class="text-muted" id="vbb-pricing-type"></small>
                                    </div>
                                </div>
                            </div>
                            <!-- Driver Share -->
                            <div class="col-6 col-md-3">
                                <div class="card bg-label-success border border-success shadow-none h-100">
                                    <div class="card-body p-3 text-center">
                                        <span class="text-muted d-block small mb-1">{{ __('مستحقات السائق') }}</span>
                                        <h5 class="mb-0 fw-bold text-success" id="vbb-driver-price">0.00 ر.س</h5>
                                        <small class="text-muted text-truncate d-block" id="vbb-driver-name">-</small>
                                    </div>
                                </div>
                            </div>
                            <!-- Total Brokers Share -->
                            <div class="col-6 col-md-3">
                                <div class="card bg-label-info border border-info shadow-none h-100">
                                    <div class="card-body p-3 text-center">
                                        <span class="text-muted d-block small mb-1">{{ __('عمولات الوسطاء') }}</span>
                                        <h5 class="mb-0 fw-bold text-info" id="vbb-brokers-share">0.00 ر.س</h5>
                                        <small class="text-info fw-semibold" id="vbb-brokers-count-text">0 وسطاء</small>
                                    </div>
                                </div>
                            </div>
                            <!-- Platform Remaining -->
                            <div class="col-6 col-md-3">
                                <div class="card bg-label-primary border border-primary shadow-none h-100">
                                    <div class="card-body p-3 text-center">
                                        <span class="text-muted d-block small mb-1">{{ __('صافي المنصة المتبقي') }}</span>
                                        <h5 class="mb-0 fw-bold text-primary" id="vbb-platform-remaining">0.00 ر.س</h5>
                                        <small class="text-muted" id="vbb-platform-gross-text"></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Financial Split Breakdown Bar -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold small text-muted">{{ __('توزيع قيمة المهمة:') }}</span>
                                <div class="d-flex gap-3 small">
                                    <span class="text-success"><i class="ti ti-circle-filled fs-6"></i> {{ __('السائق') }}</span>
                                    <span class="text-info"><i class="ti ti-circle-filled fs-6"></i> {{ __('الوسطاء') }}</span>
                                    <span class="text-primary"><i class="ti ti-circle-filled fs-6"></i> {{ __('المنصة') }}</span>
                                </div>
                            </div>
                            <div class="progress" style="height: 12px;" id="vbb-progress-bar">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 0%" id="vbb-bar-driver" title="السائق"></div>
                                <div class="progress-bar bg-info" role="progressbar" style="width: 0%" id="vbb-bar-brokers" title="الوسطاء"></div>
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 0%" id="vbb-bar-platform" title="المنصة"></div>
                            </div>
                        </div>

                        <!-- Brokers Table Header & Action -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-1">
                                <i class="ti ti-users-group text-primary"></i>
                                {{ __('قائمة الوسطاء المرتبطين بالمهمة') }}
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="vbb-connect-more-btn">
                                <i class="ti ti-edit me-1"></i> {{ __('تعديل / ربط وسطاء') }}
                            </button>
                        </div>

                        <!-- Brokers Table -->
                        <div class="table-responsive border rounded mb-3">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('الوسيط') }}</th>
                                        <th>{{ __('مصدر الارتباط') }}</th>
                                        <th>{{ __('صيغة العمولة') }}</th>
                                        <th>{{ __('المبلغ المستحق') }}</th>
                                        <th>{{ __('حالة الإيداع') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="vbb-brokers-table-body">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State for No Brokers -->
                        <div id="vbb-no-brokers" class="alert alert-warning border border-warning d-flex align-items-center justify-content-between p-3" style="display: none;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-info-circle fs-3 text-warning"></i>
                                <div>
                                    <div class="fw-bold">{{ __('لا يوجد وسطاء مرتبطين بهذه المهمة حالياً') }}</div>
                                    <small class="text-muted">{{ __('كامل عمولة المهمة تؤول للمنصة مباشرة دون استقطاع لوسطاء.') }}</small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" id="vbb-empty-connect-btn">
                                <i class="ti ti-plus me-1"></i> {{ __('ربط وسيط الآن') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-2">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('إغلاق') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endcan

    <!-- Payment Request Modal -->
    <div class="modal fade" id="paymentRequestModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Payment Request') }} <span id="paymentRequestTaskId"
                            class="bg-info text-white rounded p-1 px-2"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Task Information Section -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">{{ __('Task Information') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">{{ __('Task ID') }}:</label>
                                        <span id="taskInfoId" class="text-primary fw-bold"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">{{ __('Driver Amount') }}:</label>
                                        <span id="taskInfoDriverAmount" class="text-success fw-bold"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">{{ __('Task Owner') }}:</label>
                                        <span id="taskInfoOwner"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">{{ __('Pickup Address') }}:</label>
                                        <span id="taskInfoPickup" class="text-muted"></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">{{ __('Delivery Address') }}:</label>
                                        <span id="taskInfoDelivery" class="text-muted"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Request Form Section -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">{{ __('Payment Request Form') }}</h6>
                                </div>
                                <div class="card-body">
                                    <form id="paymentRequestForm">
                                        <input type="hidden" id="paymentRequestTaskIdInput" name="task_id">

                                        <div class="mb-3">
                                            <label class="form-label" for="requestedAmount">*
                                                {{ __('Requested Amount') }}</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control" id="requestedAmount"
                                                    name="requested_amount" required>
                                                <span class="input-group-text">{{ __('SAR') }}</span>
                                            </div>
                                            <div class="form-text">
                                                <small class="text-muted">{{ __('Maximum amount') }}: <span id="maxAmount"
                                                        class="text-primary fw-bold"></span></small>
                                            </div>
                                            <span class="requested_amount-error text-danger text-error"></span>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="paymentRecipient">*
                                                {{ __('Payment Recipient') }}</label>
                                            <select class="form-select" id="paymentRecipient" name="payment_recipient"
                                                required>
                                                <option value="">{{ __('Select Recipient') }}</option>
                                                <option value="driver">{{ __('Driver') }}</option>
                                                <option value="team_leader">{{ __('Team Leader') }}</option>
                                            </select>
                                            <span class="payment_recipient-error text-danger text-error"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="bankName">* {{ __('Bank Name') }}</label>

                                            <select name="bank_name" id="bankName" class="form-select">
                                                <option value="">{{ __('Select Bank') }}</option>
                                                <option value="Ø§Ù„Ø¨Ù†Ùƒ Ø§Ù„Ø£Ù‡Ù„ÙŠ Ø§Ù„Ø³Ø¹ÙˆØ¯ÙŠ">Ø§Ù„Ø¨Ù†Ùƒ
                                                    Ø§Ù„Ø£Ù‡Ù„ÙŠ Ø§Ù„Ø³Ø¹ÙˆØ¯ÙŠ
                                                </option>
                                                <option value="Ø¨Ù†Ùƒ Ø§Ù„Ø±Ø§Ø¬Ø­ÙŠ">Ø¨Ù†Ùƒ Ø§Ù„Ø±Ø§Ø¬Ø­ÙŠ</option>
                                                <option value="Ø¨Ù†Ùƒ Ø§Ù„Ø±ÙŠØ§Ø¶">Ø¨Ù†Ùƒ Ø§Ù„Ø±ÙŠØ§Ø¶</option>
                                                <option value="Ø§Ù„Ø¨Ù†Ùƒ Ø§Ù„Ø³Ø¹ÙˆØ¯ÙŠ Ù„Ù„Ø§Ø³ØªØ«Ù…Ø§Ø±">Ø§Ù„Ø¨Ù†Ùƒ
                                                    Ø§Ù„Ø³Ø¹ÙˆØ¯ÙŠ
                                                    Ù„Ù„Ø§Ø³ØªØ«Ù…Ø§Ø±</option>
                                                <option value="Ø§Ù„Ø¨Ù†Ùƒ Ø§Ù„Ø³Ø¹ÙˆØ¯ÙŠ Ø§Ù„ÙØ±Ù†Ø³ÙŠ">Ø§Ù„Ø¨Ù†Ùƒ
                                                    Ø§Ù„Ø³Ø¹ÙˆØ¯ÙŠ
                                                    Ø§Ù„ÙØ±Ù†Ø³ÙŠ</option>
                                                <option value="Ø§Ù„Ø¨Ù†Ùƒ Ø§Ù„Ø³Ø¹ÙˆØ¯ÙŠ Ø§Ù„Ø¨Ø±ÙŠØ·Ø§Ù†ÙŠ">Ø§Ù„Ø¨Ù†Ùƒ
                                                    Ø§Ù„Ø³Ø¹ÙˆØ¯ÙŠ
                                                    Ø§Ù„Ø¨Ø±ÙŠØ·Ø§Ù†ÙŠ (Ø³Ø§Ø¨)</option>
                                                <option value="Ø¨Ù†Ùƒ Ø§Ù„Ø¹Ø±Ø¨ÙŠ Ø§Ù„ÙˆØ·Ù†ÙŠ">Ø¨Ù†Ùƒ Ø§Ù„Ø¹Ø±Ø¨ÙŠ
                                                    Ø§Ù„ÙˆØ·Ù†ÙŠ
                                                </option>
                                                <option value="Ø¨Ù†Ùƒ Ø³Ø§Ù…Ø¨Ø§">Ø¨Ù†Ùƒ Ø³Ø§Ù…Ø¨Ø§</option>
                                                <option value="Ø§Ù„Ø¨Ù†Ùƒ Ø§Ù„Ø£ÙˆÙ„">Ø§Ù„Ø¨Ù†Ùƒ Ø§Ù„Ø£ÙˆÙ„</option>
                                                <option value="Ø¨Ù†Ùƒ Ø§Ù„Ø¬Ø²ÙŠØ±Ø©">Ø¨Ù†Ùƒ Ø§Ù„Ø¬Ø²ÙŠØ±Ø©</option>
                                                <option value="Ø¨Ù†Ùƒ Ø§Ù„Ø¥Ù†Ù…Ø§Ø¡">Ø¨Ù†Ùƒ Ø§Ù„Ø¥Ù†Ù…Ø§Ø¡</option>
                                                <option value="Ø§Ù„Ø¨Ù†Ùƒ Ø§Ù„Ø¹Ø±Ø¨ÙŠ">Ø§Ù„Ø¨Ù†Ùƒ Ø§Ù„Ø¹Ø±Ø¨ÙŠ</option>
                                                <option value="other">{{ __('Other') }}</option>
                                            </select>
                                            <input type="text" class="form-control mt-2" id="customBankName"
                                                name="custom_bank_name" placeholder="{{ __('Enter bank name') }}"
                                                style="display: none;">
                                            <span class="bank_name-error text-danger text-error"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="accountNumber">*
                                                {{ __('Account Number') }}</label>
                                            <input type="text" class="form-control" id="accountNumber" name="account_number"
                                                placeholder="1234567890" minlength="8" required>
                                            <span class="account_number-error text-danger text-error"></span>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label" for="ibanNumber">* {{ __('IBAN Number') }}</label>
                                            <input type="text" class="form-control" id="ibanNumber" name="iban_number"
                                                placeholder="SA12 3456 7890 1234 5678 90" maxlength="29" required>
                                            <div class="form-text">
                                                <small class="text-muted">{{ __('Format: SA + 22 digits') }}</small>
                                            </div>
                                            <span class="iban_number-error text-danger text-error"></span>
                                        </div>


                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-primary"
                        id="generatePaymentRequest">{{ __('Generate Payment Request') }}</button>
                </div>
            </div>
        </div>
    </div>



    <!-- Modal: Broadcast Task to Eligible Drivers via WhatsApp -->
    <div class="modal fade" id="broadcastTaskDriversModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white py-3" style="background-color: #128C7E;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-brand-whatsapp fs-2"></i>
                        <div>
                            <h5 class="modal-title text-white mb-0 fw-bold">
                                {{ __('إرسال إشعار المهمة للسائقين عبر واتساب') }}
                            </h5>
                            <small class="text-white-50">
                                {{ __('المهمة:') }} <span id="bt_modal_task_number" class="fw-bold text-white">#0</span>
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Loading Spinner -->
                    <div id="bt_modal_loader" class="text-center py-5">
                        <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;"></div>
                        <p class="mt-3 text-muted fw-semibold">{{ __('جاري البحث عن السائقين المعتمدين والمتاحين لنفس نوع المركبة...') }}</p>
                    </div>

                    <!-- Main Content (hidden while loading) -->
                    <div id="bt_modal_content" style="display: none;">
                        <!-- Task Summary Card -->
                        <div class="card bg-label-secondary border-0 mb-3">
                            <div class="card-body p-3">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-5">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-success p-2 rounded-circle">
                                                <i class="ti ti-truck fs-5"></i>
                                            </span>
                                            <div>
                                                <small class="text-muted d-block">{{ __('نوع وحجم المركبة المطلوبة') }}</small>
                                                <span class="fw-bold text-dark fs-6" id="bt_task_vehicle">-</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-7">
                                        <div class="d-flex align-items-center justify-content-between bg-white p-2 rounded border">
                                            <div class="text-start">
                                                <small class="text-muted d-block"><i class="ti ti-map-pin text-success me-1"></i>{{ __('من / الاستلام') }}</small>
                                                <span class="fw-bold small" id="bt_task_from">-</span>
                                            </div>
                                            <i class="ti ti-arrow-left text-muted fs-4"></i>
                                            <div class="text-end">
                                                <small class="text-muted d-block"><i class="ti ti-map-pin text-danger me-1"></i>{{ __('إلى / التسليم') }}</small>
                                                <span class="fw-bold small" id="bt_task_to">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Template Warning Alert if not set -->
                        <div id="bt_template_warning" class="alert alert-danger d-flex align-items-center p-3 mb-3" style="display: none !important;">
                            <i class="ti ti-alert-triangle fs-3 me-2"></i>
                            <div class="flex-grow-1">
                                <span class="fw-bold">{{ __('تنبيه:') }}</span>
                                {{ __('لم يتم ضبط معرّف أو اسم القالب المعتمد في الإعدادات!') }}
                                <a href="{{ route('settings.general') }}#nav-saei" target="_blank" class="text-danger text-decoration-underline fw-bold ms-1">
                                    {{ __('انقر هنا للانتقال لصفحة الإعدادات لضبطه') }}
                                </a>
                            </div>
                        </div>

                        <!-- Collapsible Message Preview -->
                        <div class="card border mb-3">
                            <div class="card-header bg-lighter py-2 px-3 d-flex justify-content-between align-items-center cursor-pointer"
                                data-bs-toggle="collapse" data-bs-target="#bt_preview_collapse">
                                <span class="fw-bold text-muted small">
                                    <i class="ti ti-eye text-primary me-1"></i>{{ __('معاينة نص الرسالة والقالب المعتمد (Meta WhatsApp Template)') }}
                                </span>
                                <i class="ti ti-chevron-down text-muted"></i>
                            </div>
                            <div class="collapse" id="bt_preview_collapse">
                                <div class="card-body p-3 bg-light">
                                    <div class="p-3 bg-white rounded border border-success" style="font-family: inherit; font-size: 13px; line-height: 1.6; border-right: 4px solid #25D366 !important;">
                                        <div class="text-dark fw-bold mb-1">🔔 لديك مهمة جديدة متاحة للتنفيذ.</div>
                                        <div class="text-muted small mb-2">You have a new task available for execution.</div>
                                        <div class="mb-2">🚚 <span id="bt_preview_var1" class="badge bg-label-success fw-bold">-</span></div>
                                        <div class="mb-1">📅 من / From : <span id="bt_preview_var2" class="fw-bold text-primary">-</span></div>
                                        <div class="mb-3">📅 إلى / To : <span id="bt_preview_var3" class="fw-bold text-danger">-</span></div>
                                        <div class="text-dark fw-bold">🚀 افتح التطبيق الآن للاطلاع على تفاصيل المهمة.</div>
                                        <div class="text-muted small mb-2">🚀 Open the app now to view the task details.</div>
                                        <div class="small text-muted border-top pt-2 mt-2">
                                            حمّل التطبيق الآن وابدأ باستخدام خدماتنا.<br>
                                            <strong>وجهات آمنة لخدمات النقل اللوجستي</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Custom Additional Phone Input Card -->
                        <div class="card border mb-3 bg-light">
                            <div class="card-body p-3">
                                <label for="bt_custom_phone" class="form-label fw-bold text-dark d-flex align-items-center gap-1 mb-1">
                                    <i class="ti ti-phone-plus text-success fs-5"></i>
                                    {{ __('إرسال إلى رقم خاص / إضافي (اختياري)') }}
                                </label>
                                <div class="input-group input-group-merge">
                                    <span class="input-group-text bg-white"><i class="ti ti-brand-whatsapp text-success"></i></span>
                                    <input type="text" class="form-control bg-white" id="bt_custom_phone"
                                        dir="ltr"
                                        placeholder="05XXXXXXXX أو 9665XXXXXXXX">
                                </div>
                                <small class="text-muted d-block mt-1">
                                    {{ __('يمكنك إدخال رقم هاتف إضافي لإرسال إشعار المهمة إليه عبر واتساب (سواء مع السائقين المحددين أو بشكل منفرد).') }}
                                </small>
                            </div>
                        </div>

                        <!-- Drivers Controls Bar -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="bt_select_all" checked>
                                    <label class="form-check-label fw-bold text-dark" for="bt_select_all">
                                        {{ __('تحديد الكل') }}
                                    </label>
                                </div>
                                <span class="badge bg-label-primary px-2 py-1" id="bt_counter_badge">
                                    {{ __('تم تحديد') }} <strong id="bt_selected_count">0</strong> {{ __('من أصل') }} <strong id="bt_total_count">0</strong> {{ __('سائق') }}
                                </span>
                            </div>
                            <div style="max-width: 260px;">
                                <div class="input-group input-group-merge input-group-sm">
                                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                                    <input type="text" class="form-control" id="bt_driver_search" placeholder="{{ __('بحث بالاسم أو الجوال...') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Eligible Drivers Table -->
                        <div class="table-responsive border rounded" style="max-height: 320px;">
                            <table class="table table-hover table-sm align-middle mb-0" id="bt_drivers_table">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">#</th>
                                        <th>{{ __('السائق') }}</th>
                                        <th>{{ __('المركبة') }}</th>
                                        <th>{{ __('رقم الواتساب') }}</th>
                                        <th>{{ __('الفريق') }}</th>
                                        <th class="text-center">{{ __('الحالة') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="bt_drivers_tbody">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div id="bt_empty_state" class="text-center py-4 text-muted" style="display: none;">
                            <i class="ti ti-user-x fs-1 text-secondary mb-2 d-block"></i>
                            <h6 class="fw-bold mb-1">{{ __('لا يوجد سائقين مؤهلين متاحين حالياً') }}</h6>
                            <p class="small mb-0">{{ __('لم يتم العثور على سائقين معتمدين بنفس نوع المركبة وليس لديهم مهام نشطة.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-light d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        {{ __('Cancel') }}
                    </button>
                    <button type="button" class="btn btn-success" id="btnConfirmBroadcastTaskWhatsapp" style="background-color: #198754;" disabled>
                        <i class="ti ti-brand-whatsapp me-1 fs-5"></i>
                        {{ __('إرسال الإشعار عبر واتساب') }} (<span id="bt_btn_count">0</span>)
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('admin.tasks.from-modal', ['hasTaskBrokerTemplate' => true])

    @if(auth()->check() && auth()->user()->email === 'osama.samomy@gmail.com')
        <div class="modal fade" id="investmentConflictsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title text-white"><i class="ti ti-alert-triangle me-2"></i> فحص تعارض الاستثمار</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-4">
                        <div class="alert alert-info">
                            يعرض هذا الجدول المهام التي تم فك ارتباطها بمستثمر ولكن حالة الدفع للاستثمار فيها لازالت مسجلة كـ
                            "مدفوعة".
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="conflictsTable">
                                <thead>
                                    <tr>
                                        <th>{{ __('Task Number') }}</th>
                                        <th>{{ __('Customer') }}</th>
                                        <th>حالة المهمة</th>
                                        <th>إجمالي التكلفة</th>
                                        <th>إجراء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded here via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="button" class="btn btn-success" id="fixAllConflictsBtn" style="display: none;"
                            onclick="fixInvestmentConflict('all')">
                            <i class="ti ti-tool me-1"></i> إصلاح الكل
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function openInvestmentConflictsModal() {
                const tbody = document.querySelector('#conflictsTable tbody');
                tbody.innerHTML = '<tr><td colspan="5" class="text-center"><div class="spinner-border text-primary" role="status"></div> جاري التحميل...</td></tr>';
                const modal = new bootstrap.Modal(document.getElementById('investmentConflictsModal'));
                modal.show();

                fetch('{{ route('tasks.investment_conflicts.data') }}')
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 1) {
                            tbody.innerHTML = '';
                            if (data.tasks.length === 0) {
                                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-success fw-bold">لا يوجد أي تعارضات حالياً!</td></tr>';
                                document.getElementById('fixAllConflictsBtn').style.display = 'none';
                            } else {
                                data.tasks.forEach(task => {
                                    const tr = document.createElement('tr');
                                    const cName = task.customer ? task.customer.name : '-';
                                    tr.innerHTML =
                                        '<td>#' + task.id + '</td>' +
                                        '<td>' + cName + '</td>' +
                                        '<td><span class="badge bg-label-primary">' + task.status + '</span></td>' +
                                        '<td>' + task.total_price + ' ر.س</td>' +
                                        '<td>' +
                                        '<button class="btn btn-sm btn-success" onclick="fixInvestmentConflict(' + task.id + ')">' +
                                        'إصلاح' +
                                        '</button>' +
                                        '</td>';
                                    tbody.appendChild(tr);
                                });
                                document.getElementById('fixAllConflictsBtn').style.display = 'block';
                            }
                        } else {
                            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">' + (data.message || '{{ __('Error') }}') + '</td></tr>';
                        }
                    })
                    .catch(err => {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">حدث خطأ في الاتصال.</td></tr>';
                    });
            }

            function fixInvestmentConflict(taskId) {
                Swal.fire({
                    target: document.getElementById('investmentConflictsModal'),
                    title: 'تأكيد الإصلاح',
                    text: "أدخل كلمة المرور الخاصة بك للتأكيد:",
                    input: 'password',
                    inputAttributes: {
                        autocapitalize: 'off',
                        required: 'true'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'تأكيد وإصلاح',
                    cancelButtonText: '{{ __('Cancel') }}',
                    showLoaderOnConfirm: true,
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('يرجى إدخال كلمة المرور');
                            return false;
                        }
                        return fetch('{{ route('tasks.investment_conflicts.fix') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ task_id: taskId, password: password })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status !== 1) {
                                    throw new Error(data.message || 'حدث خطأ');
                                }
                                return data;
                            })
                            .catch(error => {
                                Swal.showValidationMessage('خطأ: ' + error.message);
                            });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'نجاح!',
                            text: result.value.message
                        });
                        openInvestmentConflictsModal(); // Refresh list
                    }
                });
            }
        </script>
    @endif

    <!-- ============================================================== -->
    <!-- Modal: طرح المهام للتمويل في Go-Tech                          -->
    <!-- ============================================================== -->
    <div class="modal fade" id="gotechFundingModal" tabindex="-1" aria-labelledby="gotechFundingModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm bg-white text-primary rounded d-flex align-items-center justify-content-center p-2">
                            <i class="ti ti-cash fs-4"></i>
                        </span>
                        <div>
                            <h5 class="modal-title text-white fw-bold mb-0" id="gotechFundingModalLabel">
                                {{ __('طرح المهام للتمويل اللوجستي عبر Go-Tech') }}
                            </h5>
                            <small class="text-white-50">
                                {{ __('عرض المهام المؤهلة للعملاء المصرح لهم بالاستثمار، غير المدفوعة وغير الممولة سابقاً') }}
                            </small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Filters Row -->
                    <div class="bg-light p-3 rounded border mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold mb-1 fs-6"><i class="ti ti-search me-1"></i> {{ __('بحث سريع') }}</label>
                                <input type="text" id="gotechSearchInput" class="form-control form-control-sm" placeholder="رقم المهمة، العميل، المسار...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold mb-1 fs-6"><i class="ti ti-user-check me-1"></i> {{ __('العميل') }}</label>
                                <select id="gotechCustomerFilter" class="form-select form-select-sm">
                                    <option value="">{{ __('جميع العملاء المؤهلين') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold mb-1 fs-6"><i class="ti ti-truck me-1"></i> {{ __('نوع المركبة') }}</label>
                                <select id="gotechVehicleFilter" class="form-select form-select-sm">
                                    <option value="">{{ __('جميع أنواع المركبات') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold mb-1 fs-6"><i class="ti ti-map-pin me-1"></i> {{ __('مسار الرحلة') }}</label>
                                <select id="gotechRouteFilter" class="form-select form-select-sm">
                                    <option value="">{{ __('جميع المسارات') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2 align-items-center mt-2 pt-2 border-top">
                            <div class="col-md-3 d-flex align-items-center gap-2">
                                <label class="form-label fw-bold mb-0 text-nowrap"><i class="ti ti-list-numbers me-1"></i> {{ __('لكل صفحة:') }}</label>
                                <select id="gotechPerPageSelect" class="form-select form-select-sm" style="width: 85px;">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="form-label fw-bold mb-0 text-nowrap"><i class="ti ti-clock me-1"></i> {{ __('صلاحية العرض:') }}</label>
                                    <input type="number" id="gotechDurationMinutes" class="form-control form-control-sm" value="120" min="10" max="1440" style="width: 90px;" title="مدة العرض بالدقائق في Go-Tech">
                                    <span class="text-muted small">دقيقة</span>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAllFiltered">
                                    <i class="ti ti-checks me-1"></i> {{ __('تحديد كافة المفلتر') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearAllSelections">
                                    <i class="ti ti-trash me-1"></i> {{ __('إلغاء التحديد') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetFilters">
                                    <i class="ti ti-refresh me-1"></i> {{ __('إعادة ضبط') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Bar -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2 px-1">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-label-primary fs-6 px-3 py-2">
                                <i class="ti ti-list-check me-1"></i>
                                {{ __('المهام المطابقة للفلاتر:') }} <strong id="gotechEligibleCount">0</strong>
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="text-muted fs-6">
                                {{ __('المحدد حالياً:') }} <strong id="gotechSelectedCount" class="text-success fs-5">0</strong>
                            </span>
                            <span class="text-muted fs-6">
                                {{ __('إجمالي مبلغ التمويل:') }} <strong id="gotechSelectedTotal" class="text-primary fs-5">0.00</strong> {{ __('ريال') }}
                            </span>
                        </div>
                    </div>

                    <!-- Tasks Table -->
                    <div class="table-responsive border rounded" style="min-height: 250px;">
                        <table class="table table-hover align-middle mb-0" id="gotechEligibleTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45px;" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="gotechSelectAllPage" title="تحديد / إلغاء تحديد الكل في هذه الصفحة">
                                    </th>
                                    <th>{{ __('المهمة') }}</th>
                                    <th>{{ __('العميل') }}</th>
                                    <th>{{ __('المسار') }}</th>
                                    <th>{{ __('المركبة') }}</th>
                                    <th>{{ __('سعر المهمة') }}</th>
                                    <th style="width: 150px;">{{ __('مبلغ التمويل (ر.س)') }}</th>
                                </tr>
                            </thead>
                            <tbody id="gotechEligibleTableBody">
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                        {{ __('جاري فحص وجلب المهام المؤهلة للتمويل...') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2 border-top px-1" id="gotechPaginationWrapper">
                        <div class="text-muted small" id="gotechPaginationInfo">
                            {{ __('عرض 0 إلى 0 من إجمالي 0 مهمة') }}
                        </div>
                        <nav aria-label="Tasks pagination">
                            <ul class="pagination pagination-sm mb-0 gap-1" id="gotechPaginationList">
                                <!-- Populated dynamically -->
                            </ul>
                        </nav>
                    </div>
                </div>

                <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i> {{ __('Cancel') }}
                    </button>
                    <button type="button" class="btn btn-primary px-4 fw-bold shadow" id="btnSubmitTasksToGoTech" disabled>
                        <i class="ti ti-send me-1 fs-5"></i>
                        <span id="btnSubmitTasksToGoTechText">{{ __('إرسال المهام المحددة للتمويل في Go-Tech') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script: Go-Tech Modal Handling with Pagination & Filters -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let eligibleTasks = [];
            let gotechModalInstance = null;
            let currentPage = 1;
            let perPage = 10;
            const selectedTaskIds = new Set();
            const customAmounts = {};

            const btnOpenModal = document.getElementById('btnOpenGoTechFundingModal');
            const modalEl = document.getElementById('gotechFundingModal');
            const tableBody = document.getElementById('gotechEligibleTableBody');
            const selectAllPageCheckbox = document.getElementById('gotechSelectAllPage');
            const searchInput = document.getElementById('gotechSearchInput');
            const customerFilter = document.getElementById('gotechCustomerFilter');
            const vehicleFilter = document.getElementById('gotechVehicleFilter');
            const routeFilter = document.getElementById('gotechRouteFilter');
            const perPageSelect = document.getElementById('gotechPerPageSelect');
            const durationInput = document.getElementById('gotechDurationMinutes');
            const eligibleCountEl = document.getElementById('gotechEligibleCount');
            const selectedCountEl = document.getElementById('gotechSelectedCount');
            const selectedTotalEl = document.getElementById('gotechSelectedTotal');
            const paginationInfoEl = document.getElementById('gotechPaginationInfo');
            const paginationListEl = document.getElementById('gotechPaginationList');
            const btnSubmit = document.getElementById('btnSubmitTasksToGoTech');
            const btnSubmitText = document.getElementById('btnSubmitTasksToGoTechText');
            const btnSelectAllFiltered = document.getElementById('btnSelectAllFiltered');
            const btnClearAllSelections = document.getElementById('btnClearAllSelections');
            const btnResetFilters = document.getElementById('btnResetFilters');

            if (!btnOpenModal || !modalEl) return;

            gotechModalInstance = new bootstrap.Modal(modalEl);

            btnOpenModal.addEventListener('click', function () {
                gotechModalInstance.show();
                loadEligibleTasks();
            });

            function loadEligibleTasks() {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            {{ __('جاري فحص وجلب المهام المؤهلة للتمويل من قاعدة البيانات...') }}
                        </td>
                    </tr>
                `;
                selectAllPageCheckbox.checked = false;
                selectedTaskIds.clear();
                currentPage = 1;
                btnSubmit.disabled = true;
                updateTotals();

                fetch('{{ route('tasks.gotech.eligible') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 1) {
                        eligibleTasks = data.tasks || [];
                        // Populate initial amounts
                        eligibleTasks.forEach(t => {
                            if (!customAmounts[t.id]) {
                                customAmounts[t.id] = (parseFloat(t.funding_amount) || parseFloat(t.total_price) || 100).toFixed(2);
                            }
                        });

                        populateDropdowns(data.customers || [], data.vehicles || [], data.routes || []);
                        renderTableRows();
                    } else {
                        tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">${data.message || 'فشل جلب المهام'}</td></tr>`;
                    }
                })
                .catch(err => {
                    tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">حدث خطأ أثناء الاتصال: ${err.message}</td></tr>`;
                });
            }

            function populateDropdowns(customers, vehicles, routes) {
                // Customers
                customerFilter.innerHTML = '<option value="">{{ __('جميع العملاء المؤهلين') }}</option>';
                customers.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    customerFilter.appendChild(opt);
                });

                // Vehicles
                vehicleFilter.innerHTML = '<option value="">{{ __('جميع أنواع المركبات') }}</option>';
                vehicles.forEach(v => {
                    const opt = document.createElement('option');
                    opt.value = v;
                    opt.textContent = v;
                    vehicleFilter.appendChild(opt);
                });

                // Routes
                routeFilter.innerHTML = '<option value="">{{ __('جميع المسارات') }}</option>';
                routes.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r;
                    opt.textContent = r;
                    routeFilter.appendChild(opt);
                });
            }

            function getFilteredTasks() {
                const search = (searchInput.value || '').trim().toLowerCase();
                const custId = customerFilter.value;
                const vehicleVal = vehicleFilter.value;
                const routeVal = routeFilter.value;

                return eligibleTasks.filter(t => {
                    const matchCust = !custId || String(t.customer_id) === String(custId);
                    const matchVehicle = !vehicleVal || t.vehicle_label === vehicleVal;
                    const matchRoute = !routeVal || t.route === routeVal;
                    const matchSearch = !search
                        || String(t.id).includes(search)
                        || (t.customer_task_number && t.customer_task_number.toLowerCase().includes(search))
                        || (t.customer_name && t.customer_name.toLowerCase().includes(search))
                        || (t.route && t.route.toLowerCase().includes(search))
                        || (t.pickup_address && t.pickup_address.toLowerCase().includes(search))
                        || (t.delivery_address && t.delivery_address.toLowerCase().includes(search))
                        || (t.vehicle_label && t.vehicle_label.toLowerCase().includes(search));

                    return matchCust && matchVehicle && matchRoute && matchSearch;
                });
            }

            function renderTableRows() {
                const filtered = getFilteredTasks();
                const totalFiltered = filtered.length;
                eligibleCountEl.textContent = totalFiltered;

                if (totalFiltered === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="ti ti-inbox fs-2 mb-2 d-block"></i>
                                {{ __('لا توجد أي مهام مؤهلة للتمويل مطابقة للفلاتر المحددة.') }}
                            </td>
                        </tr>
                    `;
                    selectAllPageCheckbox.checked = false;
                    selectAllPageCheckbox.disabled = true;
                    paginationInfoEl.textContent = 'عرض 0 إلى 0 من إجمالي 0 مهمة';
                    paginationListEl.innerHTML = '';
                    updateTotals();
                    return;
                }

                selectAllPageCheckbox.disabled = false;
                const totalPages = Math.ceil(totalFiltered / perPage);
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                const startIndex = (currentPage - 1) * perPage;
                const endIndex = Math.min(startIndex + perPage, totalFiltered);
                const pageTasks = filtered.slice(startIndex, endIndex);

                let html = '';
                let allPageSelected = true;

                pageTasks.forEach(task => {
                    const isSelected = selectedTaskIds.has(task.id);
                    if (!isSelected) {
                        allPageSelected = false;
                    }
                    const amount = customAmounts[task.id] || (parseFloat(task.funding_amount) || parseFloat(task.total_price) || 100).toFixed(2);

                    html += `
                        <tr data-task-id="${task.id}" class="${isSelected ? 'table-primary bg-opacity-10' : ''}">
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input gotech-task-checkbox" value="${task.id}" ${isSelected ? 'checked' : ''}>
                            </td>
                            <td>
                                <span class="fw-bold text-primary">#${task.id}</span>
                                ${task.customer_task_number ? `<br><small class="badge bg-label-info">C#${task.customer_task_number}</small>` : ''}
                            </td>
                            <td>
                                <span class="fw-semibold">${task.customer_name}</span>
                            </td>
                            <td>
                                <span class="badge bg-label-secondary text-dark">${task.route}</span>
                            </td>
                            <td>
                                <small class="text-muted">${task.vehicle_label}</small>
                            </td>
                            <td>
                                <span class="fw-bold">${(parseFloat(task.total_price) || 0).toFixed(2)} ر.س</span>
                            </td>
                            <td>
                                <input type="number" step="0.5" min="50" class="form-control form-control-sm gotech-amount-input" 
                                    data-task-id="${task.id}" value="${amount}">
                            </td>
                        </tr>
                    `;
                });

                tableBody.innerHTML = html;
                selectAllPageCheckbox.checked = allPageSelected && pageTasks.length > 0;

                // Render pagination info and buttons
                paginationInfoEl.textContent = `عرض ${startIndex + 1} إلى ${endIndex} من أصل ${totalFiltered} مهمة`;
                renderPaginationButtons(totalPages);

                bindRowEvents();
                updateTotals();
            }

            function renderPaginationButtons(totalPages) {
                if (totalPages <= 1) {
                    paginationListEl.innerHTML = '';
                    return;
                }

                let html = '';

                // Previous button
                html += `
                    <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                        <a class="page-link py-1 px-2" href="javascript:;" data-page="${currentPage - 1}">
                            <i class="ti ti-chevron-right fs-6"></i>
                        </a>
                    </li>
                `;

                // Page numbers (smart sliding window)
                let startPage = Math.max(1, currentPage - 2);
                let endPage = Math.min(totalPages, currentPage + 2);

                if (startPage > 1) {
                    html += `<li class="page-item"><a class="page-link py-1 px-2" href="javascript:;" data-page="1">1</a></li>`;
                    if (startPage > 2) {
                        html += `<li class="page-item disabled"><span class="page-link py-1 px-2">...</span></li>`;
                    }
                }

                for (let p = startPage; p <= endPage; p++) {
                    html += `
                        <li class="page-item ${p === currentPage ? 'active' : ''}">
                            <a class="page-link py-1 px-2" href="javascript:;" data-page="${p}">${p}</a>
                        </li>
                    `;
                }

                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        html += `<li class="page-item disabled"><span class="page-link py-1 px-2">...</span></li>`;
                    }
                    html += `<li class="page-item"><a class="page-link py-1 px-2" href="javascript:;" data-page="${totalPages}">${totalPages}</a></li>`;
                }

                // Next button
                html += `
                    <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                        <a class="page-link py-1 px-2" href="javascript:;" data-page="${currentPage + 1}">
                            <i class="ti ti-chevron-left fs-6"></i>
                        </a>
                    </li>
                `;

                paginationListEl.innerHTML = html;

                // Bind click events on page links
                paginationListEl.querySelectorAll('.page-link[data-page]').forEach(link => {
                    link.addEventListener('click', function (e) {
                        e.preventDefault();
                        const p = parseInt(this.getAttribute('data-page'));
                        if (p >= 1 && p <= totalPages && p !== currentPage) {
                            currentPage = p;
                            renderTableRows();
                        }
                    });
                });
            }

            function bindRowEvents() {
                document.querySelectorAll('.gotech-task-checkbox').forEach(cb => {
                    cb.addEventListener('change', function () {
                        const taskId = parseInt(this.value);
                        if (this.checked) {
                            selectedTaskIds.add(taskId);
                            this.closest('tr').classList.add('table-primary', 'bg-opacity-10');
                        } else {
                            selectedTaskIds.delete(taskId);
                            this.closest('tr').classList.remove('table-primary', 'bg-opacity-10');
                        }
                        checkPageSelectAllState();
                        updateTotals();
                    });
                });

                document.querySelectorAll('.gotech-amount-input').forEach(inp => {
                    inp.addEventListener('input', function () {
                        const taskId = parseInt(this.getAttribute('data-task-id'));
                        const val = parseFloat(this.value) || 0;
                        customAmounts[taskId] = val.toFixed(2);
                        updateTotals();
                    });
                });
            }

            function checkPageSelectAllState() {
                const pageCheckboxes = document.querySelectorAll('.gotech-task-checkbox');
                if (pageCheckboxes.length === 0) {
                    selectAllPageCheckbox.checked = false;
                    return;
                }
                const allChecked = Array.from(pageCheckboxes).every(cb => cb.checked);
                selectAllPageCheckbox.checked = allChecked;
            }

            selectAllPageCheckbox.addEventListener('change', function () {
                const checked = this.checked;
                document.querySelectorAll('.gotech-task-checkbox').forEach(cb => {
                    cb.checked = checked;
                    const taskId = parseInt(cb.value);
                    if (checked) {
                        selectedTaskIds.add(taskId);
                        cb.closest('tr').classList.add('table-primary', 'bg-opacity-10');
                    } else {
                        selectedTaskIds.delete(taskId);
                        cb.closest('tr').classList.remove('table-primary', 'bg-opacity-10');
                    }
                });
                updateTotals();
            });

            // Select all currently filtered tasks across all pages
            btnSelectAllFiltered.addEventListener('click', function () {
                const filtered = getFilteredTasks();
                filtered.forEach(t => selectedTaskIds.add(t.id));
                renderTableRows();
            });

            // Clear all selections across all pages
            btnClearAllSelections.addEventListener('click', function () {
                selectedTaskIds.clear();
                renderTableRows();
            });

            // Reset all filters
            btnResetFilters.addEventListener('click', function () {
                searchInput.value = '';
                customerFilter.value = '';
                vehicleFilter.value = '';
                routeFilter.value = '';
                perPageSelect.value = '10';
                perPage = 10;
                currentPage = 1;
                renderTableRows();
            });

            // Filter change handlers
            searchInput.addEventListener('input', function () {
                currentPage = 1;
                renderTableRows();
            });
            customerFilter.addEventListener('change', function () {
                currentPage = 1;
                renderTableRows();
            });
            vehicleFilter.addEventListener('change', function () {
                currentPage = 1;
                renderTableRows();
            });
            routeFilter.addEventListener('change', function () {
                currentPage = 1;
                renderTableRows();
            });
            perPageSelect.addEventListener('change', function () {
                perPage = parseInt(this.value) || 10;
                currentPage = 1;
                renderTableRows();
            });

            function updateTotals() {
                const selectedCount = selectedTaskIds.size;
                let totalAmount = 0;

                selectedTaskIds.forEach(id => {
                    const task = eligibleTasks.find(t => t.id === id);
                    if (task) {
                        const amount = customAmounts[id] ? parseFloat(customAmounts[id]) : (parseFloat(task.funding_amount) || parseFloat(task.total_price) || 0);
                        totalAmount += amount;
                    }
                });

                selectedCountEl.textContent = selectedCount;
                selectedTotalEl.textContent = totalAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                if (selectedCount > 0) {
                    btnSubmit.disabled = false;
                    btnSubmitText.textContent = `إرسال (${selectedCount}) مهمة للتمويل في Go-Tech (${totalAmount.toFixed(2)} ر.س)`;
                } else {
                    btnSubmit.disabled = true;
                    btnSubmitText.textContent = `إرسال المهام المحددة للتمويل في Go-Tech`;
                }
            }

            // إرسال المهام المحددة إلى Go-Tech
            btnSubmit.addEventListener('click', function () {
                if (selectedTaskIds.size === 0) return;

                const taskIds = Array.from(selectedTaskIds);
                const fundingAmounts = {};
                taskIds.forEach(id => {
                    const task = eligibleTasks.find(t => t.id === id);
                    const amount = customAmounts[id] ? parseFloat(customAmounts[id]) : (parseFloat(task?.funding_amount) || parseFloat(task?.total_price) || 0);
                    fundingAmounts[id] = amount;
                });

                const totalAmount = Object.values(fundingAmounts).reduce((a, b) => a + b, 0);
                const duration = parseInt(durationInput.value) || 120;

                Swal.fire({
                    title: 'تأكيد الطرح في Go-Tech',
                    html: `هل أنت متأكد من رغبتك في إرسال <b>(${taskIds.length})</b> مهمة لطرحها للتمويل في منصة Go-Tech؟<br><br>
                           <div class="alert alert-primary py-2 text-center">
                               إجمالي مبلغ التمويل المطلوب: <b>${totalAmount.toFixed(2)} ر.س</b><br>
                               مدة صلاحية العرض: <b>${duration} دقيقة</b>
                           </div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="ti ti-send me-1"></i> نعم، إرسال الآن',
                    cancelButtonText: '{{ __('Cancel') }}',
                    customClass: {
                        confirmButton: 'btn btn-primary me-3',
                        cancelButton: 'btn btn-outline-secondary'
                    },
                    buttonsStyling: false
                }).then(result => {
                    if (result.isConfirmed) {
                        btnSubmit.disabled = true;
                        btnSubmitText.textContent = 'جاري الإرسال والمعالجة...';

                        fetch('{{ route('tasks.gotech.batch_submit') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                task_ids: taskIds,
                                funding_amounts: fundingAmounts,
                                expires_in_minutes: duration
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            btnSubmit.disabled = false;
                            btnSubmitText.textContent = `إرسال المهام المحددة للتمويل في Go-Tech`;

                            if (data.status === 1) {
                                let html = `<p class="mb-2">${data.message}</p>`;
                                if (data.failed && data.failed.length > 0) {
                                    html += `<div class="alert alert-warning text-start mt-2 p-2" style="font-size:12px; max-height:160px; overflow-y:auto;">
                                        <strong>تنبيهات المهام:</strong><ul class="mb-0 ps-3">`;
                                    data.failed.forEach(f => {
                                        html += `<li><strong>${f.task_number}:</strong> ${f.reason}</li>`;
                                    });
                                    html += `</ul></div>`;
                                }
                                Swal.fire({
                                    icon: data.failed && data.failed.length > 0 ? 'warning' : 'success',
                                    title: data.failed && data.failed.length > 0 ? 'اكتمل الطرح مع بعض التنبيهات' : 'تم الطرح بنجاح!',
                                    html: html,
                                    customClass: { confirmButton: 'btn btn-primary' },
                                    buttonsStyling: false
                                });
                                gotechModalInstance.hide();
                                // تحديث جدول المهام الرئيسي
                                if (typeof $ !== 'undefined' && $.fn.DataTable && $('.datatables-tasks').length) {
                                    $('.datatables-tasks').DataTable().ajax.reload(null, false);
                                }
                            } else {
                                let html = `<p class="mb-2">${data.message || 'حدث خطأ أثناء معالجة الطلب'}</p>`;
                                if (data.failed && data.failed.length > 0) {
                                    html += `<div class="alert alert-danger text-start mt-2 p-2" style="font-size:12px; max-height:160px; overflow-y:auto;">
                                        <strong>أسباب عدم قبول المهام:</strong><ul class="mb-0 ps-3">`;
                                    data.failed.forEach(f => {
                                        html += `<li><strong>${f.task_number}:</strong> ${f.reason}</li>`;
                                    });
                                    html += `</ul></div>`;
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'تعذر الإرسال',
                                    html: html,
                                    customClass: { confirmButton: 'btn btn-danger' },
                                    buttonsStyling: false
                                });
                            }
                        })
                        .catch(err => {
                            btnSubmit.disabled = false;
                            btnSubmitText.textContent = `إرسال المهام المحددة للتمويل في Go-Tech`;
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ في الاتصال',
                                text: err.message,
                                customClass: { confirmButton: 'btn btn-danger' },
                                buttonsStyling: false
                            });
                        });
                    }
                });
            });

            // إجراءات فحص الحالة وإلغاء الطرح الفردي من جدول المهام
            $(document).on('click', '.check-gotech-status', function (e) {
                e.preventDefault();
                const taskId = $(this).data('id');
                Swal.fire({
                    title: 'فحص حالة التمويل في Go-Tech',
                    text: 'جاري الاستعلام المباشر من بوابة Go-Tech...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch(`${baseUrl}admin/tasks/${taskId}/gotech-status`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 1) {
                        Swal.fire({
                            icon: 'info',
                            title: 'حالة المهمة في Go-Tech',
                            text: data.message,
                            customClass: { confirmButton: 'btn btn-primary' },
                            buttonsStyling: false
                        });
                        $('.datatables-tasks').DataTable().ajax.reload(null, false);
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: '{{ __('Notice') }}',
                            text: data.message || 'تعذر جلب الحالة',
                            customClass: { confirmButton: 'btn btn-warning' },
                            buttonsStyling: false
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Error') }}',
                        text: err.message,
                        customClass: { confirmButton: 'btn btn-danger' },
                        buttonsStyling: false
                    });
                });
            });

            $(document).on('click', '.cancel-gotech-task', function (e) {
                e.preventDefault();
                const taskId = $(this).data('id');
                const isFunded = $(this).data('funded') == 1;

                if (typeof canCancelGoTech !== 'undefined' && !canCancelGoTech) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __('Unauthorized') }}',
                        text: 'ليس لديك صلاحية فك ارتباط وإلغاء طرح المهام في Go-Tech.',
                        customClass: { confirmButton: 'btn btn-danger' },
                        buttonsStyling: false
                    });
                    return;
                }

                const titleText = isFunded ? 'فك ارتباط Go-Tech وإلغاء التمويل' : 'إلغاء طرح المهمة في Go-Tech';
                const bodyText = isFunded
                    ? 'تنبيه هام: هذه المهمة تم تمويلها بالفعل من قِبل مستثمر في منصة Go-Tech.\nفك الارتباط سيقوم بإلغاء حالة تمويل Go-Tech وسحب المهمة محلياً لتصبح متاحة فوراً للمستثمرين الداخليين.\nهل ترغب في الاستمرار؟'
                    : 'هل أنت متأكد من رغبتك في سحب هذه المهمة من سوق الاستثمار في Go-Tech وإعادتها للاستثمار الداخلي؟';
                const confirmBtnText = isFunded ? 'نعم، فك الارتباط وإلغاء التمويل' : 'نعم، إلغاء الطرح';

                const performCancel = (force = false) => {
                    Swal.fire({
                        title: force ? 'جاري فك الارتباط قسرياً...' : 'جاري معالجة الإلغاء...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    fetch(`${baseUrl}admin/tasks/${taskId}/gotech-cancel`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ force_unlink: force ? 1 : (isFunded ? 1 : 0) })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success || data.status === 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تمت العملية بنجاح!',
                                text: data.message || 'تم فك ارتباط المهمة من Go-Tech وأصبحت متاحة للمستثمرين الداخليين مجدداً.',
                                customClass: { confirmButton: 'btn btn-success' },
                                buttonsStyling: false
                            });
                            $('.datatables-tasks').DataTable().ajax.reload(null, false);
                        } else if (data.can_force) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'فك ارتباط قسري مطلوب',
                                text: (data.message || '') + '\n\nهل ترغب في فك ارتباط المهمة قسرياً محلياً وإتاحتها للمستثمرين الداخليين؟',
                                showCancelButton: true,
                                confirmButtonText: 'نعم، فك الارتباط قسرياً',
                                cancelButtonText: 'تراجع',
                                customClass: {
                                    confirmButton: 'btn btn-danger me-3',
                                    cancelButton: 'btn btn-outline-secondary'
                                },
                                buttonsStyling: false
                            }).then(fRes => {
                                if (fRes.isConfirmed) {
                                    performCancel(true);
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'تعذر الإلغاء',
                                text: data.message || 'فشلت عملية الإلغاء',
                                customClass: { confirmButton: 'btn btn-danger' },
                                buttonsStyling: false
                            });
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('Error') }}',
                            text: err.message,
                            customClass: { confirmButton: 'btn btn-danger' },
                            buttonsStyling: false
                        });
                    });
                };

                Swal.fire({
                    title: titleText,
                    text: bodyText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: confirmBtnText,
                    cancelButtonText: 'تراجع',
                    customClass: {
                        confirmButton: 'btn btn-danger me-3',
                        cancelButton: 'btn btn-outline-secondary'
                    },
                    buttonsStyling: false
                }).then(result => {
                    if (result.isConfirmed) {
                        performCancel(isFunded);
                    }
                });
            });
        });
    </script>
@endsection