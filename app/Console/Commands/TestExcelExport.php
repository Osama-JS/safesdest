<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportService;
use App\Exports\CustomerTasksExport;

class TestExcelExport extends Command
{
    protected $signature = 'test:excel-export';

    public function handle(ReportService $reportService)
    {
        // Mock a user
        $user = \App\Models\User::first();
        \Illuminate\Support\Facades\Auth::login($user);

        $filters = [
            'date_from' => '2020-01-01',
            'date_to' => '2030-01-01',
            'columns' => ['task_id', 'driver_info', 'driver_extra:1', 'driver_extra:3'],
        ];

        // Hacky way: bypass user permissions for this test
        $reportData = $reportService->generateCustomerTasksReport($filters, true);
        
        $task2 = array_values(array_filter($reportData['tasks'], function($t) { return $t['id'] == 2; }));
        if (count($task2) > 0) {
            $json = json_encode($task2[0], JSON_UNESCAPED_UNICODE);
            $this->line("Full JSON of Task 2:\n" . $json);
        } else {
            $this->error("Task 2 not found in generated report!");
        }
    }
}
