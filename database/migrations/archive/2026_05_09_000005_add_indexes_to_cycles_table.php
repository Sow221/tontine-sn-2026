<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->index('beneficiary_id');
            $table->index(['tontine_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropIndex(['beneficiary_id']);
            $table->dropIndex(['tontine_id', 'status']);
        });
    }
};
