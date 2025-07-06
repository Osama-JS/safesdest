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

    <!-- Task Distribution Interface -->
    <div class="row g-4">
        <!-- Task Assignment Form -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-send me-2"></i>{{ __('Assign New Task to Team Drivers') }}
                    </h5>
                </div>
                <div class="card-body">
                    <form id="taskDistributionForm" class="needs-validation" novalidate>
                        @csrf
                        <input type="hidden" name="team_id" value="{{ $team->id }}">
                        
                        <!-- Task Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="ti ti-info-circle me-2"></i>{{ __('Task Information') }}
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="task-title">* {{ __('Task Title') }}</label>
                                <input type="text" class="form-control" id="task-title" name="title" 
                                       placeholder="{{ __('Enter task title') }}" required>
                                <div class="invalid-feedback">{{ __('Please provide a task title') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="task-priority">{{ __('Priority') }}</label>
                                <select class="form-select" id="task-priority" name="priority">
                                    <option value="normal">{{ __('Normal') }}</option>
                                    <option value="high">{{ __('High') }}</option>
                                    <option value="urgent">{{ __('Urgent') }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Task Description -->
                        <div class="mb-4">
                            <label class="form-label" for="task-description">* {{ __('Task Description') }}</label>
                            <textarea class="form-control" id="task-description" name="description" rows="4" 
                                      placeholder="{{ __('Provide detailed task description...') }}" required></textarea>
                            <div class="invalid-feedback">{{ __('Please provide a task description') }}</div>
                        </div>

                        <!-- Location Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="ti ti-map-pin me-2"></i>{{ __('Location Details') }}
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="pickup-address">* {{ __('Pickup Address') }}</label>
                                <textarea class="form-control" id="pickup-address" name="pickup_address" rows="3" 
                                          placeholder="{{ __('Enter pickup address...') }}" required></textarea>
                                <div class="invalid-feedback">{{ __('Please provide pickup address') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="delivery-address">* {{ __('Delivery Address') }}</label>
                                <textarea class="form-control" id="delivery-address" name="delivery_address" rows="3" 
                                          placeholder="{{ __('Enter delivery address...') }}" required></textarea>
                                <div class="invalid-feedback">{{ __('Please provide delivery address') }}</div>
                            </div>
                        </div>

                        <!-- Timing Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="ti ti-clock me-2"></i>{{ __('Timing Information') }}
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="start-time">{{ __('Start Before') }}</label>
                                <input type="datetime-local" class="form-control" id="start-time" name="start_before">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="complete-time">{{ __('Complete Before') }}</label>
                                <input type="datetime-local" class="form-control" id="complete-time" name="complete_before">
                            </div>
                        </div>

                        <!-- Pricing Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="ti ti-currency-dollar me-2"></i>{{ __('Pricing Information') }}
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="task-price">* {{ __('Task Price') }}</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="task-price" name="price" 
                                           step="0.01" min="0" placeholder="0.00" required>
                                    <span class="input-group-text">SAR</span>
                                </div>
                                <div class="invalid-feedback">{{ __('Please provide task price') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="payment-method">{{ __('Payment Method') }}</label>
                                <select class="form-select" id="payment-method" name="payment_method">
                                    <option value="cash">{{ __('Cash') }}</option>
                                    <option value="card">{{ __('Card') }}</option>
                                    <option value="transfer">{{ __('Bank Transfer') }}</option>
                                    <option value="wallet">{{ __('Wallet') }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Driver Assignment -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">
                                    <i class="ti ti-users me-2"></i>{{ __('Driver Assignment') }}
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="assignment-type">{{ __('Assignment Type') }}</label>
                                <select class="form-select" id="assignment-type" name="assignment_type">
                                    <option value="specific">{{ __('Assign to Specific Driver') }}</option>
                                    <option value="broadcast">{{ __('Broadcast to All Available Drivers') }}</option>
                                    <option value="auto">{{ __('Auto-assign to Best Available Driver') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="specific-driver-section">
                                <label class="form-label" for="selected-driver">{{ __('Select Driver') }}</label>
                                <select class="form-select select2" id="selected-driver" name="driver_id">
                                    <option value="">{{ __('Choose a driver...') }}</option>
                                    @foreach($team->drivers as $driver)
                                        <option value="{{ $driver->id }}" data-status="{{ $driver->status }}" 
                                                data-online="{{ $driver->online }}">
                                            {{ $driver->name }} 
                                            @if($driver->status === 'active' && $driver->online)
                                                ({{ __('Online') }})
                                            @else
                                                ({{ __('Offline') }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Additional Notes -->
                        <div class="mb-4">
                            <label class="form-label" for="additional-notes">{{ __('Additional Notes') }}</label>
                            <textarea class="form-control" id="additional-notes" name="notes" rows="3" 
                                      placeholder="{{ __('Any additional instructions or notes for the driver...') }}"></textarea>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-end gap-3">
                            <button type="button" class="btn btn-outline-secondary" id="reset-form">
                                <i class="ti ti-refresh me-1"></i>{{ __('Reset Form') }}
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="preview-task">
                                <i class="ti ti-eye me-1"></i>{{ __('Preview Task') }}
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="ti ti-send me-1"></i>{{ __('Assign Task') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Team Drivers Status -->
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-steering-wheel me-2"></i>{{ __('Team Drivers Status') }}
                    </h5>
                </div>
                <div class="card-body">
                    @if($team->drivers->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($team->drivers as $driver)
                                <div class="list-group-item d-flex align-items-center px-0 py-3">
                                    <div class="avatar avatar-sm me-3">
                                        @if($driver->image)
                                            <img src="{{ asset($driver->image) }}" alt="{{ $driver->name }}" class="rounded-circle">
                                        @else
                                            <div class="avatar-initial bg-label-primary rounded-circle">
                                                {{ substr($driver->name, 0, 1) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">{{ $driver->name }}</h6>
                                        <small class="text-muted">{{ $driver->email }}</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div class="d-flex flex-column align-items-end">
                                            <span class="badge bg-label-{{ $driver->status === 'active' ? 'success' : 'secondary' }} mb-1">
                                                {{ ucfirst($driver->status) }}
                                            </span>
                                            <span class="badge bg-label-{{ $driver->online ? 'success' : 'secondary' }}">
                                                <i class="ti ti-circle{{ $driver->online ? '-filled' : '' }} me-1"></i>
                                                {{ $driver->online ? __('Online') : __('Offline') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti ti-users-off text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">{{ __('No drivers available') }}</p>
                            <a href="{{ route('teams.dashboard.drivers', $team->id) }}" class="btn btn-primary btn-sm">
                                {{ __('Add Drivers') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-chart-bar me-2"></i>{{ __('Quick Stats') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2">
                                    <div class="avatar-initial bg-label-success rounded">
                                        <i class="ti ti-users"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $team->drivers->where('status', 'active')->count() }}</h6>
                                    <small class="text-muted">{{ __('Active Drivers') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2">
                                    <div class="avatar-initial bg-label-info rounded">
                                        <i class="ti ti-circle-filled"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $team->drivers->where('online', 1)->count() }}</h6>
                                    <small class="text-muted">{{ __('Online Now') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2">
                                    <div class="avatar-initial bg-label-warning rounded">
                                        <i class="ti ti-truck-delivery"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $team->tasks->where('status', 'pending')->count() }}</h6>
                                    <small class="text-muted">{{ __('Pending Tasks') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-2">
                                    <div class="avatar-initial bg-label-primary rounded">
                                        <i class="ti ti-progress"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $team->tasks->whereIn('status', ['in_progress', 'started'])->count() }}</h6>
                                    <small class="text-muted">{{ __('In Progress') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Preview Modal -->
    <div class="modal fade" id="taskPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-eye me-2"></i>{{ __('Task Preview') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="task-preview-content">
                    <!-- Preview content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-success" id="confirm-assignment">
                        <i class="ti ti-send me-1"></i>{{ __('Confirm Assignment') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
