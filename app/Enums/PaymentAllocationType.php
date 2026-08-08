<?php

namespace App\Enums;

enum PaymentAllocationType: string
{
    case InvoiceItem = 'invoice_item';
    case PurchaseBalance = 'purchase_balance';
    case ClientCredit = 'client_credit';
}