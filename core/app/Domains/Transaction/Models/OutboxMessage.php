<?php

namespace App\Domains\Transaction\Models;

use Illuminate\Database\Eloquent\Model;

class OutboxMessage extends Model
{
    protected $fillable = [
        'event_type',
        'payload',
        'status',
        'failed_attempts',
        'published_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'published_at' => 'datetime',
    ];
}
