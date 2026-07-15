<?php

namespace App\Domains\Financial\Enums;

enum WalletStatus: string
{
    case ACTIVE = 'active';
    case LOCKED = 'locked';
}
