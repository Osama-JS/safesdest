<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

$convs = DB::table('whatsapp_conversations')->get();
foreach ($convs as $c) {
    $latestMsg = DB::table('whatsapp_messages')
        ->where('conversation_id', $c->id)
        ->orderBy('created_at', 'desc')
        ->first();
    echo "Conv #{$c->id} | Phone: {$c->phone_number} | last_message_time: " . ($c->last_message_time ?? 'NULL') . "\n";
    if ($latestMsg) {
        echo "   -> Latest msg #{$latestMsg->id} at {$latestMsg->created_at}: " . mb_substr($latestMsg->content, 0, 40) . "\n";
        // If last_message_time is null, fix it
        if (!$c->last_message_time) {
            DB::table('whatsapp_conversations')->where('id', $c->id)->update([
                'last_message_time' => $latestMsg->created_at,
                'last_message_preview' => mb_substr($latestMsg->content, 0, 80)
            ]);
            echo "   -> FIXED last_message_time to {$latestMsg->created_at}!\n";
        }
    } else {
        echo "   -> No messages.\n";
    }
}
