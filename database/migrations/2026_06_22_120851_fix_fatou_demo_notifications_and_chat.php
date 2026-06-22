<?php

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $fatou = User::where('email', 'fatou@tontinesn.test')->first();
        if (! $fatou) {
            return;
        }

        // Remettre les messages chat comme non-lus pour Fatou
        DB::table('tontine_members')
            ->where('user_id', $fatou->id)
            ->update(['chat_last_seen_at' => null]);

        // Créer des notifications démo si Fatou n'en a pas encore
        if (NotificationLog::where('user_id', $fatou->id)->whereNull('read_at')->exists()) {
            return;
        }

        $notifications = [
            [
                'event' => 'reminder',
                'message' => 'Rappel : votre cotisation Famille Diallo est due dans 2 jours.',
            ],
            [
                'event' => 'cycle_opened',
                'message' => 'Le cycle 6 de Tontine Famille Diallo est ouvert. Cotisez avant la date limite.',
            ],
            [
                'event' => 'payment_confirmed',
                'message' => 'Votre paiement de 15 000 FCFA sur Tontine Médina a été confirmé ✓',
            ],
        ];

        foreach ($notifications as $n) {
            NotificationLog::create([
                'user_id' => $fatou->id,
                'channel' => 'web',
                'event' => $n['event'],
                'message' => $n['message'],
                'status' => 'sent',
                'read_at' => null,
            ]);
        }
    }

    public function down(): void {}
};
