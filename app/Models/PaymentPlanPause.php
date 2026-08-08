<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPlanPause extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['pause_date' => 'date', 'planned_resume_date' => 'date', 'resume_date' => 'date', 'resumed_at' => 'datetime'];
    }

    public function paymentPlan(): BelongsTo { return $this->belongsTo(PaymentPlan::class); }
    public function pausedBy(): BelongsTo { return $this->belongsTo(User::class, 'paused_by_user_id'); }
    public function resumedBy(): BelongsTo { return $this->belongsTo(User::class, 'resumed_by_user_id'); }
}
