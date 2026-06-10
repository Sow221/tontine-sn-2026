<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_login_page(): void
    {
        $this->get(route('auth.login'))->assertOk();
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->post(route('auth.login.post'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->post(route('auth.login.post'), [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ])->assertSessionHasErrors('email');
    }

    public function test_show_register_page(): void
    {
        $this->get(route('auth.register'))->assertOk();
    }

    public function test_user_can_register(): void
    {
        $this->post(route('auth.register.post'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '+221 77 123 45 67',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post(route('auth.logout'))
            ->assertRedirect(route('auth.login'));
    }
}
