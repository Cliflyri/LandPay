<?php

namespace App\Enums;

enum FinancialEffectComponent: string
{
    case PurchasePricePrincipal = 'purchase_price_principal';
    case DocumentationFeePrincipal = 'documentation_fee_principal';
    case ScheduledPurchasePayment = 'scheduled_purchase_payment';
    case MonthlyServiceFee = 'monthly_service_fee';
    case LateFeeStageOne = 'late_fee_stage_1';
    case LateFeeStageTwo = 'late_fee_stage_2';
    case AdministrativeFee = 'administrative_fee';
    case UnappliedCredit = 'unapplied_credit';
    case Refund = 'refund';
    case WriteOff = 'write_off';
    case Other = 'other';
}