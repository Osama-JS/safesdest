@extends('layouts/layoutMaster')

@section('title', __('Templates'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

    <style>
        .sortable-ghost {
            background: #f0f8ff;
            border: 2px dashed #007bff;
            opacity: 0.7;
        }

        .drag-handle {
            cursor: grab;
        }

        .drag-handle:active {
            cursor: grabbing;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    @vite(['resources/assets/vendor/libs/sortablejs/sortable.js'])
@endsection

@section('page-script')
    @vite(['resources/js/admin/templates.js'])
    @vite(['resources/js/admin/pricing_template.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
    <script>
        let fieldIndex = {{ $data->fields->count() }};
        const formFields = @json($data->fields);
        const templateId = {{ $data->id }};
        const geoFences = @json($geofences);
    </script>
@endsection

@section('content')

    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">{{ __('Settings') }} | {{ __('Templates') }} |
                <span class="bg-info text-white px-2 rounded">
                    {{ $data->name }}</span>
            </h5>
            <p>{{ $data->description }}</p>
            <input type="hidden" class="form-control" id="template_id" value="{{ $data->id }}">

            <div class="mt-6 ">
                <span class="id-error text-danger text-error"></span>
                <span class="fields-error text-danger text-error"></span>
                <table class="table mb-6">
                    <thead>
                        <tr>
                            <th></th>
                            <th>name</th>
                            <th>label</th>
                            <th>driver can</th>
                            <th>customer can</th>
                            <th>type</th>
                            <th>value</th>
                            <th>require</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="fields_table">
                        @foreach ($data->fields as $key => $field)
                            @php
                                $fieldType = isset($field->type) ? $field->type : '';
                                $selectValues =
                                    $fieldType === 'select' && !empty($field->value)
                                        ? json_decode($field->value, true)
                                        : [];
                            @endphp

                            <tr class="form-field-row" data-id="{{ $field->id }}">
                                <td class="drag-handle" style="cursor: grab;">☰</td>
                                <td>
                                    <input type="text" class="form-control field-name-input" value="{{ $field->name }}">
                                    <span class="field-{{ $key }}-name-error text-danger text-error"></span>

                                </td>
                                <td>
                                    <input type="text" class="form-control field-label-input"
                                        value="{{ $field->label }}">
                                    <span class="field-{{ $key }}-label-error text-danger text-error"></span>

                                </td>
                                <td>
                                    <select class="form-control field-manager">
                                        <option value="hidden" {{ $field->driver_can == 'hidden' ? 'selected' : '' }}>
                                            Hidden
                                        </option>
                                        <option value="read" {{ $field->driver_can == 'read' ? 'selected' : '' }}>Read
                                            Only</option>
                                        <option value="write" {{ $field->driver_can == 'write' ? 'selected' : '' }}>Read &
                                            Write</option>
                                    </select>
                                    <span class="field-{{ $key }}-driver_can-error text-danger text-error"></span>

                                </td>
                                <td>
                                    <select class="form-control field-customer-can-select">
                                        <option value="hidden" {{ $field->customer_can == 'hidden' ? 'selected' : '' }}>
                                            Hidden</option>
                                        <option value="read" {{ $field->customer_can == 'read' ? 'selected' : '' }}>Read
                                            Only</option>
                                        <option value="write" {{ $field->customer_can == 'write' ? 'selected' : '' }}>Read
                                            & Write</option>
                                    </select>
                                    <span
                                        class="field-{{ $key }}-customer_can-error text-danger text-error"></span>

                                </td>
                                <td>
                                    <select class="form-control field-type-select">
                                        <option value="string" {{ $fieldType == 'string' ? 'selected' : '' }}>text</option>
                                        <option value="number" {{ $fieldType == 'number' ? 'selected' : '' }}>number
                                        </option>
                                        <option value="email" {{ $fieldType == 'email' ? 'selected' : '' }}>email
                                        </option>
                                        <option value="date" {{ $fieldType == 'date' ? 'selected' : '' }}>date</option>
                                        <option value="file" {{ $fieldType == 'file' ? 'selected' : '' }}>file</option>
                                        <option value="image" {{ $fieldType == 'image' ? 'selected' : '' }}>image</option>
                                        <option value="select" {{ $fieldType == 'select' ? 'selected' : '' }}>select
                                        </option>
                                    </select>
                                    <span class="field-{{ $key }}-type-error text-danger text-error"></span>

                                </td>
                                <td>
                                    <input type="text" class="form-control field-value-input"
                                        value="{{ $fieldType == 'select' ? '' : $field->value }}">
                                    <span class="field-{{ $key }}-value-error text-danger text-error"></span>

                                </td>
                                <td>
                                    <select class="form-control field-required-select">
                                        <option value="0" {{ !$field->required ? 'selected' : '' }}>NO</option>
                                        <option value="1" {{ $field->required ? 'selected' : '' }}>YES</option>
                                    </select>
                                    <span class="field-{{ $key }}-required-error text-danger text-error"></span>
                                </td>
                                <td><button class="btn btn-sm btn-icon text-danger remove-field"><i
                                            class="ti ti-trash"></i></button>
                                </td>
                            </tr>

                            @if ($field->type == 'select')
                                <tr class="select-values-table connected-row" data-id="{{ $field->id }}">
                                    <td colspan="4">
                                        <div class="p-2 border rounded  shadow-sm">
                                            <h6 class="text-primary">🔗 قيم الاختيار</h6>
                                            <table class="table ">
                                                <thead>
                                                    <tr>
                                                        <th>القيمة</th>
                                                        <th>الاسم الظاهر</th>
                                                        <th>إجراء</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="select-values-body">
                                                    @foreach (json_decode($field->value ?? '[]', true) as $option)
                                                        <tr>
                                                            <td><input type="text"
                                                                    class="form-control select-value-input"
                                                                    value="{{ $option['value'] }}"></td>
                                                            <td><input type="text" class="form-control select-name-input"
                                                                    value="{{ $option['name'] }}"></td>
                                                            <td class="text-center">
                                                                <button type="button"
                                                                    class="btn btn-sm btn-icon text-danger remove-select-value"><i
                                                                        class="ti ti-trash"></i></button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            <button type="button"
                                                class="btn btn-sm btn-icon text-primary add-select-value"> <i
                                                    class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>





                <button id="add_field" class="btn "> <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                    {{ __('More') }} </button>
                <button id="save_template" class="btn btn-primary">{{ __('save') }}</button>



            </div>

        </div>
    </div>

    <div class="col-md">

        <div id="accordionCustomIcon" class="accordion mt-4 accordion-custom-button">
            <div class="accordion-item">
                <h2 class="accordion-header text-body d-flex justify-content-between" id="accordionCustomIconOne">
                    <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse"
                        data-bs-target="#accordionCustomIcon-1" aria-controls="accordionCustomIcon-1">
                        <i class="ri-bar-chart-2-line me-2 ri-20px"></i>
                        <h5> Tasks Pricing</h5>
                    </button>
                </h2>

                <div id="accordionCustomIcon-1" class="accordion-collapse collapse" data-bs-parent="#accordionCustomIcon">
                    <div class="accordion-body">
                        <button class="add-new btn btn-primary waves-effect waves-light mb-5 mx-4" data-bs-toggle="modal"
                            data-bs-target="#submitModal">
                            <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                            <span class="d-none d-sm-inline-block"> {{ __('Add Pricing Module') }}</span>
                        </button>
                        <div class="card-datatable table-responsive">
                            <table class="table  datatables-pricing">
                                <thead class="border-top">
                                    <tr>
                                        <th></th> <!-- للعمود control -->
                                        <th>#</th> <!-- للـ fake_id -->
                                        <th>Role name</th>
                                        <th>Created at</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>


                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade " id="submitModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modelTitle">{{ __('Add Pricing Module') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST"
                    action="{{ route('settings.templates.pricing.store') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="form_id" value="{{ $data->id }}">
                        <input type="hidden" name="id" id="pricing_id">
                        <!-- Rule Name -->
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-md-8">
                                    <label class="form-label">* {{ __('Rule Name') }}</label>
                                    <input type="text" name="rule_name" class="form-control" placeholder="Role Name">
                                    <span class="rule_name-error text-danger text-error"></span>

                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">* {{ __('Set Decimal Places') }}</label>
                                    <input type="number" name="decimal_places" class="form-control"
                                        placeholder="Set Decimal Places For config the price" value="2">
                                    <span class="decimal_places-error text-danger text-error"></span>
                                </div>
                            </div>

                        </div>



                        <!-- Customers Selection -->
                        <div class="mb-3">
                            <div class="divider text-start">
                                <div class="divider-text"><strong>Customers Selections</strong></div>
                            </div>

                            <div class="mb-4">
                                <input type="checkbox" id="allCustomers" name="all_customers" value="true"
                                    class="form-check-input" checked>
                                <label for="allCustomers">Apply to All Customers</label>
                                <span class="all_customers-error text-danger text-error"></span>

                            </div>

                            <div class="row">
                                <!-- Tags -->
                                <div class="col-md-6">
                                    <label for="">Use customers tags </label>
                                    <input type="checkbox" id="useTags" name="use_tags" value="true"
                                        class="form-check-input mb-2">
                                    <select class="form-select select2-tags" name="tags[]" multiple id="tagsSelect"
                                        disabled>
                                        @foreach ($tags as $val)
                                            <option value="{{ $val->id }}">{{ $val->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="use_tags-error text-danger text-error"></span>
                                    <span class="tags-error text-danger text-error"></span>

                                </div>

                                <!-- Specific Customers -->
                                <div class="col-md-6">
                                    <label for="">Use Specific customers </label>
                                    <input type="checkbox" id="useCustomers" name="use_customers" value="true"
                                        class="form-check-input mb-2">
                                    <select class="form-select select2-customers" name="customers[]" multiple
                                        id="customersSelect" disabled>
                                        @foreach ($customers as $val)
                                            <option value="{{ $val->id }}">{{ $val->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="use_customers-error text-danger text-error"></span>
                                    <span class="customers-error text-danger text-error"></span>

                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Sizes -->
                        <div class="mb-3">
                            <div class="divider text-start">
                                <div class="divider-text"><strong>Vehicles Selections</strong></div>
                            </div>
                            <!-- vehicle tabs start -->
                            <div class="nav-align-top mb-6">
                                <ul class="nav nav-tabs mb-4" role="tablist">
                                    @foreach ($vehicle as $key => $val)
                                        <li class="nav-item">
                                            <button type="button" class="nav-link {{ $key === 0 ? 'active' : '' }}"
                                                role="tab" data-bs-toggle="tab"
                                                data-bs-target="#vehicle-{{ $val->id }}"
                                                aria-controls="vehicle-{{ $val->id }}"
                                                aria-selected="{{ $key === 0 ? 'true' : 'false' }}">
                                                {{ $val->name . ' - ' . $val->en_name }}
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="tab-content">
                                    @foreach ($vehicle as $key => $val)
                                        <div class="tab-pane fade show {{ $key === 0 ? 'active' : '' }}"
                                            id="vehicle-{{ $val->id }}" role="tabpanel">
                                            <div class="nav-align-left mb-6">
                                                <ul class="nav nav-tabs me-4" role="tablist">
                                                    @foreach ($val->types as $type => $type_val)
                                                        <li class="nav-item">
                                                            <button type="button"
                                                                class="nav-link {{ $type === 0 ? 'active' : '' }}"
                                                                role="tab" data-bs-toggle="tab"
                                                                data-bs-target="#type-{{ $type_val->id }}"
                                                                aria-controls="type-{{ $type_val->id }}"
                                                                aria-selected="true">{{ $type_val->name . ' - ' . $type_val->en_name }}</button>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                                <div class="tab-content ">
                                                    @foreach ($val->types as $type => $type_val)
                                                        <div class="tab-pane fade show {{ $type === 0 ? 'active' : '' }}"
                                                            id="type-{{ $type_val->id }}" role="tabpanel">
                                                            @foreach ($type_val->sizes as $size)
                                                                <div class="form-check mb-2">
                                                                    <input type="checkbox"
                                                                        class="form-check-input size-checkbox"
                                                                        id="size_{{ $size->id }}" name="sizes[]"
                                                                        value="{{ $size->id }}">
                                                                    <label class="form-check-label fw-bold"
                                                                        for="size_{{ $size->id }}">
                                                                        {{ $size->name }}
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <span class="sizes-error text-danger text-error"></span>

                            </div>
                        </div>

                        <!-- Pricing Inputs -->
                        <div class="mb-3">
                            <div class="divider text-start">
                                <div class="divider-text"><strong>Pricing</strong></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Base Fare</label>
                                    <input type="number" name="base_fare" class="form-control" placeholder="0.00" />
                                    <span class="base_fare-error text-danger text-error"></span>

                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Base Distance</label>
                                    <input type="number" name="base_distance" class="form-control" placeholder="km" />
                                    <span class="base_distance-error text-danger text-error"></span>

                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Base Waiting</label>
                                    <input type="number" name="base_waiting" class="form-control"
                                        placeholder="minuets" />
                                    <span class="base_waiting-error text-danger text-error"></span>

                                </div>
                                <div class="col-md-4"></div>

                                <div class="col-md-4">
                                    <label class="form-label">Distance Fare</label>
                                    <input type="number" name="distance_fare" min="0.00" class="form-control"
                                        placeholder="0.00" />
                                    <span class="distance_fare-error text-danger text-error"></span>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Waiting Fare</label>
                                    <input type="number" name="waiting_fare" min="0.00" class="form-control"
                                        placeholder="0.00" />
                                    <span class="waiting_fare-error text-danger text-error"></span>

                                </div>
                            </div>


                        </div>

                        <!-- Customize Pricing -->
                        <div class="mb-3">
                            <div class="divider text-start">
                                <div class="divider-text"><strong>Pricing Methods</strong></div>
                            </div>
                            @foreach ($pricing_methods as $method)
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input toggle-method"
                                        data-method-id="{{ $method->id }}" data-method-type="{{ $method->type }}"
                                        id="method_{{ $method->id }}" name="methods[]" value="{{ $method->id }}">
                                    <label class="form-check-label fw-bold" for="method_{{ $method->id }}">
                                        {{ $method->name }}
                                    </label>
                                </div>
                            @endforeach
                            <span class="methods-error text-danger text-error"></span>
                        </div>

                        <!-- Dynamic Pricing (جافاسكربت يضيف الحقول داخلها) -->
                        <div class="mb-3">
                            <div class="divider text-start">
                                <div class="divider-text"><strong>Dynamic Pricing Based on Field Values</strong></div>
                            </div>
                            <div>
                                <div class="row g-2 mb-2 field-pricing-row">
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm border add-field-pricing">
                                            <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i> add field
                                        </button>
                                    </div>
                                </div>
                                <div id="field-pricing-wrapper"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="divider text-start">
                                <div class="divider-text"><strong>Dynamic Pricing Based on Geo-fence </strong></div>
                            </div>

                            <button type="button" class="btn btn-sm border mb-2 add-geofence-pricing">
                                <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i> add geofence
                            </button>

                            <div id="geofence-pricing-wrapper"></div>
                        </div>


                        <!-- Commission -->
                        <div class="mb-3">
                            <div class="divider text-start">
                                <div class="divider-text"><strong>Commission</strong></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">VAT Commission</label>
                                    <input type="number" name="vat_commission" class="form-control" min="0.00"
                                        placeholder="0.00">
                                    <span class="vat_commission-error text-danger text-error"></span>

                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Service Tax Commission</label>
                                    <input type="number" name="service_commission" class="form-control" min="0.00"
                                        placeholder="0.00">
                                    <span class="service_commission-error text-danger text-error"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Discount -->
                        <div class="mb-3">
                            <div class="divider text-start">
                                <div class="divider-text"><strong>Discount Fare</strong></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Discount percentage %</label>
                                <input type="number" name="discount" class="form-control" min="0.00"
                                    placeholder="0.00">
                                <span class="discount-error text-danger text-error"></span>
                            </div>
                        </div>

                    </div>

                    <!-- Footer -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">Submit</button>
                    </div>
                </form>

            </div>
        </div>
    </div>


@endsection
