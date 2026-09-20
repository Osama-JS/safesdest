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
        window.canViewCommissions = {{ auth()->user()->can('view_task_commissions') ? 'true' : 'false' }};
        const canViewCommissions = window.canViewCommissions;
        window.canViewTotalPrice = {{ auth()->user()->can('view_task_total_price') ? 'true' : 'false' }};
        const canViewTotalPrice = window.canViewTotalPrice;
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

        @can('view_task_commissions')
        // Handler for View Brokers Breakdown Modal
        $(document).on('click', '.view-task-brokers-breakdown-btn', function (e) {
            e.preventDefault();
            const taskId = $(this).data('id');
            if (!taskId) return;

            const modalEl = document.getElementById('viewBrokersBreakdownModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();

            $('#vbb-task-id-badge').text('#' + taskId);
            $('#vbb-loading').show();
            $('#vbb-content').hide();

            const apiBase = typeof baseUrl !== 'undefined' ? baseUrl : '/';

            $.ajax({
                url: `${apiBase}admin/tasks/brokers-breakdown/${taskId}`,
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    if (res.status !== 1) {
                        modal.hide();
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: res.error || 'تعذر تحميل بيانات الوسطاء'
                        });
                        return;
                    }

                    const d = res.data;
                    $('#vbb-loading').hide();
                    $('#vbb-content').show();

                    // Subtitle info
                    const statusText = d.closed ? 'مغلقة' : d.status;
                    $('#vbb-task-subtitle').text(`حالة المهمة: ${statusText} | السائق: ${d.driver.name}`);

                    // 1. Stat cards
                    $('#vbb-total-price').text(parseFloat(d.total_price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ر.س');
                    $('#vbb-driver-price').text(parseFloat(d.driver.driver_price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ر.س');
                    $('#vbb-driver-name').text(d.driver.name + (d.driver.team && d.driver.team !== '-' ? ` (${d.driver.team})` : ''));
                    
                    $('#vbb-brokers-share').text(parseFloat(d.total_brokers_share).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ر.س');
                    $('#vbb-brokers-count-text').text(`${d.brokers_count} ${d.brokers_count === 1 ? 'وسيط' : 'وسطاء'}`);

                    $('#vbb-platform-remaining').text(parseFloat(d.platform_remaining).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ر.س');
                    $('#vbb-platform-gross-text').text(`إجمالي العمولة: ${parseFloat(d.platform_gross).toFixed(2)} ر.س`);

                    // 2. Progress Split Bar
                    const total = parseFloat(d.total_price) || 1;
                    const driverPct = Math.min(100, (parseFloat(d.driver.driver_price) / total) * 100);
                    const brokersPct = Math.min(100, (parseFloat(d.total_brokers_share) / total) * 100);
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
                    } else {
                        $('#vbb-no-brokers').show();
                    }

                    // Quick Connect Button handler
                    $('#vbb-connect-more-btn, #vbb-empty-connect-btn').off('click').on('click', function () {
                        modal.hide();
                        $(`.edit-task-broker[data-id="${taskId}"]`).first().trigger('click');
                    });
                },
                error: function () {
                    modal.hide();
                    Swal.fire({
                        icon: 'error',
                        title: 'خطأ',
                        text: 'فشل الاتصال بالخادم لجلب بيانات الوسطاء'
                    });
                }
            });
        });
        @endcan
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
        <!-- Bulk Actions -->
        <div>
            <button type="button" class="btn btn-brand-whatsapp text-white share-selected-whatsapp"
                id="shareSelectedWhatsapp" style="background-color: #25D366; display: none;">
                <i class="ti ti-brand-whatsapp me-1"></i> {{ __('Share Selected') }}
            </button>
        </div>
    </div>



@endsection
@section('content')
    <!-- Ø®Ø§Ø±Ø¬ Ø§Ù„Ù€ navbar (Ø£Ø³ÙÙ„Ù‡Ø§ Ù…Ø¨Ø§Ø´Ø±Ø©) -->
    <div id="mobile-custom-nav" class="d-lg-none  z-1 card shadow mb-3 p-2" style="white-space: nowrap;">
    </div>
    <!-- /Search -->
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ __('Tasks') }}</h5>
            @if(auth()->check() && auth()->user()->email === 'osama.samomy@gmail.com')
                <button type="button" class="btn btn-warning waves-effect waves-light" onclick="openInvestmentConflictsModal()">
                    <i class="ti ti-alert-triangle me-1"></i> فحص تعارض الاستثمار
                </button>
            @endif
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



    @include('admin.tasks.from-modal')

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
                                        <th>رقم المهمة</th>
                                        <th>العميل</th>
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
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إغلاق</button>
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

                fetch('{{ route("tasks.investment_conflicts.data") }}')
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
                            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">' + (data.message || 'خطأ') + '</td></tr>';
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
                    cancelButtonText: 'إلغاء',
                    showLoaderOnConfirm: true,
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('يرجى إدخال كلمة المرور');
                            return false;
                        }
                        return fetch('{{ route("tasks.investment_conflicts.fix") }}', {
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
@endsection