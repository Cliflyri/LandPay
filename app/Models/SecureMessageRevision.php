<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecureMessageRevision extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = ['id'];
    public function message(): BelongsTo { return $this->belongsTo(SecureMessage::class, 'secure_message_id'); }
    public function editor(): BelongsTo { return $this->belongsTo(User::class, 'edited_by_user_id'); }
}
