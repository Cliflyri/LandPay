<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AdminReminder;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Validation\Rule;
use Illuminate\View\View;
class AdminReminderController extends Controller{
 public function index():View{return view('admin.reminders.index',['reminders'=>AdminReminder::query()->latest()->get()]);}
 public function create():View{return view('admin.reminders.form',['reminder'=>new AdminReminder]);}
 public function store(Request $request):RedirectResponse{
  AdminReminder::query()->create($this->data($request)+['created_by_user_id'=>$request->user()->id,'updated_by_user_id'=>$request->user()->id]);
  return redirect()->route('admin.reminders.index')->with('success','Admin reminder created.');
 }
 public function edit(AdminReminder $reminder):View{return view('admin.reminders.form',compact('reminder'));}
 public function update(Request $request,AdminReminder $reminder):RedirectResponse{
  $reminder->update($this->data($request)+['updated_by_user_id'=>$request->user()->id]);
  return redirect()->route('admin.reminders.index')->with('success','Admin reminder updated.');
 }
 public function destroy(AdminReminder $reminder):RedirectResponse{
  $reminder->delete();return redirect()->route('admin.reminders.index')->with('success','Admin reminder deleted.');
 }
 private function data(Request $request):array{
  $data=$request->validate([
   'title'=>['required','string','max:150'],'message'=>['nullable','string','max:1000'],
   'day_of_month'=>['required','integer','between:1,31'],'display_time'=>['required','date_format:H:i'],
   'destination'=>['required',Rule::in(array_keys(AdminReminder::DESTINATIONS))],
   'send_email'=>['nullable','boolean'],'active'=>['nullable','boolean'],
  ]);
  return $data+['send_email'=>$request->boolean('send_email'),'active'=>$request->boolean('active')];
 }
}
