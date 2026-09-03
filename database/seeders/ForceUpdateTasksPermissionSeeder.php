<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class ForceUpdateTasksPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get or create permission type
        $type = Permissions_Type::where('name', 'Tasks Permissions')->orWhere('name', 'Tasks')->first();
        if (!$type) {
            $type = Permissions_Type::first();
        }

        // 2. Permission definition
        $permData = [
            'name' => 'force_update_tasks',
            'd_name' => 'التعديل الإجباري للمهام',
        ];

        $ownerRole = Role::where('name', 'Owner')->first();

        $permission = Permission::where('name', $permData['name'])->first();
        if (!$permission) {
            $permission = Permission::create([
                'name' => $permData['name'],
                'd_name' => $permData['d_name'],
                'guard_name' => 'web',
                'type_id' => $type ? $type->id : null
            ]);
        }

        if ($ownerRole && $permission) {
            $ownerRole->givePermissionTo($permission);
        }
    }
}
