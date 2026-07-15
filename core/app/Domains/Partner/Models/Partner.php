<?php

namespace App\Domains\Partner\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Partner extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'api_key',
        'api_secret',
        'webhook_url',
        'is_active',
        'rate_limit',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'api_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'rate_limit' => 'integer',
        'api_secret' => 'encrypted',
    ];

    /**
     * Get the user that owns the partner account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
