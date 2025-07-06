@extends('layouts/layoutMaster')

@section('title', __('Team Dashboard') . ' - ' . $team->name)

@section('teams-isactive')
    active
@endsection
<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
    @vite(['resources/css/app.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

<!-- Page Scripts -->
@section('page-script')
    <script>
        const teamId = {{ $team->id }};
        const teamStats = @json($stats);
    </script>
    @vite(['resources/js/admin/teams/dashboard/main.js'])
@endsection

@section('content')
    <!-- Breadcrumbs -->
    @include('admin.teams.dashboard.partials.breadcrumbs', ['team' => $team])

    <!-- Navigation -->
    @include('admin.teams.dashboard.partials.navigation', ['team' => $team])

    <!-- Stats Widgets -->
    @include('admin.teams.dashboard.partials.stats-widgets', ['stats' => $stats])



    <!-- Team Information -->
    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-info-circle me-2"></i>{{ __('Team Information') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-semibold">{{ __('Team Name') }}:</td>
                                    <td>{{ $team->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">{{ __('Team ID') }}:</td>
                                    <td>{{ $team->id }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">{{ __('Address') }}:</td>
                                    <td>{{ $team->address }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">{{ __('Commission Type') }}:</td>
                                    <td>{{ ucfirst($team->team_commission_type ?? 'N/A') }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-semibold">{{ __('Commission Value') }}:</td>
                                    <td>{{ $team->team_commission_value ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">{{ __('Location Update Interval') }}:</td>
                                    <td>{{ $team->location_update_interval ?? 30 }} {{ __('seconds') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">{{ __('Public Team') }}:</td>
                                    <td>
                                        <span class="badge bg-label-{{ $team->is_public ? 'success' : 'secondary' }}">
                                            {{ $team->is_public ? __('Yes') : __('No') }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold">{{ __('Created At') }}:</td>
                                    <td>{{ $team->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    @if ($team->note)
                        <div class="mt-3">
                            <h6>{{ __('Notes') }}:</h6>
                            <p class="text-muted">{{ $team->note }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
