<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class TasksPaymentCancellationPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Find the 'Tasks Permissions' type
        $type = Permissions_Type::where('name', 'Tasks Permissions')->first();
        
        if (!$type) {
            $type = Permissions_Type::create([
                'name' => 'Tasks Permissions',
                'guard_name' => 'web'
            ]);
        }

        // 2. Create the permission if it doesn't exist
        $permission = Permission::updateOrCreate(
            ['name' => 'cancel_paid_tasks', 'guard_name' => 'web'],
            [
                'd_name' => 'Cancel Completed Payment (Reset)',
                'type_id' => $type->id
            ]
        );

        // 3. Assign to 'Owner' role
        $role = Role::where('name', 'Owner')->where('guard_name', 'web')->first();
        if ($role) {
            if (!$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
