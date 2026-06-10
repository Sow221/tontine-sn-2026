<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('kyc_status', ['none', 'pending', 'approved', 'rejected'])->default('none')->after('kyc_document_hash');
            $table->string('kyc_rejected_reason')->nullable()->after('kyc_status');
        });

        // Migrer les données existantes
        DB::table('users')->where('kyc_verified', true)->update(['kyc_status' => 'approved']);
        DB::table('users')->where('kyc_verified', false)->whereNotNull('kyc_document')->update(['kyc_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kyc_status', 'kyc_rejected_reason']);
        });
    }
};
