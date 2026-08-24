<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Permissions_Type;

class DebitInvestorCapitalPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get or create the permissions category/type
        $type = Permissions_Type::where('name', 'Investor Management')->first();
        if (!$type) {
            $type = Permissions_Type::create([
                'name' => 'Investor Management',
                'guard_name' => 'web'
            ]);
        }

        // 2. Define the new permission
        $permName = 'debit_investor_capital';
        $permDisplayName = 'خصم وسحب من رأس مال محفظة الاستثمار';

        $permission = Permission::where('name', $permName)->first();

        if (!$permission) {
            $permission = Permission::create([
                'name' => $permName,
                'd_name' => $permDisplayName,
                'guard_name' => 'web',
                'type_id' => $type->id
            ]);
        }

        // 3. Attach to Owner and Admin roles
        $roles = Role::whereIn('name', ['Owner', 'Admin', 'Super Admin'])->get();
        foreach ($roles as $role) {
            $role->givePermissionTo($permission);
        }
    }
}
