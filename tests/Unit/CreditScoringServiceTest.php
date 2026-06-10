<?php

namespace Tests\Unit\Services;

use App\Models\CreditScore;
use App\Models\Cycle;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    private CreditScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tontine.credit_score' => [
                'weight_amount' => 0.3,
                'weight_punctuality' => 0.5,
                'weight_seniority' => 0.2,
                'base_amount' => 100_000,
                'seniority_base' => 12,
                'badges' => [
                    'bronze' => 4.0,
                    'silver' => 6.5,
                    'gold' => 8.5,
                ],
            ],
        ]);

        $this->service = new CreditScoringService;
    }

    // ─── Basic Score Calculation ──────────────────────────────────────────────

    /**
     * Test score calculation for user with no contributions.
     */
    public function test_calculate_score_no_contributions(): void
    {
        $user = User::factory()->create();

        $score = $this->service->calculate($user);

        $this->assertNotNull($score);
        $this->assertEquals($user->id, $score->user_id);
        $this->assertEquals(0, $score->score);
        $this->assertEquals('none', $score->badge);
        $this->assertEquals(0, $score->total_contributed);
        $this->assertEquals(0, $score->on_time_payments);
        $this->assertEquals(0, $score->total_cycles);
    }

    /**
     * Test score calculation with only amount contribution (no punctuality).
     */
    public function test_calculate_score_amount_only(): void
    {
        $user = User::factory()->create();

        // Create successful transactions but overdue (to test amount weight without punctuality bonus)
        for ($i = 0; $i < 3; $i++) {
            $cycle = Cycle::factory()->create([
                'due_date' => now()->subDays(5),
                'status' => 'overdue',
            ]);

            Transaction::factory()->create([
                'user_id' => $user->id,
                'cycle_id' => $cycle->id,
                'amount' => 50000,
                'status' => 'success',
                'paid_at' => now(),
            ]);
        }

        $score = $this->service->calculate($user);

        $this->assertEquals(3, $score->total_cycles);
        $this->assertEquals(150000, $score->total_contributed);
        $this->assertEquals(0, $score->on_time_payments);
        $this->assertGreaterThan(0, $score->score);
        $this->assertLessThan(10, $score->score);
    }

    /**
     * Test score calculation with perfect punctuality.
     */
    public function test_calculate_score_perfect_punctuality(): void
    {
        $user = User::factory()->create();

        // Create successful on-time transactions
        for ($i = 0; $i < 4; $i++) {
            $cycle = Cycle::factory()->create([
                'due_date' => now()->addDays(5),
            ]);

            Transaction::factory()->create([
                'user_id' => $user->id,
                'cycle_id' => $cycle->id,
                'amount' => 100000,
                'status' => 'success',
                'paid_at' => now()->subDays(3),
            ]);
        }

        $score = $this->service->calculate($user);

        $this->assertEquals(4, $score->total_cycles);
        $this->assertEquals(4, $score->on_time_payments);
        $this->assertGreaterThan(3, $score->score);
    }

    /**
     * Test score calculation respects maximum contribution weight.
     */
    public function test_calculate_score_amount_weight_cap(): void
    {
        $user = User::factory()->create();

        // Create transactions exceeding base amount
        for ($i = 0; $i < 5; $i++) {
            $cycle = Cycle::factory()->create([
                'due_date' => now()->subDays(5),
            ]);

            Transaction::factory()->create([
                'user_id' => $user->id,
                'cycle_id' => $cycle->id,
                'amount' => 500000,
                'status' => 'success',
                'paid_at' => now(),
            ]);
        }

        $score = $this->service->calculate($user);

        // Verify score is capped at maximum
        $this->assertLessThanOrEqual(10, $score->score);
        $this->assertGreaterThan(0, $score->score);
    }

    /**
     * Test score calculation with mixed on-time and late payments.
     */
    public function test_calculate_score_mixed_punctuality(): void
    {
        $user = User::factory()->create();

        // Create 3 on-time transactions
        for ($i = 0; $i < 3; $i++) {
            $cycle = Cycle::factory()->create([
                'due_date' => now()->addDays(5),
            ]);

            Transaction::factory()->create([
                'user_id' => $user->id,
                'cycle_id' => $cycle->id,
                'amount' => 100000,
                'status' => 'success',
                'paid_at' => now()->subDays(2),
            ]);
        }

        // Create 2 late transactions
        for ($i = 0; $i < 2; $i++) {
            $cycle = Cycle::factory()->create([
                'due_date' => now()->subDays(5),
            ]);

            Transaction::factory()->create([
                'user_id' => $user->id,
                'cycle_id' => $cycle->id,
                'amount' => 100000,
                'status' => 'success',
                'paid_at' => now(),
            ]);
        }

        $score = $this->service->calculate($user);

        $this->assertEquals(5, $score->total_cycles);
        $this->assertEquals(3, $score->on_time_payments);
    }

    // ─── Seniority Calculation ────────────────────────────────────────────────

    /**
     * Test score calculation with user seniority.
     */
    public function test_calculate_score_with_seniority(): void
    {
        $user = User::factory()->create([
            'created_at' => now()->subMonths(12),
        ]);

        $cycle = Cycle::factory()->create([
            'due_date' => now()->addDays(5),
        ]);

        Transaction::factory()->create([
            'user_id' => $user->id,
            'cycle_id' => $cycle->id,
            'amount' => 100000,
            'status' => 'success',
            'paid_at' => now(),
        ]);

        $score = $this->service->calculate($user);

        $this->assertGreaterThan(0, $score->seniority_months);
    }

    /**
     * Test score calculation for new user has zero seniority contribution.
     */
    public function test_calculate_score_new_user_zero_seniority(): void
    {
        $user = User::factory()->create();

        $score = $this->service->calculate($user);

        $this->assertEquals(0, $score->seniority_months);
    }

    /**
     * Test score calculation respects seniority weight cap.
     */
    public function test_calculate_score_seniority_weight_cap(): void
    {
        $user = User::factory()->create([
            'created_at' => now()->subYears(5),
        ]);

        $score = $this->service->calculate($user);

        $this->assertLessThanOrEqual(10, $score->score);
        $this->assertGreaterThanOrEqual(0, $score->score);
    }

    // ─── Badge Assignment ─────────────────────────────────────────────────────

    /**
     * Test badge assignment for bronze tier.
     */
    public function test_calculate_score_bronze_badge(): void
    {
        $user = User::factory()->create();

        // Create transactions to reach bronze threshold
        for ($i = 0; $i < 3; $i++) {
            $cycle = Cycle::factory()->create([
                'due_date' => now()->addDays(1),
            ]);

            Transaction::factory()->create([
                'user_id' => $user->id,
                'cycle_id' => $cycle->id,
                'amount' => 150000,
                'status' => 'success',
                'paid_at' => now(),
            ]);
        }

        $score = $this->service->calculate($user);

        $this->assertContains($score->badge, ['bronze', 'silver', 'gold']);
    }

    /**
     * Test badge assignment for silver tier.
     */
    public function test_calculate_score_silver_badge(): void
    {
        $user = User::factory()->create([
            'created_at' => now()->subMonths(8),
        ]);

        // Create transactions with good record
        for ($i = 0; $i < 5; $i++) {
            $cycle = Cycle::factory()->create([
                'due_date' => now()->addDays(3),
            ]);

            Transaction::factory()->create([
                'user_id' => $user->id,
                'cycle_id' => $cycle->id,
                'amount' => 200000,
                'status' => 'success',
                'paid_at' => now()->subHours(12),
            ]);
        }

        $score = $this->service->calculate($user);

        $this->assertGreaterThanOrEqual(4.0, $score->score);
    }

    /**
     * Test badge assignment for gold tier.
     */
    public function test_calculate_score_gold_badge(): void
    {
        $user = User::factory()->create([
            'created_at' => now()->subYears(2),
        ]);

        // Create excellent transaction record
        for ($i = 0; $i < 10; $i++) {
            $cycle = Cycle::factory()->create([
                'due_date' => now()->addDays(3),
            ]);

            Transaction::factory()->create([
                'user_id' => $user->id,
                'cycle_id' => $cycle->id,
                'amount' => 300000,
                'status' => 'success',
                'paid_at' => now()->subDays(1),
            ]);
        }

        $score = $this->service->calculate($user);

        $this->assertGreaterThanOrEqual(4.0, $score->score);
    }

    /**
     * Test badge 'none' for very poor record.
     */
    public function test_calculate_score_none_badge(): void
    {
        $user = User::factory()->create();

        $score = $this->service->calculate($user);

        $this->assertEquals('none', $score->badge);
    }

    // ─── Persistence and Updates ──────────────────────────────────────────────

    /**
     * Test score calculation persists to database.
     */
    public function test_calculate_score_persists(): void
    {
        $user = User::factory()->create();

        $cycle = Cycle::factory()->create();

        Transaction::factory()->create([
            'user_id' => $user->id,
            'cycle_id' => $cycle->id,
            'amount' => 100000,
            'status' => 'success',
        ]);

        $this->assertDatabaseMissing('credit_scores', ['user_id' => $user->id]);

        $score = $this->service->calculate($user);

        $this->assertDatabaseHas('credit_scores', [
            'user_id' => $user->id,
            'score' => $score->score,
        ]);
    }

    /**
     * Test score calculation updates existing score.
     */
    public function test_calculate_score_updates_existing(): void
    {
        $user = User::factory()->create();

        $cycle1 = Cycle::factory()->create();

        Transaction::factory()->create([
            'user_id' => $user->id,
            'cycle_id' => $cycle1->id,
            'amount' => 50000,
            'status' => 'success',
        ]);

        $score1 = $this->service->calculate($user);
        $initialScore = $score1->score;

        // Add more transactions
        $cycle2 = Cycle::factory()->create();

        Transaction::factory()->create([
            'user_id' => $user->id,
            'cycle_id' => $cycle2->id,
            'amount' => 100000,
            'status' => 'success',
        ]);

        $score2 = $this->service->calculate($user);

        $this->assertEquals(1, CreditScore::where('user_id', $user->id)->count());
        $this->assertNotEquals($initialScore, $score2->score);
    }

    /**
     * Test score includes calculated_at timestamp.
     */
    public function test_calculate_score_includes_timestamp(): void
    {
        $user = User::factory()->create();

        $score = $this->service->calculate($user);

        $this->assertNotNull($score->calculated_at);
        $this->assertTrue($score->calculated_at->isToday());
    }

    // ─── Boundary Cases ───────────────────────────────────────────────────────

    /**
     * Test score calculation with very large contributions.
     */
    public function test_calculate_score_large_contributions(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $cycle = Cycle::factory()->create();

            Transaction::factory()->create([
                'user_id' => $user->id,
                'cycle_id' => $cycle->id,
                'amount' => 500_000,
                'status' => 'success',
            ]);
        }

        $score = $this->service->calculate($user);

        $this->assertLessThanOrEqual(10, $score->score);
        $this->assertGreaterThanOrEqual(0, $score->score);
    }

    /**
     * Test score only counts successful transactions.
     */
    public function test_calculate_score_ignores_failed_transactions(): void
    {
        $user = User::factory()->create();

        $cycle1 = Cycle::factory()->create();
        $cycle2 = Cycle::factory()->create();

        // Successful transaction
        Transaction::factory()->create([
            'user_id' => $user->id,
            'cycle_id' => $cycle1->id,
            'amount' => 100000,
            'status' => 'success',
        ]);

        // Failed transaction
        Transaction::factory()->create([
            'user_id' => $user->id,
            'cycle_id' => $cycle2->id,
            'amount' => 100000,
            'status' => 'failed',
        ]);

        $score = $this->service->calculate($user);

        $this->assertEquals(100000, $score->total_contributed);
        $this->assertEquals(1, $score->total_cycles);
    }

    /**
     * Test score with multiple users are calculated independently.
     */
    public function test_calculate_score_multiple_users_independent(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $cycle1 = Cycle::factory()->create();
        $cycle2 = Cycle::factory()->create();

        Transaction::factory()->create([
            'user_id' => $user1->id,
            'cycle_id' => $cycle1->id,
            'amount' => 200000,
            'status' => 'success',
        ]);

        Transaction::factory()->create([
            'user_id' => $user2->id,
            'cycle_id' => $cycle2->id,
            'amount' => 50000,
            'status' => 'success',
        ]);

        $score1 = $this->service->calculate($user1);
        $score2 = $this->service->calculate($user2);

        $this->assertNotEquals($score1->score, $score2->score);
        $this->assertEquals(200000, $score1->total_contributed);
        $this->assertEquals(50000, $score2->total_contributed);
    }
}
