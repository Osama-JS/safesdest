<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class InvestorWithdrawalPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. إنشاء أو جلب تصنيف الصلاحيات
        $type = Permissions_Type::firstOrCreate(
            ['name' => 'Investor Withdrawals', 'guard_name' => 'web']
        );

        // 2. تعريف الصلاحيات الخاصة بطلبات سحب المستثمرين
        $permissions = [
            [
                'name'   => 'view_investor_withdrawals',
                'd_name' => 'عرض طلبات سحب المستثمرين',
            ],
            [
                'name'   => 'manage_investor_withdrawals',
                'd_name' => 'إدارة ومعالجة طلبات سحب المستثمرين',
            ],
            [
                'name'   => 'approve_investor_withdrawals',
                'd_name' => 'الموافقة على طلبات سحب المستثمرين ورفع الإيصالات',
            ],
            [
                'name'   => 'reject_investor_withdrawals',
                'd_name' => 'رفض طلبات سحب المستثمرين وتحديد السبب',
            ],
        ];

        // 3. الأدوار التي ستُمنح الصلاحيات افتراضياً
        $roles = Role::whereIn('name', ['Owner', 'Admin', 'Super Admin'])->get();

        foreach ($permissions as $permData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permData['name'], 'guard_name' => 'web'],
                [
                    'd_name'  => $permData['d_name'],
                    'type_id' => $type->id,
                ]
            );

            // إسناد الصلاحية للأدوار الإدارية
            foreach ($roles as $role) {
                if (!$role->hasPermissionTo($permission->name)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}
