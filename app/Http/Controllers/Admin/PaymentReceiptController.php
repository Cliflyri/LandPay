<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentReceiptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentReceiptController extends Controller
{
    public function __construct(private readonly PaymentReceiptService $receipts) {}

    public function store(Request $request, Payment $payment): RedirectResponse
    {
        $delivery = $this->receipts->send($payment, $request->user());
        return back()->with('success', 'Receipt emailed to '.$delivery->recipient_email.'.');
    }
}
