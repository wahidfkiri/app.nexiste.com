<?php

namespace NexusExtensions\Slack\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackSocketService
{
    public function emit(int $tenantId, string $event, array $payload = []): bool
    {
        if (!config('slack.socket.enabled', false)) {
            return false;
        }

        $emitUrl = trim((string) config('slack.socket.emit_url', ''));
        if ($emitUrl === '') {
            return false;
        }

        $token = trim((string) config('slack.socket.server_token', ''));

        try {
            $request = Http::timeout(3)->acceptJson();

            if ($token !== '') {
                $request = $request->withToken($token);
            }

            $response = $request->post($emitUrl, [
                'tenant_id' => $tenantId,
                'event' => $event,
                'payload' => $payload,
                'module' => 'slack',
            ]);

            if (!$response->ok()) {
                Log::warning('[Slack][Socket] emit failed', [
                    'tenant_id' => $tenantId,
                    'event' => $event,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('[Slack][Socket] emit exception', [
                'tenant_id' => $tenantId,
                'event' => $event,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

