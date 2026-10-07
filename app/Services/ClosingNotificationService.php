<?php

namespace App\Services;

use App\Mail\ClosingNotificationMail;
use App\Models\PlanClosing;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ClosingNotificationService
{
    public function send(PlanClosing $closing, ?User $actor = null): array
    {
        $results = [];
        $nextStep = $closing->released_at && $closing->forms_status === 'needed'
            ? 'Your signing packet is ready. Please review and complete step 2.'
            : 'Closing information is available. Please review your next steps.';
        $clients = $closing->paymentPlan->memberships()->whereNull('effective_to')->whereDate('effective_from', '<=', today())
            ->with('client')->get()->pluck('client')->unique('id');
        foreach ($clients as $client) {
            $result = ['client_id' => $client->id, 'email' => 'unavailable', 'sms' => 'not eligible'];
            if (filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($client->email)->send(new ClosingNotificationMail($closing->paymentPlan->plan_number, $nextStep));
                    $result['email'] = 'sent';
                } catch (Throwable $e) {
                    report($e);
                    $result['email'] = 'failed';
                }
            }
            try {
                if (app(InvoiceSmsService::class)->eligible($client)) {
                    app(SmsDeliveryService::class)->send($client->smsPreference->sms_phone_e164,
                        'LandPay: Plan '.$closing->paymentPlan->plan_number.'. '.$nextStep.' Log in: '.route('portal.login'),
                        'closing', 'closing:'.Str::uuid(), $actor, $client);
                    $result['sms'] = 'sent';
                }
            } catch (Throwable $e) {
                report($e);
                $result['sms'] = 'failed';
            }
            $results[] = $result;
        }
        app(ClosingWorkflowService::class)->log($closing, 'Client notification', $results, $actor?->id);

        return $results;
    }
}
