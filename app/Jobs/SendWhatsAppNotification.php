<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use App\Services\ReceiptService;
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
        private ?array $receipt = null,
    ) {}

    public function handle(NotificationService $notifier): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $notifier->sendWhatsAppSync($user, $this->message, $this->event);

        $receiptService = app(ReceiptService::class);

        $signaturePath = $receiptService->getSignaturePath();
        if ($signaturePath !== '') {
            $notifier->sendWhatsAppFile($user, $signaturePath);
        }

        if ($this->receipt) {
            $receiptPath = $receiptService->generatePaymentReceipt(
                $this->receipt['userName'],
                $this->receipt['amount'],
                $this->receipt['tontineName'],
                $this->receipt['date'],
                $this->receipt['cycleNumber'] ?? null,
            );

            if ($receiptPath !== '') {
                $notifier->sendWhatsAppFile($user, $receiptPath);
            }
        }
    }
}
