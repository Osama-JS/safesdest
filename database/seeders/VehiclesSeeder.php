<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VehiclesSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $vehicles = [
      [
        'name' => 'دينا',
        'en_name' =>  'Dyana',
        'types' => [
          [
            'name' => 'مفتوحة',
            'en_name' =>  'Open',
          ],
          [
            'name' => 'مغلقة',
            'en_name' =>  'Closed',
          ],
          [
            'name' => 'ثلاجة',
            'en_name' =>  'Refrigerator',
          ],
          [
            'name' => 'جوانب',
            'en_name' =>  'Tow Truck',
          ],
        ]
      ],
      [
        'name' => 'تريلا',
        'en_name' =>  'Trailer',
        'types' => [
          [
            'name' => 'سطحة',
            'en_name' =>  'Flatbed',
          ],
          [
            'name' => 'جوانب',
            'en_name' =>  'Sides',
          ],
          [
            'name' => 'ستارة',
            'en_name' =>  'Curtain',
          ],
          [
            'name' => 'ثلاجة',
            'en_name' =>  'Refrigerator',
          ],
        ]
      ],
    ];

    foreach ($vehicles as $val) {
      $done = Vehicle::create([
        'name' => $val['name'],
        'en_name' => $val['en_name'],
      ]);
      $done->types()->createMany($val['types']);
    }
  }
}
