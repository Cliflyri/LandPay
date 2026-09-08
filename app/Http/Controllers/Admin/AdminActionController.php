<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\PropertyTaxBatch;
use Illuminate\View\View;
class AdminActionController extends Controller
{
 public function __invoke():View
 {
  return view('admin.actions.index',['taxCounts'=>['draft'=>PropertyTaxBatch::where('status','draft')->count(),'partial'=>PropertyTaxBatch::where('status','partially_issued')->count(),'failed'=>PropertyTaxBatch::whereHas('rows',fn($q)=>$q->where('email_status','failed'))->count()]]);
 }
}
