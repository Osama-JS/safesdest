<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DebugDriverKeys extends Command
{
    protected $signature = 'debug:driver-keys';

    public function handle()
    {
        $keys = [];
        $labels = [];
        foreach(\App\Models\Driver::whereNotNull('additional_data')->get() as $d) {
            $data = is_string($d->additional_data) ? json_decode($d->additional_data, true) : $d->additional_data;
            if(is_array($data)) {
                foreach($data as $k => $v) {
                    $keys[$k] = true;
                    if (is_array($v) && isset($v['label'])) {
                        $labels[$v['label']] = true;
                    }
                }
            }
        }
        $this->info("Keys: " . json_encode(array_keys($keys), JSON_UNESCAPED_UNICODE));
        $this->info("Labels: " . json_encode(array_keys($labels), JSON_UNESCAPED_UNICODE));
        
        // Let's also check Form_Field
        $formFields = \App\Models\Form_Field::get(['id', 'name', 'label'])->toArray();
        $this->info("Form Fields: " . json_encode($formFields, JSON_UNESCAPED_UNICODE));
    }
}
