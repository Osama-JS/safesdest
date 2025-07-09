@php
    $currentRoute = request()->route()->getName();
    $teamId = $team->id ?? request()->route('team');
    $teamName = $team->name ?? 'Team';

    $breadcrumbs = [
        'teams.dashboard.index' => __('Dashboard'),
        'teams.dashboard.drivers' => __('Drivers'),
        'teams.dashboard.tasks' => __('Tasks'),
        'teams.dashboard.wallet' => __('Wallet'),
        'teams.dashboard.task-distribution' => __('Task Distribution'),
        'teams.dashboard.analytics' => __('Analytics'),
    ];

    $currentPageTitle = $breadcrumbs[$currentRoute] ?? __('Dashboard');
@endphp

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="{{ route('teams.teams') }}">
                        <i class="ti ti-users-group me-1"></i>{{ __('Teams') }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('teams.dashboard.index', $teamId) }}">{{ $teamName }}</a>
                </li>
                <li class="breadcrumb-item active">{{ $currentPageTitle }}</li>
            </ol>
        </nav>
        <h4 class="mb-1">
            <i class="tf-icons ti ti-users-group me-2 text-primary"></i>
            {{ $teamName }} - {{ $currentPageTitle }}
        </h4>
        @if (isset($team))
            <p class="text-muted mb-0">
                <i class="tf-icons ti ti-map-pin me-1"></i>{{ $team->address }}
            </p>
        @endif
    </div>

    <div class="d-flex gap-2 mt-3 mt-md-0">
        <a href="{{ route('teams.teams') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>{{ __('Back to Teams') }}
        </a>
        @if ($currentRoute !== 'teams.dashboard.index')
            <a href="{{ route('teams.dashboard.index', $teamId) }}" class="btn btn-outline-primary">
                <i class="ti ti-dashboard me-1"></i>{{ __('Dashboard') }}
            </a>
        @endif
    </div>
</div>
