<?php

namespace Tests\Feature;

use App\Models\Tontine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TontineTest extends TestCase
{
    use RefreshDatabase;

    // ── 1. Créer une tontine ───────────────────────────────────────────────

    public function test_authenticated_user_can_create_tontine(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tontines', [
            'name'        => 'Tontine Test',
            'amount'      => 10000,
            'frequency'   => 'monthly',
            'type'        => 'fixed',
            'start_date'  => now()->addDays(5)->toDateString(),
            'max_members' => 5,
            'draw_method' => 'sequential',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tontines', ['name' => 'Tontine Test', 'created_by' => $user->id]);
        $this->assertDatabaseHas('tontine_members', ['user_id' => $user->id, 'status' => 'active']);
    }

    public function test_guest_cannot_create_tontine(): void
    {
        $this->post('/tontines', ['name' => 'Test'])->assertRedirect(route('auth.login'));
    }

    // ── 2. Rejoindre une tontine ───────────────────────────────────────────

    public function test_user_can_join_tontine_with_valid_code(): void
    {
        $owner   = User::factory()->create();
        $member  = User::factory()->create();
        $tontine = Tontine::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($member)->post('/tontines/join', [
            'code' => $tontine->code,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tontine_members', [
            'tontine_id' => $tontine->id,
            'user_id'    => $member->id,
            'status'     => 'pending',
        ]);
    }

    public function test_user_cannot_join_with_invalid_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->post('/tontines/join', ['code' => 'XXXXXX'])
             ->assertRedirect()
             ->assertSessionHasErrors('code');
    }

    // ── 3. Supprimer une tontine ───────────────────────────────────────────

    public function test_creator_can_delete_pending_tontine(): void
    {
        $user    = User::factory()->create();
        $tontine = Tontine::factory()->create(['created_by' => $user->id, 'status' => 'pending']);

        $this->actingAs($user)
             ->delete("/tontines/{$tontine->id}")
             ->assertRedirect('/tontines');

        $this->assertSoftDeleted('tontines', ['id' => $tontine->id]);
    }

    public function test_non_creator_cannot_delete_tontine(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $tontine = Tontine::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($other)
             ->delete("/tontines/{$tontine->id}")
             ->assertForbidden();
    }
}
