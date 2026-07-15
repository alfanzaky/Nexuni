<?php

namespace App\Domains\Deposit\Enums;

enum DepositStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
