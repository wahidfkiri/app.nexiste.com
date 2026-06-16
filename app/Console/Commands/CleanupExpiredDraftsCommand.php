<?php

namespace App\Console\Commands;

use App\Models\Draft;
use Illuminate\Console\Command;

class CleanupExpiredDraftsCommand extends Command
{
    protected $signature = 'drafts:cleanup';

    protected $description = 'Delete expired drafts from storage.';

    public function handle(): int
    {
        $deleted = Draft::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();

        $this->info(sprintf('%d brouillon(s) expire(s) supprime(s).', $deleted));

        return self::SUCCESS;
    }
}
