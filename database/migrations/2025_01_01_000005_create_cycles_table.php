<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tontine_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('cycle_number');
            $table->foreignId('beneficiary_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date');
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue'])->default('pending');
            $table->unsignedBigInteger('total_collected')->default(0);
            $table->string('draw_hash', 64)->nullable();
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();

            $table->unique(['tontine_id', 'cycle_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycles');
    }
};
