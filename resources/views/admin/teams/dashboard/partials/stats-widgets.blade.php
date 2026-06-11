@php
    $balance = ($stats['wallet_credit'] ?? 0) - ($stats['wallet_debit'] ?? 0);
    $balanceClass = $balance < 0 ? 'text-danger' : 'text-success';
    $balanceSign = $balance < 0 ? '-' : '+';
@endphp

<div class="row g-4 mb-6">
    <!-- Drivers Stats -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-shrink-0">
                        <div class="avatar">
                            <div class="avatar-initial bg-primary rounded">
                                <i class="ti ti-steering-wheel ti-26px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <h6 class="mb-0">{{ __('Total Drivers') }}</h6>
                        </div>
                        <div class="d-flex align-items-center">
                            <h4 class="mb-0 me-2">{{ $stats['drivers_count'] ?? 0 }}</h4>
                            <small class="text-success">
                                <i class="ti ti-check ti-xs"></i>
                                {{ $stats['active_drivers'] ?? 0 }} {{ __('Active') }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks Stats -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-shrink-0">
                        <div class="avatar">
                            <div class="avatar-initial bg-warning rounded">
                                <i class="ti ti-clock ti-26px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <h6 class="mb-0">{{ __('Ongoing Tasks') }}</h6>
                        </div>
                        <div class="d-flex align-items-center">
                            <h4 class="mb-0 me-2">{{ $stats['ongoing_tasks'] ?? 0 }}</h4>
                            <small class="text-warning">
                                %{{ ($stats['tasks_count'] ?? 0) > 0
                                    ? round((($stats['ongoing_tasks'] ?? 0) / $stats['tasks_count']) * 100, 1)
                                    : 0 }}

                                {{ __('from total tasks') }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Completed Tasks -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-shrink-0">
                        <div class="avatar">
                            <div class="avatar-initial bg-success rounded">
                                <i class="ti ti-check ti-26px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <h6 class="mb-0">{{ __('Completed Tasks') }}</h6>
                        </div>
                        <div class="d-flex align-items-center">
                            <h4 class="mb-0 me-2">{{ $stats['completed_tasks'] ?? 0 }}</h4>
                            @if (($stats['tasks_count'] ?? 0) > 0)
                                <small class="text-success">
                                    %{{ ($stats['tasks_count'] ?? 0) > 0
                                        ? round((($stats['completed_tasks'] ?? 0) / $stats['tasks_count']) * 100, 1)
                                        : 0 }}

                                    {{ __('from total tasks') }}
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Balance -->
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="flex-shrink-0">
                        <div class="avatar">
                            <div class="avatar-initial {{ $balance < 0 ? 'bg-danger' : 'bg-success' }} rounded">
                                <i class="ti ti-wallet ti-26px"></i>
                            </div>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <h6 class="mb-0">{{ __('Wallet Balance') }}</h6>
                        </div>
                        <div class="d-flex align-items-center">
                            <h4 class="mb-0 me-2 {{ $balanceClass }}">
                                {{ $balanceSign }}{{ number_format(abs($balance), 2) }}
                            </h4>
                            <small class="text-muted">{{ __('SAR') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Wallet Stats -->
<div class="row g-4 mb-6">
    <div class="col-md-4">
        <div class="card border-start border-success border-3">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="ti ti-arrow-up-right text-success me-3 fs-2"></i>
                    <div>
                        <h6 class="mb-0">{{ __('Total Credit') }}</h6>
                        <h4 class="text-success mb-0">{{ number_format($stats['wallet_credit'] ?? 0, 2) }} SAR</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-start border-danger border-3">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="ti ti-arrow-down-left text-danger me-3 fs-2"></i>
                    <div>
                        <h6 class="mb-0">{{ __('Total Debit') }}</h6>
                        <h4 class="text-danger mb-0">{{ number_format($stats['wallet_debit'] ?? 0, 2) }} SAR</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-start border-primary border-3">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="ti ti-calculator text-primary me-3 fs-2"></i>
                    <div>
                        <h6 class="mb-0">{{ __('Net Balance') }}</h6>
                        <h4 class="mb-0 {{ $balanceClass }}">
                            {{ $balanceSign }}{{ number_format(abs($balance), 2) }} SAR
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
