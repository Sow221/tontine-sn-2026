<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('bid_rate', 5, 2)->comment('Taux proposé en % (ex: 5.00 = 5%)');
            $table->timestamps();

            $table->unique(['cycle_id', 'user_id']); // un seul bid par membre par cycle
            $table->index('cycle_id');
        });

        // Ajouter bid_amount sur cycles pour stocker le montant net de l'enchère gagnante
        Schema::table('cycles', function (Blueprint $table) {
            $table->unsignedBigInteger('bid_amount')->nullable()->after('total_collected')
                ->comment('Montant net reçu par le gagnant de l\'enchère');
        });
    }

    public function down(): void
    {
        Schema::table('cycles', function (Blueprint $table) {
            $table->dropColumn('bid_amount');
        });
        Schema::dropIfExists('auction_bids');
    }
};
