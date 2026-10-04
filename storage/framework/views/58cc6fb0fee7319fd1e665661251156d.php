<?php $__env->startSection('title', __('Platform Reports')); ?>

<!-- Vendor Styles -->
<?php $__env->startSection('vendor-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss']); ?>
<?php $__env->stopSection(); ?>

<!-- Page Styles -->
<?php $__env->startSection('page-style'); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css']); ?>
    <style>
        .report-card {
            transition: all 0.3s ease;
            border: 1px solid #e3e6f0;
            border-radius: 0.35rem;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-color: #5a5c69;
        }

        .report-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 1rem;
        }

        .report-icon.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .report-icon.success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .report-icon.warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .report-icon.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }


        .breadcrumb-item+.breadcrumb-item::before {
            color: rgba(255, 255, 255, 0.7);
        }

        .breadcrumb-item.active {
            color: rgba(255, 255, 255, 0.8);
        }

        .breadcrumb-item a {
            color: white;
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>


    <div class="card mb-4">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-2">
                <i class="tf-icons ti ti-report me-2 fs-3 text-white bg-primary rounded p-1"></i>

                <?php echo e(__('Platform Reports')); ?>

            </h5>
            <p><?php echo e(__('Generate comprehensive reports for your platform data')); ?>

            </p>
        </div>
    </div>


    <!-- Reports Grid -->
    <div class="container-fluid">
        <div class="row">
            <!-- Customer Tasks Report -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <div class="report-icon primary mx-auto">
                            <i class="ti ti-user-circle"></i>
                        </div>
                        <h5 class="card-title"><?php echo e(__('Customer Tasks Report')); ?></h5>
                        <p class="card-text text-muted">
                            <?php echo e(__('Generate detailed reports for customer tasks with customizable filters and export options')); ?>

                        </p>
                        <div class="mt-3">
                            <span class="badge bg-primary me-2"><?php echo e(__('Excel Export')); ?></span>
                            <span class="badge bg-secondary"><?php echo e(__('PDF Export')); ?></span>
                        </div>
                        <div class="mt-4">
                            <a href="<?php echo e(route('admin.reports.customer-tasks')); ?>" class="btn btn-primary">
                                <i class="ti ti-report me-1"></i>
                                <?php echo e(__('Generate Report')); ?>

                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Driver Tasks Report -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <div class="report-icon success mx-auto">
                            <i class="ti ti-steering-wheel"></i>
                        </div>
                        <h5 class="card-title"><?php echo e(__('Driver Tasks Report')); ?></h5>
                        <p class="card-text text-muted">
                            <?php echo e(__('Generate detailed reports for driver tasks with commission calculations and customizable filters')); ?>

                        </p>
                        <div class="mt-3">
                            <span class="badge bg-success me-2"><?php echo e(__('Excel Export')); ?></span>
                            <span class="badge bg-secondary me-2"><?php echo e(__('PDF Export')); ?></span>
                            
                        </div>
                        <div class="mt-4">
                            <a href="<?php echo e(route('admin.reports.driver-tasks')); ?>" class="btn btn-success">
                                <i class="ti ti-report me-1"></i>
                                <?php echo e(__('Generate Report')); ?>

                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Tasks Report -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <div class="report-icon info mx-auto">
                            <i class="ti ti-users"></i>
                        </div>
                        <h5 class="card-title"><?php echo e(__('Team Tasks Report')); ?></h5>
                        <p class="card-text text-muted">
                            <?php echo e(__('Generate comprehensive reports for team tasks with driver and customer information')); ?>

                        </p>
                        <div class="mt-3">
                            <span class="badge bg-info me-2"><?php echo e(__('Excel Export')); ?></span>
                            <span class="badge bg-secondary me-2"><?php echo e(__('PDF Export')); ?></span>
                            
                        </div>
                        <div class="mt-4">
                            <a href="<?php echo e(route('admin.reports.team-tasks')); ?>" class="btn btn-info">
                                <i class="ti ti-report me-1"></i>
                                <?php echo e(__('Generate Report')); ?>

                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wallet Reports -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <div class="report-icon success mx-auto">
                            <i class="ti ti-wallet"></i>
                        </div>
                        <h5 class="card-title"><?php echo e(__('Wallet Reports')); ?></h5>
                        <p class="card-text text-muted">
                            <?php echo e(__('Generate comprehensive wallet reports for customers, drivers, and teams with transaction details')); ?>

                        </p>
                        <div class="mt-3">
                            <span class="badge bg-success me-2"><?php echo e(__('PDF Export')); ?></span>
                            <span class="badge bg-info"><?php echo e(__('Multi-Type')); ?></span>
                        </div>
                        <div class="mt-4">
                            <a href="<?php echo e(route('admin.reports.wallet.index')); ?>" class="btn btn-success">
                                <i class="ti ti-report me-1"></i>
                                <?php echo e(__('Generate Report')); ?>

                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistical Report -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <div class="report-icon primary mx-auto" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                            <i class="ti ti-chart-bar"></i>
                        </div>
                        <h5 class="card-title"><?php echo e(__('Statistical Report (Matrix)')); ?></h5>
                        <p class="card-text text-muted">
                            <?php echo e(__('Generate a custom matrix-style report covering activity, profitability, cash flow, and collections by day.')); ?>

                        </p>
                        <div class="mt-3">
                            <span class="badge bg-success me-2"><?php echo e(__('Excel Export')); ?></span>
                            <span class="badge bg-info"><?php echo e(__('Custom Matrix')); ?></span>
                        </div>
                        <div class="mt-4">
                            <a href="<?php echo e(route('admin.reports.statistical')); ?>" class="btn btn-success" style="background-color: #11998e; border-color: #11998e;">
                                <i class="ti ti-report me-1"></i>
                                <?php echo e(__('Generate Report')); ?>

                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Brokers Report -->
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <div class="report-icon warning mx-auto" style="background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);">
                            <i class="ti ti-briefcase"></i>
                        </div>
                        <h5 class="card-title"><?php echo e(__('Brokers Report')); ?></h5>
                        <p class="card-text text-muted">
                            <?php echo e(__('Generate comprehensive reports for brokers commissions from customers, drivers, and tasks with detailed wallet transactions or summary aggregates.')); ?>

                        </p>
                        <div class="mt-3">
                            <span class="badge bg-primary me-2"><?php echo e(__('Excel Export')); ?></span>
                            <span class="badge bg-secondary me-2"><?php echo e(__('PDF Export')); ?></span>
                            <span class="badge bg-warning"><?php echo e(__('2 Modes')); ?></span>
                        </div>
                        <div class="mt-4">
                            <a href="<?php echo e(route('admin.reports.brokers')); ?>" class="btn btn-warning" style="background-color: #fda085; border-color: #fda085; color: #fff;">
                                <i class="ti ti-report me-1"></i>
                                <?php echo e(__('Generate Report')); ?>

                            </a>
                        </div>
                    </div>
                </div>
            </div>

            
        </div>
    <?php $__env->stopSection(); ?>

    <!-- Vendor Scripts -->
    <?php $__env->startSection('vendor-script'); ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js']); ?>
    <?php $__env->stopSection(); ?>

    <!-- Page Scripts -->
    <?php $__env->startSection('page-script'); ?>
        <script>
            $(document).ready(function() {
                // Add any initialization scripts here
                console.log('Platform Reports page loaded');
            });
        </script>
    <?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts/layoutMaster', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/reports/index.blade.php ENDPATH**/ ?>