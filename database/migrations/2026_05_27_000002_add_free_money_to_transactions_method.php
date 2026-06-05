<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN method ENUM('wave','orange_money','card','cash','paytech','free_money') DEFAULT 'wave'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE transactions MODIFY COLUMN method ENUM('wave','orange_money','card','cash','paytech') DEFAULT 'wave'");
    }
};
