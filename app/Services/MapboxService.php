<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MapboxService
{
  public function calculateRoute(array $pickup, array $delivery): array
  {
    $accessToken = config('services.mapbox.token');

    $url = "https://api.mapbox.com/directions/v5/mapbox/driving/"
      . implode(',', $pickup) . ';' . implode(',', $delivery)
      . "?geometries=geojson&steps=true&overview=full&access_token={$accessToken}";

    $response = Http::get($url);

    if (!$response->successful() || !isset($response['routes'][0])) {
      return ['error' => 'تعذر حساب المسار'];
    }

    $route = $response['routes'][0];

    // تحقق من الطرق المغلقة
    $blocked = [
      ['lat' => 24.774265, 'lng' => 46.738586],
      ['lat' => 24.798524, 'lng' => 46.675214],
      ['lat' => 24.761234, 'lng' => 46.690000],
    ];

    $blockedThreshold = 0.003;
    foreach ($route['geometry']['coordinates'] as $coord) {
      foreach ($blocked as $block) {
        if (
          abs($coord[0] - $block['lng']) < $blockedThreshold &&
          abs($coord[1] - $block['lat']) < $blockedThreshold
        ) {
          return ['error' => '🚧 الطريق يمر عبر نقطة مغلقة'];
        }
      }
    }

    return [
      'distance_km' => round($route['distance'] / 1000, 2),
      'duration_min' => round($route['duration'] / 60, 1),
    ];
  }
}
