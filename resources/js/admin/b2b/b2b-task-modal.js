/**
 * B2B Task Modal JavaScript Logic
 * Handles dynamic dropdowns, price calculation, and submission.
 */

$(function () {
    const b2bModal = $('#b2bTaskModal');
    const b2bForm = $('#b2b-task-form');
    const companySelect = $('#b2b-company-id');
    const warehouseSelect = $('#b2b-warehouse-id');
    const clientSelect = $('#b2b-end-client-id');
    const vehicleSelect = $('#b2b-vehicle-size-id');
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

        // Load Vehicles
        loadDropdown(vehicleSelect, `${baseUrl}/admin/b2b/api/vehicle-sizes`);
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
        
        $.get(url, function (data) {
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
        const isValid = companySelect.val() && warehouseSelect.val() && clientSelect.val() && vehicleSelect.val();
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
            vehicle_size_id: vehicleSelect.val()
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

        const formData = b2bForm.serializeArray();
        if (taskId) formData.push({name: '_method', value: 'PUT'});

        $.ajax({
            url: url,
            method: 'POST', // Always POST for Laravel if spoofing method
            data: formData,
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
        if (types.includes('vehicle')) vehicleSelect.html('<option value="">اختر...</option>').prop('disabled', true);
        if (types.includes('pricing')) {
            $('#b2b-pricing-result, #b2b-pricing-error').addClass('d-none');
            submitBtn.prop('disabled', true);
        }
    }

    // Export for Global Use (Edit Mode)
    window.openB2bEditModal = function(taskId) {
        b2bModal.modal('show');
        resetFields(['warehouse', 'client', 'vehicle', 'pricing']);
        $('#b2b-task-id').val(taskId);
        $('#b2bTaskModalTitle').text('تعديل مهمة B2B');
        $('#b2b-submit-label').text('تحديث المهمة');

        $.get(`${baseUrl}/admin/b2b/tasks/${taskId}/data`, function(res) {
            if (res.status === 1) {
                const d = res.data;
                companySelect.val(d.company_id).trigger('change');
                
                // Wait for dependent dropdowns to load (async)
                setTimeout(() => {
                    warehouseSelect.val(d.warehouse_id);

                    if (d.end_client) {
                        const newOpt = new Option(d.end_client.name, d.end_client_id, true, true);
                        clientSelect.append(newOpt).trigger('change');
                    }

                    vehicleSelect.val(d.vehicle_size_id);
                    $('#b2b-conditions').val(d.conditions);
                    
                    // Set Pricing Snapshot
                    const p = d.pricing;
                    $('#b2b-base-price').text(p.base_price + ' ر.س');
                    $('#b2b-vat-amount').text(p.vat_amount + ' ر.س');
                    $('#b2b-total-price').text(p.total_price + ' ر.س');
                    $('#b2b-pricing-rule-badge').text(getPricingLabel(p.pricing_rule));
                    $('#b2b-pricing-result').removeClass('d-none');
                    submitBtn.prop('disabled', false);
                }, 1000);
            }
        });
    }
});
