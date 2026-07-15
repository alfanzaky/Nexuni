<?php

namespace App\Domains\Transaction\DTOs;

readonly class CreateTransactionData
{
    public function __construct(
        public int $userId,
        public int $productId,
        public string $destination,
        public string $idempotencyKey
    ) {}
}
