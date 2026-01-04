<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistributionSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'auto_distribution_enabled',
                'value' => '1',
                'description' => 'Enable or disable automatic task distribution',
                'type' => 'boolean',
                'category' => 'distribution',
                'name' => 'التوزيع التلقائي للمهام',
            ],
            [
                'key' => 'distribution_mode',
                'value' => 'sequential',
                'description' => 'Mode of distribution: sequential (one by one) or broadcast (top 5)',
                'type' => 'select',
                'category' => 'distribution',
                'name' => 'نمط التوزيع',
                'options' => json_encode(['sequential' => 'متسلسل (سائق واحد)', 'broadcast' => 'بث (أقرب 5)']),
            ],
            [
                'key' => 'max_distribution_distance',
                'value' => '1000',
                'description' => 'Maximum distance (meters) for finding suitable drivers',
                'type' => 'number',
                'category' => 'distribution',
                'name' => 'أقصى مسافة للتوزيع (متر)',
            ],
            [
                'key' => 'min_driver_app_version',
                'value' => '1.0.0',
                'description' => 'Minimum required version for the driver app',
                'type' => 'text',
                'category' => 'app_update',
                'name' => 'أقل إصدار للتطبيق',
            ],
            [
                'key' => 'driver_app_update_url',
                'value' => 'https://play.google.com/store/apps',
                'description' => 'URL to redirect drivers for app update',
                'type' => 'text',
                'category' => 'app_update',
                'name' => 'رابط تحديث التطبيق',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
