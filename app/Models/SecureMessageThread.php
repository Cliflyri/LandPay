<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SecureMessageThread extends Model
{
    use HasPublicUuid;


    protected static function booted(): void
    {
        // Main cannot enforce improvement-specific access/visibility rules.
        // Global scope also protects implicit route binding for every action.
        static::addGlobalScope('main_supported_threads', function (Builder $query): void {
            if ($query->getModel()->getConnection()->getSchemaBuilder()->hasColumn('secure_message_threads', 'improvement_id')) {
                $query->whereNull('secure_message_threads.improvement_id');
            }
        });
    }

    protected $guarded = ['id', 'uuid'];

    protected function casts(): array
    {
        return [
            'starred_at' => 'datetime',
            'latest_message_at' => 'datetime',
            'notification_last_sent_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string { return 'uuid'; }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function paymentPlan(): BelongsTo { return $this->belongsTo(PaymentPlan::class); }
    public function messages(): HasMany { return $this->hasMany(SecureMessage::class)->oldest(); }

    public function scopeUnreadByAdmin(Builder $query): Builder
    {
        return $query->whereHas('messages', fn (Builder $messages) =>
            $messages->where('sender_type', 'client')->whereNull('admin_viewed_at')
        );
    }

    public function scopeUnreadByClient(Builder $query): Builder
    {
        return $query->whereHas('messages', fn (Builder $messages) =>
            $messages->where('sender_type', 'admin')->whereNull('client_viewed_at')
        );
    }
}
