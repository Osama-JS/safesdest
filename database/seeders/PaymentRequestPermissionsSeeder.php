<?php

namespace Database\Seeders;

use App\Models\Permissions_Type;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PaymentRequestPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Payment Request Permissions',
            ],
        ];

        $permissions = [
            [
                'name' => 'view_payment_requests_logs',
                'd_name' => 'View Payment Request Logs',
                'slug' => 'Payment Request Permissions'
            ],
            [
                'name' => 'generate_payment_request',
                'd_name' => 'Generate Payment Request',
                'slug' => 'Payment Request Permissions'
            ],
        ];

        $role = Role::where('name', 'Owner')->first();

        // Create permission type if it doesn't exist
        foreach ($types as $key) {
            $existingType = Permissions_Type::where('name', $key['name'])->first();
            if (!$existingType) {
                $type = Permissions_Type::create([
                    'name' => $key['name'],
                    'guard_name' => 'web'
                ]);
            }
        }

        // Create permissions
        foreach ($permissions as $permission) {
            $type = Permissions_Type::where('name', $permission['slug'])->first();
            if (!$type) {
                continue;
            }

            // Check if permission already exists
            $existingPermission = Permission::where('name', $permission['name'])->first();
            if ($existingPermission) {
                continue;
            }

            $per = Permission::create([
                'name' => $permission['name'],
                'd_name' => $permission['d_name'],
                'guard_name' => 'web',
                'type_id' => $type->id
            ]);

            // Give permission to Owner role
            if ($role) {
                $role->givePermissionTo($per);
            }
        }
    }
}
