<?php

namespace App\Enums;

enum InvoiceItemType: string
{
    case ScheduledPurchasePayment = 'scheduled_purchase_payment';
    case DocumentationFee = 'documentation_fee';
    case MonthlyServiceFee = 'monthly_service_fee';
    case LateFeeStageOne = 'late_fee_stage_1';
    case LateFeeStageTwo = 'late_fee_stage_2';
    case AdministrativeFee = 'administrative_fee';
    case Other = 'other';
}