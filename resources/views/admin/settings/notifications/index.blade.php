@extends('layouts/layoutMaster')

@section('title', __('إعدادات وتخصيص إشعارات الإدارة'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('page-script')
    <script>
        window.notificationSettingsConfig = {
            toggleUrl: "{{ url('admin/settings/notifications') }}",
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    @vite(['resources/js/admin/notifications-settings.js'])
@endsection

@section('content')
@php
    $allSettings = $settings->flatten();
    $totalEvents = $allSettings->count();
    $inAppCount  = $allSettings->where('in_app_enabled', true)->count();
    $emailCount  = $allSettings->where('email_enabled', true)->count();
    $webpushCount = $allSettings->where('webpush_enabled', true)->count();
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item">
                <a href="{{ url('admin') }}"><i class="ti ti-home-2 me-1"></i>{{ __('الرئيسية') }}</a>
            </li>
            <li class="breadcrumb-item">
                <a href="javascript:void(0);">{{ __('الإعدادات') }}</a>
            </li>
            <li class="breadcrumb-item active">{{ __('إعدادات الإشعارات') }}</li>
        </ol>
    </nav>

    <!-- Hero Header Banner -->
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, rgba(115, 103, 240, 0.08) 0%, rgba(115, 103, 240, 0.02) 100%);">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-xl bg-primary text-white rounded-3 shadow-sm d-flex align-items-center justify-content-center p-2">
                        <i class="ti ti-bell-ringing fs-1"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <h4 class="fw-bold mb-0 text-heading">{{ __('إعدادات وتخصيص إشعارات الإدارة') }}</h4>
                            <span class="badge bg-label-primary rounded-pill px-3 py-1 fs-tiny fw-semibold">
                                <i class="ti ti-shield-check me-1"></i> {{ __('مركز التحكم الموحد') }}
                            </span>
                        </div>
                        <p class="text-muted mb-0">
                            {{ __('التحكم الشامل في قنوات إرسال التنبيهات (لوحة التحكم الحية، البريد الإلكتروني، إشعارات المتصفح) وتخصيص مستلمي كل حدث تشغيلي ومالي.') }}
                        </p>
                    </div>
                </div>
                <div>
                    <a href="{{ url('admin/settings#mail_settings_card') }}" class="btn btn-primary shadow-sm">
                        <i class="ti ti-mail-cog me-1"></i> {{ __('إعدادات خادم البريد (SMTP)') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards Row -->
    <div class="row g-3 mb-4">
        <!-- Total Events -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted d-block small mb-1">{{ __('إجمالي الأحداث') }}</span>
                            <h4 class="fw-bold mb-0 text-heading">{{ $totalEvents }}</h4>
                            <small class="text-success"><i class="ti ti-check me-1"></i>{{ __('معرفة بالنظام') }}</small>
                        </div>
                        <div class="avatar avatar-md bg-label-primary rounded">
                            <i class="ti ti-bell fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- In-App Notifications -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted d-block small mb-1">{{ __('لوحة التحكم (داخلي)') }}</span>
                            <h4 class="fw-bold mb-0 text-primary">{{ $inAppCount }} / {{ $totalEvents }}</h4>
                            <small class="text-muted">{{ __('تنبيهات صوتية وفورية') }}</small>
                        </div>
                        <div class="avatar avatar-md bg-label-primary rounded">
                            <i class="ti ti-layout-navbar fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Notifications -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted d-block small mb-1">{{ __('البريد الإلكتروني') }}</span>
                            <h4 class="fw-bold mb-0 text-success">{{ $emailCount }} / {{ $totalEvents }}</h4>
                            <small class="text-muted">{{ __('إرسال عبر الـ Queue') }}</small>
                        </div>
                        <div class="avatar avatar-md bg-label-success rounded">
                            <i class="ti ti-mail fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- WebPush Notifications -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted d-block small mb-1">{{ __('إشعارات المتصفح') }}</span>
                            <h4 class="fw-bold mb-0 text-info">{{ $webpushCount }} / {{ $totalEvents }}</h4>
                            <small class="text-muted">{{ __('WebPush فوري') }}</small>
                        </div>
                        <div class="avatar avatar-md bg-label-info rounded">
                            <i class="ti ti-browser-check fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $categoryNames = [
            'financial' => ['title' => 'الأحداث والعمليات المالية والمحافظ', 'icon' => 'ti-wallet', 'color' => 'warning', 'desc' => 'طلبات سحب الرصيد، مصادقات دفعات الـ Payout، وتحديثات التحويل البنكي'],
            'tasks'     => ['title' => 'أحداث المهام والرحلات والعروض', 'icon' => 'ti-package', 'color' => 'primary', 'desc' => 'إنشاء المهام، طلبات الإلغاء العاجلة، تحديث الحالات، وعروض الأسعار'],
            'users'     => ['title' => 'أحداث المستخدمين والشركاء', 'icon' => 'ti-users', 'color' => 'info', 'desc' => 'تسجيل السائقين، تسجيل العملاء، وإنشاء فرق العمل'],
            'system'    => ['title' => 'أحداث النظام والمستندات', 'icon' => 'ti-settings', 'color' => 'secondary', 'desc' => 'تنبيهات النظام العامة وإشعارات المستندات الإدارية'],
        ];
    @endphp

    @foreach($categoryNames as $catKey => $catMeta)
        @if(isset($settings[$catKey]) && $settings[$catKey]->isNotEmpty())
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-header border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center bg-label-{{ $catMeta['color'] }}">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2 bg-{{ $catMeta['color'] }} text-white rounded d-flex align-items-center justify-content-center">
                            <i class="ti {{ $catMeta['icon'] }} fs-5"></i>
                        </div>
                        <div>
                            <h5 class="card-title mb-0 fw-bold">{{ __($catMeta['title']) }}</h5>
                            <small class="text-muted d-none d-sm-inline">{{ __($catMeta['desc']) }}</small>
                        </div>
                    </div>
                    <span class="badge bg-{{ $catMeta['color'] }} text-white rounded-pill px-3 py-1">
                        {{ $settings[$catKey]->count() }} {{ __('أحداث') }}
                    </span>
                </div>
                <div class="table-responsive text-nowrap">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 32%;">{{ __('نوع الحدث / الإشعار') }}</th>
                                <th class="text-center" style="width: 14%;">
                                    <i class="ti ti-layout-navbar me-1 text-primary"></i> {{ __('لوحة التحكم') }}
                                </th>
                                <th class="text-center" style="width: 14%;">
                                    <i class="ti ti-mail me-1 text-success"></i> {{ __('البريد الإلكتروني') }}
                                </th>
                                <th class="text-center" style="width: 14%;">
                                    <i class="ti ti-browser-check me-1 text-info"></i> {{ __('إشعار المتصفح') }}
                                </th>
                                <th style="width: 16%;">{{ __('المستلمون المصرح لهم') }}</th>
                                <th class="text-center" style="width: 10%;">{{ __('الإجراء') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($settings[$catKey] as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-3 flex-shrink-0">
                                                <span class="avatar-initial rounded-circle bg-label-{{ $item->priority == 'urgent' ? 'danger' : ($item->priority == 'high' ? 'warning' : 'primary') }}">
                                                    <i class="ti {{ \App\Services\AdminNotificationDispatcher::getDefaultIcon($item->event_key) }}"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-heading">
                                                    {{ $item->name_ar }}
                                                    @if($item->priority == 'urgent')
                                                        <span class="badge bg-label-danger ms-1 fs-tiny">{{ __('عاجل') }}</span>
                                                    @elseif($item->priority == 'high')
                                                        <span class="badge bg-label-warning ms-1 fs-tiny">{{ __('هام') }}</span>
                                                    @endif
                                                </div>
                                                <small class="text-muted d-block text-truncate" style="max-width: 320px;" title="{{ $item->description_ar }}">
                                                    {{ $item->description_ar }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- In-App Switch -->
                                    <td class="text-center">
                                        <label class="switch switch-primary switch-sm m-0">
                                            <input type="checkbox" class="switch-input channel-toggle" 
                                                   data-id="{{ $item->id }}" data-channel="in_app"
                                                   {{ $item->in_app_enabled ? 'checked' : '' }}>
                                            <span class="switch-toggle-slider">
                                                <span class="switch-on"><i class="ti ti-check"></i></span>
                                                <span class="switch-off"><i class="ti ti-x"></i></span>
                                            </span>
                                        </label>
                                    </td>
                                    <!-- Email Switch -->
                                    <td class="text-center">
                                        <label class="switch switch-success switch-sm m-0">
                                            <input type="checkbox" class="switch-input channel-toggle" 
                                                   data-id="{{ $item->id }}" data-channel="email"
                                                   {{ $item->email_enabled ? 'checked' : '' }}>
                                            <span class="switch-toggle-slider">
                                                <span class="switch-on"><i class="ti ti-check"></i></span>
                                                <span class="switch-off"><i class="ti ti-x"></i></span>
                                            </span>
                                        </label>
                                    </td>
                                    <!-- WebPush Switch -->
                                    <td class="text-center">
                                        <label class="switch switch-info switch-sm m-0">
                                            <input type="checkbox" class="switch-input channel-toggle" 
                                                   data-id="{{ $item->id }}" data-channel="webpush"
                                                   {{ $item->webpush_enabled ? 'checked' : '' }}>
                                            <span class="switch-toggle-slider">
                                                <span class="switch-on"><i class="ti ti-check"></i></span>
                                                <span class="switch-off"><i class="ti ti-x"></i></span>
                                            </span>
                                        </label>
                                    </td>
                                    <!-- Target Recipients -->
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if(!empty($item->target_roles) && count($item->target_roles) > 0)
                                                @foreach($item->target_roles as $role)
                                                    <span class="badge bg-label-dark py-1">{{ $role }}</span>
                                                @endforeach
                                            @else
                                                <span class="badge bg-label-secondary py-1">{{ __('كل المدراء') }}</span>
                                            @endif
                                            @if(!empty($item->target_user_ids) && count($item->target_user_ids) > 0)
                                                <span class="badge bg-label-primary py-1">+{{ count($item->target_user_ids) }} {{ __('مستخدمين') }}</span>
                                            @endif
                                            @if(!empty($item->custom_emails))
                                                <span class="badge bg-label-warning py-1" title="{{ $item->custom_emails }}"><i class="ti ti-mail"></i></span>
                                            @endif
                                        </div>
                                    </td>
                                    <!-- Actions -->
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-icon btn-label-primary btn-edit-setting"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->name_ar }}"
                                                data-roles="{{ json_encode($item->target_roles ?? []) }}"
                                                data-users="{{ json_encode($item->target_user_ids ?? []) }}"
                                                data-emails="{{ $item->custom_emails ?? '' }}"
                                                data-priority="{{ $item->priority }}"
                                                title="{{ __('تعديل التخصيص والمستلمين') }}">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endforeach
</div>

<!-- Edit Setting Modal -->
<div class="modal fade" id="editNotificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom bg-label-primary py-3">
                <div class="d-flex align-items-center">
                    <i class="ti ti-adjustments-alt fs-4 me-2 text-primary"></i>
                    <h5 class="modal-title fw-bold mb-0 text-heading" id="modalSettingTitle">{{ __('تخصيص مستلمي الإشعار') }}</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSettingForm">
                <input type="hidden" id="edit_setting_id">
                <div class="modal-body p-4">
                    <!-- Target Roles -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('الأدوار المستلمة (Roles)') }}</label>
                        <select id="modal_target_roles" class="select2 form-select" multiple="multiple" data-placeholder="{{ __('اختر الأدوار المصرح لها') }}">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">{{ __('إذا تركت فارغة، سيتم إرسال الإشعار لجميع المدراء (Owner و Admin).') }}</small>
                    </div>

                    <!-- Target Specific Users -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('مستخدمون محددون (اختياري)') }}</label>
                        <select id="modal_target_user_ids" class="select2 form-select" multiple="multiple" data-placeholder="{{ __('اختر مستخدمين بعينهم') }}">
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">{{ __('يمكنك اختيار موظفين محددين لتوجيه الإشعار لهم مباشرة.') }}</small>
                    </div>

                    <!-- Custom Emails -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">{{ __('إيميلات إضافية مخصصة للبريد الخارجي') }}</label>
                        <textarea id="modal_custom_emails" class="form-control" rows="2" placeholder="finance@safedest.com, management@safedest.com"></textarea>
                        <small class="text-muted d-block mt-1">{{ __('افصل بين الإيميلات بفاصلة (,).') }}</small>
                    </div>

                    <!-- Priority -->
                    <div class="mb-2">
                        <label class="form-label fw-bold">{{ __('درجة الأهمية والتنبيه') }}</label>
                        <select id="modal_priority" class="form-select">
                            <option value="normal">{{ __('عادي (Normal)') }}</option>
                            <option value="high">{{ __('هام ومستعجل (High)') }}</option>
                            <option value="urgent">{{ __('طارئ وحرج (Urgent)') }}</option>
                            <option value="low">{{ __('منخفض (Low)') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveSetting">
                        <i class="ti ti-device-floppy me-1"></i> {{ __('حفظ التعديلات') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
