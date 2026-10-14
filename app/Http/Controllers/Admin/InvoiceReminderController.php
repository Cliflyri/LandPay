<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceReminderService;
use App\Services\InvoiceSmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class InvoiceReminderController extends Controller
{
    public function __construct(private readonly InvoiceReminderService $reminders, private readonly InvoiceSmsService $sms) {}

    public function store(Request $request, Invoice $invoice): RedirectResponse|JsonResponse
    {
        $request->validate(['include_sms' => ['sometimes', 'boolean']]);
        $combined = $request->boolean('include_sms');
        $result = function (string $message, string $level, int $status = 200) use ($request) {
            return $request->expectsJson()
                ? response()->json(compact('message', 'level'), $status)
                : back()->with($level === 'success' ? 'success' : 'error', $message);
        };
        if ($combined && ! $this->sms->eligible($this->sms->recipient($invoice), false)) {
            return $result('Email not sent. SMS not sent: this client is not currently eligible for SMS. Use Send Email Reminder instead.', 'danger', 422);
        }
        try {
            $reminder = $this->reminders->send($invoice, $request->user());
        } catch (ValidationException $exception) {
            return $result('Email failed. No SMS sent. '.collect($exception->errors())->flatten()->first(), 'danger', 422);
        } catch (Throwable $exception) {
            report($exception);
            return $result('Email outcome could not be confirmed. No SMS sent. Check reminder history before retrying.', 'warning', 500);
        }
        if (! $combined) return $result('Email sent. No SMS sent.', 'success');
        try {
            $delivery = $this->sms->sendReminder($invoice, $request->user(), $reminder, 'manual-combined-reminder:'.$reminder->id);
            return $delivery
                ? $result('Email sent. SMS submitted.', 'success')
                : $result('Email sent. SMS not sent: client is no longer eligible.', 'warning');
        } catch (Throwable $exception) {
            report($exception);
            return $result('Email sent. SMS failed. Check SMS delivery history before retrying; the email has already been sent.', 'warning');
        }
    }
}
