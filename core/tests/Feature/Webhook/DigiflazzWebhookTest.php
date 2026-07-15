<?php

namespace Tests\Feature\Webhook;

use App\Domains\Financial\Services\WalletLedgerService;
use App\Domains\Identity\Models\User;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class DigiflazzWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-secret';
    private Transaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.digiflazz.webhook_secret' => $this->secret]);

        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 0, 'held_balance' => 50000]);

        $provider = \App\Domains\Product\Models\Provider::create([
            'name' => 'Telkomsel',
            'code' => 'TSEL',
            'is_active' => true,
        ]);

        $category = \App\Domains\Product\Models\Category::create([
            'name' => 'Pulsa',
            'code' => 'PULSA',
            'is_active' => true,
        ]);

        $product = \App\Domains\Product\Models\Product::create([
            'name' => 'Pulsa 10k',
            'code' => 'TSEL10',
            'category_id' => $category->id,
            'provider_id' => $provider->id,
            'price' => '10000.00',
            'is_active' => true,
        ]);

        $this->transaction = Transaction::create([
            'transaction_id' => 'trx-123',
            'user_id' => $user->id,
            'product_id' => $product->id,
            'provider_id' => $provider->id,
            'destination' => '08123456789',
            'amount' => 10000,
            'status' => TransactionStatus::PENDING,
            'idempotency_key' => 'IDEM-123',
        ]);
    }

    private function generateSignature(array $payload): string
    {
        return 'sha1=' . hash_hmac('sha1', json_encode($payload), $this->secret);
    }

    public function test_it_rejects_missing_secret()
    {
        config(['services.digiflazz.webhook_secret' => null]);
        
        $response = $this->postJson('/api/webhooks/digiflazz', []);
        
        $response->assertStatus(500)
                 ->assertJson(['error' => 'Webhook secret not configured']);
    }

    public function test_it_rejects_invalid_signature()
    {
        $payload = ['data' => ['ref_id' => 'trx-123']];
        
        $response = $this->withHeaders([
            'X-Hub-Signature' => 'sha1=invalid_signature',
        ])->postJson('/api/webhooks/digiflazz', $payload);
        
        $response->assertStatus(401)
                 ->assertJson(['error' => 'Invalid signature']);
    }

    public function test_it_handles_ping_event()
    {
        $payload = [
            'sed' => 'AgXXtVAHp',
            'hook_id' => '11aaabbb',
            'hook' => [
                'url' => 'https://example.com/webhook'
            ]
        ];

        $response = $this->withHeaders([
            'X-Hub-Signature' => $this->generateSignature($payload),
        ])->postJson('/api/webhooks/digiflazz', $payload);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'pong']);
    }

    public function test_it_processes_sukses_webhook()
    {
        // Mock ledger service to avoid real wallet logic since we just want to test the webhook
        $this->mock(WalletLedgerService::class, function (MockInterface $mock) {
            $mock->shouldReceive('captureHoldBalance')
                 ->once()
                 ->with($this->transaction->user->wallet->id, '10000', true)
                 ->andReturn(new \App\Domains\Financial\Models\WalletLedger());
        });

        $payload = [
            'data' => [
                'ref_id' => 'trx-123',
                'status' => 'Sukses',
                'message' => 'Transaksi Sukses',
                'sn' => 'SN-12345',
            ]
        ];

        $response = $this->withHeaders([
            'X-Hub-Signature' => $this->generateSignature($payload),
        ])->postJson('/api/webhooks/digiflazz', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'transaction_id' => 'trx-123',
            'status' => TransactionStatus::SUCCESS->value,
            'message' => 'Transaksi Sukses',
            'sn' => 'SN-12345',
        ]);
    }

    public function test_it_processes_gagal_webhook()
    {
        $this->mock(WalletLedgerService::class, function (MockInterface $mock) {
            $mock->shouldReceive('releaseHoldBalance')
                 ->once()
                 ->with($this->transaction->user->wallet->id, '10000', 'Refund for failed transaction trx-123', \Mockery::type(Transaction::class), true)
                 ->andReturn(new \App\Domains\Financial\Models\WalletLedger());
        });

        $payload = [
            'data' => [
                'ref_id' => 'trx-123',
                'status' => 'Gagal',
                'message' => 'Produk Gangguan',
            ]
        ];

        $response = $this->withHeaders([
            'X-Hub-Signature' => $this->generateSignature($payload),
        ])->postJson('/api/webhooks/digiflazz', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('transactions', [
            'transaction_id' => 'trx-123',
            'status' => TransactionStatus::FAILED->value,
            'message' => 'Produk Gangguan',
        ]);
    }

    public function test_it_ignores_pending_webhook()
    {
        $payload = [
            'data' => [
                'ref_id' => 'trx-123',
                'status' => 'Pending',
            ]
        ];

        $response = $this->withHeaders([
            'X-Hub-Signature' => $this->generateSignature($payload),
        ])->postJson('/api/webhooks/digiflazz', $payload);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Status remains pending']);

        $this->assertDatabaseHas('transactions', [
            'transaction_id' => 'trx-123',
            'status' => TransactionStatus::PENDING->value,
        ]);
    }
}
