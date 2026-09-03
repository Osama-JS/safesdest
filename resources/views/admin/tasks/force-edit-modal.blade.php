@php
    $forceEditCustomers = \App\Models\Customer::select('id', 'name', 'phone')->get();
    $forceEditBrokers = \App\Models\Broker::select('id', 'name', 'phone')->get();
    $forceEditTemplates = \App\Models\Form_Template::all();
@endphp

<div class="modal fade" id="forceEditModal" tabindex="-1" aria-modal="true" role="dialog" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-warning text-dark py-3">
                <div class="d-flex align-items-center">
                    <i class="ti ti-alert-triangle fs-3 me-2"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="forceEditModalTitle">
                            {{ __('التعديل الإجباري للمهمة') }} <span id="force-edit-task-badge" class="badge bg-dark text-white ms-2"></span>
                        </h5>
                        <small class="text-dark-50">{{ __('تعديل تفاصيل المهمة بغض النظر عن حالتها التشغيلية مع الحفاظ الصارم على نوع وحجم الشاحنة والتسعير') }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="force-edit-task-form" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="id" id="force-task-id">

                <div class="modal-body p-4">
                    <!-- Warning / Info Alert -->
                    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                        <i class="ti ti-shield-lock fs-2 me-3"></i>
                        <div>
                            <h6 class="alert-heading mb-1 fw-bold">{{ __('تنبيه التعديل الإجباري (Force Update)') }}</h6>
                            <p class="mb-0 small">
                                {{ __('يتيح هذا الإجراء تعديل بيانات العميل، المحطات، المستندات، الشروط، الوسيط والتواريخ. لن يتم المساس بنوع الشاحنة وحجمها أو التسعير والسائق منعاً لحدوث أي تضارب تشغيلي أو مالي.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Readonly Operational Info Bar -->
                    <div class="card bg-light border mb-4">
                        <div class="card-body p-3">
                            <div class="row g-3 text-center">
                                <div class="col-md-3 border-end">
                                    <small class="text-muted d-block">{{ __('حالة المهمة الحالية') }}</small>
                                    <span id="force-task-status" class="badge bg-secondary mt-1">-</span>
                                </div>
                                <div class="col-md-3 border-end">
                                    <small class="text-muted d-block">{{ __('السائق المعين') }}</small>
                                    <span id="force-task-driver" class="fw-bold mt-1 d-block">-</span>
                                </div>
                                <div class="col-md-3 border-end">
                                    <small class="text-muted d-block">{{ __('نوع الشاحنة والحجم') }} <i class="ti ti-lock text-muted" title="{{ __('مقفل') }}"></i></small>
                                    <span id="force-task-vehicle" class="badge bg-label-primary mt-1">-</span>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">{{ __('حالة السداد') }}</small>
                                    <span id="force-task-payment" class="badge bg-label-info mt-1">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Left Column: Owner, Dates, Broker & Conditions -->
                        <div class="col-lg-6">
                            <div class="card border h-100 shadow-none">
                                <div class="card-header bg-label-secondary py-2 px-3 fw-bold">
                                    <i class="ti ti-user-check me-1"></i> {{ __('بيانات المالك والتواريخ') }}
                                </div>
                                <div class="card-body p-3">
                                    <!-- Owner Type -->
                                    <div class="mb-3">
                                        <label for="force-task-owner" class="form-label required fw-semibold">{{ __('نوع المالك (Owner Type)') }}</label>
                                        <select name="owner" id="force-task-owner" class="form-select">
                                            <option value="admin">{{ __('مسؤول النظام (Administrator)') }}</option>
                                            <option value="customer">{{ __('عميل (Customer)') }}</option>
                                        </select>
                                        <span class="text-danger small" id="err-owner"></span>
                                    </div>

                                    <!-- Customer Select -->
                                    <div class="mb-3" id="force-customer-wrapper" style="display: none;">
                                        <label for="force-task-customer" class="form-label required fw-semibold">{{ __('اختر العميل (Customer)') }}</label>
                                        <select name="customer" id="force-task-customer" class="form-select">
                                            <option value="">{{ __('-- اختر العميل --') }}</option>
                                            @foreach ($forceEditCustomers as $cust)
                                                <option value="{{ $cust->id }}">{{ $cust->name }} ({{ $cust->phone }})</option>
                                            @endforeach
                                        </select>
                                        <span class="text-danger small" id="err-customer"></span>
                                    </div>

                                    <!-- Created At -->
                                    <div class="mb-3">
                                        <label for="force-task-created-at" class="form-label fw-semibold">{{ __('تاريخ إنشاء المهمة') }}</label>
                                        <input type="datetime-local" name="created_at" id="force-task-created-at" class="form-control" />
                                        <span class="text-danger small" id="err-created_at"></span>
                                    </div>

                                    <!-- Broker Information -->
                                    <div class="border rounded p-3 mb-3 bg-white">
                                        <h6 class="fw-bold mb-2 text-primary"><i class="ti ti-briefcase me-1"></i> {{ __('بيانات الوسيط (Broker)') }}</h6>
                                        <div class="mb-2">
                                            <label for="force-task-broker" class="form-label">{{ __('الوسيط') }}</label>
                                            <select name="broker_id" id="force-task-broker" class="form-select">
                                                <option value="">{{ __('-- بدون وسيط --') }}</option>
                                                @foreach ($forceEditBrokers as $broker)
                                                    <option value="{{ $broker->id }}">{{ $broker->name }} ({{ $broker->phone }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="form-label">{{ __('نوع عمولة الوسيط') }}</label>
                                                <select name="broker_commission_type" id="force-task-broker-type" class="form-select form-select-sm">
                                                    <option value="fixed">{{ __('مبلغ ثابت') }}</option>
                                                    <option value="percentage">{{ __('نسبة مئوية (%)') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">{{ __('قيمة العمولة') }}</label>
                                                <input type="number" step="any" name="broker_commission_value" id="force-task-broker-value" class="form-control form-control-sm" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Conditions -->
                                    <div class="mb-3">
                                        <label for="force-task-conditions" class="form-label fw-semibold">{{ __('شروط وملاحظات المهمة') }}</label>
                                        <textarea name="conditions" id="force-task-conditions" class="form-control" rows="3" placeholder="{{ __('أدخل الشروط إن وجدت...') }}"></textarea>
                                        <span class="text-danger small" id="err-conditions"></span>
                                    </div>

                                    <!-- Template & Custom Fields -->
                                    <div class="border rounded p-3 bg-white">
                                        <h6 class="fw-bold mb-2 text-primary"><i class="ti ti-file-text me-1"></i> {{ __('النموذج والحقول الإضافية') }}</h6>
                                        <div class="mb-2">
                                            <label for="force-select-template" class="form-label">{{ __('قالب النموذج (Form Template)') }}</label>
                                            <select name="template" id="force-select-template" class="form-select">
                                                <option value="">{{ __('-- بدون نموذج إضافي --') }}</option>
                                                @foreach ($forceEditTemplates as $tmpl)
                                                    <option value="{{ $tmpl->id }}">{{ $tmpl->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div id="force-additional-fields" class="mt-3">
                                            <!-- Dynamic fields will be injected here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Pickup & Delivery Points -->
                        <div class="col-lg-6">
                            <!-- Pickup Point Card -->
                            <div class="card border mb-3 shadow-none">
                                <div class="card-header bg-label-success py-2 px-3 fw-bold">
                                    <i class="ti ti-map-pin me-1"></i> {{ __('نقطة الاستلام (Pickup Point)') }}
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-6">
                                            <label class="form-label required">{{ __('اسم جهة الاتصال') }}</label>
                                            <input type="text" name="pickup_name" id="force-pickup-name" class="form-control form-control-sm" required>
                                            <span class="text-danger small" id="err-pickup_name"></span>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label required">{{ __('رقم الهاتف') }}</label>
                                            <input type="text" name="pickup_phone" id="force-pickup-phone" class="form-control form-control-sm" required>
                                            <span class="text-danger small" id="err-pickup_phone"></span>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('البريد الإلكتروني') }}</label>
                                            <input type="email" name="pickup_email" id="force-pickup-email" class="form-control form-control-sm">
                                            <span class="text-danger small" id="err-pickup_email"></span>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label required">{{ __('الاستلام قبل (الموعد)') }}</label>
                                            <input type="datetime-local" name="pickup_before" id="force-pickup-before" class="form-control form-control-sm" required>
                                            <span class="text-danger small" id="err-pickup_before"></span>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label required">{{ __('العنوان التفصيلي') }}</label>
                                        <input type="text" name="pickup_address" id="force-pickup-address" class="form-control form-control-sm" required>
                                        <span class="text-danger small" id="err-pickup_address"></span>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label required">{{ __('خط العرض (Latitude)') }}</label>
                                            <input type="number" step="any" name="pickup_latitude" id="force-pickup-latitude" class="form-control form-control-sm" required>
                                            <span class="text-danger small" id="err-pickup_latitude"></span>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label required">{{ __('خط الطول (Longitude)') }}</label>
                                            <input type="number" step="any" name="pickup_longitude" id="force-pickup-longitude" class="form-control form-control-sm" required>
                                            <span class="text-danger small" id="err-pickup_longitude"></span>
                                        </div>
                                    </div>

                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-8">
                                            <label class="form-label">{{ __('ملاحظات الاستلام') }}</label>
                                            <input type="text" name="pickup_note" id="force-pickup-note" class="form-control form-control-sm" placeholder="{{ __('ملاحظة...') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label d-block">{{ __('صورة الاستلام') }}</label>
                                            <input type="file" name="pickup_image" id="force-pickup-image" class="form-control form-control-sm" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delivery Point Card -->
                            <div class="card border shadow-none">
                                <div class="card-header bg-label-info py-2 px-3 fw-bold">
                                    <i class="ti ti-truck-delivery me-1"></i> {{ __('نقطة التسليم (Delivery Point)') }}
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-6">
                                            <label class="form-label required">{{ __('اسم جهة الاتصال') }}</label>
                                            <input type="text" name="delivery_name" id="force-delivery-name" class="form-control form-control-sm" required>
                                            <span class="text-danger small" id="err-delivery_name"></span>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label required">{{ __('رقم الهاتف') }}</label>
                                            <input type="text" name="delivery_phone" id="force-delivery-phone" class="form-control form-control-sm" required>
                                            <span class="text-danger small" id="err-delivery_phone"></span>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-md-6">
                                            <label class="form-label">{{ __('البريد الإلكتروني') }}</label>
                                            <input type="email" name="delivery_email" id="force-delivery-email" class="form-control form-control-sm">
                                            <span class="text-danger small" id="err-delivery_email"></span>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label required">{{ __('التسليم قبل (الموعد)') }}</label>
                                            <input type="datetime-local" name="delivery_before" id="force-delivery-before" class="form-control form-control-sm" required>
                                            <span class="text-danger small" id="err-delivery_before"></span>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label required">{{ __('العنوان التفصيلي') }}</label>
                                        <input type="text" name="delivery_address" id="force-delivery-address" class="form-control form-control-sm" required>
                                        <span class="text-danger small" id="err-delivery_address"></span>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label required">{{ __('خط العرض (Latitude)') }}</label>
                                            <input type="number" step="any" name="delivery_latitude" id="force-delivery-latitude" class="form-control form-control-sm" required>
                                            <span class="text-danger small" id="err-delivery_latitude"></span>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label required">{{ __('خط الطول (Longitude)') }}</label>
                                            <input type="number" step="any" name="delivery_longitude" id="force-delivery-longitude" class="form-control form-control-sm" required>
                                            <span class="text-danger small" id="err-delivery_longitude"></span>
                                        </div>
                                    </div>

                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-8">
                                            <label class="form-label">{{ __('ملاحظات التسليم') }}</label>
                                            <input type="text" name="delivery_note" id="force-delivery-note" class="form-control form-control-sm" placeholder="{{ __('ملاحظة...') }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label d-block">{{ __('صورة التسليم') }}</label>
                                            <input type="file" name="delivery_image" id="force-delivery-image" class="form-control form-control-sm" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i> {{ __('إلغاء') }}
                    </button>
                    <button type="submit" class="btn btn-warning" id="force-edit-submit-btn">
                        <i class="ti ti-check me-1"></i> {{ __('تأكيد وحفظ التعديل الإجباري') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
