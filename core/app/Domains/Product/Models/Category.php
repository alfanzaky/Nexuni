<?php

namespace App\Domains\Product\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'is_active',
    ];
}
