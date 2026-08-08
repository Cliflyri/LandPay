<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Check = 'check';
    case Ach = 'ach';
    case Card = 'card';
    case MoneyOrder = 'money_order';
    case Other = 'other';
}