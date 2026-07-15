<?php

namespace Tests\Feature\Identity\Schema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UsersTableSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_has_expected_columns()
    {
        $this->assertTrue(
            Schema::hasTable('users'),
            'Table users does not exist.'
        );

        $this->assertTrue(
            Schema::hasColumns('users', [
                'id',
                'name',
                'email',
                'phone',
                'role',
                'is_active',
                'email_verified_at',
                'password',
                'remember_token',
                'created_at',
                'updated_at',
            ]),
            'Table users does not have the expected columns.'
        );
    }
}
