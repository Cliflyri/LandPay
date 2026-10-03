<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ImprovementUpdate extends Model {
    protected $guarded = ['id'];
    protected function casts(): array { return ['photos'=>'array','received_at'=>'datetime']; }
    public function messageThread() { return $this->hasOne(SecureMessageThread::class); }
    public function improvement() { return $this->belongsTo(Improvement::class); }
}
