<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const ARCHIVED_EMAIL_DOMAIN = 'archived.local';

    public function up(): void
    {
        DB::table('clients')
            ->whereNotNull('deleted_at')
            ->whereNotNull('email')
            ->orderBy('id')
            ->chunkById(100, function ($clients): void {
                foreach ($clients as $client) {
                    $email = (string) $client->email;

                    if ($email === '' || str_ends_with($email, '@'.self::ARCHIVED_EMAIL_DOMAIN)) {
                        continue;
                    }

                    $deletedAt = $client->deleted_at
                        ? Carbon::parse((string) $client->deleted_at)->format('YmdHis')
                        : now()->format('YmdHis');

                    $localPart = sprintf(
                        'deleted+client%s+%s+%s',
                        $client->id,
                        $deletedAt,
                        substr(md5($email), 0, 10)
                    );

                    DB::table('clients')
                        ->where('id', $client->id)
                        ->update([
                            'email' => substr($localPart, 0, 64).'@'.self::ARCHIVED_EMAIL_DOMAIN,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // No rollback: original emails are intentionally released for reuse after soft delete.
    }
};
