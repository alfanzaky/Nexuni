<?php

namespace App\Domains\Reseller\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerGroup extends Model
{
    protected $fillable = [
        'name',
        'level',
        'description',
    ];
}
