@extends('layouts/layoutMaster')

@section('title', __('User Commissions Management'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/@form-validation/form-validation.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/@form-validation/popular.js',
        'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
        'resources/assets/vendor/libs/@form-validation/auto-focus.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/js/admin/commissions.js'])
@endsection

@section('content')
    <!-- Statistics Cards -->
    <div class="row g-6 mb-6">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Total Commissions') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalCommissions }}</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-percentage ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Active Commissions') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $activeCommissions }}</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Inactive Commissions') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $inactiveCommissions }}</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-x ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Commissions List Table -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0">
                <i class="tf-icons ti ti-percentage me-2 fs-3 text-white bg-primary rounded p-1"></i>
                {{ __('User Commissions') }}
            </h5>
            @can('manage_user_commissions')
                <button class="add-new btn btn-primary waves-effect waves-light mt-5 mx-4" data-bs-toggle="modal"
                    data-bs-target="#submitModal">
                    <i class="ti ti-plus me-0 me-sm-1 ti-xs"></i>
                    <span class="d-none d-sm-inline-block"> {{ __('Add New Commission') }}</span>
                </button>
            @endcan
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-commissions table">
                <thead class="border-top">
                    <tr>
                        <th></th>
                        <th>{{ __('#') }}</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Commission Type') }}</th>
                        <th>{{ __('Commission Value') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Created At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Commission Form Modal -->
    <div class="modal fade" id="submitModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-simple modal-edit-user">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-6">
                        <h4 class="mb-2" id="modalTitle">{{ __('Add New Commission') }}</h4>
                        <p>{{ __('Set commission details for user') }}</p>
                    </div>
                    <form id="submitForm" class="row g-6" onsubmit="return false">
                        <input type="hidden" id="commission_id" name="id">
                        
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="user_id">{{ __('User') }}</label>
                            <select id="user_id" name="user_id" class="form-select select2">
                                <option value="">{{ __('Select User') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <div class="text-error" id="user_id-error"></div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="customer_id">{{ __('Customer') }}</label>
                            <select id="customer_id" name="customer_id" class="form-select select2">
                                <option value="">{{ __('Select Customer') }}</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            <div class="text-error" id="customer_id-error"></div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="commission_type">{{ __('Commission Type') }}</label>
                            <select id="commission_type" name="commission_type" class="form-select">
                                <option value="">{{ __('Select Type') }}</option>
                                <option value="percentage">{{ __('Percentage') }}</option>
                                <option value="fixed">{{ __('Fixed Amount') }}</option>
                            </select>
                            <div class="text-error" id="commission_type-error"></div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="commission_value">{{ __('Commission Value') }}</label>
                            <input type="number" id="commission_value" name="commission_value" class="form-control" 
                                   placeholder="{{ __('Enter commission value') }}" step="0.01" min="0">
                            <div class="text-error" id="commission_value-error"></div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                <label class="form-check-label" for="status">{{ __('Active') }}</label>
                            </div>
                        </div>

                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary me-3">{{ __('Save') }}</button>
                            <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal" aria-label="Close">{{ __('Cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const baseUrl = '{{ url('/') }}/';
        const commissionDataUrl = '{{ route('admin.commissions.getData') }}';
        const commissionStoreUrl = '{{ route('admin.commissions.store') }}';
        const commissionEditUrl = '{{ url('admin/commissions/edit') }}';
        const commissionDeleteUrl = '{{ route('admin.commissions.destroy') }}';
        const commissionStatusUrl = '{{ route('admin.commissions.changeStatus') }}';
    </script>
@endsection
