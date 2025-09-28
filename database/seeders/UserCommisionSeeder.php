<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Permissions_Type;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class UserCommisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Beneficiaries Permissions',
            ],
        ];

        $permissions = [
          [
              'name' => 'view_beneficiaries',
              'd_name' => 'View Beneficiaries',
              'slug' => 'Beneficiaries Permissions'
          ],
          [
              'name' => 'manage_beneficiaries',
              'd_name' => 'Manage Beneficiaries',
              'slug' => 'Beneficiaries Permissions'
          ],
          [
              'name' => 'view_beneficiaries_wallet',
              'd_name' => 'View Beneficiaries Wallet',
              'slug' => 'Beneficiaries Permissions'
          ],
          [
              'name' => 'transaction_beneficiaries_wallet',
              'd_name' => 'Manage Beneficiaries Wallet',
              'slug' => 'Beneficiaries Permissions'
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
