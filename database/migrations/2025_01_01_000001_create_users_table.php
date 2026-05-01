<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20)->unique();
            $table->string('name', 100)->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('avatar')->nullable();
            $table->enum('role', ['member', 'manager', 'admin', 'super_admin'])->default('member');
            $table->enum('preferred_language', ['fr', 'wo', 'en'])->default('fr');
            $table->boolean('kyc_verified')->default(false);
            $table->string('kyc_document')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
