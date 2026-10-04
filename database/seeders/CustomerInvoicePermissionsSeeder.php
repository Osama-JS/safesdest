<?php

namespace Database\Seeders;

use App\Models\Permissions_Type;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CustomerInvoicePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Customer Invoices Permissions',
            ],
        ];

        $permissions = [
            [
                'name' => 'view_customer_invoices',
                'd_name' => 'View Customer Invoices',
                'slug' => 'Customer Invoices Permissions'
            ],
            [
                'name' => 'create_customer_invoices',
                'd_name' => 'Create Customer Invoices',
                'slug' => 'Customer Invoices Permissions'
            ],
            [
                'name' => 'edit_customer_invoices',
                'd_name' => 'Edit Customer Invoices',
                'slug' => 'Customer Invoices Permissions'
            ],
            [
                'name' => 'pay_customer_invoices',
                'd_name' => 'Pay Customer Invoices',
                'slug' => 'Customer Invoices Permissions'
            ],
            [
                'name' => 'approve_customer_invoices',
                'd_name' => 'Approve Customer Invoices',
                'slug' => 'Customer Invoices Permissions'
            ],
            [
                'name' => 'cancel_customer_invoices',
                'd_name' => 'Cancel Customer Invoices',
                'slug' => 'Customer Invoices Permissions'
            ],
        ];

        // Create permission type if it doesn't exist
        foreach ($types as $key) {
            $existingType = Permissions_Type::where('name', $key['name'])->first();
            if (!$existingType) {
                Permissions_Type::create([
                    'name' => $key['name'],
                    'guard_name' => 'web'
                ]);
            }
        }

        $roles = Role::whereIn('name', ['Owner', 'Admin', 'admin'])->get();

        // Create permissions
        foreach ($permissions as $permission) {
            $type = Permissions_Type::where('name', $permission['slug'])->first();
            if (!$type) {
                continue;
            }

            // Check if permission already exists
            $existingPermission = Permission::where('name', $permission['name'])->first();
            if ($existingPermission) {
                $per = $existingPermission;
            } else {
                $per = Permission::create([
                    'name' => $permission['name'],
                    'd_name' => $permission['d_name'],
                    'guard_name' => 'web',
                    'type_id' => $type->id,
                ]);
            }

            foreach ($roles as $role) {
                if (!$role->hasPermissionTo($per)) {
                    $role->givePermissionTo($per);
                }
            }
        }
    }
}
