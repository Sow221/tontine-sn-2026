<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client as TwilioClient;

class NotificationService
{
    const EVENT_BENEFICIARY    = 'beneficiary_notification';
    const EVENT_PAYMENT        = 'payment_confirmed';
    const EVENT_MEMBER_APPROVED = 'member_approved';
    const EVENT_REMINDER       = 'payment_reminder';
    const EVENT_CYCLE_START    = 'cycle_start';
    const EVENT_SAVINGS        = 'savings_withdrawal';
    const EVENT_KYC_APPROVED   = 'kyc_approved';
    const EVENT_KYC_REJECTED   = 'kyc_rejected';

    private ?TwilioClient $twilio = null;

    public function __construct()
    {
        $sid   = config('services.twilio.sid');
        $token = config('services.twilio.token');

        if ($sid && $token) {
            $this->twilio = new TwilioClient($sid, $token);
        }
    }

    public function sendWhatsApp(User $user, string $message, string $event = 'general'): bool
    {
        if (empty($user->phone_number)) {
            $this->logNotification($user, 'whatsapp', $event, $message, 'failed');
            return false;
        }

        $phone = preg_replace('/[^0-9]/', '', $user->phone_number);

        if ($this->twilio && config('services.twilio.whatsapp_from')) {
            try {
                $this->twilio->messages->create(
                    "whatsapp:+{$phone}",
                    [
                        'from' => 'whatsapp:' . config('services.twilio.whatsapp_from'),
                        'body' => $message,
                    ]
                );

                $this->logNotification($user, 'whatsapp', $event, $message, 'sent');
                return true;
            } catch (\Exception $e) {
                Log::error('Twilio WhatsApp failed', [
                    'user_id' => $user->id,
                    'phone'   => $phone,
                    'error'   => $e->getMessage(),
                ]);

                $this->logNotification($user, 'whatsapp', $event, $message, 'failed');
                return false;
            }
        }

        $waLink = 'https://wa.me/' . $phone . '?text=' . urlencode($message);

        Log::channel('stack')->info('WhatsApp link (no Twilio configured)', [
            'user_id' => $user->id,
            'phone'   => $phone,
            'message' => $message,
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
                'error'   => $e->getMessage(),
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
            default        => 'general',
        };

        $this->sendEmail($user, $this->subjectFromType($type), "<p>{$message}</p>", $event);
        $this->sendWhatsApp($user, $message, $event);
    }

    public function notifyBeneficiary(User $user, string $tontineName, int $amount): void
    {
        $montant = number_format($amount, 0, ',', ' ');
        $msg     = "🎉 C'est votre tour ! Vous êtes bénéficiaire de la tontine {$tontineName}. Montant à recevoir : {$montant} FCFA. Connectez-vous sur TontineSN.";

        $this->sendWhatsApp($user, $msg, self::EVENT_BENEFICIARY);
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

    public function notifyPaymentConfirmed(User $user, int $amount, string $tontineName): void
    {
        $montant = number_format($amount, 0, ',', ' ');
        $msg     = "✅ Paiement confirmé ! Votre cotisation de {$montant} FCFA pour la tontine {$tontineName} a été enregistrée. Merci !";

        $this->sendWhatsApp($user, $msg, self::EVENT_PAYMENT);
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

    public function notifyMemberApproved(User $user, string $tontineName): void
    {
        $msg = "✅ Votre adhésion à la tontine {$tontineName} a été approuvée ! Bienvenue dans le groupe. Connectez-vous sur TontineSN.";

        $this->sendWhatsApp($user, $msg, self::EVENT_MEMBER_APPROVED);
        $this->sendEmail(
            $user,
            "✅ Adhésion approuvée — {$tontineName}",
            "Bonjour <strong>{$user->name}</strong>,<br><br>
            Votre demande d'adhésion à la tontine <strong>{$tontineName}</strong> a été acceptée.<br><br>
            Bienvenue dans le groupe ! Connectez-vous pour voir les détails.",
            self::EVENT_MEMBER_APPROVED
        );
    }

    public function notifyPaymentReminder(User $user, string $tontineName, int $amount, int $daysLeft): void
    {
        $montant = number_format($amount, 0, ',', ' ');
        $msg     = "🔔 Rappel : votre cotisation de {$montant} FCFA pour la tontine {$tontineName} est due dans {$daysLeft} jour(s). Payez à temps pour garder votre score crédit.";

        $this->sendWhatsApp($user, $msg, self::EVENT_REMINDER);
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

    public function notifyCycleStart(User $user, string $tontineName, string $dueDate): void
    {
        $msg = "📅 Nouveau cycle démarré pour la tontine {$tontineName}. Date limite : {$dueDate}. Connectez-vous sur TontineSN pour payer.";

        $this->sendWhatsApp($user, $msg, self::EVENT_CYCLE_START);
        $this->sendEmail(
            $user,
            "📅 Nouveau cycle — {$tontineName}",
            "Bonjour <strong>{$user->name}</strong>,<br><br>
            Un nouveau cycle de cotisation a démarré pour <strong>{$tontineName}</strong>.<br><br>
            Date limite : <strong>{$dueDate}</strong>.",
            self::EVENT_CYCLE_START
        );
    }

    public function notifyNewChatMessage(\App\Models\Tontine $tontine, User $sender, string $message): void
    {
        $members = $tontine->activeMembers()
            ->where('users.id', '!=', $sender->id)
            ->get();

        $preview = mb_strlen($message) > 60 ? mb_substr($message, 0, 57) . '...' : $message;
        $subject = "💬 {$tontine->name} : message de {$sender->name}";
        $body    = "Bonjour,<br><br>"
                 . "<strong>{$sender->name}</strong> a envoyé un message dans <strong>{$tontine->name}</strong> :<br>"
                 . "<blockquote style='border-left:3px solid #009639;padding-left:12px;margin:8px 0;color:#374151;'>{$preview}</blockquote>"
                 . "Connectez-vous pour répondre.";

        foreach ($members as $member) {
            $settings = $member->notification_settings ?? [];
            if (($settings['chat_email'] ?? true) === false) continue;
            $this->sendEmail($member, $subject, $body, 'chat_message');
        }
    }

    public function notifySavingsWithdrawal(User $user, string $tontineName, int $amount): void
    {
        $montant = number_format($amount, 0, ',', ' ');
        $msg     = "💰 Votre épargne de {$montant} FCFA dans la tontine {$tontineName} est disponible. Contactez le gestionnaire pour récupérer votre argent.";

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

    private function subjectFromType(string $type): string
    {
        return match ($type) {
            'kyc_approved' => '✅ Identité vérifiée — TontineSN',
            'kyc_rejected' => '❌ Document KYC refusé — TontineSN',
            default        => 'Notification — TontineSN',
        };
    }

    private function logNotification(User $user, string $channel, string $event, string $message, string $status): void
    {
        try {
            NotificationLog::create([
                'user_id' => $user->id,
                'channel' => $channel,
                'event'   => $event,
                'message' => $message,
                'status'  => $status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write notification_log', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
