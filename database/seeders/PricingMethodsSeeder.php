<?php

namespace Database\Seeders;

use App\Models\Pricing_Method;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PricingMethodsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    Pricing_Method::query()->delete();
    $methods = [
      [
        "name" => "Distance calculation",
        "description" => "It calculates the number of kilometers between the pick-up and delivery point and then offers the price based on the price per kilometer",
        "type" => "distance"
      ],
      [
        "name" => "Point to Point",
        "description" => "It offers a fixed price for the delivery process from a pre-specified point to another specified point",
        "type" => "points"
      ],
    ];

    foreach ($methods as $method) {
      Pricing_Method::create($method);
    }
  }
}
