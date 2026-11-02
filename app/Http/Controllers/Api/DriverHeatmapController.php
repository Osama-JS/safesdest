<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Task_Ad;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DriverHeatmapController extends Controller
{
    /**
     * Get demand heatmap data for drivers based on trip origin locations (regardless of task status)
     */
    public function getHeatmapData(Request $request)
    {
        try {
            $driver = $request->user();

            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver not authenticated'
                ], 401);
            }

            $driverLat = !empty($driver->altitude) ? (float)$driver->altitude : null;
            $driverLng = !empty($driver->longitude) ? (float)$driver->longitude : null;

            $points = [];
            $clusterMap = [];

            // 1. Fetch created tasks pickup origin locations regardless of task status
            $allTasks = Task::with(['pickup', 'delivery', 'customer'])
                ->whereHas('pickup', function ($q) {
                    $q->whereNotNull('latitude')->whereNotNull('longitude');
                })
                ->latest()
                ->limit(500)
                ->get();

            foreach ($allTasks as $task) {
                $pickup = $task->pickup;
                if (!$pickup || empty($pickup->latitude) || empty($pickup->longitude)) {
                    continue;
                }

                $lat = (float) $pickup->latitude;
                $lng = (float) $pickup->longitude;

                // Cluster key rounded to ~2-3 km
                $clusterKey = round($lat, 2) . ',' . round($lng, 2);
                if (!isset($clusterMap[$clusterKey])) {
                    $clusterMap[$clusterKey] = [
                        'count' => 0,
                        'address' => $pickup->address ?? 'منطقة طلب شحنات',
                        'sample_task_id' => $task->id,
                        'total_price' => 0,
                    ];
                }
                $clusterMap[$clusterKey]['count']++;
                $clusterMap[$clusterKey]['total_price'] += (float)($task->total_cost ?? $task->delivery_cost ?? 0);

                $points[] = [
                    'id' => $task->id,
                    'type' => 'task',
                    'status' => $task->status,
                    'title' => 'رحلة تم إنشاؤها #' . $task->id,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'pickup_address' => $pickup->address ?? 'موقع انطلاق الرحلة',
                    'delivery_address' => $task->delivery?->address ?? 'موقع التسليم',
                    'delivery_lat' => $task->delivery?->latitude ? (float) $task->delivery->latitude : null,
                    'delivery_lng' => $task->delivery?->longitude ? (float) $task->delivery->longitude : null,
                    'price' => (float) ($task->total_cost ?? $task->delivery_cost ?? 0),
                    'customer_name' => $task->customer?->name ?? 'عميل SafeDest',
                    'intensity' => 3,
                    'created_at' => $task->created_at?->toIso8601String(),
                ];
            }

            // 2. Fetch running task ads
            $taskAds = Task_Ad::with(['task.pickup', 'task.delivery', 'task.customer'])
                ->whereHas('task.pickup', function ($q) {
                    $q->whereNotNull('latitude')->whereNotNull('longitude');
                })
                ->latest()
                ->limit(200)
                ->get();

            foreach ($taskAds as $ad) {
                $task = $ad->task;
                if (!$task || !$task->pickup || empty($task->pickup->latitude) || empty($task->pickup->longitude)) {
                    continue;
                }

                $lat = (float) $task->pickup->latitude;
                $lng = (float) $task->pickup->longitude;

                $clusterKey = round($lat, 2) . ',' . round($lng, 2);
                if (!isset($clusterMap[$clusterKey])) {
                    $clusterMap[$clusterKey] = [
                        'count' => 0,
                        'address' => $task->pickup->address ?? 'منطقة طلب شحنات',
                        'sample_task_id' => $task->id,
                        'total_price' => 0,
                    ];
                }
                $clusterMap[$clusterKey]['count']++;
                $clusterMap[$clusterKey]['total_price'] += (float)($ad->price ?? $task->total_cost ?? 0);

                $points[] = [
                    'id' => $ad->id,
                    'task_id' => $task->id,
                    'type' => 'ad',
                    'status' => $ad->status,
                    'title' => 'إعلان شحنة #' . $ad->id,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'pickup_address' => $task->pickup->address ?? 'موقع انطلاق الشحنة',
                    'delivery_address' => $task->delivery?->address ?? 'موقع التسليم',
                    'delivery_lat' => $task->delivery?->latitude ? (float) $task->delivery->latitude : null,
                    'delivery_lng' => $task->delivery?->longitude ? (float) $task->delivery->longitude : null,
                    'price' => (float) ($ad->price ?? $task->total_cost ?? 0),
                    'customer_name' => $task->customer?->name ?? 'معلن',
                    'intensity' => 2,
                    'created_at' => $ad->created_at?->toIso8601String(),
                ];
            }

            // High demand origin hotspots (sorted by trip count descending)
            $hotspots = [];
            foreach ($clusterMap as $coord => $info) {
                [$cLat, $cLng] = explode(',', $coord);
                $count = $info['count'];
                $hotspots[] = [
                    'latitude' => (float)$cLat,
                    'longitude' => (float)$cLng,
                    'count' => $count,
                    'trips_count' => $count,
                    'address' => $info['address'],
                    'level' => $count >= 8 ? 'extreme' : ($count >= 4 ? 'high' : ($count >= 2 ? 'medium' : 'normal')),
                ];
            }

            // Sort hotspots so most dense areas appear first
            usort($hotspots, function ($a, $b) {
                return $b['count'] <=> $a['count'];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'driver_location' => [
                        'latitude' => $driverLat,
                        'longitude' => $driverLng,
                    ],
                    'summary' => [
                        'total_trips_created' => count($points),
                        'hotspots_count' => count($hotspots),
                    ],
                    'points' => $points,
                    'hotspots' => $hotspots,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in DriverHeatmapController::getHeatmapData: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحميل بيانات خريطة الطلبات'
            ], 500);
        }
    }
}

