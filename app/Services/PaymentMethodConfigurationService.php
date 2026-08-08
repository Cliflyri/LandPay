<?php
namespace App\Services;
use App\Models\AppSetting;
class PaymentMethodConfigurationService {
 public const METHODS=['zelle','cash_app','venmo','chime','card','check','money_order','other'];
 public function all(): array{return collect(self::METHODS)->mapWithKeys(fn($method)=>[$method=>$this->method($method)])->all();}
 public function method(string $method): array {
  abort_unless(in_array($method,self::METHODS,true),404);
  $defaults=[
   'zelle'=>['name'=>'Zelle','button'=>'I sent this payment','recommended'=>true],
   'cash_app'=>['name'=>'Cash App','button'=>'I sent this payment'],
   'venmo'=>['name'=>'Venmo','button'=>'I sent this payment'],
   'chime'=>['name'=>'Chime','button'=>'I sent this payment'],
   'card'=>['name'=>'Credit or debit card','button'=>'Continue to secure checkout'],
   'check'=>['name'=>'Check','button'=>'I mailed this payment'],
   'money_order'=>['name'=>'Money order','button'=>'I mailed this payment'],
   'other'=>['name'=>'Other','button'=>'I will make this payment'],
  ][$method];
  return [
   'key'=>$method,'enabled'=>AppSetting::valueFor("payment_{$method}_enabled",$method==='zelle'?'1':'0')==='1',
   'name'=>AppSetting::valueFor("payment_{$method}_name",$defaults['name']),
   'instructions'=>AppSetting::valueFor("payment_{$method}_instructions",''),
   'recipient'=>AppSetting::valueFor("payment_{$method}_recipient",''),
   'link'=>AppSetting::valueFor("payment_{$method}_link",''),
   'button'=>AppSetting::valueFor("payment_{$method}_button",$defaults['button']),
   'image_url'=>AppSetting::valueFor("payment_{$method}_image_url",''),
   'recommended'=>$defaults['recommended']??false,
  ];
 }
 public function enabled(): array{return collect($this->all())->filter(fn($m)=>$m['enabled'])->values()->all();}
 public function general(): array{return [
  'enabled'=>AppSetting::valueFor('client_payments_enabled','0')==='1',
  'allow_custom_amount'=>AppSetting::valueFor('client_payments_custom_amount','1')==='1',
  'card_provider'=>AppSetting::valueFor('card_provider','disabled'),
  'intent_expiry_days'=>(int)AppSetting::valueFor('payment_intent_expiry_days','14'),
 ];}
}