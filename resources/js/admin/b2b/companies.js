/**
 * B2B Companies Management
 */

'use strict';

$(function () {
  var dt_table = $('.datatables-companies');

  if (dt_table.length) {
    var dt = dt_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: baseUrl + 'admin/b2b/companies/data',
      columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'email' },
        { data: 'phone' },
        { data: 'warehouses_count' },
        { data: 'clients_count' },
        { data: 'status' },
        { data: null }
      ],
      columnDefs: [
        {
          targets: 1,
          render: function (data, type, full, meta) {
            return `<div class="d-flex justify-content-start align-items-center user-name">
                        <div class="avatar-wrapper">
                            <div class="avatar avatar-sm me-3">
                                <span class="avatar-initial rounded-circle bg-label-primary">${data.charAt(0)}</span>
                            </div>
                        </div>
                        <div class="d-flex flex-column">
                            <a href="${baseUrl}admin/customers/account/${full.id}/${full.name}" class="text-body text-truncate"><span class="fw-medium">${data}</span></a>
                        </div>
                    </div>`;
          }
        },
        {
          targets: -2,
          render: function (data, type, full, meta) {
            let badge = data === 'status_customers' || data === 'active' ? 'bg-label-success' : 'bg-label-danger';
            return `<span class="badge ${badge} text-capitalize">${data}</span>`;
          }
        },
        {
          targets: -1,
          title: 'Actions',
          orderable: false,
          render: function (data, type, full, meta) {
            return `
                <div class="d-flex align-items-center gap-1">
                    <a href="${baseUrl}admin/b2b/warehouses?company_id=${full.id}"
                       class="btn btn-sm btn-icon btn-label-primary" title="Warehouses">
                       <i class="ti ti-home-shipping"></i>
                    </a>
                    <a href="${baseUrl}admin/b2b/end-clients?company_id=${full.id}"
                       class="btn btn-sm btn-icon btn-label-info" title="End Clients">
                       <i class="ti ti-users"></i>
                    </a>
                    <a href="${baseUrl}admin/b2b/pricing/${full.id}"
                       class="btn btn-sm btn-icon btn-label-success" title="Pricing Matrix">
                       <i class="ti ti-table"></i>
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-icon btn-label-secondary dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                            <i class="ti ti-dots-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="${baseUrl}admin/b2b/config/${full.id}">
                                <i class="ti ti-settings me-1"></i> Config
                            </a>
                            <a class="dropdown-item" href="${baseUrl}admin/customers/account/${full.id}/${full.name}">
                                <i class="ti ti-eye me-1"></i> Profile
                            </a>
                        </div>
                    </div>
                </div>
            `;
          }
        }
      ],
      dom: '<"row me-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      buttons: [
        {
          text: '<i class="ti ti-plus me-0 me-sm-1"></i><span class="d-none d-sm-inline-block">New Company</span>',
          className: 'add-new btn btn-primary ms-3',
          action: function () {
            window.location.href = baseUrl + 'admin/customers';
          }
        }
      ],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search Company...'
      }
    });
  }
});
