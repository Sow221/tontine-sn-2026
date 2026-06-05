<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'manager')->update(['role' => 'member']);

        try {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('member','admin','super_admin') NOT NULL DEFAULT 'member'");
        } catch (\Throwable $e) {
            // Déjà appliqué — ignoré
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('member','manager','admin','super_admin') NOT NULL DEFAULT 'member'");
    }
};
