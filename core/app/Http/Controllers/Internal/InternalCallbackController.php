<?php

namespace App\Http\Controllers\Internal;

use App\Domains\Transaction\Actions\UpdateTransactionStatus;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InternalCallbackController extends Controller
{
    public function __construct(
        private readonly UpdateTransactionStatus $updateStatusAction
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'required|string',
            'status' => 'required|string|in:SUCCESS,FAILED',
            'message' => 'nullable|string',
            'sn' => 'nullable|string',
        ]);

        try {
            $status = TransactionStatus::from($validated['status']);
            $this->updateStatusAction->execute(
                $validated['transaction_id'],
                $status,
                $validated['message'] ?? '',
                $validated['sn'] ?? ''
            );

            return response()->json(['message' => 'Callback processed successfully']);
        } catch (\Exception $e) {
            Log::error('Failed to process internal callback', [
                'payload' => $validated,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
