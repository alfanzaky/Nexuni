<?php

namespace App\Domains\Reseller\Actions;

use App\Domains\Reseller\DTOs\AssignResellerData;
use App\Domains\Reseller\Models\Reseller;

class AssignReseller
{
    public function execute(AssignResellerData $data): Reseller
    {
        return Reseller::create([
            'user_id' => $data->userId,
            'group_id' => $data->groupId,
            'status' => $data->status,
        ]);
    }
}
