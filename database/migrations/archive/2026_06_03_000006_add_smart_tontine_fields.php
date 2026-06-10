<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tontines', function (Blueprint $table) {
            if (! Schema::hasColumn('tontines', 'weighted_draw')) {
                $table->boolean('weighted_draw')->default(false)->after('draw_method');
            }
            if (! Schema::hasColumn('tontines', 'veto_threshold')) {
                $table->unsignedTinyInteger('veto_threshold')->nullable()->after('weighted_draw')
                    ->comment('Pourcentage de membres requis pour bloquer un tirage (ex: 50 = 50%)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tontines', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('tontines', 'weighted_draw')) {
                $cols[] = 'weighted_draw';
            }
            if (Schema::hasColumn('tontines', 'veto_threshold')) {
                $cols[] = 'veto_threshold';
            }
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
