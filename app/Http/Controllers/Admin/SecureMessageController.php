<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\Client;
use App\Models\PaymentPlan;
use App\Models\SecureMessage;
use App\Models\SecureMessageAttachment;
use App\Models\SecureMessageRevision;
use App\Models\SecureMessageThread;
use App\Models\SharedDocument;
use App\Services\SecureMessageAttachmentService;
use App\Services\SecureMessageFileService;
use App\Services\SecureMessageNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SecureMessageController extends Controller
{
    public function __construct(private readonly SecureMessageNotificationService $notifications, private readonly SecureMessageAttachmentService $attachments, private readonly SecureMessageFileService $files) {}

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
            'documents' => SharedDocument::query()->where('visible_to_client', true)->whereNull('archived_at')->orderByDesc('created_at')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateMessage($request, true);
        $this->validatePlanReference((int) $data['client_id'], $data['payment_plan_id'] ?? null);
        $this->validateDocumentReferences((int) $data['client_id'], $data['shared_document_ids'] ?? []);

        try {
            $thread = DB::transaction(function () use ($request, $data): SecureMessageThread {
                $thread = SecureMessageThread::query()->create([
                    'client_id' => $data['client_id'],
                    'payment_plan_id' => $data['payment_plan_id'] ?? null,
                    'subject' => $data['subject'],
                    'category' => $data['category'],
                    'latest_message_at' => now(),
                ]);
                $message=$thread->messages()->create([
                    'sender_type' => 'admin',
                    'sender_user_id' => $request->user()->id,
                    'body' => $data['body'],
                ]);
                $this->files->attach($message,$request->file('attachments',[]),$data['save_in_documents']??[],$data['shared_document_ids']??[],['client_id'=>$thread->client_id,'payment_plan_id'=>$thread->payment_plan_id,'user_id'=>$request->user()->id,'category'=>$this->documentCategory($data['category'])]);
                return $thread;
            });
        } catch (Throwable $exception) {
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
        $thread->load(['client', 'paymentPlan', 'messages.senderUser', 'messages.senderClient', 'messages.documents', 'messages.attachments', 'messages.revisions.editor']);
        $documents=SharedDocument::query()->where('client_id',$thread->client_id)->where('visible_to_client',true)->whereNull('archived_at')->latest()->get();
        return view('admin.messages.show', compact('thread','documents'));
    }

    public function reply(Request $request, SecureMessageThread $thread): RedirectResponse
    {
        $data = $this->validateMessage($request, false);
        $this->validateDocumentReferences($thread->client_id, $data['shared_document_ids'] ?? []);
        try {
            DB::transaction(function () use ($request, $thread, $data): void {
                $message=$thread->messages()->create([
                    'sender_type' => 'admin',
                    'sender_user_id' => $request->user()->id,
                    'body' => $data['body'],
                ]);
                $this->files->attach($message,$request->file('attachments',[]),$data['save_in_documents']??[],$data['shared_document_ids']??[],['client_id'=>$thread->client_id,'payment_plan_id'=>$thread->payment_plan_id,'user_id'=>$request->user()->id,'category'=>$this->documentCategory($thread->category)]);
                $thread->update(['latest_message_at' => now()]);
            });
        } catch (Throwable $exception) {
            throw $exception;
        }
        $sent = $this->notifications->send($thread->load('client'));
        return back()->with('success', 'Reply sent.'.($sent ? ' Email notification sent.' : ' Email notification was not sent.'));
    }

    public function update(Request $request, SecureMessageThread $thread, SecureMessage $message): RedirectResponse
    {
        abort_unless($message->secure_message_thread_id === $thread->id && $message->sender_type === 'admin', 404);
        $data = $request->validate(['body' => ['nullable', 'string', 'max:10000']]);
        $body = trim((string) ($data['body'] ?? ''));
        if ($body === '' && blank($message->attachment_path) && ! $message->attachments()->exists() && ! $message->documents()->exists()) {
            throw ValidationException::withMessages(['body' => 'Message text is required when there is no attachment.']);
        }
        if ($body === $message->body) return back()->with('success', 'No message changes were made.');

        DB::transaction(function () use ($request, $message, $body): void {
            SecureMessageRevision::query()->create([
                'secure_message_id' => $message->id,
                'body' => $message->body,
                'edited_by_user_id' => $request->user()->id,
            ]);
            $message->update(['body' => $body]);
        });

        return back()->with('success', 'Message text updated. Previously sent notifications were not changed or resent.');
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

    public function downloadFile(Request $request,SecureMessageThread $thread,SecureMessage $message,SecureMessageAttachment $attachment)
    {
        abort_unless($message->secure_message_thread_id===$thread->id&&$attachment->secure_message_id===$message->id,404);
        $disk=Storage::disk($attachment->disk);abort_unless($disk->exists($attachment->path),404);
        if($request->boolean('inline')&&in_array($attachment->mime,['image/jpeg','image/png'],true))return response()->file($disk->path($attachment->path),['Content-Type'=>$attachment->mime]);
        return $disk->download($attachment->path,$attachment->name,['X-Content-Type-Options'=>'nosniff']);
    }

    public function destroyFile(SecureMessageThread $thread,SecureMessage $message,SecureMessageAttachment $attachment):RedirectResponse
    {
        abort_unless($message->secure_message_thread_id===$thread->id&&$attachment->secure_message_id===$message->id,404);
        abort_if(!$this->files->deleteAttachmentFile($attachment),500,'The attachment could not be deleted.');
        $attachment->delete();return back()->with('success','Attachment deleted.');
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
        $newAttachments=SecureMessageAttachment::query()->whereHas('message',fn($query)=>$query->where('secure_message_thread_id',$thread->id))->get();
        foreach($newAttachments as $attachment)abort_if(!$this->files->deleteAttachmentFile($attachment),500,'An attachment could not be deleted.');
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
            'attachments' => ['nullable','array','max:5'],
            'attachments.*' => ['file','mimes:pdf,jpg,jpeg,png,docx','mimetypes:application/pdf,image/jpeg,image/png,application/vnd.openxmlformats-officedocument.wordprocessingml.document','max:10240'],
            'save_in_documents' => ['nullable','array','max:5'],
            'save_in_documents.*' => ['integer','between:0,4'],
            'shared_document_ids' => ['nullable','array','max:5'],
            'shared_document_ids.*' => ['integer','distinct','exists:shared_documents,id'],
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

    private function validateDocumentReferences(int $clientId, array $documentIds): void
    {
        if (! $documentIds) return;
        $count=SharedDocument::query()->whereKey($documentIds)->where('client_id',$clientId)->where('visible_to_client',true)->whereNull('archived_at')->count();
        abort_unless($count===count(array_unique($documentIds)),422,'A selected document is not available to this client.');
    }

    private function documentCategory(string $category):string{return match($category){'contract'=>'contract','closing_documents'=>'closing_document',default=>'general'};}


}
