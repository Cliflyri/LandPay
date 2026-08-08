<?php

namespace App\Financial;

use App\Enums\FinancialEffectComponent;
use App\Enums\FinancialEffectType;

readonly class PostingEffect
{
    public function __construct(
        public FinancialEffectType $type,
        public int $amountDelta,
        public FinancialEffectComponent $component,
        public ?int $invoiceId = null,
        public ?int $invoiceItemId = null,
        public ?int $feeAssessmentId = null,
        public ?string $description = null,
    ) {}
}
