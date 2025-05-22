<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Permissions_Type;


class PermissionsSeeder extends Seeder
{
  public function run(): void
  {
    $types = [
      [
        'name' =>  'Customers Permissions',
        'slug' => 'customers_permissions',
      ],
      [
        'name' =>  'Drivers Permissions',
        'slug' => 'drivers_permissions',
      ],
      [
        'name' =>  'Teams Permissions',
        'slug' => 'teams_permissions',
      ],
      [
        'name' =>  'Wallets Permissions',
        'slug' => 'wallets_permissions',
      ],
      [
        'name' =>  'Admins Permissions',
        'slug' => 'admins_permissions',
      ],
      [
        'name' =>  'Roles Permissions',
        'slug' => 'roles_permissions',
      ],
      [
        'name' =>  'Settings Permissions',
        'slug' => 'settings_permissions',
      ]
    ];
    $permissions = [

      [
        'name' => 'view_customers',
        'd_name' => 'View Customers',
        'slug' => 'customers_permissions'
      ],
      [
        'name' => 'save_customers',
        'd_name' => 'Create & Edit Customers',
        'slug' => 'customers_permissions'
      ],
      [
        'name' => 'status_customers',
        'd_name' => 'Edit Customers Status',
        'slug' => 'customers_permissions'
      ],
      [
        'name' => 'delete_customers',
        'd_name' => 'Delete Customers',
        'slug' => 'customers_permissions'
      ],
      [
        'name' => 'profile_customers',
        'd_name' => 'View Customer Profile',
        'slug' => 'customers_permissions'
      ],
      [
        'name' => 'wallet_customers',
        'd_name' => 'View Customer Wallet',
        'slug' => 'customers_permissions'
      ],
      [
        'name' => 'mange_wallet_customers',
        'd_name' => 'Mange Customer Wallet',
        'slug' => 'customers_permissions'
      ],
      [
        'name' => 'task_customers',
        'd_name' => 'Create Tasks for Customers',
        'slug' => 'customers_permissions'
      ],
      [
        'name' => 'mange_customers',
        'd_name' => 'Mange All Customers',
        'slug' => 'customers_permissions'
      ],

      [
        'name' => 'view_drivers',
        'd_name' => 'View Drivers',
        'slug' => 'drivers_permissions'
      ],
      [
        'name' => 'save_drivers',
        'd_name' => 'Create & Edit Drivers',
        'slug' => 'drivers_permissions'
      ],
      [
        'name' => 'status_drivers',
        'd_name' => 'Edit Drivers Status',
        'slug' => 'drivers_permissions'
      ],
      [
        'name' => 'delete_drivers',
        'd_name' => 'Delete Drivers',
        'slug' => 'drivers_permissions'
      ],
      [
        'name' => 'profile_drivers',
        'd_name' => 'View Driver Profile',
        'slug' => 'drivers_permissions'
      ],
      [
        'name' => 'wallet_drivers',
        'd_name' => 'View Driver Wallet',
        'slug' => 'drivers_permissions'
      ],
      [
        'name' => 'manage_wallet_drivers',
        'd_name' => 'Mange Drivers Wallet',
        'slug' => 'drivers_permissions'
      ],
      [
        'name' => 'mange_drivers',
        'd_name' => 'Mange All Drivers',
        'slug' => 'drivers_permissions'
      ],

      [
        'name' => 'view_teams',
        'd_name' => 'View Teams',
        'slug' => 'teams_permissions'
      ],
      [
        'name' => 'save_teams',
        'd_name' => 'Create & Edit Teams',
        'slug' => 'teams_permissions'
      ],
      [
        'name' => 'delete_teams',
        'd_name' => 'Delete Teams',
        'slug' => 'teams_permissions'
      ],
      [
        'name' => 'details_teams',
        'd_name' => 'View Team Details',
        'slug' => 'teams_permissions'
      ],
      [
        'name' => 'mange_teams',
        'd_name' => 'Mange All Teams',
        'slug' => 'teams_permissions'
      ],

      [
        'name' => 'view_wallets',
        'd_name' => 'View Wallets',
        'slug' => 'wallets_permissions'
      ],
      [
        'name' => 'save_wallets',
        'd_name' => 'Update & edit Wallets',
        'slug' => 'wallets_permissions'
      ],
      [
        'name' => 'details_wallets',
        'd_name' => 'View Wallet Details',
        'slug' => 'wallets_permissions'
      ],
      [
        'name' => 'transaction_wallets',
        'd_name' => 'Manage Wallet Transactions',
        'slug' => 'wallets_permissions'
      ],
      [
        'name' => 'mange_wallets',
        'd_name' => 'Mange All Wallets',
        'slug' => 'wallets_permissions'
      ],




      [
        'name' => 'view_admins',
        'd_name' => 'View Admins',
        'slug' => 'admins_permissions'
      ],
      [
        'name' => 'save_admins',
        'd_name' => 'Create & Edit Admins',
        'slug' => 'admins_permissions'
      ],
      [
        'name' => 'status_admins',
        'd_name' => 'Edit Admins Status',
        'slug' => 'admins_permissions'
      ],
      [
        'name' => 'delete_admins',
        'd_name' => 'Delete Admins',
        'slug' => 'admins_permissions'
      ],
      [
        'name' => 'profile_admin',
        'd_name' => 'View Admin Profile',
        'slug' => 'admins_permissions'
      ],
      [
        'name' => 'view_roles',
        'd_name' => 'View Roles',
        'slug' => 'roles_permissions'
      ],
      [
        'name' => 'save_roles',
        'd_name' => 'Create & Update Roles',
        'slug' => 'roles_permissions'
      ],
      [
        'name' => 'delete_roles',
        'd_name' => 'Delete Roles',
        'slug' => 'roles_permissions'
      ],

      [
        'name' => 'view_setting_list',
        'd_name' => 'View Setting list',
        'slug' => 'settings_permissions'
      ],
      [
        'name' => 'general_settings',
        'd_name' => 'Manage General Settings',
        'slug' => 'settings_permissions'
      ],
      [
        'name' => 'vehicles_settings',
        'd_name' => 'Manage Vehicles',
        'slug' => 'settings_permissions'
      ],
      [
        'name' => 'tags_settings',
        'd_name' => 'Manage Tags',
        'slug' => 'settings_permissions'
      ],
      [
        'name' => 'geo_fence_settings',
        'd_name' => 'Manage Geo-fences',
        'slug' => 'settings_permissions'
      ],
      [
        'name' => 'points_settings',
        'd_name' => 'Manage Points',
        'slug' => 'settings_permissions'
      ],
      [
        'name' => 'blockages_settings',
        'd_name' => 'Manage Blockages',
        'slug' => 'settings_permissions'
      ],
      [
        'name' => 'pricing_methods_settings',
        'd_name' => 'Manage Pricing Methods',
        'slug' => 'settings_permissions'
      ],
      [
        'name' => 'templates_settings',
        'd_name' => 'Manage Templates',
        'slug' => 'settings_permissions'
      ],

    ];

    Permission::query()->delete();
    Permissions_Type::query()->delete();

    foreach ($types as $key) {
      $type = Permissions_Type::create([
        'name' => $key['name'],
        'guard_name' => 'web'
      ]);
      foreach ($permissions as $permission) {
        if ($permission['slug'] !== $key['slug']) {
          continue;
        }
        Permission::create([
          'name' => $permission['name'],
          'd_name' => $permission['d_name'],
          'guard_name' => 'web',
          'type_id' =>  $type->id
        ]);
      }
    }
  }
}
