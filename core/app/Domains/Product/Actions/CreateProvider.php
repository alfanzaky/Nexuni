<?php

namespace App\Domains\Product\Actions;

use App\Domains\Product\DTOs\CreateProviderData;
use App\Domains\Product\Models\Provider;

class CreateProvider
{
    public function execute(CreateProviderData $data): Provider
    {
        return Provider::create([
            'code' => $data->code,
            'name' => $data->name,
            'is_active' => $data->isActive,
        ]);
    }
}
