@extends('layouts/layoutMaster')

@section('title', __('General Settings'))

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
    @vite(['resources/js/admin/settings.js'])

@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-header border-bottom">
            <h5 class="card-title ">
                <i class="tf-icons ti ti-adjustments me-2 fs-3 text-white bg-primary rounded p-1"></i>

                {{ __('Settings') }} | {{ __('General Settings') }}
            </h5>
            <p>{{ __('You can manage the main and vital settings of the platform from here, so be careful.') }}</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong>{{ __('Templates') }}</strong>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-9">
                        <label for="customer-template" class="mb-2">{{ __('Default Customer Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="customer_template">
                            @if (empty($settings['customer_template']['value']) || empty($templates))
                                <option value="">{{ __('--- Select Template') }}</option>
                            @endif
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ ($settings['customer_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                            @if (!empty($settings['customer_template']['value']))
                                <option value="">{{ __('--- Select Template') }}</option>
                            @endif
                        </select>
                        <span class="customer-error text-danger"></span>
                    </div>
                    <div class="form-group mb-9">
                        <label for="driver-template" class="mb-2">{{ __('Default Driver Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="driver_template" id="driver-template">
                            @if (empty($settings['driver_template']['value']) || empty($templates))
                                <option value="">{{ __('--- Select Template') }}</option>
                            @endif
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ ($settings['driver_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                            @if (!empty($settings['customer_template']['value']))
                                <option value="">{{ __('--- Select Template') }}</option>
                            @endif
                        </select>
                        <span class="driver-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="user-template" class="mb-2">{{ __('Default User Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="user_template" id="user-template">
                            @if (empty($settings['user_template']['value']) || empty($templates))
                                <option value="">{{ __('--- Select Template') }}</option>
                            @endif
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ ($settings['user_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                            @if (!empty($settings['customer_template']['value']))
                                <option value="">{{ __('--- Select Template') }}</option>
                            @endif
                        </select>
                        <span class="user-error text-danger"></span>
                    </div>
                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2">{{ __('Default Task Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="task_template" id="task-template">
                            <option value="">{{ __('--- Select Template') }}</option>
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ ($settings['task_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2">{{ __('Default Task (From Port) Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="task_from_port_template"
                            id="task-from-port-template">
                            <option value="">{{ __('--- Select Template') }}</option>
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ ($settings['task_from_port_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2">{{ __('Default Task (To Port) Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="task_to_port_template"
                            id="task-to-port-template">
                            <option value="">{{ __('--- Select Template') }}</option>
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ ($settings['task_to_port_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="task-template" class="mb-2">{{ __('Default Customs Clearances Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="customs_clearance_template"
                            id="task-to-port-template">
                            <option value="">{{ __('--- Select Template') }}</option>
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ ($settings['customs_clearance_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="task-template"
                            class="mb-2">{{ __('Default Customs Clearances Agent Template') }}</label>
                        <select class="form-select  update-setting-select" data-key="customs_clearance_agent_template"
                            id="task-to-port-template">
                            <option value="">{{ __('--- Select Template') }}</option>
                            @foreach ($templates as $val)
                                <option value="{{ $val->id }}"
                                    {{ ($settings['customs_clearance_agent_template']['value'] ?? null) == $val->id ? 'selected' : '' }}>
                                    {{ $val->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="task-error text-danger"></span>
                    </div>

                </div>
            </div>
        <div class="col-md-4">
            <div class="card mt-3">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong>{{ __('Policies & Reports Settings') }}</strong>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label class="mb-2 d-flex justify-content-between align-items-center">
                            {{ __('Enable Internal Signatures') }}
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input update-setting-checkbox" type="checkbox"
                                    data-key="internal_signatures_enabled"
                                    {{ ($settings['internal_signatures_enabled']['value'] ?? '0') == '1' ? 'checked' : '' }}>
                            </div>
                        </label>
                        <p class="text-muted small">{{ $settings['internal_signatures_enabled']['description'] ?? __('Enable display of stored signatures in PDF policies and reports.') }}</p>
                    </div>
                </div>
            </div>
        </div>
        </div>
        {{-- <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong>{{ __('Drivers Commission') }}</strong>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-9">
                        <label for="commission-type" class="mb-2">{{ __('Commission Type') }}</label>
                        <select class="form-select  update-setting-select" data-key="commission_type">

                            <option value="rate" {{ ($settings['commission_type']['value'] ?? null) == 'rate' ? 'selected' : '' }}>
                                {{ __('Rate') }}</option>
                            <option value="fixed"
                                {{ ($settings['commission_type']['value'] ?? null) == 'fixed' ? 'selected' : '' }}>
                                {{ __('Fixed') }}</option>
                        </select>
                        <span class="commission_type-error text-danger"></span>
                    </div>
                    <div class="form-group mb-9">
                        <label for="commission_rate" class="mb-2">{{ __('Commission Rate') }}</label>
                        <input type="number" data-key="commission_rate" max="100" min="0" step="any"
                            value="{{ $settings['commission_rate']['value'] ?? '' }}" class="form-control update-setting-input">
                        <span class="commission_rate-error text-danger"></span>
                    </div>

                    <div class="form-group mb-9">
                        <label for="commission_fixed" class="mb-2">{{ __('Commission fixed Amount') }}</label>
                        <input type="number" data-key="commission_fixed" min="0" step="any"
                            value="{{ $settings['commission_fixed']['value'] ?? '' }}" class="form-control update-setting-input">
                        <span class="commission_fixed-error text-danger"></span>
                    </div>
                </div>
            </div>
        </div> --}}
        <div class="col-md-8">

            <div class="card border ">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong>{{ __('System Management') }}</strong></div>
                    </div>
                </div>

                <div class="card-body text-center">

                    <h5 class="card-title">{{ __('Backup Management') }}</h5>
                    <p class="card-text text-muted">
                        {{ __('Manage database backups and uploaded files with advanced encryption') }}
                    </p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('settings.backup') }}" class="btn btn-primary">
                            <i class="ti ti-settings me-1"></i>
                            {{ __('Manage Backups') }}
                        </a>

                    </div>
                </div>
            </div>
            <div class="row">
        <!-- Task Distribution Settings -->
        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong>{{ __('Task Distribution Settings') }}</strong></div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label class="mb-2 d-flex justify-content-between align-items-center">
                            {{ __('Auto Distribution Enabled') }}
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input update-setting-checkbox" type="checkbox"
                                    data-key="auto_distribution_enabled"
                                    {{ ($settings['auto_distribution_enabled']['value'] ?? '0') == '1' ? 'checked' : '' }}>
                            </div>
                        </label>
                        <p class="text-muted small">{{ $settings['auto_distribution_enabled']['description'] ?? '' }}</p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="distribution_mode" class="mb-2">{{ __('Distribution Mode') }}</label>
                        <select class="form-select update-setting-select" data-key="distribution_mode">
                            <option value="sequential" {{ ($settings['distribution_mode']['value'] ?? 'sequential') == 'sequential' ? 'selected' : '' }}>
                                {{ __('Sequential (One by one)') }}
                            </option>
                            <option value="broadcast" {{ ($settings['distribution_mode']['value'] ?? '') == 'broadcast' ? 'selected' : '' }}>
                                {{ __('Broadcast (Top 5 nearby)') }}
                            </option>
                        </select>
                        <p class="text-muted small">{{ $settings['distribution_mode']['description'] ?? '' }}</p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="max_distribution_distance" class="mb-2">{{ __('Max Distribution Distance (Meters)') }}</label>
                        <input type="number" data-key="max_distribution_distance"
                            value="{{ $settings['max_distribution_distance']['value'] ?? '1000' }}"
                            class="form-control update-setting-input">
                        <p class="text-muted small">{{ $settings['max_distribution_distance']['description'] ?? '' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- App Update Settings -->
        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong>{{ __('Driver App Update Settings') }}</strong></div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label for="min_driver_app_version" class="mb-2">{{ __('Minimum App Version') }}</label>
                        <input type="text" data-key="min_driver_app_version"
                            value="{{ $settings['min_driver_app_version']['value'] ?? '1.0.0' }}"
                            class="form-control update-setting-input" placeholder="e.g., 1.0.5">
                        <p class="text-muted small">{{ $settings['min_driver_app_version']['description'] ?? '' }}</p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="driver_app_update_url" class="mb-2">{{ __('ِAndroid App Update URL') }}</label>
                        <input type="url" data-key="driver_app_update_url"
                            value="{{ $settings['driver_app_update_url']['value'] ?? '' }}"
                            class="form-control update-setting-input" placeholder="https://play.google.com/store/apps/details?id=...">
                        <p class="text-muted small">{{ $settings['driver_app_update_url']['description'] ?? '' }}</p>
                    </div>
                    <div class="form-group mb-4">
                        <label for="driver_app_ios_update_url" class="mb-2">{{ __('IOS App Update URL') }}</label>
                        <input type="url" data-key="driver_app_ios_update_url"
                            value="{{ $settings['driver_app_ios_update_url']['value'] ?? '' }}"
                            class="form-control update-setting-input" placeholder="https://">
                        <p class="text-muted small">{{ $settings['driver_app_ios_update_url']['description'] ?? '' }}</p>
                    </div>
                </div>
            </div>

             <div class="card mt-3">
                <div class="card-header">
                    <div class="divider text-start">
                        <div class="divider-text"><strong>{{ __('Customer App Update Settings') }}</strong></div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="form-group mb-4">
                        <label for="min_customer_app_version" class="mb-2">{{ __('Minimum App Version') }}</label>
                        <input type="text" data-key="min_customer_app_version"
                            value="{{ $settings['min_customer_app_version']['value'] ?? '1.0.0' }}"
                            class="form-control update-setting-input" placeholder="e.g., 1.0.5">
                        <p class="text-muted small">{{ $settings['min_customer_app_version']['description'] ?? '' }}</p>
                    </div>

                    <div class="form-group mb-4">
                        <label for="customer_app_update_url" class="mb-2">{{ __('ِAndroid App Update URL') }}</label>
                        <input type="url" data-key="customer_app_update_url"
                            value="{{ $settings['customer_app_update_url']['value'] ?? '' }}"
                            class="form-control update-setting-input" placeholder="https://play.google.com/store/apps/details?id=...">
                        <p class="text-muted small">{{ $settings['customer_app_update_url']['description'] ?? '' }}</p>
                    </div>
                    <div class="form-group mb-4">
                        <label for="customer_app_ios_update_url" class="mb-2">{{ __('IOS App Update URL') }}</label>
                        <input type="url" data-key="customer_app_ios_update_url"
                            value="{{ $settings['customer_app_ios_update_url']['value'] ?? '' }}"
                            class="form-control update-setting-input" placeholder="https://">
                        <p class="text-muted small">{{ $settings['customer_app_ios_update_url']['description'] ?? '' }}</p>
                    </div>
                </div>
            </div>

            <!-- Mtahd (Amnn) Escrow Settings Card -->
            <div class="card mt-4 border-0 shadow-sm">
                <div class="card-header bg-label-primary py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <h5 class="card-title mb-0 text-primary">
                                <i class="ti ti-shield-check me-2 fs-3"></i>{{ __('إعدادات وحساب المنصة في متعهد (Amnn / Mtahd Escrow)') }}
                            </h5>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input update-setting-checkbox" type="checkbox" id="setting_mtahd_enabled" data-key="mtahd_enabled" {{ ($settings['mtahd_enabled']['value'] ?? '1') != '0' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold text-dark" for="setting_mtahd_enabled">
                                    {{ __('تفعيل خدمة متعهد في النظام والتطبيق') }}
                                </label>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn_test_mtahd_conn">
                                <i class="ti ti-plug-connected me-1"></i>{{ __('اختبار الاتصال بالـ API') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-4">
                    <div class="alert alert-info border-0 mb-4" role="alert">
                        <div class="d-flex">
                            <i class="ti ti-info-circle fs-3 me-2"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold">{{ __('آلية الضمان المالي في متعهد:') }}</h6>
                                <p class="mb-0 small">
                                    {{ __('المنصة مسجلة كبائع معتمد في متعهد، ويقوم العميل بسداد قيمة المهمة في حساب الضمان، وعند إتمام التوصيل يتم تحرير كامل المبلغ لحساب المنصة وتغذية محفظة السائق بصافي مستحقاته تلقائياً.') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">{{ __('رقم حساب المنصة كبائع في متعهد (Seller Customer #)') }}</label>
                                <div class="input-group">
                                    <input type="text" id="setting_mtahd_seller_num" data-key="mtahd_platform_customer_number"
                                        value="{{ $settings['mtahd_platform_customer_number']['value'] ?? config('services.mtahd.platform_seller_number') }}"
                                        class="form-control update-setting-input font-monospace" placeholder="e.g., CUST_123456">
                                    <button class="btn btn-primary" type="button" id="btn_create_platform_mtahd">
                                        <i class="ti ti-user-plus me-1"></i>{{ __('إنشاء / توثيق في متعهد') }}
                                    </button>
                                </div>
                                <small class="text-muted">{{ __('معرف حساب المنصة المسجل لدى متعهد لتلقي أموال الضمان المالي.') }}</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">{{ __('رابط الـ API الأساسي (Base URL)') }}</label>
                                <input type="url" data-key="mtahd_base_url"
                                    value="{{ $settings['mtahd_base_url']['value'] ?? config('services.mtahd.base_url') }}"
                                    class="form-control update-setting-input font-monospace" placeholder="https://sandbox-api.amnn.sa/api/v1">
                                <small class="text-muted">{{ __('بيئة الاختبار: https://sandbox-api.amnn.sa/api/v1 | بيئة الإنتاج: https://api.amnn.sa/api/v1') }}</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">{{ __('مفتاح الربط (API Token / Secret)') }}</label>
                                <input type="password" data-key="mtahd_api_token"
                                    value="{{ $settings['mtahd_api_token']['value'] ?? config('services.mtahd.api_token') }}"
                                    class="form-control update-setting-input font-monospace" placeholder="c2199c8e...">
                                <small class="text-muted">{{ __('رمز التفويض المعتمد الخاص بحساب منصتكم من شركة متعهد (أمن).') }}</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label fw-bold">{{ __('مفتاح توقيع الإشعارات (Webhook Secret)') }}</label>
                                <input type="password" data-key="mtahd_webhook_secret"
                                    value="{{ $settings['mtahd_webhook_secret']['value'] ?? config('services.mtahd.webhook_secret') }}"
                                    class="form-control update-setting-input font-monospace" placeholder="webhook_secret_key">
                                <small class="text-muted">{{ __('يستخدم للتحقق المشفر من صحة الإشعارات اللحظية الواردة إلى: ') }} <code>/api/webhooks/mtahd</code></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Test Mtahd Connection Button
        const btnTest = document.getElementById('btn_test_mtahd_conn');
        if (btnTest) {
            btnTest.addEventListener('click', function () {
                Swal.fire({
                    title: '{{ __("جاري فحص الاتصال...") }}',
                    text: '{{ __("التحقق من صحة مفتاح الربط والاتصال بمنصة متعهد") }}',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                fetch("{{ route('settings.mtahd.test-connection') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: '{{ __("الاتصال سليم") }}', text: data.message });
                    } else {
                        Swal.fire({ icon: 'error', title: '{{ __("فشل الاتصال") }}', text: data.message });
                    }
                })
                .catch(() => Swal.fire({ icon: 'error', title: '{{ __("خطأ") }}', text: '{{ __("تعذر الاتصال بالسيرفر") }}' }));
            });
        }

        // Create Platform Account in Mtahd Button
        const btnCreate = document.getElementById('btn_create_platform_mtahd');
        if (btnCreate) {
            btnCreate.addEventListener('click', function () {
                Swal.fire({
                    title: '{{ __("إنشاء / توثيق حساب المنصة في متعهد") }}',
                    text: '{{ __("سيتم إرسال بيانات المنصة الرسمية إلى متعهد للحصول على رقم بائع معتمد (Platform Seller Number).") }}',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '{{ __("نعم، أنشئ الحساب") }}',
                    cancelButtonText: '{{ __("إلغاء") }}',
                    customClass: { confirmButton: 'btn btn-primary me-2', cancelButton: 'btn btn-label-secondary' },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({ title: '{{ __("جاري الإرسال...") }}', didOpen: () => { Swal.showLoading(); } });

                        fetch("{{ route('settings.mtahd.create-account') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                name: 'منصة سيف ديست للخدمات اللوجستية (SafeDests)',
                                phone: '+966500000000',
                                email: 'finance@safedests.com'
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('setting_mtahd_seller_num').value = data.customer_number;
                                Swal.fire({ icon: 'success', title: '{{ __("تم بنجاح") }}', text: data.message });
                            } else {
                                Swal.fire({ icon: 'error', title: '{{ __("فشل الإنشاء") }}', text: data.message });
                            }
                        })
                        .catch(() => Swal.fire({ icon: 'error', title: '{{ __("خطأ") }}', text: '{{ __("تعذر الاتصال بالسيرفر") }}' }));
                    }
                });
            });
        }
    });
</script>
@endsection
