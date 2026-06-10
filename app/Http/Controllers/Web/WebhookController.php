<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\WebhookLog;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Services\PayTechService;
use App\Services\WhatsApp\GreenApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private PayTechService $payTechService,
        private NotificationService $notifier,
        private GreenApiService $greenApi,
    ) {}

    public function paytech(Request $request)
    {
        try {
            $data = $request->all();
            $token = $data['token'] ?? '';

            $webhookHash = hash('sha256', json_encode($data).$token);

            if (WebhookLog::where('webhook_hash', $webhookHash)->where('status', 'processed')->exists()) {
                Log::info('Webhook PayTech déjà traité', ['token' => $token]);

                return response()->json(['status' => 'ok', 'message' => 'already_processed']);
            }

            WebhookLog::create([
                'provider' => 'paytech',
                'webhook_hash' => $webhookHash,
                'payload' => $data,
                'status' => 'received',
            ]);

            if (! $this->payTechService->verifyWebhook($data)) {
                Log::warning('Webhook PayTech verification echouee', ['token' => $token]);
                WebhookLog::where('webhook_hash', $webhookHash)->update(['status' => 'failed', 'error' => 'Verification failed']);

                return response()->json(['error' => 'Verification failed'], 401);
            }

            $ref = $data['ref_command'] ?? '';
            $transactionId = str_replace('TontineSN-', '', $ref);
            $transaction = Transaction::find($transactionId);

            if (! $transaction) {
                Log::warning('Transaction introuvable pour webhook', ['ref' => $ref]);
                WebhookLog::where('webhook_hash', $webhookHash)->update(['status' => 'failed', 'error' => 'Transaction not found']);

                return response()->json(['status' => 'ok']);
            }

            if (in_array($transaction->status, ['success', 'reversed'])) {
                WebhookLog::where('webhook_hash', $webhookHash)->update(['status' => 'processed']);

                return response()->json(['status' => 'ok', 'message' => 'already_processed']);
            }

            $webhookAmount = isset($data['amount']) ? (int) $data['amount'] : null;

            if ($webhookAmount !== null && $webhookAmount !== $transaction->amount) {
                Log::warning('Webhook PayTech : montant incohérent', [
                    'transaction_id' => $transaction->id,
                    'expected' => $transaction->amount,
                    'received' => $webhookAmount,
                ]);
                WebhookLog::where('webhook_hash', $webhookHash)->update(['status' => 'failed', 'error' => 'Amount mismatch']);

                return response()->json(['error' => 'Amount mismatch'], 422);
            }

            $this->paymentService->confirmPayment($transaction, $webhookAmount);
            $transaction->load('user', 'cycle.tontine');

            if ($transaction->user && $transaction->cycle?->tontine) {
                $this->notifier->notifyPaymentConfirmed(
                    $transaction->user,
                    $transaction->amount,
                    $transaction->cycle->tontine->name,
                    $transaction->cycle->cycle_number
                );
            }

            WebhookLog::where('webhook_hash', $webhookHash)->update(['status' => 'processed']);

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('Erreur webhook PayTech', ['error' => $e->getMessage(), 'class' => get_class($e)]);

            return response()->json(['status' => 'error', 'message' => 'Internal error'], 500);
        }
    }

    public function greenapi(Request $request)
    {
        try {
            $data = $request->all();

            $webhookHash = hash('sha256', json_encode($data));

            if (WebhookLog::where('webhook_hash', $webhookHash)->where('status', 'processed')->exists()) {
                Log::info('Webhook Green API déjà traité', ['hash' => $webhookHash]);

                return response()->json(['status' => 'ok', 'message' => 'already_processed']);
            }

            WebhookLog::create([
                'provider' => 'greenapi',
                'webhook_hash' => $webhookHash,
                'payload' => $data,
                'status' => 'received',
            ]);

            $processed = $this->greenApi->processWebhook($data);

            if ($processed['type'] === 'incomingMessageReceived') {
                $messageData = $processed['message'] ?? [];
                $senderData = $processed['sender'] ?? [];

                $fromPhone = $senderData['chatId'] ?? '';
                $text = $messageData['textMessageData']['textMessage'] ?? '';
                $type = $messageData['typeMessage'] ?? '';

                Log::info('Green API message received', [
                    'from' => $fromPhone,
                    'text' => $text,
                    'type' => $type,
                ]);
            }

            WebhookLog::where('webhook_hash', $webhookHash)->update(['status' => 'processed']);

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('Erreur webhook Green API', ['error' => $e->getMessage(), 'class' => get_class($e)]);

            return response()->json(['status' => 'error', 'message' => 'Internal error'], 500);
        }
    }
}
