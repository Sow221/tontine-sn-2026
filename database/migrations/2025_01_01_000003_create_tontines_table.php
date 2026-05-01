<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tontines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 10)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount');
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('monthly');
            $table->enum('type', ['fixed', 'auction', 'forced_saving', 'ceremonial'])->default('fixed');
            $table->enum('status', ['pending', 'active', 'completed', 'suspended'])->default('pending');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('max_members')->default(20);
            $table->unsignedDecimal('penalty_rate', 5, 2)->default(0);
            $table->unsignedInteger('quorum')->default(1);
            $table->enum('draw_method', ['random', 'sequential'])->default('sequential');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tontines');
    }
};
