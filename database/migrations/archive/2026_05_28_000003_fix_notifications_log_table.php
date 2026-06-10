<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajouter les colonnes manquantes et rendre event nullable
        Schema::table('notifications_log', function (Blueprint $table) {
            $table->string('event')->nullable()->change();
            if (! Schema::hasColumn('notifications_log', 'type')) {
                $table->string('type')->nullable()->after('event');
            }
            if (! Schema::hasColumn('notifications_log', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('status');
            }
        });

        // 2. Convertir ENUMs en VARCHAR (portable)
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE notifications_log MODIFY COLUMN channel VARCHAR(20) NOT NULL DEFAULT 'email'");
            DB::statement("ALTER TABLE notifications_log MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        }

        // 3. Remplacer cascadeOnDelete par nullOnDelete pour préserver l'historique
        Schema::table('notifications_log', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // 4. Index sur le statut pour les requêtes de reporting
        Schema::table('notifications_log', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE notifications_log MODIFY COLUMN channel ENUM('sms','push','email') NOT NULL DEFAULT 'email'");
            DB::statement("ALTER TABLE notifications_log MODIFY COLUMN status ENUM('sent','failed','pending') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('notifications_log', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('event')->nullable(false)->change();
            $table->dropColumn(['type', 'sent_at']);
        });
    }
};
