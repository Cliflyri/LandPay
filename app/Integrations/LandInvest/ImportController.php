<?php

namespace App\Integrations\LandInvest;

use App\Models\PaymentPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ImportController
{
    public function show(RepaymentSnapshot $snapshot)
    {
        return response()->view('admin.actions.landinvest-import', ['snapshot' => $snapshot->current()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, RepaymentSnapshot $snapshot)
    {
        $validated = $request->validate(['snapshot' => ['required', 'string', 'max:2097152']]);
        try {
            $data = $snapshot->import($validated['snapshot']);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['snapshot' => $e->getMessage()]);
        }
        $known = PaymentPlan::withTrashed()->pluck('plan_number')->map(fn ($number) => RepaymentSnapshot::normalize($number))->flip();
        $unmatched = array_values(array_map(fn ($property) => $property['plan_number'], array_filter(
            $data['properties'], fn ($property) => ! $known->has(RepaymentSnapshot::normalize($property['plan_number']))
        )));
        $counts = array_count_values(array_column($data['properties'], 'status'));
        $summary = 'Imported '.count($data['properties']).' property statuses: '
            .($counts['paid'] ?? 0).' paid, '.($counts['unpaid'] ?? 0).' unpaid, '
            .($counts['partial'] ?? 0).' partial, '.($counts['not_funded'] ?? 0).' not funded. '
            .(count($data['properties']) - count($unmatched)).' matched LandPay properties.';
        return redirect()->route('admin.landinvest.import')->with('import_summary', $summary)->with('import_unmatched', $unmatched);
    }
}
