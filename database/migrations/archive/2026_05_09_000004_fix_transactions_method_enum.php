<?php

// NOTE: Cette migration est un doublon de 2026_05_09_000002.
// Les ENUMs ont été convertis en VARCHAR dans 2026_05_28_000001.
// Cette classe est laissée vide pour compatibilité.

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Déjà appliqué par 2026_05_09_000002 — ne rien faire
    }

    public function down(): void
    {
        // Ne rien faire
    }
};
