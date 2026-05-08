<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CreditScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_gets_zero_score(): void
    {
        $user  = User::factory()->create();
        $score = app(CreditScoringService::class)->calculate($user);

        $this->assertEquals(0.0, $score->score);
        $this->assertEquals('none', $score->badge);
    }

    public function test_score_is_persisted_in_database(): void
    {
        $user = User::factory()->create();
        app(CreditScoringService::class)->calculate($user);

        $this->assertDatabaseHas('credit_scores', ['user_id' => $user->id]);
    }
}
