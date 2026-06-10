<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN method ENUM('wave','orange_money','free_money','card','cash','paytech') NOT NULL DEFAULT 'wave'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN method ENUM('wave','orange_money','card','cash','paytech') NOT NULL DEFAULT 'wave'");
        }
    }
};
