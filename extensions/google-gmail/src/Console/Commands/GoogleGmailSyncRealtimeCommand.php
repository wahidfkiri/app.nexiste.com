<?php

namespace NexusExtensions\GoogleGmail\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use NexusExtensions\GoogleGmail\Models\GoogleGmailToken;
use NexusExtensions\GoogleGmail\Services\GoogleGmailService;
use Throwable;
use Vendor\Extensions\Models\Extension;
use Vendor\Extensions\Models\TenantExtension;

class GoogleGmailSyncRealtimeCommand extends Command
{
    protected $signature = 'google-gmail:sync-realtime {--tenant_id=* : Limit sync to one or more tenant IDs}';

    protected $description = 'Synchronise Gmail en arriere-plan et pousse les mises a jour via Socket.IO.';

    public function handle(GoogleGmailService $service): int
    {
        if (!config('google-gmail.socket.enabled', false) || !config('google-gmail.socket.scheduler_enabled', true)) {
            $this->components->info('Google Gmail Socket.IO scheduler is disabled.');
            return self::SUCCESS;
        }

        if (!$this->storageReady()) {
            $this->components->warn('Google Gmail storage is not ready. Run migrations first.');
            return self::SUCCESS;
        }

        $tenantIds = collect((array) $this->option('tenant_id'))
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->values();

        $activeExtensionId = Extension::query()
            ->where('slug', 'google-gmail')
            ->value('id');

        $query = GoogleGmailToken::query()
            ->where('is_active', true);

        if ($tenantIds->isNotEmpty()) {
            $query->whereIn('tenant_id', $tenantIds->all());
        } elseif ($activeExtensionId) {
            $activeTenantIds = TenantExtension::query()
                ->where('extension_id', $activeExtensionId)
                ->whereIn('status', ['active', 'trial'])
                ->pluck('tenant_id');

            $query->whereIn('tenant_id', $activeTenantIds);
        }

        $tokens = $query->get(['tenant_id']);

        if ($tokens->isEmpty()) {
            $this->components->info('No active Gmail tenants to sync.');
            return self::SUCCESS;
        }

        $synced = 0;
        $emitted = 0;

        foreach ($tokens as $token) {
            try {
                $result = $service->syncMailboxRealtime((int) $token->tenant_id);
                $synced++;

                if (!empty($result['emitted'])) {
                    $emitted++;
                }
            } catch (Throwable $e) {
                Log::warning('[GoogleGmail][RealtimeSync] tenant sync failed', [
                    'tenant_id' => (int) $token->tenant_id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $this->components->info(sprintf('Gmail realtime sync complete. Tenants processed: %d. Socket emits: %d.', $synced, $emitted));

        return self::SUCCESS;
    }

    private function storageReady(): bool
    {
        return Schema::hasTable('google_gmail_tokens')
            && Schema::hasTable('google_gmail_messages')
            && Schema::hasTable('google_gmail_labels')
            && Schema::hasTable('google_gmail_activity_logs')
            && Schema::hasTable('google_gmail_settings');
    }
}
