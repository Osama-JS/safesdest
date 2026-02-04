<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Permissions_Type;
use Spatie\Permission\Models\Role;

class NotificationsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create the Admins Permissions type
        $type = Permissions_Type::where('name', 'Admins Permissions')->first();

        if (!$type) {
            $type = Permissions_Type::create([
                'name' => 'Admins Permissions',
                'guard_name' => 'web'
            ]);
        }

        // Create the view_notifications permission
        $permission = Permission::where('name', 'view_notifications')->first();

        if (!$permission) {
            Permission::create([
                'name' => 'view_notifications',
                'd_name' => 'View Admin Notifications',
                'guard_name' => 'web',
                'type_id' => $type->id
            ]);
        }

        $role = Role::where('name', 'Owner')->first();
        $role->givePermissionTo($permission);
    }
}
