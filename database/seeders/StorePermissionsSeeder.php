<?php

namespace Database\Seeders;

use App\Models\Permissions_Type;
use Faker\Provider\ar_EG\Person;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StorePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Store Permissions',
            ],
        ];

        $permissions = [
          [
              'name' => 'store_management',
              'd_name' => 'Store Management',
              'slug' => 'Store Permissions'
          ],
          [
              'name' => 'view_products',
              'd_name' => 'View Products',
              'slug' => 'Store Permissions'
          ],
          [
              'name' => 'store_products',
              'd_name' => 'Create & Edit Products',
              'slug' => 'Store Permissions'
          ],
          [
              'name' => 'delete_products',
              'd_name' => 'Delete Products',
              'slug' => 'Store Permissions'
          ],
          [
              'name' => 'status_products',
              'd_name' => 'Change Products Status',
              'slug' => 'Store Permissions'
          ],
          [
              'name' => 'view_product_details',
              'd_name' => 'View Product Details',
              'slug' => 'Store Permissions'
          ],
          [
              'name' => 'vehicles_products',
              'd_name' => 'Manage Products Vehicles',
              'slug' => 'Store Permissions'
          ],
           [
              'name' => 'pricing_products',
              'd_name' => 'Manage Products Pricing',
              'slug' => 'Store Permissions'
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
