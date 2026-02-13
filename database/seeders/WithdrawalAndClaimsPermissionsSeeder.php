<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class WithdrawalAndClaimsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Define Types
        $types = [
            [
                'name' => 'Withdrawals Permissions',
                'slug' => 'withdrawals_permissions',
            ],
            [
                'name' => 'Task Claims Permissions',
                'slug' => 'claims_permissions',
            ],
        ];

        foreach ($types as $typeData) {
            Permissions_Type::firstOrCreate(
                ['slug' => $typeData['slug']], // Search by slug
                ['name' => $typeData['name'], 'guard_name' => 'web']
            );
        }

        // Get IDs for relationship
        $withdrawalType = Permissions_Type::where('slug', 'withdrawals_permissions')->first();
        $claimsType = Permissions_Type::where('slug', 'claims_permissions')->first();

        // 2. Define Permissions
        $permissions = [
            // Withdrawals
            [
                'name' => 'view_withdrawal_requests',
                'd_name' => 'View Withdrawal Requests',
                'type_id' => $withdrawalType->id,
            ],
            [
                'name' => 'process_withdrawal_requests',
                'd_name' => 'Process Withdrawal Requests',
                'type_id' => $withdrawalType->id,
            ],
            // Task Claims
            [
                'name' => 'view_task_claims',
                'd_name' => 'View Task Claims',
                'type_id' => $claimsType->id,
            ],
            [
                'name' => 'manage_task_claims', // Used for approving/rejecting
                'd_name' => 'Manage Task Claims',
                'type_id' => $claimsType->id,
            ],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                ['d_name' => $perm['d_name'], 'type_id' => $perm['type_id']]
            );
        }

        // 3. Assign to Owner
        $role = Role::where('name', 'Owner')->where('guard_name', 'web')->first();

        if ($role) {
            foreach ($permissions as $perm) {
                $role->givePermissionTo($perm['name']);
            }
            $this->command->info('Permissions assigned to Owner role.');
        } else {
            $this->command->error('Owner role not found.');
        }
    }
}
