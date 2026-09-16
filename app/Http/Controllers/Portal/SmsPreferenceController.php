<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Services\{ClientSmsPreferenceService,TwilioConfigurationService};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Validation\ValidationException;
class SmsPreferenceController extends Controller {
 public function __construct(private readonly ClientSmsPreferenceService $preferences,private readonly TwilioConfigurationService $twilio){}
 public function update(Request $request):RedirectResponse{$enabled=$request->boolean('sms_enabled');if($enabled&&!$this->twilio->values()['enabled'])throw ValidationException::withMessages(['sms_enabled'=>$this->twilio->values()['disabled_notice']]);$account=$request->user('client')->load('client');$this->preferences->set($account->client,$enabled,'portal',$request,account:$account);return back()->with('status',$enabled?'Text-message notifications enabled.':'Text-message notifications disabled.');}
}

