<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BackfillCustomerTaskNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:backfill-numbers
                            {--customer= : Backfill for a specific customer ID only}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill customer_task_number for existing tasks of customers with custom numbering';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = $this->option('customer');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        // Get customers with custom numbering enabled
        $query = Customer::whereNotNull('task_number_start');

        if ($customerId) {
            $query->where('id', $customerId);
        }

        $customers = $query->get();

        if ($customers->isEmpty()) {
            $this->warn('No customers found with custom task numbering enabled.');
            return 0;
        }

        $this->info("Found {$customers->count()} customer(s) with custom numbering.");
        $this->newLine();

        $totalUpdated = 0;

        foreach ($customers as $customer) {
            $this->info("Processing customer #{$customer->id}: {$customer->name}");
            $this->info("  Start number: {$customer->task_number_start}");

            // Get all tasks for this customer ordered by creation date
            $tasks = Task::where('customer_id', $customer->id)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($tasks->isEmpty()) {
                $this->warn("  No tasks found for this customer. Skipping.");
                $this->newLine();
                continue;
            }

            $this->info("  Found {$tasks->count()} task(s)");

            $currentNumber = $customer->task_number_start;
            $updatedCount = 0;

            if (!$isDryRun) {
                DB::beginTransaction();
            }

            try {
                foreach ($tasks as $task) {
                    if ($isDryRun) {
                        $this->line("    [DRY RUN] Task #{$task->id} (created: {$task->created_at}) → customer_task_number = {$currentNumber}");
                    } else {
                        $task->customer_task_number = $currentNumber;
                        $task->saveQuietly(); // Avoid triggering observer
                    }

                    $currentNumber++;
                    $updatedCount++;
                }

                if (!$isDryRun) {
                    // Update the customer's next number counter
                    $customer->task_number_next = $currentNumber;
                    $customer->saveQuietly();

                    DB::commit();
                    $this->info("  ✅ Updated {$updatedCount} tasks (numbers {$customer->task_number_start} to " . ($currentNumber - 1) . ")");
                    $this->info("  Next available number: {$currentNumber}");
                } else {
                    $this->info("  [DRY RUN] Would update {$updatedCount} tasks (numbers {$customer->task_number_start} to " . ($currentNumber - 1) . ")");
                    $this->info("  [DRY RUN] Next available number would be: {$currentNumber}");
                }

                $totalUpdated += $updatedCount;

            } catch (\Exception $e) {
                if (!$isDryRun) {
                    DB::rollBack();
                }
                $this->error("  ❌ Error processing customer #{$customer->id}: {$e->getMessage()}");
                Log::error("BackfillCustomerTaskNumbers: Error for customer #{$customer->id}", [
                    'error' => $e->getMessage()
                ]);
            }

            $this->newLine();
        }

        $this->newLine();
        $this->info("🏁 Done! Total tasks " . ($isDryRun ? 'that would be ' : '') . "updated: {$totalUpdated}");

        return 0;
    }
}
