<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\PropertyTaxBatch;
use App\Integrations\LandInvest\Connection;
use App\Integrations\LandInvest\ExportConnection;
use App\Services\AdminReminderService;
use Illuminate\Http\Request;
use Illuminate\View\View;
class AdminActionController extends Controller
{
 public function __invoke(Request $request,AdminReminderService $reminders,Connection $landInvestImport,ExportConnection $landInvestExport):View
 {
  return view('admin.actions.index',['landInvestImportEnabled'=>$landInvestImport->metadata()['enabled'],'landInvestExportEnabled'=>$landInvestExport->metadata()['enabled'],'taxCounts'=>['draft'=>PropertyTaxBatch::where('status','draft')->count(),'partial'=>PropertyTaxBatch::where('status','partially_issued')->count(),'failed'=>PropertyTaxBatch::whereHas('rows',fn($q)=>$q->where('email_status','failed'))->count()],'adminReminderCounts'=>['due'=>$reminders->dueFor($request->user())->count(),'active'=>$reminders->activeCount()]]);
 }
}
