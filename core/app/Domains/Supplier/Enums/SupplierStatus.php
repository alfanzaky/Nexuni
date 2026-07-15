<?php

namespace App\Domains\Supplier\Enums;

enum SupplierStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
}
