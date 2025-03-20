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
        'name' => 'create_admin',
        'd_name' => 'Create Admin',
        'slug' => 'admins_permissions'
      ],
      [
        'name' => 'update_admin',
        'd_name' => 'Update Admin',
        'slug' => 'admins_permissions'
      ],
      [
        'name' => 'delete_admin',
        'd_name' => 'Delete Admin',
        'slug' => 'admins_permissions'
      ],
      [
        'name' => 'view_admin',
        'd_name' => 'View Admin',
        'slug' => 'admins_permissions'
      ],
      [
        'name' => 'create_role',
        'd_name' => 'Create Role',
        'slug' => 'roles_permissions'
      ],
      [
        'name' => 'update_role',
        'd_name' => 'Update Role',
        'slug' => 'roles_permissions'
      ],
      [
        'name' => 'delete_role',
        'd_name' => 'Delete Role',
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
