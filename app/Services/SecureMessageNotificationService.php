<?php

namespace App\Services;

use App\Mail\AdminSecureMessageNotificationMail;
use App\Mail\SecureMessageNotificationMail;
use App\Models\AppSetting;
use App\Models\SecureMessageThread;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SecureMessageNotificationService
{
    public function sendToAdmin(SecureMessageThread $thread): bool
    {
        if (AppSetting::valueFor('secure_message_admin_email_enabled', '0') !== '1') return false;
        $email = AppSetting::valueFor('reply_to_email');
        if (blank($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
        try {
            Mail::to(strtolower(trim($email)))->send(new AdminSecureMessageNotificationMail(route('admin.messages.show', $thread)));
            return true;
        } catch (Throwable $exception) {
            report($exception);
            return false;
        }
    }

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
