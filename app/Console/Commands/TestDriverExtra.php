<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestDriverExtra extends Command
{
    protected $signature = 'test:driver-extra';
    protected $description = 'Test driver extra fields extraction';

    public function handle()
    {
        $task = \App\Models\Task::with(['driver:id,additional_data'])->whereHas('driver', function($q) { $q->whereNotNull('additional_data'); })->first();
        if (!$task) {
            $this->error('No task found with a driver having additional_data');
            return;
        }

        $fields = \App\Models\Form_Field::get(['id', 'label', 'name']);
        
        $extraColumnsMap = [];
        $extraFieldNamesMap = [];
        foreach ($fields as $field) {
            $extraColumnsMap['driver_extra:' . $field->id] = trim($field->label);
            $extraFieldNamesMap['driver_extra:' . $field->id] = trim($field->name);
        }

        $additionalData = is_string($task->driver->additional_data) ? json_decode($task->driver->additional_data, true) : $task->driver->additional_data;

        $processedTask = [];
        
        $this->info("Driver additional data:");
        $this->line(json_encode($additionalData, JSON_UNESCAPED_UNICODE));

        if (is_array($additionalData)) {
            foreach ($extraColumnsMap as $colKey => $label) {
                $processedTask[$colKey] = 'غير محدد';
                $fieldName = $extraFieldNamesMap[$colKey] ?? null;

                $this->info("Checking $colKey -> fieldName: $fieldName, label: $label");

                // 1. Try matching by key (name)
                if ($fieldName && isset($additionalData[$fieldName])) {
                    $this->info("Found by name key $fieldName");
                    if (isset($additionalData[$fieldName]['value'])) {
                        $val = $additionalData[$fieldName]['value'];
                        if ($val !== null && $val !== '') {
                            $processedTask[$colKey] = $val;
                            continue;
                        } else {
                            $this->info("Value is null or empty");
                        }
                    } else {
                        $this->info("Value key not set");
                    }
                }

                // 2. Fallback to matching by label
                foreach ($additionalData as $item) {
                    if (is_array($item) && isset($item['label']) && trim($item['label']) === $label) {
                        $this->info("Found by label " . $item['label']);
                        if (isset($item['value']) && $item['value'] !== null && $item['value'] !== '') {
                            $processedTask[$colKey] = $item['value'];
                        }
                        break;
                    }
                }
            }
        }

        $this->info("Result:");
        $this->line(json_encode($processedTask, JSON_UNESCAPED_UNICODE));
    }
}
