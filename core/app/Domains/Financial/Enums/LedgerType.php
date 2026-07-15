<?php

namespace App\Domains\Financial\Enums;

enum LedgerType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
}
