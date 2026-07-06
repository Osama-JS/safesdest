<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class ActivityLogPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Create or get Permission Type
        $type = Permissions_Type::firstOrCreate([
            'name' => 'System Logs Permissions',
        ], [
            'guard_name' => 'web'
        ]);

        // 2. Define the permissions for Activity Logs
        $permissions = [
            [
                'name' => 'view_activity_logs',
                'd_name' => 'View Activity Logs',
                'type_id' => $type->id,
                'guard_name' => 'web'
            ],
        ];

        // 3. Create the permissions
        $createdPermissions = [];
        foreach ($permissions as $permData) {
            $createdPermissions[] = Permission::firstOrCreate(
                ['name' => $permData['name']],
                $permData
            );
        }

        // 4. Get the owner role
        $ownerRole = Role::where('name', 'Owner')->first();

        // 5. Assign permissions to the owner role
        if ($ownerRole) {
            $ownerRole->givePermissionTo(collect($createdPermissions)->pluck('name')->toArray());
            $this->command->info('Activity Logs permissions assigned to Owner role successfully.');
        } else {
            $this->command->error('Owner role not found!');
        }
    }
}
