<?php

namespace App\Domains\Pricing\Models;

use App\Domains\Product\Models\Category;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\Provider;
use App\Domains\Reseller\Models\ResellerGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Margin extends Model
{
    protected $fillable = [
        'reseller_group_id',
        'category_id',
        'provider_id',
        'product_id',
        'amount',
        'percentage',
        'is_active',
    ];

    public function resellerGroup(): BelongsTo
    {
        return $this->belongsTo(ResellerGroup::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
