<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // La contrainte unique bloquait les retries : si une tx échoue (failed),
            // l'utilisateur ne pouvait plus payer — la DB rejetait la nouvelle insertion.
            // La vérification applicative dans PaymentService::recordPayment() suffit.
            $table->dropUnique(['cycle_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unique(['cycle_id', 'user_id']);
        });
    }
};
