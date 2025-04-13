/**
 * Pricing Rules Management
 * Includes CRUD operations and dynamic form handling
 */
'use strict';
import { deleteRecord, showAlert, showFormModal } from '../ajax';

// Configuration and Initialization
const PricingManager = (() => {
  // DOM Elements
  const dtDataTable = $('.datatables-pricing');
  const formModal = $('#submitModal');
  const form = document.querySelector('.form_submit');

  // State Variables
  let fieldPricingIndex = 1;
  let geofencePricingIndex = 0;
  let dataTable;

  // Initialization
  const init = () => {
    setupAjax();
    initializeDataTable();
    setupEventHandlers();
    initializeSelect2();
    updateGeofenceButtons();
  };

  // Setup Functions
  const setupAjax = () => {
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });
  };

  const initializeSelect2 = () => {
    initSelect2('.select2-tags', 'Select Tags');
    initSelect2('.select2-customers', 'Select Customers');
  };

  const initSelect2 = (selector, placeholder) => {
    $(selector)
      .wrap('<div class="position-relative"></div>')
      .select2({
        allowClear: true,
        placeholder: placeholder,
        dropdownParent: $(selector).parent(),
        closeOnSelect: false
      });
  };

  // DataTable Initialization
  const initializeDataTable = () => {
    if (!dtDataTable.length) return;

    dataTable = dtDataTable.DataTable({
      processing: true,
      serverSide: true,
      ajax: `${baseUrl}admin/settings/templates/pricing/data/${templateId}`,
      columns: [
        { data: '', className: 'control' },
        { data: 'fake_id' },
        { data: 'name' },
        { data: 'created_at' },
        { data: null, title: 'Actions' }
      ],
      columnDefs: createColumnDefs(),
      order: [[2, 'desc']],
      dom: createDomLayout(),
      language: dataTableLanguage(),
      responsive: createResponsiveConfig()
    });
  };

  const createColumnDefs = () => [
    {
      searchable: false,
      orderable: false,
      targets: 0,
      render: () => ''
    },
    {
      targets: 1,
      render: (data, type, full) => `<span>${full.fake_id}</span>`
    },
    {
      targets: 2,
      responsivePriority: 4,
      render: (data, type, full) => full.name
    },
    {
      targets: 3,
      render: (data, type, full) => full.created_at
    },
    {
      targets: 4,
      searchable: false,
      orderable: false,
      render: (data, type, full) => createActionButtons(full)
    }
  ];

  const createActionButtons = record => `
    <div class="d-flex align-items-center gap-50">
      <button class="btn btn-sm btn-icon edit-record btn-text-secondary rounded-pill waves-effect"
        data-id="${record.id}" data-bs-toggle="modal" data-bs-target="#largeModal">
        <i class="ti ti-edit"></i>
      </button>
      <button class="btn btn-sm btn-icon delete-record btn-text-secondary rounded-pill waves-effect"
        data-id="${record.id}" data-name="${record.name}">
        <i class="ti ti-trash"></i>
      </button>
    </div>
  `;

  // Event Handlers
  const setupEventHandlers = () => {
    $(document)
      .on('click', '.edit-record', handleEdit)
      .on('click', '.delete-record', handleDelete)
      .on('click', '.add-row', addParameterRow)
      .on('click', '.remove-row', removeParameterRow)
      .on('click', '.add-field-pricing', addFieldPricing)
      .on('click', '.remove-field-pricing', removeFieldPricing)
      .on('click', '.add-geofence-pricing', addGeofencePricing)
      .on('click', '.remove-geofence-pricing', removeGeofencePricing)
      .on('change', '.toggle-method', handleMethodToggle)
      .on('change', '.edit_status', handleStatusChange)
      .on('change', '#allCustomers, #useTags, #useCustomers', handleCustomerSelection)
      .on('change', '.field-select', updateFieldOptions);

    formModal.on('hidden.bs.modal', resetForm);
    document.addEventListener('formSubmitted', refreshDataTable);
    document.addEventListener('deletedSuccess', refreshDataTable);
  };

  // Core Handlers
  const handleEdit = function () {
    const id = $(this).data('id');
    loadEditData(id);
  };

  const handleDelete = function () {
    const url = `${baseUrl}admin/settings/templates/pricing/delete/${$(this).data('id')}`;
    deleteRecord($(this).data('name'), url);
  };

  const handleMethodToggle = function () {
    const methodId = $(this).data('method-id');
    $(this).is(':checked') ? createMethodParameters(methodId) : $(`#params_${methodId}`).remove();
  };

  const handleStatusChange = function () {
    updateStatus($(this).data('id'), this.checked);
  };

  // Data Loading
  const loadEditData = id => {
    $.get(`${baseUrl}admin/settings/templates/pricing/edit/${id}`, data => {
      resetForm();
      populateFormData(data);
      updateFormTitle(data.rule_name);
    });
  };

  const populateFormData = data => {
    populateBasicFields(data);
    populateCheckboxes(data);
    populateMethods(data);
    populateFieldPricing(data);
    populateGeofencePricing(data);
  };

  // Helper Functions
  const populateBasicFields = data => {
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
  };

  const populateCheckboxes = data => {
    $('#allCustomers').prop('checked', data.all_customers);
    $('#useTags').prop('checked', data.use_tags);
    $('#useCustomers').prop('checked', data.use_customers);
    $('#tagsSelect').prop('disabled', !data.use_tags).val(data.tags).trigger('change');
    $('#customersSelect').prop('disabled', !data.use_customers).val(data.customers).trigger('change');
  };

  // Dynamic Form Elements
  const createMethodParameters = methodId => {
    const paramsHTML = `
      <div class="method-parameters mb-3 p-3 border rounded" id="params_${methodId}">
        <label><strong>Set Parameters for Method #${methodId}</strong></label>
        <div class="parameter-rows" data-method="${methodId}">
          ${createParameterRow(methodId, 0)}
        </div>
      </div>
    `;
    $(`#method_${methodId}`).closest('.form-check').after(paramsHTML);
  };

  const createParameterRow = (methodId, index) => `
    <div class="row g-2 parameter-row mt-2">
      <input type="hidden" name="params[${methodId}][${index}][method_id]" value="${methodId}">
      <div class="col-md-3">
        <input type="number" name="params[${methodId}][${index}][from_val]" class="form-control" placeholder="From">
      </div>
      <div class="col-md-3">
        <input type="number" name="params[${methodId}][${index}][to_val]" class="form-control" placeholder="To">
      </div>
      <div class="col-md-3">
        <input type="number" name="params[${methodId}][${index}][price]" value="0.00" class="form-control" placeholder="Price">
      </div>
      <div class="col-md-3">
        ${
          index === 0
            ? '<button type="button" class="btn btn-sm btn-icon border add-row"><i class="ti ti-plus"></i></button>'
            : '<button type="button" class="btn btn-sm btn-icon text-danger remove-row"><i class="ti ti-trash"></i></button>'
        }
      </div>
    </div>
  `;

  // Form Management
  const resetForm = () => {
    form.reset();
    $('.text-error').html('');
    $('#field-pricing-wrapper, #geofence-pricing-wrapper').html('');
    $('.method-parameters').remove();
    $('#modelTitle').html('Add New Pricing Role');
    handleCustomerSelection('all');
  };

  const refreshDataTable = () => dataTable?.draw();

  // Geofence Management
  const updateGeofenceButtons = () => {
    const available = geoFences.filter(f => !getUsedGeofences().includes(String(f.id)));
    $('.add-geofence-pricing').prop('disabled', available.length === 0);
  };

  const getUsedGeofences = () => {
    return $('#geofence-pricing-wrapper select[name^="geofence_pricing"]')
      .map((i, el) => $(el).val())
      .get();
  };

  return { init };
})();

// Initialization on Document Ready
$(document).ready(() => PricingManager.init());
