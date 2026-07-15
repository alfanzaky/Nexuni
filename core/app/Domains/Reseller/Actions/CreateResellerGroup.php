<?php

namespace App\Domains\Reseller\Actions;

use App\Domains\Reseller\DTOs\CreateResellerGroupData;
use App\Domains\Reseller\Models\ResellerGroup;

class CreateResellerGroup
{
    public function execute(CreateResellerGroupData $data): ResellerGroup
    {
        return ResellerGroup::create([
            'name' => $data->name,
            'level' => $data->level,
            'description' => $data->description,
        ]);
    }
}
