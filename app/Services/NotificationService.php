<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendPush(User $user, string $title, string $body): bool
    {
        $serverKey = config('mobilemoney.fcm.server_key');

        if (empty($serverKey)) {
            Log::info("PUSH [SIMULATION] → {$user->id}: {$title}");
            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "key={$serverKey}",
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to'           => $user->fcm_token ?? '/topics/user_' . $user->id,
                'notification' => ['title' => $title, 'body' => $body],
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Push failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function notifyCycleStart(User $user, string $tontineName, string $dueDate): void
    {
        $this->sendPush($user, 'Nouveau cycle', "Cotisation pour {$tontineName} due le {$dueDate}.");
    }

    public function notifyPaymentConfirmed(User $user, int $amount): void
    {
        $this->sendPush($user, 'Paiement confirmé', "Paiement de {$amount} FCFA confirmé. Merci !");
    }

    public function notifyBeneficiary(User $user, string $tontineName, int $amount): void
    {
        $this->sendPush($user, '🎉 Vous avez gagné !', "Vous êtes bénéficiaire de {$tontineName}. Montant : {$amount} FCFA.");
    }

    public function notifyPaymentReminder(User $user, string $tontineName, int $amount, int $daysLeft): void
    {
        $this->sendPush($user, 'Rappel de cotisation', "Rappel : {$amount} FCFA pour {$tontineName} dans {$daysLeft} jour(s).");
    }
}
