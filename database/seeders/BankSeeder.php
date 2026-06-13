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
            ['name' => 'البنك العربي', 'code' => 'ARNBUS6XXX']
        ];

        foreach ($banks as $bank) {
            Bank::firstOrCreate(['code' => $bank['code']], [
                'name' => $bank['name'],
                'is_active' => true
            ]);
        }
    }
}
