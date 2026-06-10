<?php

namespace Tests\Unit\Services;

use App\Models\Cycle;
use App\Models\Transaction;
use App\Services\LoggingService;
use App\Services\PayTechService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PayTechServiceTest extends TestCase
{
    use RefreshDatabase;

    private PayTechService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mobilemoney.paytech.api_key' => 'test_api_key',
            'mobilemoney.paytech.api_secret' => 'test_api_secret',
            'mobilemoney.paytech.base_url' => 'https://paytech-test.sn',
            'mobilemoney.paytech.timeout' => 30,
            'mobilemoney.paytech.currency' => 'XOF',
        ]);

        $loggingService = $this->createMock(LoggingService::class);
        $this->service = new PayTechService($loggingService);
    }

    // ─── Initiate Payment Tests ──────────────────────────────────────────────

    /**
     * Test successful payment initiation with valid transaction.
     */
    public function test_initiate_payment_success(): void
    {
        $transaction = Transaction::factory()->create(['amount' => 50000]);

        Http::fake([
            'https://paytech-test.sn/api/payment/request-payment' => Http::response([
                'success' => 1,
                'token' => 'test_payment_token_123',
            ]),
        ]);

        $result = $this->service->initiatePayment($transaction);

        $this->assertTrue($result['success']);
        $this->assertEquals('https://paytech.sn/payment/checkout/test_payment_token_123', $result['redirect_url']);

        $transaction->refresh();
        $this->assertEquals('test_payment_token_123', $transaction->external_reference);
    }

    /**
     * Test payment initiation with PayTech API error response.
     */
    public function test_initiate_payment_api_error(): void
    {
        $transaction = Transaction::factory()->create(['amount' => 50000]);

        Http::fake([
            'https://paytech-test.sn/api/payment/request-payment' => Http::response([
                'success' => 0,
                'errors' => ['Erreur de configuration PayTech'],
            ]),
        ]);

        $result = $this->service->initiatePayment($transaction);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Erreur de configuration PayTech', $result['error']);
    }

    /**
     * Test payment initiation with HTTP request failure.
     */
    public function test_initiate_payment_http_failure(): void
    {
        $transaction = Transaction::factory()->create(['amount' => 50000]);

        Http::fake([
            'https://paytech-test.sn/api/payment/request-payment' => Http::response(
                ['error' => 'Server Error'],
                500
            ),
        ]);

        $result = $this->service->initiatePayment($transaction);

        $this->assertFalse($result['success']);
        $this->assertEquals('Erreur de connexion PayTech', $result['error']);
    }

    /**
     * Test payment initiation with network exception.
     */
    public function test_initiate_payment_network_exception(): void
    {
        $transaction = Transaction::factory()->create(['amount' => 50000]);

        Http::fake([
            'https://paytech-test.sn/api/payment/request-payment' => fn () => throw new \Exception('Network timeout'),
        ]);

        $result = $this->service->initiatePayment($transaction);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Erreur de paiement', $result['error']);
    }

    /**
     * Test payment initiation includes correct payload structure.
     */
    public function test_initiate_payment_sends_correct_payload(): void
    {
        $cycle = Cycle::factory()->create();
        $transaction = Transaction::factory()->create([
            'amount' => 75000,
            'cycle_id' => $cycle->id,
        ]);

        Http::fake([
            'https://paytech-test.sn/api/payment/request-payment' => Http::response(['success' => 1, 'token' => 'tok_123']),
        ]);

        $this->service->initiatePayment($transaction);

        Http::assertSent(function ($request) use ($transaction, $cycle) {
            $data = json_decode($request->body(), true);

            return $data['item_price'] === 75000
                && $data['ref_command'] === "TontineSN-{$transaction->id}"
                && $data['command_name'] === 'Cotisation tontine'
                && $data['currency'] === 'XOF'
                && $data['item_name'] === "Cotisation TontineSN #{$cycle->id}"
                && isset($data['ipn_url'])
                && isset($data['success_url'])
                && isset($data['cancel_url']);
        });
    }

    /**
     * Test payment initiation with missing token in response.
     */
    public function test_initiate_payment_missing_token_in_response(): void
    {
        $transaction = Transaction::factory()->create(['amount' => 50000]);

        Http::fake([
            'https://paytech-test.sn/api/payment/request-payment' => Http::response([
                'success' => 1,
                'token' => null,
            ]),
        ]);

        $result = $this->service->initiatePayment($transaction);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Token manquant', $result['error']);
    }

    // ─── Webhook Verification Tests ───────────────────────────────────────────

    /**
     * Test successful webhook verification with completed payment.
     */
    public function test_verify_webhook_success(): void
    {
        $transaction = Transaction::factory()->create(['amount' => 50000]);

        Http::fake([
            'https://paytech-test.sn/api/payment/details/token_123' => Http::response([
                'success' => 1,
                'payment_status' => 'completed',
                'amount' => 50000,
            ]),
        ]);

        $result = $this->service->verifyWebhook(['token' => 'token_123']);
        $this->assertTrue($result);
    }

    /**
     * Test webhook verification fails with incomplete payment.
     */
    public function test_verify_webhook_incomplete_payment(): void
    {
        Http::fake([
            'https://paytech-test.sn/api/payment/details/token_123' => Http::response([
                'success' => 1,
                'payment_status' => 'pending',
            ]),
        ]);

        $result = $this->service->verifyWebhook(['token' => 'token_123']);

        $this->assertFalse($result);
    }

    /**
     * Test webhook verification fails when API returns success = 0.
     */
    public function test_verify_webhook_api_error(): void
    {
        Http::fake([
            'https://paytech-test.sn/api/payment/details/token_123' => Http::response([
                'success' => 0,
                'error' => 'Payment not found',
            ]),
        ]);

        $result = $this->service->verifyWebhook(['token' => 'token_123']);

        $this->assertFalse($result);
    }

    /**
     * Test webhook verification fails with HTTP error.
     */
    public function test_verify_webhook_http_error(): void
    {
        Http::fake([
            'https://paytech-test.sn/api/payment/details/token_123' => Http::response(
                ['error' => 'Not Found'],
                404
            ),
        ]);

        $result = $this->service->verifyWebhook(['token' => 'token_123']);

        $this->assertFalse($result);
    }

    /**
     * Test webhook verification fails with exception.
     */
    public function test_verify_webhook_exception(): void
    {
        Http::fake([
            'https://paytech-test.sn/api/payment/details/token_123' => fn () => throw new \Exception('Connection refused'),
        ]);

        $result = $this->service->verifyWebhook(['token' => 'token_123']);

        $this->assertFalse($result);
    }

    /**
     * Test webhook verification handles empty token string.
     */
    public function test_verify_webhook_empty_token(): void
    {
        Http::fake([
            'https://paytech-test.sn/api/payment/details/' => Http::response(
                ['error' => 'Invalid token'],
                400
            ),
        ]);

        $result = $this->service->verifyWebhook(['token' => '']);

        $this->assertFalse($result);
    }

    /**
     * Test payment initiation always uses production environment.
     */
    public function test_initiate_payment_uses_production_environment(): void
    {
        $transaction = Transaction::factory()->create(['amount' => 50000]);

        Http::fake([
            'https://paytech-test.sn/api/payment/request-payment' => Http::response(['success' => 1, 'token' => 'tok']),
        ]);

        $this->service->initiatePayment($transaction);

        Http::assertSent(function ($request) {
            $data = json_decode($request->body(), true);

            return $data['env'] === 'prod';
        });
    }

    /**
     * Test payment initiation includes auth headers.
     */
    public function test_initiate_payment_includes_auth_headers(): void
    {
        $transaction = Transaction::factory()->create(['amount' => 50000]);

        Http::fake([
            'https://paytech-test.sn/api/payment/request-payment' => Http::response(['success' => 1, 'token' => 'tok']),
        ]);

        $this->service->initiatePayment($transaction);

        Http::assertSent(function ($request) {
            return ($request->header('API_KEY')[0] ?? null) === 'test_api_key'
                && ($request->header('API_SECRET')[0] ?? null) === 'test_api_secret';
        });
    }

    /**
     * Test multiple payment initiations update references correctly.
     */
    public function test_initiate_multiple_payments(): void
    {
        $transaction1 = Transaction::factory()->create(['amount' => 50000]);
        $transaction2 = Transaction::factory()->create(['amount' => 75000]);

        Http::fake([
            'https://paytech-test.sn/api/payment/request-payment' => Http::sequence()
                ->push(['success' => 1, 'token' => 'token_first'])
                ->push(['success' => 1, 'token' => 'token_second']),
        ]);

        $result1 = $this->service->initiatePayment($transaction1);
        $result2 = $this->service->initiatePayment($transaction2);

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);

        $transaction1->refresh();
        $transaction2->refresh();

        $this->assertEquals('token_first', $transaction1->external_reference);
        $this->assertEquals('token_second', $transaction2->external_reference);
    }
}
