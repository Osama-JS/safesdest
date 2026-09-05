@extends('layouts.layoutMaster')

@section('title', 'Task Details')

@section('page-style')
    <style>
        .timeline {
            list-style: none;
            padding: 0;
            position: relative;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #ccc;
        }

        .timeline-item {
            position: relative;
            padding-left: 40px;
            margin-bottom: 20px;
        }

        .timeline-point {
            position: absolute;
            left: 7px;
            top: 5px;
            width: 16px;
            height: 16px;
            background: #0d6efd;
            border-radius: 50%;
        }

        .info-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .status-badge {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
        }

        .delivery-note-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .delivery-note-preview:hover {
            transform: scale(1.05);
        }

        .file-icon {
            font-size: 3rem;
            color: #6c757d;
        }

        .gradient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-header-custom {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stats-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border: none;
            color: white;
        }

        .delivery-section {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            border: none;
            color: white;
        }
    </style>
@endsection
@section('page-script')
    @vite(['resources/js/admin/tasks/force-edit.js', 'resources/js/ajax.js'])
    <script>
        const canForceEdit = {{ auth()->user()->can('force_update_tasks') ? 'true' : 'false' }};
        const templateId = {{ $task->form_template_id ?? 0 }};

        function openReport() {
            const reportWindow = window.open('{{ route('tasks.report', $task->id) }}', '_blank');
        }

        function openCustomPolicy() {
            window.open('{{ route('tasks.policy_custom', $task->id) }}', '_blank');
        }

        function createMtahdDealForTask(taskId) {
            Swal.fire({
                title: '{{ __("إنشاء صفقة ضمان مالي") }}',
                text: '{{ __("هل ترغب في إنشاء صفقة ضمان مالي في منصة متعهد وتوليد رابط سداد العميل؟") }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '{{ __("نعم، أنشئ الصفقة") }}',
                cancelButtonText: '{{ __("إلغاء") }}',
                customClass: { confirmButton: 'btn btn-primary me-2', cancelButton: 'btn btn-label-secondary' },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: '{{ __("جاري إنشاء الصفقة...") }}',
                        text: '{{ __("يتم الاتصال بمنصة متعهد") }}',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    $.post(`{{ url('admin/mtahd-deals/create-for-task') }}/${taskId}`, { _token: '{{ csrf_token() }}' }, function(res) {
                        if (res.status) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __("تم إنشاء الصفقة بنجاح!") }}',
                                html: `
                                    <p class="mb-2">{{ __("رقم الصفقة:") }} <b>${res.deal_number}</b></p>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control form-control-sm" id="deal-payment-url-input" value="${res.payment_url}" readonly>
                                        <button class="btn btn-sm btn-outline-primary" type="button" onclick="navigator.clipboard.writeText('${res.payment_url}'); Swal.fire({icon: 'success', title: '{{ __('تم نسخ الرابط بنجاح') }}', timer: 1500, showConfirmButton: false});">
                                            <i class="ti ti-copy"></i>
                                        </button>
                                    </div>
                                `,
                                customClass: { confirmButton: 'btn btn-primary' }
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: '{{ __("فشل إنشاء الصفقة") }}', text: res.error || '{{ __("حدث خطأ أثناء الاتصال بمتعهد") }}' });
                        }
                    }).fail(() => Swal.fire({ icon: 'error', title: '{{ __("خطأ") }}', text: '{{ __("فشل الاتصال بالسيرفر") }}' }));
                }
            });
        }

        function syncMtahdDeal(dealNumber) {
            Swal.fire({
                title: '{{ __("جاري الاستعلام...") }}',
                text: `{{ __("الاستعلام عن الصفقة") }} ${dealNumber}`,
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            $.post(`{{ url('admin/mtahd-deals') }}/${dealNumber}/check-status`, { _token: '{{ csrf_token() }}' }, function(res) {
                if (res.status) {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("نتيجة الاستعلام") }}',
                        text: '{{ __("تمت المزامنة بنجاح") }}',
                        customClass: { confirmButton: 'btn btn-primary' }
                    }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: '{{ __("فشل الاستعلام") }}', text: res.error || '{{ __("تعذر جلب البيانات") }}' });
                }
            }).fail(() => Swal.fire({ icon: 'error', title: '{{ __("خطأ") }}', text: '{{ __("فشل الاتصال") }}' }));
        }

        function releaseMtahdDeal(dealNumber) {
            Swal.fire({
                title: '{{ __("تحرير الضمان المالي") }}',
                text: `{{ __("هل أنت متأكد من تحرير وصرف الضمان المالي للصفقة") }} ${dealNumber}؟`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __("نعم، حرر الضمان") }}',
                cancelButtonText: '{{ __("إلغاء") }}',
                customClass: { confirmButton: 'btn btn-success me-2', cancelButton: 'btn btn-label-secondary' },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: '{{ __("جاري التنفيذ...") }}', didOpen: () => { Swal.showLoading(); } });
                    $.post(`{{ url('admin/mtahd-deals') }}/${dealNumber}/release`, { _token: '{{ csrf_token() }}', task_id: '{{ $task->id }}' }, function(res) {
                        if (res.status) {
                            Swal.fire({ icon: 'success', title: '{{ __("نجاح") }}', text: res.message }).then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: '{{ __("خطأ") }}', text: res.error });
                        }
                    }).fail(() => Swal.fire({ icon: 'error', title: '{{ __("خطأ") }}', text: '{{ __("فشل الاتصال") }}' }));
                }
            });
        }

        function cancelMtahdDeal(dealNumber) {
            Swal.fire({
                title: '{{ __("إلغاء الصفقة واسترداد الضمان") }}',
                text: `{{ __("أدخل سبب إلغاء الصفقة") }} ${dealNumber}:`,
                input: 'textarea',
                inputPlaceholder: '{{ __("سبب الإلغاء...") }}',
                showCancelButton: true,
                confirmButtonText: '{{ __("تأكيد الإلغاء") }}',
                cancelButtonText: '{{ __("تراجع") }}',
                customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-label-secondary' },
                buttonsStyling: false,
                inputValidator: (v) => { if (!v) return '{{ __("يرجى كتابة سبب الإلغاء") }}'; }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({ title: '{{ __("جاري الإلغاء...") }}', didOpen: () => { Swal.showLoading(); } });
                    $.post(`{{ url('admin/mtahd-deals') }}/${dealNumber}/cancel`, { _token: '{{ csrf_token() }}', task_id: '{{ $task->id }}', reason: result.value }, function(res) {
                        if (res.status) {
                            Swal.fire({ icon: 'success', title: '{{ __("نجاح") }}', text: res.message }).then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: '{{ __("خطأ") }}', text: res.error });
                        }
                    }).fail(() => Swal.fire({ icon: 'error', title: '{{ __("خطأ") }}', text: '{{ __("فشل الاتصال") }}' }));
                }
            });
        }
    </script>
