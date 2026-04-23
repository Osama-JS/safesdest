import { generateFields } from '../../ajax';

$(function () {
    const b2bModal = $('#b2bTaskModal');
    const b2bForm = $('#b2b-task-form');
    const companySelect = $('#b2b-company-id');
    const warehouseSelect = $('#b2b-warehouse-id');
    const clientSelect = $('#b2b-end-client-id');
    const vehicleSelect = $('#b2b-vehicle-id');
    const typeSelect = $('#b2b-vehicle-type-id');
    const sizeSelect = $('#b2b-vehicle-size-id');
    const quantityInput = $('#b2b-quantity');
    const templateSelect = $('#b2b-select-template');
    const additionalForm = $('#b2b-additional-form');
    const calcBtn = $('#b2b-calc-price-btn');
    const submitBtn = $('#b2b-submit-btn');

    // ─── INITIALIZATION ───────────────────────────────────────────

    // Initialize Select2 if available
    if ($.fn.select2) {
        b2bModal.find('.b2b-select2').select2({
            dropdownParent: b2bModal
        });

        clientSelect.select2({
            dropdownParent: b2bModal,
            placeholder: 'ابحث عن العميل (الاسم أو الكود)...',
            ajax: {
                url: function () {
                    const compId = companySelect.val();
                    if (!compId) return '';
                    return `${baseUrl}/admin/b2b/api/companies/${compId}/end-clients`;
                },
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        search: params.term,
                        page: params.page || 1
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.name + (item.client_code ? ' - ' + item.client_code : '')
                            };
                        }),
                        pagination: {
                            more: data.current_page < data.last_page
                        }
                    };
                },
                cache: true
            }
        });
    }

    // Dynamic Base URL from layout
    const baseUrl = $('html').attr('data-base-url') || '';

    // ─── EVENTS ───────────────────────────────────────────────────

    // 1. When Company changes -> Load Warehouses, Clients, and Vehicles
    companySelect.on('change', function () {
        const companyId = $(this).val();
        resetFields(['warehouse', 'client', 'vehicle', 'pricing']);

        if (!companyId) return;

        // Load Warehouses
        loadDropdown(warehouseSelect, `${baseUrl}/admin/b2b/api/companies/${companyId}/warehouses`);
        
        // Enable Client Select AJAX
        clientSelect.prop('disabled', false);

        // Load Vehicles (Brands/Models)
        loadDropdown(vehicleSelect, `${baseUrl}/admin/b2b/api/vehicles`);
    });

    // 1.1 Vehicle -> Type
    vehicleSelect.on('change', function () {
        const vehicleId = $(this).val();
        resetFields(['vehicle-type', 'vehicle-size', 'pricing']);
        if (!vehicleId) return;

        loadDropdown(typeSelect, `${baseUrl}/admin/settings/vehicles/types/${vehicleId}`);
    });

    // 1.2 Type -> Size
    typeSelect.on('change', function () {
        const typeId = $(this).val();
        resetFields(['vehicle-size', 'pricing']);
        if (!typeId) return;

        loadDropdown(sizeSelect, `${baseUrl}/admin/settings/vehicles/sizes/${typeId}`);
    });

    // 1.3 Handle Template Change
    templateSelect.on('change', function () {
        const templateId = $(this).val();
        additionalForm.html(''); // Clear previous fields

        if (templateId) {
            $.get(`${baseUrl}/admin/settings/templates/fields`, { id: templateId }, function (res) {
                if (res.fields) {
                    generateFields(res.fields, {}, '#b2b-additional-form');
                }
            });
        }
    });

    // 2. Enable/Disable Calc Button
    b2bForm.on('change', 'select', function() {
        validateInputs();
    });

    // 3. Calculate Price
    calcBtn.on('click', function () {
        calculatePrice();
    });

    // 4. Submit Form
    submitBtn.on('click', function () {
        submitB2bTask();
    });

    // ─── FUNCTIONS ────────────────────────────────────────────────

    function loadDropdown(element, url) {
        element.prop('disabled', true).html('<option value="">جاري التحميل...</option>');
        
        return $.get(url, function (data) {
            let options = '<option value="">اختر...</option>';
            
            // Handle different data structures (array or paginated object)
            const items = data.data || data;
            
            items.forEach(item => {
                const label = item.name_ar || item.name || item.contact_name;
                options += `<option value="${item.id}" data-meta='${JSON.stringify(item)}'>${label}</option>`;
            });

            element.html(options).prop('disabled', false);
        }).fail(() => {
            element.html('<option value="">فشل التحميل</option>');
        });
    }

    function validateInputs() {
        const isValid = companySelect.val() && warehouseSelect.val() && clientSelect.val() && sizeSelect.val();
        calcBtn.prop('disabled', !isValid);
        
        // If anything changed, hide previous pricing
        if (!isValid) {
            $('#b2b-pricing-result').addClass('d-none');
            submitBtn.prop('disabled', true);
        }
    }

    function calculatePrice() {
        calcBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        $('#b2b-pricing-result, #b2b-pricing-error').addClass('d-none');

        const payload = {
            _token: $('input[name="_token"]').val(),
            company_id: companySelect.val(),
            warehouse_id: warehouseSelect.val(),
            end_client_id: clientSelect.val(),
            vehicle_size_id: sizeSelect.val()
        };

        $.post(`${baseUrl}/admin/b2b/api/calculate-price`, payload, function (res) {
            if (res.status === 1) {
                const p = res.pricing;
                $('#b2b-base-price').text(p.base_price + ' ر.س');
                $('#b2b-vat-amount').text(p.vat_amount + ' ر.س');
                $('#b2b-total-price').text(p.total_price + ' ر.س');
                $('#b2b-pricing-rule-badge').text(getPricingLabel(p.pricing_rule));
                
                $('#b2b-pricing-result').removeClass('d-none');
                submitBtn.prop('disabled', false);
            }
        }).fail(err => {
            const msg = err.responseJSON?.message || 'خطأ في حساب السعر';
            $('#b2b-pricing-error-msg').text(msg);
            $('#b2b-pricing-error').removeClass('d-none');
        }).always(() => {
            calcBtn.prop('disabled', false).html('<i class="ti ti-calculator me-1"></i> احسب السعر');
        });
    }

    function submitB2bTask() {
        const taskId = $('#b2b-task-id').val();
        const url = taskId ? `${baseUrl}/admin/b2b/tasks/${taskId}` : `${baseUrl}/admin/b2b/tasks`;
        const method = taskId ? 'PUT' : 'POST';

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> جاري الحفظ...');

        const formData = new FormData(b2bForm[0]);
        if (taskId) formData.append('_method', 'PUT');

        $.ajax({
            url: url,
            method: 'POST', 
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.status === 1) {
                    Swal.fire({ icon: 'success', title: 'تم!', text: res.message, timer: 1500 });
                    b2bModal.modal('hide');
                    if (window.LaravelDataTables) {
                        window.LaravelDataTables["tasks-table"].draw();
                    } else {
                        location.reload();
                    }
                }
            },
            error: function (err) {
                const errors = err.responseJSON?.errors;
                $('.b2b-error').text('');
                if (errors) {
                    $.each(errors, function (key, val) {
                        $(`.b2b-error[data-field="${key}"]`).text(val[0]);
                    });
                } else {
                    Swal.fire('خطأ', err.responseJSON?.message || 'فشل الحفظ', 'error');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html('<i class="ti ti-check me-1"></i> حفظ المهمة');
            }
        });
    }

    function getPricingLabel(rule) {
        const labels = {
            'client_vehicle': 'سعر مخصص للعميل',
            'route_vehicle': 'سعر الشاحنة للمسار',
            'route_default': 'السعر الافتراضي للمسار'
        };
        return labels[rule] || 'تسعير مخصص';
    }

    function resetFields(types) {
        if (types.includes('warehouse')) warehouseSelect.html('<option value="">اختر...</option>').prop('disabled', true);
        if (types.includes('client')) clientSelect.html('<option value="">اختر...</option>').prop('disabled', true);
        if (types.includes('vehicle')) {
            vehicleSelect.html('<option value="">اختر المركبة...</option>').prop('disabled', true);
            typeSelect.html('<option value="">اختر النوع...</option>').prop('disabled', true);
            sizeSelect.html('<option value="">اختر الحجم...</option>').prop('disabled', true);
        }
        if (types.includes('vehicle-type')) {
            typeSelect.html('<option value="">اختر النوع...</option>').prop('disabled', true);
            sizeSelect.html('<option value="">اختر الحجم...</option>').prop('disabled', true);
        }
        if (types.includes('vehicle-size')) {
            sizeSelect.html('<option value="">اختر الحجم...</option>').prop('disabled', true);
        }
        if (types.includes('pricing')) {
            $('#b2b-pricing-result, #b2b-pricing-error').addClass('d-none');
            submitBtn.prop('disabled', true);
        }
    }

    // Export for Global Use (Edit Mode)
    window.openB2bEditModal = function (taskId) {
        b2bModal.modal('show');
        resetFields(['warehouse', 'client', 'vehicle', 'pricing']);
        $('#b2b-task-id').val(taskId);
        $('#b2bTaskModalTitle').text('تعديل مهمة B2B');
        $('#b2b-submit-label').text('حفظ التعديلات');

        $.get(`${baseUrl}/admin/b2b/tasks/${taskId}/data`, function (res) {
            if (res.status === 1) {
                const d = res.data;

                // 1. الأساسيات
                companySelect.val(d.company_id);
                $('#b2b-conditions').val(d.conditions);
                $('#b2b-delivery-before').val(d.delivery_before);

                // 2. تحميل المستودعات والعملاء
                const pWarehouse = loadDropdown(warehouseSelect, `${baseUrl}/admin/b2b/api/companies/${d.company_id}/warehouses`);
                clientSelect.prop('disabled', false);
                if (d.end_client) {
                    const newOpt = new Option(d.end_client.name, d.end_client_id, true, true);
                    clientSelect.append(newOpt).trigger('change');
                }

                // 3. تسلسل المركبات (Cascading Vehicles)
                const pVehicle = loadDropdown(vehicleSelect, `${baseUrl}/admin/b2b/api/vehicles`);

                $.when(pWarehouse, pVehicle).done(() => {
                    warehouseSelect.val(d.warehouse_id);

                    if (d.vehicle_id) {
                        vehicleSelect.val(d.vehicle_id);
                        loadDropdown(typeSelect, `${baseUrl}/admin/settings/vehicles/types/${d.vehicle_id}`).done(() => {
                            if (d.vehicle_type_id) {
                                typeSelect.val(d.vehicle_type_id);
                                loadDropdown(sizeSelect, `${baseUrl}/admin/settings/vehicles/sizes/${d.vehicle_type_id}`).done(() => {
                                    if (d.vehicle_size_id) {
                                        sizeSelect.val(d.vehicle_size_id);
                                    }
                                });
                            }
                        });
                    }
                });

                // 4. القوالب والبيانات الإضافية
                if (d.form_template_id) {
                    templateSelect.val(d.form_template_id);
                    $.get(`${baseUrl}/admin/settings/templates/fields`, { id: d.form_template_id }, function (res) {
                        if (res.fields) {
                            generateFields(res.fields, d.additional_data, '#b2b-additional-form');
                        }
                    });
                } else {
                    templateSelect.val('').trigger('change');
                }

                // 5. لقطة التسعير (Snapshot)
                if (d.pricing) {
                    const p = d.pricing;
                    $('#b2b-base-price').text(p.base_price + ' ر.س');
                    $('#b2b-vat-amount').text(p.vat_amount + ' ر.س');
                    $('#b2b-total-price').text(p.total_price + ' ر.س');
                    $('#b2b-pricing-rule-badge').text(getPricingLabel(p.pricing_rule));
                    $('#b2b-pricing-result').removeClass('d-none');
                    submitBtn.prop('disabled', false);
                }
            }
        });
    }
});
