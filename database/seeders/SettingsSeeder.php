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
      [
        'key' => 'customer_template',
        'value' => null,
        'description' => "This is a default Template form customers",
      ],
      [
        'key' => 'driver_template',
        'value' => null,
        'description' => "This is a default Template form drivers",
      ],
      [
        'key' => 'user_template',
        'value' => null,
        'description' => "This is a default Template form users",
      ],
      [
        'key' => 'task_template',
        'value' => null,
        'description' => "This is a default Template form tasks",
      ],
    ];

    foreach ($settings as $setting) {
      Settings::create($setting);
    }
  }
}
