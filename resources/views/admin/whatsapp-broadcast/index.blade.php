@extends('layouts/layoutMaster')

@section('title', __('Send WhatsApp Message'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
    <style>
        .wa-preview-box {
            background-color: #e5ddd5;
            background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png');
            background-repeat: repeat;
            border-radius: 8px;
            padding: 20px;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }

        .wa-bubble-out {
            background-color: #dcf8c6;
            border-radius: 7.5px;
            padding: 8px 10px;
            position: relative;
            max-width: 90%;
            margin-left: auto;
            margin-bottom: 5px;
            box-shadow: 0 1px 0.5px rgba(0, 0, 0, 0.13);
        }

        .wa-bubble-out::after {
            content: '';
            position: absolute;
            top: 0;
            right: -8px;
            width: 0;
            height: 0;
            border-top: 10px solid #dcf8c6;
            border-right: 10px solid transparent;
        }

        .wa-bubble-time {
            font-size: 11px;
            color: rgba(0, 0, 0, 0.45);
            text-align: right;
            margin-top: -10px;
            margin-bottom: -5px;
        }

        .wa-bubble-check {
            color: #4fc3f7;
            margin-left: 2px;
        }
    </style>
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">{{ __('WhatsApp') }} /</span> {{ __('Send Message Manually') }}
</h4>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <h5 class="card-header">{{ __('Broadcast Settings') }}</h5>
            <div class="card-body">
                <form id="broadcastForm">
                    @csrf
                    
                    <!-- Target Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">{{ __('Select Recipients Group') }}</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check custom-option custom-option-basic">
                                <label class="form-check-label custom-option-content" for="targetCustomers">
                                    <input name="target_type" class="form-check-input" type="radio" value="customers" id="targetCustomers" checked />
                                    <span class="custom-option-header">
                                        <span class="h6 mb-0">{{ __('Customers') }}</span>
                                    </span>
                                </label>
                            </div>
                            <div class="form-check custom-option custom-option-basic">
                                <label class="form-check-label custom-option-content" for="targetDrivers">
                                    <input name="target_type" class="form-check-input" type="radio" value="drivers" id="targetDrivers" />
                                    <span class="custom-option-header">
                                        <span class="h6 mb-0">{{ __('Drivers') }}</span>
                                    </span>
                                </label>
                            </div>
                            <div class="form-check custom-option custom-option-basic">
                                <label class="form-check-label custom-option-content" for="targetCustom">
                                    <input name="target_type" class="form-check-input" type="radio" value="custom" id="targetCustom" />
                                    <span class="custom-option-header">
                                        <span class="h6 mb-0">{{ __('Custom Numbers') }}</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Target IDs (Select2) -->
                    <div class="mb-4" id="targetIdsSection">
                        <label class="form-label" for="targetIds">{{ __('Select Recipients') }}</label>
                        <select id="targetIds" name="target_ids[]" class="select2 form-select" multiple data-placeholder="{{ __('Search for recipients...') }}">
                        </select>
                        <div class="form-text">{{ __('Leave empty if you want to send to all? No, you must select them manually here.') }}</div>
                    </div>

                    <!-- Custom Numbers (Textarea) -->
                    <div class="mb-4 d-none" id="customNumbersSection">
                        <label class="form-label" for="customNumbers">{{ __('Phone Numbers') }}</label>
                        <textarea class="form-control" id="customNumbers" name="custom_numbers" rows="3" placeholder="Ex: 966500000000, 966500000001"></textarea>
                        <div class="form-text">{{ __('Enter numbers separated by commas. Please include the country code without + or zeros. E.g. 9665...') }}</div>
                    </div>

                    <hr class="my-4">

                    <!-- Template Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold" for="templateSelect">{{ __('Select Template') }}</label>
                        <select id="templateSelect" name="template_id" class="form-select select2" required>
                            <option value="">{{ __('Choose a template...') }}</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" data-purpose="{{ $template->purpose }}">{{ $template->template_name }} ({{ $template->language }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Dynamic Variables -->
                    <div id="dynamicVariablesSection" class="mb-4 d-none">
                        <label class="form-label fw-bold">{{ __('Template Variables') }}</label>
                        <div class="alert alert-info py-2 mb-3">
                            <i class="ti ti-info-circle me-1"></i> {{ __('This template requires dynamic variables to be filled.') }}
                        </div>
                        <div id="variablesContainer"></div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="ti ti-send me-1"></i> {{ __('Send Messages') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Live Preview -->
    <div class="col-md-4">
        <div class="card mb-4" style="position: sticky; top: 20px;">
            <h5 class="card-header">{{ __('Live Preview') }}</h5>
            <div class="card-body">
                <div class="wa-preview-box">
                    <div class="wa-bubble-out">
                        <div id="previewText" style="white-space: pre-wrap; font-size: 14px; margin-bottom: 12px; color: #111b21;">
                            {{ __('Select a template to preview.') }}
                        </div>
                        <div class="wa-bubble-time">
                            {{ now()->format('H:i') }} <span class="wa-bubble-check">✓✓</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script type="module">
$(document).ready(function() {
    var searchUrl = '{{ route("admin.whatsapp-broadcast.search") }}';
    var templateUrl = '{{ url("admin/whatsapp-broadcast/template") }}';
    var currentTemplateText = '';

    // Initialize Select2
    $('.select2').select2();

    // Initialize Select2 for dynamic searching
    function initTargetSelect2(type) {
        $('#targetIds').empty().trigger('change');
        $('#targetIds').select2({
            ajax: {
                url: searchUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        type: type
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            placeholder: '{{ __("Search for recipients...") }}',
            minimumInputLength: 0
        });
    }

    // Default init
    initTargetSelect2('customers');

    // Handle Radio Changes
    $('input[name="target_type"]').change(function() {
        var val = $(this).val();
        if (val === 'custom') {
            $('#targetIdsSection').addClass('d-none');
            $('#customNumbersSection').removeClass('d-none');
        } else {
            $('#targetIdsSection').removeClass('d-none');
            $('#customNumbersSection').addClass('d-none');
            initTargetSelect2(val);
        }
    });

    // Handle Template Selection
    $('#templateSelect').change(function() {
        var id = $(this).val();
        if (!id) {
            $('#dynamicVariablesSection').addClass('d-none');
            $('#variablesContainer').empty();
            $('#previewText').text('{{ __("Select a template to preview.") }}');
            currentTemplateText = '';
            return;
        }

        $.get(templateUrl + '/' + id, function(data) {
            if (!data.purpose) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Warning',
                    text: '{{ __("This template does not have a purpose set. You cannot send it until it has a purpose.") }}'
                });
            }

            var text = data.body_text || '';
            currentTemplateText = text;
            
            // Find all variables like {{1}}, {{2}}
            var matches = text.match(/\{\{(\d+)\}\}/g);
            var uniqueVars = [];
            if (matches) {
                matches.forEach(function(m) {
                    var num = m.replace(/\{\{|\}\}/g, '');
                    if (!uniqueVars.includes(num)) {
                        uniqueVars.push(num);
                    }
                });
                uniqueVars.sort(); // 1, 2, 3...
            }

            if (uniqueVars.length > 0) {
                $('#dynamicVariablesSection').removeClass('d-none');
                $('#variablesContainer').empty();

                uniqueVars.forEach(function(num) {
                    var html = `
                        <div class="mb-3">
                            <label class="form-label">Variable @{{${num}}}</label>
                            <input type="text" name="variables[${num}]" class="form-control template-var-input" data-var="${num}" required placeholder="Value for @{{${num}}}">
                        </div>
                    `;
                    $('#variablesContainer').append(html);
                });
            } else {
                $('#dynamicVariablesSection').addClass('d-none');
                $('#variablesContainer').empty();
            }

            updatePreview();
        });
    });

    // Update Live Preview when typing variables
    $(document).on('input', '.template-var-input', function() {
        updatePreview();
    });

    function updatePreview() {
        if (!currentTemplateText) return;
        
        var preview = currentTemplateText;
        $('.template-var-input').each(function() {
            var val = $(this).val();
            var varNum = $(this).data('var');
            if (val) {
                preview = preview.replace(new RegExp('\\{\\{' + varNum + '\\}\\}', 'g'), val);
            }
        });

        $('#previewText').text(preview);
    }

    // Submit Form
    $('#broadcastForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = $('#btnSubmit');
        var originalBtnText = submitBtn.html();

        submitBtn.html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> {{ __("Sending...") }}').prop('disabled', true);

        $.ajax({
            url: '{{ route("admin.whatsapp-broadcast.send") }}',
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                submitBtn.html(originalBtnText).prop('disabled', false);
                if (response.status) {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("Success") }}',
                        text: response.success,
                    }).then(() => {
                        window.location.href = '{{ route("admin.whatsapp-logs.index") }}';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("Error") }}',
                        text: response.error,
                    });
                }
            },
            error: function() {
                submitBtn.html(originalBtnText).prop('disabled', false);
                Swal.fire({
                    icon: 'error',
                    title: '{{ __("Error") }}',
                    text: '{{ __("A server error occurred. Please try again.") }}',
                });
            }
        });
    });
});
</script>
@endsection
