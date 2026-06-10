<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Migration rendue obsolète par 2026_06_15_000001
        // (method passe d'ENUM à VARCHAR, ALTER MODIFY COLUMN n'est plus nécessaire)
    }

    public function down(): void
    {
        //
    }
};
