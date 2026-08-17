<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'updated_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string { return 'uuid'; }
    public function thread(): BelongsTo { return $this->belongsTo(SecureMessageThread::class, 'secure_message_thread_id'); }
    public function senderUser(): BelongsTo { return $this->belongsTo(User::class, 'sender_user_id'); }
    public function senderClient(): BelongsTo { return $this->belongsTo(Client::class, 'sender_client_id'); }
    public function documents(): BelongsToMany { return $this->belongsToMany(SharedDocument::class, 'secure_message_documents')->withTimestamps(); }
    public function attachments(): HasMany { return $this->hasMany(SecureMessageAttachment::class); }
    public function revisions(): HasMany { return $this->hasMany(SecureMessageRevision::class)->oldest(); }
}
