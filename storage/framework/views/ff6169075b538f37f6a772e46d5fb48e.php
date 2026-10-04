<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" dir="<?php echo e(app()->getLocale() == 'ar' ? 'rtl' : 'ltr'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(__('Brokers Commissions Report')); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Segoe UI', Tahoma, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: white;
            direction: <?php echo e(app()->getLocale() == 'ar' ? 'rtl' : 'ltr'); ?>;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            margin-top: 5px;
            margin-bottom: 5px;
        }

        .report-title {
            font-size: 16px;
            color: #34495e;
            font-weight: bold;
        }

        .report-info {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 11px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: bold;
            color: #495057;
        }

        .info-value {
            color: #212529;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #dee2e6;
            padding: 7px 6px;
            text-align: center;
            vertical-align: middle;
        }

        .data-table th {
            background: #2c3e50;
            color: white;
            font-weight: bold;
            font-size: 11px;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .source-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            color: white;
        }

        .source-customer {
            background-color: #fd7e14;
        }

        .source-driver {
            background-color: #20c997;
        }

        .source-task {
            background-color: #0d6efd;
        }

        .summary {
            background: #e8f5e8;
            border: 1px solid #28a745;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
        }

        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #155724;
            margin-bottom: 12px;
            text-align: center;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .summary-item {
            text-align: center;
            background: white;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #c3e6cb;
        }

        .summary-label {
            font-weight: bold;
            color: #155724;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 13px;
            font-weight: bold;
            color: #2c3e50;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .container {
                padding: 5px;
            }

            @page {
                size: A4 landscape;
                margin: 0.8cm;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <?php if(view()->exists('_partials.macros')): ?>
                <span class="app-brand-logo demo"><?php echo $__env->make('_partials.macros', ['height' => 24], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></span>
            <?php endif; ?>
            <div class="company-name"><?php echo e(__('SafeDests for Transport and Logistics')); ?></div>
            <div class="report-title">
                <?php if(($reportData['mode'] ?? 'transactions') === 'aggregated'): ?>
                    <?php echo e(__('Brokers Commissions Report - Summary Aggregates')); ?>

                <?php else: ?>
                    <?php echo e(__('Brokers Commissions Report - Wallet Recorded Transactions')); ?>

                <?php endif; ?>
            </div>
        </div>

        <!-- Report Information -->
        <div class="report-info">
            <div class="info-row">
                <div>
                    <span class="info-label"><?php echo e(__('Brokers:')); ?> </span>
                    <span class="info-value"><?php echo e($reportData['filters_applied']['brokers'] ?? __('All')); ?></span>
                </div>
                <div>
                    <span class="info-label"><?php echo e(__('Time Period:')); ?> </span>
                    <span class="info-value"><?php echo e($reportData['filters_applied']['date_range'] ?? __('All')); ?></span>
                </div>
            </div>
            <div class="info-row">
                <div>
                    <span class="info-label"><?php echo e(__('Commission Source:')); ?> </span>
                    <span class="info-value"><?php echo e($reportData['filters_applied']['source'] ?? __('All')); ?></span>
                </div>
                <div>
                    <span class="info-label"><?php echo e(__('Report Type:')); ?> </span>
                    <span class="info-value"><?php echo e($reportData['filters_applied']['mode'] ?? '-'); ?></span>
                </div>
                <div>
                    <span class="info-label"><?php echo e(__('Creation Date:')); ?> </span>
                    <span class="info-value"><?php echo e($reportData['generated_at'] ? $reportData['generated_at']->format('Y-m-d H:i:s') : date('Y-m-d H:i:s')); ?></span>
                </div>
            </div>
        </div>

        <!-- Tables -->
        <?php if(($reportData['mode'] ?? 'transactions') === 'aggregated'): ?>
            <!-- Mode 2: Aggregated Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th><?php echo e(__('Broker Name')); ?></th>
                        <th><?php echo e(__('Phone')); ?></th>
                        <th><?php echo e(__('Customer Linking Commissions')); ?></th>
                        <th><?php echo e(__('Driver Linking Commissions')); ?></th>
                        <th><?php echo e(__('Direct Task Linking Commissions')); ?></th>
                        <th><?php echo e(__('Total Commissions')); ?></th>
                        <th><?php echo e(__('Transactions Count')); ?></th>
                        <th><?php echo e(__('Current Wallet Balance')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $reportData['brokers']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($index + 1); ?></td>
                            <td style="font-weight: bold; text-align: right; padding-right: 10px;"><?php echo e($b['broker_name']); ?></td>
                            <td><?php echo e($b['broker_phone']); ?></td>
                            <td style="color: #fd7e14; font-weight: bold;"><?php echo e(number_format($b['customer_commissions'], 2)); ?> <?php echo e(__('SAR')); ?></td>
                            <td style="color: #20c997; font-weight: bold;"><?php echo e(number_format($b['driver_commissions'], 2)); ?> <?php echo e(__('SAR')); ?></td>
                            <td style="color: #0d6efd; font-weight: bold;"><?php echo e(number_format($b['task_commissions'], 2)); ?> <?php echo e(__('SAR')); ?></td>
                            <td style="color: #28a745; font-weight: bold;"><?php echo e(number_format($b['total_commissions'], 2)); ?> <?php echo e(__('SAR')); ?></td>
                            <td><?php echo e($b['transactions_count']); ?></td>
                            <td style="font-weight: bold;"><?php echo e(number_format($b['wallet_balance'], 2)); ?> <?php echo e(__('SAR')); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 20px;"><?php echo e(__('No data available for selected brokers in specified period')); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Mode 1: Detailed Transactions Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 6%;"><?php echo e(__('Tx #')); ?></th>
                        <th style="width: 14%;"><?php echo e(__('Broker Name')); ?></th>
                        <th style="width: 12%;"><?php echo e(__('Source Type')); ?></th>
                        <th style="width: 16%;"><?php echo e(__('Source Entity / Name')); ?></th>
                        <th style="width: 8%;"><?php echo e(__('Task #')); ?></th>
                        <th style="width: 10%;"><?php echo e(__('Commission Amount')); ?></th>
                        <th style="width: 20%;"><?php echo e(__('Description')); ?></th>
                        <th style="width: 14%;"><?php echo e(__('Date & Time')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $reportData['transactions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>#<?php echo e($tx['sequence'] ?? $tx['id']); ?></td>
                            <td style="font-weight: bold; text-align: right; padding-right: 8px;"><?php echo e($tx['broker_name']); ?></td>
                            <td>
                                <?php if($tx['source_type'] === 'customer'): ?>
                                    <span class="source-badge source-customer"><?php echo e(__('Linked with Customer')); ?></span>
                                <?php elseif($tx['source_type'] === 'driver'): ?>
                                    <span class="source-badge source-driver"><?php echo e(__('Linked with Driver')); ?></span>
                                <?php else: ?>
                                    <span class="source-badge source-task"><?php echo e(__('Linked with Task')); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($tx['source_name']); ?></td>
                            <td><?php echo e(!empty($tx['task_id']) && $tx['task_id'] !== '-' ? '#' . $tx['task_id'] : '-'); ?></td>
                            <td style="color: #28a745; font-weight: bold;"><?php echo e(number_format($tx['amount'], 2)); ?> <?php echo e(__('SAR')); ?></td>
                            <td style="font-size: 9.5px; text-align: right;"><?php echo e($tx['description']); ?></td>
                            <td><?php echo e($tx['created_at']); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;"><?php echo e(__('No commission transactions recorded matching specified filter criteria')); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Summary Statistics -->
        <?php if(isset($reportData['summary'])): ?>
            <div class="summary">
                <div class="summary-title"><?php echo e(__('Brokers Report Statistics Summary')); ?></div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label"><?php echo e(__('Grand Total Commissions')); ?></div>
                        <div class="summary-value" style="color: #28a745;">
                            <?php echo e(number_format(($reportData['mode'] === 'aggregated' ? $reportData['summary']['grand_total_commissions'] : $reportData['summary']['total_commissions']), 2)); ?> <?php echo e(__('SAR')); ?>

                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label"><?php echo e(__('Customer Linking Commissions')); ?></div>
                        <div class="summary-value" style="color: #fd7e14;">
                            <?php echo e(number_format(($reportData['mode'] === 'aggregated' ? $reportData['summary']['total_customer_commissions'] : $reportData['summary']['customer_commissions_total']), 2)); ?> <?php echo e(__('SAR')); ?>

                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label"><?php echo e(__('Driver Linking Commissions')); ?></div>
                        <div class="summary-value" style="color: #20c997;">
                            <?php echo e(number_format(($reportData['mode'] === 'aggregated' ? $reportData['summary']['total_driver_commissions'] : $reportData['summary']['driver_commissions_total']), 2)); ?> <?php echo e(__('SAR')); ?>

                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label"><?php echo e(__('Direct Task Linking Commissions')); ?></div>
                        <div class="summary-value" style="color: #0d6efd;">
                            <?php echo e(number_format(($reportData['mode'] === 'aggregated' ? $reportData['summary']['total_task_commissions'] : $reportData['summary']['task_commissions_total']), 2)); ?> <?php echo e(__('SAR')); ?>

                        </div>
                    </div>
                </div>

                <div class="summary-grid" style="margin-top: 10px; grid-template-columns: repeat(2, 1fr);">
                    <div class="summary-item">
                        <div class="summary-label"><?php echo e(__('Total Transactions Count')); ?></div>
                        <div class="summary-value">
                            <?php echo e($reportData['mode'] === 'aggregated' ? $reportData['summary']['total_transactions_count'] : $reportData['summary']['transactions_count']); ?>

                        </div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label"><?php echo e(__('Brokers Count in Report')); ?></div>
                        <div class="summary-value">
                            <?php echo e($reportData['mode'] === 'aggregated' ? $reportData['summary']['total_brokers'] : $reportData['summary']['brokers_count']); ?>

                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p><?php echo e(__('This report was generated automatically by SafeDests Logistics & Transportation System')); ?></p>
            <p><?php echo e(__('For inquiries and technical support, please contact the platform administration')); ?></p>
        </div>
    </div>

    <!-- Print Script -->
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        window.onafterprint = function() {
            setTimeout(function() {
                window.close();
            }, 1000);
        };
    </script>
</body>

</html>
<?php /**PATH C:\xampp\htdocs\safedestssss\resources\views/admin/reports/pdf/broker-report-simple.blade.php ENDPATH**/ ?>