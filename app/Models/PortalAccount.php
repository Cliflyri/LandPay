<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class PortalAccount extends Authenticatable
{
    use Notifiable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'enabled' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function displayName(): string
    {
        return $this->client->organization_name
            ?: trim(($this->client->preferred_name ?: $this->client->first_name).' '.$this->client->last_name);
    }

    public function activePlanIds(): array
    {
        return $this->client->memberships()
            ->whereNull('effective_to')
            ->whereDate('effective_from', '<=', today())
            ->pluck('payment_plan_id')->map(fn ($id) => (int) $id)->all();
    }
}
