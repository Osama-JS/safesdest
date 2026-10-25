<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bank;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banks = [
            ['name' => 'البنك الأهلي السعودي', 'code' => 'NCBKSAJE'],
            ['name' => 'بنك الراجحي', 'code' => 'RJHISARI'],
            ['name' => 'بنك الرياض', 'code' => 'RIBLSARI'],
            ['name' => 'بنك الانماء', 'code' => 'INMASARI'],
            ['name' => 'البنك الاول', 'code' => 'SABBSARI'],
            ['name' => 'البنك العربي الوطني', 'code' => 'ARNBSARI'],
            ['name' => 'بنك البلاد', 'code' => 'ALBISARI'],
            ['name' => 'البنك السعودي الفرنسي', 'code' => 'BSFRSARI'],
            ['name' => 'البنك السعودي للاستثمار', 'code' => 'SIBCSARI'],
            ['name' => 'بنك الجزيرة', 'code' => 'BJAZSAJE'],
        ];

        foreach ($banks as $bank) {
            Bank::firstOrCreate(['code' => $bank['code']], [
                'name' => $bank['name'],
                'is_active' => true
            ]);
        }
    }
}
