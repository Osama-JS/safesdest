<?php

namespace App\Observers;

use App\Models\Teams;
use App\Services\AdminNotificationDispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class TeamObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Teams $team)
    {
        Log::info("TeamObserver: New team created #{$team->id}");

        $content = "تم إنشاء فريق جديد في المنصة:\n" .
                   "- اسم الفريق: {$team->name}\n" .
                   "- العنوان: " . ($team->address ?? 'N/A');

        AdminNotificationDispatcher::dispatch(
            eventKey: 'team_created',
            title: "إنشاء فريق جديد: {$team->name}",
            message: $content,
            actionUrl: url("/admin/teams"),
            extraData: [
                'team_id'   => $team->id,
                'team_name' => $team->name,
                'address'   => $team->address,
            ]
        );
    }
}
