<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\Client;
use App\Models\PaymentPlan;
use App\Models\SecureMessage;
use App\Models\SecureMessageThread;
use App\Services\SecureMessageAttachmentService;
use App\Services\SecureMessageNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SecureMessageController extends Controller
{
    public function __construct(private readonly SecureMessageNotificationService $notifications, private readonly SecureMessageAttachmentService $attachments) {}

    public function index(Request $request): View
    {
        $filter = in_array($request->string('filter')->value(), ['unread', 'starred'], true)
            ? $request->string('filter')->value() : 'all';

        $threads = SecureMessageThread::query()
            ->with(['client', 'paymentPlan'])
            ->withCount(['messages as unread_count' => fn ($query) =>
                $query->where('sender_type', 'client')->whereNull('admin_viewed_at')
            ])
            ->when($filter === 'unread', fn ($query) => $query->unreadByAdmin())
            ->when($filter === 'starred', fn ($query) => $query->whereNotNull('starred_at'))
            ->orderByDesc('latest_message_at')
            ->paginate(25)
            ->withQueryString();

        $adminEmail = \App\Models\AppSetting::valueFor('reply_to_email', '');
        $adminEmailAvailable = filled($adminEmail) && filter_var($adminEmail, FILTER_VALIDATE_EMAIL);

        return view('admin.messages.index', [
            'threads' => $threads,
            'filter' => $filter,
            'adminEmail' => $adminEmail,
            'adminEmailAvailable' => $adminEmailAvailable,
            'adminEmailEnabled' => $adminEmailAvailable && \App\Models\AppSetting::valueFor('secure_message_admin_email_enabled', '0') === '1',
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.messages.create', [
            'clients' => Client::query()->whereNull('archived_at')->orderBy('last_name')->orderBy('first_name')->get(),
            'plans' => PaymentPlan::query()->with('memberships')->orderBy('plan_number')->get(),
            'selectedClientId' => $request->integer('client') ?: null,
            'selectedPlanId' => $request->integer('plan') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateMessage($request, true);
        $this->validatePlanReference((int) $data['client_id'], $data['payment_plan_id'] ?? null);
        $attachment = $this->attachments->store($request->file('attachment'), true);

        try {
            $thread = DB::transaction(function () use ($request, $data, $attachment): SecureMessageThread {
                $thread = SecureMessageThread::query()->create([
                    'client_id' => $data['client_id'],
                    'payment_plan_id' => $data['payment_plan_id'] ?? null,
                    'subject' => $data['subject'],
                    'category' => $data['category'],
                    'latest_message_at' => now(),
                ]);
                $thread->messages()->create($attachment + [
                    'sender_type' => 'admin',
                    'sender_user_id' => $request->user()->id,
                    'body' => $data['body'],
                ]);
                return $thread;
            });
        } catch (Throwable $exception) {
            $this->attachments->delete($attachment);
            throw $exception;
        }

        $sent = $this->notifications->send($thread->load('client'));
        return redirect()->route('admin.messages.show', $thread)
            ->with('success', 'Secure message sent.'.($sent ? ' Email notification sent.' : ' Email notification was not sent.'));
    }

    public function show(SecureMessageThread $thread): View
    {
        $thread->messages()->where('sender_type', 'client')->whereNull('admin_viewed_at')->update(['admin_viewed_at' => now()]);
        AdminNotice::query()->where('secure_message_thread_id', $thread->id)->whereNull('dismissed_at')->update([
            'dismissed_at' => now(),
            'dismissed_by_user_id' => auth()->id(),
        ]);
        $thread->load(['client', 'paymentPlan', 'messages.senderUser', 'messages.senderClient']);
        return view('admin.messages.show', compact('thread'));
    }

    public function reply(Request $request, SecureMessageThread $thread): RedirectResponse
    {
        $data = $this->validateMessage($request, false);
        $attachment = $this->attachments->store($request->file('attachment'), true);
        try {
            DB::transaction(function () use ($request, $thread, $data, $attachment): void {
                $thread->messages()->create($attachment + [
                    'sender_type' => 'admin',
                    'sender_user_id' => $request->user()->id,
                    'body' => $data['body'],
                ]);
                $thread->update(['latest_message_at' => now()]);
            });
        } catch (Throwable $exception) {
            $this->attachments->delete($attachment);
            throw $exception;
        }
        $sent = $this->notifications->send($thread->load('client'));
        return back()->with('success', 'Reply sent.'.($sent ? ' Email notification sent.' : ' Email notification was not sent.'));
    }

    public function updateEmailNotifications(Request $request): RedirectResponse
    {
        $email = \App\Models\AppSetting::valueFor('reply_to_email');
        if ($request->boolean('enabled') && (blank($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL))) {
            return back()->with('error', 'Set a valid reply-to email in Admin Settings before enabling secure-message email notifications.');
        }
        \App\Models\AppSetting::putMany(['secure_message_admin_email_enabled' => $request->boolean('enabled') ? '1' : '0']);
        return back()->with('success', 'Administrator email notifications updated.');
    }

    public function star(SecureMessageThread $thread): RedirectResponse
    {
        $starred = ! $thread->starred_at;
        $thread->update(['starred_at' => $starred ? now() : null]);
        return back()->with('success', $starred ? 'Conversation starred for follow-up.' : 'Follow-up star removed.');
    }

    public function remind(SecureMessageThread $thread): RedirectResponse
    {
        $sent = $this->notifications->send($thread->load('client'), true);
        return back()->with($sent ? 'success' : 'error', $sent ? 'Email reminder sent.' : 'Email reminder could not be sent.');
    }

    public function download(Request $request, SecureMessageThread $thread, SecureMessage $message)
    {
        abort_unless($message->secure_message_thread_id === $thread->id && filled($message->attachment_path), 404);
        $disk = Storage::disk($message->attachment_disk ?: 'local');
        abort_unless($disk->exists($message->attachment_path), 404);

        if ($request->boolean('inline') && in_array($message->attachment_mime, ['image/jpeg', 'image/png'], true)) {
            return response()->file($disk->path($message->attachment_path), ['Content-Type' => $message->attachment_mime]);
        }

        return $disk->download($message->attachment_path, $message->attachment_name);
    }

    public function destroyAttachment(SecureMessageThread $thread, SecureMessage $message): RedirectResponse
    {
        abort_unless($message->secure_message_thread_id === $thread->id && filled($message->attachment_path), 404);
        $disk = Storage::disk($message->attachment_disk ?: 'local');
        abort_if($disk->exists($message->attachment_path) && ! $disk->delete($message->attachment_path), 500, 'The attachment could not be deleted.');

        $message->update([
            'attachment_disk' => null,
            'attachment_path' => null,
            'attachment_name' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
            'attachment_downloaded_at' => null,
        ]);

        return back()->with('success', 'Attachment deleted.');
    }

    public function destroy(SecureMessageThread $thread): RedirectResponse
    {
        $attachments = $thread->messages()->whereNotNull('attachment_path')
            ->get(['attachment_disk', 'attachment_path']);

        foreach ($attachments as $attachment) {
            $disk = Storage::disk($attachment->attachment_disk ?: 'local');
            abort_if($disk->exists($attachment->attachment_path) && ! $disk->delete($attachment->attachment_path), 500, 'An attachment could not be deleted.');
        }

        DB::transaction(function () use ($thread): void {
            AdminNotice::query()->where('secure_message_thread_id', $thread->id)->delete();
            $thread->delete();
        });

        return redirect()->route('admin.messages.index')->with('success', 'Secure message conversation deleted.');
    }

    private function validateMessage(Request $request, bool $new): array
    {
        $rules = [
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'mimetypes:application/pdf,image/jpeg,image/png', 'max:10240'],
        ];
        if ($new) $rules += [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'payment_plan_id' => ['nullable', 'integer', 'exists:payment_plans,id'],
            'subject' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(['general', 'closing_documents', 'contract'])],
        ];
        return $request->validate($rules);
    }

    private function validatePlanReference(int $clientId, mixed $planId): void
    {
        if (! $planId) return;
        abort_unless(DB::table('payment_plan_clients')->where('client_id', $clientId)->where('payment_plan_id', $planId)->exists(), 422, 'The selected plan is not associated with this client.');
    }


}
