<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClosingDocument extends Model
{
    protected $guarded = ['id'];

    public function closing()
    {
        return $this->belongsTo(PlanClosing::class, 'plan_closing_id');
    }
}
