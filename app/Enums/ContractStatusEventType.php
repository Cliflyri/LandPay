<?php

namespace App\Enums;

enum ContractStatusEventType: string
{
    case DefaultEligible = 'default_eligible';
    case TerminationConfirmed = 'termination_confirmed';
    case Reinstated = 'reinstated';
}