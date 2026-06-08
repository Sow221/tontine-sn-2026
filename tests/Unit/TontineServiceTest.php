<?php

namespace Tests\Unit\Services;

use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CycleService;
use App\Services\DrawService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TontineServiceTest extends TestCase
{
    use RefreshDatabase;

    private CycleService $cycleService;
    private DrawService $drawService;
    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cycleService = $this->app->make(CycleService::class);
        $this->drawService = $this->app->make(DrawService::class);
        $this->paymentService = $this->app->make(PaymentService::class);
    }

    // ─── Cycle Creation Tests ─────────────────────────────────────────────────

    /**
     * Test cycle creation for tontine with multiple members.
     */
    public function test_create_cycles_generates_correct_count(): void
    {
        $tontine = Tontine::factory()
            ->has(User::factory()->count(5), 'members')
            ->create(['frequency' => 'weekly', 'start_date' => now()]);

        // Mark members as active
        $tontine->members()->updateExistingPivot(
            $tontine->members->pluck('id')->all(),
            ['status' => 'active']
        );

        $this->assertFalse($tontine->cycles()->exists());

        $this->cycleService->createCycles($tontine);

        $this->assertEquals(5, $tontine->cycles()->count());
    }

    /**
     * Test cycle creation with correct cycle numbers.
     */
    public function test_create_cycles_assigns_correct_numbers(): void
    {
        $members = User::factory()->count(3)->create();
        $tontine = Tontine::factory()->create(['frequency' => 'weekly']);

        foreach ($members as $member) {
            $tontine->members()->attach($member, ['status' => 'active', 'position' => 0]);
        }

        $this->cycleService->createCycles($tontine);

        $cycles = $tontine->cycles()->orderBy('cycle_number')->get();

        $this->assertEquals(1, $cycles[0]->cycle_number);
        $this->assertEquals(2, $cycles[1]->cycle_number);
        $this->assertEquals(3, $cycles[2]->cycle_number);
    }

    /**
     * Test cycle creation with weekly frequency.
     */
    public function test_create_cycles_weekly_frequency(): void
    {
        $members = User::factory()->count(3)->create();
        $startDate = now()->startOfDay();
        $tontine = Tontine::factory()->create([
            'frequency'  => 'weekly',
            'start_date' => $startDate,
        ]);

        foreach ($members as $member) {
            $tontine->members()->attach($member, ['status' => 'active']);
        }

        $this->cycleService->createCycles($tontine);

        $cycles = $tontine->cycles()->orderBy('cycle_number')->get();

        $this->assertEquals($startDate->toDateString(), $cycles[0]->due_date->toDateString());
        $this->assertEquals($startDate->copy()->addWeek()->toDateString(), $cycles[1]->due_date->toDateString());
        $this->assertEquals($startDate->copy()->addWeeks(2)->toDateString(), $cycles[2]->due_date->toDateString());
    }

    /**
     * Test cycle creation with monthly frequency.
     */
    public function test_create_cycles_monthly_frequency(): void
    {
        $members = User::factory()->count(3)->create();
        $startDate = now()->startOfDay();
        $tontine = Tontine::factory()->create([
            'frequency'  => 'monthly',
            'start_date' => $startDate,
        ]);

        foreach ($members as $member) {
            $tontine->members()->attach($member, ['status' => 'active']);
        }

        $this->cycleService->createCycles($tontine);

        $cycles = $tontine->cycles()->orderBy('cycle_number')->get();

        $this->assertEquals($startDate->toDateString(), $cycles[0]->due_date->toDateString());
        $this->assertEquals($startDate->copy()->addMonth()->toDateString(), $cycles[1]->due_date->toDateString());
        $this->assertEquals($startDate->copy()->addMonths(2)->toDateString(), $cycles[2]->due_date->toDateString());
    }

    /**
     * Test cycle creation with daily frequency.
     */
    public function test_create_cycles_daily_frequency(): void
    {
        $members = User::factory()->count(3)->create();
        $startDate = now()->startOfDay();
        $tontine = Tontine::factory()->create([
            'frequency'  => 'daily',
            'start_date' => $startDate,
        ]);

        foreach ($members as $member) {
            $tontine->members()->attach($member, ['status' => 'active']);
        }

        $this->cycleService->createCycles($tontine);

        $cycles = $tontine->cycles()->orderBy('cycle_number')->get();

        $this->assertEquals($startDate->toDateString(), $cycles[0]->due_date->toDateString());
        $this->assertEquals($startDate->copy()->addDay()->toDateString(), $cycles[1]->due_date->toDateString());
        $this->assertEquals($startDate->copy()->addDays(2)->toDateString(), $cycles[2]->due_date->toDateString());
    }

    /**
     * Test cycle creation sets all cycles to pending status.
     */
    public function test_create_cycles_sets_pending_status(): void
    {
        $members = User::factory()->count(3)->create();
        $tontine = Tontine::factory()->create(['frequency' => 'weekly']);

        foreach ($members as $member) {
            $tontine->members()->attach($member, ['status' => 'active']);
        }

        $this->cycleService->createCycles($tontine);

        $cycles = $tontine->cycles()->get();

        foreach ($cycles as $cycle) {
            $this->assertEquals('pending', $cycle->status);
        }
    }

    /**
     * Test cycle creation is idempotent (not creating duplicates).
     */
    public function test_create_cycles_idempotent(): void
    {
        $members = User::factory()->count(3)->create();
        $tontine = Tontine::factory()->create(['frequency' => 'weekly']);

        foreach ($members as $member) {
            $tontine->members()->attach($member, ['status' => 'active']);
        }

        $this->cycleService->createCycles($tontine);
        $firstCount = $tontine->cycles()->count();

        // Try to create again
        $this->cycleService->createCycles($tontine);
        $secondCount = $tontine->cycles()->count();

        $this->assertEquals($firstCount, $secondCount);
        $this->assertEquals(3, $secondCount);
    }

    /**
     * Test cycle creation with no active members does nothing.
     */
    public function test_create_cycles_no_active_members(): void
    {
        $members = User::factory()->count(3)->create();
        $tontine = Tontine::factory()->create(['frequency' => 'weekly']);

        // Add members but mark as inactive
        foreach ($members as $member) {
            $tontine->members()->attach($member, ['status' => 'suspended']);
        }

        $this->cycleService->createCycles($tontine);

        $this->assertFalse($tontine->cycles()->exists());
    }

    /**
     * Test cycle creation with only one active member.
     */
    public function test_create_cycles_single_member(): void
    {
        $member = User::factory()->create();
        $tontine = Tontine::factory()->create(['frequency' => 'weekly']);

        $tontine->members()->attach($member, ['status' => 'active']);

        $this->cycleService->createCycles($tontine);

        $this->assertEquals(1, $tontine->cycles()->count());
    }

    // ─── Beneficiary Drawing Tests ────────────────────────────────────────────

    /**
     * Test beneficiary drawing with random selection method.
     */
    public function test_draw_beneficiary_random_selection(): void
    {
        $members = User::factory()->count(5)->create();
        $tontine = Tontine::factory()->create([
            'frequency'   => 'weekly',
            'draw_method' => 'random',
        ]);

        foreach ($members as $member) {
            $tontine->members()->attach($member, ['status' => 'active']);
        }

        $this->cycleService->createCycles($tontine);
        $cycle = $tontine->cycles()->first();

        $this->assertNull($cycle->beneficiary_id);

        $this->drawService->drawBeneficiary($cycle);

        $cycle->refresh();

        $this->assertNotNull($cycle->beneficiary_id);
        $this->assertContains($cycle->beneficiary_id, $members->pluck('id')->all());
        $this->assertNotNull($cycle->drawn_at);
        $this->assertNotNull($cycle->draw_hash);
    }

    /**
     * Test beneficiary drawing with sequential selection method.
     */
    public function test_draw_beneficiary_sequential_selection(): void
    {
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $member3 = User::factory()->create();

        $tontine = Tontine::factory()->create([
            'frequency'   => 'weekly',
            'draw_method' => 'sequential',
        ]);

        $tontine->members()->attach($member1, ['status' => 'active', 'position' => 1]);
        $tontine->members()->attach($member2, ['status' => 'active', 'position' => 2]);
        $tontine->members()->attach($member3, ['status' => 'active', 'position' => 3]);

        $this->cycleService->createCycles($tontine);

        $cycle1 = $tontine->cycles()->where('cycle_number', 1)->first();
        $cycle2 = $tontine->cycles()->where('cycle_number', 2)->first();
        $cycle3 = $tontine->cycles()->where('cycle_number', 3)->first();

        $this->drawService->drawBeneficiary($cycle1);
        $this->drawService->drawBeneficiary($cycle2);
        $this->drawService->drawBeneficiary($cycle3);

        $this->assertEquals($member1->id, $cycle1->beneficiary_id);
        $this->assertEquals($member2->id, $cycle2->beneficiary_id);
        $this->assertEquals($member3->id, $cycle3->beneficiary_id);
    }

    /**
     * Test draw_hash is uniquely generated.
     */
    public function test_draw_beneficiary_generates_unique_hash(): void
    {
        $members = User::factory()->count(2)->create();
        $tontine = Tontine::factory()->create(['draw_method' => 'random']);

        foreach ($members as $member) {
            $tontine->members()->attach($member, ['status' => 'active']);
        }

        $this->cycleService->createCycles($tontine);

        $cycle1 = $tontine->cycles()->where('cycle_number', 1)->first();
        $cycle2 = $tontine->cycles()->where('cycle_number', 2)->first();

        $this->drawService->drawBeneficiary($cycle1);
        $this->drawService->drawBeneficiary($cycle2);

        $this->assertNotEquals($cycle1->draw_hash, $cycle2->draw_hash);
    }

    /**
     * Test beneficiary cannot be drawn twice in same tontine.
     */
    public function test_draw_beneficiary_prevents_duplicate_winners(): void
    {
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $tontine = Tontine::factory()->create(['draw_method' => 'sequential']);

        $tontine->members()->attach($member1, ['status' => 'active', 'position' => 1]);
        $tontine->members()->attach($member2, ['status' => 'active', 'position' => 2]);

        $this->cycleService->createCycles($tontine);

        $cycle1 = $tontine->cycles()->where('cycle_number', 1)->first();
        $cycle2 = $tontine->cycles()->where('cycle_number', 2)->first();

        $this->drawService->drawBeneficiary($cycle1);
        $cycle1->refresh();

        $winner1 = $cycle1->beneficiary_id;

        $this->drawService->drawBeneficiary($cycle2);
        $cycle2->refresh();

        $winner2 = $cycle2->beneficiary_id;

        $this->assertNotEquals($winner1, $winner2);
    }

    /**
     * Test drawing beneficiary with inactive members excluded.
     */
    public function test_draw_beneficiary_excludes_inactive_members(): void
    {
        $activeMember = User::factory()->create();
        $inactiveMember = User::factory()->create();

        $tontine = Tontine::factory()->create(['draw_method' => 'random']);

        $tontine->members()->attach($activeMember, ['status' => 'active']);
        $tontine->members()->attach($inactiveMember, ['status' => 'suspended']);

        $this->cycleService->createCycles($tontine);
        $cycle = $tontine->cycles()->first();

        $this->drawService->drawBeneficiary($cycle);

        $cycle->refresh();

        $this->assertEquals($activeMember->id, $cycle->beneficiary_id);
    }

    /**
     * Test draw returns early if no eligible members remain.
     */
    public function test_draw_beneficiary_all_members_drawn(): void
    {
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $tontine = Tontine::factory()->create(['draw_method' => 'sequential']);

        $tontine->members()->attach($member1, ['status' => 'active', 'position' => 1]);
        $tontine->members()->attach($member2, ['status' => 'active', 'position' => 2]);

        $this->cycleService->createCycles($tontine);

        $cycles = $tontine->cycles()->get();

        $this->drawService->drawBeneficiary($cycles[0]);
        $this->drawService->drawBeneficiary($cycles[1]);

        // Manually create a third cycle
        $cycle3 = Cycle::create([
            'tontine_id'   => $tontine->id,
            'cycle_number' => 3,
            'due_date'     => now(),
            'status'       => 'pending',
        ]);

        $this->drawService->drawBeneficiary($cycle3);

        $cycle3->refresh();

        // No more eligible members, beneficiary should remain null
        $this->assertNull($cycle3->beneficiary_id);
    }

    // ─── Payment Recording Tests ───────────────────────────────────────────────

    /**
     * Test recording cash payment.
     */
    public function test_record_payment_cash(): void
    {
        $cycle = Cycle::factory()->create(['due_date' => now()->addDays(5)]);
        $user = User::factory()->create();

        $transaction = $this->paymentService->recordPayment(
            $cycle,
            $user->id,
            100000,
            'cash'
        );

        $this->assertEquals($user->id, $transaction->user_id);
        $this->assertEquals($cycle->id, $transaction->cycle_id);
        $this->assertEquals(100000, $transaction->amount);
        $this->assertEquals('pending', $transaction->status);
        $this->assertNull($transaction->paid_at);

        $this->paymentService->confirmPayment($transaction);
        $transaction->refresh();
        $this->assertEquals('success', $transaction->status);
        $this->assertNotNull($transaction->paid_at);
    }

    /**
     * Test recording digital payment (pending).
     */
    public function test_record_payment_digital(): void
    {
        $cycle = Cycle::factory()->create();
        $user = User::factory()->create();

        $transaction = $this->paymentService->recordPayment(
            $cycle,
            $user->id,
            50000,
            'wave',
            'ref_123'
        );

        $this->assertEquals('wave', $transaction->method);
        $this->assertEquals('ref_123', $transaction->external_reference);
        $this->assertEquals('pending', $transaction->status);
        $this->assertNull($transaction->paid_at);
    }

    /**
     * Test payment applies penalty when cycle overdue.
     */
    public function test_record_payment_applies_penalty(): void
    {
        $tontine = Tontine::factory()->create(['penalty_rate' => 10]);
        $cycle = Cycle::factory()->create([
            'tontine_id' => $tontine->id,
            'due_date'   => now()->subDays(3),
            'status'     => 'overdue',
        ]);

        $user = User::factory()->create();

        $transaction = $this->paymentService->recordPayment(
            $cycle,
            $user->id,
            100000,
            'cash'
        );

        $expectedAmount = 100000 + (int)round(100000 * 10 / 100);

        $this->assertEquals($expectedAmount, $transaction->amount);
    }

    /**
     * Test payment without penalty when cycle not overdue.
     */
    public function test_record_payment_no_penalty_when_on_time(): void
    {
        $tontine = Tontine::factory()->create(['penalty_rate' => 10]);
        $cycle = Cycle::factory()->create([
            'tontine_id' => $tontine->id,
            'due_date'   => now()->addDays(3),
            'status'     => 'pending',
        ]);

        $user = User::factory()->create();

        $transaction = $this->paymentService->recordPayment(
            $cycle,
            $user->id,
            100000,
            'cash'
        );

        $this->assertEquals(100000, $transaction->amount);
    }

    /**
     * Test payment without penalty when rate is zero.
     */
    public function test_record_payment_no_penalty_rate_zero(): void
    {
        $tontine = Tontine::factory()->create(['penalty_rate' => 0]);
        $cycle = Cycle::factory()->create([
            'tontine_id' => $tontine->id,
            'due_date'   => now()->subDays(5),
            'status'     => 'overdue',
        ]);

        $user = User::factory()->create();

        $transaction = $this->paymentService->recordPayment(
            $cycle,
            $user->id,
            100000,
            'cash'
        );

        $this->assertEquals(100000, $transaction->amount);
    }

    // ─── Payment Confirmation Tests ────────────────────────────────────────────

    /**
     * Test confirming pending payment.
     */
    public function test_confirm_payment_success(): void
    {
        $cycle = Cycle::factory()->create();
        $transaction = Transaction::factory()->create([
            'cycle_id' => $cycle->id,
            'status'   => 'pending',
            'paid_at'  => null,
        ]);

        $this->paymentService->confirmPayment($transaction);

        $transaction->refresh();

        $this->assertEquals('success', $transaction->status);
        $this->assertNotNull($transaction->paid_at);
    }

    /**
     * Test confirming already successful payment is idempotent.
     */
    public function test_confirm_payment_idempotent(): void
    {
        $cycle = Cycle::factory()->create();
        $paidAt = now()->subHours(1);

        $transaction = Transaction::factory()->create([
            'cycle_id' => $cycle->id,
            'status'   => 'success',
            'paid_at'  => $paidAt,
        ]);

        $this->paymentService->confirmPayment($transaction);

        $transaction->refresh();

        $this->assertEquals('success', $transaction->status);
        // paid_at might be updated, but remains set
        $this->assertNotNull($transaction->paid_at);
    }

    /**
     * Test cycle total is updated after payment confirmation.
     */
    public function test_confirm_payment_updates_cycle_total(): void
    {
        $cycle = Cycle::factory()->create([
            'total_collected' => 0,
            'status'          => 'pending',
        ]);

        $transaction = Transaction::factory()->create([
            'cycle_id' => $cycle->id,
            'amount'   => 150000,
            'status'   => 'pending',
        ]);

        $this->paymentService->confirmPayment($transaction);

        $cycle->refresh();

        $this->assertEquals(150000, $cycle->total_collected);
    }

    /**
     * Test multiple payments update cycle status correctly.
     */
    public function test_confirm_payment_multiple_transactions(): void
    {
        $tontine = Tontine::factory()->create(['amount' => 100000]);
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();

        $tontine->members()->attach([$member1->id, $member2->id], ['status' => 'active']);

        $cycle = Cycle::factory()->create(['tontine_id' => $tontine->id]);

        $t1 = Transaction::factory()->create([
            'cycle_id' => $cycle->id,
            'user_id'  => $member1->id,
            'amount'   => 100000,
            'status'   => 'pending',
        ]);

        $t2 = Transaction::factory()->create([
            'cycle_id' => $cycle->id,
            'user_id'  => $member2->id,
            'amount'   => 100000,
            'status'   => 'pending',
        ]);

        $this->paymentService->confirmPayment($t1);

        $cycle->refresh();
        $this->assertEquals('partial', $cycle->status);
        $this->assertEquals(100000, $cycle->total_collected);

        $this->paymentService->confirmPayment($t2);

        $cycle->refresh();
        $this->assertEquals('paid', $cycle->status);
        $this->assertEquals(200000, $cycle->total_collected);
    }
}
