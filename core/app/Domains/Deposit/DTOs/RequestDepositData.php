<?php

namespace App\Domains\Deposit\DTOs;

readonly class RequestDepositData
{
    public function __construct(
        public int $userId,
        public string $amount,
        public ?string $paymentMethod = null
    ) {}
}
