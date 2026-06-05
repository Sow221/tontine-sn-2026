<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_profile(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
             ->get(route('profile.show'))
             ->assertOk();
    }

    public function test_update_profile(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
             ->put(route('profile.update'), [
                 'name'  => 'New Name',
                 'email' => $user->email,
             ])
             ->assertRedirect()
             ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'New Name']);
    }

    public function test_guest_cannot_access_profile(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('auth.login'));
    }
}
