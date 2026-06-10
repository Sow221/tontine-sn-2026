<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 4, 2)->default(0);
            $table->unsignedBigInteger('total_contributed')->default(0);
            $table->unsignedInteger('on_time_payments')->default(0);
            $table->unsignedInteger('total_cycles')->default(0);
            $table->unsignedInteger('seniority_months')->default(0);
            $table->enum('badge', ['none', 'bronze', 'silver', 'gold'])->default('none');
            $table->timestamp('calculated_at')->useCurrent();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_scores');
    }
};
