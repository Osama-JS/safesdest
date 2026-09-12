<?php $__env->startSection('title', __('Commission Wallet - Profits')); ?>

<?php $__env->startSection('vendor-style'); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/daterangepicker/daterangepicker.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-xxl flex-grow-1 container-p-y">

    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-cash me-2 text-success"></i><?php echo e(__('Commission Wallet')); ?></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-style1 mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo e(route('investor.dashboard')); ?>"><?php echo e(__('Home')); ?></a></li>
                    <li class="breadcrumb-item active"><?php echo e(__('Commission Wallet')); ?></li>
                </ol>
            </nav>
        </div>
        
        <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?php echo e(route('investor.personal-wallet.export', request()->query())); ?>" class="btn btn-success shadow-sm">
            <i class="ti ti-file-spreadsheet me-1"></i> <?php echo e(__('Export Excel')); ?>

        </a>
        <a href="<?php echo e(route('investor.commission-withdrawals')); ?>" class="btn btn-label-secondary shadow-sm">
            <i class="ti ti-history me-1"></i> طلبات السحب
        </a>
        <button type="button" class="btn btn-warning shadow-sm text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#withdrawCommissionModal">
            <i class="ti ti-cash-banknote me-1"></i> طلب سحب العمولة
        </button>
        <?php if(($personalWallet?->withdrawable_balance ?? 0) > 0): ?>
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#reinvestProfitsModal">
            <i class="ti ti-refresh me-1"></i> <?php echo e(__('Reinvest Profits')); ?>

        </button>
        <?php endif; ?>
        <?php if($contract && $contract->contract_type === 'general_investment' && $contract->isActive()): ?>
            <button type="button" class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#calculateCommissionsModal">
                <i class="ti ti-calculator me-1"></i> <?php echo e(__('Calculate Commissions Now')); ?>

            </button>
        <?php endif; ?>

        </div>
    </div>

    <?php $__currentLoopData = ['success','error','info']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(session($msg)): ?>
            <div class="alert alert-<?php echo e($msg === 'error' ? 'danger' : $msg); ?> alert-dismissible mb-4" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php echo e(session($msg)); ?>

            </div>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    
    <?php if($contract && $contract->contract_type === 'general_investment'): ?>
    <div class="card bg-label-info mb-4 border-0">
        <div class="card-body py-3">
            <div class="d-flex align-items-center">
                <div class="avatar avatar-sm me-3">
                    <span class="avatar-initial rounded-circle bg-info"><i class="ti ti-info-circle"></i></span>
                </div>
                <div>
                    <p class="mb-0 small fw-medium text-info"><?php echo e(__('Actual entitlement scope')); ?></p>
                    <p class="mb-0 small">
                        <?php echo e(__('You receive :value :type of platform net commission per eligible task.', ['value' => $contract->commission_value, 'type' => $contract->commission_type === 'percentage' ? '%' : __('Fixed Amount (SAR)')])); ?>

                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
    <?php if($contract && $contract->contract_type === 'task_investment'): ?>
    <div class="card mb-4 border-0" style="background: linear-gradient(135deg, #f0f7ff 0%, #e8f4e8 100%); border-right: 4px solid #696cff !important; border-right-width: 4px !important;">
        <div class="card-body py-3">
            <div class="d-flex align-items-start gap-3">
                <div class="avatar avatar-sm flex-shrink-0 mt-1">
                    <span class="avatar-initial rounded-circle" style="background: linear-gradient(135deg, #696cff, #7367f0); color:#fff;">
                        <i class="ti ti-shield-check"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <p class="mb-1 fw-bold" style="color:#696cff; font-size: 0.9rem;">
                        <i class="ti ti-calculator me-1"></i><?php echo e(__('How is withdrawable balance calculated?')); ?>

                    </p>
                    <p class="mb-2 small text-muted">
                        <?php echo e(__('As a task-based investor, your withdrawable balance is calculated as:')); ?>

                    </p>
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                        <span class="badge" style="background:#e8f4e8; color:#28a745; font-size:0.8rem; padding: 6px 10px;">
                            <i class="ti ti-check me-1"></i><?php echo e(__('Settled task commissions')); ?>

                        </span>
                        <span class="text-muted fw-bold">+</span>
                        <span class="badge" style="background:#fff3cd; color:#856404; font-size:0.8rem; padding: 6px 10px;">
                            <i class="ti ti-gift me-1"></i><?php echo e(__('Manual deposits (bonuses)')); ?>

                        </span>
                        <span class="text-muted fw-bold">−</span>
                        <span class="badge" style="background:#fde8e8; color:#dc3545; font-size:0.8rem; padding: 6px 10px;">
                            <i class="ti ti-arrow-up me-1"></i><?php echo e(__('Previous withdrawals')); ?>

                        </span>
                    </div>
                    <p class="mb-0 small" style="color:#555;">
                        <i class="ti ti-info-circle me-1 text-primary"></i>
                        <?php echo e(__('Task commission becomes withdrawable only after its amount is settled and capital is returned to your investment wallet.')); ?>

                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
    
    <div class="row g-4 mb-4">
        
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100" style="border-top: 3px solid #28a745; box-shadow: 0 4px 18px rgba(40,167,69,0.10);">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded" style="background: linear-gradient(135deg,#28a745,#20c997); color:#fff;">
                                <i class="ti ti-wallet ti-md"></i>
                            </span>
                        </div>
                        <h4 class="ms-1 mb-0 text-success"><?php echo e(number_format($personalWallet?->withdrawable_balance ?? 0, 2)); ?></h4>
                    </div>
                    <p class="mb-1 fw-bold"><?php echo e(__('Available for Withdrawal')); ?></p>
                    <p class="mb-2 small text-muted">
                        <span class="text-success me-1"><?php echo e(__('SAR')); ?></span>
                        <?php if($contract && $contract->contract_type === 'task_investment'): ?>
                            <?php echo e(__('Settled commissions + manual deposits − withdrawals')); ?>

                        <?php else: ?>
                            <?php echo e(__('Your net profit balance available')); ?>

                        <?php endif; ?>
                    </p>
                    <button type="button" class="btn btn-xs btn-label-success w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#withdrawCommissionModal">
                        <i class="ti ti-cash-banknote me-1"></i> طلب سحب العمولة
                    </button>
                </div>
            </div>
        </div>

        
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-trending-up ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0"><?php echo e(number_format($personalWallet?->credit ?? 0, 2)); ?></h4>
                    </div>
                    <p class="mb-1 fw-medium"><?php echo e(__('Total Earned Commissions')); ?></p>
                    <p class="mb-0 small text-muted">
                        <span class="text-primary me-1"><?php echo e(__('SAR')); ?></span>
                        <?php if($contract && $contract->contract_type === 'task_investment'): ?>
                            <span class="badge bg-label-warning" style="font-size:0.72rem;">
                                <i class="ti ti-clock-hour-4 me-1"></i><?php echo e(__('May include unsettled commissions')); ?>

                            </span>
                        <?php else: ?>
                            <?php echo e(__('Total credited to wallet')); ?>

                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-danger h-100" style="border-top: 3px solid #ea5455;">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-danger"><i class="ti ti-arrow-up-right ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0 text-danger"><?php echo e(number_format($personalWallet?->debit ?? 0, 2)); ?></h4>
                    </div>
                    <p class="mb-1 fw-medium"><?php echo e(__('Total Withdrawals / Reinvestments')); ?></p>
                    <p class="mb-0 small text-muted">
                        <span class="text-danger me-1"><?php echo e(__('SAR')); ?></span> <?php echo e(__('Total withdrawn & reinvested')); ?>

                    </p>
                </div>
            </div>
        </div>

        
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="ti ti-checklist ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0"><?php echo e($transactions->total() ?? 0); ?></h4>
                    </div>
                    <p class="mb-1 fw-medium"><?php echo e(__('Total operations')); ?></p>
                    <p class="mb-0 small text-muted">
                        <span class="text-info me-1"><?php echo e(__('Commission')); ?></span> <?php echo e(__('operations recorded')); ?>

                    </p>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><?php echo e(__('Task #')); ?></label>
                    <input type="text" name="search" class="form-control" value="<?php echo e(request('search')); ?>" placeholder="<?php echo e(__('Search')); ?>...">
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo e(__('Operation type')); ?></label>
                    <select name="type" class="form-select">
                        <option value=""><?php echo e(__('All')); ?></option>
                        <option value="credit" <?php echo e(request('type') === 'credit' ? 'selected' : ''); ?>><?php echo e(__('Commission / Deposit')); ?></option>
                        <option value="debit"  <?php echo e(request('type') === 'debit'  ? 'selected' : ''); ?>><?php echo e(__('Withdrawal / Reinvest')); ?></option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?php echo e(__('Date Range')); ?></label>
                    <input type="text" id="dateRange" class="form-control" placeholder="<?php echo e(__('Select Date Range')); ?>">
                    <input type="hidden" name="from" value="<?php echo e(request('from')); ?>">
                    <input type="hidden" name="to" value="<?php echo e(request('to')); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i><?php echo e(__('Filter')); ?></button>
                    <a href="<?php echo e(route('investor.personal-wallet')); ?>" class="btn btn-label-secondary w-100"><?php echo e(__('Reset')); ?></a>
                </div>
            </form>
        </div>
    </div>

    
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><?php echo e(__('Commission Wallet Transactions Log')); ?></h5>
            <span class="badge bg-label-secondary"><?php echo e(__('Total operations')); ?>: <?php echo e($transactions->total()); ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-muted small"><?php echo e(__('Operation type')); ?></th>
                        <th class="text-muted small"><?php echo e(__('Task')); ?></th>
                        <th class="text-muted small"><?php echo e(__('Amount')); ?></th>
                        <th class="text-muted small"><?php echo e(__('Description')); ?></th>
                        <th class="text-muted small"><?php echo e(__('Date and Time')); ?></th>
                        <th class="text-muted small"><?php echo e(__('Attachment')); ?></th>
                    </tr>
                </thead>
                <tbody class="table-border-bottom-0">
                    <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $isReinvest = $tx->transaction_type === 'debit' && str_contains($tx->description ?? '', __('Profit reinvestment from commission wallet'));
                    ?>
                    <tr>
                        <td>
                            <?php if($tx->transaction_type === 'credit'): ?>
                                <span class="badge bg-label-success"><i class="ti ti-plus ti-xs me-1"></i><?php echo e(__('Commission')); ?></span>
                            <?php elseif($isReinvest): ?>
                                <span class="badge bg-label-primary"><i class="ti ti-refresh ti-xs me-1"></i><?php echo e(__('Reinvestment')); ?></span>
                            <?php else: ?>
                                <span class="badge bg-label-danger"><i class="ti ti-minus ti-xs me-1"></i><?php echo e(__('Withdrawal')); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($tx->task_id): ?>
                                <span class="badge bg-label-primary">#<?php echo e($tx->task_id); ?></span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="fw-bold <?php echo e($tx->transaction_type === 'credit' ? 'text-success' : 'text-danger'); ?>">
                                <?php echo e($tx->transaction_type === 'credit' ? '+' : '−'); ?><?php echo e(number_format($tx->amount, 2)); ?> <?php echo e(__('SAR')); ?>

                            </span>
                        </td>
                        <td class="text-truncate" style="max-width: 280px;"><?php echo e($tx->description ?? '—'); ?></td>
                        <td class="small"><?php echo e($tx->created_at->format('Y-m-d')); ?> <br> <span class="text-muted"><?php echo e($tx->created_at->format('H:i')); ?></span></td>
                        <td>
                            <?php if($tx->image): ?>
                                <button type="button" class="btn btn-sm btn-label-primary" title="<?php echo e(__('View Attachment')); ?>" onclick="openAttachmentModal('<?php echo e(asset('storage/' . $tx->image)); ?>')">
                                    <i class="ti ti-file-symlink"></i>
                                </button>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <img src="<?php echo e(asset('assets/img/illustrations/empty-state.png')); ?>" alt="Empty state" width="120" class="mb-3 opacity-50">
                            <p class="text-muted"><?php echo e(__('No transactions yet.')); ?></p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($transactions->hasPages()): ?>
        <div class="card-footer px-4">
            <?php echo e($transactions->appends(request()->input())->links('investor.partials.pagination')); ?>

        </div>
        <?php endif; ?>
    </div>

