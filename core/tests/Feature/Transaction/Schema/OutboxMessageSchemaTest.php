<?php

namespace Tests\Feature\Transaction\Schema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OutboxMessageSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_outbox_messages_table_has_correct_schema(): void
    {
        $this->assertTrue(Schema::hasTable('outbox_messages'));

        $columns = [
            'id',
            'event_type',
            'payload',
            'status',
            'failed_attempts',
            'published_at',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn('outbox_messages', $column),
                "Column [{$column}] not found in outbox_messages table."
            );
        }
    }
}
