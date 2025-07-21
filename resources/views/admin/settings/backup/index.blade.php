@extends('layouts/layoutMaster')

@section('title', __('Backup Management'))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-2">
                        <i class="ti ti-adjustments me-2 fs-3 text-white bg-primary rounded p-1"></i>
                        {{ __('Settings') }} | {{ __('Backup Management') }}
                    </h5>
                    <p class="text-muted mb-3">{{ __('Manage database and uploaded files backups') }}</p>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#createBackupModal">
                            <i class="ti ti-plus me-1"></i>
                            {{ __('Create Backup') }}
                        </button>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal"
                            data-bs-target="#uploadRestoreModal">
                            <i class="ti ti-upload me-1"></i>
                            {{ __('Upload restore Backup') }}
                        </button>
                        <button type="button" class="btn btn-outline-info" id="refreshBackups">
                            <i class="ti ti-refresh me-1"></i>
                            {{ __('Refresh') }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
                            data-bs-target="#statisticsModal">
                            <i class="ti ti-chart-bar me-1"></i>
                            {{ __('Statistics') }}
                        </button>
                    </div>


                </div>

                <!-- Statistics Cards -->
                <div class="card-body border-bottom mt-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card ">
                                <div class="card-body text-center">
                                    <h3 class="card-title" id="totalBackups">0</h3>
                                    <p class="card-text">{{ __('Total Backups') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card ">
                                <div class="card-body text-center">
                                    <h3 class="card-title" id="totalSize">0 MB</h3>
                                    <p class="card-text">{{ __('Total Size') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card ">
                                <div class="card-body text-center">
                                    <h3 class="card-title" id="latestBackup">-</h3>
                                    <p class="card-text">{{ __('Latest Backup') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card ">
                                <div class="card-body text-center">
                                    <h3 class="card-title" id="backupHealth">{{ __('Good') }}</h3>
                                    <p class="card-text">{{ __('System Status') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-datatable table-responsive">
                    <table id="backupsTable" class="datatables-backups table border-top">
                        <thead>
                            <tr>
                                <th>{{ __('Backup Name') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Size') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Created At') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Backup Modal -->
    <div class="modal fade" id="createBackupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Create New Backup') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createBackupForm" class="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Backup Type') }}</label>
                                <select name="backup_type" class="form-select " required>
                                    <option value="">{{ __('Choose Type') }}</option>
                                    <option value="full">{{ __('Full Backup (Database + Files)') }}</option>
                                    <option value="database_only">{{ __('Database Only') }}</option>
                                    <option value="files_only">{{ __('Files Only') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Description (Optional)') }}</label>
                                <input type="text" name="description" class="form-control"
                                    placeholder="{{ __('Brief description of the backup') }}">
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i class="ti ti-info-circle me-2"></i>
                            <strong>{{ __('Note:') }}</strong>
                            {{ __('The backup creation process may take several minutes depending on the data size.') }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>
                            {{ __('Create Backup') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Restore Backup Modal -->
    <div class="modal fade" id="restoreBackupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Restore Backup') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="restoreBackupForm" class="">
                    <div class="modal-body">
                        <input type="hidden" name="backup_name" id="restoreBackupName">

                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <strong>{{ __('Warning:') }}</strong>
                            {{ __('The restore process will replace current data. Make sure to create a backup before proceeding.') }}
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">{{ __('Restore Type') }}</label>
                                <select name="restore_type" class="form-select " required>
                                    <option value="">{{ __('Choose Restore Type') }}</option>
                                    <option value="full">{{ __('Full Restore') }}</option>
                                    <option value="database_only">{{ __('Database Only') }}</option>
                                    <option value="files_only">{{ __('Files Only') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="confirmRestore" required>
                            <label class="form-check-label" for="confirmRestore">
                                {{ __('I confirm that I understand this process will replace current data') }}
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="ti ti-restore me-1"></i>
                            {{ __('Restore Backup') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Statistics Modal -->
    <div class="modal fade" id="statisticsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Backup Statistics') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="statisticsContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">{{ __('Loading...') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Backups From Uploaded File Modal -->
    <div class="modal fade" id="uploadRestoreModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Restore Backup from Uploaded File') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="uploadRestoreForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Backup File (ZIP)') }}</label>
                            <input type="file" class="form-control" name="backup_file" accept=".zip" required>
                            <small class="text-muted">{{ __('Maximum size: 1 GB') }}</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Backup Password') }}</label>
                            <input type="password" class="form-control" name="backup_password" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Restore Type') }}</label>
                            <select class="form-select" name="restore_type" required>
                                <option value="full">{{ __('Full Restore') }}</option>
                                <option value="database_only">{{ __('Database Only') }}</option>
                                <option value="files_only">{{ __('Files Only') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Restore Backup') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('page-script')
    @vite(['resources/js/admin/settings/backup.js', 'resources/js/ajax.js'])
@endsection
