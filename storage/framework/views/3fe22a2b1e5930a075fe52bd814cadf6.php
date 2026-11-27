<?php $__env->startSection('title', __('Task Funding') . ' - ' . $investor->name); ?>

<?php $__env->startSection('vendor-style'); ?>
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
<?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
  <div class="container-xxl flex-grow-1 container-p-y">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold mb-1"><i class="ti ti-credit-card me-2 text-primary"></i><?php echo e(__('Tasks Available for Funding')); ?></h4>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb breadcrumb-style1 mb-0">
            <li class="breadcrumb-item"><a href="<?php echo e(route('admin.user-wallets.show', $investor->id)); ?>"><?php echo e(__('Investor Wallet')); ?></a></li>
            <li class="breadcrumb-item active"><?php echo e(__('Task Funding')); ?></li>
          </ol>
        </nav>
      </div>
      <div class="col-auto">
        <div class="card card-border-shadow-warning p-2">
          <div class="d-flex align-items-center">
            <div class="avatar avatar-sm me-2">
              <span class="avatar-initial rounded bg-label-warning"><i class="ti ti-wallet"></i></span>
            </div>
            <div>
              <small class="d-block text-muted"><?php echo e(__('Available Investment Balance Label')); ?></small>
              <h5 class="mb-0 fw-bold text-warning"><?php echo e(number_format($walletBalance, 2)); ?> <small><?php echo e(__('SAR')); ?></small></h5>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php $__currentLoopData = ['success', 'error']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php if(session($msg)): ?>
        <div class="alert alert-<?php echo e($msg === 'error' ? 'danger' : $msg); ?> alert-dismissible mb-4" role="alert">
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          <?php echo e(session($msg)); ?>

        </div>
      <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    
    <div class="row g-4 mb-4">
      <div class="col-lg-8">
        <div class="card bg-label-primary border-0 h-100">
          <div class="card-body py-3 d-flex align-items-center">
            <div class="avatar avatar-md me-3">
              <span class="avatar-initial rounded-circle bg-primary"><i class="ti ti-user"></i></span>
            </div>
            <div>
              <h6 class="mb-1 text-primary"><?php echo e(__('Investor')); ?>: <strong><?php echo e($investor->name); ?></strong> (ID: <?php echo e($investor->id); ?>)</h6>
              <p class="mb-0 small">
                <?php echo e(__('Your commission')); ?>:
                <strong><?php echo e($contract->commission_value); ?><?php echo e($contract->commission_type === 'percentage' ? '%' : ' ' . __('SAR')); ?></strong>
                <?php if($contract->min_commission_threshold): ?>
                  &nbsp;|&nbsp; <?php echo e(__('Minimum platform commission')); ?>:
                  <strong><?php echo e(number_format($contract->min_commission_threshold, 2)); ?> <?php echo e(__('SAR')); ?></strong>
                <?php endif; ?>
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card h-100 shadow-none border">
          <div class="card-body py-2 d-flex align-items-center">
            <form method="GET" class="d-flex align-items-center h-100 flex-grow-1">
              <div class="input-group input-group-merge border-0">
                <span class="input-group-text border-0 ps-0"><i class="ti ti-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-0 shadow-none"
                  placeholder="<?php echo e(__('Search by task number')); ?>" value="<?php echo e(request('search')); ?>">
                <button type="submit" class="btn btn-sm btn-primary rounded ms-2 px-3"><?php echo e(__('Search')); ?></button>
              </div>
            </form>
            <button class="btn btn-sm btn-icon btn-label-secondary ms-2 flex-shrink-0 d-none" id="restoreHiddenTasksBtn" title="<?php echo e(__('Restore Hidden Tasks')); ?>" data-bs-toggle="tooltip">
              <i class="ti ti-reload"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    
    <div class="row g-4">
      <?php $__empty_1 = true; $__currentLoopData = $tasks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="col-md-6 col-xl-4 task-card-wrapper" data-task-id="<?php echo e($task->id); ?>">
          <div class="card h-100 card-action shadow-sm border-0 position-relative">
            <button type="button" class="btn btn-sm btn-icon btn-text-secondary hide-task-btn position-absolute top-0 end-0 m-2" data-task-id="<?php echo e($task->id); ?>" title="<?php echo e(__('Hide Task')); ?>" data-bs-toggle="tooltip" style="z-index: 10;">
                <i class="ti ti-x text-muted fs-5"></i>
            </button>
            <div class="card-header pb-2 mt-2">
              <div class="d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center">
                  <div class="avatar avatar-sm me-2">
                    <span class="avatar-initial rounded bg-label-info"><i class="ti ti-truck"></i></span>
                  </div>
                  <div>
                    <h6 class="mb-0"><?php echo e(__('Task #')); ?><?php echo e($task->id); ?></h6>
                    <small class="text-muted"><?php echo e($task->created_at->format('M d, Y')); ?></small>
                  </div>
                </div>
                <div class="d-flex flex-column align-items-end gap-1">
                  <span class="badge bg-label-primary rounded-pill"><?php echo e(number_format($task->total_price, 2)); ?> <?php echo e(__('SAR')); ?></span>
                  <span class="badge bg-label-info rounded-pill"><?php echo e(__('Status')); ?>: <?php echo e(__($task->status)); ?></span>
                  <span class="badge bg-label-success rounded-pill"><?php echo e(__('Payment Status')); ?>: <?php echo e(__($task->payment_status)); ?></span>
                </div>
              </div>

            </div>
            <div class="card-body">
              <div>
                <span class="badge bg-label-info mb-3  rounded-pill"><?php echo e(__('Vehicle Size')); ?>: <?php echo e($task->vehicle_size?->VehicleName ?? __('Not specified')); ?>

                </span>
              </div>
              
              <div class="trip-path mb-3 p-2 bg-light rounded">
                <div class="d-flex align-items-center mb-2">
                  <i class="ti ti-circle-filled text-primary me-2" style="font-size: 10px;"></i>
                  <span class="text-truncate small fw-medium text-dark"><?php echo e(__('from')); ?> :
                    <?php echo e($task->pickup->address ?? __('Pickup point')); ?></span>
                </div>
                <div class="ms-1 ps-2 border-start border-primary border-2 mb-2" style="height: 15px;"></div>
                <div class="d-flex align-items-center">
                  <i class="ti ti-map-pin-filled text-danger me-2" style="font-size: 10px;"></i>
                  <span class="text-truncate small fw-medium text-dark"><?php echo e(__('to')); ?>

                    :<?php echo e($task->delivery->address ?? __('Delivery point')); ?></span>
                </div>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-6">
                  <div class="border rounded p-2 text-center">
                    <small class="text-muted d-block mb-1"><?php echo e(__('Customer')); ?></small>
                    <span class="fw-semibold small text-truncate d-block"><?php echo e($task->customer?->name ?? '—'); ?></span>
                  </div>
                </div>
                <div class="col-6">
                  <?php
                    $calc = $task->calculateInvestorCommission($contract);
                    $myComm = $calc['net_investor_commission'];
                    $tooltipParts = ['<strong>تفاصيل الحسبة:</strong>'];
                    $tooltipParts[] = 'عمولة المنصة: ' . number_format($calc['platform_gross'], 2) . ' ر.س';
                    if ($calc['user_commissions_total'] > 0) {
                        $tooltipParts[] = 'عمولات المستخدمين: -' . number_format($calc['user_commissions_total'], 2) . ' ر.س';
                    }
                    if ($calc['task_brokers_total'] > 0) {
                        $tooltipParts[] = 'عمولات وسطاء المهمة: -' . number_format($calc['task_brokers_total'], 2) . ' ر.س';
                    }
                    if ($calc['contract_task_brokers_total'] > 0) {
                        $tooltipParts[] = 'عمولة وسيط العقد: -' . number_format($calc['contract_task_brokers_total'], 2) . ' ر.س';
                    }
                    $tooltipParts[] = 'صافي العمولة الأساسية: ' . number_format($calc['net_task_commission'], 2) . ' ر.س';
                    if ($calc['contract_investor_brokers_total'] > 0) {
                        $tooltipParts[] = 'خصم وسيط المستثمر: -' . number_format($calc['contract_investor_brokers_total'], 2) . ' ر.س';
                    }
                    $tooltipTitle = implode('<br>', $tooltipParts);
                  ?>
                  <div class="border rounded p-2 text-center bg-label-success position-relative"
                       <?php if($calc['has_deductions']): ?>
                         data-bs-toggle="tooltip"
                         data-bs-html="true"
                         data-bs-placement="top"
                         title="<?php echo $tooltipTitle; ?>"
                       <?php endif; ?>>
                    <small class="text-success d-block mb-1"><?php echo e(__('Your Expected Commission')); ?></small>
                    <span class="fw-bold text-success fs-5"><?php echo e(number_format($myComm, 2)); ?> <small><?php echo e(__('SAR')); ?></small></span>
                    <?php if($calc['has_deductions']): ?>
                      <div class="mt-1">
                        <span class="badge bg-white text-dark border shadow-xs py-1 px-2" style="font-size: 0.68rem; cursor: help;" title="تم خصم عمولات المستخدمين والوسطاء من عمولة المهمة أولاً">
                          <i class="ti ti-info-circle text-warning me-1" style="font-size: 0.78rem;"></i>بعد خصم الشركاء (<?php echo e(number_format($calc['total_deductions_from_task'] + $calc['contract_investor_brokers_total'], 2)); ?> ر.س)
                        </span>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>



            </div>
            <div class="card-footer pt-0">
              <hr class="mt-0">
              <?php if($walletBalance >= $task->total_price): ?>
                <button type="button" class="btn btn-primary w-100 shadow-sm fund-task-btn"
                  data-bs-toggle="modal" 
                  data-bs-target="#fundTaskModal"
                  data-task-id="<?php echo e($task->id); ?>"
                  data-total-price="<?php echo e(number_format($task->total_price, 2)); ?>"
                  data-expected-commission="<?php echo e(number_format($myComm, 2)); ?>"
                  data-deductions="<?php echo e($calc['has_deductions'] ? number_format($calc['total_deductions_from_task'] + $calc['contract_investor_brokers_total'], 2) : '0.00'); ?>"
                  data-url="<?php echo e(route('admin.user-wallets.pay-task', ['userId' => $investor->id, 'task' => $task->id])); ?>">
                  <i class="ti ti-credit-card me-1"></i> <?php echo e(__('Pay Task Value')); ?>

                </button>
              <?php else: ?>
                <button class="btn btn-label-danger w-100" disabled>
                  <i class="ti ti-alert-triangle me-1"></i> <?php echo e(__('Insufficient balance')); ?>

                </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="col-12">
          <div class="card shadow-none border-2 border-dashed text-center py-5">
            <div class="card-body">
              <img src="<?php echo e(asset('assets/img/illustrations/page-misc-under-maintenance.png')); ?>" alt="No tasks" width="150"
                class="mb-4">
              <h5 class="text-muted"><?php echo e(__('No tasks available for funding now')); ?></h5>
              <p class="text-muted px-md-5"><?php echo e(__('Tasks matching contract will appear')); ?></p>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    
    <?php if($tasks->hasPages()): ?>
      <div class="card mt-3">
        <div class="card-body px-4 py-2">
            <?php echo e($tasks->appends(request()->input())->links('investor.partials.pagination')); ?>

        </div>
      </div>
    <?php endif; ?>

  </div>

  <!-- Modal تأكيد التمويل -->
  <div class="modal fade" id="fundTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-bottom">
          <h5 class="modal-title d-flex align-items-center">
            <i class="ti ti-shield-check text-primary me-2 ti-md"></i>
            تأكيد تمويل المهمة <span id="displayTaskId" class="ms-1 fw-bold"></span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="fundTaskForm" method="POST">
          <?php echo csrf_field(); ?>
          <div class="modal-body">
            <!-- شروط التمويل -->
            <div class="alert alert-warning d-flex align-items-start mb-0">
              <i class="ti ti-alert-circle me-2 mt-1"></i>
              <div>
                <h6 class="alert-heading mb-1 fw-bold"><?php echo e(__('Funding terms and conditions')); ?></h6>
                <ul class="mb-0 ps-3 small">
                  <li>سيتم خصم مبلغ <strong id="displayTotalPrice"></strong> <?php echo e(__('SAR')); ?> من محفظة المستثمر <strong><?php echo e($investor->name); ?></strong> الاستثمارية.</li>
                  <li id="displayCommissionRow">العمولة المقدرة للمستثمر: <strong id="displayExpectedComm" class="text-success"></strong> <?php echo e(__('SAR')); ?> <span id="displayDeductionsBadge" class="badge bg-warning text-dark ms-1 d-none" style="font-size: 0.7rem;">بعد خصم عمولات الشركاء</span></li>
                  <li><?php echo e(__('Funding cannot be reversed')); ?></li>
                  <li><?php echo e(__('Expected profits recorded on task completion')); ?></li>
                </ul>
              </div>
            </div>
          </div>
          <div class="modal-footer border-top">
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
            <button type="submit" class="btn btn-primary btn-submit">
              <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
              <?php echo e(__('Confirm Payment')); ?>

            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const fundTaskModal = document.getElementById('fundTaskModal');
    if (fundTaskModal) {
      fundTaskModal.addEventListener('show.bs.modal', function (event) {
        // الزر الذي فتح المودال
        const button = event.relatedTarget;
        
        // استخراج البيانات من سمات الزر
        const taskId = button.getAttribute('data-task-id');
        const totalPrice = button.getAttribute('data-total-price');
        const expComm = button.getAttribute('data-expected-commission');
        const deductions = parseFloat(button.getAttribute('data-deductions') || 0);
        const actionUrl = button.getAttribute('data-url');

        // تحديث محتوى المودال
        const displayTaskId = fundTaskModal.querySelector('#displayTaskId');
        const displayTotalPrice = fundTaskModal.querySelector('#displayTotalPrice');
        const displayExpectedComm = fundTaskModal.querySelector('#displayExpectedComm');
        const displayDeductionsBadge = fundTaskModal.querySelector('#displayDeductionsBadge');
        const form = fundTaskModal.querySelector('#fundTaskForm');

        if (displayTaskId) displayTaskId.textContent = '#' + taskId;
        if (displayTotalPrice) displayTotalPrice.textContent = totalPrice;
        if (displayExpectedComm) displayExpectedComm.textContent = expComm;
        if (displayDeductionsBadge) {
          if (deductions > 0) {
            displayDeductionsBadge.classList.remove('d-none');
          } else {
            displayDeductionsBadge.classList.add('d-none');
          }
        }
        if (form) form.setAttribute('action', actionUrl);
      });
    }

    // معالجة حالة التحميل عند الإرسال
    const fundTaskForm = document.getElementById('fundTaskForm');
    if (fundTaskForm) {
      fundTaskForm.addEventListener('submit', function() {
        const submitBtn = this.querySelector('.btn-submit');
        if (submitBtn) {
          submitBtn.disabled = true;
          const spinner = submitBtn.querySelector('.spinner-border');
          if (spinner) spinner.classList.remove('d-none');
        }
      });
    }

    // إخفاء المهام بناءً على LocalStorage
    const investorId = <?php echo e($investor->id); ?>;
    const storageKey = `hidden_funding_tasks_${investorId}`;
    
    // جلب المهام المخفية من الذاكرة
    let hiddenTasks = JSON.parse(localStorage.getItem(storageKey)) || [];
    
    const restoreBtn = document.getElementById('restoreHiddenTasksBtn');
    if (hiddenTasks.length > 0 && restoreBtn) {
      restoreBtn.classList.remove('d-none');
    }

    if (restoreBtn) {
      restoreBtn.addEventListener('click', function(e) {
        e.preventDefault();
        Swal.fire({
          title: '<?php echo e(__("Are you sure?")); ?>',
          text: '<?php echo e(__("Do you want to restore all hidden tasks?")); ?>',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: '<?php echo e(__("Yes, restore them")); ?>',
          cancelButtonText: '<?php echo e(__("Cancel")); ?>',
          customClass: {
            confirmButton: 'btn btn-primary me-3',
            cancelButton: 'btn btn-label-secondary'
          },
          buttonsStyling: false
        }).then(function (result) {
          if (result.isConfirmed) {
            localStorage.removeItem(storageKey);
            window.location.reload();
          }
        });
      });
    }

    // إخفاء المهام فور تحميل الصفحة
    document.querySelectorAll('.task-card-wrapper').forEach(card => {
      const taskId = card.getAttribute('data-task-id');
      if (hiddenTasks.includes(taskId)) {
        card.style.display = 'none';
        card.classList.add('d-none'); // For extra safety against layout issues
      }
    });

    // معالجة ضغطة زر الإخفاء (X)
    document.querySelectorAll('.hide-task-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // لمنع تفعيل أي أحداث أخرى في البطاقة
        
        const taskId = this.getAttribute('data-task-id');
        const cardWrapper = document.querySelector(`.task-card-wrapper[data-task-id="${taskId}"]`);
        
        Swal.fire({
          title: '<?php echo e(__("Are you sure?")); ?>',
          text: '<?php echo e(__("Do you really want to hide this task?")); ?>',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: '<?php echo e(__("Yes, hide it!")); ?>',
          cancelButtonText: '<?php echo e(__("Cancel")); ?>',
          customClass: {
            confirmButton: 'btn btn-primary me-3',
            cancelButton: 'btn btn-label-secondary'
          },
          buttonsStyling: false
        }).then(function (result) {
          if (result.isConfirmed) {
            if (!hiddenTasks.includes(taskId)) {
              hiddenTasks.push(taskId);
              localStorage.setItem(storageKey, JSON.stringify(hiddenTasks));
              if (restoreBtn) restoreBtn.classList.remove('d-none');
            }

            // إخفاء البطاقة بتأثير حركي بسيط
            if (cardWrapper) {
              cardWrapper.style.transition = "opacity 0.3s ease, transform 0.3s ease";
              cardWrapper.style.opacity = "0";
              cardWrapper.style.transform = "scale(0.9)";
              setTimeout(() => {
                cardWrapper.style.display = 'none';
                cardWrapper.classList.add('d-none');
              }, 300);
            }
          }
        });
      });
    });

    // تفعيل الـ Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });

  });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/user-wallets/tasks-funding.blade.php ENDPATH**/ ?>