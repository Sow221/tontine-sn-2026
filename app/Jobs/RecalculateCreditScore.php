<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\CreditScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateCreditScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $userId) {}

    public function handle(CreditScoringService $scorer): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $scorer->calculate($user);

        CheckAndNotifyBadges::dispatch($this->userId)->afterResponse();
    }
}
