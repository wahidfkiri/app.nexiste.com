<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_gmail_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->onDelete('cascade');
            $table->boolean('signature_enabled')->default(false);
            $table->longText('signature_html')->nullable();
            $table->text('signature_text')->nullable();
            $table->boolean('signature_on_replies')->default(true);
            $table->boolean('signature_on_forwards')->default(true);
            $table->json('default_cc')->nullable();
            $table->json('default_bcc')->nullable();
            $table->unsignedSmallInteger('polling_interval_seconds')->default(45);
            $table->json('main_labels')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'signature_enabled'], 'ggmail_set_tenant_signature_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_gmail_settings');
    }
};