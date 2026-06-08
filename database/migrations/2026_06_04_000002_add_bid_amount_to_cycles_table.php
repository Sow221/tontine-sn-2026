<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            if (!Schema::hasColumn('cycles', 'bid_amount')) {
                $table->unsignedBigInteger('bid_amount')->nullable()->after('total_collected');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn('bid_amount');
        });
    }
};
