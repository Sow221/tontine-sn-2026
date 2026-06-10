<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Services\PayTechService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_missing_token(): void
    {
        $payTechMock = $this->createMock(PayTechService::class);
        $payTechMock->method('verifyWebhook')->willReturn(false);

        $this->app->instance(PayTechService::class, $payTechMock);

        $this->post(route('webhooks.paytech'), [])
            ->assertStatus(401);
    }

    public function test_webhook_verifies_and_confirms_payment(): void
    {
        $transaction = Transaction::factory()->create([
            'amount' => 50000,
            'status' => 'pending',
            'external_reference' => 'valid_token',
        ]);

        $payTechMock = $this->createMock(PayTechService::class);
        $payTechMock->method('verifyWebhook')->willReturn(true);

        $this->app->instance(PayTechService::class, $payTechMock);

        $this->post(route('webhooks.paytech'), [
            'token' => 'valid_token',
            'ref_command' => "TontineSN-{$transaction->id}",
            'amount' => 50000,
        ])->assertOk();

        $transaction->refresh();
        $this->assertEquals('success', $transaction->status);
    }

    public function test_webhook_rejects_invalid_token(): void
    {
        $payTechMock = $this->createMock(PayTechService::class);
        $payTechMock->method('verifyWebhook')->willReturn(false);

        $this->app->instance(PayTechService::class, $payTechMock);

        $this->post(route('webhooks.paytech'), [
            'token' => 'invalid_token',
        ])->assertStatus(401);
    }
}
