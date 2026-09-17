<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\AdminReminderOccurrence;
use Illuminate\Http\{RedirectResponse,Request};
class AdminReminderDismissalController extends Controller{
 public function __invoke(Request $request,AdminReminderOccurrence $occurrence):RedirectResponse{
  $occurrence->dismissals()->firstOrCreate(['user_id'=>$request->user()->id],['dismissed_at'=>now()]);
  return back()->with('success','Reminder dismissed for this month.');
 }
}
