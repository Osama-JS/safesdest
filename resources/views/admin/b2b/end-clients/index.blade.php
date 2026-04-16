@extends('layouts/layoutMaster')

@section('title', __('End Clients Management'))

@section('vendor-style')
    <link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" />
    <link href="https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v4.7.2/mapbox-gl-geocoder.css" rel="stylesheet" />
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
        const vehicleSizes = @json($vehicleSizes);
    </script>
    @vite(['resources/js/admin/b2b/end-clients.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="py-3 mb-0">
          <span class="avatar-initial rounded bg-primary text-white ps-2 pb-2 me-2">
        <i class="ti ti-building-skyscraper ti-26px"></i>
      </span>
            <span class="text-muted fw-light">{{ __('B2B Module') }} /</span> {{ __('End Clients Management') }}
        </h4>
    </div>

    <!-- Statistics Cards -->
    @if($stats)
    <div class="row g-4 mb-4 animate__animated animate__fadeIn">
        <div class="col-sm-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Total End Clients') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['total_clients'] }}</h4>
                            </div>
                            <small class="text-muted">{{ __('Registered delivery points') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-users ti-26px"></i>
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
                            <span class="text-heading">{{ __('Active Clients') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-success">{{ $stats['active_clients'] }}</h4>
                            </div>
                            <small class="text-muted">{{ __('Verified and available') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user-check ti-26px"></i>
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
                            <span class="text-heading">{{ __('Inactive/Drafts') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 text-danger">{{ $stats['inactive_clients'] }}</h4>
                            </div>
                            <small class="text-muted">{{ __('Currently restricted') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-x ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Filter Card -->
    <div class="card mb-4 border-info">
        <div class="card-body">
            <div class="row align-items-end g-3">
                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-bold text-info"><i class="ti ti-building me-1"></i>{{ __('Select B2B Company') }}</label>
                    <select id="filter-company" class="form-select select2">
                        <option value="">{{ __('--- Choose Company ---') }}</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ request('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-bold"><i class="ti ti-search me-1"></i>{{ __('Quick Search') }}</label>
                    <input type="text" id="filter-search" class="form-control" placeholder="{{ __('Name, ID, or Phone...') }}">
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
                    <label class="form-label fw-bold"><i class="ti ti-user-check me-1"></i>{{ __('Status') }}</label>
                    <select id="filter-status" class="form-select select2">
                        <option value="">{{ __('All') }}</option>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-md-6 col-lg-2">
                    <div class="alert alert-label-info mb-0 d-flex align-items-center" role="alert" style="padding: 0.5rem 1rem;">
                        <i class="ti ti-info-circle me-2"></i>
                        {{ __('Filter results') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Clients Table -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">{{ __('End Clients Directory') }}</h5>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-clients table border-top">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>{{ __('Company') }}</th>
                        <th>{{ __('Client Name') }}</th>
                        <th>{{ __('Special ID') }}</th>
                        <th>{{ __('Province') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="clientModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content p-3 p-md-4">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h3 class="mb-2">{{ __('End Client Profile') }}</h3>
                        <p class="text-muted">{{ __('Manage destination details and specific pricing overrides.') }}</p>
                    </div>
                    <form id="client-form" class="row g-3">
                        @csrf
                        <input type="hidden" name="id" id="client-id">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Parent B2B Company') }}</label>
                            <select name="company_id" id="client-company" class="form-select select2" required>
                                <option value="">{{ __('Select Company') }}</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                            <span class="company_id-error text-danger small text-error"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Client/Recipient Name') }}</label>
                            <input type="text" name="name" id="client-name" class="form-control" placeholder="{{ __('e.g. Panda Retail - Branch 01') }}" required>
                            <span class="name-error text-danger small text-error"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Special Client Number') }}</label>
                            <input type="text" name="client_code" id="client-code" class="form-control" placeholder="{{ __('Internal Company ID (Optional)') }}">
                            <span class="client_code-error text-danger small text-error"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Province/Region') }}</label>
                            <select name="province_id" id="client-province" class="form-select select2" required>
                                <option value="">{{ __('Select Province') }}</option>
                                @foreach($provinces as $province)
                                    <option value="{{ $province->id }}">{{ $province->name_ar }}</option>
                                @endforeach
                            </select>
                            <span class="province_id-error text-danger small text-error"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Primary Contact') }}</label>
                            <input type="text" name="phone" id="client-phone" class="form-control" placeholder="05xxxxxxxx" required>
                            <span class="phone-error text-danger small text-error"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Secondary Phone') }}</label>
                            <input type="text" name="phone_2" id="client-phone2" class="form-control">
                            <span class="phone_2-error text-danger small text-error"></span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Exact Address') }}</label>
                            <input type="text" name="address" id="client-address" class="form-control">
                            <span class="address-error text-danger small text-error"></span>
                        </div>
                        <div class="col-12 mt-4">
                            <label class="form-label fw-bold">{{ __('Destination Location Picker') }}</label>
                            <div class="input-group mb-2 shadow-sm">
                                <div class="form-control p-0" id="client-geocoder"></div>
                                <button type="button" title="{{ __('Extract from Link') }}" id="client-toggle-link-input" class="input-group-text bg-white border-start-0">
                                    <i class="ti ti-link text-secondary"></i>
                                </button>
                                <button type="button" title="{{ __('Manual Input') }}" id="client-manual-btn" class="input-group-text bg-white">
                                    <i class="ti ti-map-pin text-primary"></i>
                                </button>
                                <button type="button" title="{{ __('My Location') }}" id="client-getCurrentLocation" class="input-group-text bg-white">
                                    <i class="ti ti-location text-success"></i>
                                </button>
                            </div>

                            <div id="client-link-input-wrapper" class="mb-3" style="display: none;">
                                <div class="input-group">
                                    <input type="text" id="client-map-link" class="form-control form-control-sm" placeholder="{{ __('Paste Google Maps link here...') }}">
                                    <button type="button" id="client-parse-link" class="btn btn-secondary btn-sm">{{ __('Parse') }}</button>
                                </div>
                            </div>

                            <div id="client-map-container" class="position-relative rounded overflow-hidden border mb-2" style="height: 250px; display: none;">
                                <div id="client-map" class="w-100 h-100"></div>
                                <div class="position-absolute top-0 start-0 m-2 z-3 d-flex gap-2">
                                    <input type="number" step="any" id="client-lat" name="latitude" class="form-control form-control-sm bg-white shadow-sm" style="width: 120px;" placeholder="Lat">
                                    <input type="number" step="any" id="client-lng" name="longitude" class="form-control form-control-sm bg-white shadow-sm" style="width: 120px;" placeholder="Lng">
                                </div>
                                <button type="button" id="confirm-client-location" class="btn btn-primary btn-sm position-absolute bottom-0 start-50 translate-middle-x mb-2 shadow">
                                    {{ __('Confirm Location') }}
                                </button>
                            </div>
                            <span class="latitude-error text-danger small text-error"></span>
                            <span class="longitude-error text-danger small text-error"></span>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Internal Notes') }}</label>
                            <textarea name="notes" id="client-notes" class="form-control" rows="2"></textarea>
                            <span class="notes-error text-danger small text-error"></span>
                        </div>

                        <div class="divider mt-4">
                            <div class="divider-text fw-bold text-primary">{{ __('Custom Destination Pricing (Layer 1)') }}</div>
                        </div>
                        <p class="small text-muted text-center"><i class="ti ti-money me-1"></i>{{ __('Define specific rates for this CLIENT when shipping from specific warehouses. This is the highest priority pricing layer.') }}</p>

                        <div id="pricing-rows" class="accordion mt-2">
                            <!-- Populated via JS Load -->
                        </div>

                        <div class="col-12 text-center mt-4">
                            <hr>
                            <button type="submit" class="btn btn-primary me-sm-3 me-1 px-4 shadow-sm">{{ __('Submit & Save') }}</button>
                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Excel Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title"><i class="ti ti-file-spreadsheet me-2 text-success ti-md"></i>{{ __('Bulk Import Clients') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pb-0">
                    <div class="bg-label-info p-3 rounded mb-4">
                        <p class="mb-2 fw-bold"><i class="ti ti-table me-1 text-primary"></i>{{ __('Expected Excel Columns') }}:</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered small mb-2">
                                <thead class="table-primary">
                                    <tr>
                                        <th>{{ __('Column Header') }}</th>
                                        <th>{{ __('Description') }}</th>
                                        <th>{{ __('Required?') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>ID</code></td><td>{{ __('Special Client Number (Internal ID)') }}</td><td><span class="badge bg-label-warning">{{ __('Optional') }}</span></td></tr>
                                    <tr><td><code>CUSTOMER_NAME</code></td><td>{{ __('Client / Recipient Name') }}</td><td><span class="badge bg-label-danger">{{ __('Required') }}</span></td></tr>
                                    <tr><td><code>CITY</code></td><td>{{ __('City (created automatically if not found)') }}</td><td><span class="badge bg-label-warning">{{ __('Optional') }}</span></td></tr>
                                    <tr><td><code>LATITUDE</code></td><td>{{ __('GPS Latitude') }}</td><td><span class="badge bg-label-warning">{{ __('Optional') }}</span></td></tr>
                                    <tr><td><code>LONGITUDE</code></td><td>{{ __('GPS Longitude') }}</td><td><span class="badge bg-label-warning">{{ __('Optional') }}</span></td></tr>
                                    <tr><td><code>NOTES</code></td><td>{{ __('Any extra notes') }}</td><td><span class="badge bg-label-warning">{{ __('Optional') }}</span></td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted mb-0"><i class="ti ti-info-circle me-1"></i>{{ __('If a client with the same ID already exists for this company, their data will be updated (not duplicated).') }}</p>
                    </div>
                    <form id="import-form" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-bold">{{ __('Target B2B Company') }}</label>
                            <select name="company_id" class="form-select select2" id="import-company-id" required>
                                <option value="">{{ __('Select Company') }}</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                            <span class="company_id-error text-danger small text-error"></span>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold">{{ __('Excel/CSV File') }}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti ti-upload"></i></span>
                                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                            </div>
                            <span class="file-error text-danger small text-error"></span>
                        </div>
                        <div class="text-center mb-3">
                            <button type="submit" id="import-submit-btn" class="btn btn-success w-100 py-2">
                                <i class="ti ti-upload me-1"></i>{{ __('Start Bulk Upload') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
