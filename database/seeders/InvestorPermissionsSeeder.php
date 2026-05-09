<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class InvestorPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 0. Create Investor Role if not exists
        $investorRole = Role::where('name', 'Investor')->first();
        if (!$investorRole) {
            $investorRole = Role::create(['name' => 'Investor', 'guard_name' => 'web']);
        }

        // 1. Create or get the permissions category/type
        $type = Permissions_Type::where('name', 'Investor Management')->first();
        if (!$type) {
            $type = Permissions_Type::create([
                'name' => 'Investor Management',
                'guard_name' => 'web'
            ]);
        }

        // 2. Define the permissions to be created
        $permissions = [
            [
                'name' => 'view_investors',
                'd_name' => 'عرض قائمة المستثمرين',
            ],
            [
                'name' => 'save_investors',
                'd_name' => 'إضافة وتعديل المستثمرين',
            ],
            [
                'name' => 'delete_investors',
                'd_name' => 'حذف المستثمرين',
            ],
        ];

        $ownerRole = Role::where('name', 'Owner')->first();

        foreach ($permissions as $perm) {
            $permission = Permission::where('name', $perm['name'])->first();

            if (!$permission) {
                $permission = Permission::create([
                    'name' => $perm['name'],
                    'd_name' => $perm['d_name'],
                    'guard_name' => 'web',
                    'type_id' => $type->id
                ]);
            }

            // 3. Attach to owner role if it exists
            if ($ownerRole) {
                $ownerRole->givePermissionTo($permission);
            }
        }
    }
}
