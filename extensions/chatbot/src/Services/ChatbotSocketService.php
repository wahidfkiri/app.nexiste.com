<?php

namespace NexusExtensions\Chatbot\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotSocketService
{
    public function emit(int $tenantId, string $event, array $payload = []): bool
    {
        if (!config('chatbot.socket.enabled', false)) {
            return false;
        }

        $emitUrl = trim((string) config('chatbot.socket.emit_url', ''));
        if ($emitUrl === '') {
            return false;
        }

        $token = trim((string) config('chatbot.socket.server_token', ''));

        try {
            $request = Http::timeout(3)->acceptJson();
            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->post($emitUrl, [
                'tenant_id' => $tenantId,
                'event' => $event,
                'payload' => $payload,
                'module' => 'chatbot',
            ]);

            if (!$response->ok()) {
                Log::warning('[Chatbot][Socket] emit failed', [
                    'tenant_id' => $tenantId,
                    'event' => $event,
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('[Chatbot][Socket] emit exception', [
                'tenant_id' => $tenantId,
                'event' => $event,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
