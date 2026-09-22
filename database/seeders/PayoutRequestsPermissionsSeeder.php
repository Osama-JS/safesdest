<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class PayoutRequestsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get or create the permissions category/type
        $type = Permissions_Type::firstOrCreate(
            ['name' => 'Payout Requests Permissions'],
            ['guard_name' => 'web']
        );

        // 2. Define the new permissions
        $permissions = [
            [
                'name' => 'view_payout_requests',
                'd_name' => 'عرض طلبات الدفع عبر Payout',
            ],
            [
                'name' => 'approve_payout_requests',
                'd_name' => 'مصادقة وتنفيذ أو رفض طلبات الدفع عبر Payout',
            ],
        ];

        foreach ($permissions as $perm) {
            $permission = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'd_name' => $perm['d_name'],
                    'type_id' => $type->id,
                ]
            );

            // Assign to Owner role
            $roles = Role::whereIn('name', ['Owner', 'Super Admin', 'Admin'])->get();
            foreach ($roles as $role) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
