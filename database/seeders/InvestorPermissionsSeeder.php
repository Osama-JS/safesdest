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
            [
                'name' => 'view_investors_audit',
                'd_name' => 'عرض تدقيق ومطابقة محافظ المستثمرين والمهام',
            ],
            [
                'name' => 'edit_investors_audit_funding',
                'd_name' => 'تعديل مبالغ تمويل المهام في المطابقة والتدقيق',
            ],
            [
                'name' => 'delete_investors_audit_transaction',
                'd_name' => 'حذف عمليات الاسترداد المعلقة في المطابقة والتدقيق',
            ],
            [
                'name' => 'settle_investors_audit_canceled_task',
                'd_name' => 'تسوية المهام الملغاة في المطابقة والتدقيق',
            ],
            [
                'name' => 'settle_investors_audit_commission',
                'd_name' => 'تسوية عمولات المهام في المطابقة والتدقيق',
            ],
        ];

        $ownerRole = Role::where('name', 'Owner')->first();
        $adminRole = Role::where('name', 'admin')->first();

        foreach ($permissions as $perm) {
            $permission = Permission::where('name', $perm['name'])->first();

            if (!$permission) {
                $permission = Permission::create([
                    'name' => $perm['name'],
                    'd_name' => $perm['d_name'],
                    'guard_name' => 'web',
                    'type_id' => $type->id
                ]);
            } else {
                $permission->update(['d_name' => $perm['d_name']]);
            }

            // 3. Attach to owner and admin role if they exist
            if ($ownerRole) {
                $ownerRole->givePermissionTo($permission);
            }
            if ($adminRole) {
                $adminRole->givePermissionTo($permission);
            }
        }
    }
}
