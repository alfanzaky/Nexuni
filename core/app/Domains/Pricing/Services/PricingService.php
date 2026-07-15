<?php

namespace App\Domains\Pricing\Services;

use App\Domains\Pricing\Actions\CalculateFinalPrice;
use App\Domains\Pricing\Actions\CreateMargin;
use App\Domains\Pricing\DTOs\CalculatePriceData;
use App\Domains\Pricing\DTOs\CreateMarginData;
use App\Domains\Pricing\Models\Margin;

class PricingService
{
    public function __construct(
        private readonly CreateMargin $createMargin,
        private readonly CalculateFinalPrice $calculateFinalPrice,
    ) {}

    public function setMargin(CreateMarginData $data): Margin
    {
        return $this->createMargin->execute($data);
    }

    public function calculateFinalPrice(CalculatePriceData $data): float
    {
        return $this->calculateFinalPrice->execute($data);
    }
}
