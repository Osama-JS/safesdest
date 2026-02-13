<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MapboxService
{
    public function calculateRoute(array $origin, array $destination): array
    {
        $accessToken = config('services.mapbox.token');

        // Mapbox expects [longitude, latitude]
        $originStr = $origin[1] . ',' . $origin[0];
        $destStr = $destination[1] . ',' . $destination[0];

        $url = "https://api.mapbox.com/directions/v5/mapbox/driving/"
            . $originStr . ';' . $destStr
            . "?geometries=geojson&access_token={$accessToken}";

        try {
            $response = Http::get($url);

            if (!$response->successful() || !isset($response['routes'][0])) {
                return ['error' => 'Unable to calculate road distance'];
            }

            $route = $response['routes'][0];

            return [
                'distance_km' => round($route['distance'] / 1000, 2),
                'duration_min' => round($route['duration'] / 60, 1),
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
