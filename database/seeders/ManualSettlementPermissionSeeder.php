<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class ManualSettlementPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get the permissions category/type
        $type = Permissions_Type::where('name', 'Investor Management')->first();
        if (!$type) {
            $type = Permissions_Type::create([
                'name' => 'Investor Management',
                'guard_name' => 'web'
            ]);
        }

        // 2. Define the new permission
        $permName = 'manual_investment_settlement';
        $permDisplayName = 'إجراء التسوية اليدوية للمستثمرين';

        $permission = Permission::where('name', $permName)->first();

        if (!$permission) {
            $permission = Permission::create([
                'name' => $permName,
                'd_name' => $permDisplayName,
                'guard_name' => 'web',
                'type_id' => $type->id
            ]);
        }

        // 3. Attach to Owner role
        $ownerRole = Role::where('name', 'Owner')->first();
        if ($ownerRole) {
            $ownerRole->givePermissionTo($permission);
        }
    }
}
