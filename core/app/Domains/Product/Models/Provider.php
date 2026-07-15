<?php

namespace App\Domains\Product\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];
}
