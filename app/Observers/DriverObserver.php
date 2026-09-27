<?php

namespace App\Observers;

use App\Models\Driver;
use App\Services\AdminNotificationDispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class DriverObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Driver $driver)
    {
        // Generate Driver Code (S00001)
        $driver->update([
            'driver_code' => 'S' . str_pad($driver->id, 5, '0', STR_PAD_LEFT)
        ]);

        Log::info("DriverObserver: New driver registered #{$driver->id} with code {$driver->driver_code}");

        $content = "تم تسجيل سائق جديد في المنصة:\n" .
                   "- الاسم: {$driver->name}\n" .
                   "- الكود: {$driver->driver_code}\n" .
                   "- الجوال: {$driver->phone}\n" .
                   "- البريد الإلكتروني: " . ($driver->email ?? 'N/A');

        AdminNotificationDispatcher::dispatch(
            eventKey: 'driver_registered',
            title: "تسجيل سائق جديد: {$driver->name} ({$driver->driver_code})",
            message: $content,
            actionUrl: url("/admin/drivers"),
            extraData: [
                'driver_id'   => $driver->id,
                'driver_name' => $driver->name,
                'driver_code' => $driver->driver_code,
                'phone'       => $driver->phone,
            ]
        );
    }
}
