@extends('layouts/layoutMaster')

@section('title', __('Users'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/spinkit/spinkit.scss'])

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
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/block-ui/block-ui.js'])
    @vite(['resources/assets/vendor/libs/sortablejs/sortable.js'])
@endsection

@section('page-script')
    @vite(['resources/js/admin/templates.js'])
    @vite(['resources/js/admin/pricing_template.js'])

    @vite(['resources/js/ajax.js'])
    @vite(['resources/js/model.js'])
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
                        @foreach ($data->fields as $field)
                            @php
                                $fieldType = isset($field->type) ? $field->type : '';
                                $selectValues =
                                    $fieldType === 'select' && !empty($field->value)
                                        ? json_decode($field->value, true)
                                        : [];
                            @endphp

                            <tr class="form-field-row" data-id="{{ $field->id }}">
                                <td class="drag-handle" style="cursor: grab;">☰</td>
                                <td><input type="text" class="form-control field-name-input" value="{{ $field->name }}">
                                <td><input type="text" class="form-control field-label-input"
                                        value="{{ $field->label }}">
                                </td>
                                <td>
                                    <select class="form-control field-manager">
                                        <option value="hidden" {{ $field->driver_can == 'hidden' ? 'selected' : '' }}>Hidden
                                        </option>
                                        <option value="read" {{ $field->driver_can == 'read' ? 'selected' : '' }}>Read
                                            Only</option>
                                        <option value="write" {{ $field->driver_can == 'write' ? 'selected' : '' }}>Read &
                                            Write</option>
                                    </select>
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
                                </td>
                                <td>
                                    <select class="form-control field-type-select">
                                        <option value="string" {{ $fieldType == 'string' ? 'selected' : '' }}>نص</option>
                                        <option value="number" {{ $fieldType == 'number' ? 'selected' : '' }}>رقم</option>
                                        <option value="email" {{ $fieldType == 'email' ? 'selected' : '' }}>بريد إلكتروني
                                        </option>
                                        <option value="date" {{ $fieldType == 'date' ? 'selected' : '' }}>تاريخ</option>
                                        <option value="select" {{ $fieldType == 'select' ? 'selected' : '' }}>اختيار
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control field-value-input"
                                        value="{{ $fieldType == 'select' ? '' : $field->value }}">
                                </td>
                                <td>
                                    <select class="form-control field-required-select">
                                        <option value="0" {{ !$field->required ? 'selected' : '' }}>اختياري</option>
                                        <option value="1" {{ $field->required ? 'selected' : '' }}>إلزامي</option>
                                    </select>
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
                            <table class="table table-striped" id="pricing-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Role name</th>
                                        <th scope="col">Created at</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>

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
                    action="{{ route('settings.pricing.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Rule Name</label>
                            <input type="text" class="form-control" value="Dyana Open 4.5 Ton">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Set Decimal Places</label>
                            <input type="number" class="form-control" value="2">
                        </div>

                        <div class="mb-3">
                            <div class="divider text-start">
                                <div class="divider-text"><strong>Customers Selections</strong></div>
                            </div>

                            <div class="mb-4">
                                <input type="checkbox" name="" id="" class="form-check-input" checked>

                                <label for="">Apply to All Customers </label>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="">Use customers tags </label>
                                    <input type="checkbox" name="" id="" class="form-check-input mb-2">
                                    <select class="form-select">
                                        <option>-Select tags-</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="">Use Specific customers </label>
                                    <input type="checkbox" name="" id="" class="form-check-input mb-2">
                                    <select class="form-select">
                                        <option>-Select Customers-</option>
                                    </select>
                                </div>
                            </div>

                        </div>

                        <div class="mb-3 ">
                            <div class="divider text-start">
                                <div class="divider-text"><strong>Vehicles Selections</strong></div>
                            </div>

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
                                                                        class="form-check-input size-checkbox "
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
                            </div>
                        </div>


                        <div class="mb-3">
                            <div class="divider text-start">
                                <div class="divider-text "><strong>Pricing</strong></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Base Fare</label>
                                    <input type="number" class="form-control" value="0.00">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"> Base Duration</label>
                                    <input type="number" class="form-control" value="0.00">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"> Base Distance</label>
                                    <input type="number" class="form-control" value="1.45">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"> Base Waiting</label>
                                    <input type="number" class="form-control" value="0.00">
                                </div>
                                <div class="col-md-3">

                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Duration Fare</label>
                                    <input type="number" class="form-control" value="0.00">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Distance Fare</label>
                                    <input type="number" class="form-control" value="1.45">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Waiting Fare</label>
                                    <input type="number" class="form-control" value="0.00">
                                </div>
                            </div>

                            <label class="form-label">Customize Pricing</label>
                            <div class="mb-3">
                                <button id="add_field" type="button" class="btn "> <i
                                        class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                                    {{ __('add pricing method') }} </button>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Discount Fare</label>
                                <input type="number" class="form-control" value="0.00">
                            </div>


                        </div>
                        <div class="mb-3">
                            <div class="divider text-start">
                                <div class="divider-text "><strong>Commission</strong></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">VAT Commission</label>
                                    <input type="number" class="form-control" value="100.00">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Service Tax Commission</label>
                                    <input type="number" class="form-control" value="100.00">
                                </div>
                            </div>
                        </div>




                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary me-3 data-submit">Submit</button>

                    </div>
                </form>

            </div>
        </div>
    </div>

@endsection
