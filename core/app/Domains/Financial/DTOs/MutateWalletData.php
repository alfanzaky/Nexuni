<?php

namespace App\Domains\Financial\DTOs;

use Illuminate\Database\Eloquent\Model;

readonly class MutateWalletData
{
    public function __construct(
        public int $walletId,
        public string $type, // 'credit' or 'debit'
        public string $amount,
        public string $description,
        public ?Model $reference = null
    ) {}
}
