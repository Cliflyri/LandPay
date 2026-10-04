<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Improvement extends Model {
    use Concerns\HasPublicUuid;
    protected $guarded = ['id','uuid'];
    public function getRouteKeyName(): string { return 'uuid'; }
    public function recordedBy() { return $this->belongsTo(User::class,'recorded_by_user_id'); }
    public function paymentPlan() { return $this->belongsTo(PaymentPlan::class)->withTrashed(); }
    public function client() { return $this->belongsTo(Client::class); }
    public function messageThread() { return $this->hasOne(SecureMessageThread::class)->whereNull('improvement_update_id'); }
    public function updates() { return $this->hasMany(ImprovementUpdate::class); }
    public function latestUpdate() { return $this->hasOne(ImprovementUpdate::class)->latestOfMany(); }
    public function scopeForAccount($query, PortalAccount $account) {
        return $query->where('client_id',$account->client_id)->whereIn('payment_plan_id',$account->activePlanIds())
            ->whereHas('paymentPlan',fn($q)=>$q->whereNull('deleted_at'));
    }
}
