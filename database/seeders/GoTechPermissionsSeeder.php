<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class GoTechPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get or create the permissions category/type
        $type = Permissions_Type::firstOrCreate(
            ['name' => 'Go-Tech Integration'],
            ['guard_name' => 'web']
        );

        // 2. Define dedicated Go-Tech permissions
        $permissions = [
            [
                'name' => 'manage_gotech_settings',
                'd_name' => 'إدارة إعدادات وربط بوابة Go-Tech للتمويل',
            ],
            [
                'name' => 'submit_tasks_gotech',
                'd_name' => 'طرح وإرسال المهام إلى بوابة Go-Tech للاستثمار',
            ],
            [
                'name' => 'cancel_tasks_gotech',
                'd_name' => 'فك ارتباط وإلغاء طرح المهام في Go-Tech وسحبها للمستثمرين الداخليين',
            ],
        ];

        foreach ($permissions as $perm) {
            $permission = Permission::updateOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'd_name'  => $perm['d_name'],
                    'type_id' => $type->id,
                ]
            );

            // Assign to Owner, Admin, Super Admin roles
            $roles = Role::whereIn('name', ['Owner', 'Admin', 'admin', 'Super Admin'])->get();
            foreach ($roles as $role) {
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
