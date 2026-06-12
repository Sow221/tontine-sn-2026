<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter la colonne email_verified_at si elle n'existe pas déjà
        if (! Schema::hasColumn('users', 'email_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable()->after('max_streak');
            });
        }

        // Les utilisateurs créés avant l'activation de MustVerifyEmail
        // sont considérés comme vérifiés — ils n'ont pas à re-vérifier.
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Irréversible par design — on ne sait pas qui était non vérifié avant.
    }
};
