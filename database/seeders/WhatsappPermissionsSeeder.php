<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Permissions_Type;
use Spatie\Permission\Models\Role;

class WhatsappPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // WhatsApp Templates
            [
                'name' => 'view_whatsapp_templates',
                'd_name' => 'View WhatsApp Templates',
            ],
            [
                'name' => 'edit_whatsapp_templates',
                'd_name' => 'Edit WhatsApp Templates',
            ],
            [
                'name' => 'delete_whatsapp_templates',
                'd_name' => 'Delete WhatsApp Templates',
            ],
            [
                'name' => 'sync_whatsapp_templates',
                'd_name' => 'Sync WhatsApp Templates (from Meta)',
            ],
            
            // WhatsApp Broadcast
            [
                'name' => 'view_whatsapp_broadcast',
                'd_name' => 'View WhatsApp Broadcast',
            ],
            [
                'name' => 'send_whatsapp_broadcast',
                'd_name' => 'Send WhatsApp Broadcast (Manual Messages)',
            ],
            
            // WhatsApp Logs
            [
                'name' => 'view_whatsapp_logs',
                'd_name' => 'View WhatsApp Logs',
            ],
            [
                'name' => 'delete_whatsapp_logs',
                'd_name' => 'Delete WhatsApp Logs',
            ],
        ];

        // Create the specific section (Permissions_Type)
        $type = Permissions_Type::firstOrCreate(
            ['name' => 'WhatsApp Permissions', 'guard_name' => 'web']
        );

        $role = Role::where('name', 'Owner')->first();

        foreach ($permissions as $permission) {
            // Create or update the permission
            $per = Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['d_name' => $permission['d_name'], 'type_id' => $type->id]
            );

            // Give permission to Owner
            if ($role) {
                $role->givePermissionTo($per);
            }
        }
    }
}
