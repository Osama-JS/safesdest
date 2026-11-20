<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class UserCommissionWalletPermissionSeeder extends Seeder
{
    /**
     * تشغيل بذر الصلاحية لمحفظة عمولاتي الشخصية للمستخدم
     */
    public function run(): void
    {
        // 1. تحديد أو إنشاء تصنيف الصلاحية
        $type = Permissions_Type::where('name', 'Beneficiaries Wallets')->first()
            ?? Permissions_Type::where('name', 'Beneficiaries Permissions')->first()
            ?? Permissions_Type::where('name', 'Wallets Permissions')->first();

        if (!$type) {
            $type = Permissions_Type::create([
                'name' => 'Beneficiaries Wallets',
                'guard_name' => 'web',
            ]);
        }

        // 2. إنشاء أو تحديث الصلاحية
        $permission = Permission::updateOrCreate(
            [
                'name'       => 'access_my_commission_wallet',
                'guard_name' => 'web',
            ],
            [
                'd_name'  => 'الوصول الى محفظة عمولاتي',
                'type_id' => $type->id,
            ]
        );

        // 3. إسناد الصلاحية للأدوار الرئيسية والأدوار المستفيدة من العمولات (الوسطاء، المدراء، المالك)
        $roles = Role::whereIn('name', ['Owner', 'Admin', 'admin', 'Super Admin', 'Broker'])->get();
        foreach ($roles as $role) {
            if (!$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }
}
