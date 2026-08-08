<?php

namespace App\Enums;

enum FinancialEffectType: string
{
    case InvoiceDue = 'invoice_due';
    case ClientCredit = 'client_credit';
    case PurchaseBalance = 'purchase_balance';
}