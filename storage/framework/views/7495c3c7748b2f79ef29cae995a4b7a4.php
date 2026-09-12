<?php $__env->startSection('title', __('Investor Dashboard')); ?>

<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/apex-charts/apex-charts.scss']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('vendor-script'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/apex-charts/apexcharts.js']); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script>
    'use strict';
    (function () {
        document.addEventListener('DOMContentLoaded', function () {
            let cardColor, headingColor, labelColor, shadeColor, borderColor;

        cardColor = config.colors.cardColor;
        headingColor = config.colors.headingColor;
        labelColor = config.colors.textMuted;
        borderColor = config.colors.borderColor;

        // Profit Chart
        const profitChartEl = document.querySelector('#profitChart'),
            profitChartConfig = {
                series: [{
                    name: '<?php echo e(__('Profits (SAR)')); ?>',
                    data: <?php echo json_encode($chartData['data'], 15, 512) ?>
                }],
                chart: {
                    height: 250,
                    type: 'area',
                    toolbar: { show: false }
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                grid: {
                    borderColor: borderColor,
                    padding: { top: 0, bottom: -10, left: 20, right: 20 }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.5,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: <?php echo json_encode($chartData['labels'], 15, 512) ?>,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { colors: labelColor, fontSize: '13px' } }
                },
                yaxis: { labels: { show: false } },
                colors: [config.colors.success]
            };
        if (typeof profitChartEl !== undefined && profitChartEl !== null) {
            const profitChart = new ApexCharts(profitChartEl, profitChartConfig);
            profitChart.render();
        }

        // ROI Donut Chart
        const investmentChartEl = document.querySelector('#investmentRadialChart'),
            investmentChartConfig = {
                chart: {
                    height: 300,
                    type: 'donut'
                },
                labels: [<?php echo json_encode(__('Total Invested'), 15, 512) ?>, <?php echo json_encode(__('Total Earned Commissions'), 15, 512) ?>],
                series: [<?php echo e((float)$stats['total_invested']); ?>, <?php echo e((float)$stats['total_commissions']); ?>],
                colors: [config.colors.primary, config.colors.success],
                stroke: { width: 0 },
                dataLabels: { enabled: false },
                legend: { show: false },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '75%',
                            labels: {
                                show: true,
                                value: {
                                    fontSize: '1.5rem',
                                    fontFamily: 'Public Sans',
                                    color: headingColor,
                                    offsetY: -15,
                                    formatter: function (val) {
                                        return parseInt(val) + ' <?php echo e(__('SAR')); ?>';
                                    }
                                },
                                name: { offsetY: 20, fontFamily: 'Public Sans' },
                                total: {
                                    show: true,
                                    fontSize: '0.8125rem',
                                    color: labelColor,
                                    label: '<?php echo e(__('ROI')); ?>',
                                    formatter: function (w) {
                                        return '<?php echo e($stats['roi_percentage']); ?>%';
                                    }
                                }
                            }
                        }
                    }
                }
            };
            if (typeof investmentChartEl !== undefined && investmentChartEl !== null) {
                const investmentChart = new ApexCharts(investmentChartEl, investmentChartConfig);
                investmentChart.render();
            }
        });
    })();
