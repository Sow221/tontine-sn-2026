<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhooks_log', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // paytech, stripe, etc
            $table->string('webhook_hash')->unique();
            $table->jsonb('payload');
            $table->string('status'); // received, processing, processed, failed
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['provider', 'created_at']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks_log');
    }
};
