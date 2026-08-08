<?php

namespace App\Enums;

enum FinancialTransactionType: string
{
    case OpeningPurchaseBalance = 'opening_purchase_balance';
    case InvoiceCharge = 'invoice_charge';
    case RecurringFee = 'recurring_fee';
    case Payment = 'payment';
    case CreditApplication = 'credit_application';
    case Adjustment = 'adjustment';
    case Reversal = 'reversal';
    case Refund = 'refund';
    case WriteOff = 'write_off';
}