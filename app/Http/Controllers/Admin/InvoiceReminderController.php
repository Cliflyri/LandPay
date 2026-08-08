<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoiceReminderController extends Controller
{
    public function __construct(private readonly InvoiceReminderService $reminders) {}

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $reminder = $this->reminders->send($invoice, $request->user());

        return back()->with('success', 'Reminder sent to '.$reminder->recipient_email.'.');
    }
}
