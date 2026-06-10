<?php

namespace App\Jobs;

use App\Models\Tontine;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendChatNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $tontineId,
        private int $senderId,
        private string $message
    ) {}

    public function handle(NotificationService $notifier): void
    {
        $tontine = Tontine::find($this->tontineId);
        if (! $tontine) {
            return;
        }

        $sender = User::find($this->senderId);
        if (! $sender) {
            return;
        }

        $members = $tontine->activeMembers()
            ->where('users.id', '!=', $sender->id)
            ->get();

        $preview = mb_strlen($this->message) > 60
            ? mb_substr($this->message, 0, 57).'...'
            : $this->message;

        $subject = "💬 {$tontine->name} : message de {$sender->name}";
        $body = 'Bonjour,<br><br>'
            ."<strong>{$sender->name}</strong> a envoyé un message dans <strong>{$tontine->name}</strong> :<br>"
            ."<blockquote style='border-left:3px solid #009639;padding-left:12px;margin:8px 0;color:#374151;'>{$preview}</blockquote>"
            .'Connectez-vous pour répondre.';

        foreach ($members as $member) {
            $settings = $member->notification_settings ?? [];
            if (($settings['chat_email'] ?? true) === false) {
                continue;
            }
            $notifier->sendEmail($member, $subject, $body, 'chat_message');
        }
    }
}
