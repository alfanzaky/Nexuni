<?php

namespace App\Domains\Transaction\Enums;

enum TransactionStatus: string
{
    case CREATED = 'CREATED';
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
}
