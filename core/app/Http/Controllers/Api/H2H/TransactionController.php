<?php

namespace App\Http\Controllers\Api\H2H;

use App\Domains\Partner\Models\Partner;
use App\Domains\Transaction\Actions\CreateTransaction;
use App\Domains\Transaction\DTOs\CreateTransactionData;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function __construct(
        private readonly CreateTransaction $createTransactionAction
    ) {}

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id' => ['required', 'integer'],
                'destination' => ['required', 'string', 'max:255'],
                'idempotency_key' => ['required', 'string', 'max:255'],
            ]);

            /** @var Partner $partner */
            $partner = $request->attributes->get('partner');

            if (! $partner || ! $partner->user_id) {
                return response()->json(['message' => 'Partner unauthenticated or invalid'], 401);
            }

            $dto = new CreateTransactionData(
                userId: $partner->user_id,
                productId: (int) $validated['product_id'],
                destination: $validated['destination'],
                idempotencyKey: $validated['idempotency_key']
            );

            $transaction = $this->createTransactionAction->execute($dto);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaction created successfully',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'status' => $transaction->status,
                    'amount' => $transaction->amount,
                    'created_at' => $transaction->created_at,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation Failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\App\Domains\Financial\Exceptions\WalletInsufficientBalanceException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        } catch (\App\Domains\Product\Exceptions\ProductInactiveException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        } catch (Exception $e) {
            Log::error('H2H Transaction Error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'status' => 'error',
                'message' => 'An internal error occurred while processing your request.',
            ], 500);
        }
    }
}
