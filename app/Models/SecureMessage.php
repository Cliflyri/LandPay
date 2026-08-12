<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecureMessage extends Model
{
    use HasPublicUuid;

    protected $guarded = ['id', 'uuid'];

    protected function casts(): array
    {
        return [
            'client_viewed_at' => 'datetime',
            'admin_viewed_at' => 'datetime',
            'attachment_downloaded_at' => 'datetime',
            'attachment_size' => 'integer',
        ];
    }

    public function getRouteKeyName(): string { return 'uuid'; }
    public function thread(): BelongsTo { return $this->belongsTo(SecureMessageThread::class, 'secure_message_thread_id'); }
    public function senderUser(): BelongsTo { return $this->belongsTo(User::class, 'sender_user_id'); }
    public function senderClient(): BelongsTo { return $this->belongsTo(Client::class, 'sender_client_id'); }
}
