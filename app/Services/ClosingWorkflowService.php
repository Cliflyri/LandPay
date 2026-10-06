<?php

namespace App\Services;

use App\Models\PaymentPlan;
use App\Models\PlanClosing;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClosingWorkflowService
{
    public function __construct(private readonly FinancialBalanceService $balances) {}

    public function eligible(PaymentPlan $plan): bool
    {
        return in_array($plan->status, ['active', 'paused'], true) && $this->balances->contractBalance($plan) <= 0;
    }

    public function closing(PaymentPlan $plan): PlanClosing
    {
        return PlanClosing::firstOrCreate(['payment_plan_id' => $plan->id]);
    }

    // Reuse the ledger's existing balances; never sum payments or extra invoices into contract balance.
    public function balances(PaymentPlan $plan): array
    {
        $invoices = $plan->invoices()->get()->map(fn ($invoice) => [
            'invoice' => $invoice, 'balance' => max(0, $this->balances->invoiceBalance($invoice)),
        ])->filter(fn ($row) => $row['balance'] > 0)->values();

        return ['contract' => max(0, $this->balances->contractBalance($plan)), 'invoices' => $invoices,
            'outstanding' => (int) $invoices->sum('balance')];
    }

    public function notices(int $userId)
    {
        PaymentPlan::whereIn('status', ['active', 'paused'])->with('closing')->chunkById(100, function ($plans) {
            foreach ($plans as $plan) {
                if (! $plan->closing?->eligible_at && $this->eligible($plan)) {
                    $closing = $this->closing($plan);
                    $closing->update(['eligible_at' => now()]);
                }
            }
        });

        return PlanClosing::with('paymentPlan')->whereNotNull('eligible_at')->where('status', '!=', 'completed')
            ->whereHas('paymentPlan')->whereNotIn('id', DB::table('closing_notice_dismissals')->where('user_id', $userId)->select('plan_closing_id'))
            ->orderBy('eligible_at')->get();
    }

    public function log(PlanClosing $closing, string $action, array $context = [], ?int $userId = null, ?int $clientId = null): void
    {
        $closing->events()->create(['action' => $action, 'context' => $context, 'user_id' => $userId, 'client_id' => $clientId]);
    }

    public function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['closing' => $message]);
        }
    }

    public function checkBalances(PaymentPlan $plan, array $data): array
    {
        $balances = $this->balances($plan);
        if ($balances['contract'] > 0 || $balances['outstanding'] > 0) {
            $this->require(! empty($data['acknowledge_balance']) && filled($data['balance_reason'] ?? null),
                'Outstanding balances remain. Review the contract and invoices, acknowledge them, and enter a reason to proceed.');
        }

        return ['contract_balance' => $balances['contract'], 'invoice_balance' => $balances['outstanding'],
            'acknowledged' => ! empty($data['acknowledge_balance']), 'reason' => $data['balance_reason'] ?? null];
    }

    public function reopenPaperwork(PlanClosing $closing): void
    {
        if ($closing->released_at) {
            $details = $closing->details ?? [];
            $details['packet_previously_released'] = true;
            $closing->details = $details;
        }
        $closing->fill(['released_at' => null, 'forms_status' => 'needed', 'paperwork_accepted_at' => null,
            'ready_on' => null, 'submitted_on' => null, 'recorded_on' => null, 'recording_reference' => null]);
    }

    public function reopenStep(PlanClosing $closing, string $section, ?string $instruction = null): void
    {
        $step = ['paperwork' => '1-2', 'details' => '1', 'extras' => '2', 'forms' => '3'][$section];
        $details = $closing->details ?? [];
        $notes = $details['reopen_steps'] ?? [];
        if ($section === 'forms') {
            $closing->fill(['forms_status' => 'needed', 'paperwork_accepted_at' => null,
                'ready_on' => null, 'submitted_on' => null, 'recorded_on' => null, 'recording_reference' => null]);
            $default = 'Please review the signing instructions and complete step 3 again. Contact us with any questions.';
        } else {
            $this->reopenPaperwork($closing);
            $details['packet_previously_released'] = ! empty($closing->details['packet_previously_released']);
            $closing->details_status = 'needed';
            $closing->extras_status = 'needed';
            unset($notes[1], $notes[2], $notes['1-2']);
            $details['confirmed'] = false;
            $details['combined_submission'] = false;
            unset($notes[3]);
            $default = 'Please review and correct section '.$step.', then submit sections 1 and 2 together again.';
        }
        $notes[$step] = filled($instruction) ? $instruction : $default;
        $details['reopen_steps'] = $notes;
        $closing->details = $details;
        if ($closing->status === 'completed') {
            $closing->status = 'active';
        }
    }

    public function undoProgress(PlanClosing $closing, string $milestone, ?string $instruction = null): void
    {
        $fields = ['paperwork' => 'paperwork_accepted_at', 'ready' => 'ready_on', 'submitted' => 'submitted_on', 'recorded' => 'recorded_on'];
        $this->require((bool) $closing->{$fields[$milestone]}, 'That milestone is not complete.');
        if ($milestone === 'paperwork') {
            $this->reopenStep($closing, 'forms', $instruction);

            return;
        }
        $clear = false;
        foreach ($fields as $key => $field) {
            if ($key === $milestone) {
                $clear = true;
            }
            if ($clear) {
                $closing->{$field} = null;
            }
        }
        $closing->recording_reference = null;
        if ($closing->status === 'completed') {
            $closing->status = 'active';
        }
    }

    public function prefill(PaymentPlan $plan): array
    {
        $client = $plan->memberships()->where('role', 'primary')->whereNull('effective_to')
            ->whereDate('effective_from', '<=', today())->first()?->client;

        return ['owners' => [[
            'name' => $client ? ($client->organization_name ?: trim(implode(' ', array_filter([$client->first_name, $client->middle_name, $client->last_name])))) : '',
            'address' => $client ? implode("\n", array_filter([$client->address_line_1, $client->address_line_2,
                trim(implode(' ', array_filter([$client->city, $client->state_region, $client->postal_code]))), $client->country_code])) : '',
            'mailing_address' => '', 'married' => '',
        ]], 'titling' => '', 'beneficiary' => ''];
    }
}
