<?php

namespace App\Jobs;

use App\Models\CreditScore;
use App\Models\User;
use App\Services\CreditScoringService;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckAndNotifyBadges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $userId) {}

    public function handle(
        CreditScoringService $scorer,
        GamificationService $gamification,
        NotificationService $notifier
    ): void {
        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $oldScore = CreditScore::where('user_id', $user->id)->value('score');
        $oldBadge = CreditScore::where('user_id', $user->id)->value('badge');

        $scorer->calculate($user);

        $newScore = CreditScore::where('user_id', $user->id)->value('score');
        $newBadge = CreditScore::where('user_id', $user->id)->value('badge');

        if ($oldBadge !== $newBadge && $newBadge !== 'none') {
            $this->notifyBadge($notifier, $user, $newBadge, $newScore);
        }

        $earnedBadges = $gamification->checkAndAwardBadges($user);
        foreach ($earnedBadges as $badge) {
            $this->notifyBadge($notifier, $user, $badge->slug, null, $badge);
        }
    }

    private function notifyBadge(
        NotificationService $notifier,
        User $user,
        string $badgeSlug,
        ?float $score = null,
        ?\App\Models\Badge $badge = null
    ): void {
        $badgeNames = [
            'bronze' => 'Bronze',
            'silver' => 'Argent',
            'gold'   => 'Or',
            'parrain_boost' => 'Parrain Boost',
        ];

        $icons = [
            'bronze' => '🥉',
            'silver' => '🥈',
            'gold'   => '🥇',
            'parrain_boost' => '⚡',
        ];

        $name = $badgeNames[$badgeSlug] ?? $badge?->name ?? 'Badge';
        $icon = $icons[$badgeSlug] ?? $badge?->icon ?? '🏆';

        $title = "{$icon} Badge débloqué : {$name} !";
        $body  = $score !== null
            ? "Votre score crédit a atteint {$score}/10 — vous décrochez le badge {$name} !"
            : ($badge?->description ?? "Nouveau badge obtenu : {$name}");

        $notifier->sendWebPush($user, $title, $body, '/profil/badges');

        Log::info('Badge notification sent', [
            'user_id' => $user->id,
            'badge'   => $badgeSlug,
            'score'   => $score,
        ]);
    }
}