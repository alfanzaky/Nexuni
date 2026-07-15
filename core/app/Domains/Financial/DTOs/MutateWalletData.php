<?php

namespace App\Domains\Financial\DTOs;

use App\Domains\Financial\Enums\LedgerType;
use Illuminate\Database\Eloquent\Model;

readonly class MutateWalletData
{
    public function __construct(
        public int $walletId,
        public LedgerType $type,
        public string $amount,
        public string $description,
        public ?Model $reference = null
    ) {}
}
