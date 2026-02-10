<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class InternalSignaturesSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Settings::updateOrCreate(
            ['key' => 'internal_signatures_enabled'],
            [
                'value' => '0',
                'name' => 'تفعيل التواقيع الداخلية',
                'description' => 'تفعيل عرض التواقيع المخزنة في المنصة للسائق والعميل في بوليصات المهام والتقارير.',
                'type' => 'boolean',
                'category' => 'general'
            ]
        );
    }
}
