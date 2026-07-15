<?php

namespace App\Domains\Identity\DTOs;

readonly class RegisterUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public string $password,
        public string $role = 'reseller',
        public bool $isActive = true,
    ) {}
}
