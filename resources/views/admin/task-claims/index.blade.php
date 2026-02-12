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

    <!-- Task Claims List Table -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-3">{{ __('Search Filter') }}</h5>
            <div class="d-flex justify-content-between align-items-center row pb-2 gap-3 gap-md-0">
                <div class="col-md-4 status_filter"></div>
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
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Created At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- Approve/Reject Modal -->
    <div class="modal fade" id="reviewClaimModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content p-3 p-md-5">
                <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h3 id="modalTitle">Review Claim Request</h3>
                        <p id="modalSubtitle">Please provide a note for the driver (optional).</p>
                    </div>
                    <form id="reviewClaimForm" class="row g-3">
                        <input type="hidden" id="claimId" name="id">
                        <input type="hidden" id="claimAction" name="action">
                        <div class="col-12">
                            <label class="form-label" for="adminNote">Note</label>
                            <textarea class="form-control" id="adminNote" name="note" rows="3" placeholder="Enter note here..."></textarea>
                        </div>
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary me-sm-3 me-1" id="submitBtn">Submit</button>
                            <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