@endsection
@section('task-isactive', 'active')
@section('content')
    <div class="container my-4">
        <div class="row">
            <!-- تفاصيل المهمة -->
            <div class="col-lg-8 col-md-12">
                <div class="card mb-4 info-card">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-dark">
                            <i class="fas fa-tasks me-2 text-primary"></i>
                            {{ __('Task Details') }} (New) #{{ $task->id }}
                            @if($task->customer_task_number)
                                <span class="badge bg-info ms-2">{{ __('Customer #') }}{{ $task->customer_task_number }}</span>
                            @endif
                        </h5>

                        <div class="d-flex gap-2">
                            <a href="#" class="btn btn-sm btn-primary" onclick="openReport()">
                                <i class="fas fa-download me-1"></i>
                                {{ __('Download Report') }}
                            </a>

                            @if($task->customer && $task->customer->policy_file_name)
                            <a href="#" class="btn btn-sm btn-info" onclick="openCustomPolicy()">
                                <i class="fas fa-print me-1"></i>
                                {{ __('Print Policy') }}
                            </a>
                            @endif

                            <a href="{{ route('tasks.invoice', $task->id) }}" class="btn btn-sm btn-success" target="_blank">
                                <i class="fas fa-file-invoice me-1"></i>
                                {{ __('Download Invoice') }}
                            </a>

                            @can('force_update_tasks')
                                @if(!in_array(strtolower($task->status), ['in_progress', 'cancelled', 'cancel', 'canceled', 'refund', 'refunded']) && !$task->refunded)
                                    <button type="button" class="btn btn-sm btn-warning force-edit-task" data-id="{{ $task->id }}">
                                        <i class="fas fa-edit me-1"></i>
                                        {{ __('تعديل إجباري') }}
                                    </button>
                                @endif
                            @endcan

                            <a href="{{ route('tasks.list') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                {{ __('Back to Tasks') }}
                            </a>
                        </div>
                    </div>

                    <div class="card-body mt-3">
                        <p><strong>{{ __('owner') }}:</strong>
                            {{ $task->owner }}</p>
                        <p></strong>
                            {{ $task->owner == 'admin' ? $task->user->name : $task->customer->name }}</p>
                        <p><strong>{{ __('phone') }}:</strong>
                            {{ $task->owner == 'admin' ? $task->user->phone : $task->customer->phone }}</p>

                        @if ($task->investor)
                            <div class="alert alert-success mt-3 mb-3 p-3 d-flex align-items-center border-0 shadow-sm">
                                <i class="ti ti-piggy-bank fs-2 me-3"></i>
                                <div>
                                    <h6 class="alert-heading mb-1 fw-bold">{{ __('Funded By Investor') }}</h6>
                                    <p class="mb-0 text-muted">
                                        <strong>{{ $task->investor->name }}</strong> - <span dir="ltr">{{ $task->investor->phone }}</span>
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if ($task->conditions)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                {{ $task->conditions }}
                            </div>
                        @endif
                        @if ($task->driver_id && $task->driver)
                            <div class="card mb-4 shadow-sm">
                                <div
                                    class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 text-dark">
                                        <i class="fas fa-user me-2 text-primary"></i> {{ __('Assigned Driver') }}
                                    </h6>
                                    <a href="{{ route('drivers.show', [$task->driver->id, $task->driver->name]) }}"
                                        class="btn btn-primary btn-sm">
                                        {{ __('View Profile') }}
                                    </a>
                                </div>
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center">
                                            @if ($task->driver->image)
                                                <img src="{{ asset('storage/' . $task->driver->image) }}"
                                                    alt="{{ $task->driver->name }}"
                                                    class="img-fluid rounded-circle shadow-sm mb-2"
                                                    style="width: 80px; height: 80px; object-fit: cover;">
                                            @else
                                                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                                                    style="width: 80px; height: 80px;">
                                                    <i class="fas fa-user fa-2x"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="col-md-10">
                                            <h5 class="mb-1">{{ $task->driver->name }}</h5>
                                            <p class="mb-1">
                                                <i class="fas fa-phone-alt me-1 text-success"></i>
                                                <a href="tel:{{ $task->driver->phone }}">{{ $task->driver->phone }}</a>
                                            </p>
                                            @if ($task->driver->email)
                                                <p class="mb-1">
                                                    <i class="fas fa-envelope me-1 text-warning"></i>
                                                    <a
                                                        href="mailto:{{ $task->driver->email }}">{{ $task->driver->email }}</a>
                                                </p>
                                            @endif
                                            @if ($task->driver->vehicle_size)
                                                <p class="mb-1">
                                                    <i class="fas fa-truck me-1 text-primary"></i>
                                                    {{ $task->driver->vehicle_size->name }}
                                                </p>
                                            @endif
                                            @if ($task->driver->team)
                                                <p class="mb-0">
                                                    <i class="fas fa-users me-1 text-info"></i>
                                                    {{ __('Team') }}: {{ $task->driver->team->name }}
                                                </p>
                                            @endif

                                            @if ($task->driver->longitude && $task->driver->altitude)
                                                <p class="mt-2 mb-0">
                                                    <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                                    <a href="https://www.google.com/maps?q={{ $task->driver->altitude }},{{ $task->driver->longitude }}"
                                                        target="_blank">
                                                        {{ __('View on Map') }}
                                                    </a>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                {{ __('Driver not assigned yet') }}
                            </div>
                        @endif


                        <!-- إحصائيات المهمة -->
                        <div class="row g-3 mb-4">
                            @can('view_task_total_price')
                            <div class="col-md-3 col-sm-6">
                                <div class="card stats-card text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-dollar-sign fs-2 mb-2"></i>
                                        <h6 class="card-title">{{ __('Total Price') }}</h6>
                                        <h4 class="mb-0">
                                            {{ $task->total_price ? number_format($task->total_price, 2) : '0.00' }} SAR
                                        </h4>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            @can('view_task_commissions')
                            <div class="col-md-3 col-sm-6">
                                <div class="card bg-warning text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-percentage fs-2 mb-2"></i>
                                        <h6 class="card-title">{{ __('Commission') }}</h6>
                                        <h4 class="mb-0">
                                            {{ $task->commission ? number_format($task->commission, 2) : '0.00' }} SAR
                                        </h4>
                                    </div>
                                </div>
                            </div>
                            @endcan

                            <div class="col-md-3 col-sm-6">
                                <div class="card bg-info text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-info-circle fs-2 mb-2"></i>
                                        <h6 class="card-title">{{ __('Status') }}</h6>
                                        <span class="status-badge bg-white text-info fw-bold">
                                            {{ ucfirst($task->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 col-sm-6">
                                <div class="card bg-{{ $task->closed ? 'danger' : 'success' }} text-white h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-lock{{ $task->closed ? '' : '-open' }} fs-2 mb-2"></i>
                                        <h6 class="card-title">{{ __('Task Status') }}</h6>
                                        <span
                                            class="status-badge bg-white text-{{ $task->closed ? 'danger' : 'success' }} fw-bold">
                                            {{ $task->closed ? __('Closed') : __('Open') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- نقطة الاستلام والتسليم -->
                <div class="card mb-4 info-card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark">
                            <i class="fas fa-route me-2 text-primary"></i>
                            {{ __('Pickup & Delivery Points') }}
                        </h5>
                    </div>
                    <div class="card-body mt-4">
                        <div class="row g-4">
                            <!-- Pickup -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 shadow-sm bg-white">
                                    <h6 class="mb-3 text-primary d-flex align-items-center justify-content-between">
                                        <span>
                                            <i class="fas fa-map-marker-alt me-1"></i> {{ __('Pickup') }}
                                        </span>
                                        @if (optional($task->pickup)->latitude && optional($task->pickup)->longitude)
                                            <a href="https://www.google.com/maps?q={{ $task->pickup->latitude }},{{ $task->pickup->longitude }}"
                                                target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-map-location-dot me-1"></i>
                                            </a>
                                        @endif
                                    </h6>


                                    <ul class="list-unstyled mb-3">
                                        <li><strong>{{ __('Address:') }}</strong> {{ optional($task->pickup)->address }}</li>
                                        <li><strong>{{ __('Contact Name:') }}</strong> {{ optional($task->pickup)->contact_name }}</li>
                                        <li><strong>{{ __('Phone:') }}</strong> {{ optional($task->pickup)->contact_phone }}</li>
                                        <li><strong>{{ __('Email:') }}</strong> {{ optional($task->pickup)->contact_emil }}</li>
                                        <li><strong>{{ __('Note:') }}</strong> {{ optional($task->pickup)->note }}</li>
                                        @if (optional($task->pickup)->scheduled_time)
                                            <li><strong>{{ __('Pickup Before:') }}</strong>
                                                {{ \Carbon\Carbon::parse($task->pickup->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                    </ul>

                                    @if (optional($task->pickup)->image)
                                        <div class="mt-3">
                                            <strong>{{ __('Image:') }}</strong>
                                            <div class="mt-2 border rounded overflow-hidden" style=" width: 150px;">
                                                <img src="{{ asset($task->pickup->image) }}" alt="Pickup Image"
                                                    class="img-fluid rounded">
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Delivery -->
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100 shadow-sm bg-white">
                                    <h6 class="mb-3 text-success d-flex align-items-center justify-content-between">
                                        <span>
                                            <i class="fas fa-truck me-1"></i> {{ __('Delivery') }}
                                        </span>
                                        @if (optional($task->delivery)->latitude && optional($task->delivery)->longitude)
                                            <a href="https://www.google.com/maps?q={{ $task->delivery->latitude }},{{ $task->delivery->longitude }}"
                                                target="_blank" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-map-location-dot me-1"></i>
                                            </a>
                                        @endif
                                    </h6>


                                    <ul class="list-unstyled mb-3">
                                        <li><strong>{{ __('Address:') }}</strong> {{ optional($task->delivery)->address }}</li>
                                        <li><strong>{{ __('Contact Name:') }}</strong> {{ optional($task->delivery)->contact_name }}
                                        </li>
                                        <li><strong>{{ __('Phone:') }}</strong> {{ optional($task->delivery)->contact_phone }}</li>
                                        <li><strong>{{ __('Email:') }}</strong> {{ optional($task->delivery)->contact_emil }}</li>
                                        <li><strong>{{ __('Note:') }}</strong> {{ optional($task->delivery)->note }}</li>
                                        @if (optional($task->delivery)->scheduled_time)
                                            <li><strong>{{ __('Delivery Before:') }}</strong>
                                                {{ \Carbon\Carbon::parse($task->delivery->scheduled_time)->format('Y-m-d H:i') }}
                                            </li>
                                        @endif
                                    </ul>

                                    @if (optional($task->delivery)->image_path)
                                        <div class="mt-3">
                                            <strong>{{ __('Image:') }}</strong>
                                            <div class="mt-2 border rounded overflow-hidden" style="max-height: 250px;">
                                                <img src="{{ asset('storage/' . $task->delivery->image_path) }}"
                                                    alt="Delivery Image" class="img-fluid rounded">
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- مذكرة التوصيل -->
                @php
                    $deliveryNotes = [];
                    if (!empty($task->delivery_notes)) {
                        $decoded = json_decode($task->delivery_notes, true);
                        $deliveryNotes = is_array($decoded) ? $decoded : [$task->delivery_notes];
                    } elseif (!empty($task->delivery_note)) {
                        $decoded = json_decode($task->delivery_note, true);
                        $deliveryNotes = is_array($decoded) ? $decoded : [$task->delivery_note];
                    }
                @endphp

                @if ($task->closed && (!empty($deliveryNotes) || $task->delivery_number))
                    <div class="card mb-4 info-card">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-file-invoice me-2 text-primary"></i>
                                {{ __('Delivery Note') }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                @if ($task->delivery_number)
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100 bg-light">
                                            <h6 class="text-primary mb-3">
                                                <i class="fas fa-hashtag me-1"></i>
                                                {{ __('Delivery Number') }}
                                            </h6>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle p-2 me-3">
                                                    <i class="fas fa-barcode"></i>
                                                </div>
                                                <div>
                                                    <h4 class="mb-0 text-primary fw-bold">{{ $task->delivery_number }}</h4>
                                                    <small class="text-muted">{{ __('Reference Number') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if (!empty($deliveryNotes))
                                    <div class="col-md-{{ $task->delivery_number ? '6' : '12' }}">
                                        <div class="border rounded p-3 h-100 bg-light">
                                            <h6 class="text-success mb-3">
                                                <i class="fas fa-file-alt me-1"></i>
                                                {{ __('Delivery Note Files') }}
                                            </h6>

                                            <div class="d-flex flex-wrap gap-3">
                                                @foreach ($deliveryNotes as $filePath)
                                                    @php
                                                        $fileName = basename($filePath);
                                                        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                                        $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                        $fileIcons = [
                                                            'pdf' => ['icon' => 'fas fa-file-pdf', 'color' => 'text-danger'],
                                                            'doc' => ['icon' => 'fas fa-file-word', 'color' => 'text-primary'],
                                                            'docx' => ['icon' => 'fas fa-file-word', 'color' => 'text-primary'],
                                                            'txt' => ['icon' => 'fas fa-file-alt', 'color' => 'text-secondary'],
                                                            'csv' => ['icon' => 'fas fa-file-csv', 'color' => 'text-success'],
                                                        ];
                                                        $fileIcon = $fileIcons[$fileExtension] ?? ['icon' => 'fas fa-file', 'color' => 'text-muted'];
                                                    @endphp

                                                    @if ($isImage)
                                                        <div class="text-center" style="width: 150px;">
                                                            <a href="{{ asset('storage/' . $filePath) }}" target="_blank">
                                                                <img src="{{ asset('storage/' . $filePath) }}"
                                                                    alt="{{ __('Delivery Note') }}"
                                                                    class="delivery-note-preview img-fluid border shadow-sm"
                                                                    style="cursor: pointer; max-height: 120px; object-fit: contain;">
                                                            </a>
                                                            <div class="mt-2 text-truncate" title="{{ $fileName }}">
                                                                <small class="text-muted">{{ $fileName }}</small>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="text-center border rounded p-2 bg-white" style="width: 150px;">
                                                            <div class="mb-2">
                                                                <i class="{{ $fileIcon['icon'] }} fs-1 {{ $fileIcon['color'] }}"></i>
                                                            </div>
                                                            <div class="text-truncate mb-2" title="{{ $fileName }}">
                                                                <small>{{ $fileName }}</small>
                                                            </div>
                                                            <a href="{{ asset('storage/' . $filePath) }}" target="_blank"
                                                                class="btn btn-outline-primary btn-sm w-100">
                                                                <i class="fas fa-download me-1"></i>
                                                                {{ __('Download') }}
                                                            </a>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if (!empty($deliveryNotes) && $task->delivery_number)
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="alert alert-success d-flex align-items-center mb-0">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <div>
                                                <strong>{{ __('Task Completed Successfully') }}</strong><br>
                                                <small>{{ __('Delivery completed with reference number') }}:
                                                    <strong>{{ $task->delivery_number }}</strong></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- بيانات إضافية -->
                @if ($task->additional_data)
                    <div class="card mb-4 info-card">
                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0 text-dark">
                                <i class="fas fa-layer-group me-2 text-primary"></i>
                                {{ __('Additional Data') }}
                            </h5>
                        </div>

                        <div class="card-body mt-3">
                            @if (is_array($task->additional_data) && count($task->additional_data) > 0)
                                <div class="row">
                                    @foreach ($task->additional_data as $key => $field)
                                        <div class="col-md-6 mb-4">
                                            <div class="border rounded p-3 h-100 ">
                                                <h6 class="text-muted mb-2">

                                                    {{ $field['label'] }}
                                                </h6>

                                                @switch($field['type'])
                                                    @case('text')
                                                    @case('string')

                                                    @case('number')
                                                        <p class="mb-0">{{ $field['value'] }}</p>
                                                    @break

                                                    @case('image')
                                                        <img src="{{ asset('storage/' . $field['value']) }}"
                                                            alt="{{ $field['label'] }}" class="img-fluid rounded border"
                                                            style="max-height: 200px; object-fit: cover;">
                                                    @break

                                                    @case('file')
                                                        @php
                                                            $ext = strtolower(
                                                                pathinfo($field['value'], PATHINFO_EXTENSION),
                                                            );
                                                            $icons = [
                                                                'pdf' => 'ti ti-file-text',
                                                                'doc' => 'ti ti-file-description',
                                                                'docx' => 'ti ti-file-description',
                                                                'xls' => 'ti ti-file-spreadsheet',
                                                                'xlsx' => 'ti ti-file-spreadsheet',
                                                                'ppt' => 'ti ti-presentation',
                                                                'pptx' => 'ti ti-presentation',
                                                            ];
                                                            $iconClass = $icons[$ext] ?? 'ti ti-file';
                                                        @endphp

                                                        <a href="{{ asset('storage/' . $field['value']) }}" target="_blank"
                                                            class="d-flex align-items-center text-decoration-none mt-1">
                                                            <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                            <span class="text-truncate">{{ basename($field['value']) }}</span>
                                                        </a>
                                                    @break

                                                    @case('file_with_text')
                                                        @if ($field['value'])
                                                            @php
                                                                $ext = strtolower(
                                                                    pathinfo($field['value'], PATHINFO_EXTENSION),
                                                                );
                                                                $icons = [
                                                                    'pdf' => 'ti ti-file-text',
                                                                    'doc' => 'ti ti-file-description',
                                                                    'docx' => 'ti ti-file-description',
                                                                    'xls' => 'ti ti-file-spreadsheet',
                                                                    'xlsx' => 'ti ti-file-spreadsheet',
                                                                    'ppt' => 'ti ti-presentation',
                                                                    'pptx' => 'ti ti-presentation',
                                                                ];
                                                                $iconClass = $icons[$ext] ?? 'ti ti-file';
                                                            @endphp

                                                            <div class="d-flex align-items-center mb-2">
                                                                <a href="{{ asset('storage/' . $field['value']) }}"
                                                                    target="_blank"
                                                                    class="d-flex align-items-center text-decoration-none">
                                                                    <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                                    <span
                                                                        class="text-truncate">{{ basename($field['value']) }}</span>
                                                                </a>
                                                            </div>
                                                        @endif

                                                        @if (isset($field['text']) && $field['text'])
                                                            <div class="mt-2">
                                                                <small class="text-muted">{{ __('Additional Info:') }}</small>
                                                                <p class="mb-0 fw-medium">{{ $field['text'] }}</p>
                                                            </div>
                                                        @endif
                                                    @break

                                                    @case('file_expiration_date')
                                                        @if ($field['value'])
                                                            @php
                                                                $ext = strtolower(
                                                                    pathinfo($field['value'], PATHINFO_EXTENSION),
                                                                );
                                                                $icons = [
                                                                    'pdf' => 'ti ti-file-text',
                                                                    'doc' => 'ti ti-file-description',
                                                                    'docx' => 'ti ti-file-description',
                                                                    'xls' => 'ti ti-file-spreadsheet',
                                                                    'xlsx' => 'ti ti-file-spreadsheet',
                                                                    'ppt' => 'ti ti-presentation',
                                                                    'pptx' => 'ti ti-presentation',
                                                                ];
                                                                $iconClass = $icons[$ext] ?? 'ti ti-file';
                                                            @endphp

                                                            <div class="d-flex align-items-center mb-2">
                                                                <a href="{{ asset('storage/' . $field['value']) }}"
                                                                    target="_blank"
                                                                    class="d-flex align-items-center text-decoration-none">
                                                                    <i class="{{ $iconClass }} me-2 fs-4 text-primary"></i>
                                                                    <span
                                                                        class="text-truncate">{{ basename($field['value']) }}</span>
                                                                </a>
                                                            </div>
                                                        @endif

                                                        @if (isset($field['expiration']) && $field['expiration'])
                                                            <div class="mt-2">
                                                                <small class="text-muted">{{ __('Expires:') }}</small>
                                                                <p class="mb-0 fw-medium">
                                                                    {{ \Carbon\Carbon::parse($field['expiration'])->format('Y-m-d') }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                    @break

                                                    @default
                                                        <p class="mb-0">{{ $field['value'] }}</p>
                                                @endswitch
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-info" role="alert">
                                    {{ __('No additional data found for this customer.') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endif




            </div>

            <!-- الشريط الجانبي -->
            <div class="col-lg-4 col-md-12">
                <!-- بطاقة الضمان المالي في متعهد -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-label-primary d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 text-primary">
                            <i class="ti ti-shield-check me-2"></i>{{ __('ضمان مالي (متعهد / Escrow)') }}
                        </h5>
                        @if ($task->amnn_deal_number)
                            <span class="badge bg-primary">{{ $task->mtahd_status_label }}</span>
                        @else
                            <span class="badge bg-label-secondary">{{ __('غير منشأ') }}</span>
                        @endif
                    </div>
                    <div class="card-body pt-3">
                        @if ($task->amnn_deal_number)
                            <div class="mb-2 d-flex justify-content-between">
                                <span class="text-muted">{{ __('رقم الصفقة:') }}</span>
                                <span class="fw-bold font-monospace text-dark">{{ $task->amnn_deal_number }}</span>
                            </div>
                            <div class="mb-2 d-flex justify-content-between">
                                <span class="text-muted">{{ __('مبلغ الضمان:') }}</span>
                                <span class="fw-bold text-success">{{ number_format($task->total_price, 2) }} SAR</span>
                            </div>
                            <div class="mb-3 d-flex justify-content-between">
                                <span class="text-muted">{{ __('حالة الضمان:') }}</span>
                                <span class="badge bg-label-info">{{ $task->mtahd_status_label }}</span>
                            </div>

                            @if($task->amnn_payment_url)
                                <div class="mb-3">
                                    <label class="form-label text-muted small mb-1">{{ __('رابط سداد العميل في متعهد:') }}</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control font-monospace" id="amnn_payment_link_val" value="{{ $task->amnn_payment_url }}" readonly>
                                        <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText('{{ $task->amnn_payment_url }}'); Swal.fire({icon: 'success', title: '{{ __('تم نسخ الرابط بنجاح') }}', timer: 1500, showConfirmButton: false});">
                                            <i class="ti ti-copy"></i>
                                        </button>
                                        <a href="{{ $task->amnn_payment_url }}" target="_blank" class="btn btn-primary" title="{{ __('فتح صفحة السداد') }}">
                                            <i class="ti ti-external-link"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif

                            <div class="d-flex gap-1 justify-content-between mt-3 pt-2 border-top flex-wrap">
                                <button type="button" class="btn btn-xs btn-outline-info" onclick="syncMtahdDeal('{{ $task->amnn_deal_number }}')">
                                    <i class="ti ti-refresh me-1"></i>{{ __('مزامنة') }}
                                </button>
                                @if($task->amnn_deal_status === 'paid')
                                    <button type="button" class="btn btn-xs btn-outline-success" onclick="releaseMtahdDeal('{{ $task->amnn_deal_number }}')">
                                        <i class="ti ti-cash me-1"></i>{{ __('تحرير') }}
                                    </button>
                                @endif
                                @if(!in_array($task->amnn_deal_status, ['released', 'cancelled']))
                                    <button type="button" class="btn btn-xs btn-outline-danger" onclick="cancelMtahdDeal('{{ $task->amnn_deal_number }}')">
                                        <i class="ti ti-ban me-1"></i>{{ __('إلغاء') }}
                                    </button>
                                @endif
                            </div>
                        @else
                            <p class="text-muted small mb-3">
                                {{ __('يمكنك إنشاء صفقة ضمان مالي في منصة متعهد لهذه المهمة بقيمة') }} <b class="text-dark">{{ number_format($task->total_price, 2) }} SAR</b> {{ __('وتوليد رابط سداد آمن لمشاركته مع العميل.') }}
                            </p>
                            @if (!\App\Services\MtahdService::isServiceEnabled())
                                <div class="alert alert-warning py-2 mb-0 small text-center">
                                    <i class="ti ti-alert-triangle me-1"></i>{{ __('خدمة متعهد معطلة حالياً في إعدادات النظام') }}
                                </div>
                            @elseif ($task->payment_status !== 'completed' && $task->payment_status !== 'paid')
                                <button type="button" class="btn btn-sm btn-primary w-100" onclick="createMtahdDealForTask({{ $task->id }})">
                                    <i class="ti ti-shield-plus me-1"></i>{{ __('إنشاء صفقة متعهد وتوليد رابط السداد') }}
                                </button>
                            @else
                                <div class="alert alert-success py-2 mb-0 small text-center">
                                    <i class="ti ti-circle-check me-1"></i>{{ __('المهمة مدفوعة بالكامل مسبقاً') }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="card info-card">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-dark">
                            <i class="fas fa-history me-2 text-primary"></i>
                            {{ __('Task History') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        @if ($task->history->count())
                            <ul class="timeline">
                                @foreach ($task->history->sortByDesc('created_at') as $entry)
                                    <li class="timeline-item">
                                        <span class="timeline-point"></span>
                                        <div class="timeline-event">
                                            <div class="d-flex justify-content-between mb-1">
                                                <strong>{{ ucfirst($entry->action_type) }}</strong>
                                                <small
                                                    class="text-muted">{{ $entry->created_at->format('Y-m-d H:i') }}</small>
                                            </div>
                                            <p class="mb-1">{{ $entry->description }}</p>
                                            @if ($entry->file_path)
                                                <a href="{{ asset('storage/' . $entry->file_path) }}" target="_blank"
                                                    class="btn btn-sm btn-outline-primary">{{ __('View Attachment') }}</a>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted">{{ __('No events recorded.') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('admin.tasks.from-modal', [
        'customers' => $customers ?? \App\Models\Customer::where('status', 'active')->get(),
        'vehicles' => $vehicles ?? \App\Models\Vehicle::all(),
        'templates' => $templates ?? \App\Models\Form_Template::all(),
        'task_template' => $task_template ?? null,
        'task_from_template' => $task_from_template ?? null,
        'task_to_template' => $task_to_template ?? null,
    ])
@endsection
