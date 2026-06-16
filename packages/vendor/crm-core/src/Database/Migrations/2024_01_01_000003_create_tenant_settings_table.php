<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
            
            $table->unique(['tenant_id', 'key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_settings');
    }
};