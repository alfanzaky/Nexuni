<?php

namespace App\Domains\Reseller\Services;

use App\Domains\Reseller\Actions\AssignReseller;
use App\Domains\Reseller\Actions\CreateResellerGroup;
use App\Domains\Reseller\DTOs\AssignResellerData;
use App\Domains\Reseller\DTOs\CreateResellerGroupData;
use App\Domains\Reseller\Models\Reseller;
use App\Domains\Reseller\Models\ResellerGroup;

class ResellerService
{
    public function __construct(
        private readonly CreateResellerGroup $createResellerGroup,
        private readonly AssignReseller $assignReseller,
    ) {}

    public function createGroup(CreateResellerGroupData $data): ResellerGroup
    {
        return $this->createResellerGroup->execute($data);
    }

    public function assignToGroup(AssignResellerData $data): Reseller
    {
        return $this->assignReseller->execute($data);
    }
}
