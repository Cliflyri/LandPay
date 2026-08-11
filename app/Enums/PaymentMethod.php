<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Check = 'check';
    case Ach = 'ach';
    case Card = 'card';
    case MoneyOrder = 'money_order';
    case Zelle = 'zelle';
    case CashApp = 'cash_app';
    case Venmo = 'venmo';
    case Chime = 'chime';
    case Melio = 'melio';
    case Other = 'other';
}