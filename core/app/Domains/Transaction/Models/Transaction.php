<?php

namespace App\Domains\Transaction\Models;

use App\Domains\Identity\Models\User;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\Provider;
use App\Domains\Supplier\Models\Supplier;
use App\Domains\Transaction\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'user_id',
        'product_id',
        'provider_id',
        'destination',
        'amount',
        'status',
        'supplier_id',
        'idempotency_key',
        'sn',
    ];

    protected $casts = [
        'status' => TransactionStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
