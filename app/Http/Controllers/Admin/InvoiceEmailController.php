<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceEmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoiceEmailController extends Controller
{
    public function __construct(private readonly InvoiceEmailService $emails) {}

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate(['delivery_format' => ['required', 'in:inline,pdf,both']]);
        $delivery = $this->emails->send($invoice, $request->user(), $data['delivery_format']);

        return back()->with('success', 'Invoice emailed to '.$delivery->recipient_email.'.');
    }
}
