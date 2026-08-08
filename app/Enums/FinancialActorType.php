<?php

namespace App\Enums;

enum FinancialActorType: string
{
    case Administrator = 'administrator';
    case Client = 'client';
    case System = 'system';
}