<?php $__env->startSection('title', __('Backup Management')); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-bottom">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-1">
                                <i class="ti ti-server-cog me-2 fs-3 text-white bg-primary rounded p-1"></i>
                                <?php echo e(__('Settings')); ?> | <?php echo e(__('Backup Management')); ?>

                            </h5>
                            <p class="text-muted mb-0"><?php echo e(__('إدارة النسخ الاحتياطي لقاعدة البيانات والملفات مع الرفع السحابي والجدولة التلقائية')); ?></p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                                <i class="ti ti-plus me-1"></i>
                                <?php echo e(__('Create Backup')); ?>

                            </button>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cloudSettingsModal">
                                <i class="ti ti-cloud-computing me-1"></i>
                                <?php echo e(__('النسخ التلقائي والسحابي')); ?>

                            </button>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadRestoreModal">
                                <i class="ti ti-upload me-1"></i>
                                <?php echo e(__('Upload restore Backup')); ?>

                            </button>
                            <button type="button" class="btn btn-outline-info" id="refreshBackups">
                                <i class="ti ti-refresh me-1"></i>
                                <?php echo e(__('Refresh')); ?>

                            </button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#statisticsModal">
                                <i class="ti ti-chart-bar me-1"></i>
                                <?php echo e(__('Statistics')); ?>

                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="card-body border-bottom mt-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="card bg-white border shadow-none">
                                <div class="card-body text-center p-3">
                                    <h3 class="card-title text-dark mb-1" id="totalBackups">0</h3>
                                    <p class="card-text text-muted mb-0"><?php echo e(__('Total Backups')); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-white border shadow-none">
                                <div class="card-body text-center p-3">
                                    <h3 class="card-title text-dark mb-1" id="totalSize">0 MB</h3>
                                    <p class="card-text text-muted mb-0"><?php echo e(__('Total Size')); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-white border shadow-none">
                                <div class="card-body text-center p-3">
                                    <h3 class="card-title text-dark mb-1" id="latestBackup">-</h3>
                                    <p class="card-text text-muted mb-0"><?php echo e(__('Latest Backup')); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-white border shadow-none">
                                <div class="card-body text-center p-3">
                                    <h3 class="card-title text-dark mb-1" id="autoBackupStatus">-</h3>
                                    <p class="card-text text-muted mb-0"><?php echo e(__('النسخ التلقائي')); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-datatable table-responsive">
                    <table id="backupsTable" class="datatables-backups table border-top">
                        <thead>
                            <tr>
                                <th><?php echo e(__('Backup Name')); ?></th>
                                <th><?php echo e(__('Type')); ?></th>
                                <th><?php echo e(__('Description')); ?></th>
                                <th><?php echo e(__('Size')); ?></th>
                                <th><?php echo e(__('Status')); ?></th>
                                <th><?php echo e(__('Created At')); ?></th>
                                <th class="text-center"><?php echo e(__('Actions')); ?></th>
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
                    <h5 class="modal-title">
                        <i class="ti ti-plus me-1 text-primary"></i>
                        <?php echo e(__('Create New Backup')); ?>

                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createBackupForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?php echo e(__('Backup Type')); ?></label>
                                <select name="backup_type" class="form-select" required>
                                    <option value="database_only" selected><?php echo e(__('Database Only (قاعدة البيانات فقط)')); ?></option>
                                    <option value="full"><?php echo e(__('Full Backup (Database + Files) (شامل قاعدة البيانات والملفات)')); ?></option>
                                    <option value="files_only"><?php echo e(__('Files Only (ملفات التخزين فقط)')); ?></option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?php echo e(__('Description (Optional)')); ?></label>
                                <input type="text" name="description" class="form-control"
                                    placeholder="<?php echo e(__('وصف أو ملاحظة حول النسخة')); ?>">
                            </div>

                            <!-- Destinations -->
                            <div class="col-12 mt-3">
                                <label class="form-label fw-bold"><?php echo e(__('وجهات حفظ وإرسال النسخة')); ?></label>
                                <div class="d-flex gap-4 flex-wrap border rounded p-3 bg-light">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="destinations[]" value="local" id="destLocal" checked>
                                        <label class="form-check-label" for="destLocal">
                                            <i class="ti ti-device-floppy text-primary me-1"></i>
                                            حفظ محلياً بالسيرفر
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="destinations[]" value="telegram" id="destTelegram">
                                        <label class="form-check-label" for="destTelegram">
                                            <i class="ti ti-brand-telegram text-info me-1"></i>
                                            إرسال فوري إلى تليجرام
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="destinations[]" value="email" id="destEmail">
                                        <label class="form-check-label" for="destEmail">
                                            <i class="ti ti-mail text-danger me-1"></i>
                                            إرسال فوري إلى البريد الإلكتروني
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Encryption -->
                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" id="is_encrypted" name="is_encrypted" value="1">
                                    <label class="form-check-label fw-bold" for="is_encrypted">
                                        <i class="ti ti-lock me-1"></i>
                                        تشفير النسخة الاحتياطية (AES-256)
                                    </label>
                                </div>
                                <div class="mt-2" id="passwordInputContainer" style="display: none;">
                                    <input type="password" name="password" class="form-control" placeholder="أدخل كلمة مرور للتشفير (اختياري - سيتم توليد كلمة مرور آمنة تلقائياً)">
                                    <small class="text-muted">إذا تركتها فارغة سيقوم النظام بتوليد وحفظ كلمة مرور معقدة مشفرة تلقائياً.</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3 mb-0">
                            <i class="ti ti-info-circle me-2"></i>
                            <strong><?php echo e(__('Note:')); ?></strong>
                            <?php echo e(__('The backup creation process may take several minutes depending on the data size.')); ?>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>
                            <?php echo e(__('Create Backup')); ?>

                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cloud & Automated Backup Settings Modal -->
    <div class="modal fade" id="cloudSettingsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-cloud-computing text-warning me-1"></i>
                        <?php echo e(__('إعدادات النسخ الاحتياطي التلقائي والسحابي')); ?>

                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="cloudBackupSettingsForm">
                    <div class="modal-body">
                        <!-- Nav Tabs -->
                        <ul class="nav nav-tabs nav-fill mb-3" role="tablist">
                            <li class="nav-item">
                                <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-auto-schedule">
                                    <i class="ti ti-calendar-time me-1"></i>
                                    الجدولة التلقائية
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-telegram">
                                    <i class="ti ti-brand-telegram me-1"></i>
                                    تليجرام (Telegram)
                                </button>
                            </li>
                            <li class="nav-item">
                                <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-email">
                                    <i class="ti ti-mail me-1"></i>
                                    البريد الإلكتروني
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content pt-2">
                            <!-- Tab 1: Auto Schedule -->
                            <div class="tab-pane fade show active" id="tab-auto-schedule" role="tabpanel">
                                <div class="form-check form-switch mb-3 p-2 bg-label-warning rounded">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" id="cfg_backup_auto_enabled" name="backup_auto_enabled" value="1">
                                    <label class="form-check-label fw-bold text-dark" for="cfg_backup_auto_enabled">
                                        تفعيل النسخ الاحتياطي التلقائي المجدول (Cron Scheduler)
                                    </label>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">تكرار النسخ</label>
                                        <select class="form-select" name="backup_schedule_frequency" id="cfg_backup_schedule_frequency">
                                            <option value="daily">يومياً (Daily)</option>
                                            <option value="weekly">أسبوعياً (Weekly)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">وقت التنفيذ اليومي</label>
                                        <input type="time" class="form-control" name="backup_schedule_time" id="cfg_backup_schedule_time" value="02:00">
                                        <small class="text-muted">الوقت بتوقيت الخادم (يفضل أثناء فترات هدوء النشاط مثل 02:00 ص)</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">أقصى عدد نسخ للاحتفاظ بها بالسيرفر (Retention)</label>
                                        <input type="number" class="form-control" name="backup_max_retention_count" id="cfg_backup_max_retention_count" min="1" max="100" value="15">
                                        <small class="text-muted">يقوم النظام بحذف وتدوير النسخ القديمة الزائدة تلقائياً لتوفير المساحة.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">وجهات الإرسال التلقائي</label>
                                        <input type="text" class="form-control" name="backup_auto_destinations" id="cfg_backup_auto_destinations" value="local,telegram" placeholder="local,telegram,email">
                                        <small class="text-muted">قيم مفصولة بفاصلة: <code>local</code>, <code>telegram</code>, <code>email</code></small>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="cfg_backup_auto_encrypt" name="backup_auto_encrypt" value="1">
                                            <label class="form-check-label" for="cfg_backup_auto_encrypt">
                                                تشفير النسخ التلقائية تلقائياً (AES-256)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab 2: Telegram -->
                            <div class="tab-pane fade" id="tab-telegram" role="tabpanel">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="cfg_telegram_backup_enabled" name="telegram_backup_enabled" value="1">
                                    <label class="form-check-label fw-bold" for="cfg_telegram_backup_enabled">
                                        تفعيل وجهة تليجرام السحابية
                                    </label>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">رمز البوت (Telegram Bot Token)</label>
                                        <input type="text" class="form-control" name="backup_telegram_bot_token" id="cfg_backup_telegram_bot_token" placeholder="مثال: 123456789:ABCdefGhIJKlmNoPQRstuvWXyz">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">معرف الشات / القناة (Telegram Chat ID)</label>
                                        <input type="text" class="form-control" name="backup_telegram_chat_id" id="cfg_backup_telegram_chat_id" placeholder="مثال: 123456789 أو للقناة -100123456789">
                                    </div>
                                    <div class="col-12">
                                        <button type="button" class="btn btn-outline-info" id="btnTestTelegram">
                                            <i class="ti ti-brand-telegram me-1"></i>
                                            اختبار اتصال روبوت تليجرام
                                        </button>
                                    </div>
                                </div>

                                <div class="alert alert-info mt-3 mb-0">
                                    <h6 class="alert-heading fw-bold mb-1"><i class="ti ti-help me-1"></i> كيفية الإعداد:</h6>
                                    <ol class="ps-3 mb-0">
                                        <li>تحدث مع <code>@BotFather</code> في تليجرام وأنشئ بوتاً جديداً عبر <code>/newbot</code> وانسخ الـ Token.</li>
                                        <li>أرسل رسالة للبوت، ثم احصل على Chat ID الخاص بك عبر <code>@userinfobot</code> أو أضف البوت لمجموعة وامنحه صلاحية إرسال الرسائل.</li>
                                    </ol>
                                </div>
                            </div>

                            <!-- Tab 3: Email -->
                            <div class="tab-pane fade" id="tab-email" role="tabpanel">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="cfg_email_backup_enabled" name="email_backup_enabled" value="1">
                                    <label class="form-check-label fw-bold" for="cfg_email_backup_enabled">
                                        تفعيل وجهة البريد الإلكتروني
                                    </label>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">البريد الإلكتروني للمستلم(ين)</label>
                                        <input type="text" class="form-control" name="backup_email_recipient" id="cfg_backup_email_recipient" placeholder="admin@safedest.com, backup@safedest.com">
                                        <small class="text-muted">يمكن إدخال أكثر من بريد إلكتروني مفصولين بفاصلة.</small>
                                    </div>
                                    <div class="col-12">
                                        <button type="button" class="btn btn-outline-danger" id="btnTestEmail">
                                            <i class="ti ti-mail-fast me-1"></i>
                                            اختبار إرسال بريد تجريبي
                                        </button>
                                    </div>
                                </div>

                                <div class="alert alert-secondary mt-3 mb-0">
                                    <i class="ti ti-info-circle me-1"></i>
                                    يتم استخدام إعدادات خادم SMTP المعتمدة في النظام لإرسال النسخ ومرفقات الـ ZIP تلقائياً.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="ti ti-check me-1"></i>
                            حفظ الإعدادات
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
                    <h5 class="modal-title">
                        <i class="ti ti-restore text-warning me-1"></i>
                        <?php echo e(__('Restore Backup')); ?>

                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="restoreBackupForm">
                    <div class="modal-body">
                        <input type="hidden" name="backup_name" id="restoreBackupName">

                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <strong><?php echo e(__('Warning:')); ?></strong>
                            <?php echo e(__('The restore process will replace current data. The system automatically creates a safety rollback backup before restoring.')); ?>

                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label"><?php echo e(__('Restore Type')); ?></label>
                                <select name="restore_type" class="form-select" required>
                                    <option value="database_only" selected><?php echo e(__('Database Only')); ?></option>
                                    <option value="full"><?php echo e(__('Full Restore (Database + Files)')); ?></option>
                                    <option value="files_only"><?php echo e(__('Files Only')); ?></option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">كلمة مرور فك التشفير (إذا كانت النسخة مشفرة)</label>
                                <input type="password" name="password" class="form-control" placeholder="اتركها فارغة إذا كانت النسخة غير مشفرة أو محفوظة بالنظام">
                            </div>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="confirmRestore" required>
                            <label class="form-check-label text-danger fw-bold" for="confirmRestore">
                                <?php echo e(__('I confirm that I understand this process will replace current data')); ?>

                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                        <button type="submit" class="btn btn-warning">
                            <i class="ti ti-restore me-1"></i>
                            <?php echo e(__('Restore Backup')); ?>

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
                    <h5 class="modal-title"><?php echo e(__('Backup Statistics')); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="statisticsContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden"><?php echo e(__('Loading...')); ?></span>
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
                    <h5 class="modal-title">
                        <i class="ti ti-upload text-success me-1"></i>
                        <?php echo e(__('Restore Backup from Uploaded File')); ?>

                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="uploadRestoreForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label"><?php echo e(__('Backup File (ZIP)')); ?></label>
                            <input type="file" class="form-control" name="backup_file" accept=".zip" required>
                            <small class="text-muted"><?php echo e(__('Maximum size: 1 GB')); ?></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo e(__('Backup Password (إذا كانت مشفرة)')); ?></label>
                            <input type="password" class="form-control" name="backup_password" placeholder="كلمة المرور إن وجدت">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?php echo e(__('Restore Type')); ?></label>
                            <select class="form-select" name="restore_type" required>
                                <option value="database_only" selected><?php echo e(__('Database Only')); ?></option>
                                <option value="full"><?php echo e(__('Full Restore')); ?></option>
                                <option value="files_only"><?php echo e(__('Files Only')); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                        <button type="submit" class="btn btn-primary"><?php echo e(__('Restore Backup')); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/admin/settings/backup.js', 'resources/js/ajax.js']); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/settings/backup/index.blade.php ENDPATH**/ ?>