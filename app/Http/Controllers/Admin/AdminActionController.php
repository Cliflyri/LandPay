<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\PropertyTaxBatch;
use App\Services\AdminReminderService;
use Illuminate\Http\Request;
use Illuminate\View\View;
class AdminActionController extends Controller
{
 public function __invoke(Request $request,AdminReminderService $reminders):View
 {
  return view('admin.actions.index',['taxCounts'=>['draft'=>PropertyTaxBatch::where('status','draft')->count(),'partial'=>PropertyTaxBatch::where('status','partially_issued')->count(),'failed'=>PropertyTaxBatch::whereHas('rows',fn($q)=>$q->where('email_status','failed'))->count()],'adminReminderCounts'=>['due'=>$reminders->dueFor($request->user())->count(),'active'=>$reminders->activeCount()]]);
 }
}
