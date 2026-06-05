<?php

namespace Tests\Feature;

use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_payment_form(): void
    {
        $owner   = User::factory()->create();
        $member  = User::factory()->create();
        $tontine = Tontine::factory()->create(['created_by' => $owner->id, 'status' => 'active']);
        $tontine->members()->attach($member->id, ['status' => 'active', 'position' => 1]);
        $cycle = Cycle::factory()->create(['tontine_id' => $tontine->id, 'status' => 'pending']);

        $this->actingAs($member)
             ->get(route('cycles.pay', $cycle))
             ->assertOk();
    }

    public function test_guest_cannot_access_payment(): void
    {
        $owner   = User::factory()->create();
        $tontine = Tontine::factory()->create(['created_by' => $owner->id]);
        $cycle   = Cycle::factory()->create(['tontine_id' => $tontine->id]);

        $this->get(route('cycles.pay', $cycle))->assertRedirect(route('auth.login'));
    }
}
