<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceReminder extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'failed_at' => 'datetime', 'trigger_date' => 'date', 'automated' => 'boolean'];
    }

    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function paymentPlan(): BelongsTo { return $this->belongsTo(PaymentPlan::class); }
    public function recipientClient(): BelongsTo { return $this->belongsTo(Client::class, 'recipient_client_id'); }
    public function sentBy(): BelongsTo { return $this->belongsTo(User::class, 'sent_by_user_id'); }
}
