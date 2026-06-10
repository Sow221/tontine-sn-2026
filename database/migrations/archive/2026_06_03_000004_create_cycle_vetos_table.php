<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cycle_vetos')) {
            Schema::create('cycle_vetos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cycle_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['cycle_id', 'user_id']);
                $table->index('cycle_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_vetos');
    }
};
