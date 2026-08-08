<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AdminNotice;
use App\Models\ClientPaymentIntent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class AdminNoticeController extends Controller {public function dismiss(Request $request,AdminNotice $notice): RedirectResponse {DB::transaction(function()use($request,$notice):void{$locked=AdminNotice::query()->lockForUpdate()->findOrFail($notice->id);$locked->update(['dismissed_by_user_id'=>$request->user()->id,'dismissed_at'=>now()]);if($locked->client_payment_intent_id){ClientPaymentIntent::query()->whereKey($locked->client_payment_intent_id)->where('status','announced')->update(['status'=>'cancelled','cancelled_at'=>now()]);}});return back()->with('success','Notice dismissed.');}}
