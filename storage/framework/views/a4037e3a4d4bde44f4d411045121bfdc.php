<?php $__env->startSection('title', 'سجل حركات النظام'); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
    ]); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')([
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/flatpickr/flatpickr.js'
    ]); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">الإدارة /</span> سجل حركات النظام (Activity Logs)
</h4>

<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span>إجمالي الحركات</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2"><?php echo e($totalLogs); ?></h3>
            </div>
            <p class="mb-0">في قاعدة البيانات</p>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="ti ti-activity ti-sm"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-header border-bottom">
    <h5 class="card-title mb-0">فلاتر البحث</h5>
  </div>
  <div class="card-body py-3">
    <div class="row g-3">
      <!-- User Filter -->
      <div class="col-12 col-md-2">
        <label class="form-label" for="filter-user">المستخدم</label>
        <select id="filter-user" class="select2 form-select" data-allow-clear="true">
          <option value="">جميع المستخدمين</option>
          <?php $__currentLoopData = \App\Models\User::all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($u->id); ?>"><?php echo e($u->name); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>

      <!-- Action Type Filter -->
      <div class="col-12 col-md-2">
        <label class="form-label" for="filter-action">نوع الحركة</label>
        <select id="filter-action" class="select2 form-select" data-allow-clear="true">
          <option value="">جميع الحركات</option>
          <option value="إنشاء">إنشاء</option>
          <option value="تحديث">تحديث</option>
          <option value="حذف">حذف</option>
          <option value="تعديل إجباري">تعديل إجباري</option>
        </select>
      </div>

      <!-- Table Filter -->
      <div class="col-12 col-md-2">
        <label class="form-label" for="filter-table">الجدول المتأثر</label>
        <select id="filter-table" class="select2 form-select" data-allow-clear="true">
          <option value="">جميع الجداول</option>
          <!-- Using distinct table names from ActivityLog -->
          <?php $__currentLoopData = \App\Models\ActivityLog::select('table_name')->distinct()->pluck('table_name'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $table): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($table); ?>"><?php echo e($table); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>

      <!-- Date Filter -->
      <div class="col-12 col-md-2">
        <label class="form-label" for="filter-date">التاريخ</label>
        <input type="text" id="filter-date" class="form-control flatpickr-date" placeholder="اختر نطاق التاريخ" />
      </div>

      <!-- General Search -->
      <div class="col-12 col-md-3">
        <label class="form-label" for="filter-search">بحث عام</label>
        <input type="text" id="filter-search" class="form-control" placeholder="بحث في السجلات..." />
      </div>

      <!-- Submit Button -->
      <div class="col-12 col-md-1 d-flex align-items-end">
        <button id="btn-filter" class="btn btn-primary w-100" data-bs-toggle="tooltip" data-bs-placement="top" title="تصفية النتائج">
          <i class="ti ti-filter"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-datatable table-responsive">
    <table class="datatables-activity-logs table">
      <thead class="border-top">
        <tr>
          <th>ID</th>
          <th>المستخدم</th>
          <th>الإجراء</th>
          <th>الجدول</th>
          <th>عنوان IP</th>
          <th>التاريخ والوقت</th>
          <th>تفاصيل</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Modal لعرض التفاصيل -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">تفاصيل الحركة</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="logDetailsBody">
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إغلاق</button>
      </div>
    </div>
  </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script type="module">
$(function () {
  // Initialize Select2 & Flatpickr
  if ($('.select2').length) {
    $('.select2').select2();
  }
  if ($('.flatpickr-date').length) {
    $('.flatpickr-date').flatpickr({
      mode: 'range',
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'Y-m-d'
    });
  }

  var dt_logs_table = $('.datatables-activity-logs');

  if (dt_logs_table.length) {
    var dt_logs = dt_logs_table.DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: "<?php echo e(route('admin.activity_logs.data')); ?>",
        type: 'GET',
        data: function (d) {
          d.user_id = $('#filter-user').val();
          d.date = $('#filter-date').val();
          d.action_type = $('#filter-action').val();
          d.table_name = $('#filter-table').val();
          d.general_search = $('#filter-search').val();
        }
      },
      columns: [
        { data: 'id' },
        { data: 'user' },
        { data: 'action' },
        { data: 'table_name' },
        { data: 'ip_address' },
        { data: 'created_at' },
        { data: 'actions', orderable: false, searchable: false }
      ],
      order: [[0, 'desc']],
      dom:
        '<"row me-2"' +
        '<"col-md-2"<"me-3"l>>' +
        '<"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0">>' +
        '>t' +
        '<"row mx-2"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: '_MENU_'
      }
    });
  }

  $('#btn-filter').on('click', function() {
    dt_logs.ajax.reload();
  });

  // عرض تفاصيل الحركة
  $(document).on('click', '.view-record', function() {
      var logId = $(this).data('id');
      $('#logDetailsModal').modal('show');
      $('#logDetailsBody').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
      
      $.ajax({
          url: "<?php echo e(url('admin/activity-logs/show')); ?>/" + logId,
          type: 'GET',
          success: function(response) {
              $('#logDetailsBody').html(response.html);
          },
          error: function() {
              $('#logDetailsBody').html('<div class="alert alert-danger">حدث خطأ أثناء جلب التفاصيل.</div>');
          }
      });
  });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/activity-logs/index.blade.php ENDPATH**/ ?>