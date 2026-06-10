<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('webhooks_log');
    }

    public function down(): void
    {
        Schema::create('webhooks_log', function ($table) {
            $table->id();
            $table->string('provider');
            $table->string('webhook_hash')->unique();
            $table->json('payload');
            $table->string('status');
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['provider', 'created_at']);
            $table->index(['status']);
        });
    }
};
