<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations to merge duplicate conversations and normalize phone numbers.
     */
    public function up(): void
    {
        $conversations = WhatsappConversation::all();

        foreach ($conversations as $conv) {
            $rawPhone = (string) $conv->phone_number;
            $cleanPhone = WhatsappConversation::normalizePhone($rawPhone);

            if ($rawPhone !== $cleanPhone) {
                // Check if a conversation with the clean phone already exists
                $targetConv = WhatsappConversation::where('phone_number', $cleanPhone)
                    ->where('id', '!=', $conv->id)
                    ->first();

                if ($targetConv) {
                    // Move all messages from the duplicate to the target conversation
                    WhatsappMessage::where('conversation_id', $conv->id)
                        ->update(['conversation_id' => $targetConv->id]);

                    // Merge user details if missing
                    if (!$targetConv->user_id && $conv->user_id) {
                        $targetConv->user_id = $conv->user_id;
                        $targetConv->user_type = $conv->user_type;
                    }
                    if (!$targetConv->saei_conversation_id && $conv->saei_conversation_id) {
                        $targetConv->saei_conversation_id = $conv->saei_conversation_id;
                    }
                    if ($conv->reply_window_expires_at && (!$targetConv->reply_window_expires_at || $conv->reply_window_expires_at > $targetConv->reply_window_expires_at)) {
                        $targetConv->reply_window_expires_at = $conv->reply_window_expires_at;
                    }

                    // Delete the duplicate conversation
                    $conv->delete();

                    // Update target conversation with latest message details
                    $latestMsg = $targetConv->messages()->latest('created_at')->first();
                    if ($latestMsg) {
                        $targetConv->last_message_preview = Str::limit($latestMsg->content, 80);
                        $targetConv->last_message_time = $latestMsg->created_at;
                    }
                    $targetConv->save();
                } else {
                    // Update phone number directly to clean format
                    $conv->update(['phone_number' => $cleanPhone]);
                }
            }
        }

        // Refresh all conversation previews and timestamps
        foreach (WhatsappConversation::all() as $conv) {
            $latestMsg = $conv->messages()->latest('created_at')->first();
            if ($latestMsg) {
                $conv->update([
                    'last_message_preview' => Str::limit($latestMsg->content, 80),
                    'last_message_time'    => $latestMsg->created_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data fix migration; no rollback needed.
    }
};
