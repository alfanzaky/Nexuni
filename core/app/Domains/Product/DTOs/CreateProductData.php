<?php

namespace App\Domains\Product\DTOs;

readonly class CreateProductData
{
    public function __construct(
        public int $providerId,
        public int $categoryId,
        public string $code,
        public string $name,
        public float $price,
        public ?string $description = null,
        public bool $isActive = true,
    ) {}
}
