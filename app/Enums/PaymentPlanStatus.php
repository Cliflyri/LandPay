<?php

namespace App\Enums;

enum PaymentPlanStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Paused = 'paused';
    case PaidOff = 'paid_off';
    case DefaultEligible = 'default_eligible';
    case Terminated = 'terminated';
    case Closed = 'closed';
}