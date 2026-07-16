<?php

namespace Tests\Feature\H2H;

use App\Domains\Identity\Models\User;
use App\Domains\Partner\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class H2HAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/test-h2h', function () {
            return response()->json(['message' => 'success']);
        })->middleware('h2h_auth');
    }

    public function test_rejects_without_headers()
    {
        $response = $this->postJson('/test-h2h');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Missing required security headers']);
    }

    public function test_rejects_invalid_timestamp()
    {
        $response = $this->withHeaders([
            'X-API-Key' => 'key',
            'X-Signature' => 'sig',
            'X-Timestamp' => now()->subMinutes(10)->toIso8601String(),
            'X-Nonce' => 'nonce',
        ])->postJson('/test-h2h');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Request expired or invalid timestamp']);
    }

    public function test_accepts_valid_request()
    {
        $user = User::factory()->create();
        $partner = Partner::create([
            'user_id' => $user->id,
            'name' => 'Test',
            'api_key' => 'test_key',
            'api_secret' => 'test_secret',
            'is_active' => true,
        ]);

        $payload = [];
        $timestamp = now()->toIso8601String();
        $nonce = 'unique_nonce_123';

        // HMAC-SHA256(payload + timestamp + nonce, api_secret)
        $stringToSign = json_encode($payload).$timestamp.$nonce;
        $signature = hash_hmac('sha256', $stringToSign, 'test_secret');

        $response = $this->withHeaders([
            'X-API-Key' => 'test_key',
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'Content-Type' => 'application/json',
        ])->postJson('/test-h2h', $payload);

        $response->assertStatus(200)
            ->assertJson(['message' => 'success']);
    }

    public function test_rejects_replay_attack()
    {
        $user = User::factory()->create();
        $partner = Partner::create([
            'user_id' => $user->id,
            'name' => 'Test',
            'api_key' => 'test_key',
            'api_secret' => 'test_secret',
            'is_active' => true,
        ]);

        $payload = [];
        $timestamp = now()->toIso8601String();
        $nonce = 'replay_nonce_123';

        $stringToSign = json_encode($payload).$timestamp.$nonce;
        $signature = hash_hmac('sha256', $stringToSign, 'test_secret');

        // First request should succeed
        $response1 = $this->withHeaders([
            'X-API-Key' => 'test_key',
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'Content-Type' => 'application/json',
        ])->postJson('/test-h2h', $payload);

        $response1->assertStatus(200);

        // Second request with same nonce should fail
        $response2 = $this->withHeaders([
            'X-API-Key' => 'test_key',
            'X-Signature' => $signature,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'Content-Type' => 'application/json',
        ])->postJson('/test-h2h', $payload);

        $response2->assertStatus(401)
            ->assertJson(['message' => 'Replay attack detected']);
    }
}
