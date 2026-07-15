<?php

namespace App\Http\Controllers\Webhook;

use App\Domains\Transaction\Actions\UpdateTransactionStatus;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DigiflazzWebhookController extends Controller
{
    public function __construct(
        private readonly UpdateTransactionStatus $updateTransactionStatusAction
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $secret = config('services.digiflazz.webhook_secret');

        if (empty($secret)) {
            Log::error('Digiflazz webhook received but webhook secret is not configured.');

            return response()->json(['error' => 'Webhook secret not configured'], 500);
        }

        $signature = hash_hmac('sha1', $request->getContent(), $secret);

        $hubSignature = (string) $request->header('X-Hub-Signature');

        if (!hash_equals('sha1='.$signature, $hubSignature)) {
            Log::warning('Digiflazz webhook received with invalid signature.', [
                'received' => $hubSignature,
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = json_decode($request->getContent(), true);

        // Handle Ping event
        if (isset($payload['hook_id']) && isset($payload['sed'])) {
            Log::info('Digiflazz webhook ping received successfully.', ['hook_id' => $payload['hook_id']]);

            return response()->json(['message' => 'pong'], 200);
        }

        if (! isset($payload['data']['ref_id'])) {
            Log::warning('Digiflazz webhook received with invalid payload structure.', ['payload' => $payload]);

            return response()->json(['error' => 'Invalid payload structure'], 400);
        }

        $data = $payload['data'];
        $transactionId = $data['ref_id'];
        $digiflazzStatus = $data['status'] ?? '';
        $message = $data['message'] ?? '';
        $sn = $data['sn'] ?? '';

        $status = $this->mapStatus($digiflazzStatus);

        if ($status === TransactionStatus::PENDING) {
            // Still pending, no action needed, but acknowledge receipt
            return response()->json(['message' => 'Status remains pending'], 200);
        }

        try {
            $this->updateTransactionStatusAction->execute($transactionId, $status, $message, $sn);

            Log::info("Digiflazz webhook processed successfully for transaction {$transactionId}. New status: {$status->value}");

            return response()->json(['message' => 'Webhook processed successfully'], 200);
        } catch (Exception $e) {
            Log::error("Failed to process Digiflazz webhook for transaction {$transactionId}", ['exception' => $e->getMessage()]);

            return response()->json(['error' => 'Internal server error processing webhook'], 500);
        }
    }

    private function mapStatus(string $digiflazzStatus): TransactionStatus
    {
        return match (strtolower($digiflazzStatus)) {
            'sukses' => TransactionStatus::SUCCESS,
            'gagal' => TransactionStatus::FAILED,
            default => TransactionStatus::PENDING,
        };
    }
}
