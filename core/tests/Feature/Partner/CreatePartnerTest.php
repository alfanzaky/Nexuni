<?php

namespace Tests\Feature\Partner;

use App\Domains\Partner\Actions\CreatePartner;
use App\Domains\Partner\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatePartnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_partner()
    {
        $action = new CreatePartner;

        $data = [
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'phone' => '081234567890',
            'password' => 'secret',
            'webhook_url' => 'https://example.com/webhook',
        ];

        $partner = $action->execute($data);

        $this->assertInstanceOf(Partner::class, $partner);
        $this->assertEquals('Test Partner', $partner->name);
        $this->assertNotNull($partner->api_key);
        $this->assertNotNull($partner->api_secret);

        // Assert Wallet created for user
        $this->assertTrue($partner->user->wallet()->exists());
        $this->assertEquals(0, $partner->user->wallet->balance);

        $this->assertDatabaseHas('partners', [
            'name' => 'Test Partner',
            'webhook_url' => 'https://example.com/webhook',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'partner@test.com',
        ]);
    }
}
