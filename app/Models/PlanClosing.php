<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanClosing extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'details' => 'array', 'signing_instructions' => 'array', 'show_hold_notice' => 'boolean',
            'eligible_at' => 'datetime', 'released_at' => 'datetime', 'paperwork_accepted_at' => 'datetime',
            'ready_on' => 'date', 'submitted_on' => 'date', 'recorded_on' => 'date',
        ];
    }

    public function paymentPlan()
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    public function documents()
    {
        return $this->hasMany(ClosingDocument::class);
    }

    public function events()
    {
        return $this->hasMany(ClosingEvent::class)->latest('id');
    }

    public function threads()
    {
        return $this->hasMany(SecureMessageThread::class);
    }

    public function label(): string
    {
        return match ($this->status) {
            'active' => 'Closing in progress', 'hold' => 'Closing on hold',
            'completed' => 'Closed', default => 'Ready to close',
        };
    }

    public function visible(): bool
    {
        return in_array($this->status, ['active', 'completed'], true);
    }

    public function paperworkDisplayStatus(): string
    {
        if ($this->paperworkReviewed()) return 'complete';
        foreach (['needed', 'draft', 'submitted'] as $status) {
            if (in_array($status, [$this->details_status, $this->extras_status], true)) return $status;
        }
        return 'needed';
    }

    public static function currentStepWording(string $text): string
    {
        return strtr($text, [
            'complete step 3 again' => 'complete step 2 again',
            'review step 3.' => 'review step 2.',
            'sections 1 and 2 together again' => 'your paperwork details and requests again',
            'sections 1 and 2' => 'step 1',
            'correct section 2,' => 'correct your paperwork requests,',
            'correct section 1,' => 'correct your paperwork details,',
        ]);
    }

    public function paperworkReviewed(): bool
    {
        return $this->details_status === 'complete' && in_array($this->extras_status, ['complete', 'not_required'], true);
    }

    public function packetPreviouslyReleased(): bool
    {
        return (bool) $this->released_at || ! empty($this->details['packet_previously_released'])
            || $this->events()->where('action', 'release')->exists();
    }

    public function compactProgress(): bool
    {
        return $this->visible() && (bool) $this->paperwork_accepted_at;
    }

    public function nextAction(bool $admin = false): ?string
    {
        if (in_array($this->status, ['hold', 'completed'], true)) {
            return null;
        }
        if ($this->status === 'review') {
            return $admin ? 'Review the closing information, then start closing when ready.' : null;
        }
        $reopened = $this->details['reopen_steps'] ?? [];
        if ($reopened) {
            return ($admin ? 'Awaiting client action. ' : '').collect($reopened)->map(
                fn ($note, $step) => 'Step '.((string) $step === '3' ? '2' : '1').' reopened: '.self::currentStepWording($note)
            )->implode(' ');
        }
        $reviews = [];
        if ($this->details_status === 'submitted') {
            $reviews[] = '1';
        }
        if ($this->extras_status === 'submitted') {
            $reviews[] = '2';
        }
        if ($reviews) {
            return $admin
                ? 'Client submitted step 1. Please review below.'
                : 'Your paperwork details and requests have been submitted. Please wait for admin review.';
        }
        if ($this->details_status !== 'complete' || ! in_array($this->extras_status, ['complete', 'not_required'], true)) {
            if (! $admin && ($this->details_status === 'draft' || $this->extras_status === 'draft')) {
                return null;
            }

            return $admin ? 'Client information incomplete — awaiting combined submission of step 1.'
                : 'Please complete your paperwork details and requests, then submit below.';
        }
        if ($this->paperwork_accepted_at) {
            if ($admin) {
                return $this->submitted_on
                    ? 'County paperwork submitted. Confirm recording in step 3 when available.'
                    : ($this->ready_on ? 'Ready for recording. Submit the county paperwork and update step 3.'
                        : 'Required paperwork accepted. Review balances and complete the recording steps in step 3.');
            }

            return $this->submitted_on
                ? 'Your paperwork has been submitted to the county. View the recording progress in step 3.'
                : 'Your required paperwork has been accepted. View the next stage in step 3; no further paperwork action is needed now.';
        }
        if ($this->forms_status === 'submitted') {
            return $admin
                ? 'Client reports mailing signed forms. Please confirm receipt and review step 2.'
                : 'Your mailing update was received. Please wait for confirmation that your signed forms have been accepted.';
        }
        if ($this->released_at) {
            return $admin
                ? 'Signing packet released. Awaiting the client’s signed forms for step 2.'
                : 'Your signing packet is ready. Please review and complete step 2 below.';
        }

        return $admin ? 'Client information approved. Prepare and release the signing packet in step 2.'
            : 'Your paperwork details and requests have been reviewed. We are preparing your signing packet for step 2.';
    }

    public function formsLabel(bool $admin = false): string
    {
        return match ($this->forms_status) {
            'complete' => 'Complete',
            'not_required' => 'Not required',
            'submitted' => $admin ? 'Awaiting receipt and review' : 'Awaiting admin review',
            default => $this->released_at
                ? ($admin ? 'Awaiting signed forms' : 'Ready to sign and mail')
                : 'Forms being prepared',
        };
    }

    public function instructionsText(): string
    {
        if (isset($this->signing_instructions['text'])) {
            return $this->signing_instructions['text'];
        }

        return collect($this->instructions())->map(fn ($line, $i) => chr(97 + $i).') '.$line)->implode("\n");
    }

    public function instructions(): array
    {
        return $this->signing_instructions ?? [
            'Please print the required form.',
            'Obtain notarization where indicated (do not sign until in the presence of a notary).',
            'Physically mail the original to us at the address indicated in section 2 on the form.',
        ];
    }
}
