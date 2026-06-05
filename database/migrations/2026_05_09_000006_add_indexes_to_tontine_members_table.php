<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tontine_members', function (Blueprint $table) {
            $table->index(['user_id', 'status']);
            $table->index(['tontine_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('tontine_members', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['tontine_id', 'status']);
        });
    }
};
