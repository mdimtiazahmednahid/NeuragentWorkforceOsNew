<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class WhatsAppService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('services.whatsapp', [
            'provider' => 'GREEN_API',
            'enabled' => env('WHATSAPP_ENABLED', true),
            'id_instance' => env('GREEN_API_ID_INSTANCE', ''),
            'api_token_instance' => env('GREEN_API_TOKEN_INSTANCE', ''),
            'green_api_url' => env('GREEN_API_URL', 'https://7107.api.greenapi.com'),
            'mock_mode' => env('WHATSAPP_MOCK_MODE', false)
        ]);
    }

    public function sendToUser(int $userId, string $messageType, string $message, array $payload = []): int
    {
        $user = User::find($userId);
        if (!$user) return 0;
        
        $phone = $user->whatsapp_number ?: $user->normalized_phone_number ?: $user->phone;
        return $this->sendToPhone((string) $phone, $messageType, $message, $payload, $userId);
    }

    public function sendToPhone(string $phone, string $messageType, string $message, array $payload = [], ?int $userId = null): int
    {
        return $this->logMessage('USER', $phone, null, $messageType, $message, $payload, $userId);
    }

    public function sendText(int $notificationId): array
    {
        $notification = DB::table('whatsapp_notifications')->find($notificationId);
        
        if (!$notification) {
            return ['ok' => false, 'error' => 'Notification not found'];
        }

        if ($this->config['mock_mode']) {
            DB::table('whatsapp_notifications')->where('id', $notificationId)->update([
                'status' => 'mock_sent',
                'sent_at' => now(),
                'updated_at' => now()
            ]);
            return ['ok' => true, 'mock' => true];
        }

        if (!$this->config['enabled']) {
            $this->markFailed($notificationId, 'WhatsApp is not enabled.');
            return ['ok' => false, 'error' => 'WhatsApp is not enabled'];
        }

        return $this->sendViaGreenApi((array) $notification, $notificationId);
    }

    private function sendViaGreenApi(array $notification, int $notificationId): array
    {
        $idInstance = $this->config['id_instance'];
        $apiToken = $this->config['api_token_instance'];
        $apiUrl = rtrim($this->config['green_api_url'], '/');

        if (empty($idInstance) || empty($apiToken)) {
            $this->markFailed($notificationId, 'GreenAPI not configured');
            return ['ok' => false, 'error' => 'GreenAPI not configured'];
        }

        $recipient = $notification['recipient_phone'] ?: $notification['recipient'];
        
        if ($notification['recipient_type'] === 'GROUP') {
            $groupId = $notification['recipient_group_id'] ?: $recipient;
            $chatId = str_contains($groupId, '@g.us') ? $groupId : $groupId . '@g.us';
        } else {
            $phone = preg_replace('/[^\d]/', '', $recipient);
            $chatId = $phone . '@c.us';
        }

        $url = "{$apiUrl}/waInstance{$idInstance}/sendMessage/{$apiToken}";
        $payload = [
            'chatId' => $chatId,
            'message' => $notification['message_body'],
        ];

        try {
            $response = Http::timeout(20)->post($url, $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                DB::table('whatsapp_notifications')->where('id', $notificationId)->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'provider_message_id' => $data['idMessage'] ?? null,
                    'updated_at' => now()
                ]);
                return ['ok' => true, 'response' => $data];
            } else {
                $error = 'HTTP Error: ' . $response->status();
                $this->markFailed($notificationId, $error);
                return ['ok' => false, 'error' => $error];
            }
        } catch (\Exception $e) {
            $this->markFailed($notificationId, 'Exception: ' . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function logMessage(string $recipientType, ?string $recipientPhone, ?string $groupId, string $messageType, string $body, array $payload, ?int $userId, ?string $template = null): int
    {
        $id = DB::table('whatsapp_notifications')->insertGetId([
            'recipient_type' => $recipientType,
            'recipient' => $recipientPhone ?? $groupId ?? 'UNKNOWN',
            'recipient_phone' => $recipientPhone,
            'recipient_group_id' => $groupId,
            'user_id' => $userId,
            'message_type' => $messageType,
            'message_body' => $body,
            'template_name' => $template,
            'payload_json' => json_encode($payload),
            'status' => 'queued',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return $id;
    }

    private function markFailed(int $id, string $error): void
    {
        DB::table('whatsapp_notifications')->where('id', $id)->update([
            'status' => 'failed',
            'error_message' => substr($error, 0, 500),
            'updated_at' => now()
        ]);
    }
}
