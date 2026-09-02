<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingDefault;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BillingDefaultSettingsController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scheduled_payment_amount' => ['nullable', 'decimal:0,2', 'min:0'],
            'monthly_service_fee' => ['required', 'decimal:0,2', 'min:0'],
            'due_days_after_issue' => ['required', 'integer', 'between:0,60'],
            'grace_days' => ['required', 'integer', 'between:0,60'],
            'stage_one_fee_type' => ['required', Rule::in(['fixed', 'percentage'])],
            'stage_one_fee_value' => ['required', 'numeric', 'min:0'],
            'stage_one_minimum_amount' => ['nullable', 'required_if:stage_one_fee_type,percentage', 'decimal:0,2'],
            'stage_two_enabled' => ['nullable', 'boolean'],
            'stage_two_days_late' => ['nullable', 'required_if:stage_two_enabled,1', 'integer', 'between:1,365'],
            'stage_two_fee_type' => ['nullable', 'required_if:stage_two_enabled,1', Rule::in(['fixed', 'percentage'])],
            'stage_two_fee_value' => ['nullable', 'required_if:stage_two_enabled,1', 'numeric', 'min:0'],
            'stage_two_minimum_amount' => ['nullable', 'required_if:stage_two_fee_type,percentage', 'decimal:0,2'],
            'default_eligibility_days' => ['required', 'integer', 'between:1,730'],
        ]);

        $stageOneDaysLate = (int) $data['grace_days'] + 1;
        if ($request->boolean('stage_two_enabled') && (int) $data['stage_two_days_late'] <= $stageOneDaysLate) {
            throw ValidationException::withMessages(['stage_two_days_late' => 'Stage two must occur after the stage-one late fee.']);
        }

        $defaults = BillingDefault::query()->latest('id')->first() ?? new BillingDefault;
        $stageTwo = $request->boolean('stage_two_enabled');
        $defaults->fill([
            'frequency' => 'monthly', 'invoice_day' => $defaults->invoice_day ?: 3,
            'scheduled_payment_amount' => filled($data['scheduled_payment_amount'] ?? null) ? Money::toCents($data['scheduled_payment_amount']) : 0,
            'monthly_service_fee' => Money::toCents($data['monthly_service_fee']),
            'due_days_after_issue' => $data['due_days_after_issue'], 'grace_days' => $data['grace_days'],
            'stage_one_enabled' => true, 'stage_one_fee_type' => $data['stage_one_fee_type'],
            'stage_one_fixed_amount' => $data['stage_one_fee_type'] === 'fixed' ? Money::toCents((string) $data['stage_one_fee_value']) : null,
            'stage_one_percentage_rate' => $data['stage_one_fee_type'] === 'percentage' ? $data['stage_one_fee_value'] : null,
            'stage_one_minimum_amount' => $data['stage_one_fee_type'] === 'percentage' ? Money::toCents($data['stage_one_minimum_amount']) : 0,
            'stage_one_days_late' => $stageOneDaysLate, 'stage_two_enabled' => $stageTwo,
            'stage_two_fee_type' => $stageTwo ? $data['stage_two_fee_type'] : null,
            'stage_two_fixed_amount' => $stageTwo && $data['stage_two_fee_type'] === 'fixed' ? Money::toCents((string) $data['stage_two_fee_value']) : null,
            'stage_two_percentage_rate' => $stageTwo && $data['stage_two_fee_type'] === 'percentage' ? $data['stage_two_fee_value'] : null,
            'stage_two_minimum_amount' => $stageTwo && $data['stage_two_fee_type'] === 'percentage' ? Money::toCents($data['stage_two_minimum_amount']) : 0,
            'stage_two_days_late' => $stageTwo ? $data['stage_two_days_late'] : null,
            'default_eligibility_days' => $data['default_eligibility_days'], 'updated_by_user_id' => $request->user()->id,
        ])->save();

        return redirect()->route('admin.settings.index', ['section' => 'billing'])->with('success', 'Billing defaults saved for future plans.');
    }
}
