<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class TaskCommissionViewPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Adds the 'view_task_commissions' permission under 'Tasks Permissions'.
     * Assigns it to the 'Owner' role automatically.
     */
    public function run(): void
    {
        // 1. Find or create the 'Tasks Permissions' type
        $type = Permissions_Type::where('name', 'Tasks Permissions')->first();

        if (!$type) {
            $type = Permissions_Type::create([
                'name'       => 'Tasks Permissions',
                'guard_name' => 'web',
            ]);
        }

        // 2. Create the permission if it doesn't exist
        $permission = Permission::updateOrCreate(
            ['name' => 'view_task_commissions', 'guard_name' => 'web'],
            [
                'd_name'  => 'View Task Commissions',
                'type_id' => $type->id,
            ]
        );

        // 3. Assign to 'Owner' role (super admin)
        $role = Role::where('name', 'Owner')->where('guard_name', 'web')->first();
        if ($role && !$role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
    }
}
