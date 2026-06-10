<?php

use Illuminate\Database\Migrations\Migration;

// Cette migration est un doublon : le champ 'password' existe déjà dans
// 2025_01_01_000001_create_users_table.php — conservée vide pour ne pas
// casser l'historique des migrations.
return new class extends Migration
{
    public function up(): void {}

    public function down(): void {}
};
