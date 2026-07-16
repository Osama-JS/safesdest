@extends('layouts/layoutMaster')

@section('title', __('WhatsApp Templates'))

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
@endsection

@section('content')

<style>
:root {
    --wa-green: #25D366;
    --wa-green-dark: #128C7E;
    --wa-green-light: #DCF8C6;
    --wa-teal: #075E54;
    --wa-bubble-bg: #f0f2f5;
}

/* Hero */
.wa-hero {
    background: linear-gradient(135deg, #075E54 0%, #128C7E 60%, #25D366 100%);
    border-radius: 16px;
    padding: 32px;
    color: white;
    position: relative;
    overflow: hidden;
    margin-bottom: 28px;
}
.wa-hero::before {
    content:''; position:absolute; top:-40px; right:-40px;
    width:200px; height:200px; border-radius:50%;
    background:rgba(255,255,255,0.06);
}
.wa-hero::after {
    content:''; position:absolute; bottom:-60px; left:20%;
    width:280px; height:280px; border-radius:50%;
    background:rgba(255,255,255,0.04);
}
.wa-logo-circle {
    width:64px; height:64px;
    background:white;
    border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    margin-bottom:16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.wa-logo-svg { width:38px; height:38px; }

/* Stat Cards */
.wa-stat-card {
    border-radius: 12px;
    padding: 12px 14px;
    transition: transform .2s, box-shadow .2s;
}
.wa-stat-card:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,.08); }
.wa-stat-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.wa-stat-icon.green  { background: rgba(37,211,102,.12); color: #128C7E; }
.wa-stat-icon.teal   { background: rgba(7,94,84,.10);    color: #075E54; }
.wa-stat-icon.orange { background: rgba(255,193,7,.15);  color: #c79100; }
.wa-stat-icon.red    { background: rgba(220,53,69,.12);  color: #dc3545; }


/* Template Cards */
.wa-grid-card {
    background:var(--bs-card-bg,#fff);
    border:1px solid rgba(0,0,0,.07);
    border-radius:14px; overflow:hidden;
    transition:transform .2s, box-shadow .2s;
    cursor:pointer; height:100%;
}
.wa-grid-card:hover { transform:translateY(-4px); box-shadow:0 12px 30px rgba(0,0,0,.10); }
.wa-grid-card-header {
    padding:14px 16px;
    display:flex; align-items:center; justify-content:space-between;
    border-bottom:1px solid rgba(0,0,0,.06);
    background:rgba(7,94,84,.04);
}
.wa-template-badge {
    font-size:11px; padding:3px 10px;
    border-radius:20px; font-weight:600;
}
.wa-grid-card-body { padding:16px; }
.wa-mini-preview { background:var(--wa-bubble-bg); border-radius:10px; padding:10px; min-height:60px; }
.wa-mini-bubble {
    background:var(--wa-green-light); border-radius:10px 0 10px 10px;
    padding:8px 10px; font-size:12px; line-height:1.5; color:#111;
    max-width:90%; margin-left:auto; position:relative; word-break:break-word;
}
.wa-mini-bubble::after {
    content:''; position:absolute; top:0; right:-6px;
    border-left:6px solid var(--wa-green-light);
    border-bottom:6px solid transparent;
}
.badge-approved  { background:rgba(37,211,102,.12); color:#128c7e; }
.badge-pending   { background:rgba(255,193,7,.15);  color:#c79100; }
.badge-rejected  { background:rgba(220,53,69,.12);  color:#dc3545; }
.badge-local     { background:rgba(108,117,125,.12); color:#495057; }

/* Phone mockup */
.wa-phone-wrap { display:flex; justify-content:center; }
.wa-phone {
    width:280px; background:#fff; border-radius:32px;
    box-shadow:0 20px 60px rgba(0,0,0,.18);
    overflow:hidden; border:8px solid #1a1a2e;
}
.wa-phone-notch {
    height:20px; background:#1a1a2e;
    display:flex; align-items:center; justify-content:center;
}
.wa-phone-notch::after {
    content:''; width:55px; height:5px;
    background:#333; border-radius:3px;
}
.wa-chat-header {
    background:var(--wa-teal); padding:10px 14px;
    display:flex; align-items:center; gap:10px; color:white;
}
.wa-chat-avatar {
    width:36px; height:36px; background:var(--wa-green);
    border-radius:50%; display:flex; align-items:center; justify-content:center;
    flex-shrink:0; overflow:hidden;
}
.wa-chat-body {
    background:var(--wa-bubble-bg); min-height:250px;
    padding:14px 10px; display:flex; flex-direction:column; gap:8px;
}
.wa-bubble-out {
    background:var(--wa-green-light); border-radius:12px 0 12px 12px;
    padding:9px 11px; max-width:88%; align-self:flex-end;
    box-shadow:0 1px 2px rgba(0,0,0,.1); font-size:13px;
    line-height:1.5; color:#111; position:relative; word-break:break-word;
}
.wa-bubble-out::after {
    content:''; position:absolute; top:0; right:-8px;
    border-left:8px solid var(--wa-green-light);
    border-bottom:8px solid transparent;
}
.wa-bubble-time { font-size:10px; color:#8696a0; text-align:right; margin-top:4px; display:flex; align-items:center; justify-content:flex-end; gap:3px; }
.wa-bubble-check { color:#53bdeb; }
.wa-header-media { width:100%; height:80px; background:linear-gradient(135deg,var(--wa-teal),var(--wa-green)); border-radius:8px; display:flex; align-items:center; justify-content:center; color:white; font-size:28px; margin-bottom:6px; }

/* View toggle */
.view-toggle .btn.active { background:var(--wa-teal); color:white; border-color:var(--wa-teal); }
.spin { animation:spin 1s linear infinite; }
@keyframes spin { 100% { transform:rotate(360deg); } }

/* Dark mode */
[data-bs-theme="dark"] .wa-grid-card { background:var(--bs-card-bg); border-color:rgba(255,255,255,.08); }
[data-bs-theme="dark"] .wa-phone { border-color:#111; }
[data-bs-theme="dark"] .wa-chat-body { background:#1a2634; }
[data-bs-theme="dark"] .wa-bubble-out { background:#025c4c; color:#eee; }
[data-bs-theme="dark"] .wa-bubble-out::after { border-left-color:#025c4c; }
[data-bs-theme="dark"] .wa-mini-bubble { background:#025c4c; color:#eee; }
[data-bs-theme="dark"] .wa-mini-bubble::after { border-left-color:#025c4c; }
[data-bs-theme="dark"] .wa-mini-preview { background:#1a2634; }
[data-bs-theme="dark"] .wa-grid-card-header { background:rgba(255,255,255,.03); }
</style>

{{-- ─── Hero Banner ─────────────────────────────────── --}}
<div class="wa-hero mb-6">
    <div class="wa-logo-circle">
        <svg class="wa-logo-svg" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="24" cy="24" r="24" fill="#25D366"/>
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M34.4 13.5C31.8 10.9 28.3 9.5 24.6 9.5C16.9 9.5 10.7 15.7 10.7 23.4C10.7 25.9 11.4 28.3 12.6 30.4L10.5 38L18.3 35.9C20.3 37 22.4 37.6 24.6 37.6C32.3 37.6 38.5 31.4 38.5 23.7C38.5 20 37.1 16.5 34.4 13.5ZM24.6 35.1C22.6 35.1 20.7 34.6 19 33.6L18.6 33.3L14.1 34.5L15.3 30.1L15 29.7C13.9 27.9 13.3 25.7 13.3 23.4C13.3 17.1 18.4 12 24.6 12C27.6 12 30.4 13.2 32.5 15.3C34.6 17.4 35.9 20.2 35.9 23.2C35.9 29.7 30.9 35.1 24.6 35.1ZM30.8 26.3C30.5 26.2 28.9 25.4 28.7 25.3C28.4 25.2 28.2 25.2 28 25.5C27.8 25.8 27.2 26.5 27 26.7C26.8 26.9 26.6 26.9 26.3 26.8C25.2 26.3 24.2 25.6 23.4 24.7C22.6 23.8 22 22.8 21.6 21.7C21.4 21.4 21.6 21.2 21.8 21C22 20.8 22.2 20.5 22.4 20.3C22.5 20.1 22.6 19.9 22.6 19.7C22.7 19.5 22.6 19.3 22.5 19.1C22.4 18.9 21.8 17.4 21.5 16.7C21.3 16.1 21 16.1 20.8 16.1C20.6 16.1 20.4 16.1 20.2 16.1C20 16.1 19.6 16.2 19.3 16.5C19 16.8 18.2 17.6 18.2 19.1C18.2 20.6 19.3 22 19.5 22.2C19.7 22.4 21.8 25.8 25.1 27.1C28.4 28.4 28.4 28 29 27.9C29.6 27.9 30.9 27.1 31.1 26.4C31.3 25.7 31.3 25.1 31.2 25C31.1 24.8 30.9 24.8 30.8 26.3Z"
                fill="white"/>
        </svg>
    </div>
    <h3 class="mb-1 text-white fw-bold fs-4">{{ __('WhatsApp Templates') }}</h3>
    <p class="mb-0 opacity-75" style="font-size:14px;">{{ __('Manage and sync your WhatsApp message templates with Meta Business') }}</p>
    <div class="mt-4 d-flex flex-wrap gap-2" style="position:relative; z-index:2;">
        <button id="syncCloudBtn" onclick="syncFromCloud()"
            class="btn btn-sm fw-semibold"
            style="background:rgba(255,255,255,.15); color:white; border:1px solid rgba(255,255,255,.35); backdrop-filter:blur(8px);">
            <i class="ti ti-cloud-download me-1"></i> {{ __('Sync from Meta') }}
        </button>
        <button class="btn btn-sm fw-semibold" style="background:white; color:#075E54;"
            data-bs-toggle="modal" data-bs-target="#submitModal" onclick="openAddModal()">
            <i class="ti ti-plus me-1"></i> {{ __('Add Manually') }}
        </button>
    </div>
</div>

{{-- ─── Stats Row ───────────────────────────────────── --}}
<div class="row g-3 mb-5">
    <div class="col-sm-6 col-xl-3">
        <div class="card wa-stat-card">
            <div class="card-body p-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1" style="font-size:12px;">{{ __('Total Templates') }}</span>
                        <h5 class="mb-0 fw-bold" id="stat-total">{{ $templatesCount }}</h5>
                    </div>
                    <div class="wa-stat-icon green">
                        <i class="ti ti-template"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card wa-stat-card">
            <div class="card-body p-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1" style="font-size:12px;">{{ __('Approved / Active') }}</span>
                        <h5 class="mb-0 fw-bold" id="stat-approved">{{ $activeCount }}</h5>
                    </div>
                    <div class="wa-stat-icon teal">
                        <i class="ti ti-circle-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card wa-stat-card">
            <div class="card-body p-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1" style="font-size:12px;">{{ __('Pending Review') }}</span>
                        <h5 class="mb-0 fw-bold" id="stat-pending">0</h5>
                    </div>
                    <div class="wa-stat-icon orange">
                        <i class="ti ti-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card wa-stat-card">
            <div class="card-body p-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block mb-1" style="font-size:12px;">{{ __('Inactive / Rejected') }}</span>
                        <h5 class="mb-0 fw-bold" id="stat-inactive">{{ $inactiveCount }}</h5>
                    </div>
                    <div class="wa-stat-icon red">
                        <i class="ti ti-circle-x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── Templates Section ───────────────────────────── --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3 py-4">
        <div>
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <svg width="20" height="20" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="24" fill="#25D366"/><path fill-rule="evenodd" clip-rule="evenodd" d="M34.4 13.5C31.8 10.9 28.3 9.5 24.6 9.5C16.9 9.5 10.7 15.7 10.7 23.4C10.7 25.9 11.4 28.3 12.6 30.4L10.5 38L18.3 35.9C20.3 37 22.4 37.6 24.6 37.6C32.3 37.6 38.5 31.4 38.5 23.7C38.5 20 37.1 16.5 34.4 13.5ZM24.6 35.1C22.6 35.1 20.7 34.6 19 33.6L18.6 33.3L14.1 34.5L15.3 30.1L15 29.7C13.9 27.9 13.3 25.7 13.3 23.4C13.3 17.1 18.4 12 24.6 12C27.6 12 30.4 13.2 32.5 15.3C34.6 17.4 35.9 20.2 35.9 23.2C35.9 29.7 30.9 35.1 24.6 35.1ZM30.8 26.3C30.5 26.2 28.9 25.4 28.7 25.3C28.4 25.2 28.2 25.2 28 25.5C27.8 25.8 27.2 26.5 27 26.7C26.8 26.9 26.6 26.9 26.3 26.8C25.2 26.3 24.2 25.6 23.4 24.7C22.6 23.8 22 22.8 21.6 21.7C21.4 21.4 21.6 21.2 21.8 21C22 20.8 22.2 20.5 22.4 20.3C22.5 20.1 22.6 19.9 22.6 19.7C22.7 19.5 22.6 19.3 22.5 19.1C22.4 18.9 21.8 17.4 21.5 16.7C21.3 16.1 21 16.1 20.8 16.1C20.6 16.1 20.4 16.1 20.2 16.1C20 16.1 19.6 16.2 19.3 16.5C19 16.8 18.2 17.6 18.2 19.1C18.2 20.6 19.3 22 19.5 22.2C19.7 22.4 21.8 25.8 25.1 27.1C28.4 28.4 28.4 28 29 27.9C29.6 27.9 30.9 27.1 31.1 26.4C31.3 25.7 31.3 25.1 31.2 25C31.1 24.8 30.9 24.8 30.8 26.3Z" fill="white"/></svg>
                {{ __('Message Templates') }}
            </h5>
            <small class="text-muted">{{ __('Click a template to preview it as a WhatsApp message') }}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="btn-group view-toggle">
                <button class="btn btn-sm btn-outline-secondary active" id="viewGrid" title="Card View"><i class="ti ti-layout-grid"></i></button>
                <button class="btn btn-sm btn-outline-secondary" id="viewTable" title="Table View"><i class="ti ti-table"></i></button>
            </div>
            <input type="text" class="form-control form-control-sm" id="globalSearch"
                placeholder="{{ __('Search...') }}" style="width:180px;">
        </div>
    </div>

    {{-- Grid View --}}
    <div class="card-body" id="gridViewContainer">
        <div class="row g-4" id="templatesGrid">
            <div class="col-12 text-center py-5" id="gridLoader">
                <div class="spinner-border" style="color:var(--wa-teal);" role="status"></div>
                <div class="mt-2 text-muted">{{ __('Loading templates...') }}</div>
            </div>
        </div>
    </div>

    {{-- Table View --}}
    <div id="tableViewContainer" style="display:none;">
        <div class="card-datatable table-responsive">
            <table class="table" id="templatesTable">
                <thead class="border-top">
                    <tr>
                        <th></th>
                        <th>#</th>
                        <th>{{ __('Template Name') }}</th>
                        <th>{{ __('Purpose') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Language') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Meta Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- ─── Preview Modal ───────────────────────────────── --}}
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--wa-teal); color:white;">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="24" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M34.4 13.5C31.8 10.9 28.3 9.5 24.6 9.5C16.9 9.5 10.7 15.7 10.7 23.4C10.7 25.9 11.4 28.3 12.6 30.4L10.5 38L18.3 35.9C20.3 37 22.4 37.6 24.6 37.6C32.3 37.6 38.5 31.4 38.5 23.7C38.5 20 37.1 16.5 34.4 13.5ZM24.6 35.1C22.6 35.1 20.7 34.6 19 33.6L18.6 33.3L14.1 34.5L15.3 30.1L15 29.7C13.9 27.9 13.3 25.7 13.3 23.4C13.3 17.1 18.4 12 24.6 12C27.6 12 30.4 13.2 32.5 15.3C34.6 17.4 35.9 20.2 35.9 23.2C35.9 29.7 30.9 35.1 24.6 35.1ZM30.8 26.3C30.5 26.2 28.9 25.4 28.7 25.3C28.4 25.2 28.2 25.2 28 25.5C27.8 25.8 27.2 26.5 27 26.7C26.8 26.9 26.6 26.9 26.3 26.8C25.2 26.3 24.2 25.6 23.4 24.7C22.6 23.8 22 22.8 21.6 21.7C21.4 21.4 21.6 21.2 21.8 21C22 20.8 22.2 20.5 22.4 20.3C22.5 20.1 22.6 19.9 22.6 19.7C22.7 19.5 22.6 19.3 22.5 19.1C22.4 18.9 21.8 17.4 21.5 16.7C21.3 16.1 21 16.1 20.8 16.1C20.6 16.1 20.4 16.1 20.2 16.1C20 16.1 19.6 16.2 19.3 16.5C19 16.8 18.2 17.6 18.2 19.1C18.2 20.6 19.3 22 19.5 22.2C19.7 22.4 21.8 25.8 25.1 27.1C28.4 28.4 28.4 28 29 27.9C29.6 27.9 30.9 27.1 31.1 26.4C31.3 25.7 31.3 25.1 31.2 25C31.1 24.8 30.9 24.8 30.8 26.3Z" fill="#25D366"/></svg>
                    <span id="previewModalTitle">{{ __('Template Preview') }}</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">{{ __('Template Name') }}</label>
                            <div class="fw-semibold" id="prev-name">-</div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">{{ __('Purpose') }}</label>
                                <div class="fw-semibold" id="prev-purpose">-</div>
                            </div>
                            <div class="col-6">
                                <label class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">{{ __('Category') }}</label>
                                <div class="fw-semibold" id="prev-category">-</div>
                            </div>
                            <div class="col-6">
                                <label class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">{{ __('Language') }}</label>
                                <div class="fw-semibold" id="prev-language">-</div>
                            </div>
                            <div class="col-6">
                                <label class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">{{ __('Meta Status') }}</label>
                                <div id="prev-meta-status">-</div>
                            </div>
                        </div>
                        <div>
                            <label class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">{{ __('Body Text') }}</label>
                            <div class="p-3 rounded mt-1" style="background:rgba(0,0,0,.04);font-size:13px;line-height:1.7;" id="prev-body-text">-</div>
                        </div>
                        <div id="prev-components-section" class="mt-3" style="display:none;">
                            <label class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">{{ __('Components') }}</label>
                            <div id="prev-components" class="mt-2 d-flex flex-column gap-2"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted d-block text-center mb-3" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">{{ __('WhatsApp Preview') }}</label>
                        <div class="wa-phone-wrap">
                            <div class="wa-phone">
                                <div class="wa-phone-notch"></div>
                                <div class="wa-chat-header">
                                    <div class="wa-chat-avatar">
                                        <svg width="22" height="22" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="24" fill="#25D366"/><path fill-rule="evenodd" clip-rule="evenodd" d="M34.4 13.5C31.8 10.9 28.3 9.5 24.6 9.5C16.9 9.5 10.7 15.7 10.7 23.4C10.7 25.9 11.4 28.3 12.6 30.4L10.5 38L18.3 35.9C20.3 37 22.4 37.6 24.6 37.6C32.3 37.6 38.5 31.4 38.5 23.7C38.5 20 37.1 16.5 34.4 13.5ZM24.6 35.1C22.6 35.1 20.7 34.6 19 33.6L18.6 33.3L14.1 34.5L15.3 30.1L15 29.7C13.9 27.9 13.3 25.7 13.3 23.4C13.3 17.1 18.4 12 24.6 12C27.6 12 30.4 13.2 32.5 15.3C34.6 17.4 35.9 20.2 35.9 23.2C35.9 29.7 30.9 35.1 24.6 35.1ZM30.8 26.3C30.5 26.2 28.9 25.4 28.7 25.3C28.4 25.2 28.2 25.2 28 25.5C27.8 25.8 27.2 26.5 27 26.7C26.8 26.9 26.6 26.9 26.3 26.8C25.2 26.3 24.2 25.6 23.4 24.7C22.6 23.8 22 22.8 21.6 21.7C21.4 21.4 21.6 21.2 21.8 21C22 20.8 22.2 20.5 22.4 20.3C22.5 20.1 22.6 19.9 22.6 19.7C22.7 19.5 22.6 19.3 22.5 19.1C22.4 18.9 21.8 17.4 21.5 16.7C21.3 16.1 21 16.1 20.8 16.1C20.6 16.1 20.4 16.1 20.2 16.1C20 16.1 19.6 16.2 19.3 16.5C19 16.8 18.2 17.6 18.2 19.1C18.2 20.6 19.3 22 19.5 22.2C19.7 22.4 21.8 25.8 25.1 27.1C28.4 28.4 28.4 28 29 27.9C29.6 27.9 30.9 27.1 31.1 26.4C31.3 25.7 31.3 25.1 31.2 25C31.1 24.8 30.9 24.8 30.8 26.3Z" fill="white"/></svg>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size:13px;" id="prev-phone-name">Business</div>
                                        <div style="font-size:10px;opacity:.8;">{{ __('Business Account') }}</div>
                                    </div>
                                </div>
                                <div class="wa-chat-body" id="prev-phone-body">
                                    <div class="wa-bubble-out">
                                        <div id="prev-bubble-header-area"></div>
                                        <div id="prev-bubble-body">{{ __('Select a template to preview') }}</div>
                                        <div id="prev-bubble-footer-area"></div>
                                        <div class="wa-bubble-time">
                                            {{ now()->format('H:i') }}
                                            <span class="wa-bubble-check">✓✓</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex gap-2 w-100 align-items-center justify-content-between">
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary" onclick="editFromPreview()"><i class="ti ti-edit me-1"></i>{{ __('Edit') }}</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteFromPreview()"><i class="ti ti-trash me-1"></i>{{ __('Delete') }}</button>
                    </div>
                    <button class="btn btn-sm btn-label-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ─── Add / Edit Modal ────────────────────────────── --}}
<div class="modal fade" id="submitModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--wa-teal); color:white;">
                <h5 class="modal-title d-flex align-items-center gap-2" id="modalTitle">
                    <i class="ti ti-plus"></i> {{ __('Add New Template') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <div class="col-md-7">
                        <form id="submitForm" class="form_submit" method="POST" action="{{ route('admin.whatsapp-templates.store') }}">
                            @csrf
                            <input type="hidden" id="template_id" name="id">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="purpose">{{ __('Purpose / Usage Key') }}</label>
                                    <input type="text" id="purpose" name="purpose" class="form-control" placeholder="e.g. otp, new_task" required>
                                    <div class="form-text">{{ __('Used internally to identify which template to send') }}</div>
                                    <span class="text-danger text-error purpose-error"></span>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="template_name">{{ __('Template Name in Meta') }}</label>
                                    <input type="text" id="template_name" name="template_name" class="form-control" placeholder="e.g. otp_verification_ar" required>
                                    <span class="text-danger text-error template_name-error"></span>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="language">{{ __('Language') }}</label>
                                    <select id="language" name="language" class="form-select" required>
                                        <option value="ar">{{ __('Arabic') }} (ar)</option>
                                        <option value="en">{{ __('English') }} (en)</option>
                                        <option value="en_US">{{ __('English US') }} (en_US)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="category">{{ __('Category') }}</label>
                                    <select id="category" name="category" class="form-select">
                                        <option value="">-- {{ __('Select') }}</option>
                                        <option value="AUTHENTICATION">AUTHENTICATION</option>
                                        <option value="MARKETING">MARKETING</option>
                                        <option value="UTILITY">UTILITY</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="body_text">{{ __('Body Text (Preview)') }}</label>
                                    <textarea id="body_text" name="body_text" class="form-control" rows="4"
                                        placeholder="{{ __('Enter template body text...') }}" oninput="updateLivePreview()"></textarea>
                                    <div class="form-text">{{ __('Use') }} &#123;&#123;1&#125;&#125;, &#123;&#123;2&#125;&#125; {{ __('for variables') }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="status" name="status" value="1" checked>
                                        <label class="form-check-label fw-semibold" for="status">{{ __('Active') }}</label>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-5">
                        <label class="text-muted d-block text-center mb-2" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;">{{ __('Live Preview') }}</label>
                        <div class="wa-phone-wrap">
                            <div class="wa-phone" style="width:220px;">
                                <div class="wa-phone-notch"></div>
                                <div class="wa-chat-header" style="padding:8px 10px;">
                                    <div class="wa-chat-avatar" style="width:28px;height:28px;">
                                        <svg width="18" height="18" viewBox="0 0 48 48" fill="none"><circle cx="24" cy="24" r="24" fill="#25D366"/><path fill-rule="evenodd" clip-rule="evenodd" d="M34.4 13.5C31.8 10.9 28.3 9.5 24.6 9.5C16.9 9.5 10.7 15.7 10.7 23.4C10.7 25.9 11.4 28.3 12.6 30.4L10.5 38L18.3 35.9C20.3 37 22.4 37.6 24.6 37.6C32.3 37.6 38.5 31.4 38.5 23.7C38.5 20 37.1 16.5 34.4 13.5ZM24.6 35.1C22.6 35.1 20.7 34.6 19 33.6L18.6 33.3L14.1 34.5L15.3 30.1L15 29.7C13.9 27.9 13.3 25.7 13.3 23.4C13.3 17.1 18.4 12 24.6 12C27.6 12 30.4 13.2 32.5 15.3C34.6 17.4 35.9 20.2 35.9 23.2C35.9 29.7 30.9 35.1 24.6 35.1ZM30.8 26.3C30.5 26.2 28.9 25.4 28.7 25.3C28.4 25.2 28.2 25.2 28 25.5C27.8 25.8 27.2 26.5 27 26.7C26.8 26.9 26.6 26.9 26.3 26.8C25.2 26.3 24.2 25.6 23.4 24.7C22.6 23.8 22 22.8 21.6 21.7C21.4 21.4 21.6 21.2 21.8 21C22 20.8 22.2 20.5 22.4 20.3C22.5 20.1 22.6 19.9 22.6 19.7C22.7 19.5 22.6 19.3 22.5 19.1C22.4 18.9 21.8 17.4 21.5 16.7C21.3 16.1 21 16.1 20.8 16.1C20.6 16.1 20.4 16.1 20.2 16.1C20 16.1 19.6 16.2 19.3 16.5C19 16.8 18.2 17.6 18.2 19.1C18.2 20.6 19.3 22 19.5 22.2C19.7 22.4 21.8 25.8 25.1 27.1C28.4 28.4 28.4 28 29 27.9C29.6 27.9 30.9 27.1 31.1 26.4C31.3 25.7 31.3 25.1 31.2 25C31.1 24.8 30.9 24.8 30.8 26.3Z" fill="white"/></svg>
                                    </div>
                                    <div style="font-size:11px; font-weight:600;">Business</div>
                                </div>
                                <div class="wa-chat-body" style="min-height:150px;padding:10px 8px;">
                                    <div class="wa-bubble-out" style="font-size:11px;padding:7px 9px;">
                                        <div id="live-preview-text" style="min-height:28px;white-space:pre-wrap;">{{ __('Preview here...') }}</div>
                                        <div class="wa-bubble-time" style="font-size:9px;">
                                            {{ now()->format('H:i') }} <span class="wa-bubble-check">✓✓</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" form="submitForm" class="btn data-submit fw-semibold" style="background:var(--wa-teal);color:white;">
                    <i class="ti ti-device-floppy me-1"></i> {{ __('Save Template') }}
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ─── JS Variables (PHP → JS) ─────────────────────── --}}
<script>
var templateDataUrl   = '{{ route("admin.whatsapp-templates.data") }}';
var templateStoreUrl  = '{{ route("admin.whatsapp-templates.store") }}';
var templateEditUrl   = '{{ url("admin/whatsapp-templates/edit") }}';
var templateDeleteUrl = '{{ route("admin.whatsapp-templates.delete") }}';
var templateStatusUrl = '{{ route("admin.whatsapp-templates.status") }}';
var templateSyncUrl   = '{{ route("admin.whatsapp-templates.fetch-cloud") }}';
var csrfToken         = '{{ csrf_token() }}';
</script>
@endsection

@section('page-script')
<script type="module">

/* ── helpers ─────────────────────────────────────────────── */
function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/* ── DataTable ───────────────────────────────────────────── */
let dtTable;

$(function () {
    dtTable = $('#templatesTable').DataTable({
        ajax: { url: templateDataUrl, type: 'GET' },
        columns: [
            { data: '' },
            { data: 'id' },
            { data: 'template_name' },
            { data: 'purpose' },
            { data: 'category' },
            { data: 'language' },
            { data: 'status' },
            { data: 'meta_status' },
            { data: 'actions' },
        ],
        columnDefs: [
            {
                targets: 0, className: 'control',
                searchable: false, orderable: false,
                render: function () { return ''; }
            },
            {
                targets: 1,
                render: function (d, t, r) { return '<span class="fw-semibold">#' + r.id + '</span>'; }
            },
            {
                targets: 6,
                render: function (d, t, r) {
                    if (r.status == 1) {
                        return '<span class="badge badge-approved wa-template-badge" style="cursor:pointer" onclick="changeStatus(' + r.id + ',0)">Active</span>';
                    }
                    return '<span class="badge badge-rejected wa-template-badge" style="cursor:pointer" onclick="changeStatus(' + r.id + ',1)">Inactive</span>';
                }
            },
            {
                targets: 7,
                render: function (d, t, r) {
                    if (!r.meta_status) { return '<span class="badge badge-local wa-template-badge">Local</span>'; }
                    var map = { APPROVED: 'badge-approved', PENDING: 'badge-pending', REJECTED: 'badge-rejected' };
                    var cls = map[r.meta_status] || 'badge-local';
                    return '<span class="badge ' + cls + ' wa-template-badge">' + r.meta_status + '</span>';
                }
            },
            {
                targets: -1,
                searchable: false, orderable: false,
                render: function (d, t, r) {
                    return '<div class="d-flex gap-1">'
                        + '<button class="btn btn-sm btn-icon" onclick="openPreviewModal(' + r.id + ')" title="Preview"><i class="ti ti-eye" style="color:var(--wa-teal)"></i></button>'
                        + '<button class="btn btn-sm btn-icon edit-record" data-id="' + r.id + '" title="Edit"><i class="ti ti-edit"></i></button>'
                        + '<button class="btn btn-sm btn-icon delete-record text-danger" data-id="' + r.id + '" title="Delete"><i class="ti ti-trash"></i></button>'
                        + '</div>';
                }
            }
        ],
        order: [[1, 'desc']],
        dom: '<"row mx-1"<"col-sm-12 col-md-6 d-flex align-items-center gap-3"l><"col-sm-12 col-md-6 d-flex justify-content-end"f>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        responsive: { details: { type: 'column', target: 0 } }
    });

    $(document).on('click', '.edit-record', function () {
        loadEditModal($(this).data('id'));
    });

    $(document).on('click', '.delete-record', function () {
        confirmDelete($(this).data('id'));
    });

    document.addEventListener('formSubmitted', function () {
        $('#submitModal').modal('hide');
        dtTable.ajax.reload();
        loadGrid();
    });
});

/* ── Grid View ───────────────────────────────────────────── */
var allTemplates = [];

function loadGrid() {
    $.get(templateDataUrl, {
        length: 200, start: 0, draw: 1,
        'order[0][column]': 1, 'order[0][dir]': 'desc', 'search[value]': ''
    }, function (res) {
        allTemplates = res.data || [];
        updateStats(allTemplates);
        renderGrid(allTemplates);
    });
}

function updateStats(data) {
    $('#stat-total').text(data.length);
    var approved = data.filter(function (t) {
        return t.meta_status === 'APPROVED' || (t.status == 1 && !t.meta_status);
    }).length;
    var pending  = data.filter(function (t) { return t.meta_status === 'PENDING'; }).length;
    var inactive = data.filter(function (t) { return t.status == 0; }).length;
    $('#stat-approved').text(approved);
    $('#stat-pending').text(pending);
    $('#stat-inactive').text(inactive);
}

function renderGrid(templates) {
    var grid = $('#templatesGrid');
    grid.empty();
    if (!templates.length) {
        grid.html('<div class="col-12 text-center py-5 text-muted"><i class="ti ti-template" style="font-size:48px;"></i><div class="mt-2">No templates yet</div></div>');
        return;
    }
    templates.forEach(function (t) {
        var statusCls = t.status == 1 ? 'badge-approved' : 'badge-rejected';
        var statusLbl = t.status == 1 ? 'Active' : 'Inactive';
        var metaMap = { APPROVED: 'badge-approved', PENDING: 'badge-pending', REJECTED: 'badge-rejected' };
        var metaCls = metaMap[t.meta_status] || 'badge-local';
        var metaLbl = t.meta_status || 'Local';
        var body = t.body_text
            ? esc(t.body_text).substring(0, 120) + (t.body_text.length > 120 ? '…' : '')
            : '<em class="text-muted">No body text</em>';
        var catMap = { AUTHENTICATION: '🔑', MARKETING: '📢', UTILITY: '⚙️' };
        var catIcon = catMap[t.category] || '📄';

        var html = '<div class="col-sm-6 col-xl-4">'
            + '<div class="wa-grid-card" onclick="openPreviewModal(' + t.id + ')">'
            + '<div class="wa-grid-card-header">'
            + '<div class="d-flex align-items-center gap-2">'
            + '<span style="font-size:18px;">' + catIcon + '</span>'
            + '<div>'
            + '<div class="fw-semibold" style="font-size:13px;line-height:1.2;">' + esc(t.template_name) + '</div>'
            + '<div class="text-muted" style="font-size:11px;">' + esc(t.purpose) + '</div>'
            + '</div></div>'
            + '<div class="d-flex flex-column gap-1 align-items-end">'
            + '<span class="badge ' + statusCls + ' wa-template-badge">' + statusLbl + '</span>'
            + '<span class="badge ' + metaCls + ' wa-template-badge">' + metaLbl + '</span>'
            + '</div>'
            + '</div>'
            + '<div class="wa-grid-card-body">'
            + '<div class="wa-mini-preview"><div class="wa-mini-bubble">' + body + '</div></div>'
            + '<div class="d-flex align-items-center justify-content-between mt-3">'
            + '<span class="text-muted" style="font-size:11px;">🌐 ' + esc(t.language) + '</span>'
            + '<div class="d-flex gap-1">'
            + '<button class="btn btn-xs btn-icon" style="font-size:13px;padding:3px 6px;" onclick="event.stopPropagation();loadEditModal(' + t.id + ')" title="Edit"><i class="ti ti-edit"></i></button>'
            + '<button class="btn btn-xs btn-icon text-danger" style="font-size:13px;padding:3px 6px;" onclick="event.stopPropagation();confirmDelete(' + t.id + ')" title="Delete"><i class="ti ti-trash"></i></button>'
            + '</div></div>'
            + '</div></div></div>';

        grid.append(html);
    });
}

/* ── Preview Modal ───────────────────────────────────────── */
var currentPreviewId = null;

window.openPreviewModal = function (id) {
    currentPreviewId = id;
    var t = null;
    for (var i = 0; i < allTemplates.length; i++) {
        if (allTemplates[i].id == id) { t = allTemplates[i]; break; }
    }
    if (!t) return;

    $('#previewModalTitle').text(t.template_name);
    $('#prev-name').text(t.template_name);
    $('#prev-purpose').text(t.purpose || '-');
    $('#prev-category').text(t.category || '-');
    $('#prev-language').text(t.language || '-');
    $('#prev-phone-name').text(t.template_name);

    var metaBadge = {
        APPROVED: '<span class="badge badge-approved wa-template-badge">✅ APPROVED</span>',
        PENDING:  '<span class="badge badge-pending wa-template-badge">⏳ PENDING</span>',
        REJECTED: '<span class="badge badge-rejected wa-template-badge">❌ REJECTED</span>'
    };
    $('#prev-meta-status').html(
        t.meta_status ? (metaBadge[t.meta_status] || t.meta_status) : '<span class="badge badge-local wa-template-badge">Local</span>'
    );

    var bodyText = t.body_text || 'No body text available';
    $('#prev-body-text').text(bodyText);
    $('#prev-bubble-body').text(bodyText);
    $('#prev-bubble-header-area').empty();
    $('#prev-bubble-footer-area').empty();

    var components = t.components || [];
    if (typeof components === 'string') {
        try { components = JSON.parse(components); } catch (e) { components = []; }
    }

    if (components.length) {
        $('#prev-components-section').show();
        var compHtml = '';
        components.forEach(function (c) {
            var icon = { HEADER: '🔖', BODY: '📝', FOOTER: '🔻', BUTTONS: '🔘' }[c.type] || '📄';
            var txt  = c.text || (c.buttons ? JSON.stringify(c.buttons) : '');
            compHtml += '<div class="p-2 rounded" style="background:rgba(0,0,0,.04);font-size:12px;">'
                + '<span class="fw-semibold">' + icon + ' ' + c.type + '</span>'
                + '<div class="text-muted mt-1">' + esc(txt) + '</div></div>';

            if (c.type === 'HEADER') {
                if (c.format === 'TEXT') {
                    $('#prev-bubble-header-area').html('<div class="fw-bold mb-1" style="font-size:13px;">' + esc(c.text || '') + '</div>');
                } else if (c.format === 'IMAGE' || c.format === 'VIDEO') {
                    $('#prev-bubble-header-area').html('<div class="wa-header-media mb-1">' + (c.format === 'VIDEO' ? '🎥' : '🖼️') + '</div>');
                } else if (c.format === 'DOCUMENT') {
                    $('#prev-bubble-header-area').html('<div class="wa-header-media mb-1">📄</div>');
                }
            }
            if (c.type === 'FOOTER') {
                $('#prev-bubble-footer-area').html('<div style="border-top:1px solid rgba(0,0,0,.1);margin-top:6px;padding-top:6px;font-size:11px;color:#8696a0;">' + esc(c.text || '') + '</div>');
            }
            if (c.type === 'BUTTONS' && c.buttons) {
                var btns = '';
                c.buttons.forEach(function (b) {
                    btns += '<a href="#" onclick="return false" style="display:block;text-align:center;padding:5px 0;border-top:1px solid rgba(0,0,0,.08);color:#00a5f4;font-size:12px;">' + esc(b.text || '') + '</a>';
                });
                $('#prev-bubble-footer-area').append(btns);
            }
        });
        $('#prev-components').html(compHtml);
    } else {
        $('#prev-components-section').hide();
    }

    $('#previewModal').modal('show');
};

window.editFromPreview = function () {
    if (!currentPreviewId) return;
    $('#previewModal').modal('hide');
    setTimeout(function () { loadEditModal(currentPreviewId); }, 300);
};

window.deleteFromPreview = function () {
    if (!currentPreviewId) return;
    $('#previewModal').modal('hide');
    setTimeout(function () { confirmDelete(currentPreviewId); }, 300);
};

/* ── Add / Edit Modal ────────────────────────────────────── */
window.openAddModal = function () {
    $('#submitForm')[0].reset();
    $('#template_id').val('');
    $('#modalTitle').html('<i class="ti ti-plus"></i> Add New Template');
    $('#live-preview-text').text('Preview here...');
    $('#status').prop('checked', true);
};

window.loadEditModal = function (id) {
    $.get(templateEditUrl + '/' + id, function (data) {
        $('#template_id').val(data.id);
        $('#purpose').val(data.purpose);
        $('#template_name').val(data.template_name);
        $('#language').val(data.language);
        $('#category').val(data.category || '');
        $('#body_text').val(data.body_text || '');
        $('#status').prop('checked', data.status == 1);
        $('#live-preview-text').text(data.body_text || 'Preview here...');
        $('#modalTitle').html('<i class="ti ti-edit"></i> Edit Template');
        $('#submitModal').modal('show');
    });
};

window.updateLivePreview = function () {
    $('#live-preview-text').text($('#body_text').val() || 'Preview here...');
};

/* ── Delete ──────────────────────────────────────────────── */
window.confirmDelete = function (id) {
    Swal.fire({
        title: 'Are you sure?',
        text: 'You will not be able to revert this!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-label-secondary' },
        buttonsStyling: false
    }).then(function (r) {
        if (!r.value) return;
        $.post(templateDeleteUrl, { _token: csrfToken, id: id }, function (res) {
            if (res.status == 1) {
                Swal.fire({ icon: 'success', title: 'Deleted!', text: res.success, customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
                dtTable && dtTable.ajax.reload();
                loadGrid();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.error, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false });
            }
        });
    });
};

/* ── Change Status ───────────────────────────────────────── */
window.changeStatus = function (id, status) {
    $.post(templateStatusUrl, { _token: csrfToken, id: id, status: status }, function (res) {
        if (res.status == 1) {
            dtTable && dtTable.ajax.reload(null, false);
            loadGrid();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.error, customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false });
        }
    });
};

/* ── Sync from Meta ──────────────────────────────────────── */
window.syncFromCloud = function () {
    var btn = $('#syncCloudBtn');
    btn.prop('disabled', true).html('<i class="ti ti-loader spin me-1"></i> Syncing...');
    $.post(templateSyncUrl, { _token: csrfToken }, function (res) {
        btn.prop('disabled', false).html('<i class="ti ti-cloud-download me-1"></i> Sync from Meta');
        if (res.status == 1) {
            Swal.fire({ icon: 'success', title: 'Synced!', html: '<p>' + res.message + '</p>', customClass: { confirmButton: 'btn btn-success' }, buttonsStyling: false });
            dtTable && dtTable.ajax.reload();
            loadGrid();
        } else {
            Swal.fire({ icon: 'warning', title: 'Could not sync', html: '<p>' + (res.message || res.error || 'Unknown error') + '</p>', customClass: { confirmButton: 'btn btn-warning' }, buttonsStyling: false });
        }
    }).fail(function () {
        btn.prop('disabled', false).html('<i class="ti ti-cloud-download me-1"></i> Sync from Meta');
        Swal.fire({ icon: 'error', title: 'Error', text: 'Request failed', customClass: { confirmButton: 'btn btn-danger' }, buttonsStyling: false });
    });
};

/* ── View Toggle ─────────────────────────────────────────── */
$('#viewGrid').on('click', function () {
    $(this).addClass('active');
    $('#viewTable').removeClass('active');
    $('#gridViewContainer').show();
    $('#tableViewContainer').hide();
});

$('#viewTable').on('click', function () {
    $(this).addClass('active');
    $('#viewGrid').removeClass('active');
    $('#tableViewContainer').show();
    $('#gridViewContainer').hide();
    dtTable && dtTable.ajax.reload(null, false);
});

/* ── Grid Search ─────────────────────────────────────────── */
$('#globalSearch').on('input', function () {
    var q = $(this).val().toLowerCase();
    var filtered = allTemplates.filter(function (t) {
        return (t.template_name || '').toLowerCase().indexOf(q) > -1
            || (t.purpose || '').toLowerCase().indexOf(q) > -1
            || (t.body_text || '').toLowerCase().indexOf(q) > -1;
    });
    renderGrid(filtered);
    dtTable && dtTable.search(q).draw();
});

/* ── Modal Reset ─────────────────────────────────────────── */
$('#submitModal').on('hidden.bs.modal', function () {
    $('#submitForm')[0].reset();
    $('#template_id').val('');
    $('#live-preview-text').text('Preview here...');
});

/* ── Init ────────────────────────────────────────────────── */
loadGrid();

</script>
@endsection
