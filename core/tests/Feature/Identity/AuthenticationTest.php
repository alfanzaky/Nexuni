<?php

namespace Tests\Feature\Identity;

use App\Domains\Identity\DTOs\LoginUserData;
use App\Domains\Identity\DTOs\RegisterUserData;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Services\AuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private AuthenticationService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = $this->app->make(AuthenticationService::class);
    }

    public function test_user_can_register()
    {
        $data = new RegisterUserData(
            name: 'John Doe',
            email: 'john@example.com',
            phone: '081234567890',
            password: 'password123'
        );

        $user = $this->authService->register($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('reseller', $user->role);
        $this->assertTrue($user->is_active);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('securepassword'),
        ]);

        $data = new LoginUserData(
            email: 'jane@example.com',
            password: 'securepassword'
        );

        $result = $this->authService->login($data);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($user->id, $result['user']->id);
        $this->assertNotEmpty($result['token']);
    }

    public function test_inactive_user_cannot_login()
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => bcrypt('securepassword'),
            'is_active' => false,
        ]);

        $data = new LoginUserData(
            email: 'inactive@example.com',
            password: 'securepassword'
        );

        $this->expectException(ValidationException::class);

        $this->authService->login($data);
    }

    public function test_user_can_register_and_login()
    {
        $registerData = new RegisterUserData(
            name: 'Alice',
            email: 'alice@example.com',
            phone: '08111222333',
            password: 'alicepassword123'
        );

        $this->authService->register($registerData);

        $loginData = new LoginUserData(
            email: 'alice@example.com',
            password: 'alicepassword123'
        );

        $result = $this->authService->login($loginData);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertEquals('alice@example.com', $result['user']->email);
    }
}
