<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $maxAttempts = 3;

    public function __construct(
        private int $userId,
        private string $message,
        private string $event = 'general',
    ) {}

    public function handle(NotificationService $notifier): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $notifier->sendWhatsAppSync($user, $this->message, $this->event);
    }
}
