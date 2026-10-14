<?php
namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\InvoiceReminderController;
use App\Models\{Client,Invoice,InvoiceReminder,SmsDelivery,User};
use App\Services\{InvoiceReminderService,InvoiceSmsService};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DashboardReminderActionTest extends TestCase
{
    private function runAction(bool $combined, bool $eligible=true, bool $smsFails=false, bool $emailFails=false): array
    {
        $invoice=new Invoice;$invoice->setRawAttributes(['id'=>1]);
        $user=new User;$user->setRawAttributes(['id'=>1]);
        $client=new Client;
        $reminder=new InvoiceReminder;$reminder->setRawAttributes(['id'=>42]);
        $email=$this->mock(InvoiceReminderService::class);
        $sms=$this->mock(InvoiceSmsService::class);
        if($combined){$sms->shouldReceive('recipient')->once()->with($invoice)->andReturn($client);$sms->shouldReceive('eligible')->once()->with($client,false)->andReturn($eligible);}
        if(!$combined||$eligible){
            $expect=$email->shouldReceive('send')->once()->with($invoice,$user);
            if($emailFails)$expect->andThrow(ValidationException::withMessages(['reminder'=>'Delivery failed.']));else $expect->andReturn($reminder);
        }else $email->shouldNotReceive('send');
        if($combined&&$eligible&&!$emailFails){
            $expect=$sms->shouldReceive('sendReminder')->once()->with($invoice,$user,$reminder,'manual-combined-reminder:42');
            if($smsFails)$expect->andThrow(ValidationException::withMessages(['sms'=>'Failed']));else $expect->andReturn(new SmsDelivery);
        }else $sms->shouldNotReceive('sendReminder');
        $request=Request::create('/reminders','POST',$combined?['include_sms'=>'1']:[]);
        $request->headers->set('Accept','application/json');$request->setUserResolver(fn()=>$user);
        return (new InvoiceReminderController($email,$sms))->store($request,$invoice)->getData(true);
    }
    public function test_email_only_explicitly_reports_no_sms():void{$this->assertSame(['message'=>'Email sent. No SMS sent.','level'=>'success'],$this->runAction(false));}
    public function test_combined_success():void{$this->assertSame('Email sent. SMS submitted.',$this->runAction(true)['message']);}
    public function test_sms_failure_preserves_email_success():void{$r=$this->runAction(true,true,true);$this->assertSame('warning',$r['level']);$this->assertStringContainsString('Email sent. SMS failed.',$r['message']);}
    public function test_ineligible_recipient_sends_neither_channel():void{$r=$this->runAction(true,false);$this->assertSame('danger',$r['level']);$this->assertStringContainsString('Email not sent. SMS not sent:',$r['message']);}
    public function test_email_failure_does_not_send_sms():void{$r=$this->runAction(true,true,false,true);$this->assertSame('danger',$r['level']);$this->assertStringContainsString('Email failed. No SMS sent.',$r['message']);}
}
