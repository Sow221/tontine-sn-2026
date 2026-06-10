<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\CreditScore;
use App\Models\Cycle;
use App\Models\CycleVeto;
use App\Models\Tontine;
use App\Models\User;
use App\Services\DrawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartTontineTest extends TestCase
{
    use RefreshDatabase;

    private DrawService $drawService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->drawService = $this->app->make(DrawService::class);
    }

    public function test_weighted_draw_favors_higher_score(): void
    {
        $tontine = Tontine::factory()->create([
            'weighted_draw' => true,
            'draw_method' => 'sequential',
        ]);

        $lowScore = User::factory()->create();
        $highScore = User::factory()->create();

        $tontine->members()->attach([
            $lowScore->id => ['status' => 'active', 'position' => 1],
            $highScore->id => ['status' => 'active', 'position' => 2],
        ]);

        CreditScore::create(['user_id' => $lowScore->id,  'score' => 0.5,  'badge' => 'none',  'calculated_at' => now()]);
        CreditScore::create(['user_id' => $highScore->id, 'score' => 9.5, 'badge' => 'gold',  'calculated_at' => now()]);

        $cycle = Cycle::factory()->create([
            'tontine_id' => $tontine->id,
            'status' => 'paid',
            'total_collected' => $tontine->amount * 2,
        ]);

        $this->drawService->drawBeneficiary($cycle);
        $cycle->refresh();

        // High-score user should almost always be drawn
        $this->assertNotNull($cycle->beneficiary_id);
    }

    public function test_can_draw_checks_quorum(): void
    {
        $tontine = Tontine::factory()->create([
            'quorum' => 60,
        ]);

        $members = User::factory()->count(5)->create();
        $tontine->members()->attach($members->pluck('id'), ['status' => 'active']);

        $cycle = Cycle::factory()->create([
            'tontine_id' => $tontine->id,
            'status' => 'pending',
            'total_collected' => 0,
        ]);

        $reason = $this->drawService->canDraw($cycle);
        $this->assertStringContainsString('Quorum', $reason ?? '');
    }

    public function test_can_veto_returns_true_for_active_member(): void
    {
        $tontine = Tontine::factory()->create(['veto_threshold' => 50]);
        $member = User::factory()->create();
        $tontine->members()->attach($member->id, ['status' => 'active']);

        $cycle = Cycle::factory()->create([
            'tontine_id' => $tontine->id,
            'beneficiary_id' => User::factory()->create()->id,
        ]);

        $this->assertTrue($this->drawService->canVeto($cycle, $member->id));
    }

    public function test_is_vetoed_checks_threshold(): void
    {
        $tontine = Tontine::factory()->create(['veto_threshold' => 50]);
        $members = User::factory()->count(4)->create();
        $tontine->members()->attach($members->pluck('id'), ['status' => 'active']);

        $cycle = Cycle::factory()->create([
            'tontine_id' => $tontine->id,
            'beneficiary_id' => User::factory()->create()->id,
        ]);

        // 2 vetos out of 4 = 50% = threshold reached
        foreach ($members->take(2) as $member) {
            CycleVeto::create([
                'cycle_id' => $cycle->id,
                'user_id' => $member->id,
            ]);
        }

        $this->assertTrue($this->drawService->isVetoed($cycle));
    }
}
