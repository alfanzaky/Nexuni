<?php

namespace App\Domains\Reseller\DTOs;

readonly class AssignResellerData
{
    public function __construct(
        public int $userId,
        public int $groupId,
        public string $status = 'active',
    ) {}
}
