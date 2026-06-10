<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('preferred_language', 'wo')->update(['preferred_language' => 'fr']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN preferred_language ENUM('fr', 'en') NOT NULL DEFAULT 'fr'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN preferred_language ENUM('fr', 'wo', 'en') NOT NULL DEFAULT 'fr'");
        }
    }
};
