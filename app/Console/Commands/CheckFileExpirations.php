<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileExpirationService;
use Illuminate\Support\Facades\Log;
use Exception;

class CheckFileExpirations extends Command
{
    /**
     * Command name and signature
     */
    protected $signature = 'files:check-expirations
                            {--dry-run : Run in test mode without sending emails or suspending accounts}
                            {--user-type= : Check a specific user type (user, customer, driver)}
                            {--stats : Display only system statistics}';

    /**
     * Command description
     */
    protected $description = 'Check expired files, send notifications, and suspend accounts if required';

    /**
     * File expiration service
     */
    protected $fileExpirationService;

    /**
     * Create a new command instance
     */
    public function __construct(FileExpirationService $fileExpirationService)
    {
        parent::__construct();
        $this->fileExpirationService = $fileExpirationService;
    }

    /**
     * Execute the command
     */
    public function handle()
    {
        $startTime = microtime(true);

        try {
            $this->displayHeader();

            // Show statistics only if requested
            if ($this->option('stats')) {
                return $this->displayStatistics();
            }

            // Check dry run mode
            if ($this->option('dry-run')) {
                $this->warn('🧪 Dry run - No emails will be sent and no accounts will be suspended');
                $this->newLine();
            }

            // Start file expiration check
            $this->info('🔍 Starting expired file check...');
            $this->newLine();

            $results = $this->fileExpirationService->checkAndNotifyExpiredFiles();

            // Display results
            $this->displayResults($results, microtime(true) - $startTime);

            // Display statistics if verbose is enabled
            if ($this->option('verbose')) {
                $this->newLine();
                $this->displayStatistics();
            }

            return $this->getExitCode($results);

        } catch (Exception $e) {
            $this->error('❌ An error occurred while running the command:');
            $this->error($e->getMessage());

            if ($this->option('verbose')) {
                $this->error('Error details:');
                $this->error($e->getTraceAsString());
            }

            Log::error('CheckFileExpirations command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }

    /**
     * Display command header
     */
    protected function displayHeader()
    {
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                  🔔 File Expiration Check System               ║');
        $this->info('║                        SafeDests Platform                      ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->info('📅 Date: ' . now()->format('Y-m-d H:i:s'));
        $this->info('🌍 Timezone: ' . config('app.timezone', 'UTC'));
        $this->newLine();
    }

    /**
     * Display check results
     */
    protected function displayResults(array $results, float $executionTime)
    {
        $this->info('✅ File expiration check completed successfully!');
        $this->newLine();

        // Create results table
        $tableData = [
            ['Metric', 'Count', 'Status'],
            ['Users checked', $results['users_checked'], '✓'],
            ['Customers checked', $results['customers_checked'], '✓'],
            ['Drivers checked', $results['drivers_checked'], '✓'],
            ['Notifications sent', $results['notifications_sent'], $results['notifications_sent'] > 0 ? '📧' : '-'],
            ['Accounts suspended', $results['accounts_suspended'], $results['accounts_suspended'] > 0 ? '🚫' : '-'],
        ];

        $this->table($tableData[0], array_slice($tableData, 1));

        // Display errors if any
        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error('⚠️  Errors occurred:');
            foreach ($results['errors'] as $error) {
                $this->error("   • $error");
            }
        }

        // Performance info
        $this->newLine();
        $this->info("⏱️  Execution time: " . round($executionTime, 2) . " seconds");
        $this->info("💾 Memory usage: " . $this->formatBytes(memory_get_peak_usage(true)));
    }

    /**
     * Display system statistics
     */
    protected function displayStatistics()
    {
        try {
            $this->info('📊 System Statistics:');
            $this->newLine();

            $stats = $this->fileExpirationService->getSystemStatistics();

            // Active users statistics
            $this->info('👥 Active users:');
            $activeUsersData = [
                ['Type', 'Count'],
                ['System users', $stats['active_users']['users']],
                ['Customers', $stats['active_users']['customers']],
                ['Drivers', $stats['active_users']['drivers']],
                ['Total', array_sum($stats['active_users'])]
            ];
            $this->table($activeUsersData[0], array_slice($activeUsersData, 1));

            // Notifications statistics
            if (!empty($stats['notifications'])) {
                $this->newLine();
                $this->info('🔔 Notifications today (' . $stats['date'] . '):');

                $notificationData = [
                    ['Metric', 'Count']
                ];

                $notificationData[] = ['Total notifications', $stats['notifications']['total']];

                if (!empty($stats['notifications']['by_user_type'])) {
                    foreach ($stats['notifications']['by_user_type'] as $type => $count) {
                        $typeLabel = [
                            'user' => 'System users',
                            'customer' => 'Customers',
                            'driver' => 'Drivers'
                        ][$type] ?? $type;
                        $notificationData[] = ["Notifications for {$typeLabel}", $count];
                    }
                }

                $notificationData[] = ['Expired files', $stats['notifications']['expired_files'] ?? 0];
                $notificationData[] = ['Files expiring soon', $stats['notifications']['expiring_soon'] ?? 0];
                $notificationData[] = ['Accounts suspended today', $stats['suspended_today']];

                $this->table($notificationData[0], array_slice($notificationData, 1));
            }

            return 0;

        } catch (Exception $e) {
            $this->error('❌ Error displaying statistics: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Determine exit code based on results
     */
    protected function getExitCode(array $results): int
    {
        // If there are errors
        if (!empty($results['errors'])) {
            return 1;
        }

        // If too many accounts were suspended
        if ($results['accounts_suspended'] > 10) {
            $this->warn('⚠️  Warning: A large number of accounts were suspended (' . $results['accounts_suspended'] . ')');
            return 2;
        }

        return 0;
    }

    /**
     * Format memory size
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
