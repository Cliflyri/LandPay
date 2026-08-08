<?php

namespace App\Enums;

enum LateFeeType: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}