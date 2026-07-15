<?php

namespace App\Domains\Reseller\DTOs;

readonly class CreateResellerGroupData
{
    public function __construct(
        public string $name,
        public int $level,
        public ?string $description = null,
    ) {}
}
