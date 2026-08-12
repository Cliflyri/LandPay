<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\SecureMessage;
use App\Models\SecureMessageThread;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureMessageController extends Controller
{
    public function index(Request $request): View
    {
        $threads = SecureMessageThread::query()
            ->where('client_id', $request->user('client')->client_id)
            ->with('paymentPlan')
            ->withCount(['messages as unread_count' => fn ($query) =>
                $query->where('sender_type', 'admin')->whereNull('client_viewed_at')
            ])
            ->orderByDesc('latest_message_at')
            ->paginate(20);
        return view('portal.messages.index', compact('threads'));
    }

    public function show(Request $request, SecureMessageThread $thread): View
    {
        $this->authorizeThread($request, $thread);
        $thread->messages()->where('sender_type', 'admin')->whereNull('client_viewed_at')->update(['client_viewed_at' => now()]);
        $thread->load(['paymentPlan', 'messages.senderUser', 'messages.senderClient']);
        return view('portal.messages.show', compact('thread'));
    }

    public function reply(Request $request, SecureMessageThread $thread): RedirectResponse
    {
        $this->authorizeThread($request, $thread);
        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);

        DB::transaction(function () use ($request, $thread, $data): void {
            $thread->messages()->create([
                'sender_type' => 'client',
                'sender_client_id' => $request->user('client')->client_id,
                'body' => $data['body'],
            ]);
            $thread->update(['latest_message_at' => now()]);
            AdminNotice::query()->create([
                'type' => 'secure_message_reply',
                'client_id' => $thread->client_id,
                'secure_message_thread_id' => $thread->id,
                'title' => 'Secure message reply',
                'message' => $request->user('client')->displayName().' replied to "'.$thread->subject.'"'.($thread->paymentPlan ? ' for plan '.$thread->paymentPlan->plan_number : '').'.',
            ]);
        });

        return back()->with('success', 'Your reply was sent securely.');
    }

    public function download(Request $request, SecureMessageThread $thread, SecureMessage $message): StreamedResponse
    {
        $this->authorizeThread($request, $thread);
        abort_unless($message->secure_message_thread_id === $thread->id && filled($message->attachment_path), 404);
        abort_unless(Storage::disk($message->attachment_disk)->exists($message->attachment_path), 404);
        $message->update(['attachment_downloaded_at' => $message->attachment_downloaded_at ?? now()]);
        return Storage::disk($message->attachment_disk)->download($message->attachment_path, $message->attachment_name);
    }

    private function authorizeThread(Request $request, SecureMessageThread $thread): void
    {
        abort_unless($thread->client_id === $request->user('client')->client_id, 404);
    }
}
