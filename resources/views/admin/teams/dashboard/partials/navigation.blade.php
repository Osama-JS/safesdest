@php
    $currentRoute = request()->route()->getName();
    $teamId = $team->id ?? request()->route('team');
@endphp

<div class="nav-align-top nav-tabs-shadow mb-6">
    <ul class="nav nav-tabs nav-fill" role="tablist">
        <li class="nav-item">
            <a href="{{ route('teams.dashboard.index', $teamId) }}"
                class="nav-link py-4 {{ $currentRoute === 'teams.dashboard.index' ? 'active' : '' }}">
                <span class="d-none d-sm-block">
                    <i class="tf-icons ti ti-dashboard me-1"></i> {{ __('Dashboard') }}
                </span>
                <i class="ti ti-dashboard ti-sm d-sm-none"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('teams.dashboard.drivers', $teamId) }}"
                class="nav-link py-4 {{ $currentRoute === 'teams.dashboard.drivers' ? 'active' : '' }}">
                <span class="d-none d-sm-block">
                    <i class="tf-icons ti ti-steering-wheel me-1"></i> {{ __('Drivers') }}
                    @if (isset($team) && $team->drivers_count > 0)
                        <span
                            class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-primary ms-1">{{ $team->drivers_count }}</span>
                    @endif
                </span>
                <i class="ti ti-steering-wheel ti-sm d-sm-none"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('teams.dashboard.tasks', $teamId) }}"
                class="nav-link py-4 {{ $currentRoute === 'teams.dashboard.tasks' ? 'active' : '' }}">
                <span class="d-none d-sm-block">
                    <i class="tf-icons ti ti-truck-delivery me-1"></i> {{ __('Tasks') }}
                    @if (isset($team) && $team->tasks_count > 0)
                        <span
                            class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-info ms-1">{{ $team->tasks_count }}</span>
                    @endif
                </span>
                <i class="ti ti-truck-delivery ti-sm d-sm-none"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('teams.dashboard.wallet', $teamId) }}"
                class="nav-link py-4 {{ $currentRoute === 'teams.dashboard.wallet' ? 'active' : '' }}">
                <span class="d-none d-sm-block">
                    <i class="tf-icons ti ti-wallet me-1"></i> {{ __('Wallet') }}
                </span>
                <i class="ti ti-wallet ti-sm d-sm-none"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('teams.dashboard.task-distribution', $teamId) }}"
                class="nav-link py-4 {{ $currentRoute === 'teams.dashboard.task-distribution' ? 'active' : '' }}">
                <span class="d-none d-sm-block">
                    <i class="tf-icons ti ti-send me-1"></i> {{ __('Task Distribution') }}
                </span>
                <i class="ti ti-send ti-sm d-sm-none"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('teams.dashboard.analytics', $teamId) }}"
                class="nav-link py-4 {{ $currentRoute === 'teams.dashboard.analytics' ? 'active' : '' }}">
                <span class="d-none d-sm-block">
                    <i class="tf-icons ti ti-chart-bar me-1"></i> {{ __('Analytics') }}
                </span>
                <i class="ti ti-chart-bar ti-sm d-sm-none"></i>
            </a>
        </li>
    </ul>
</div>
