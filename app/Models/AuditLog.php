<?php

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasPublicUuid;

    public $timestamps = false;

    protected $guarded = ['id', 'uuid'];

    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
            'created_at' => 'datetime',
        ];
    }
}