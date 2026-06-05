<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('icon', 50)->default('🏅');
            $table->string('tier', 20)->default('bronze'); // bronze, silver, gold
            $table->string('criteria_type', 50); // payment_streak, beneficiary_count, tontines_created, on_time_months, tontine_completed, invited_members
            $table->unsignedSmallInteger('criteria_value'); // threshold to earn
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('earned_at')->useCurrent();
            $table->timestamps();
            $table->unique(['user_id', 'badge_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedSmallInteger('payment_streak')->default(0)->after('last_seen_at');
            $table->unsignedSmallInteger('max_streak')->default(0)->after('payment_streak');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['payment_streak', 'max_streak']);
        });
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
    }
};
