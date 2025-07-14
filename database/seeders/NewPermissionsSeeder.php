<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Permissions_Type;
use Spatie\Permission\Models\Role;

class NewPermissionsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $permissions = [
      [
        'name' => 'wallet_teams',
        'd_name' => 'View Team Wallet',
        'slug' => 'Teams Permissions'
      ],
      [
        'name' => 'wallet_mange_teams',
        'd_name' => 'Mange Team Wallet',
        'slug' => 'Teams Permissions'
      ],

      [
        'name' => 'accept_offer_ads',
        'd_name' => 'Accept Ads Offer',
        'slug' => 'Tasks Ads Permissions'
      ],

      [
        'name' => 'close_ads',
        'd_name' => 'Close Ads',
        'slug' => 'Tasks Ads Permissions'
      ],
      [
        'name' => 'mange_ads',
        'd_name' => 'Mange All ads',
        'slug' => 'Tasks Ads Permissions'
      ],
    ];

    $role = Role::where('name', 'Owner')->first();
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
