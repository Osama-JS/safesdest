<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\Models\Permissions_Type;
use Spatie\Permission\Models\Role;

class WhatsappChatPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'name' => 'view_whatsapp_chat',
                'd_name' => 'View WhatsApp Chat (عرض واستخدام شات الواتساب)',
            ],
            [
                'name' => 'send_whatsapp_chat',
                'd_name' => 'Send WhatsApp Chat Messages (إرسال رسائل شات الواتساب)',
            ],
        ];

        // Ensure the specific section (Permissions_Type) exists
        $type = Permissions_Type::firstOrCreate(
            ['name' => 'WhatsApp Permissions', 'guard_name' => 'web']
        );

        $role = Role::where('name', 'Owner')->first();

        foreach ($permissions as $permission) {
            $per = Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['d_name' => $permission['d_name'], 'type_id' => $type->id]
            );

            // Grant to Owner role
            if ($role) {
                $role->givePermissionTo($per);
            }
        }
    }
}
