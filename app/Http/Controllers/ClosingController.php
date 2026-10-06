<?php

namespace App\Http\Controllers;

use App\Models\AdminNotice;
use App\Models\ClosingDocument;
use App\Models\PaymentPlan;
use App\Models\PlanClosing;
use App\Services\ClosingNotificationService;
use App\Services\ClosingWorkflowService;
use App\Services\SecureMessageNotificationService;
use App\Services\VestingGuideService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClosingController extends Controller
{
    public function __construct(private readonly ClosingWorkflowService $workflow) {}

    private function authorizeClient(Request $request, PaymentPlan $plan, bool $write = false): PlanClosing
    {
        abort_unless(in_array($plan->id, $request->user('client')->activePlanIds(), true), 404);
        $closing = $plan->closing;
        abort_unless($closing && ($write ? $closing->status === 'active' : $closing->visible()), 403);

        return $closing;
    }

    public function dismiss(Request $request, PaymentPlan $plan)
    {
        $closing = $plan->closing;
        abort_unless($closing?->eligible_at, 404);
        DB::table('closing_notice_dismissals')->insertOrIgnore([
            'plan_closing_id' => $closing->id, 'user_id' => $request->user()->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'Closing review notification dismissed for your account.');
    }

    public function admin(Request $request, PaymentPlan $plan)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['save', 'start', 'resume', 'disable', 'review_paperwork', 'review_details', 'review_extras', 'review_forms', 'release', 'ready', 'submit', 'complete', 'reopen', 'use_default_vesting', 'reopen_step', 'undo_progress'])],
            'version' => ['required', 'integer', 'min:0'],
            'section' => ['required_if:action,reopen_step', Rule::in(['paperwork', 'details', 'extras', 'forms'])],
            'milestone' => ['required_if:action,undo_progress', Rule::in(['paperwork', 'ready', 'submitted', 'recorded'])],
            'reopen_instruction' => ['nullable', 'string', 'max:3000'],
            'admin_notes' => ['nullable', 'string', 'max:10000'],
            'vesting_notes' => ['nullable', 'string', 'max:10000'],
            'instructions' => ['sometimes', 'array', 'size:3'],
            'instructions.*' => ['required', 'string', 'max:3000'],
            'client_note' => ['nullable', 'string', 'max:3000'],
            'show_hold_notice' => ['sometimes', 'boolean'],
            'notify_client' => ['sometimes', 'boolean'],
            'section_status' => ['sometimes', Rule::in(['needed', 'complete', 'not_required'])],
            'acknowledge_balance' => ['sometimes', 'accepted'],
            'balance_reason' => ['nullable', 'string', 'max:3000'],
            'milestone_date' => ['nullable', 'date', 'before_or_equal:today'],
            'recording_reference' => ['nullable', 'string', 'max:255'],
        ]);
        $closing = $this->workflow->closing($plan);
        DB::transaction(function () use ($request, $plan, $data, $closing) {
            PaymentPlan::whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $closing = PlanClosing::whereKey($closing->id)->lockForUpdate()->firstOrFail();
            $this->workflow->require((int) $closing->version === (int) $data['version'], 'This closing has changed. Refresh the page and review the latest information before saving.');
            $action = $data['action'];
            $context = [];
            if (in_array($action, ['reopen_step', 'undo_progress'], true)) {
                $context['previous'] = $closing->only(['status', 'details_status', 'extras_status', 'forms_status', 'released_at',
                    'paperwork_accepted_at', 'ready_on', 'submitted_on', 'recorded_on', 'recording_reference']);
                if ($action === 'reopen_step') {
                    $this->workflow->reopenStep($closing, $data['section'], $data['reopen_instruction'] ?? null);
                    $context['section'] = $data['section'];
                } else {
                    $this->workflow->undoProgress($closing, $data['milestone'], $data['reopen_instruction'] ?? null);
                    $context['milestone'] = $data['milestone'];
                }
                $context['client_instructions'] = $closing->details['reopen_steps'] ?? [];
            } elseif ($action === 'use_default_vesting') {
                $context = app(VestingGuideService::class)->assign($closing);
            } elseif ($action === 'save') {
                foreach (['admin_notes', 'vesting_notes'] as $field) {
                    if (array_key_exists($field, $data)) {
                        $closing->{$field} = $data[$field];
                    }
                }
                if (array_key_exists('instructions', $data)) {
                    $closing->signing_instructions = $data['instructions'];
                }
            } elseif (in_array($action, ['start', 'resume'], true)) {
                $this->workflow->require(in_array($closing->status, ['review', 'hold'], true), 'Only a new or held closing can be started or resumed.');
                if ($closing->status === 'review' && ! $closing->documents()->where('kind', 'vesting')->exists()
                    && app(VestingGuideService::class)->current()) {
                    $context['vesting_guide'] = app(VestingGuideService::class)->assign($closing);
                }
                $closing->status = 'active';
            } elseif ($action === 'disable') {
                $this->workflow->require($closing->status === 'active', 'Only an active closing can be disabled.');
                $closing->fill(['status' => 'hold', 'show_hold_notice' => $request->boolean('show_hold_notice'),
                    'client_note' => $data['client_note'] ?? null]);
            } elseif ($action === 'reopen') {
                $this->workflow->require($closing->status === 'completed', 'Only completed closings can be reopened.');
                $closing->fill(['status' => 'active', 'recorded_on' => null]);
            } elseif ($action === 'review_paperwork') {
                $this->workflow->require($closing->status !== 'completed', 'Reopen closing before changing an approval.');
                $this->workflow->require(! empty($closing->details['combined_submission']) && ! empty($closing->details['confirmed'])
                    && $closing->details_status !== 'draft' && filled($closing->extras_choice),
                    'Await the combined confirmed client submission before approval.');
                $details = $closing->details;
                unset($details['reopen_steps'][1], $details['reopen_steps'][2], $details['reopen_steps']['1-2']);
                $closing->fill(['details' => $details, 'details_status' => 'complete',
                    'extras_status' => $closing->extras_choice === 'none' ? 'not_required' : 'complete']);
                $context = ['section' => 'paperwork', 'details' => $details, 'extras_choice' => $closing->extras_choice];
            } elseif (str_starts_with($action, 'review_')) {
                $this->workflow->require($closing->status !== 'completed', 'Reopen closing before changing an approval.');
                $section = substr($action, 7);
                $status = $data['section_status'] ?? 'needed';
                $this->workflow->require($section !== 'details' || $status !== 'not_required', 'Paperwork details always require review.');
                if (in_array($section, ['details', 'extras'], true) && in_array($status, ['complete', 'not_required'], true)) {
                    $this->workflow->require(! empty($closing->details['combined_submission']),
                        'Await the combined submission of client details and additional-paperwork choices before approval.');
                }
                if ($section === 'details' && $status === 'complete') {
                    $this->workflow->require(! empty($closing->details['confirmed']) && $closing->details_status !== 'draft',
                        'Client must submit and confirm the paperwork details before approval.');
                }
                if ($section === 'extras' && $status === 'complete') {
                    $this->workflow->require(filled($closing->extras_choice), 'Review the client request before marking it complete.');
                }
                if ($section === 'forms' && in_array($status, ['complete', 'not_required'], true)) {
                    $this->requireReviewed($closing);
                    $this->workflow->require($status === 'not_required' || (bool) $closing->released_at, 'Release the signing packet before accepting signed forms.');
                    $closing->paperwork_accepted_at ??= now();
                } elseif ($status === 'needed') {
                    $this->workflow->reopenStep($closing, $section, $data['reopen_instruction'] ?? null);
                }
                if ($status !== 'needed') {
                    $details = $closing->details ?? [];
                    unset($details['reopen_steps'][['details' => 1, 'extras' => 2, 'forms' => 3][$section]]);
                    $closing->details = $details;
                }
                $closing->{$section.'_status'} = $status;
                $context = ['section' => $section, 'status' => $status, 'details' => $section === 'details' ? $closing->details : null];
            } elseif ($action === 'release') {
                $this->requireReviewed($closing);
                $this->workflow->require($closing->status === 'active', 'Activate closing before releasing forms.');
                $this->workflow->require($closing->documents()->where('kind', 'signing')->exists(), 'Upload a signing form before releasing the packet.');
                $closing->released_at = now();
            } elseif (in_array($action, ['ready', 'submit', 'complete'], true)) {
                $this->workflow->require($closing->status === 'active', 'Closing must be active.');
                $this->requireReviewed($closing);
                $this->workflow->require(in_array($closing->forms_status, ['complete', 'not_required'], true), 'Accept all required paperwork before proceeding.');
                if ($action === 'submit') {
                    $this->workflow->require((bool) $closing->ready_on, 'Mark ready for recording first.');
                }
                if ($action === 'complete') {
                    $this->workflow->require((bool) $closing->submitted_on, 'Record county submission first.');
                }
                $this->workflow->require(filled($data['milestone_date'] ?? null), 'Enter the milestone date.');
                $date = $data['milestone_date'];
                if ($action === 'submit') {
                    $this->workflow->require($date >= $closing->ready_on->toDateString(), 'Submission cannot precede readiness.');
                }
                if ($action === 'complete') {
                    $this->workflow->require($date >= $closing->submitted_on->toDateString(), 'Recording cannot precede submission.');
                }
                if ($action === 'ready') {
                    $this->workflow->require(! $closing->submitted_on, 'County submission is already recorded.');
                }
                if ($action === 'submit') {
                    $this->workflow->require(! $closing->submitted_on, 'County submission is already recorded.');
                }
                $context = $this->workflow->checkBalances($plan, $data);
                $field = ['ready' => 'ready_on', 'submit' => 'submitted_on', 'complete' => 'recorded_on'][$action];
                $closing->{$field} = $date;
                if ($action === 'complete') {
                    $closing->fill(['status' => 'completed', 'recording_reference' => $data['recording_reference'] ?? null]);
                }
                $context += ['date' => $date, 'reference' => $data['recording_reference'] ?? null];
            }
            $closing->version++;
            $closing->save();
            $this->workflow->log($closing, $action, $context ?: $closing->only(['status', 'admin_notes', 'vesting_notes', 'signing_instructions', 'client_note', 'show_hold_notice']), $request->user()->id);
        });
        $message = 'Closing updated.';
        if (in_array($data['action'], ['start', 'resume', 'release'], true) && $request->boolean('notify_client')) {
            $results = app(ClosingNotificationService::class)->send($closing->fresh(), $request->user());
            $sent = collect($results)->sum(fn ($result) => (int) ($result['email'] === 'sent') + (int) ($result['sms'] === 'sent'));
            $failed = collect($results)->contains(fn ($result) => $result['email'] === 'failed' || $result['sms'] === 'failed');
            $message .= " {$sent} notification(s) sent.".($failed ? ' Some deliveries failed; review closing history.' : '');
        }

        return redirect()->to(route('admin.plans.show', $plan).'#closing')->with('success', $message);
    }

    private function requireReviewed(PlanClosing $closing): void
    {
        $this->workflow->require($closing->details_status === 'complete'
            && in_array($closing->extras_status, ['complete', 'not_required'], true),
            'Approve paperwork details and resolve additional paperwork and fees first.');
    }

    public function client(Request $request, PaymentPlan $plan)
    {
        $closing = $this->authorizeClient($request, $plan, true);
        $data = $request->validate([
            'action' => ['required', Rule::in(['details', 'draft', 'mailed'])],
            'version' => ['required', 'integer', 'min:0'],
        ]);
        $action = $data['action'];
        if (in_array($action, ['details', 'draft'], true)) {
            $required = $action === 'details' ? 'required' : 'nullable';
            $data += $request->validate([
                'titling' => [$required, 'string', 'max:3000'],
                'owners' => ['required', 'array', 'min:1', 'max:20'],
                'owners.*.name' => [$required, 'string', 'max:255'],
                'owners.*.address' => [$required, 'string', 'max:2000'],
                'owners.*.mailing_address' => ['nullable', 'string', 'max:2000'],
                'owners.*.married' => [$required, Rule::in(['yes', 'no'])],
                'beneficiary' => [$required, Rule::in(['yes', 'no'])],
                'extras_choice' => [$required, Rule::in(['none', 'request'])],
                'extras_comments' => $action === 'details'
                    ? ['nullable', 'required_if:extras_choice,request', 'string', 'max:5000']
                    : ['nullable', 'string', 'max:5000'],
                'confirmed' => $action === 'details' ? ['required', 'accepted'] : ['nullable', 'boolean'],
            ]);

        }
        DB::transaction(function () use ($request, $closing, $data, $action) {
            $closing = PlanClosing::whereKey($closing->id)->lockForUpdate()->firstOrFail();
            abort_unless($closing->status === 'active', 403);
            $this->workflow->require((int) $closing->version === (int) $data['version'], 'This closing has changed. Refresh and review the latest information.');
            $this->workflow->require(! $closing->submitted_on, 'Paperwork has been submitted to the county. Contact admin for any corrections.');
            if (in_array($action, ['details', 'draft'], true)) {
                $this->workflow->require(! $closing->paperworkReviewed(), 'Your requests are approved. Please contact us to request changes.');
                $reopenNotes = $closing->details['reopen_steps'] ?? [];
                $packetPreviouslyReleased = $closing->packetPreviouslyReleased();
                if ($action === 'details') {
                    $this->workflow->require(($data['beneficiary'] ?? '') !== 'yes' || $data['extras_choice'] === 'request',
                        'You requested a beneficiary deed. Select a special request in step 2, or update your beneficiary answer.');
                }
                $closing->details = ['titling' => $data['titling'] ?? '', 'owners' => array_values($data['owners']),
                    'beneficiary' => $data['beneficiary'] ?? '', 'confirmed' => $action === 'details',
                    'packet_previously_released' => $packetPreviouslyReleased,
                    'combined_submission' => $action === 'details', 'reopen_steps' => $action === 'draft' ? $reopenNotes : []];
                $closing->fill([
                    'details_status' => $action === 'details' ? 'submitted' : 'draft',
                    'extras_status' => $action === 'details' ? 'submitted' : 'draft',
                    'extras_choice' => $data['extras_choice'] ?? null,
                    'extras_comments' => $data['extras_comments'] ?? null,
                ]);
                $this->workflow->reopenPaperwork($closing);
            } elseif ($action === 'mailed') {
                $this->workflow->require((bool) $closing->released_at && ! in_array($closing->forms_status, ['complete', 'not_required'], true), 'There is no released packet awaiting receipt.');
                $closing->forms_status = 'submitted';
                $details = $closing->details ?? [];
                unset($details['reopen_steps'][3]);
                $closing->details = $details;
            }
            $closing->version++;
            $closing->save();
            $this->workflow->log($closing, 'Client '.$action, $data, null, $request->user('client')->client_id);
            if ($action !== 'draft') {
                AdminNotice::create([
                    'type' => 'closing_submission', 'client_id' => $request->user('client')->client_id,
                    'payment_plan_id' => $closing->payment_plan_id, 'title' => 'Closing paperwork update',
                    'message' => $action === 'mailed'
                        ? 'Client reports mailing signed forms. Please confirm receipt and review step 3.'
                        : 'Client submitted sections 1 and 2. Please review.',
                ]);
            }
        });

        return redirect()->to(route('portal.dashboard').'#closing-'.$plan->id)->with('success', $action === 'draft' ? 'Draft saved.' : 'Submitted for admin review.');
    }

    public function upload(Request $request, PaymentPlan $plan)
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(['vesting', 'signing', 'recorded'])],
            'version' => ['required', 'integer'],
            'document' => $request->input('kind') === 'vesting'
                ? ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240']
                : ['required', 'file', 'mimes:pdf,docx,jpg,jpeg,png', 'max:10240'],
        ]);
        $closing = $this->workflow->closing($plan);
        $path = $request->file('document')->store('closing/'.$closing->id, 'local');
        try {
            DB::transaction(function () use ($request, $closing, $data, $path) {
                $closing = PlanClosing::whereKey($closing->id)->lockForUpdate()->firstOrFail();
                $this->workflow->require((int) $closing->version === (int) $data['version'], 'Closing changed. Refresh before uploading.');
                if ($data['kind'] === 'signing') {
                    $this->workflow->require(! $closing->submitted_on && $closing->status !== 'completed', 'Reopen the paperwork review before changing a packet submitted to the county.');
                    $this->workflow->reopenPaperwork($closing);
                }
                if ($data['kind'] === 'vesting') {
                    app(VestingGuideService::class)->removeReferences($closing);
                }
                $document = $closing->documents()->create(['kind' => $data['kind'], 'path' => $path, 'name' => basename($request->file('document')->getClientOriginalName())]);
                $closing->version++;
                $closing->save();
                $this->workflow->log($closing, 'Document uploaded', ['name' => $document->name, 'kind' => $document->kind], $request->user()->id);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return redirect()->to(route('admin.plans.show', $plan).'#closing')->with('success', 'Document uploaded. Signing packet changes require release again.');
    }

    public function removeDocument(Request $request, PaymentPlan $plan, ClosingDocument $document)
    {
        abort_unless($document->closing->payment_plan_id === $plan->id, 404);
        $request->validate(['version' => ['required', 'integer']]);
        DB::transaction(function () use ($request, $document) {
            $closing = PlanClosing::whereKey($document->plan_closing_id)->lockForUpdate()->firstOrFail();
            $this->workflow->require((int) $closing->version === $request->integer('version'), 'Closing changed. Refresh before removing a document.');
            if ($document->kind === 'signing') {
                $this->workflow->require(! $closing->submitted_on && $closing->status !== 'completed', 'Reopen paperwork review before changing a submitted packet.');
                $this->workflow->reopenPaperwork($closing);
            }
            $this->workflow->log($closing, 'Document removed', ['name' => $document->name, 'kind' => $document->kind], $request->user()->id);
            $document->delete();
            $closing->version++;
            $closing->save();
        });
        if (! str_starts_with($document->path, VestingGuideService::PREFIX)) {
            Storage::disk('local')->delete($document->path);
        }

        return back()->with('success', 'Document removed.');
    }

    public function download(Request $request, PaymentPlan $plan, ClosingDocument $document)
    {
        abort_unless($document->closing->payment_plan_id === $plan->id, 404);
        if ($request->routeIs('portal.*')) {
            $closing = $this->authorizeClient($request, $plan);
            abort_if($document->kind === 'signing' && ! $closing->released_at, 403);
            abort_if($document->kind === 'recorded' && $closing->status !== 'completed', 403);
        }
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        if ($request->boolean('inline')) {
            abort_unless($document->kind === 'vesting' && Storage::disk('local')->mimeType($document->path) === 'application/pdf', 415);

            return response()->file(Storage::disk('local')->path($document->path), [
                'Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline',
                'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return Storage::disk('local')->download($document->path, $document->name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function message(Request $request, PaymentPlan $plan)
    {
        $admin = $request->routeIs('admin.*');
        $closing = $admin ? $this->workflow->closing($plan) : $this->authorizeClient($request, $plan, true);
        $data = $request->validate(['body' => ['required', 'string', 'max:10000'], 'client_id' => [$admin ? 'required' : 'nullable', 'integer']]);
        $clientId = $admin ? (int) $data['client_id'] : $request->user('client')->client_id;
        abort_unless($plan->memberships()->where('client_id', $clientId)->whereNull('effective_to')->whereDate('effective_from', '<=', today())->exists(), 404);
        $thread = DB::transaction(function () use ($request, $plan, $closing, $admin, $data, $clientId) {
            $closing = PlanClosing::whereKey($closing->id)->lockForUpdate()->firstOrFail();
            if (! $admin) {
                abort_unless($closing->status === 'active', 403);
            }
            $thread = $closing->threads()->firstOrCreate(['client_id' => $clientId], [
                'payment_plan_id' => $plan->id, 'subject' => 'Closing: '.$plan->plan_number, 'category' => 'general', 'latest_message_at' => now(),
            ]);
            $thread->messages()->create(['sender_type' => $admin ? 'admin' : 'client',
                'sender_user_id' => $admin ? $request->user()->id : null,
                'sender_client_id' => $admin ? null : $clientId, 'body' => $data['body']]);
            $thread->update(['latest_message_at' => now()]);
            if (! $admin) {
                AdminNotice::create(['type' => 'secure_message_reply', 'client_id' => $clientId,
                    'payment_plan_id' => $plan->id, 'secure_message_thread_id' => $thread->id,
                    'title' => 'Closing message', 'message' => 'New closing message for plan '.$plan->plan_number.'.']);
            }

            return $thread;
        });
        if ($admin) {
            app(SecureMessageNotificationService::class)->send($thread->load('client'));
        }

        return redirect()->to($admin ? route('admin.plans.show', $plan).'#closing-messages' : ($closing->compactProgress() ? route('portal.messages.show', $thread) : route('portal.dashboard').'#closing-messages-'.$plan->id))
            ->with('success', 'Closing message sent.');
    }
}
