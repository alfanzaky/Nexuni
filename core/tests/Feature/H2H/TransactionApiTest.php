<?php

namespace Tests\Feature\H2H;

use App\Domains\Financial\Enums\WalletStatus;
use App\Domains\Financial\Models\Wallet;
use App\Domains\Identity\Models\User;
use App\Domains\Partner\Models\Partner;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_h2h_transaction()
    {
        $user = User::factory()->create();
        $wallet = $user->wallet()->first();
        if ($wallet) {
            $wallet->available_balance = 100000;
            $wallet->status = WalletStatus::ACTIVE;
            $wallet->save();
        } else {
            $wallet = new Wallet;
            $wallet->user_id = $user->id;
            $wallet->available_balance = 100000;
            $wallet->status = WalletStatus::ACTIVE;
            $wallet->save();
        }

        $partner = Partner::create([
            'user_id' => $user->id,
            'name' => 'Test Partner',
            'api_key' => 'test_key',
            'api_secret' => 'test_secret',
            'is_active' => true,
        ]);

        DB::table('providers')->insert([
            'id' => 1, 'code' => 'P1', 'name' => 'Provider 1', 'is_active' => true,
        ]);
        DB::table('categories')->insert([
            'id' => 1, 'code' => 'C1', 'name' => 'Cat 1', 'is_active' => true,
        ]);

        $product = Product::create([
            'name' => 'Pulsa 10k',
            'code' => 'PULSA10',
            'category_id' => 1,
            'provider_id' => 1,
            'price' => 10500,
            'is_active' => true,
        ]);

        $payload = [
            'product_id' => $product->id,
            'destination' => '081234567890',
            'idempotency_key' => 'idemp-12345',
        ];

        $timestamp = now()->toIso8601String();
        $nonce = 'nonce-tx-123';

        $stringToSign = json_encode($payload).$timestamp.$nonce;
        $signature = hash_hmac('sha256', $stringToSign, 'test_secret');

        $response = $this->withHeaders([
            'X-API-Key' => 'test_key',
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->postJson('/api/h2h/v1/transaction', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'transaction_id',
                    'status',
                    'amount',
                    'created_at',
                ],
            ]);

        // Verify balance was held (wallet ledger logic inside CreateTransaction)
        $this->assertEquals(89500, $user->wallet->fresh()->available_balance);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'destination' => '081234567890',
            'idempotency_key' => 'idemp-12345',
        ]);
    }
}