</script>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="container-xxl flex-grow-1 container-p-y">

    
    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible mb-4" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    
    <div class="row mb-4">
        <div class="col-lg-8 col-12">
            <div class="card bg-primary text-white h-100">
                <div class="card-body d-flex justify-content-between flex-wrap gap-3">
                    <div class="d-flex flex-column justify-content-center">
                        <h4 class="text-white mb-1"><?php echo e(__('Welcome')); ?>, <?php echo e(auth()->user()->name); ?> 👋</h4>
                        <p class="mb-3"><?php echo e(__('We are glad to see your investments grow today.')); ?></p>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if($contract): ?>
                                <span class="badge bg-white text-primary px-3">
                                    <i class="ti ti-certificate me-1"></i>
                                    <?php echo e(__('Contract')); ?> <?php echo e($contract->contract_type === 'task_investment' ? __('Task-based investment') : __('General investment')); ?>

                                </span>
                                <span class="badge bg-white text-primary px-3">
                                    <i class="ti ti-percentage me-1"></i>
                                    <?php echo e(__('Commission')); ?> <?php echo e($contract->commission_value); ?><?php echo e($contract->commission_type === 'percentage' ? '%' : ' ' . __('SAR')); ?>

                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-none d-md-block">
                        <img src="<?php echo e(asset('assets/img/illustrations/page-pricing-enterprise.png')); ?>" alt="Dashboard Illustration" width="140">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-12 mt-4 mt-lg-0">
            <div class="card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="row">
                        <div class="col-6 border-end">
                            <p class="mb-1 text-muted small"><?php echo e(__('Total Invested')); ?></p>
                            <h4 class="mb-0 fw-bold"><?php echo e(number_format($stats['total_invested'], 2)); ?></h4>
                        </div>
                        <div class="col-6">
                            <p class="mb-1 text-muted small"><?php echo e(__('Net Profits')); ?></p>
                            <h4 class="mb-0 fw-bold text-success"><?php echo e(number_format($stats['total_commissions'], 2)); ?></h4>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="badge bg-label-success rounded-pill px-3 py-2">
                            <i class="ti ti-trending-up me-1"></i>
                            <?php echo e(__('ROI Rate')); ?>: <?php echo e($stats['roi_percentage']); ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-warning"><i class="ti ti-wallet ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0"><?php echo e(number_format($stats['investment_balance'], 2)); ?></h4>
                    </div>
                    <p class="mb-1"><?php echo e(__('Available Investment Balance')); ?></p>
                    <p class="mb-0 small text-muted">
                        <span class="text-success me-1"><?php echo e(__('Ready to fund')); ?></span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-success"><i class="ti ti-cash ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0"><?php echo e(number_format($stats['personal_balance'], 2)); ?></h4>
                    </div>
                    <p class="mb-1"><?php echo e(__('Personal Wallet Balance')); ?></p>
                    <p class="mb-0 small text-muted">
                        <span class="text-info me-1"><?php echo e(__('Withdrawable')); ?></span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-checklist ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0"><?php echo e(number_format($stats['paid_tasks_count'])); ?></h4>
                    </div>
                    <p class="mb-1"><?php echo e(__('Total Funded Tasks')); ?></p>
                    <p class="mb-0 small text-muted">
                        <span class="text-primary me-1"><?php echo e(__('Successful operations')); ?></span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2 pb-1">
                        <div class="avatar me-2">
                            <span class="avatar-initial rounded bg-label-info"><i class="ti ti-calendar ti-md"></i></span>
                        </div>
                        <h4 class="ms-1 mb-0"><?php echo e($contract ? $contract->start_date->format('Y-m-d') : '—'); ?></h4>
                    </div>
                    <p class="mb-1"><?php echo e(__('Investment Start Date')); ?></p>
                    <p class="mb-0 small text-muted">
                        <span class="text-muted"><?php echo e(__('Current contract')); ?></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row mb-4">
        
        <div class="col-lg-8 col-12">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="card-title mb-0">
                        <h5 class="m-0 me-2 text-muted"><?php echo e(__('Profit Growth (last 6 months)')); ?></h5>
                    </div>
                </div>
                <div class="card-body">
                    <div id="profitChart"></div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-12 mt-4 mt-lg-0">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title m-0 me-2 text-muted"><?php echo e(__('Profit to Investment Ratio')); ?></h5>
                </div>
                <div class="card-body">
                    <div id="investmentRadialChart"></div>
                    <div class="d-flex flex-column gap-3 mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge badge-dot bg-primary"></span>
                                <small class="fw-bold"><?php echo e(__('Total Invested')); ?></small>
                            </div>
                            <small class="fw-bold"><?php echo e(number_format($stats['total_invested'], 2)); ?> <?php echo e(__('SAR')); ?></small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge badge-dot bg-success"></span>
                                <small class="fw-bold"><?php echo e(__('Total Earned Commissions')); ?></small>
                            </div>
                            <small class="fw-bold text-success"><?php echo e(number_format($stats['total_commissions'], 2)); ?> <?php echo e(__('SAR')); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row">
        <div class="col-md-8 col-12">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><?php echo e(__('Recent Investment Wallet Activity')); ?></h5>
                    <a href="<?php echo e(route('investor.investment-wallet')); ?>" class="btn btn-sm btn-label-primary"><?php echo e(__('View All')); ?></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?php echo e(__('Operation Type')); ?></th>
                                <th><?php echo e(__('Amount')); ?></th>
                                <th><?php echo e(__('Task')); ?></th>
                                <th><?php echo e(__('Date')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $recentActivity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>
                                    <span class="badge bg-label-<?php echo e($tx->transaction_type === 'credit' ? 'success' : 'danger'); ?> rounded-pill">
                                        <?php echo e($tx->transaction_type === 'credit' ? __('Deposit') : __('Task Funding')); ?>

                                    </span>
                                </td>
                                <td class="fw-bold <?php echo e($tx->transaction_type === 'credit' ? 'text-success' : 'text-danger'); ?>">
                                    <?php echo e($tx->transaction_type === 'credit' ? '+' : '-'); ?><?php echo e(number_format($tx->amount, 2)); ?>

                                </td>
                                <td>
                                    <?php if($tx->task_id): ?>
                                        <span class="text-primary">#<?php echo e($tx->task_id); ?></span>
                                    <?php else: ?> <small class="text-muted"><?php echo e(__('System')); ?></small> <?php endif; ?>
                                </td>
                                <td class="small"><?php echo e($tx->created_at->format('M d, H:i')); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="4" class="text-center py-4"><?php echo e(__('No recent activity')); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12 mt-4 mt-md-0">
            <div class="card h-100">
                <div class="card-header"><h5 class="card-title mb-0"><?php echo e(__('Quick Links')); ?></h5></div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="<?php echo e(route('investor.task-payment')); ?>" class="list-group-item list-group-item-action d-flex align-items-center px-0">
                            <i class="ti ti-credit-card me-3 text-primary"></i> <?php echo e(__('Fund New Tasks')); ?>

                        </a>
                        <a href="<?php echo e(route('investor.paid-tasks')); ?>" class="list-group-item list-group-item-action d-flex align-items-center px-0">
                            <i class="ti ti-list-check me-3 text-success"></i> <?php echo e(__('Funded Tasks History')); ?>

                        </a>
                        <a href="<?php echo e(route('investor.investment-wallet')); ?>" class="list-group-item list-group-item-action d-flex align-items-center px-0">
                            <i class="ti ti-wallet me-3 text-warning"></i> <?php echo e(__('Investment Wallet Statement')); ?>

                        </a>
                        <a href="<?php echo e(route('investor.profile')); ?>" class="list-group-item list-group-item-action d-flex align-items-center px-0">
                            <i class="ti ti-settings me-3 text-info"></i> <?php echo e(__('Account Settings')); ?>

                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/investor/dashboard.blade.php ENDPATH**/ ?>