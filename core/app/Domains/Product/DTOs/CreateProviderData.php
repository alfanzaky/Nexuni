<?php

namespace App\Domains\Product\DTOs;

readonly class CreateProviderData
{
    public function __construct(
        public string $code,
        public string $name,
        public bool $isActive = true,
    ) {}
}
