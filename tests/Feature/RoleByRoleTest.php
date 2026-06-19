<?php

namespace Tests\Feature;

use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleByRoleTest extends TestCase
{
    use RefreshDatabase;

    // ── RÔLE : INVITÉ (non authentifié) ───────────────────────────────────

    public function test_guest_public_pages(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('cgu'))->assertOk();
        $this->get(route('mentions'))->assertOk();
        $this->get(route('privacy'))->assertOk();
        $this->get(route('faq.index'))->assertOk();
        $this->get(route('auth.login'))->assertOk();
        $this->get(route('auth.register'))->assertOk();
        $this->get(route('posts.index'))->assertOk();
    }

    public function test_guest_redirected_from_all_member_pages(): void
    {
        $tontine = Tontine::factory()->create();

        $this->get(route('dashboard'))->assertRedirect(route('auth.login'));
        $this->get(route('tontines.index'))->assertRedirect(route('auth.login'));
        $this->get(route('tontines.create'))->assertRedirect(route('auth.login'));
        $this->get(route('tontines.show', $tontine))->assertRedirect(route('auth.login'));
        $this->get(route('chat.index'))->assertRedirect(route('auth.login'));
        $this->get(route('historique.index'))->assertRedirect(route('auth.login'));
        $this->get(route('profile.show'))->assertRedirect(route('auth.login'));
        $this->get(route('notifications.index'))->assertRedirect(route('auth.login'));
    }

    public function test_guest_redirected_from_admin_pages(): void
    {
        $this->get('/admin')->assertRedirect(route('auth.login'));
        $this->get('/admin/users')->assertRedirect(route('auth.login'));
        $this->get('/admin/tontines')->assertRedirect(route('auth.login'));
        $this->get('/admin/transactions')->assertRedirect(route('auth.login'));
        $this->get('/admin/logs')->assertRedirect(route('auth.login'));
    }

    public function test_guest_cannot_create_tontine(): void
    {
        $this->post('/tontines', ['name' => 'Test'])->assertRedirect(route('auth.login'));
    }

    public function test_guest_cannot_join_tontine(): void
    {
        $this->post('/tontines/join', ['code' => 'XXXX'])->assertRedirect(route('auth.login'));
    }

    // ── RÔLE : MEMBRE ──────────────────────────────────────────────────────

    public function test_member_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_member_can_access_explorer(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('tontines.explore'))->assertOk();
    }

    public function test_member_can_create_all_tontine_types(): void
    {
        $user = User::factory()->create();
        $types = ['fixed', 'auction', 'forced_saving', 'ceremonial'];

        foreach ($types as $type) {
            $payload = [
                'name' => "Tontine $type",
                'amount' => 10000,
                'frequency' => 'monthly',
                'type' => $type,
                'start_date' => now()->addDays(5)->toDateString(),
                'max_members' => 5,
                'draw_method' => 'sequential',
            ];

            if (in_array($type, ['forced_saving', 'ceremonial'])) {
                $payload['end_date'] = now()->addMonths(6)->toDateString();
            }

            $response = $this->actingAs($user)->post('/tontines', $payload);
            $response->assertRedirect();
            $response->assertSessionHasNoErrors();

            $this->assertDatabaseHas('tontines', [
                'name' => "Tontine $type",
                'type' => $type,
                'created_by' => $user->id,
            ]);
        }
    }

    public function test_member_can_view_own_tontine(): void
    {
        $user = User::factory()->create();
        $tontine = Tontine::factory()->create(['created_by' => $user->id]);

        $this->actingAs($user)
            ->get(route('tontines.show', $tontine))
            ->assertOk();
    }

    public function test_member_can_view_public_tontine(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $tontine = Tontine::factory()->create([
            'created_by' => $owner->id,
            'visibility' => 'public',
        ]);

        $this->actingAs($user)
            ->get(route('tontines.show', $tontine))
            ->assertOk();
    }

    public function test_member_cannot_create_tontine_with_invalid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tontines', [
            'name' => '',
            'amount' => 0,
            'type' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['name', 'amount', 'type', 'frequency', 'start_date']);
    }

    public function test_member_can_leave_tontine(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $tontine = Tontine::factory()->create(['created_by' => $creator->id]);
        $tontine->members()->attach($member->id, ['status' => 'active', 'position' => 1]);

        $this->actingAs($member)
            ->delete(route('tontines.leave', $tontine))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_creator_cannot_leave_own_tontine(): void
    {
        $creator = User::factory()->create();
        $tontine = Tontine::factory()->create(['created_by' => $creator->id]);
        $tontine->members()->attach($creator->id, ['status' => 'active', 'position' => 1]);

        $this->actingAs($creator)
            ->delete(route('tontines.leave', $tontine))
            ->assertSessionHasErrors();
    }

    public function test_member_cannot_access_private_tontine(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $tontine = Tontine::factory()->create([
            'created_by' => $owner->id,
            'visibility' => 'private',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('tontines.show', $tontine))
            ->assertForbidden();
    }

    public function test_member_can_access_chat(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('chat.index'))->assertOk();
    }

    public function test_member_can_access_historique(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('historique.index'))->assertOk();
    }

    public function test_member_can_access_notifications(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('notifications.index'))->assertOk();
    }

    public function test_member_cannot_access_admin_pages(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get('/admin')->assertForbidden();
        $this->actingAs($member)->get('/admin/users')->assertForbidden();
        $this->actingAs($member)->get('/admin/tontines')->assertForbidden();
        $this->actingAs($member)->get('/admin/transactions')->assertForbidden();
        $this->actingAs($member)->get('/admin/logs')->assertForbidden();
    }

    public function test_explorer_shows_public_tontines(): void
    {
        $user = User::factory()->create();

        $publicTontine = Tontine::factory()->create([
            'visibility' => 'public',
            'status' => 'pending',
            'name' => 'Public Tontine Visible',
        ]);
        $privateTontine = Tontine::factory()->create([
            'visibility' => 'private',
            'status' => 'pending',
            'name' => 'Private Not Visible',
        ]);

        $response = $this->actingAs($user)->get(route('tontines.explore'));

        $response->assertOk();
        $response->assertSee('Public Tontine Visible');
        $response->assertDontSee('Private Not Visible');
    }

    // ── RÔLE : ADMIN ───────────────────────────────────────────────────────

    public function test_admin_can_access_all_admin_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users'))->assertOk();
        $this->actingAs($admin)->get(route('admin.tontines'))->assertOk();
        $this->actingAs($admin)->get(route('admin.transactions'))->assertOk();
    }

    public function test_admin_can_toggle_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.users.toggle', $user))
            ->assertRedirect();

        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_admin_can_change_user_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => 'member']);

        $this->actingAs($admin)
            ->post(route('admin.users.role', $user), ['role' => 'admin'])
            ->assertRedirect();

        $this->assertEquals('admin', $user->fresh()->role);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.role', $admin), ['role' => 'member'])
            ->assertSessionHasErrors();
    }

    public function test_super_admin_can_access_admin_pages(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($super)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($super)->get(route('admin.users'))->assertOk();
        $this->actingAs($super)->get(route('admin.tontines'))->assertOk();
    }

    public function test_admin_cannot_assign_super_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['role' => 'member']);

        $this->actingAs($admin)
            ->post(route('admin.users.role', $user), ['role' => 'super_admin'])
            ->assertSessionHasErrors(['error']);
    }

    // ── EDGE CASES ─────────────────────────────────────────────────────────

    public function test_explorer_handles_missing_description(): void
    {
        $user = User::factory()->create();
        Tontine::factory()->create([
            'visibility' => 'public',
            'description' => null,
        ]);

        $this->actingAs($user)
            ->get(route('tontines.explore'))
            ->assertOk();
    }

    public function test_explorer_handles_all_types(): void
    {
        $user = User::factory()->create();

        foreach (['fixed', 'auction', 'forced_saving', 'ceremonial'] as $type) {
            Tontine::factory()->create([
                'visibility' => 'public',
                'type' => $type,
            ]);
        }

        $this->actingAs($user)
            ->get(route('tontines.explore'))
            ->assertOk();
    }

    public function test_explorer_handles_all_frequencies(): void
    {
        $user = User::factory()->create();

        foreach (['daily', 'weekly', 'monthly'] as $freq) {
            Tontine::factory()->create([
                'visibility' => 'public',
                'frequency' => $freq,
            ]);
        }

        $this->actingAs($user)
            ->get(route('tontines.explore'))
            ->assertOk();
    }

    public function test_tontine_show_with_no_cycles(): void
    {
        $user = User::factory()->create();
        $tontine = Tontine::factory()->create([
            'created_by' => $user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get(route('tontines.show', $tontine))
            ->assertOk();
    }

    public function test_tontine_show_with_active_cycles(): void
    {
        $user = User::factory()->create();
        $tontine = Tontine::factory()->create([
            'created_by' => $user->id,
            'status' => 'active',
        ]);
        $tontine->members()->attach($user->id, ['status' => 'active', 'position' => 1]);
        foreach ([1, 2, 3] as $num) {
            Cycle::factory()->create([
                'tontine_id' => $tontine->id,
                'cycle_number' => $num,
                'due_date' => now()->addDays(7 * $num),
            ]);
        }

        $this->actingAs($user)
            ->get(route('tontines.show', $tontine))
            ->assertOk();
    }

    public function test_member_cannot_join_already_member_tontine(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $tontine = Tontine::factory()->create([
            'created_by' => $owner->id,
            'visibility' => 'public',
        ]);
        $tontine->members()->attach($member->id, ['status' => 'active', 'position' => 1]);

        $this->actingAs($member)
            ->post('/tontines/join', ['code' => $tontine->code])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_dashboard_with_active_memberships(): void
    {
        $user = User::factory()->create();
        $tontine = Tontine::factory()->create([
            'created_by' => $user->id,
            'status' => 'active',
        ]);
        $tontine->members()->attach($user->id, ['status' => 'active', 'position' => 1]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_chat_room_access_as_member(): void
    {
        $creator = User::factory()->create();
        $member = User::factory()->create();
        $tontine = Tontine::factory()->create(['created_by' => $creator->id]);
        $tontine->members()->attach($member->id, ['status' => 'active', 'position' => 1]);

        $this->actingAs($member)
            ->get(route('chat.show', $tontine))
            ->assertOk();
    }

    public function test_non_member_cannot_access_chat_room(): void
    {
        $creator = User::factory()->create();
        $stranger = User::factory()->create();
        $tontine = Tontine::factory()->create(['created_by' => $creator->id]);

        $this->actingAs($stranger)
            ->get(route('chat.show', $tontine))
            ->assertForbidden();
    }
}
