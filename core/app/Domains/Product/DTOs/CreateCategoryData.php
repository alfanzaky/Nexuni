<?php

namespace App\Domains\Product\DTOs;

readonly class CreateCategoryData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $type = 'prepaid',
        public bool $isActive = true,
    ) {}
}
