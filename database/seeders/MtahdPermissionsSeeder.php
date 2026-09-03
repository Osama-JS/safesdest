<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class MtahdPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get or create the permissions category/type
        $type = Permissions_Type::where('name', 'Mtahd Escrow')->first();
        if (!$type) {
            $type = Permissions_Type::where('name', 'Wallets')->first();
        }
        if (!$type) {
            $type = Permissions_Type::create([
                'name' => 'Mtahd Escrow',
                'guard_name' => 'web'
            ]);
        }

        // 2. Define the permissions
        $permissions = [
            [
                'name' => 'view_mtahd_deal_logs',
                'd_name' => 'عرض سجلات عمليات متعهد',
            ],
            [
                'name' => 'manage_mtahd_deals',
                'd_name' => 'إدارة صفقات متعهد (تحرير/إلغاء/مزامنة)',
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

            // Assign to Owner role
            $roles = Role::whereIn('name', ['Owner', 'Admin'])->get();
            foreach ($roles as $role) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
