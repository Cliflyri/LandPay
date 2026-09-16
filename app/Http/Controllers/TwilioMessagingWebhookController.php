<?php
namespace App\Http\Controllers;
use App\Models\Client;
use App\Services\{ClientSmsPreferenceService,PhoneNumberService,TwilioConfigurationService};
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Twilio\Security\RequestValidator;
class TwilioMessagingWebhookController extends Controller {
 public function __construct(private readonly TwilioConfigurationService $configuration,private readonly PhoneNumberService $phones,private readonly ClientSmsPreferenceService $preferences){}
 public function __invoke(Request $request):Response{
  $config=$this->configuration->values();
  abort_unless($config['auth_token']&&(new RequestValidator($config['auth_token']))->validate((string)$request->header('X-Twilio-Signature'),$request->fullUrl(),$request->all()),403);
  if(strtoupper((string)$request->input('OptOutType'))==='STOP'){$phone=$this->phones->e164($request->input('From'));if($phone){$client=Client::query()->whereHas('smsPreference',fn($q)=>$q->where('sms_phone_e164',$phone))->first();if($client)$this->preferences->stop($client,$phone);}}
  return response('',204);
 }
}
