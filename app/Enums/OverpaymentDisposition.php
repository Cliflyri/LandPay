<?php

namespace App\Enums;

enum OverpaymentDisposition: string
{
    case Principal = 'principal';
    case NextInvoiceCredit = 'next_invoice_credit';
}