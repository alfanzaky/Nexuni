<?php

namespace App\Domains\Supplier\Models;

use App\Domains\Supplier\Enums\SupplierStatus;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'code',
        'status',
    ];

    protected $casts = [
        'status' => SupplierStatus::class,
    ];
}
