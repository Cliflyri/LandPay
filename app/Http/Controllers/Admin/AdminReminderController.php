<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{AdminNotice,AdminReminder};
use App\Services\AdminNoticeEmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Validation\Rule;
use Illuminate\View\View;
class AdminReminderController extends Controller{
 public function index():View{return view('admin.reminders.index',['reminders'=>AdminReminder::query()->latest()->get()]);}
 public function create():View{return view('admin.reminders.form',['reminder'=>new AdminReminder]);}
 public function store(Request $request):RedirectResponse{
  if($request->input('intent')==='test')return $this->testReminder($request);
  AdminReminder::query()->create($this->data($request)+['created_by_user_id'=>$request->user()->id,'updated_by_user_id'=>$request->user()->id]);
  return redirect()->route('admin.reminders.index')->with('success','Admin reminder created.');
 }
 public function edit(AdminReminder $reminder):View{return view('admin.reminders.form',compact('reminder'));}
 public function update(Request $request,AdminReminder $reminder):RedirectResponse{
  if($request->input('intent')==='test')return $this->testReminder($request);
  $request->validate(['reset_status'=>['nullable','boolean']]);
  $data=$this->data($request);
  DB::transaction(function()use($request,$reminder,$data):void{
   $reminder->update($data+['updated_by_user_id'=>$request->user()->id]);
   if($request->boolean('reset_status')){
    $reminder->occurrences()->where('period',$reminder->period(now()->timezone(config('app.timezone'))))->delete();
   }
  });
  return redirect()->route('admin.reminders.index')->with('success',$request->boolean('reset_status')?'Admin reminder saved and reset. It can run again when due.':'Admin reminder updated.');
 }
 public function destroy(AdminReminder $reminder):RedirectResponse{
  $reminder->delete();return redirect()->route('admin.reminders.index')->with('success','Admin reminder deleted.');
 }
 public function dismissTest(Request $request):RedirectResponse{
  $request->session()->forget('admin_reminder_test.'.$request->user()->id);
  return back()->with('success','Test reminder dismissed.');
 }
 private function testReminder(Request $request):RedirectResponse{
  $data=$request->validate([
   'title'=>['required','string','max:150'],'message'=>['nullable','string','max:1000'],
   'destination'=>['required',Rule::in(array_keys(AdminReminder::DESTINATIONS))],
  ]);
  $preview=new AdminReminder($data);
  $request->session()->put('admin_reminder_test.'.$request->user()->id,[
   'title'=>$preview->title,'message'=>$preview->message,'url'=>$preview->destinationUrl(),'destination'=>$preview->destinationLabel(),
  ]);
  $notice=new AdminNotice(['type'=>'scheduled_reminder','title'=>'[TEST] '.$preview->title,'message'=>$preview->message?:$preview->title]);
  $notice->setAttribute('action_url',$preview->destinationUrl());
  $sent=app(AdminNoticeEmailService::class)->send($notice,true);
  return back()->withInput($request->except('_token','_method','intent','reset_status'))->with($sent?'success':'error',
   $sent?'Test email sent. Your dashboard test preview is ready; nothing was saved or reset.':'Dashboard test preview is ready, but the test email could not be sent. Check the admin email address and mail configuration. Nothing was saved or reset.');
 }
 private function data(Request $request):array{
  $data=$request->validate([
   'title'=>['required','string','max:150'],'message'=>['nullable','string','max:1000'],
   'recurrence_type'=>['sometimes',Rule::in(['weekly','monthly','annually'])],
   'day_of_week'=>['nullable','required_if:recurrence_type,weekly','integer','between:1,7'],
   'month_of_year'=>['nullable','required_if:recurrence_type,annually','integer','between:1,12'],
   'day_of_month'=>['required_unless:recurrence_type,weekly','integer','between:1,31'],'display_time'=>['required','date_format:H:i'],
   'destination'=>['sometimes',Rule::in(array_keys(AdminReminder::DESTINATIONS))],
   'send_email'=>['nullable','boolean'],'active'=>['nullable','boolean'],
  ]);
  return $data+['day_of_month'=>1,'recurrence_type'=>'monthly','destination'=>'notification_only','send_email'=>$request->boolean('send_email'),'active'=>$request->boolean('active')];
 }
}
