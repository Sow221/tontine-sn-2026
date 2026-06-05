<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Corriger bid_amount sur cycles (était absent du fillable et mal utilisé)
        Schema::table('cycles', function (Blueprint $table) {
            if (!Schema::hasColumn('cycles', 'bid_amount')) {
                $table->unsignedBigInteger('bid_amount')->nullable()->after('total_collected')
                      ->comment('Montant net reçu par le gagnant de l\'enchère');
            }
        });

        // Table des retraits d'épargne individuelle (forced_saving)
        Schema::create('savings_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['tontine_id', 'user_id']);
            $table->index('tontine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_withdrawals');
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn('bid_amount');
        });
    }
};
