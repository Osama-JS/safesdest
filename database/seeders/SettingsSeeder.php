<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
        //   [
        //     'key' => 'customer_template',
        //     'value' => null,
        //     'description' => "This is a default Template for customers",
        //   ],
        //   [
        //     'key' => 'driver_template',
        //     'value' => null,
        //     'description' => "This is a default Template for drivers",
        //   ],
        //   [
        //     'key' => 'user_template',
        //     'value' => null,
        //     'description' => "This is a default Template for users",
        //   ],
        //   [
        //     'key' => 'task_template',
        //     'value' => null,
        //     'description' => "This is a default Template for tasks",
        //   ],
        //   [
        //     'key' => 'task_from_port_template',
        //     'value' => null,
        //     'description' => "This is a default Template for tasks From Port",
        //   ],
        //   [
        //     'key' => 'task_to_port_template',
        //     'value' => null,
        //     'description' => "This is a default Template for tasks To Port",
        //   ],
        //   [
        //     'key' => 'commission_type',
        //     'value' => 'rate',
        //     'description' => "Select The Commission Type The will bs upper to all drivers",
        //   ],
        //   [
        //     'key' => 'commission_rate',
        //     'value' => 15,
        //     'description' => "rate commission",
        //   ],
        //   [
        //     'key' => 'commission_fixed',
        //     'value' => 500,
        //     'description' => "fixed amount commission",
        //   ],
        //   [
        //     'key' => 'customs_clearance_template',
        //     'value' => null,
        //     'description' => "This is a default Template for Customs Clearances",
        //   ],
        //   [
        //     'key' => 'customs_clearance_agent_template',
        //     'value' => null,
        //     'description' => "This is a default Template for Customs Clearances Agents",
        //   ],
        //   [
        //     'key' => 'main_email',
        //     'value' => "osama.samomy@gmail.com",
        //     'description' => "This Main Email in the platform",
        //   ],


        //  [
        //     'key' => 'auto_distribution_enabled',
        //     'value' => 0,
        //     'description' => "Enable or disable automatic task distribution",
        //   ],
        //   [
        //     'key' => 'distribution_mode',
        //     'value' => 'sequential',
        //     'description' => "Mode of distribution: sequential (one by one) or broadcast (top 5)",
        //   ],
        //   [
        //     'key' => 'max_distribution_distance',
        //     'value' => 1000,
        //     'description' => "Maximum distance (meters) for finding suitable drivers",
        //   ],
          [
            'key' => 'min_driver_app_version',
            'value' => "1.0.0",
            'description' => "Minimum required version for the driver app",
          ],
          [
            'key' => 'driver_app_update_url',
            'value' => "https://play.google.com/store/apps",
            'description' => "URL to redirect drivers for app update",
          ],
        ];
        // $settings = [
        //   [
        //     'key' => 'min_customer_app_version',
        //     'value' => "1.0.0",
        //     'description' => "Minimum required version for the customer app",
        //   ],
        //   [
        //     'key' => 'driver_app_ios_update_url',
        //     'value' => "https://apps.apple.com/us/",
        //     'description' => "URL to redirect drivers for ios app update",
        //   ],
        //   [
        //     'key' => 'customer_app_update_url',
        //     'value' => "https://play.google.com/store/apps",
        //     'description' => "URL to redirect Customer for app update",
        //   ],
        //   [
        //     'key' => 'customer_app_ios_update_url',
        //     'value' => "https://apps.apple.com/us/",
        //     'description' => "URL to redirect Customer for ios app update",
        //   ],
        // ];

        // Settings::query()->delete();

        foreach ($settings as $setting) {
            Settings::create($setting);
        }
    }
}
