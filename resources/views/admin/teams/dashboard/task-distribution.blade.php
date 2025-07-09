@extends('layouts/layoutMaster')

@section('title', __('Task Distribution') . ' - ' . $team->name)

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
    @vite(['resources/css/app.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    <script>
        const teamID = {{ $team->id }};
        const availableDrivers = @json($team->drivers);
    </script>
@endsection

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/js/admin/teams/dashboard/task-distribution.js'])
@endsection

@section('content')
    <!-- Breadcrumbs -->
    @include('admin.teams.dashboard.partials.breadcrumbs', ['team' => $team])

    <!-- Navigation -->
    @include('admin.teams.dashboard.partials.navigation', ['team' => $team])

    <!-- Task Distribution Content -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ti ti-truck-delivery me-2"></i>{{ __('Available Tasks for Assignment') }}
                        </h5>
                        <small class="text-muted">{{ __('Tasks matching team vehicle sizes and geofence areas') }}</small>
                    </div>
                    <button type="button" class="btn btn-primary" id="refresh-tasks">
                        <i class="ti ti-refresh me-1"></i>{{ __('Refresh') }}
                    </button>
                </div>

                <!-- Team Info -->
                <div class="card-body border-bottom">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <div class="avatar-initial bg-label-info rounded">
                                        <i class="ti ti-users"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0" id="team-drivers-count">{{ $team->drivers->count() }}</h6>
                                    <small class="text-muted">{{ __('Team Drivers') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <div class="avatar-initial bg-label-success rounded">
                                        <i class="ti ti-map-pin"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0" id="team-geofences-count">-</h6>
                                    <small class="text-muted">{{ __('Geofences') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <div class="avatar-initial bg-label-warning rounded">
                                        <i class="ti ti-truck"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0" id="vehicle-sizes-count">-</h6>
                                    <small class="text-muted">{{ __('Vehicle Sizes') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <div class="avatar-initial bg-label-primary rounded">
                                        <i class="ti ti-clipboard-list"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0" id="available-tasks-count">0</h6>
                                    <small class="text-muted">{{ __('Available Tasks') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasks Container -->
                <div class="card-body">
                    <div id="tasks-loading" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ __('Loading...') }}</span>
                        </div>
                        <p class="mt-2 text-muted">{{ __('Loading available tasks...') }}</p>
                    </div>

                    <div id="tasks-container">
                        <!-- Tasks will be loaded here -->
                    </div>

                    <div id="no-tasks-message" class="text-center py-5" style="display: none;">
                        <div class="avatar avatar-xl mx-auto mb-3">
                            <div class="avatar-initial bg-label-secondary rounded">
                                <i class="ti ti-clipboard-off" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                        <h6 class="mb-1">{{ __('No Available Tasks') }}</h6>
                        <p class="text-muted">{{ __('There are no tasks available for assignment at the moment.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignTitle">{{ __('Assign Task') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form class="add-new-user pt-0 form_submit" method="POST" action="{{ route('tasks.assign') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="col-xl-12">
                            <div class="nav-align-top">
                                <div class="tab-content">
                                    <div class="tab-pane fade show active">
                                        <input type="hidden" name="id" id="task-assign-id">
                                        <span class="id-error text-danger text-error"></span>
                                        <div class="mb-4">
                                            <label for="task-driver" class="form-label">{{ __('Select Driver') }}</label>
                                            <select name="driver" id="task-driver" class="form-select select2">
                                                <option value="">{{ __('Select Driver') }}</option>
                                            </select>
                                            <span class="driver-error text-danger text-error"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary"
                            data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Assign Task') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
