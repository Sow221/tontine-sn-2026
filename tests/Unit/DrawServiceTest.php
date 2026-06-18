<?php

namespace Tests\Unit;

use App\Models\AuctionBid;
use App\Models\CreditScore;
use App\Models\Cycle;
use App\Models\CycleVeto;
use App\Models\Tontine;
use App\Models\TontineDebt;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DrawService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DrawServiceTest extends TestCase
{
    use RefreshDatabase;

    private DrawService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $notifier = Mockery::mock(NotificationService::class);
        $notifier->shouldIgnoreMissing();

        $this->service = new DrawService($notifier);
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function activeTontine(array $attrs = []): Tontine
    {
        return Tontine::factory()->active()->create(array_merge([
            'type'       => 'fixed',
            'amount'     => 10000,
            'draw_method' => 'sequential',
        ], $attrs));
    }

    private function attachMember(Tontine $tontine, User $user, int $position = 1): void
    {
        $tontine->members()->attach($user->id, [
            'status'   => 'active',
            'position' => $position,
            'joined_at' => now(),
        ]);
    }

    private function pendingCycle(Tontine $tontine, int $num = 1): Cycle
    {
        return Cycle::factory()->create([
            'tontine_id'       => $tontine->id,
            'cycle_number'     => $num,
            'status'           => 'paid',
            'total_collected'  => $tontine->amount * 2,
            'due_date'         => now()->subDay(),
        ]);
    }

    private function payForAll(Cycle $cycle, iterable $users, int $amount): void
    {
        foreach ($users as $user) {
            Transaction::factory()->create([
                'cycle_id' => $cycle->id,
                'user_id'  => $user->id,
                'amount'   => $amount,
                'status'   => 'success',
                'method'   => 'wave',
                'type'     => 'cotisation',
                'paid_at'  => now(),
            ]);
        }
    }

    // ── 1. canDraw ─────────────────────────────────────────────────────────

    public function test_can_draw_returns_null_when_cycle_is_fully_paid(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id]);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $member, 2);

        $cycle = $this->pendingCycle($tontine);
        $this->payForAll($cycle, [$owner, $member], $tontine->amount);

        $this->assertNull($this->service->canDraw($cycle->fresh()));
    }

    public function test_can_draw_returns_message_when_already_drawn(): void
    {
        $owner  = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id]);
        $this->attachMember($tontine, $owner, 1);

        $cycle = $this->pendingCycle($tontine);
        $cycle->update(['beneficiary_id' => $owner->id]);

        $this->assertNotNull($this->service->canDraw($cycle->fresh()));
    }

    public function test_can_draw_returns_message_when_not_fully_paid(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id]);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $member, 2);

        // total_collected = 1 membre seulement sur 2 → completionRate = 50%
        $cycle = Cycle::factory()->create([
            'tontine_id'      => $tontine->id,
            'cycle_number'    => 1,
            'status'          => 'pending',
            'total_collected' => $tontine->amount,
            'due_date'        => now()->subDay(),
        ]);

        $this->assertNotNull($this->service->canDraw($cycle->fresh()));
    }

    // ── 2. drawBeneficiary (sequential) ───────────────────────────────────

    public function test_sequential_draw_picks_member_by_position(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id, 'draw_method' => 'sequential']);
        $this->attachMember($tontine, $owner, 2);
        $this->attachMember($tontine, $member, 1);  // position 1 = doit gagner en premier

        $cycle = $this->pendingCycle($tontine);
        $this->payForAll($cycle, [$owner, $member], $tontine->amount);

        $this->service->drawBeneficiary($cycle->fresh());

        $this->assertEquals($member->id, $cycle->fresh()->beneficiary_id);
    }

    public function test_draw_skips_members_who_already_won(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id, 'draw_method' => 'sequential']);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $member, 2);

        // Cycle 1 : owner a déjà gagné
        $cycle1 = $this->pendingCycle($tontine, 1);
        $cycle1->update(['beneficiary_id' => $owner->id]);

        // Cycle 2 : member doit gagner
        $cycle2 = $this->pendingCycle($tontine, 2);
        $this->payForAll($cycle2, [$owner, $member], $tontine->amount);

        $this->service->drawBeneficiary($cycle2->fresh());

        $this->assertEquals($member->id, $cycle2->fresh()->beneficiary_id);
    }

    public function test_draw_sets_hash_and_drawn_at(): void
    {
        $owner = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id]);
        $this->attachMember($tontine, $owner, 1);

        $cycle = $this->pendingCycle($tontine);
        $this->payForAll($cycle, [$owner], $tontine->amount);

        $this->service->drawBeneficiary($cycle->fresh());

        $fresh = $cycle->fresh();
        $this->assertNotNull($fresh->draw_hash);
        $this->assertNotNull($fresh->drawn_at);
    }

    // ── 3. Véto ────────────────────────────────────────────────────────────

    public function test_can_veto_returns_true_for_active_member(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id, 'veto_threshold' => 50]);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $member, 2);

        $cycle = $this->pendingCycle($tontine);
        $cycle->update(['beneficiary_id' => $owner->id]);

        $this->assertTrue($this->service->canVeto($cycle->fresh(), $member->id));
    }

    public function test_beneficiary_cannot_veto_own_draw(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id, 'veto_threshold' => 50]);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $member, 2);

        $cycle = $this->pendingCycle($tontine);
        $cycle->update(['beneficiary_id' => $owner->id]);

        $this->assertFalse($this->service->canVeto($cycle->fresh(), $owner->id));
    }

    public function test_veto_threshold_not_reached_returns_false(): void
    {
        $owner  = User::factory()->create();
        $m1 = User::factory()->create();
        $m2 = User::factory()->create();
        // 4 members, threshold 50% → 2 required
        $tontine = $this->activeTontine(['created_by' => $owner->id, 'veto_threshold' => 50]);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $m1, 2);
        $this->attachMember($tontine, $m2, 3);

        $cycle = $this->pendingCycle($tontine);
        $cycle->update(['beneficiary_id' => $owner->id]);

        // Un seul vote → seuil non atteint
        CycleVeto::create(['cycle_id' => $cycle->id, 'user_id' => $m1->id]);

        $this->assertFalse($this->service->isVetoed($cycle->fresh()));
    }

    public function test_veto_threshold_reached_returns_true(): void
    {
        $owner  = User::factory()->create();
        $m1 = User::factory()->create();
        $m2 = User::factory()->create();
        // 3 members, threshold 50% → ceil(3*50/100) = 2 required
        $tontine = $this->activeTontine(['created_by' => $owner->id, 'veto_threshold' => 50]);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $m1, 2);
        $this->attachMember($tontine, $m2, 3);

        $cycle = $this->pendingCycle($tontine);
        $cycle->update(['beneficiary_id' => $owner->id]);

        CycleVeto::create(['cycle_id' => $cycle->id, 'user_id' => $m1->id]);
        CycleVeto::create(['cycle_id' => $cycle->id, 'user_id' => $m2->id]);

        $this->assertTrue($this->service->isVetoed($cycle->fresh()));
    }

    public function test_apply_veto_resets_beneficiary_and_redraws(): void
    {
        $owner  = User::factory()->create();
        $m1 = User::factory()->create();
        $m2 = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id, 'veto_threshold' => 50]);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $m1, 2);
        $this->attachMember($tontine, $m2, 3);

        $cycle = $this->pendingCycle($tontine);
        $this->payForAll($cycle, [$owner, $m1, $m2], $tontine->amount);
        $cycle->update(['beneficiary_id' => $owner->id]);

        // Votes suffisants
        CycleVeto::create(['cycle_id' => $cycle->id, 'user_id' => $m1->id]);
        CycleVeto::create(['cycle_id' => $cycle->id, 'user_id' => $m2->id]);

        $applied = $this->service->applyVetoIfThresholdReached($cycle->fresh());

        $this->assertTrue($applied);
        // Le re-tirage a attribué un bénéficiaire
        $this->assertNotNull($cycle->fresh()->beneficiary_id);
    }

    // ── 4. Enchères ───────────────────────────────────────────────────────

    public function test_auction_winner_is_highest_bidder(): void
    {
        $owner  = User::factory()->create();
        $m1 = User::factory()->create();
        $m2 = User::factory()->create();
        $tontine = $this->activeTontine([
            'created_by' => $owner->id,
            'type'       => 'auction',
            'amount'     => 10000,
        ]);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $m1, 2);
        $this->attachMember($tontine, $m2, 3);

        $cycle = $this->pendingCycle($tontine);
        $this->payForAll($cycle, [$owner, $m1, $m2], $tontine->amount);

        AuctionBid::create(['cycle_id' => $cycle->id, 'user_id' => $m1->id, 'bid_rate' => 5.0]);
        AuctionBid::create(['cycle_id' => $cycle->id, 'user_id' => $m2->id, 'bid_rate' => 10.0]);

        $this->service->drawBeneficiary($cycle->fresh());

        // m2 a le taux le plus élevé → gagne
        $this->assertEquals($m2->id, $cycle->fresh()->beneficiary_id);
    }

    public function test_auction_redistribution_is_created_for_losers(): void
    {
        $owner  = User::factory()->create();
        $m1 = User::factory()->create();
        $tontine = $this->activeTontine([
            'created_by' => $owner->id,
            'type'       => 'auction',
            'amount'     => 10000,
        ]);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $m1, 2);

        $cycle = $this->pendingCycle($tontine);
        $this->payForAll($cycle, [$owner, $m1], $tontine->amount);

        // owner enchérit à 10% → pot 20 000 → reçoit 18 000, 2 000 redistribués à m1
        AuctionBid::create(['cycle_id' => $cycle->id, 'user_id' => $owner->id, 'bid_rate' => 10.0]);

        $this->service->drawBeneficiary($cycle->fresh());

        $this->assertDatabaseHas('transactions', [
            'cycle_id' => $cycle->id,
            'user_id'  => $m1->id,
            'type'     => 'redistribution',
            'status'   => 'success',
        ]);
    }

    public function test_can_draw_auction_requires_at_least_one_bid(): void
    {
        $owner  = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id, 'type' => 'auction']);
        $this->attachMember($tontine, $owner, 1);

        $cycle = $this->pendingCycle($tontine);

        $this->assertNotNull($this->service->canDraw($cycle->fresh()));
    }

    // ── 5. Tirage pondéré (weighted_draw) ─────────────────────────────────

    public function test_weighted_draw_assigns_a_beneficiary(): void
    {
        $owner  = User::factory()->create();
        $m1 = User::factory()->create();
        $tontine = $this->activeTontine([
            'created_by'   => $owner->id,
            'weighted_draw' => true,
        ]);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $m1, 2);

        CreditScore::create(['user_id' => $owner->id, 'score' => 8.0, 'badge' => 'gold']);
        CreditScore::create(['user_id' => $m1->id,   'score' => 4.0, 'badge' => 'bronze']);

        $cycle = $this->pendingCycle($tontine);
        $this->payForAll($cycle, [$owner, $m1], $tontine->amount);

        $this->service->drawBeneficiary($cycle->fresh());

        $this->assertNotNull($cycle->fresh()->beneficiary_id);
        $this->assertContains($cycle->fresh()->beneficiary_id, [$owner->id, $m1->id]);
    }

    // ── 6. Forced saving et ceremonial ignorent drawBeneficiary ──────────

    public function test_forced_saving_draw_does_nothing(): void
    {
        $owner = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id, 'type' => 'forced_saving']);
        $this->attachMember($tontine, $owner, 1);

        $cycle = $this->pendingCycle($tontine);

        $this->service->drawBeneficiary($cycle->fresh());

        $this->assertNull($cycle->fresh()->beneficiary_id);
    }

    public function test_ceremonial_draw_does_nothing(): void
    {
        $owner = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id, 'type' => 'ceremonial']);
        $this->attachMember($tontine, $owner, 1);

        $cycle = $this->pendingCycle($tontine);

        $this->service->drawBeneficiary($cycle->fresh());

        $this->assertNull($cycle->fresh()->beneficiary_id);
    }

    // ── 7. Tirage forcé + dettes ──────────────────────────────────────────

    public function test_force_draw_creates_debt_for_non_payer(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id]);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $member, 2);

        $cycle = $this->pendingCycle($tontine);
        // Seul owner paie
        $this->payForAll($cycle, [$owner], $tontine->amount);

        $this->service->drawBeneficiary($cycle->fresh(), force: true);

        $this->assertDatabaseHas('tontine_debts', [
            'tontine_id' => $tontine->id,
            'user_id'    => $member->id,
            'cycle_id'   => $cycle->id,
            'status'     => 'pending',
        ]);
    }

    public function test_force_draw_eligible_only_includes_payers(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id, 'draw_method' => 'sequential']);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $member, 2);

        $cycle = $this->pendingCycle($tontine);
        // Seul owner paie → seul owner peut gagner
        $this->payForAll($cycle, [$owner], $tontine->amount);

        $this->service->drawBeneficiary($cycle->fresh(), force: true);

        $this->assertEquals($owner->id, $cycle->fresh()->beneficiary_id);
    }

    public function test_normal_draw_excludes_members_with_pending_debt(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id, 'draw_method' => 'sequential']);
        $this->attachMember($tontine, $owner, 2);
        $this->attachMember($tontine, $member, 1);

        // member a une dette pendante sur un cycle précédent
        $pastCycle = $this->pendingCycle($tontine, 0);
        TontineDebt::create([
            'tontine_id' => $tontine->id,
            'user_id'    => $member->id,
            'cycle_id'   => $pastCycle->id,
            'amount'     => $tontine->amount,
            'status'     => 'pending',
        ]);

        $cycle = $this->pendingCycle($tontine, 1);
        $this->payForAll($cycle, [$owner, $member], $tontine->amount);

        // member est à la position 1 (devrait gagner en séquentiel) mais a une dette → owner gagne
        $this->service->drawBeneficiary($cycle->fresh());

        $this->assertEquals($owner->id, $cycle->fresh()->beneficiary_id);
    }

    public function test_canDraw_force_allows_partial_payment_after_due_date(): void
    {
        $owner  = User::factory()->create();
        $member = User::factory()->create();
        $tontine = $this->activeTontine(['created_by' => $owner->id]);
        $this->attachMember($tontine, $owner, 1);
        $this->attachMember($tontine, $member, 2);

        $cycle = Cycle::factory()->create([
            'tontine_id'      => $tontine->id,
            'cycle_number'    => 1,
            'status'          => 'overdue',
            'total_collected' => $tontine->amount,
            'due_date'        => now()->subDay(),
        ]);
        $this->payForAll($cycle, [$owner], $tontine->amount);

        // Normal → bloqué
        $this->assertNotNull($this->service->canDraw($cycle->fresh()));
        // Force → autorisé
        $this->assertNull($this->service->canDraw($cycle->fresh(), force: true));
    }
}
