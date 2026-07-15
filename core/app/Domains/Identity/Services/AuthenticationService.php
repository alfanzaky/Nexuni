<?php

namespace App\Domains\Identity\Services;

use App\Domains\Identity\Actions\AuthenticateUser;
use App\Domains\Identity\Actions\RegisterUser;
use App\Domains\Identity\DTOs\LoginUserData;
use App\Domains\Identity\DTOs\RegisterUserData;
use App\Domains\Identity\Models\User;

class AuthenticationService
{
    public function __construct(
        private readonly RegisterUser $registerUser,
        private readonly AuthenticateUser $authenticateUser,
    ) {}

    public function register(RegisterUserData $data): User
    {
        return $this->registerUser->execute($data);
    }

    public function login(LoginUserData $data): array
    {
        $user = $this->authenticateUser->execute($data);

        $token = $user->createToken($data->deviceName)->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
