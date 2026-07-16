<?php

namespace App\Domains\Partner\Actions;

use App\Domains\Partner\Models\Partner;
use Illuminate\Support\Str;

class RotatePartnerSecret
{
    /**
     * Rotates the API secret for a given partner.
     */
    public function execute(Partner $partner): Partner
    {
        $newSecret = Str::random(64);

        $partner->api_secret = $newSecret;
        $partner->save();

        // Expose the plain secret temporarily for the user response.
        $partner->plain_secret = $newSecret;

        return $partner;
    }
}
