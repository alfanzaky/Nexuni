<?php

namespace Tests\Feature\Reseller;

use App\Domains\Identity\Models\User;
use App\Domains\Reseller\DTOs\AssignResellerData;
use App\Domains\Reseller\DTOs\CreateResellerGroupData;
use App\Domains\Reseller\Services\ResellerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerTest extends TestCase
{
    use RefreshDatabase;

    private ResellerService $resellerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resellerService = $this->app->make(ResellerService::class);
    }

    public function test_can_create_reseller_group()
    {
        $data = new CreateResellerGroupData(
            name: 'VIP',
            level: 1,
            description: 'VIP Reseller Group'
        );

        $group = $this->resellerService->createGroup($data);

        $this->assertEquals('VIP', $group->name);
        $this->assertEquals(1, $group->level);

        $this->assertDatabaseHas('reseller_groups', [
            'name' => 'VIP',
            'level' => 1,
        ]);
    }

    public function test_can_assign_user_to_reseller_group()
    {
        $user = User::factory()->create();
        $group = $this->resellerService->createGroup(new CreateResellerGroupData(
            name: 'Reguler',
            level: 3
        ));

        $data = new AssignResellerData(
            userId: $user->id,
            groupId: $group->id,
            status: 'active'
        );

        $reseller = $this->resellerService->assignToGroup($data);

        $this->assertEquals($user->id, $reseller->user_id);
        $this->assertEquals($group->id, $reseller->group_id);
        $this->assertEquals('active', $reseller->status);

        $this->assertDatabaseHas('resellers', [
            'user_id' => $user->id,
            'group_id' => $group->id,
            'status' => 'active',
        ]);
    }
}
