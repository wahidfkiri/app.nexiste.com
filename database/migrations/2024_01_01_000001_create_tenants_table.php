<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nom de l'entreprise
            $table->string('slug')->unique(); // slug pour URL: /entreprise/ma-societe
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('logo')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('locale')->default('fr');
            $table->string('currency')->default('EUR');
            $table->enum('status', ['active', 'suspended', 'pending'])->default('active');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['slug', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenants');
    }
};