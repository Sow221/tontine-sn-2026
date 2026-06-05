<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Convertir ENUMs en VARCHAR (portable)
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN method VARCHAR(30) NOT NULL DEFAULT 'wave'");
            DB::statement("ALTER TABLE transactions MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE cycles MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE tontines MODIFY COLUMN frequency VARCHAR(20) NOT NULL DEFAULT 'monthly'");
            DB::statement("ALTER TABLE tontines MODIFY COLUMN type VARCHAR(30) NOT NULL DEFAULT 'fixed'");
            DB::statement("ALTER TABLE tontines MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE tontines MODIFY COLUMN draw_method VARCHAR(20) NOT NULL DEFAULT 'sequential'");
            DB::statement("ALTER TABLE credit_scores MODIFY COLUMN badge VARCHAR(20) NOT NULL DEFAULT 'none'");
        }

        // 2. Ajouter les index manquants
        Schema::table('cycles', function (Blueprint $table) {
            $table->index('due_date');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('paid_at');
        });

        Schema::table('tontines', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN method ENUM('wave','orange_money','free_money','card','cash','paytech') NOT NULL DEFAULT 'wave'");
            DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('pending','success','failed','reversed') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE cycles MODIFY COLUMN status ENUM('pending','partial','paid','overdue') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE tontines MODIFY COLUMN frequency ENUM('daily','weekly','monthly') NOT NULL DEFAULT 'monthly'");
            DB::statement("ALTER TABLE tontines MODIFY COLUMN type ENUM('fixed','auction','forced_saving','ceremonial') NOT NULL DEFAULT 'fixed'");
            DB::statement("ALTER TABLE tontines MODIFY COLUMN status ENUM('pending','active','completed','suspended') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE tontines MODIFY COLUMN draw_method ENUM('random','sequential') NOT NULL DEFAULT 'sequential'");
            DB::statement("ALTER TABLE credit_scores MODIFY COLUMN badge ENUM('none','bronze','silver','gold') NOT NULL DEFAULT 'none'");
        }

        Schema::table('cycles', function (Blueprint $table) {
            $table->dropIndex(['due_date']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['paid_at']);
        });

        Schema::table('tontines', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['published_at']);
        });
    }
};
