@extends('layouts/layoutMaster')

@section('title', __('Provinces Management'))

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
  <script>
    const baseUrl = "{{ url('/') }}/";
  </script>
  @vite(['resources/js/admin/b2b/provinces.js'])
@endsection

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="py-3 mb-0">
      <span class="avatar-initial rounded bg-primary text-white ps-2 pb-2 me-2">
        <i class="ti ti-building-skyscraper ti-26px"></i>
      </span>
      <span class="text-muted fw-light">{{ __('B2B Module') }} /</span> {{ __('Provinces Management') }}
    </h4>
  </div>

  <!-- Statistics Cards -->
  <div class="row g-4 mb-4 animate__animated animate__fadeIn">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">{{ __('Total Provinces') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ $stats['total_provinces'] }}</h4>
              </div>
              <small class="text-muted">{{ __('System-wide regions') }}</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="ti ti-map ti-26px"></i>
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
              <span class="text-heading">{{ __('Active Regions') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">{{ $stats['active_provinces'] }}</h4>
              </div>
              <small class="text-muted">{{ __('Available for selection') }}</small>
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
              <span class="text-heading">{{ __('Inactive Regions') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-danger">{{ $stats['inactive_provinces'] }}</h4>
              </div>
              <small class="text-muted">{{ __('Currently hidden') }}</small>
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

  <!-- Provinces Table -->
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">{{ __('B2B Regions/Provinces Directory') }}</h5>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-provinces table border-top">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>{{ __('Name (AR)') }}</th>
            <th>{{ __('Name (EN)') }}</th>
            <th>{{ __('Region Group') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

  <!-- Add/Edit Modal -->
  <div class="modal fade" id="provinceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content p-3 p-md-4">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="modal-body">
          <div class="text-center mb-4">
            <h3 class="mb-2">{{ __('Province Profile') }}</h3>
            <p class="text-muted">{{ __('Define a new geographic region for B2B pricing and logistics.') }}</p>
          </div>
          <form id="province-form" class="row g-3">
            @csrf
            <input type="hidden" name="id" id="province-id">
            <div class="col-12">
              <label class="form-label">{{ __('Province Name (Arabic)') }}</label>
              <input type="text" name="name_ar" id="province-name-ar" class="form-control" placeholder="الرياض" required>
              <span class="name_ar-error text-danger small text-error"></span>
            </div>
            <div class="col-12">
              <label class="form-label">{{ __('Province Name (English)') }}</label>
              <input type="text" name="name_en" id="province-name-en" class="form-control" placeholder="Riyadh" required>
              <span class="name_en-error text-danger small text-error"></span>
            </div>
            <div class="col-12">
              <label class="form-label">{{ __('Region Grouping') }}</label>
              <input type="text" name="region" id="province-region" class="form-control" placeholder="Central Region">
              <span class="region-error text-danger small text-error"></span>
            </div>
            <div class="col-12">
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" id="province-status" value="1" checked>
                <label class="form-check-label" for="province-status">{{ __('Active') }}</label>
              </div>
            </div>
            <div class="col-12 text-center mt-4">
              <hr>
              <button type="submit" class="btn btn-primary me-sm-3 me-1 px-4 shadow-sm">{{ __('Save Changes') }}</button>
              <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
