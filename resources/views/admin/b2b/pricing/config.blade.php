@extends('layouts/layoutMaster')

@section('title', __('Company Pricing Config') . ' — ' . $company->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
<script>
$(function () {

    // Toggle commission value label
    function updateCommissionLabel() {
        const type = $('#commission_type').val();
        $('#commission-unit').text(type === 'percentage' ? '%' : '{{ __("SAR") }}');
    }
    $('#commission_type').on('change', updateCommissionLabel);
    updateCommissionLabel();

    // Save config
    $('#config-form').on('submit', function (e) {
        e.preventDefault();
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.text-error').text('');

        const data = {
            _token: "{{ csrf_token() }}",
            commission_type:  $('#commission_type').val(),
            commission_value: $('#commission_value').val(),
            vat_percentage:   $('#vat_percentage').val(),
        };
        $.post("{{ route('b2b.config.save', $company->id) }}", data, function (res) {
            if (res.status === 1) {
                Swal.fire({ icon: 'success', text: res.success, timer: 2000, showConfirmButton: false });
            } else if (res.status === 0) {
                // Validation Errors
                if (typeof res.error === 'object') {
                    $.each(res.error, function (key, messages) {
                        var field = $(`[name="${key}"]`);
                        field.addClass('is-invalid');
                        $(`.${key}-error`).text(messages[0]);
                    });
                     Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please check the configuration values.',
                        customClass: { confirmButton: 'btn btn-primary' }
                    });
                } else {
                    Swal.fire('خطأ', res.error, 'error');
                }
            } else {
                Swal.fire('خطأ', res.error || 'Something went wrong', 'error');
            }
        });
    });
});
</script>
@endsection

@section('content')
<h4 class="py-3 mb-4">
    <span class="text-muted fw-light">
        <a href="{{ route('b2b.companies') }}">{{ __('B2B Module') }}</a> /
        {{ $company->name }} /
    </span>
    {{ __('Commission & VAT Configuration') }}
    <a href="{{ route('b2b.pricing.index', $company->id) }}" class="btn btn-sm btn-outline-primary ms-2">
        <i class="ti ti-table me-1"></i>{{ __('Pricing Matrix') }}
    </a>
</h4>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="ti ti-settings me-1 text-primary"></i>
                    {{ __('Pricing Configuration for') }}: <strong>{{ $company->name }}</strong>
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <i class="ti ti-info-circle me-1"></i>
                    {{ __('These settings control how the platform calculates the final task price for this company. The formula is:') }}
                    <br>
                    <code>{{ __('Final Price = (Base Price + Commission) × (1 + VAT%)') }}</code>
                </div>

                <form id="config-form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('Commission Type') }}</label>
                            <select id="commission_type" name="commission_type" class="form-select">
                                <option value="percentage" {{ old('commission_type', $config->commission_type) === 'percentage' ? 'selected' : '' }}>
                                    {{ __('Percentage (%)') }}
                                </option>
                                <option value="fixed" {{ old('commission_type', $config->commission_type) === 'fixed' ? 'selected' : '' }}>
                                    {{ __('Fixed Amount (SAR)') }}
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('Commission Value') }}</label>
                            <div class="input-group">
                                <input type="number" id="commission_value" name="commission_value"
                                    class="form-control"
                                    value="{{ old('commission_value', $config->commission_value) }}"
                                    min="0" step="0.01" required>
                                <span class="input-group-text" id="commission-unit">%</span>
                            </div>
                            <span class="commission_value-error text-danger small text-error"></span>
                            <small class="text-muted d-block mt-1">{{ __('Platform commission per task for this company') }}</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('VAT Percentage (%)') }}</label>
                            <div class="input-group">
                                <input type="number" id="vat_percentage" name="vat_percentage"
                                    class="form-control"
                                    value="{{ old('vat_percentage', $config->vat_percentage ?? 15.00) }}"
                                    min="0" max="100" step="0.01" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <span class="vat_percentage-error text-danger small text-error"></span>
                            <small class="text-muted d-block mt-1">{{ __('Default: 15% (Saudi VAT)') }}</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted">{{ __('Price Calculation Preview') }}</label>
                            <div class="card bg-light border-0">
                                <div class="card-body py-2 small">
                                    <div>{{ __('Base Price') }}: <strong>100 SAR</strong></div>
                                    <div>{{ __('+ Commission') }}: <strong class="text-primary">{{ $config->commission_value ?? 0 }} {{ $config->commission_type === 'percentage' ? '%' : 'SAR' }}</strong></div>
                                    <div>{{ __('+ VAT') }}: <strong class="text-warning">{{ $config->vat_percentage ?? 15 }}%</strong></div>
                                    <hr class="my-1">
                                    <div class="fw-bold text-success">
                                        {{ __('Total') }}: {{ number_format(
                                            (100 + ($config->commission_type === 'percentage' ? 100 * ($config->commission_value ?? 0) / 100 : ($config->commission_value ?? 0))) *
                                            (1 + ($config->vat_percentage ?? 15) / 100)
                                        , 2) }} SAR
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-primary px-5">
                            <i class="ti ti-device-floppy me-1"></i>{{ __('Save Configuration') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
