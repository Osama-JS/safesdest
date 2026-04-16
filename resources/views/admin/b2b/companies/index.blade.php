@extends('layouts/layoutMaster')

@section('title', __('B2B Companies Management'))

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
  @vite(['resources/js/admin/b2b/companies.js'])
@endsection

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-4">

    <h4 class="py-3 mb-0">
      <span class="avatar-initial rounded bg-primary text-white ps-2 pb-2 me-2">
        <i class="ti ti-building-skyscraper ti-26px"></i>
      </span>
      <span class="text-muted fw-light">{{ __('B2B Module') }} /</span> {{ __('Companies Management') }}
    </h4>
  </div>

  <!-- Statistics Cards -->
  <div class="row g-4 mb-4 animate__animated animate__fadeIn">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">{{ __('Total Companies') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2">{{ $stats['total_companies'] }}</h4>
              </div>
              <small class="text-muted">{{ __('Registered B2B entities') }}</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="ti ti-building ti-26px"></i>
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
              <span class="text-heading">{{ __('Active Warehouses') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-success">{{ $stats['total_warehouses'] }}</h4>
              </div>
              <small class="text-muted">{{ __('Available loading points') }}</small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
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
              <span class="text-heading">{{ __('Total End Clients') }}</span>
              <div class="d-flex align-items-center my-1">
                <h4 class="mb-0 me-2 text-info">{{ $stats['total_end_clients'] }}</h4>
              </div>
              <small class="text-muted">{{ __('Saved delivery destinations') }}</small>
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
  </div>

  <!-- Companies Table -->
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">{{ __('B2B Entities List') }}</h5>
    </div>
    <div class="card-datatable table-responsive">
      <table class="datatables-companies table border-top">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>{{ __('Company') }}</th>
            <th>{{ __('Email') }}</th>
            <th>{{ __('Phone') }}</th>
            <th>{{ __('WHs') }}</th>
            <th>{{ __('Clients') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
@endsection
