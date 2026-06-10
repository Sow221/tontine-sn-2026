<?php

namespace App\Services;

use App\Jobs\SendChatNotifications;
use App\Jobs\SendWhatsAppNotification;
use App\Models\FcmToken;
use App\Models\NotificationLog;
use App\Models\Tontine;
use App\Models\User;
use App\Services\WhatsApp\GreenApiService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    const EVENT_BENEFICIARY = 'beneficiary_notification';

    const EVENT_PAYMENT = 'payment_confirmed';

    const EVENT_MEMBER_APPROVED = 'member_approved';

    const EVENT_REMINDER = 'payment_reminder';

    const EVENT_CYCLE_START = 'cycle_start';

    const EVENT_SAVINGS = 'savings_withdrawal';

    const EVENT_MEMBER_REQUEST = 'member_request';

    const EVENT_KYC_APPROVED = 'kyc_approved';

    const EVENT_KYC_REJECTED = 'kyc_rejected';

    public function __construct(
        private GreenApiService $greenApi,
    ) {}

    public function sendWebPush(User $user, string $title, string $body, string $url = '/dashboard'): bool
    {
        $tokens = FcmToken::where('user_id', $user->id)->get();

        if ($tokens->isEmpty()) {
            return false;
        }

        $payload = json_encode([
            'notification' => [
                'title' => $title,
                'body' => $body,
                'icon' => '/images/icon-192.png',
            ],
            'data' => [
                'url' => $url,
            ],
        ]);

        $client = new Client;
        $success = false;

        foreach ($tokens as $token) {
            try {
                // Envoyer le push notification à l'endpoint Web Push
                $response = $client->post($token->endpoint, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'TTL' => '3600',
                    ],
                    'json' => [
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                            'icon' => '/images/icon-192.png',
                        ],
                        'data' => [
                            'url' => $url,
                        ],
                    ],
                    'http_errors' => false,
                ]);

                if ($response->getStatusCode() === 201 || $response->getStatusCode() === 200) {
                    $token->update(['last_used_at' => now()]);
                    $success = true;
                } elseif ($response->getStatusCode() === 410 || $response->getStatusCode() === 404) {
                    // Token expiré ou invalid, le supprimer
                    $token->delete();
                }
            } catch (\Exception $e) {
                Log::warning('Web Push failed', [
                    'user_id' => $user->id,
                    'endpoint' => $token->endpoint,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $success;
    }

    public function sendWhatsApp(User $user, string $message, string $event = 'general', ?array $receipt = null): bool
    {
        SendWhatsAppNotification::dispatch($user->id, $message, $event, $receipt);

        return true;
    }

    public function sendWhatsAppFile(User $user, string $filePath): bool
    {
        if (empty($user->phone_number)) {
            return false;
        }

        $phone = preg_replace('/[^0-9]/', '', $user->phone_number);

        if ($this->greenApi->isConfigured()) {
            return $this->greenApi->sendFileByUpload($phone, $filePath, 'recu_tontinesn.png');
        }

        return false;
    }

    public function sendWhatsAppSync(User $user, string $message, string $event = 'general'): bool
    {
        if (empty($user->phone_number)) {
            $this->logNotification($user, 'whatsapp', $event, $message, 'failed');

            return false;
        }

        $phone = preg_replace('/[^0-9]/', '', $user->phone_number);

        $appUrl = rtrim(config('app.url'), '/');
        $link = match ($event) {
            self::EVENT_PAYMENT => "\n\n📄 Reçu : {$appUrl}/transactions/{$user->id}/recu",
            self::EVENT_REMINDER => "\n\n💳 Payer maintenant : {$appUrl}/dashboard",
            self::EVENT_BENEFICIARY => "\n\n👤 Voir les détails : {$appUrl}/dashboard",
            self::EVENT_CYCLE_START => "\n\n💳 Payer : {$appUrl}/dashboard",
            self::EVENT_MEMBER_APPROVED => "\n\n🚀 Rejoindre : {$appUrl}/dashboard",
            self::EVENT_SAVINGS => "\n\n💰 Voir mon épargne : {$appUrl}/dashboard",
            default => '',
        };

        $fullMessage = $message.$link;

        if ($this->greenApi->isConfigured()) {
            $sent = $this->greenApi->sendText($phone, $fullMessage);

            $this->logNotification($user, 'whatsapp', $event, $message, $sent ? 'sent' : 'failed');

            return $sent;
        }

        $waLink = 'https://wa.me/'.$phone.'?text='.urlencode($fullMessage);

        Log::channel('stack')->info('WhatsApp link (no Green API configured)', [
            'user_id' => $user->id,
            'phone' => $phone,
            'message' => $fullMessage,
            'wa_link' => $waLink,
        ]);

        $this->logNotification($user, 'whatsapp', $event, $message, 'pending');

        return true;
    }

    public function sendEmail(User $user, string $subject, string $body, string $event = 'general'): bool
    {
        if (empty($user->email)) {
            $this->logNotification($user, 'email', $event, strip_tags($body), 'failed');

            return false;
        }

        try {
            Mail::send('emails.notification', ['subject' => $subject, 'body' => $body, 'user' => $user],
                function ($message) use ($user, $subject) {
                    $message->to($user->email, $user->name ?? $user->email)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
                }
            );

            $this->logNotification($user, 'email', $event, strip_tags($body), 'sent');

            return true;
        } catch (\Exception $e) {
            Log::error('Email notification failed', [
                'user_id' => $user->id,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            $this->logNotification($user, 'email', $event, strip_tags($body), 'failed');

            return false;
        }
    }

    public function send(User $user, string $type, string $message): void
    {
        $event = match ($type) {
            'kyc_approved' => self::EVENT_KYC_APPROVED,
            'kyc_rejected' => self::EVENT_KYC_REJECTED,
            default => 'general',
        };

        $this->sendEmail($user, $this->subjectFromType($type), "<p>{$message}</p>", $event);
        $this->sendWhatsApp($user, "Bonjour {$user->name}, {$message}", $event);
    }

    private function wantsChannel(User $user, string $settingKey): bool
    {
        $settings = $user->notification_settings ?? [];

        return ($settings[$settingKey] ?? true) !== false;
    }

    public function notifyBeneficiary(User $user, string $tontineName, int $amount): void
    {
        $montant = number_format($amount, 0, ',', ' ');
        $msg = "🎉 Bonjour {$user->name}, c'est votre tour ! Vous êtes bénéficiaire de la tontine {$tontineName}. Montant à recevoir : {$montant} FCFA. Connectez-vous sur TontineSN.";

        if ($this->wantsChannel($user, 'beneficiary_whatsapp')) {
            $this->sendWhatsApp($user, $msg, self::EVENT_BENEFICIARY);
        }
        if ($this->wantsChannel($user, 'beneficiary_email')) {
            $this->sendEmail(
                $user,
                "🎉 C'est votre tour — {$tontineName}",
                "Bonjour <strong>{$user->name}</strong>,<br><br>
            Vous avez été désigné(e) bénéficiaire de la tontine <strong>{$tontineName}</strong>.<br><br>
            <strong>Montant à recevoir : {$montant} FCFA</strong><br><br>
            Connectez-vous à TontineSN pour plus de détails.",
                self::EVENT_BENEFICIARY
            );
        }
    }

    public function notifyPaymentConfirmed(User $user, int $amount, string $tontineName, ?int $cycleNumber = null): void
    {
        $montant = number_format($amount, 0, ',', ' ');
        $cycleInfo = $cycleNumber ? " (Cycle n°{$cycleNumber})" : '';
        $msg = "✅ Bonjour {$user->name}, paiement confirmé{$cycleInfo} ! Votre cotisation de {$montant} FCFA pour la tontine {$tontineName} a été enregistrée. Merci !";
        $receipt = [
            'userName' => $user->name,
            'amount' => $amount,
            'tontineName' => $tontineName,
            'date' => now()->isoFormat('D MMMM YYYY'),
            'cycleNumber' => $cycleNumber,
        ];

        if ($this->wantsChannel($user, 'payment_whatsapp')) {
            $this->sendWhatsApp($user, $msg, self::EVENT_PAYMENT, $receipt);
        }
        if ($this->wantsChannel($user, 'payment_email')) {
            $this->sendEmail(
                $user,
                "✅ Paiement confirmé — {$tontineName}",
                "Bonjour <strong>{$user->name}</strong>,<br><br>
            Votre paiement de <strong>{$montant} FCFA</strong>
            pour la tontine <strong>{$tontineName}</strong> a bien été confirmé.<br><br>
            Merci pour votre ponctualité !",
                self::EVENT_PAYMENT
            );
        }
    }

    public function notifyMemberApproved(User $user, string $tontineName): void
    {
        $msg = "✅ Bonjour {$user->name}, votre adhésion à la tontine {$tontineName} a été approuvée ! Bienvenue dans le groupe. Connectez-vous sur TontineSN.";

        if ($this->wantsChannel($user, 'member_whatsapp')) {
            $this->sendWhatsApp($user, $msg, self::EVENT_MEMBER_APPROVED);
        }
        if ($this->wantsChannel($user, 'member_email')) {
            $this->sendEmail(
                $user,
                "✅ Adhésion approuvée — {$tontineName}",
                "Bonjour <strong>{$user->name}</strong>,<br><br>
            Votre demande d'adhésion à la tontine <strong>{$tontineName}</strong> a été acceptée.<br><br>
            Bienvenue dans le groupe ! Connectez-vous pour voir les détails.",
                self::EVENT_MEMBER_APPROVED
            );
        }
    }

    public function notifyPaymentReminder(User $user, string $tontineName, int $amount, int $daysLeft): void
    {
        $montant = number_format($amount, 0, ',', ' ');
        $msg = "🔔 Bonjour {$user->name}, rappel : votre cotisation de {$montant} FCFA pour la tontine {$tontineName} est due dans {$daysLeft} jour(s). Payez à temps pour garder votre score crédit.";

        if ($this->wantsChannel($user, 'reminder_whatsapp')) {
            $this->sendWhatsApp($user, $msg, self::EVENT_REMINDER);
        }
        if ($this->wantsChannel($user, 'reminder_email')) {
            $this->sendEmail(
                $user,
                "🔔 Rappel de cotisation — {$tontineName}",
                "Bonjour <strong>{$user->name}</strong>,<br><br>
            Rappel : votre cotisation de <strong>{$montant} FCFA</strong>
            pour la tontine <strong>{$tontineName}</strong> est due dans <strong>{$daysLeft} jour(s)</strong>.<br><br>
            Payez maintenant pour maintenir votre score crédit.",
                self::EVENT_REMINDER
            );
        }
    }

    public function notifyCycleStart(User $user, string $tontineName, string $dueDate): void
    {
        $msg = "📅 Bonjour {$user->name}, nouveau cycle démarré pour la tontine {$tontineName}. Date limite : {$dueDate}. Connectez-vous sur TontineSN pour payer.";

        if ($this->wantsChannel($user, 'cycle_whatsapp')) {
            $this->sendWhatsApp($user, $msg, self::EVENT_CYCLE_START);
        }
        if ($this->wantsChannel($user, 'cycle_email')) {
            $this->sendEmail(
                $user,
                "📅 Nouveau cycle — {$tontineName}",
                "Bonjour <strong>{$user->name}</strong>,<br><br>
            Un nouveau cycle de cotisation a démarré pour <strong>{$tontineName}</strong>.<br><br>
            Date limite : <strong>{$dueDate}</strong>.",
                self::EVENT_CYCLE_START
            );
        }
    }

    public function notifyNewChatMessage(Tontine $tontine, User $sender, string $message): void
    {
        SendChatNotifications::dispatch($tontine->id, $sender->id, $message);
    }

    public function notifySavingsWithdrawal(User $user, string $tontineName, int $amount): void
    {
        $montant = number_format($amount, 0, ',', ' ');
        $msg = "💰 Bonjour {$user->name}, votre épargne de {$montant} FCFA dans la tontine {$tontineName} est disponible. Contactez le gestionnaire pour récupérer votre argent.";

        $this->sendWhatsApp($user, $msg, self::EVENT_SAVINGS);
        $this->sendEmail(
            $user,
            "💰 Votre épargne est disponible — {$tontineName}",
            "Bonjour <strong>{$user->name}</strong>,<br><br>
            L'épargne de la tontine <strong>{$tontineName}</strong> a été clôturée.<br><br>
            <strong>Montant à retirer : {$montant} FCFA</strong><br><br>
            Contactez le gestionnaire de votre tontine pour récupérer votre épargne.",
            self::EVENT_SAVINGS
        );
    }

    public function notifyNewMemberRequest(User $creator, User $newMember, Tontine $tontine): void
    {
        $msg = "👤 Bonjour {$creator->name}, {$newMember->name} souhaite rejoindre votre tontine {$tontine->name}. Connectez-vous pour approuver ou refuser.";

        $this->sendWhatsApp($creator, $msg, self::EVENT_MEMBER_REQUEST);
        $this->sendEmail(
            $creator,
            "👤 Nouvelle demande d'adhésion — {$tontine->name}",
            "Bonjour <strong>{$creator->name}</strong>,<br><br>
            <strong>{$newMember->name}</strong> souhaite rejoindre votre tontine <strong>{$tontine->name}</strong>.<br><br>
            Connectez-vous à TontineSN pour approuver ou refuser cette demande.",
            self::EVENT_MEMBER_REQUEST
        );
    }

    private function subjectFromType(string $type): string
    {
        return match ($type) {
            'kyc_approved' => '✅ Identité vérifiée — TontineSN',
            'kyc_rejected' => '❌ Document KYC refusé — TontineSN',
            default => 'Notification — TontineSN',
        };
    }

    private function logNotification(User $user, string $channel, string $event, string $message, string $status): void
    {
        try {
            NotificationLog::create([
                'user_id' => $user->id,
                'channel' => $channel,
                'event' => $event,
                'message' => $message,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write notification_log', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
