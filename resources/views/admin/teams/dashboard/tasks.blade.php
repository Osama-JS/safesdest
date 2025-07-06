@extends('layouts/layoutMaster')

@section('title', __('Team Tasks') . ' - ' . $team->name)
@section('teams-isactive')
    active
@endsection
<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
    @vite(['resources/css/app.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
    <script>
        const teamID = {{ $team->id }}
    </script>
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/teams/dashboard/tasks.js'])
@endsection

@section('content')
    <!-- Breadcrumbs -->
    @include('admin.teams.dashboard.partials.breadcrumbs', ['team' => $team])

    <!-- Navigation -->
    @include('admin.teams.dashboard.partials.navigation', ['team' => $team])

    <!-- Tasks Management -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="ti ti-truck-delivery me-2"></i>{{ __('Team Tasks Management') }}
            </h5>
            {{-- <div class="d-flex gap-2">
                <a href="{{ route('teams.dashboard.task-distribution', $team->id) }}" class="btn btn-success">
                    <i class="ti ti-send me-1"></i>{{ __('Assign New Task') }}
                </a>

            </div> --}}
        </div>



        <!-- Quick Stats -->
        <div class="card-body border-bottom">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                            <div class="avatar-initial bg-label-primary rounded">
                                <i class="ti ti-truck-delivery"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-0" id="total-tasks">{{ $team->tasks->count() }}</h6>
                            <small class="text-muted">{{ __('Total Tasks') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                            <div class="avatar-initial bg-label-warning rounded">
                                <i class="ti ti-clock"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-0" id="pending-tasks">
                                {{ $team->tasks->where('status', 'completed')->where('closed', false)->count() }}
                            </h6>
                            <small class="text-muted">{{ __('Pending') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                            <div class="avatar-initial bg-label-info rounded">
                                <i class="ti ti-progress"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-0" id="progress-tasks">
                                {{ $team->tasks->whereIn('status', ['assign', 'in_progress', 'started', 'loading', 'in pickup point', 'in the way', 'in delivery point', 'unloading'])->count() }}
                            </h6>
                            <small class="text-muted">{{ __('In Progress') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-3">
                            <div class="avatar-initial bg-label-success rounded">
                                <i class="ti ti-check"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-0" id="completed-tasks">
                                {{ $team->tasks->where('status', 'completed')->where('closed', true)->count() }}</h6>
                            <small class="text-muted">{{ __('Completed') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tasks Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="datatables-tasks table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th></th>
                            <th>{{ __('Task ID') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Driver') }}</th>
                            <th>{{ __('Pickup Address') }}</th>
                            <th>{{ __('Start Before') }}</th>
                            <th>{{ __('Complete Before') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Payment') }}</th>
                            <th>{{ __('Created') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Task Details Modal -->
    <div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Task Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="task-details-content">
                    <!-- Task details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-primary" id="edit-task">{{ __('Edit Task') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Modal -->
    <div class="modal fade" id="bulkActionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Bulk Actions') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ __('Select an action to perform on selected tasks:') }}</p>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-warning" id="bulk-assign">
                            <i class="ti ti-user-plus me-2"></i>{{ __('Assign to Driver') }}
                        </button>
                        <button type="button" class="btn btn-outline-info" id="bulk-status">
                            <i class="ti ti-edit me-2"></i>{{ __('Change Status') }}
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="bulk-cancel">
                            <i class="ti ti-x me-2"></i>{{ __('Cancel Tasks') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
