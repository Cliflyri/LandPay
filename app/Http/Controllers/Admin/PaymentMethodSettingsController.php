<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\PaymentMethodConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
class PaymentMethodSettingsController extends Controller {
 public function __construct(private readonly PaymentMethodConfigurationService $configuration){}
 public function index(): View{return view('admin.settings.payment-methods',['general'=>$this->configuration->general(),'methods'=>$this->configuration->all(),'square'=>$this->provider('square'),'stripe'=>$this->provider('stripe')]);}
 public function updateGeneral(Request $request): RedirectResponse {
  $data=$request->validate(['card_provider'=>['required',Rule::in(['disabled','square','stripe'])],'intent_expiry_days'=>['required','integer','between:1,90']]);
  AppSetting::putMany(['client_payments_enabled'=>$request->boolean('enabled')?'1':'0','client_payments_custom_amount'=>$request->boolean('allow_custom_amount')?'1':'0','card_provider'=>$data['card_provider'],'payment_intent_expiry_days'=>(string)$data['intent_expiry_days']]);
  return back()->with('success','Client payment settings saved.');
 }
 public function updateMethod(Request $request,string $method): RedirectResponse {
  abort_unless(in_array($method,PaymentMethodConfigurationService::METHODS,true),404);
  $data=$request->validate(['name'=>['required','string','max:80'],'instructions'=>['nullable','string','max:5000'],'recipient'=>['nullable','string','max:255'],'link'=>['nullable','url','max:1000'],'image_url'=>['nullable','url','starts_with:https://','max:1000'],'button'=>['required','string','max:100']]);
  AppSetting::putMany(["payment_{$method}_enabled"=>$request->boolean('enabled')?'1':'0',"payment_{$method}_name"=>$data['name'],"payment_{$method}_instructions"=>$data['instructions']??'',"payment_{$method}_recipient"=>$data['recipient']??'',"payment_{$method}_link"=>$data['link']??'',"payment_{$method}_image_url"=>$data['image_url']??'',"payment_{$method}_button"=>$data['button']]);
  return back()->with('success',$data['name'].' settings saved.');
 }
 public function updateProvider(Request $request,string $provider): RedirectResponse {
  abort_unless(in_array($provider,['square','stripe'],true),404);
  $data=$request->validate(['environment'=>['required',Rule::in(['sandbox','live'])],'public_id'=>['nullable','string','max:255'],'webhook_secret'=>['nullable','string','max:1000'],'api_secret'=>['nullable','string','max:2000']]);
  AppSetting::putMany(["{$provider}_environment"=>$data['environment'],"{$provider}_public_id"=>$data['public_id']??'']);
  foreach(['webhook_secret','api_secret'] as $key)if(filled($data[$key]??null))AppSetting::putEncrypted("{$provider}_{$key}",$data[$key]);
  return back()->with('success',ucfirst($provider).' settings saved.');
 }
 private function provider(string $provider): array{return ['environment'=>AppSetting::valueFor("{$provider}_environment",'sandbox'),'public_id'=>AppSetting::valueFor("{$provider}_public_id",''),'api_secret_set'=>filled(AppSetting::encryptedValueFor("{$provider}_api_secret")),'webhook_secret_set'=>filled(AppSetting::encryptedValueFor("{$provider}_webhook_secret"))];}
}