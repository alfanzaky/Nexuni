<?php

namespace App\Domains\Pricing\DTOs;

readonly class CreateMarginData
{
    public function __construct(
        public int $resellerGroupId,
        public float $amount = 0.0,
        public float $percentage = 0.0,
        public ?int $categoryId = null,
        public ?int $providerId = null,
        public ?int $productId = null,
        public bool $isActive = true,
    ) {}
}
