<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Permissions_Type;
use Spatie\Permission\Models\Role;

class RefundTasksPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
      [
        'name' => 'refund_tasks',
        'd_name' => 'Refund Task',
        'slug' => 'Tasks Permissions'
      ],
    ];

        $role = Role::where('name', 'Owner')->first();
        foreach ($permissions as $permission) {

            $type = Permissions_Type::where('name', $permission['slug'])->first();
            if (!$type) {
                continue;
            }

            $per = Permission::create([
              'name' => $permission['name'],
              'd_name' => $permission['d_name'],
              'guard_name' => 'web',
              'type_id' =>  $type->id
            ]);
            $role->givePermissionTo($per);
        }
    }
}
