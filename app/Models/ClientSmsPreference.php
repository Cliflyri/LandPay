<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientSmsPreference extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['enabled' => 'boolean', 'opted_in_at' => 'datetime', 'opted_out_at' => 'datetime', 'stopped_at' => 'datetime']; }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
}
