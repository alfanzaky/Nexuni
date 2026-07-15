<?php

namespace App\Domains\Pricing\DTOs;

readonly class CalculatePriceData
{
    public function __construct(
        public int $productId,
        public int $resellerGroupId,
    ) {}
}
