<?php $__env->startSection('title', __('Funded Tasks History')); ?>

<?php $__env->startSection('content'); ?>
<div class="container-xxl flex-grow-1 container-p-y">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-list-check me-2 text-primary"></i><?php echo e(__('Funded Tasks History')); ?></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('investor.dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
                    <li class="breadcrumb-item active"><?php echo e(__('Paid Tasks')); ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="<?php echo e(route('investor.paid-tasks.export', request()->query())); ?>"
               class="btn btn-success d-flex align-items-center shadow-sm py-2 px-4">
                <i class="ti ti-file-spreadsheet me-2 ti-sm"></i>
                <span class="fw-bold"><?php echo e(__('Export Excel')); ?></span>
            </a>
        </div>
    </div>


    
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span><?php echo e(__('Total Fundings')); ?></span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2"><?php echo e(number_format($tasks->sum('total_price'), 2)); ?></h4>
                                <small class="text-muted"><?php echo e(__('SAR')); ?></small>
                            </div>
                            <small class="text-muted small"><?php echo e(__('For this page')); ?></small>
                        </div>
                        <span class="badge bg-label-danger rounded p-2">
                            <i class="ti ti-currency-dollar ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span><?php echo e(__('Total Earned Commissions')); ?></span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">
                                    <?php
                                        $totalComm = 0;
                                        foreach($tasks as $t) {
                                            $totalComm += $t->userWalletTransactions->first()?->amount ?? 0;
                                        }
                                    ?>
                                    <?php echo e(number_format($totalComm, 2)); ?>

                                </h4>
                                <small class="text-muted"><?php echo e(__('SAR')); ?></small>
                            </div>
                            <small class="text-muted small"><?php echo e(__('For this page')); ?></small>
                        </div>
                        <span class="badge bg-label-success rounded p-2">
                            <i class="ti ti-chart-pie-2 ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span><?php echo e(__('Total Funded Tasks')); ?></span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2"><?php echo e($tasks->total()); ?></h4>
                            </div>
                            <small class="text-muted small"><?php echo e(__('Total records')); ?></small>
                        </div>
                        <span class="badge bg-label-primary rounded p-2">
                            <i class="ti ti-list-check ti-sm"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><?php echo e(__('Search task or customer')); ?></label>
                    <div class="input-group input-group-merge">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="<?php echo e(__('Search')); ?>..." value="<?php echo e(request('search')); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo e(__('From date')); ?></label>
                    <input type="date" name="from" class="form-control" value="<?php echo e(request('from')); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo e(__('To date')); ?></label>
                    <input type="date" name="to" class="form-control" value="<?php echo e(request('to')); ?>">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i><?php echo e(__('Filter')); ?></button>
                    <a href="<?php echo e(route('investor.paid-tasks')); ?>" class="btn btn-label-secondary w-100"><?php echo e(__('Reset')); ?></a>
                </div>
            </form>
        </div>
    </div>

    
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-hover border-top">
                <thead class="table-light">
                    <tr>
                        <th><?php echo e(__('Task #')); ?></th>
                        <th><?php echo e(__('Funding date')); ?></th>
                        <th><?php echo e(__('Customer')); ?></th>
                        <th><?php echo e(__('Route (from - to)')); ?></th>
                        <th><?php echo e(__('Paid Amount')); ?></th>
                        <th><?php echo e(__('Earned Commission')); ?></th>
                        <th><?php echo e(__('Status')); ?></th>
                        <th class="text-center"><?php echo e(__('Actions')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $tasks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <span class="fw-bold text-primary">#<?php echo e($task->id); ?></span>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="text-nowrap"><?php echo e($task->updated_at->format('Y-m-d')); ?></span>
                                <small class="text-muted"><?php echo e($task->updated_at->format('H:i A')); ?></small>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-xs me-2">
                                    <span class="avatar-initial rounded-circle bg-label-info"><i class="ti ti-user ti-xs"></i></span>
                                </div>
                                <span class="text-truncate" style="max-width: 150px;"><?php echo e($task->customer?->name ?? '—'); ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center small">
                                <span class="text-truncate" title="<?php echo e($task->pickup->address ?? ''); ?>"><?php echo e($task->pickup->address ?? '—'); ?></span>
                                <i class="ti ti-arrow-narrow-left mx-1 text-muted"></i>
                                <span class="text-truncate" title="<?php echo e($task->delivery->address ?? ''); ?>"><?php echo e($task->delivery->address ?? '—'); ?></span>
                            </div>
                            <?php if($task->vehicle_size): ?>
                                <div class="mt-1">
                                    <span class="badge bg-label-secondary p-1 px-2 small" style="font-size: 10px;">
                                        <i class="ti ti-truck me-1"></i><?php echo e($task->vehicle_size->type->name ?? ''); ?> (<?php echo e($task->vehicle_size->name ?? ''); ?>)
                                    </span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="fw-semibold text-danger"><?php echo e(number_format($task->total_price, 2)); ?></span>
                            <small class="text-muted"><?php echo e(__('SAR')); ?></small>
                        </td>
                        <td>
                            <?php
                                $commissionTrans = $task->userWalletTransactions->first();
                            ?>
                            <?php if($commissionTrans): ?>
                                <span class="fw-bold text-success"><?php echo e(number_format($commissionTrans->amount, 2)); ?></span>
                                <small class="text-muted"><?php echo e(__('SAR')); ?></small>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($task->closed): ?>
                                <span class="badge bg-label-secondary"><?php echo e(__('Closed')); ?></span>
                            <?php else: ?>
                                <span class="badge bg-label-success"><?php echo e(__('Funded')); ?></span>
                            <?php endif; ?>
                            <div class="mt-1 small text-muted"><?php echo e($task->status); ?></div>
                        </td>
                        <td class="text-center">
                            <div class="d-inline-block text-nowrap">
                                <a href="<?php echo e(route('investor.paid-tasks.report', $task)); ?>" target="_blank" class="btn btn-sm btn-icon btn-label-secondary rounded-pill me-1" title="<?php echo e(__('Print report')); ?>">
                                    <i class="ti ti-printer"></i>
                                </a>
                                <button class="btn btn-sm btn-icon btn-label-primary rounded-pill" title="<?php echo e(__('View details coming soon')); ?>">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ti ti-archive ti-lg d-block mb-2"></i>
                                <?php echo e(__('No matching records')); ?>

                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <?php if($tasks->hasPages()): ?>
    <div class="card mt-3">
        <div class="card-body px-4 py-2">
            <?php echo e($tasks->appends(request()->input())->links('investor.partials.pagination')); ?>

        </div>
    </div>
    <?php endif; ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/investor/paid-tasks/index.blade.php ENDPATH**/ ?>