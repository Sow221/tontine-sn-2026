<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                DELETE t1 FROM transactions t1
                INNER JOIN (
                    SELECT cycle_id, user_id, MAX(id) as max_id
                    FROM transactions
                    WHERE cycle_id IS NOT NULL
                    GROUP BY cycle_id, user_id
                    HAVING COUNT(*) > 1
                ) t2
                ON t1.cycle_id = t2.cycle_id AND t1.user_id = t2.user_id
                WHERE t1.id < t2.max_id
            ');
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['cycle_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE transactions MODIFY COLUMN cycle_id BIGINT UNSIGNED NULL');
            DB::statement("ALTER TABLE transactions MODIFY COLUMN method VARCHAR(50) NOT NULL DEFAULT 'wave'");
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreign('cycle_id')->references('id')->on('cycles')->cascadeOnDelete();

            $table->string('type', 50)->nullable()->after('method');
            $table->text('description')->nullable()->after('amount');
            $table->json('metadata')->nullable()->after('description');

            $table->unique(['cycle_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['cycle_id', 'user_id']);
            $table->dropColumn(['type', 'description', 'metadata']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN method ENUM('wave','orange_money','card','cash','paytech') NOT NULL DEFAULT 'wave'");
            DB::statement('ALTER TABLE transactions MODIFY COLUMN cycle_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['cycle_id']);
            $table->foreign('cycle_id')->references('id')->on('cycles')->cascadeOnDelete();
        });
    }
};
