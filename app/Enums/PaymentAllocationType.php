<?php

namespace App\Enums;

enum PaymentAllocationType: string
{
    case InvoiceItem = 'invoice_item';
    case ServiceFee = 'service_fee';
    case ProcessingFee = 'processing_fee';
    case PurchaseBalance = 'purchase_balance';
    case ClientCredit = 'client_credit';
}