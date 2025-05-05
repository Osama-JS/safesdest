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
