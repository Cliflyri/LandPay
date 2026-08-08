<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanPause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentPlanPauseController extends Controller
{
    public function pause(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $data = $request->validate(['pause_date' => ['required', 'date', 'before_or_equal:today'], 'planned_resume_date' => ['nullable', 'date', 'after:pause_date'], 'reason' => ['required', 'string', 'max:500']]);
        DB::transaction(function () use ($request, $plan, $data): void {
            $locked = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            if ($locked->status !== 'active') throw ValidationException::withMessages(['payment_plan' => 'Only an active plan can be paused.']);
            $pause = PaymentPlanPause::query()->create(['payment_plan_id' => $locked->id, 'pause_date' => $data['pause_date'], 'planned_resume_date' => $data['planned_resume_date'] ?? null, 'reason' => $data['reason'], 'paused_by_user_id' => $request->user()->id]);
            $locked->update(['status' => 'paused', 'updated_by_user_id' => $request->user()->id]);
            $this->audit($request, $locked, 'payment_plan.paused', ['pause' => $pause->getAttributes()]);
        }, 3);
        return back()->with('success', 'Plan paused. Scheduled invoices and automated reminders are suspended; payments remain available.');
    }

    public function resume(Request $request, PaymentPlan $plan): RedirectResponse
    {
        $data = $request->validate(['resume_date' => ['required', 'date', 'before_or_equal:today'], 'resume_note' => ['nullable', 'string', 'max:500']]);
        DB::transaction(function () use ($request, $plan, $data): void {
            $locked = PaymentPlan::query()->lockForUpdate()->findOrFail($plan->id);
            $pause = PaymentPlanPause::query()->where('payment_plan_id', $locked->id)->whereNull('resume_date')->lockForUpdate()->latest('id')->first();
            if ($locked->status !== 'paused' || $pause === null) throw ValidationException::withMessages(['payment_plan' => 'This plan is not currently paused.']);
            if (now()->parse($data['resume_date'])->lt($pause->pause_date)) throw ValidationException::withMessages(['resume_date' => 'Resume date cannot precede the pause date.']);
            $pause->update(['resume_date' => $data['resume_date'], 'resume_note' => $data['resume_note'] ?? null, 'resumed_by_user_id' => $request->user()->id, 'resumed_at' => now()]);
            $locked->update(['status' => 'active', 'updated_by_user_id' => $request->user()->id]);
            $this->audit($request, $locked, 'payment_plan.resumed', ['pause' => $pause->fresh()->getAttributes()]);
        }, 3);
        return back()->with('success', 'Plan resumed. Paused billing periods were skipped and will not be back-billed.');
    }

    private function audit(Request $request, PaymentPlan $plan, string $event, array $after): void
    {
        AuditLog::query()->create(['actor_type' => 'administrator', 'actor_user_id' => $request->user()->id, 'event' => $event, 'auditable_type' => PaymentPlan::class, 'auditable_id' => $plan->id, 'before_values' => null, 'after_values' => $after, 'ip_address' => $request->ip(), 'user_agent' => str($request->userAgent())->limit(500)]);
    }
}
