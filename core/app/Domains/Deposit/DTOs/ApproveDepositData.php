<?php

namespace App\Domains\Deposit\DTOs;

readonly class ApproveDepositData
{
    public function __construct(
        public int $depositId,
        public int $approvedByUserId
    ) {}
}
