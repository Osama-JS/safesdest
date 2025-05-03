<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $this->call([
      PermissionsSeeder::class,
      AdminSeeder::class,
      PricingMethodsSeeder::class,
      SettingsSeeder::class,
      VehiclesSeeder::class,
    ]);
    // Customer::factory()->count(10)->create();
  }
}
