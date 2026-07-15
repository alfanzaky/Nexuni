<?php

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\DTOs\RegisterUserData;
use App\Domains\Identity\Models\User;

class RegisterUser
{
    public function execute(RegisterUserData $data): User
    {
        $user = new User([
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'password' => $data->password,
        ]);

        $user->role = $data->role;
        $user->is_active = $data->isActive;
        $user->save();

        return $user;
    }
}
