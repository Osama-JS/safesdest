/**
 * Page User List
 */

'use strict';
import { deleteRecord, showAlert, showFormModal } from '../ajax';

// Datatable (jquery)
$(function () {
  var dt_data_table = $('.datatables-pricing');
  let fieldPricingIndex = 1;
  let geofencePricingIndex = 0;
  let pricingParamsIndex = 0;
  let pricingPoints = 0;

  // ajax setup
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  /* ====================== Configure Tags Selection  =============================== */
  var select_tags = $('.select2-tags');
  if (select_tags.length) {
    var $this = select_tags;
    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: 'Select Tags',
      dropdownParent: $this.parent(),
      closeOnSelect: false
    });
  }

  /* ====================== Configure Customers Selection  =============================== */
  var select_customers = $('.select2-customers');
  if (select_customers.length) {
    var $this = select_customers;
    $this.wrap('<div class="position-relative"></div>').select2({
      allowClear: true,
      placeholder: 'Select Customers',
      dropdownParent: $this.parent(),
      closeOnSelect: false
    });
  }

  /* ====================== DataTable Configuration  =============================== */
  if (dt_data_table.length) {
    var dt_data = dt_data_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: baseUrl + 'admin/settings/templates/pricing/data/' + templateId
      },
      columns: [{ data: '' }, { data: 'fake_id' }, { data: 'name' }, { data: 'created_at' }, { data: null }],
      columnDefs: [
        {
          // For Responsive
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 1,
          targets: 0,
          render: function (data, type, full, meta) {
            return '';
          }
        },
        {
          searchable: false,
          orderable: false,
          targets: 1,
          render: function (data, type, full, meta) {
            return `<span>${full.fake_id}</span>`;
          }
        },
        {
          // User full name
          targets: 2,
          responsivePriority: 4,
          render: function (data, type, full, meta) {
            return full['name'];
          }
        },
        {
          targets: 3,
          render: function (data, type, full, meta) {
            return full['created_at'];
          }
        },

        {
          // Actions
          targets: 4,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center gap-50">' +
              `<button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}" data-name="${full['name']}"  data-guard="${full['guard']}" data-bs-toggle="modal" data-bs-target="#largeModal"><i class="ti ti-edit"></i></button>` +
              `<button class="btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect" data-id="${full['id']}" data-name="${full['name']}"><i class="ti ti-trash"></i></button> </div>`
            );
          }
        }
      ],
      order: [[2, 'desc']],
      dom:
        '<"row"' +
        '<"col-md-2"<"ms-n2"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-6 mb-md-0 mt-n6 mt-md-0"fB>>' +
        '>t' +
        '<"row"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      lengthMenu: [10, 20, 50, 100], //for length of menu
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search...',
        info: 'Displaying _START_ to _END_ of _TOTAL_ entries',
        paginate: {
          next: '<i class="ti ti-chevron-right ti-sm"></i>',
          previous: '<i class="ti ti-chevron-left ti-sm"></i>'
        }
      },
      // Buttons
      buttons: [],
      // For responsive popup
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data.name;
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col) {
              return col.title
                ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                      <td>${col.title}:</td>
                      <td>${col.data}</td>
                   </tr>`
                : '';
            }).join('');
            return $('<table class="table"/><tbody />').append(data);
          }
        }
      },
      initComplete: function () {
        $('.dataTables_filter input').removeClass(' form-control-sm'); // عدّل حسب الكلاسات اللي تبغى تشيلها
      }
    });
    document.dispatchEvent(new CustomEvent('dtUserReady', { detail: dt_data }));
  }

  /* ====================== Form Submit Event Actions  =============================== */
  document.addEventListener('formSubmitted', function (event) {
    dt_data.draw();
    setTimeout(() => {
      $('#submitModal').modal('hide');
    }, 2000);
  });

  /* ====================== Delete Record Event Actions  =============================== */
  document.addEventListener('deletedSuccess', function (event) {
    if (dt_data) {
      dt_data.draw();
    }
  });

  /* ====================== Get Points  =============================== */
  let allPoints = []; // قائمة مسطحة بالنقاط لكل الاستخدامات العامة
  let groupedOptionsHTML = ''; // HTML كامل يستخدم داخل كل select

  function fetchPoints(customerIds = []) {
    $.ajax({
      url: `${baseUrl}admin/settings/points/get`,
      type: 'POST',
      data: {
        customer_ids: customerIds,
        _token: $('meta[name="csrf-token"]').attr('content')
      },
      success: function (data) {
        allPoints = [];
        groupedOptionsHTML = '';

        // النقاط العامة
        if (data.general && data.general.length > 0) {
          groupedOptionsHTML += `<optgroup label="نقاط عامة">`;
          data.general.forEach(point => {
            groupedOptionsHTML += `<option value="${point.id}">${point.name}</option>`;
            allPoints.push(point);
          });
          groupedOptionsHTML += `</optgroup>`;
        }

        // النقاط المرتبطة بالعملاء
        Object.keys(data).forEach(key => {
          if (key.startsWith('customer_')) {
            const group = data[key];
            if (group.points.length > 0) {
              groupedOptionsHTML += `<optgroup label="${group.label}">`;
              group.points.forEach(point => {
                groupedOptionsHTML += `<option value="${point.id}">${point.name}</option>`;
                allPoints.push(point);
              });
              groupedOptionsHTML += `</optgroup>`;
            }
          }
        });

        // تحديث كل الحقول select الحالية
        $('.from-input.select-point, .to-input.select-point').each(function () {
          const currentVal = $(this).val();
          $(this).html(`<option value="">اختر نقطة</option>` + groupedOptionsHTML);

          if ($(this).find(`option[value="${currentVal}"]`).length) {
            $(this).val(currentVal);
          } else {
            $(this).val('');
          }
        });
      }
    });
  }

  // تحميل النقاط العامة افتراضيًا
  fetchPoints();

  // إعادة تحميل النقاط عند تغيير العملاء
  $('#customersSelect').on('change', function () {
    const customerIds = $(this).val();
    fetchPoints(customerIds);
  });

  /* ====================== Edit Action Button  =============================== */
  function waitForSelect(selector) {
    return new Promise(resolve => {
      const interval = setInterval(() => {
        const $el = $(selector);
        if ($el.length) {
          clearInterval(interval);
          resolve($el);
        }
      }, 50);
    });
  }

  $(document).on('click', '.edit-record', function () {
    let id = $(this).data('id');
    $.get(`${baseUrl}admin/settings/templates/pricing/edit/${id}`, function (data) {
      $('.text-error').html(''); // تنظيف الأخطاء السابقة

      // حقول ثابتة
      $('#pricing_id').val(data.id);
      $('input[name="rule_name"]').val(data.rule_name);
      $('input[name="decimal_places"]').val(data.decimal_places);
      $('input[name="base_fare"]').val(data.base_fare);
      $('input[name="base_distance"]').val(data.base_distance);
      $('input[name="base_waiting"]').val(data.base_waiting);
      $('input[name="distance_fare"]').val(data.distance_fare);
      $('input[name="waiting_fare"]').val(data.waiting_fare);
      $('input[name="vat_commission"]').val(data.vat_commission);
      $('input[name="service_commission"]').val(data.service_commission);
      $('input[name="discount"]').val(data.discount);

      // تحديد حالة checkboxes
      $('#allCustomers').prop('checked', data.all_customers);
      $('#useTags').prop('checked', data.use_tags);
      $('#useCustomers').prop('checked', data.use_customers);

      // تفعيل/تعطيل select حسب checkbox
      $('#tagsSelect').prop('disabled', !data.use_tags).val(data.tags).trigger('change');
      $('#customersSelect').prop('disabled', !data.use_customers).val(data.customers).trigger('change');

      fetchPoints(data.customers);
      // تفعيل الميثودات
      // تفريغ وتفعيل الـ methods
      $('.toggle-method').prop('checked', false); // reset

      if (Array.isArray(data.methods)) {
        data.methods.forEach(methodId => {
          // تفعيل الـ checkbox
          const $checkbox = $(`#method_${methodId}`);
          $checkbox.prop('checked', true);
          const status = data.method_status.find(item => item.method_id === methodId);

          // توليد الحاوية يدويًا لو مش موجودة
          if (!$(`#params_${methodId}`).length) {
            const block = `
        <div class="method-parameters mb-3 p-3 border rounded" id="params_${methodId}">
          <label><strong>Set Parameters for Method #${methodId}</strong></label>
          <label class="switch switch-success">
              <input type="checkbox" class="switch-input edit_status" data-id=${status.id} ${status.status == 1 ? 'checked' : ''} />
              <span class="switch-toggle-slider">
                <span class="switch-on">
                  <i class="ti ti-check"></i>
                </span>
                <span class="switch-off">
                  <i class="ti ti-x"></i>
                </span>
              </span>
            </label>
          <div class="parameter-rows" data-method="${methodId}" data-type="${status.type}"></div>
        </div>
      `;
            $checkbox.closest('.form-check').after(block);
          }

          // تحميل الـ params الخاصة بهذا method
          const container = $(`#params_${methodId} .parameter-rows`);
          container.empty();
          pricingParamsIndex = 0;
          pricingPoints = 0;

          if (Array.isArray(data.params)) {
            const flatParams = data.params.reduce((acc, val) => acc.concat(val), []);
            const params = flatParams.filter(p => String(p.method_id) === String(methodId));

            if (params.length) {
              params.forEach((param, index) => {
                const actionButton =
                  index === 0
                    ? `<button type="button" class="btn btn-sm btn-icon border add-row"><i class="ti ti-plus"></i></button>`
                    : `<button type="button" class="btn btn-sm btn-icon text-danger remove-row"><i class="ti ti-trash"></i></button>`;
                let fields = '';
                if (status.type === 'distance') {
                  fields = `
                    <div class="col-md-3">
                      <input type="number" name="params[${methodId}][${pricingParamsIndex}][from_val]"  class="form-control from-input" value="${param.from_val}" placeholder="From">
                      <span class="params-${methodId}-${pricingParamsIndex}-from_val-error text-danger text-error"></span>

                    </div>
                    <div class="col-md-3">
                      <input type="number" name="params[${methodId}][${pricingParamsIndex}][to_val]" class="form-control to-input"  value="${param.to_val}" placeholder="To">
                      <span class="params-${methodId}-${pricingParamsIndex}-to_val-error text-danger text-error"></span>

                    </div>
                  `;
                  pricingParamsIndex++;
                } else if (status.type === 'points') {
                  console.log(groupedOptionsHTML);
                  fields = `
                    <div class="col-md-3">
                      <select name="params[${methodId}][${pricingPoints}][from_val]" class="form-select point-select from-input">
                        <option value="">From Point</option>${groupedOptionsHTML}
                      </select>
                      <span class="params-${methodId}-${pricingPoints}-from_val-error text-danger text-error"></span>

                    </div>
                    <div class="col-md-3">
                      <select name="params[${methodId}][${pricingPoints}][to_val]" class="form-select point-select to-input">
                        <option value="">To Point</option>${groupedOptionsHTML}
                      </select>
                      <span class="params-${methodId}-${pricingPoints}-to_val-error text-danger text-error"></span>

                    </div>
                  `;
                  waitForSelect(`select[name="params[${methodId}][${pricingPoints}][from_val]"]`).then($select => {
                    if ($select.find(`option[value="${param.from_val}"]`).length === 0) {
                      $select.append(new Option(param.from_val, param.from_val));
                    }
                    $select.val(param.from_val).trigger('change');
                  });

                  waitForSelect(`select[name="params[${methodId}][${pricingPoints}][to_val]"]`).then($select => {
                    if ($select.find(`option[value="${param.to_val}"]`).length === 0) {
                      $select.append(new Option(param.to_val, param.to_val));
                    }
                    $select.val(param.to_val).trigger('change');
                  });

                  console.log(param.from_val);
                  console.log(param.to_val);
                  pricingPoints++;
                }

                const row = `
                  <div class="row g-2 parameter-row mt-2">
                    <input type="hidden" name="params[${methodId}][${index}][method_id]" value="${methodId}">
                    ${fields}
                    <div class="col-md-3">
                      <input type="number" name="params[${methodId}][${index}][price]" step="any" class="form-control" placeholder="Price" value="${param.price}">
                      <span class="params-${methodId}-${index}-price-error text-danger text-error"></span>
                    </div>
                    <div class="col-md-3">
                      ${actionButton}
                    </div>
                  </div>
                `;

                container.append(row);
              });
            }
          }
        });
      }

      ///////////////
      $('#field-pricing-wrapper').html('');
      if (Array.isArray(data.field_pricing)) {
        data.field_pricing.forEach(item => {
          $('#field-pricing-wrapper').append(`
    <div class="row g-2 mb-2 field-pricing-row">
      <div class="col-md-3">
        <select name="field_pricing[${fieldPricingIndex}][field_id]" class="form-select field-select">
          ${generateFieldOptions(item.field_id)}
        </select>
        <span class="field_pricing-${fieldPricingIndex}-field_id-error text-danger text-error"></span>

      </div>
      <div class="col-md-2">
        <select name="field_pricing[${fieldPricingIndex}][option]" class="form-select">
          <option value="equal" ${item.option == 'equal' ? 'selected' : ''}>Equal</option>
          <option value="not_equal" ${item.option == 'not_equal' ? 'selected' : ''}>Not Equal</option>
          <option value="greater" ${item.option == 'greater' ? 'selected' : ''}>Greater Than</option>
          <option value="less" ${item.option == 'less' ? 'selected' : ''}>Less Than</option>
          <option value="greater_equal" ${item.option == 'greater_equal' ? 'selected' : ''}>Greater or Equal</option>
          <option value="less_equal" ${item.option == 'less_equal' ? 'selected' : ''}>Less or Equal</option>
        </select>
        <span class="field_pricing-${fieldPricingIndex}-option-error text-danger text-error"></span>

      </div>
      <div class="col-md-2">
        <input type="text" name="field_pricing[${fieldPricingIndex}][value]" value=${item.value} class="form-control" placeholder="Value">
        <span class="field_pricing-${fieldPricingIndex}-value-error text-danger text-error"></span>

      </div>
      <div class="col-md-2">
        <select name="field_pricing[${fieldPricingIndex}][type]" class="form-select">
          <option value="fixed" ${item.type == 'fixed' ? 'selected' : ''}>Fixed</option>
          <option value="percentage" ${item.type == 'percentage' ? 'selected' : ''}>Percentage</option>
        </select>
        <span class="field_pricing-${fieldPricingIndex}-type-error text-danger text-error"></span>

      </div>
      <div class="col-md-2">
        <input type="number" step="0.01" value=${item.amount} name="field_pricing[${fieldPricingIndex}][amount]" class="form-control">
        <span class="field_pricing-${fieldPricingIndex}-amount-error text-danger text-error"></span>

      </div>
      <div class="col-md-1 d-flex align-items-end">
        <button type="button" class="btn btn-sm btn-icon text-danger  remove-field-pricing"><i class="ti ti-trash"></i></button>
      </div>
    </div>
  `);
          fieldPricingIndex++;
        });
      }

      // ✅ تعبئة الجيوفينس
      $('#geofence-pricing-wrapper').html('');
      if (Array.isArray(data.geofence_pricing)) {
        data.geofence_pricing.forEach(item => {
          $('#geofence-pricing-wrapper').append(`
      <div class="row g-2 mb-2 geofence-pricing-row">
        <div class="col-md-4">
          <select name="geofence_pricing[${geofencePricingIndex}][geofence_id]" class="form-select geofence-select">
            ${renderGeofenceOptions(item.geofence)}
          </select>
        <span class="geofence_pricing-${geofencePricingIndex}-geofence_id-error text-danger text-error"></span>

        </div>
        <div class="col-md-3">
          <select name="geofence_pricing[${geofencePricingIndex}][type]" class="form-select">
            <option value="fixed" ${item.type == 'fixed' ? 'selected' : ''}>Fixed</option>
            <option value="percentage" ${item.type == 'percentage' ? 'selected' : ''}>Percentage</option>
          </select>
        <span class="geofence_pricing-${geofencePricingIndex}-type-error text-danger text-error"></span>

        </div>
        <div class="col-md-3">
          <input type="number" step="0.01" value=${item.amount} name="geofence_pricing[${geofencePricingIndex}][amount]" class="form-control">
        <span class="geofence_pricing-${geofencePricingIndex}-amount-error text-danger text-error"></span>

        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="button" class="btn btn-sm btn-icon text-danger remove-geofence-pricing"><i class="ti ti-trash"></i></button>
        </div>
      </div>
    `);
          geofencePricingIndex++;
          updateGeofenceButtons();
        });
      }

      // ✅ تفعيل checkbox الأحجام (sizes)
      $('.size-checkbox').prop('checked', false);
      if (Array.isArray(data.sizes)) {
        data.sizes.forEach(id => {
          $(`#size_${id}`).prop('checked', true);
        });
      }

      // تغيير عنوان المودال
      $('#modelTitle').html(`Edit Template: <span class="bg-info text-white px-2 rounded">${data.rule_name}</span>`);
    });
  });

  /* ====================== Change Status Action Button  =============================== */
  $(document).on('change', '.edit_status', function () {
    var Id = $(this).data('id');
    console.log(Id);
    $.ajax({
      url: `${baseUrl}admin/settings/templates/pricing/status/${Id}`,
      type: 'post',

      success: function (response) {
        if (response.status != 1) {
          showAlert('error', response.error, 10000, true);
        }
      },
      error: function () {
        showAlert('Error!', 'Failed Request', 'error');
      }
    });
  });
  /* ====================== Delete Action Button  =============================== */
  $(document).on('click', '.delete-record', function () {
    let url = baseUrl + 'admin/settings/templates/pricing/delete/' + $(this).data('id');
    deleteRecord($(this).data('name'), url);
  });
  /* ====================== Close Modal Event Actions  =============================== */
  $('#submitModal').on('hidden.bs.modal', function () {
    $('.form_submit').trigger('reset');
    $('.text-error').html('');
    $('#pricing_id').val('');
    $('#field-pricing-wrapper').html('');
    $('.method-parameters').remove();
    $('#geofence-pricing-wrapper').html('');
    handleSelection('all');
    tagsSelect.val('').trigger('change');
    customersSelect.val('').trigger('change');
    $('#modelTitle').html('Add New Pricing Role');
  });

  /* ====================== Pricing Role Owner Selector  =============================== */
  const allCheckbox = $('#allCustomers');
  const tagsCheckbox = $('#useTags');
  const specificCheckbox = $('#useCustomers');

  const tagsSelect = $('#tagsSelect');
  const customersSelect = $('#customersSelect');

  function handleSelection(selected) {
    if (selected === 'all') {
      allCheckbox.prop('checked', true);
      tagsCheckbox.prop('checked', false);
      specificCheckbox.prop('checked', false);
      tagsSelect.prop('disabled', true);
      customersSelect.prop('disabled', true);
    } else if (selected === 'tags') {
      allCheckbox.prop('checked', false);
      tagsCheckbox.prop('checked', true);
      specificCheckbox.prop('checked', false);
      tagsSelect.prop('disabled', false);
      customersSelect.prop('disabled', true);
    } else if (selected === 'customers') {
      allCheckbox.prop('checked', false);
      tagsCheckbox.prop('checked', false);
      specificCheckbox.prop('checked', true);
      tagsSelect.prop('disabled', true);
      customersSelect.prop('disabled', false);
    }
  }

  $(document).on('change', '#allCustomers', function () {
    handleSelection('all');
  });

  $(document).on('change', '#useTags', function () {
    handleSelection('tags');
  });

  $(document).on('change', '#useCustomers', function () {
    handleSelection('customers');
  });

  /* ====================== Pricing Methods Selection  =============================== */
  const methodParametersContainer = {};
  $(document).on('change', '.toggle-method', function () {
    const methodId = $(this).data('method-id');
    const isChecked = $(this).is(':checked');
    const target = `#params_${methodId}`;
    if (isChecked) {
      // If the method is selected, create the fields and place them under the button.
      if (!methodParametersContainer[methodId]) {
        const methodType = $(this).data('method-type'); // type: distance OR points

        let fields = renderMethodParameters(methodType, methodId);
        methodParametersContainer[methodId] = `
          <div class="method-parameters mb-3 p-3 border rounded" id="params_${methodId}">
            <label><strong>Set Parameters for Method #${methodId}</strong></label>
            <div class="parameter-rows" data-method="${methodId}" data-type="${methodType}">
              <div class="row g-2 parameter-row">
                <input type="hidden" name="params[${methodId}][0][method_id]" value="${methodId}">
                ${fields}
                <div class="col-md-3">
                  <input type="number" name="params[${methodId}][0][price]" class="form-control" placeholder="Price" value="0.00">
                </div>
                <div class="col-md-3">
                  <button type="button" class="btn btn-sm btn-icon border add-row"><i class="ti ti-plus"></i></button>
                </div>
              </div>
            </div>
          </div>
        `;
      }

      // Add it after the selected checkbox.
      $(this).closest('.form-check').after(methodParametersContainer[methodId]);
    } else {
      // If deselected, delete elements from the DOM.
      $(`#params_${methodId}`).remove();
    }
  });

  function renderMethodParameters(type, methodId) {
    let fields = '';

    if (type === 'distance') {
      fields = `
        <div class="col-md-3">
          <input type="number" name="params[${methodId}][${pricingParamsIndex}][from_val]" class="form-control from-input" placeholder="From">
            <span class="params-${methodId}-${pricingParamsIndex}-from_val-error text-danger text-error"></span>

        </div>
        <div class="col-md-3">
          <input type="number" name="params[${methodId}][${pricingParamsIndex}][to_val]" class="form-control to-input" placeholder="To">
            <span class="params-${methodId}-${pricingParamsIndex}-to_val-error text-danger text-error"></span>

        </div>
      `;
      pricingParamsIndex++;
    } else if (type === 'points') {
      fields = `
        <div class="col-md-3">
          <select name="params[${methodId}][${pricingPoints}][from_val]" class="form-select select-point from-input">
            <option value="">From Point</option>${groupedOptionsHTML}
          </select>
          <span class="params-${methodId}-${pricingPoints}-from_val-error text-danger text-error"></span>

        </div>
        <div class="col-md-3">
          <select name="params[${methodId}][${pricingPoints}][to_val]" class="form-select select-point to-input">
            <option value="">To Point</option>${groupedOptionsHTML}
          </select>
          <span class="params-${methodId}-${pricingPoints}-to_val-error text-danger text-error"></span>

        </div>
      `;
      pricingPoints++;
    }

    return fields;
  }

  // configure selected methods
  $('.toggle-method').on('change', function () {
    const methodId = $(this).data('method-id');
    const paramContainer = $('#params_' + methodId);
    if ($(this).is(':checked')) {
      paramContainer.removeClass('d-none');
    } else {
      paramContainer.addClass('d-none');
    }
  });

  // Check fields to prevent duplication and anomalies.
  $(document).on('change', '.from-input, .to-input', function () {
    const row = $(this).closest('.parameter-row');
    const fromInput = row.find('.from-input');
    const toInput = row.find('.to-input');
    const fromVal = fromInput.val();
    const toVal = toInput.val();

    if (!fromVal || !toVal) return;

    // نفس القيم داخل نفس الصف
    if (fromVal === toVal) {
      showAlert('warning', 'لا يمكن اختيار نفس النقطة في من وإلى.', 3000, true);
      $(this).val('');
      return;
    }

    // تكرار أو انعكاس في صفوف أخرى
    const allRows = $('.parameter-row');
    const currentIndex = allRows.index(row);
    let isDuplicate = false;

    allRows.each(function (index) {
      if (index === currentIndex) return;
      const otherFrom = $(this).find('.from-input').val();
      const otherTo = $(this).find('.to-input').val();

      const isExactMatch = fromVal === otherFrom && toVal === otherTo;
      const isReversedMatch =
        fromInput.is('select') && toInput.is('select') && fromVal === otherTo && toVal === otherFrom;

      if (isExactMatch || isReversedMatch) {
        isDuplicate = true;
        return false;
      }
    });

    if (isDuplicate) {
      showAlert('warning', 'هذه النقطة أو عكسها مستخدمة مسبقًا في صف آخر.', 3000, true);
      $(this).val('');
      return;
    }

    // تحقق رقمي (من < إلى)
    const isNumeric = fromInput.is('[type="number"]') && toInput.is('[type="number"]');
    if (isNumeric && parseFloat(fromVal) >= parseFloat(toVal)) {
      showAlert('warning', 'في الحقول الرقمية يجب أن تكون من أقل من إلى.', 3000, true);
      $(this).val('');
      return;
    }
  });

  // Add params action Button
  $(document).on('click', '.add-row', function () {
    const wrapper = $(this).closest('.parameter-rows');
    const methodId = wrapper.data('method');
    const type = wrapper.data('type');
    const index = wrapper.find('.parameter-row').length;

    let fields = renderMethodParameters(type, methodId);

    const row = `
        <div class="row g-2 parameter-row mt-2">
          <input type="hidden" name="params[${methodId}][${index}][method_id]" value="${methodId}">
          ${fields}
          <div class="col-md-3">
            <input type="number" name="params[${methodId}][${index}][price]" value="0.00" class="form-control" placeholder="Price">
            <span class="params-${methodId}-${index}-price-error text-danger text-error"></span>
          </div>
          <div class="col-md-3">
            <button type="button" class="btn btn-sm btn-icon  text-danger remove-row"><i class="ti ti-trash"></i></button>
          </div>
        </div>
      `;

    wrapper.append(row);
  });

  // Remove Param Action Button
  $(document).on('click', '.remove-row', function () {
    $(this).closest('.parameter-row').remove();
  });

  /* ====================== Configure the Pricing Fields Selector  =============================== */
  function generateFieldOptions(selected = null) {
    const usedFieldIds = [];
    return formFields
      .filter(f => !usedFieldIds.includes(String(f.id)))
      .map(f => `<option value="${f.id}" ${f.id == selected ? 'selected' : ''}>${f.label}</option>`)
      .join('');
  }

  // Add Pricing Field Action Button
  $(document).on('click', '.add-field-pricing', function () {
    const options = generateFieldOptions();
    const row = `
    <div class="row g-2 mb-2 field-pricing-row">
      <div class="col-md-3">
        <select name="field_pricing[${fieldPricingIndex}][field_id]" class="form-select field-select">
          ${options}
        </select>
        <span class="field_pricing-${fieldPricingIndex}-field_id-error text-danger text-error"></span>

      </div>
      <div class="col-md-2">
        <select name="field_pricing[${fieldPricingIndex}][option]" class="form-select">
          <option value="equal">Equal</option>
          <option value="not_equal">Not Equal</option>
          <option value="greater">Greater Than</option>
          <option value="less">Less Than</option>
          <option value="greater_equal">Greater or Equal</option>
          <option value="less_equal">Less or Equal</option>
        </select>
        <span class="field_pricing-${fieldPricingIndex}-option-error text-danger text-error"></span>

      </div>
      <div class="col-md-2">
        <input type="text" name="field_pricing[${fieldPricingIndex}][value]" class="form-control" placeholder="Value">
        <span class="field_pricing-${fieldPricingIndex}-value-error text-danger text-error"></span>

      </div>
      <div class="col-md-2">
        <select name="field_pricing[${fieldPricingIndex}][type]" class="form-select">
          <option value="fixed">Fixed</option>
          <option value="percentage">Percentage</option>
        </select>
        <span class="field_pricing-${fieldPricingIndex}-type-error text-danger text-error"></span>

      </div>
      <div class="col-md-2">
        <input type="number" step="0.01" value="0.00" name="field_pricing[${fieldPricingIndex}][amount]" class="form-control">
        <span class="field_pricing-${fieldPricingIndex}-amount-error text-danger text-error"></span>

      </div>
      <div class="col-md-1 d-flex align-items-end">
        <button type="button" class="btn btn-sm btn-icon text-danger  remove-field-pricing"><i class="ti ti-trash"></i></button>
      </div>
    </div>
  `;
    $('#field-pricing-wrapper').append(row);
    fieldPricingIndex++;
    $('.field-select').trigger('change'); // تحديث الخيارات
  });

  // Update options once you change any field.
  $(document).on('change', '.field-select', function () {
    $('.field-select').each(function () {
      const selected = $(this).val();
      const options = generateFieldOptions(selected);
      $(this).html(options).val(selected);
    });
  });

  // Delete a row and re-refresh
  $(document).on('click', '.remove-field-pricing', function () {
    $(this).closest('.field-pricing-row').remove();
    $('.field-select').trigger('change');
  });

  /* ====================== Configure the Pricing GeoFences Selector  =============================== */
  function getUsedGeofences() {
    let used = [];
    $('#geofence-pricing-wrapper select[name^="geofence_pricing"]').each(function () {
      used.push($(this).val());
    });
    return used;
  }

  function renderGeofenceOptions(selected = null) {
    const used = getUsedGeofences();
    return geoFences
      .filter(f => !used.includes(String(f.id)))
      .map(f => `<option value="${f.id}" ${f.id == selected ? 'selected' : ''}>${f.name}</option>`)
      .join('');
  }

  function updateGeofenceButtons() {
    const available = geoFences.filter(f => !getUsedGeofences().includes(String(f.id)));
    if (available.length === 0) {
      $('.add-geofence-pricing').prop('disabled', true);

      showAlert('warning', 'No more Geo-Fences available to select', 10000, true);
    } else {
      $('.add-geofence-pricing').prop('disabled', false);
      $('#geofence-limit-alert').remove();
    }
  }

  // Add Pricing GeoFence Action Button
  $(document).on('click', '.add-geofence-pricing', function () {
    const options = renderGeofenceOptions();
    if (!options) return;

    const row = `
      <div class="row g-2 mb-2 geofence-pricing-row">
        <div class="col-md-4">
          <select name="geofence_pricing[${geofencePricingIndex}][geofence_id]" class="form-select geofence-select">
            ${options}
          </select>
        <span class="geofence_pricing-${geofencePricingIndex}-geofence_id-error text-danger text-error"></span>

        </div>
        <div class="col-md-3">
          <select name="geofence_pricing[${geofencePricingIndex}][type]" class="form-select">
            <option value="fixed">Fixed</option>
            <option value="percentage">Percentage</option>
          </select>
        <span class="geofence_pricing-${geofencePricingIndex}-type-error text-danger text-error"></span>

        </div>
        <div class="col-md-3">
          <input type="number" step="0.01" value="00" name="geofence_pricing[${geofencePricingIndex}][amount]" class="form-control">
        <span class="geofence_pricing-${geofencePricingIndex}-amount-error text-danger text-error"></span>

        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="button" class="btn btn-sm btn-icon text-danger remove-geofence-pricing"><i class="ti ti-trash"></i></button>
        </div>
      </div>
    `;

    $('#geofence-pricing-wrapper').append(row);
    geofencePricingIndex++;
    updateGeofenceButtons();
  });

  // Remove Pricing GeoFence Action Button
  $(document).on('click', '.remove-geofence-pricing', function () {
    $(this).closest('.geofence-pricing-row').remove();
    updateGeofenceButtons();
  });

  // $(document).ready(function () {
  //   updateGeofenceButtons();
  // });
});
