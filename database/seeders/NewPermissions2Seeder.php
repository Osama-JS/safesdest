<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Permissions_Type;
use Spatie\Permission\Models\Role;

class NewPermissions2Seeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $types = [
      [
        'name' =>  'Statistics Permissions',
      ],
    ];

    $permissions = [
      [
        'name' => 'backups_settings',
        'd_name' => 'Manage Backups',
        'slug' => 'Settings Permissions'
      ],
      [
        'name' => 'view_statistics',
        'd_name' => 'View System Statistics',
        'slug' => 'Statistics Permissions'
      ],
    ];

    $role = Role::where('name', 'Owner')->first();

    foreach ($types as $key) {
      $type = Permissions_Type::create([
        'name' => $key['name'],
        'guard_name' => 'web'
      ]);
    }

    foreach ($permissions as $permission) {
      $type = Permissions_Type::where('name', $permission['slug'])->first();
      if (!$type) {
        continue;
      }

      $per = Permission::create([
        'name' => $permission['name'],
        'd_name' => $permission['d_name'],
        'guard_name' => 'web',
        'type_id' =>  $type->id
      ]);
      $role->givePermissionTo($per);
    }
  }
}
