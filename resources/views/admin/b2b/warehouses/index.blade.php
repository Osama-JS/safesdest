@extends('layouts/layoutMaster')

@section('title', __('Warehouses Management'))

@section('vendor-style')
  <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
  <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css"
    rel="stylesheet" />
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss'])
@endsection

@section('vendor-script')
  <script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
  <script src="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.min.js"></script>
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
  <script>
    const baseUrl = "{{ url('/') }}/";
  </script>
  @vite(['resources/js/admin/b2b/warehouses.js'])
@endsection

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="py-3 mb-0">
      <span class="avatar-initial rounded bg-primary text-white ps-2 pb-2 me-2">
        <i class="ti ti-building-skyscraper ti-26px"></i>
      </span>
      <span class="text-muted fw-light">{{ __('B2B Module') }} /</span> {{ __('Warehouses Management') }}
    </h4>
  </div>

  <!-- Statistics Cards -->
  <div class="row g-4 mb-4 animate__animated animate__fadeIn">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">{{ __('Total Warehouses') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ $stats['total_warehouses'] }}</h4>
              </div>
              <small class="text-muted">{{ __('Registered locations') }}</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="ti ti-home-shipping ti-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">{{ __('Active Locations') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">{{ $stats['active_warehouses'] }}</h4>
              </div>
              <small class="text-muted">{{ __('Operational and available') }}</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="ti ti-circle-check ti-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">{{ __('Inactive/Pending') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-danger">{{ $stats['inactive_warehouses'] }}</h4>
              </div>
              <small class="text-muted">{{ __('Currently offline') }}</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-danger">
                <i class="ti ti-circle-x ti-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Card -->
  <div class="card mb-4 border-primary">
    <div class="card-body">
      <div class="row align-items-end g-3">
        <div class="col-md-6 col-lg-3">
          <label class="form-label fw-bold text-primary"><i
              class="ti ti-filter me-1"></i>{{ __('Select B2B Company') }}</label>
          <select id="filter-company" class="form-select select2">
            <option value="">{{ __('--- Choose Company ---') }}</option>
            @foreach($companies as $company)
              <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>
                {{ $company->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6 col-lg-3">
            <label class="form-label fw-bold"><i class="ti ti-search me-1"></i>{{ __('Quick Search') }}</label>
            <input type="text" id="filter-search" class="form-control" placeholder="{{ __('Name, Address...') }}">
        </div>
        <div class="col-md-6 col-lg-2">
            <label class="form-label fw-bold"><i class="ti ti-map-pin me-1"></i>{{ __('Province') }}</label>
            <select id="filter-province" class="form-select select2">
                <option value="">{{ __('All') }}</option>
                @foreach($provinces as $province)
                    <option value="{{ $province->id }}">{{ $province->name_ar }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 col-lg-2">
            <label class="form-label fw-bold"><i class="ti ti-check me-1"></i>{{ __('Status') }}</label>
            <select id="filter-status" class="form-select select2">
                <option value="">{{ __('All') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
            </select>
        </div>
        <div class="col-md-6 col-lg-2">
          <div class="alert alert-label-primary mb-0 d-flex align-items-center" role="alert" style="padding: 0.5rem 1rem;">
            <span class="alert-icon me-2"><i class="ti ti-info-circle"></i></span>
            {{ __('Filter results') }}
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Warehouses Table -->
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('Warehouses List') }}</h5>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-warehouses table border-top">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>{{ __('Company') }}</th>
            <th>{{ __('Warehouse Name') }}</th>
            <th>{{ __('Province') }}</th>
            <th>{{ __('Address') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

  <!-- Add/Edit Warehouse Modal -->
  <div class="modal fade" id="warehouseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content p-3 p-md-4">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-body">
          <div class="text-center mb-4">
            <h3 class="mb-2">{{ __('Warehouse Configuration') }}</h3>
            <p class="text-muted">{{ __('Setup loading point details and route specific pricing.') }}</p>
          </div>
          <form id="warehouse-form" class="row g-3">
            @csrf
            <input type="hidden" name="id" id="warehouse-id">
            <div class="col-md-6">
              <label class="form-label">{{ __('Target Company') }}</label>
              <select name="company_id" id="warehouse-company" class="form-select select2" required>
                <option value="">{{ __('Select Company') }}</option>
                @foreach($companies as $company)
                  <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
              </select>
              <span class="company_id-error text-danger small text-error"></span>
            </div>
            <div class="col-md-6">
              <label class="form-label">{{ __('Warehouse/Loading Point Name') }}</label>
              <input type="text" name="name" id="warehouse-name" class="form-control"
                placeholder="{{ __('e.g. Main Riyadh Warehouse') }}" required>
              <span class="name-error text-danger small text-error"></span>
            </div>
            <div class="col-md-6">
              <label class="form-label">{{ __('Province') }}</label>
              <select name="province_id" id="warehouse-province" class="form-select select2" required>
                <option value="">{{ __('Select Province') }}</option>
                @foreach($provinces as $province)
                  <option value="{{ $province->id }}">{{ $province->name_ar }}</option>
                @endforeach
              </select>
              <span class="province_id-error text-danger small text-error"></span>
            </div>
            <div class="col-md-6">
              <label class="form-label">{{ __('Full Address') }}</label>
              <input type="text" name="address" id="warehouse-address" class="form-control"
                placeholder="{{ __('Street, District, ZIP') }}" required>
              <span class="address-error text-danger small text-error"></span>
            </div>
            <div class="col-md-6">
              <label class="form-label">{{ __('Contact Personnel Name') }}</label>
              <input type="text" name="contact_name" id="warehouse-contact-name" class="form-control"
                placeholder="{{ __('e.g. Ahmed Ali') }}" required>
              <span class="contact_name-error text-danger small text-error"></span>
            </div>
            <div class="col-md-6">
              <label class="form-label">{{ __('Contact Phone Number') }}</label>
              <input type="text" name="contact_phone" id="warehouse-contact-phone" class="form-control"
                placeholder="05xxxxxxxx" required>
              <span class="contact_phone-error text-danger small text-error"></span>
            </div>
            <div class="col-12">
              <label class="form-label fw-bold">{{ __('Location Picker') }}</label>
              <div class="input-group mb-2 shadow-sm">
                <div class="form-control p-0" id="warehouse-geocoder"></div>
                <button type="button" title="{{ __('Extract from Link') }}" id="warehouse-toggle-link-input"
                  class="input-group-text bg-white border-start-0">
                  <i class="ti ti-link text-secondary"></i>
                </button>
                <button type="button" title="{{ __('Manual Input') }}" id="warehouse-manual-btn"
                  class="input-group-text bg-white">
                  <i class="ti ti-map-pin text-primary"></i>
                </button>
                <button type="button" title="{{ __('My Location') }}" id="warehouse-getCurrentLocation"
                  class="input-group-text bg-white">
                  <i class="ti ti-location text-success"></i>
                </button>
              </div>

              <div id="warehouse-link-input-wrapper" class="mb-3" style="display: none;">
                <div class="input-group">
                  <input type="text" id="warehouse-map-link" class="form-control form-control-sm"
                    placeholder="{{ __('Paste Google Maps link here...') }}">
                  <button type="button" id="warehouse-parse-link"
                    class="btn btn-secondary btn-sm">{{ __('Parse') }}</button>
                </div>
              </div>

              <div id="warehouse-map-container" class="position-relative rounded overflow-hidden border mb-3"
                style="height: 250px; display: none;">
                <div id="warehouse-map" class="w-100 h-100"></div>
                <div class="position-absolute top-0 start-0 m-2 z-3 d-flex gap-2">
                  <input type="number" step="any" id="warehouse-lat" name="latitude"
                    class="form-control form-control-sm bg-white shadow-sm" style="width: 120px;" placeholder="Lat">
                  <input type="number" step="any" id="warehouse-lng" name="longitude"
                    class="form-control form-control-sm bg-white shadow-sm" style="width: 120px;" placeholder="Lng">
                </div>
                <button type="button" id="confirm-warehouse-location"
                  class="btn btn-primary btn-sm position-absolute bottom-0 start-50 translate-middle-x mb-2 shadow">
                  {{ __('Confirm Location') }}
                </button>
              </div>
              <span class="latitude-error text-danger small text-error"></span>
              <span class="longitude-error text-danger small text-error"></span>
            </div>

            <div class="divider mt-5">
              <div class="divider-text fw-bold text-primary">{{ __('Custom Route Pricing (Layer 4)') }}</div>
            </div>
            <p class="small text-muted text-center"><i
                class="ti ti-bulb me-1"></i>{{ __('Define specific delivery rates from THIS warehouse to any province. This overrides global rules.') }}
            </p>

            <div class="row mt-2 g-2">
              @foreach($provinces as $province)
                <div class="col-md-4">
                  <div class="input-group input-group-merge input-group-sm">
                    <span class="input-group-text bg-light text-dark fw-medium"
                      style="min-width: 100px;">{{ $province->name_ar }}</span>
                    <input type="number" name="pricing[{{ $province->id }}]" class="form-control pricing-input"
                      data-province="{{ $province->id }}" placeholder="SAR">
                  </div>
                </div>
              @endforeach
            </div>

            <div class="col-12 text-center mt-4">
              <hr>
              <button type="submit" class="btn btn-primary me-sm-3 me-1 shadow-sm">{{ __('Save Warehouse') }}</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
