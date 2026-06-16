<?php

namespace NexusExtensions\GoogleGmail\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleGmailSocketService
{
    public function emit(int $tenantId, string $event, array $payload = []): bool
    {
        if (!config('google-gmail.socket.enabled', false)) {
            return false;
        }

        $emitUrl = trim((string) config('google-gmail.socket.emit_url', ''));
        if ($emitUrl === '') {
            return false;
        }

        $token = trim((string) config('google-gmail.socket.server_token', ''));

        try {
            $request = Http::timeout(3)->acceptJson();

            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->post($emitUrl, [
                'tenant_id' => $tenantId,
                'event' => $event,
                'payload' => $payload,
                'module' => 'google-gmail',
            ]);

            if (!$response->ok()) {
                Log::warning('[GoogleGmail][Socket] emit failed', [
                    'tenant_id' => $tenantId,
                    'event' => $event,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('[GoogleGmail][Socket] emit exception', [
                'tenant_id' => $tenantId,
                'event' => $event,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
