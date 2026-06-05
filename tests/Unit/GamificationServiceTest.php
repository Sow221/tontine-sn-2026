<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Badge;
use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\GamificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private GamificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\BadgeSeeder::class);
        $this->service = $this->app->make(GamificationService::class);
    }

    public function test_awards_first_payment_badge(): void
    {
        $user = User::factory()->create();
        $cycle = Cycle::factory()->create();

        Transaction::factory()->create([
            'user_id' => $user->id,
            'cycle_id' => $cycle->id,
            'status' => 'success',
        ]);

        $badges = $this->service->checkAndAwardBadges($user);

        $this->assertTrue($badges->pluck('slug')->contains('first_payment'));
        $this->assertCount(1, $badges);
    }

    public function test_awards_streak_badges(): void
    {
        $user = User::factory()->create(['payment_streak' => 10, 'max_streak' => 10]);

        $badges = $this->service->checkAndAwardBadges($user);

        $this->assertTrue($badges->pluck('slug')->contains('streak_5'));
        $this->assertTrue($badges->pluck('slug')->contains('streak_10'));
    }

    public function test_does_not_award_same_badge_twice(): void
    {
        $user = User::factory()->create(['payment_streak' => 10, 'max_streak' => 10]);

        $this->service->checkAndAwardBadges($user);
        $badges = $this->service->checkAndAwardBadges($user);

        $this->assertCount(0, $badges);
    }

    public function test_update_payment_streak_on_time(): void
    {
        $user = User::factory()->create(['payment_streak' => 0]);
        $cycle = Cycle::factory()->create(['due_date' => now()->addDay()]);

        $this->service->updatePaymentStreak($user, $cycle, true);

        $this->assertEquals(1, $user->fresh()->payment_streak);
        $this->assertEquals(1, $user->fresh()->max_streak);
    }

    public function test_update_payment_streak_late(): void
    {
        $user = User::factory()->create(['payment_streak' => 5]);

        $this->service->updatePaymentStreak($user, new Cycle(), false);

        $this->assertEquals(0, $user->fresh()->payment_streak);
    }

    public function test_get_leaderboard_returns_top_users(): void
    {
        $users = User::factory()->count(3)->create();

        $tontine = Tontine::factory()->create();
        $tontine->members()->attach($users->pluck('id'), ['status' => 'active']);

        $leaderboard = $this->service->getLeaderboard($tontine, 10);

        $this->assertCount(3, $leaderboard);
    }

    public function test_get_user_stats(): void
    {
        $user = User::factory()->create(['payment_streak' => 3, 'max_streak' => 7]);

        $stats = $this->service->getUserStats($user);

        $this->assertEquals(3, $stats['payment_streak']);
        $this->assertEquals(7, $stats['max_streak']);
        $this->assertArrayHasKey('badges', $stats);
    }
}
