<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class UserPaymentRequestPermissionsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // 1. Get or create the permissions category/type
    $type = Permissions_Type::where('name', 'Beneficiaries Wallets')->first();
    if (!$type) {
      $type = Permissions_Type::where('name', 'Wallets')->first();
    }
    if (!$type) {
      $type = Permissions_Type::create([
        'name' => 'Beneficiaries Wallets',
        'guard_name' => 'web'
      ]);
    }

    // 2. Define the new permissions
    $permissions = [
      [
        'name' => 'generate_user_payment_request',
        'd_name' => 'إنشاء وطباعة طلب سداد لمحفظة المستخدم',
      ],
      [
        'name' => 'view_user_payment_requests_logs',
        'd_name' => 'عرض سجلات طلبات السداد لمحفظة المستخدم',
      ],
    ];

    foreach ($permissions as $perm) {
      $permission = Permission::where('name', $perm['name'])->first();

      if (!$permission) {
        $permission = Permission::create([
          'name' => $perm['name'],
          'd_name' => $perm['d_name'],
          'guard_name' => 'web',
          'type_id' => $type->id
        ]);
      }

      // Assign to roles
      $roles = Role::whereIn('name', ['Owner'])->get();
      foreach ($roles as $role) {
        $role->givePermissionTo($permission);
      }
    }
  }
}
