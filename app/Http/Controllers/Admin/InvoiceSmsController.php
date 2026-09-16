<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceSmsService;
use Illuminate\Http\{RedirectResponse,Request};
class InvoiceSmsController extends Controller {
 public function __construct(private readonly InvoiceSmsService $sms){}
 public function store(Request $request,Invoice $invoice):RedirectResponse{$delivery=$this->sms->sendReminder($invoice,$request->user());return back()->with('success','SMS reminder sent to '.$delivery->recipient_phone.'.');}
}
