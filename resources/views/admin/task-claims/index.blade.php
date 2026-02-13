@extends('layouts/layoutMaster')

@section('title', __('Task Claim Requests'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/js/admin/task-claims.js'])
@endsection

@section('content')
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">{{ __('Tasks') }} /</span> {{ __('Claim Requests') }}
    </h4>

    {{-- Statistics Cards --}}
    <div class="row g-6 mb-6">
        {{-- Total Claims --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Total Claims') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['total'] }}</h4>
                            </div>
                            <small class="text-muted">{{ __('Today') }}: {{ $stats['today'] }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-file-text ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pending --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card cursor-pointer stat-filter-card" data-status="pending">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Pending') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['pending'] }}</h4>
                                @if($stats['today_pending'] > 0)
                                    <span class="badge bg-warning rounded-pill">{{ $stats['today_pending'] }} {{ __('new') }}</span>
                                @endif
                            </div>
                            <small class="text-muted">{{ __('Awaiting review') }}</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-clock ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Approved --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card cursor-pointer stat-filter-card" data-status="approved">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Approved') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['approved'] }}</h4>
                            </div>
                            <small class="text-muted">{{ __('Successfully assigned') }}</small>
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

        {{-- Rejected --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card cursor-pointer stat-filter-card" data-status="rejected">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">{{ __('Rejected') }}</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $stats['rejected'] }}</h4>
                            </div>
                            <small class="text-muted">{{ __('Declined requests') }}</small>
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

    {{-- Claims List Table --}}
    <div class="card">
        <div class="card-header border-bottom">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="card-title mb-0">
                    <i class="tf-icons ti ti-list-check me-2 fs-3 text-white bg-primary rounded p-1"></i>
                    {{ __('Claim Requests') }}
                </h5>
                <div class="d-flex align-items-center gap-3 mt-3 mt-md-0">
                    <div class="btn-group" role="group" id="statusFilterBtns">
                        <button type="button" class="btn btn-label-primary btn-sm active" data-status="">
                            <i class="ti ti-list me-1 ti-xs"></i>{{ __('All') }}
                        </button>
                        <button type="button" class="btn btn-label-warning btn-sm" data-status="pending">
                            <i class="ti ti-clock me-1 ti-xs"></i>{{ __('Pending') }}
                        </button>
                        <button type="button" class="btn btn-label-success btn-sm" data-status="approved">
                            <i class="ti ti-check me-1 ti-xs"></i>{{ __('Approved') }}
                        </button>
                        <button type="button" class="btn btn-label-danger btn-sm" data-status="rejected">
                            <i class="ti ti-x me-1 ti-xs"></i>{{ __('Rejected') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-task-claims table border-top">
                <thead>
                    <tr>
                        <th></th>
                        <th>{{ __('Task #') }}</th>
                        <th>{{ __('Driver') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Note') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Created At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Approve/Reject Modal --}}
    <div class="modal fade" id="reviewClaimModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="ti ti-clipboard-check me-2"></i>{{ __('Review Claim Request') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <div class="avatar avatar-lg mb-3" id="modalIcon">
                            <span class="avatar-initial rounded-circle bg-label-primary">
                                <i class="ti ti-clipboard-check ti-lg"></i>
                            </span>
                        </div>
                        <p class="text-muted mb-0" id="modalSubtitle">{{ __('Please provide a note for the driver (optional).') }}</p>
                    </div>
                    <form id="reviewClaimForm" class="row g-3">
                        <input type="hidden" id="claimId" name="id">
                        <input type="hidden" id="claimAction" name="action">
                        <div class="col-12">
                            <label class="form-label" for="adminNote">
                                <i class="ti ti-note me-1"></i>{{ __('Note') }}
                            </label>
                            <textarea class="form-control" id="adminNote" name="note" rows="3"
                                placeholder="{{ __('Enter note here...') }}"></textarea>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1" id="submitBtn">
                                <i class="ti ti-check me-1"></i>{{ __('Submit') }}
                            </button>
                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                                aria-label="Close">{{ __('Cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
