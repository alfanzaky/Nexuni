<?php

namespace App\Domains\Deposit\Actions;

use App\Domains\Deposit\DTOs\RequestDepositData;
use App\Domains\Deposit\Enums\DepositStatus;
use App\Domains\Deposit\Models\Deposit;
use InvalidArgumentException;

class RequestDeposit
{
    public function execute(RequestDepositData $data): Deposit
    {
        if (bccomp($data->amount, '0', 2) <= 0) {
            throw new InvalidArgumentException('Deposit amount must be greater than zero.');
        }

        return Deposit::create([
            'user_id' => $data->userId,
            'amount' => $data->amount,
            'status' => DepositStatus::PENDING,
            'payment_method' => $data->paymentMethod,
        ]);
    }
}
