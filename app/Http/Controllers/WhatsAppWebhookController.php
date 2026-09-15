<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Handle incoming webhooks from WhatsApp (e.g. GreenAPI)
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        
        // Basic logging of incoming webhook
        Log::info('WhatsApp Webhook Received', ['payload' => $payload]);

        if (empty($payload)) {
            return response()->json(['status' => 'ignored', 'reason' => 'empty payload']);
        }

        // GreenAPI delivery/read status updates
        if (isset($payload['typeWebhook']) && in_array($payload['typeWebhook'], ['outgoingMessageStatus', 'outgoingAPIMessageReceived'])) {
            $messageId = $payload['idMessage'] ?? null;
            $status = $payload['status'] ?? null;

            if ($messageId && $status) {
                // Map GreenAPI statuses to our internal statuses
                $internalStatus = match($status) {
                    'sent' => 'sent',
                    'delivered' => 'delivered',
                    'read' => 'read',
                    'failed' => 'failed',
                    default => 'unknown'
                };

                DB::table('whatsapp_notifications')
                    ->where('provider_message_id', $messageId)
                    ->update([
                        'status' => $internalStatus,
                        'updated_at' => now(),
                    ]);
                    
                return response()->json(['status' => 'status_updated']);
            }
        }

        // Handle incoming messages if needed
        if (isset($payload['typeWebhook']) && $payload['typeWebhook'] === 'incomingMessageReceived') {
            $messageData = $payload['messageData'] ?? [];
            $sender = $payload['senderData']['sender'] ?? null;
            
            // Here you could trigger jobs or log incoming replies
            Log::info("Incoming message from $sender", $messageData);
            
            return response()->json(['status' => 'message_logged']);
        }

        return response()->json(['status' => 'ok']);
    }
}
