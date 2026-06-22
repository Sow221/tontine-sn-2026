<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tontine_members', function (Blueprint $table) {
            $table->string('role', 20)->default('member')->after('status');
        });

        DB::table('tontine_members')
            ->join('tontines', 'tontines.id', '=', 'tontine_members.tontine_id')
            ->whereColumn('tontines.created_by', '=', 'tontine_members.user_id')
            ->update(['tontine_members.role' => 'manager']);
    }

    public function down(): void
    {
        Schema::table('tontine_members', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
