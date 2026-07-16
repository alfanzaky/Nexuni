<?php

namespace App\Domains\Partner\Actions;

use App\Domains\Financial\Enums\WalletStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Partner\Models\Partner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreatePartner
{
    /**
     * @param array{
     *   name: string,
     *   email: string,
     *   phone: string,
     *   password?: string,
     *   webhook_url?: string,
     *   rate_limit?: int,
     *   is_active?: bool
     * } $data
     */
    public function execute(array $data): Partner
    {
        return DB::transaction(function () use ($data) {
            // 1. Check or Create User for the Partner
            $user = User::firstOrNew(['email' => $data['email']]);
            $user->name = $data['name'];
            $user->phone = $data['phone'];
            $user->role = 'partner'; // Ensure the user has the correct role
            
            // Only set/overwrite password if creating a new user, or if explicitly provided
            if (! $user->exists || isset($data['password'])) {
                $user->password = $data['password'] ?? Str::random(12);
            }
            $user->save();

            // 2. Ensure User has a wallet (Wallet should be created usually, but we handle it just in case)
            if (! $user->wallet()->exists()) {
                $user->wallet()->create([
                    'status' => WalletStatus::ACTIVE,
                ]);
            }

            // 3. Generate API Key and Secret
            $apiKey = 'nx_'.Str::random(32);
            $apiSecret = Str::random(64); // Will be encrypted by the model cast

            // 4. Create Partner
            $partner = Partner::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'webhook_url' => $data['webhook_url'] ?? null,
                'is_active' => $data['is_active'] ?? false,
                'rate_limit' => $data['rate_limit'] ?? 60,
            ]);

            // Expose the plain api_secret temporarily on creation so it can be shown to the user once.
            $partner->plain_secret = $apiSecret;

            return $partner;
        });
    }
}