</div>

    
    <?php if($contract && $contract->contract_type === 'general_investment' && $contract->isActive()): ?>
    <div class="modal fade" id="calculateCommissionsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="ti ti-calculator text-success me-2 ti-md"></i>
                        <?php echo e(__('Confirm Commission Calculation')); ?>

                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo e(route('investor.personal-wallet.calculate')); ?>" id="calculateCommissionsForm">
                    <?php echo csrf_field(); ?>
                    <div class="modal-body">
                        <!-- تعليمات الاحتساب -->
                        <div class="alert alert-info d-flex align-items-start mb-4">
                            <i class="ti ti-info-circle me-2 mt-1"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold"><?php echo e(__('Important instructions:')); ?></h6>
                                <ul class="mb-0 ps-3 small">
                                    <li><?php echo e(__('All tasks within your contract period will be scanned.')); ?></li>
                                    <li><?php echo e(__('Only tasks not previously calculated will be included.')); ?></li>
                                    <li><?php echo e(__('Earnings will be added directly to your withdrawable balance.')); ?></li>
                                </ul>
                            </div>
                        </div>

                        <!-- طلب كلمة المرور -->
                        <div class="mb-0">
                            <label class="form-label fw-bold mb-1" for="password"><?php echo e(__('Enter password to confirm:')); ?></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti ti-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control" 
                                    placeholder="············" required autocomplete="current-password">
                            </div>
                            <small class="text-danger mt-1 d-block">
                                * <?php echo e(__('Please enter your password to confirm.')); ?>

                            </small>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                        <button type="submit" class="btn btn-success btn-submit">
                            <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                            <?php echo e(__('Confirm Calculation')); ?>

                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
    <?php if(($personalWallet?->withdrawable_balance ?? 0) > 0): ?>
    <div class="modal fade" id="reinvestProfitsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="ti ti-refresh text-primary me-2 ti-md"></i>
                        <?php echo e(__('Reinvest Profits')); ?>

                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo e(route('investor.personal-wallet.reinvest')); ?>" id="reinvestProfitsForm">
                    <?php echo csrf_field(); ?>
                    <div class="modal-body">
                        <div class="alert alert-primary d-flex align-items-start mb-4">
                            <i class="ti ti-info-circle me-2 mt-1"></i>
                            <div class="small">
                                <strong><?php echo e(__('How does it work?')); ?></strong>
                                <ul class="mb-0 ps-3 mt-1">
                                    <li><?php echo e(__('Amount is deducted from withdrawable balance in commission wallet.')); ?></li>
                                    <li><?php echo e(__('Amount is added to investment wallet as new capital.')); ?></li>
                                    <li><?php echo e(__('You can use it immediately to fund new tasks (task-based investors).')); ?></li>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold"><?php echo e(__('Top up amount (SAR)')); ?></label>
                            <div class="input-group">
                                <input type="number" name="amount" id="reinvest_amount" class="form-control"
                                    step="0.01" min="0.01"
                                    max="<?php echo e(number_format($personalWallet->withdrawable_balance, 2, '.', '')); ?>"
                                    value="<?php echo e(number_format($personalWallet->withdrawable_balance, 2, '.', '')); ?>"
                                    required>
                                <button type="button" class="btn btn-label-secondary" id="reinvestMaxBtn"><?php echo e(__('Maximum')); ?></button>
                            </div>
                            <small class="text-muted"><?php echo e(__('Withdrawable balance')); ?>: <?php echo e(number_format($personalWallet->withdrawable_balance, 2)); ?> <?php echo e(__('SAR')); ?></small>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-bold" for="reinvest_password"><?php echo e(__('Enter password to confirm:')); ?></label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-text"><i class="ti ti-lock"></i></span>
                                <input type="password" name="password" id="reinvest_password" class="form-control"
                                    placeholder="············" required autocomplete="current-password">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                        <button type="submit" class="btn btn-primary btn-reinvest-submit">
                            <span class="spinner-border spinner-border-sm d-none me-1" role="status"></span>
                            <?php echo e(__('Confirm Reinvestment')); ?>

                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
    <?php
        $availWithdrawable = (float) ($personalWallet?->withdrawable_balance ?? 0);
    ?>
    <div class="modal fade" id="withdrawCommissionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="ti ti-cash-banknote text-warning me-2 ti-md"></i>
                        طلب سحب من محفظة العمولات
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="<?php echo e(route('investor.personal-wallet.request-withdrawal')); ?>" id="withdrawCommissionForm">
                    <?php echo csrf_field(); ?>
                    <div class="modal-body">
                        <?php if($availWithdrawable <= 0): ?>
                            <div class="alert alert-warning d-flex align-items-start mb-4">
                                <i class="ti ti-alert-triangle me-2 mt-1"></i>
                                <div class="small">
                                    <strong>لا يوجد رصيد متاح للسحب حالياً (0.00 ر.س).</strong>
                                    <p class="mb-0 mt-1">
                                        <?php if($contract && $contract->contract_type === 'task_investment'): ?>
                                            تصبح عمولات المهام متاحة للسحب بعد سداد العميل واسترداد رأس مال المهام الممولة إلى محفظة الاستثمار.
                                        <?php else: ?>
                                            يجب توفر أرباح أو عمولات مسجلة في محفظتك لتتمكن من تقديم طلب السحب.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning d-flex align-items-start mb-4">
                                <i class="ti ti-info-circle me-2 mt-1"></i>
                                <div class="small">
                                    <strong>تنبيه هام:</strong>
                                    <p class="mb-0 mt-1">يتم تقديم الطلب للإدارة لمراجعته وإتمام التحويل لحسابك البنكي، وتوثيق إيصال السداد وخصمه من محفظة العمولات فور الاعتماد.</p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label fw-bold">المبلغ المطلوب سحبه (ر.س) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="amount" id="withdraw_amount" class="form-control"
                                    step="0.01" min="1"
                                    max="<?php echo e(number_format($availWithdrawable, 2, '.', '')); ?>"
                                    value="<?php echo e($availWithdrawable > 0 ? number_format($availWithdrawable, 2, '.', '') : '0.00'); ?>"
                                    <?php echo e($availWithdrawable <= 0 ? 'disabled' : ''); ?>

                                    required>
                                <button type="button" class="btn btn-label-secondary" id="withdrawMaxBtn" <?php echo e($availWithdrawable <= 0 ? 'disabled' : ''); ?>><?php echo e(__('Maximum')); ?></button>
                            </div>
                            <small class="text-muted">الرصيد المتاح للسحب حالياً: <strong class="<?php echo e($availWithdrawable > 0 ? 'text-success' : 'text-danger'); ?>"><?php echo e(number_format($availWithdrawable, 2)); ?> ر.س</strong></small>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small">اسم البنك</label>
                                <input type="text" name="bank_name" class="form-control" value="<?php echo e($investor->bank_name); ?>" placeholder="مثال: مصرف الراجحي" <?php echo e($availWithdrawable <= 0 ? 'disabled' : ''); ?>>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">اسم المستفيد</label>
                                <input type="text" name="account_holder" class="form-control" value="<?php echo e($investor->name); ?>" placeholder="الاسم ثلاثي">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small">رقم الآيبان (IBAN) أو الحساب</label>
                                <input type="text" name="iban_number" class="form-control font-monospace" value="<?php echo e($investor->iban_number ?: $investor->account_number); ?>" placeholder="SA0000000000000000000000" <?php echo e($availWithdrawable <= 0 ? 'disabled' : ''); ?>>
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label small">ملاحظات للمسؤول (اختياري)</label>
                            <textarea name="investor_notes" class="form-control" rows="2" placeholder="أي تفاصيل أو تعليمات إضافية بخصوص السحب..." <?php echo e($availWithdrawable <= 0 ? 'disabled' : ''); ?>></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('Cancel')); ?></button>
                        <button type="submit" class="btn btn-warning text-dark fw-bold" <?php echo e($availWithdrawable <= 0 ? 'disabled' : ''); ?>>
                            <i class="ti ti-send me-1"></i> تقديم طلب السحب
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Attachment Modal -->
    <div class="modal fade" id="viewAttachmentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo e(__('View Attachment')); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0" id="attachmentModalBody">
                    <!-- Dynamic content will be injected here -->
                </div>
                <div class="modal-footer">
                    <a href="#" id="attachmentDownloadBtn" class="btn btn-primary" download>
                        <i class="ti ti-download me-1"></i> <?php echo e(__('Download')); ?>

                    </a>
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"><?php echo e(__('Close')); ?></button>
                </div>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        window.openAttachmentModal = function(url) {
            const modalBody = document.getElementById('attachmentModalBody');
            const downloadBtn = document.getElementById('attachmentDownloadBtn');
            const ext = url.split('.').pop().toLowerCase();
            
            modalBody.innerHTML = '';
            downloadBtn.href = url;

            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
                modalBody.innerHTML = `<img src="${url}" class="img-fluid" style="max-height: 70vh;" alt="Attachment">`;
            } else if (ext === 'pdf') {
                modalBody.innerHTML = `<iframe src="${url}" width="100%" height="500px" style="border: none;"></iframe>`;
            } else {
                modalBody.innerHTML = `
                    <div class="py-5">
                        <i class="ti ti-file-text display-1 text-muted mb-3"></i>
                        <h5><?php echo e(__('File preview not available')); ?></h5>
                        <p class="text-muted"><?php echo e(__('Please download the file to view it.')); ?></p>
                    </div>`;
            }
            
            new bootstrap.Modal(document.getElementById('viewAttachmentModal')).show();
        };

        const calcForm = document.getElementById('calculateCommissionsForm');
        if (calcForm) {
            calcForm.addEventListener('submit', function() {
                const submitBtn = this.querySelector('.btn-submit');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    const spinner = submitBtn.querySelector('.spinner-border');
                    if (spinner) spinner.classList.remove('d-none');
                }
            });
        }

        const reinvestForm = document.getElementById('reinvestProfitsForm');
        const reinvestMaxBtn = document.getElementById('reinvestMaxBtn');
        const reinvestAmount = document.getElementById('reinvest_amount');

        if (reinvestMaxBtn && reinvestAmount) {
            reinvestMaxBtn.addEventListener('click', function () {
                reinvestAmount.value = reinvestAmount.getAttribute('max');
            });
        }

        const withdrawMaxBtn = document.getElementById('withdrawMaxBtn');
        const withdrawAmount = document.getElementById('withdraw_amount');
        if (withdrawMaxBtn && withdrawAmount) {
            withdrawMaxBtn.addEventListener('click', function () {
                withdrawAmount.value = withdrawAmount.getAttribute('max');
            });
        }

        if (reinvestForm) {
            reinvestForm.addEventListener('submit', function () {
                const submitBtn = this.querySelector('.btn-reinvest-submit');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    const spinner = submitBtn.querySelector('.spinner-border');
                    if (spinner) spinner.classList.remove('d-none');
                }
            });
        }
    });
</script>
<?php echo app('Illuminate\Foundation\Vite')(['resources/js/investor/wallet.js']); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/investor/personal-wallet/index.blade.php ENDPATH**/ ?>