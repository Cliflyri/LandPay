<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientSmsConsentEvent extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['enabled' => 'boolean']; }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
}
