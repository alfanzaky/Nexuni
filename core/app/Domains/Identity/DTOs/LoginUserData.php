<?php

namespace App\Domains\Identity\DTOs;

readonly class LoginUserData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName = 'default',
    ) {}
}
