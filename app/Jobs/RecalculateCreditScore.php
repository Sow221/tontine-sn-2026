<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\CreditScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RecalculateCreditScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $userId) {}

    public function handle(CreditScoringService $scorer): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $scorer->calculate($user);

        // Invalider le cache leaderboard de cet utilisateur après recalcul du score
        Cache::forget('leaderboard.user.'.$this->userId);

        CheckAndNotifyBadges::dispatch($this->userId)->afterResponse();
    }
}
