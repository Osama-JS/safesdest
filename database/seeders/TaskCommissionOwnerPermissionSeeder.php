<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\Permissions_Type;
use App\Models\User;

class TaskCommissionOwnerPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates the 'view_task_commissions' and 'view_task_total_price' permissions
     * and guarantees they are assigned to the 'Owner' and 'Admin' roles and users.
     */
    public function run(): void
    {
        // 1. Clear Spatie Permission cache first
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Find or create the 'Tasks Permissions' type
        $type = Permissions_Type::where('name', 'Tasks Permissions')
            ->orWhere('name', 'Tasks')
            ->first();

        if (!$type) {
            $type = Permissions_Type::firstOrCreate(
                ['name' => 'Tasks Permissions'],
                ['guard_name' => 'web']
            );
        }

        // 3. Define permissions to ensure exist and link
        $permissionsList = [
            [
                'name'   => 'view_task_commissions',
                'd_name' => 'عرض عمولات المهام والوسطاء (View Task Commissions)',
            ],
            [
                'name'   => 'view_task_total_price',
                'd_name' => 'عرض إجمالي سعر المهمة (View Task Total Price)',
            ],
        ];

        $createdPermissions = [];
        foreach ($permissionsList as $permData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permData['name'], 'guard_name' => 'web'],
                [
                    'd_name'  => $permData['d_name'],
                    'type_id' => $type ? $type->id : null,
                ]
            );

            // Update d_name and type_id if empty
            if (empty($permission->d_name) || empty($permission->type_id)) {
                $permission->d_name = $permData['d_name'];
                if ($type) {
                    $permission->type_id = $type->id;
                }
                $permission->save();
            }

            $createdPermissions[] = $permission;
            $this->command?->info("Permission ensured: {$permission->name}");
        }

        // 4. Find all Owner / Admin roles (case-insensitive)
        $roles = Role::whereIn('name', ['Owner', 'owner', 'Admin', 'admin', 'Super Admin', 'super admin'])->get();

        // If no Owner role exists at all, create it
        if ($roles->isEmpty()) {
            $roles = collect([Role::firstOrCreate(['name' => 'Owner', 'guard_name' => 'web'])]);
        }

        foreach ($roles as $role) {
            foreach ($createdPermissions as $perm) {
                if (!$role->hasPermissionTo($perm)) {
                    $role->givePermissionTo($perm);
                    $this->command?->info("Assigned permission '{$perm->name}' to role '{$role->name}'");
                }
            }
        }

        // 5. Also explicitly assign permissions directly to all Owner/Admin users
        $roleIds = $roles->pluck('id')->toArray();
        $adminUsers = User::whereIn('role_id', $roleIds)
            ->orWhereHas('roles', function ($q) use ($roleIds) {
                $q->whereIn('id', $roleIds);
            })
            ->orWhere('email', 'like', '%admin%')
            ->get();

        foreach ($adminUsers as $adminUser) {
            foreach ($createdPermissions as $perm) {
                try {
                    if (!$adminUser->hasDirectPermission($perm) && !$adminUser->hasPermissionTo($perm)) {
                        $adminUser->givePermissionTo($perm);
                    }
                } catch (\Throwable $e) {
                    // Ignore if already assigned or table constraint
                }
            }
            $this->command?->info("Permissions verified for user: {$adminUser->email} (#{$adminUser->id})");
        }

        // 6. Clear Spatie Permission cache once again
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info("Task commissions permissions successfully linked to Owner role and users!");
    }
}
