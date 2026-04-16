{{-- B2B Task Modal - إنشاء وتعديل مهام الشركات --}}
<div class="modal fade" id="b2bTaskModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="b2bTaskModalTitle">
                    <i class="ti ti-building me-2"></i>
                    {{ __('إنشاء مهمة شركة B2B') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <form id="b2b-task-form">
                    @csrf
                    <input type="hidden" id="b2b-task-id" name="task_id" value="">
                    <input type="hidden" id="b2b-form-method" value="POST">

                    {{-- ─── اختيار الشركة ─────────────────────────────── --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="ti ti-building me-1 text-primary"></i>
                            * {{ __('الشركة') }}
                        </label>
                        <select name="company_id" id="b2b-company-id" class="form-select b2b-select2">
                            <option value="">{{ __('اختر الشركة...') }}</option>
                            @foreach($companies ?? [] as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                        <span class="b2b-error text-danger small" data-field="company_id"></span>
                    </div>

                    <div class="row">
                        {{-- ─── المستودع (Pickup) ─────────────────────── --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-warehouse me-1 text-warning"></i>
                                * {{ __('المستودع (نقطة الاستلام)') }}
                            </label>
                            <select name="warehouse_id" id="b2b-warehouse-id" class="form-select" disabled>
                                <option value="">{{ __('اختر المستودع...') }}</option>
                            </select>
                            <span class="b2b-error text-danger small" data-field="warehouse_id"></span>
                            {{-- معاينة بيانات المستودع --}}
                            <div id="b2b-warehouse-preview" class="mt-2 p-2 bg-light rounded d-none small text-muted">
                                <i class="ti ti-map-pin me-1"></i><span id="b2b-warehouse-address"></span>
                            </div>
                        </div>

                        {{-- ─── العميل النهائي (Delivery) ──────────────── --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-user me-1 text-success"></i>
                                * {{ __('العميل النهائي (نقطة التسليم)') }}
                            </label>
                            <select name="end_client_id" id="b2b-end-client-id" class="form-select b2b-ajax-select2" disabled>
                                <option value="">{{ __('ابحث عن العميل...') }}</option>
                            </select>
                            <span class="b2b-error text-danger small" data-field="end_client_id"></span>
                            {{-- معاينة بيانات العميل --}}
                            <div id="b2b-client-preview" class="mt-2 p-2 bg-light rounded d-none small text-muted">
                                <i class="ti ti-map-pin me-1"></i><span id="b2b-client-city"></span>
                                <span id="b2b-client-code" class="badge bg-label-secondary ms-1"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- ─── نوع المركبة ─────────────────────────────── --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-truck me-1 text-info"></i>
                                * {{ __('نوع المركبة') }}
                            </label>
                            <select name="vehicle_size_id" id="b2b-vehicle-size-id" class="form-select" disabled>
                                <option value="">{{ __('اختر نوع المركبة...') }}</option>
                            </select>
                            <span class="b2b-error text-danger small" data-field="vehicle_size_id"></span>
                        </div>

                        {{-- ─── تاريخ التسليم ───────────────────────────── --}}
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="ti ti-calendar me-1 text-secondary"></i>
                                {{ __('التسليم قبل') }}
                            </label>
                            <input type="datetime-local"
                                   name="delivery_before"
                                   id="b2b-delivery-before"
                                   class="form-control"
                                   value="{{ now()->addHours(3)->format('Y-m-d\TH:i') }}">
                        </div>
                    </div>

                    {{-- ─── ملاحظات ──────────────────────────────────────── --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="ti ti-note me-1 text-secondary"></i>
                            {{ __('ملاحظات') }}
                        </label>
                        <textarea name="conditions" id="b2b-conditions" class="form-control" rows="2"
                                  placeholder="{{ __('أي شروط أو ملاحظات خاصة بهذه المهمة...') }}"></textarea>
                    </div>

                    {{-- ─── زر حساب السعر ────────────────────────────────── --}}
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" id="b2b-calc-price-btn" class="btn btn-outline-primary" disabled>
                            <i class="ti ti-calculator me-1"></i>
                            {{ __('احسب السعر') }}
                        </button>
                    </div>

                    {{-- ─── نتيجة التسعير (مخفية حتى الحساب) ──────────── --}}
                    <div id="b2b-pricing-result" class="d-none">
                        <div class="alert alert-success border-0 p-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="small text-muted">{{ __('السعر الأساسي') }}</div>
                                    <div class="fw-bold fs-6" id="b2b-base-price">—</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">{{ __('الضريبة') }}</div>
                                    <div class="fw-bold fs-6" id="b2b-vat-amount">—</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted fw-semibold">{{ __('الإجمالي') }}</div>
                                    <div class="fw-bold fs-5 text-success" id="b2b-total-price">—</div>
                                </div>
                            </div>
                            <div class="text-center mt-2">
                                <span class="badge bg-label-primary" id="b2b-pricing-rule-badge"></span>
                            </div>
                        </div>
                    </div>

                    {{-- ─── خطأ التسعير ──────────────────────────────────── --}}
                    <div id="b2b-pricing-error" class="alert alert-danger d-none">
                        <i class="ti ti-alert-circle me-1"></i>
                        <span id="b2b-pricing-error-msg"></span>
                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                    {{ __('إلغاء') }}
                </button>
                <button type="button" id="b2b-submit-btn" class="btn btn-primary" disabled>
                    <i class="ti ti-check me-1"></i>
                    <span id="b2b-submit-label">{{ __('إنشاء المهمة') }}</span>
                </button>
            </div>

        </div>
    </div>
</div>
