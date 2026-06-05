<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'kyc_document_hash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('kyc_document_hash')->nullable()->index()->after('kyc_document');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'kyc_document_hash')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('kyc_document_hash');
            });
        }
    }
};
