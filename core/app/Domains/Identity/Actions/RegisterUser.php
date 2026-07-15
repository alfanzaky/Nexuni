<?php

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\DTOs\RegisterUserData;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterUser
{
    public function execute(RegisterUserData $data): User
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'password' => Hash::make($data->password),
            'role' => $data->role,
            'is_active' => $data->isActive,
        ]);
    }
}
