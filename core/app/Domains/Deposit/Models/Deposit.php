<?php

namespace App\Domains\Deposit\Models;

use App\Domains\Deposit\Enums\DepositStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'status',
        'payment_method',
        'approved_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => DepositStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
