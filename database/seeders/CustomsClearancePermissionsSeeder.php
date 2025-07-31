<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permissions_Type;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CustomsClearancePermissionsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $types = [
      [
        'name' =>  'Customs Clearance Permissions',
      ],
    ];

    $permissions = [

      [
        'name' => 'status_customs_clearances',
        'd_name' => 'Change Customs Clearances status',
        'slug' => 'Customs Clearance Permissions'
      ],
      [
        'name' => 'payment_customs_clearances',
        'd_name' => 'Pay Customs Clearances',
        'slug' => 'Customs Clearance Permissions'
      ],
      // [
      //   'name' => 'view_customs_clearances',
      //   'd_name' => 'View Customs Clearances',
      //   'slug' => 'Customs Clearance Permissions'
      // ],
      // [
      //   'name' => 'create_customs_clearances',
      //   'd_name' => 'Create Customs Clearances',
      //   'slug' => 'Customs Clearance Permissions'
      // ],
      // [
      //   'name' => 'edit_customs_clearances',
      //   'd_name' => 'Edit Customs Clearances',
      //   'slug' => 'Customs Clearance Permissions'
      // ],
      // [
      //   'name' => 'delete_customs_clearances',
      //   'd_name' => 'Delete Customs Clearances',
      //   'slug' => 'Customs Clearance Permissions'
      // ],
      // [
      //   'name' => 'close_customs_clearances',
      //   'd_name' => 'Close Customs Clearances',
      //   'slug' => 'Customs Clearance Permissions'
      // ],
      // [
      //   'name' => 'assign_customs_clearances',
      //   'd_name' => 'Assign Customs Clearances',
      //   'slug' => 'Customs Clearance Permissions'
      // ],
      // [
      //   'name' => 'manage_customs_clearances',
      //   'd_name' => 'Manage All Customs Clearances',
      //   'slug' => 'Customs Clearance Permissions'
      // ],
    ];

    $role = Role::where('name', 'Owner')->first();

    // foreach ($types as $key) {
    //   $type = Permissions_Type::create([
    //     'name' => $key['name'],
    //     'guard_name' => 'web'
    //   ]);
    // }

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
