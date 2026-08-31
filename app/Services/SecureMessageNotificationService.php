<?php

namespace App\Services;

use App\Mail\SecureMessageNotificationMail;
use App\Models\SecureMessageThread;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SecureMessageNotificationService
{
    public function send(SecureMessageThread $thread, bool $reminder = false): bool
    {
        $email = $thread->client->email;
        if (blank($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $thread->update(['notification_status' => 'unavailable']);
            return false;
        }
        try {
            Mail::to(strtolower(trim($email)))->send(new SecureMessageNotificationMail(route('portal.messages.show', $thread)));
            $thread->update(['notification_last_sent_at'=>now(),'notification_status'=>'sent','reminder_count'=>$thread->reminder_count+($reminder?1:0)]);
            return true;
        } catch (Throwable $exception) {
            report($exception);
            $thread->update(['notification_status'=>'failed']);
            return false;
        }
    }
}
