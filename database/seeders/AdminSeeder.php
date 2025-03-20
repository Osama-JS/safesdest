<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    User::query()->delete();
    Role::query()->delete();

    $role = Role::create(['name' => 'Owner', 'guard_name' => 'web']);
    $permissions = Permission::where('guard_name', 'web')->pluck('id', 'id')->all();

    $user = User::create([
      'name' => 'Admin',
      'email' => 'admin@mail.com',
      'phone' => '00967777958051',
      'password' => bcrypt('admin@123'),
      'status' => 'active',
      'reset_password' => 0,
      'role_id' => $role->id,
    ]);

    $role->givePermissionTo($permissions);
    $user->assignRole([$role->id]);
  }
}
