<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendSms(string $phone, string $message): bool
    {
        $cfg = config('mobilemoney.sms');

        if (empty($cfg['api_key'])) {
            Log::info("SMS [SIMULATION] → {$phone}: {$message}");
            return true;
        }

        try {
            $response = Http::post('https://api.sms-provider.sn/send', [
                'api_key' => $cfg['api_key'],
                'sender'  => $cfg['sender'],
                'to'      => $phone,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SMS failed', ['phone' => $phone, 'error' => $e->getMessage()]);
            return false;
        }
    }

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
        $msg = "TontineSN - Nouveau cycle de {$tontineName}. Cotisation due le {$dueDate}.";
        $this->sendSms($user->phone_number, $msg);
        $this->sendPush($user, 'Nouveau cycle', $msg);
    }

    public function notifyPaymentReminder(User $user, string $tontineName, int $amount, int $daysLeft): void
    {
        $msg = "TontineSN - Rappel : {$amount} FCFA pour {$tontineName} dans {$daysLeft} jour(s).";
        $this->sendSms($user->phone_number, $msg);
    }

    public function notifyPaymentConfirmed(User $user, int $amount): void
    {
        $msg = "TontineSN - Paiement de {$amount} FCFA confirmé. Merci !";
        $this->sendPush($user, 'Paiement confirmé', $msg);
    }

    public function notifyBeneficiary(User $user, string $tontineName, int $amount): void
    {
        $msg = "TontineSN - Félicitations ! Vous êtes bénéficiaire de {$tontineName}. Montant : {$amount} FCFA.";
        $this->sendSms($user->phone_number, $msg);
        $this->sendPush($user, '🎉 Vous avez gagné !', $msg);
    }
}
