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
          // 1) Base permissions & types (must run first)
          PermissionsSeeder::class,

          // 2) Owner role + admin user (depends on permissions)
          AdminSeeder::class,

          // 3) Additional permissions (depend on Owner role + base types)
          NewPermissionsSeeder::class,
          NewPermissions2Seeder::class,
          CustomsClearancePermissionsSeeder::class,
          UserCommisionSeeder::class,
          ReportsPermissionsSeeder::class,
          RefundTasksPermissionsSeeder::class,
          StorePermissionsSeeder::class,
          WithdrawalAndClaimsPermissionsSeeder::class,


          // 4) Settings & catalogs
          SettingsSeeder::class,
          DistributionSettingsSeeder::class,
          InternalSignaturesSettingSeeder::class,
          PricingMethodsSeeder::class,
          VehiclesSeeder::class,

          NotificationsPermissionsSeeder::class,
          PaymentRequestPermissionsSeeder::class,
          TasksPaymentCancellationPermissionSeeder::class,
          InvestorPermissionsSeeder::class,
          DebitInvestorCapitalPermissionSeeder::class,
          UserPaymentRequestPermissionsSeeder::class,
          MtahdPermissionsSeeder::class,
          ForceUpdateTasksPermissionSeeder::class,
        ]);

        Customer::factory()->count(10)->create();
    }
}
